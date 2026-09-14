<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AgentTransfer;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\FinancialReportService;
use App\Services\PosTransactionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileApiController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService,
        protected AccountingService $accountingService,
        protected FinancialReportService $reportService
    ) {}

    /**
     * Mobile Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi tidak valid.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda sedang dinonaktifkan.',
            ], 403);
        }

        // Generate simple API token
        $token = bin2hex(random_bytes(32));
        $user->remember_token = $token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'store_name' => $user->store_name,
                'phone' => $user->phone,
                'permissions' => $user->permissions,
            ],
        ]);
    }

    /**
     * Dashboard Summary Stats
     */
    public function dashboard(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $today = Carbon::today();

        $totalSalesToday = Sale::whereDate('sale_date', $today)->sum('grand_total');
        $trxCountToday = Sale::whereDate('sale_date', $today)->count();
        $pendingTransfers = AgentTransfer::where('status', 'pending')->count();
        $totalProducts = Product::count();

        $recentTransfers = AgentTransfer::with(['user', 'processedBy'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'user' => $user,
            'stats' => [
                'sales_today' => (float) $totalSalesToday,
                'trx_today' => $trxCountToday,
                'pending_transfers' => $pendingTransfers,
                'total_products' => $totalProducts,
            ],
            'recent_transfers' => $recentTransfers,
        ]);
    }

    /**
     * Master Data: Products Catalog
     */
    public function products(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $search = $request->query('q');
        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
        }

        $products = $query->orderBy('name')->take(200)->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Master Data: Customers
     */
    public function customers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $customers = Customer::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $customers]);
    }

    /**
     * Master Data: Suppliers
     */
    public function suppliers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $suppliers = Supplier::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    /**
     * Master Data: Accounts (Bagan Akun / Kas)
     */
    public function accounts(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $accounts = Account::orderBy('code')->get();
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    /**
     * POS Data Helper (For Retail & Wholesale checkout form)
     */
    public function posData(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $customers = Customer::where('status', 'Aktif')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        return response()->json([
            'success' => true,
            'products' => $products,
            'customers' => $customers,
            'accounts' => $accounts,
        ]);
    }

    /**
     * POS Checkout (Retail / Grosir)
     */
    public function posCheckout(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'sale_type' => 'required|in:retail,grosir',
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);

        try {
            $sale = $this->posService->checkoutPos($data);
            return response()->json([
                'success' => true,
                'message' => 'Transaksi POS berhasil disimpan!',
                'sale' => $sale,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Digital / Pulsa Product List & Balance
     */
    public function digitalData(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $products = DigitalProduct::where('status', 'OPEN')->orderBy('name')->get();
        $depositAccounts = Account::whereIn('code', ['1-1131', '1-1113', '1-1120'])->get();
        $cashAccounts = Account::whereIn('code', ['1-1110', '1-1112'])->get();

        $saldoMulti = Account::where('code', '1-1131')->value('current_balance') ?? 0;

        return response()->json([
            'success' => true,
            'products' => $products,
            'deposit_accounts' => $depositAccounts,
            'cash_accounts' => $cashAccounts,
            'saldo_multi' => (float) $saldoMulti,
        ]);
    }

    /**
     * Digital / Pulsa Checkout
     */
    public function digitalCheckout(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'digital_product_id' => 'required|exists:digital_products,id',
            'customer_number' => 'required|string',
            'deposit_account_id' => 'required|exists:accounts,id',
            'cash_account_id' => 'required|exists:accounts,id',
            'selling_price' => 'nullable|numeric|min:0',
            'hpp' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processDigitalSale($data);
            return response()->json([
                'success' => true,
                'message' => 'Transaksi Pulsa / Elektrik berhasil!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Agent Transfer List
     */
    public function transfers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $query = AgentTransfer::with(['user', 'processedBy', 'sourceAccount'])->latest();

        if ($user->isToko()) {
            $query->where('user_id', $user->id);
        }

        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $transfers = $query->take(50)->get();
        $bankAccounts = Account::where('group', 'like', '%AKTIVA%')->where('type', 'D')->take(15)->get();

        return response()->json([
            'success' => true,
            'data' => $transfers,
            'bank_accounts' => $bankAccounts,
        ]);
    }

    /**
     * Store Agent Transfer Request
     */
    public function storeTransfer(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string|max:255',
        ]);

        $reference = 'TF-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $adminFee = 0;
        $totalAmount = $request->amount + $adminFee;

        $transfer = AgentTransfer::create([
            'reference_no' => $reference,
            'user_id' => $user->id,
            'bank_name' => strtoupper($request->bank_name),
            'account_number' => $request->account_number,
            'account_holder' => strtoupper($request->account_holder),
            'amount' => $request->amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan transfer berhasil dikirim!',
            'data' => $transfer,
        ]);
    }

    /**
     * Cash Flow: Kas Masuk / Kas Keluar
     */
    public function cashTransactions(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $type = $request->query('type'); // in / out / transfer
        $query = CashTransaction::with(['account', 'oppositeAccount'])->latest();

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->take(50)->get();
        return response()->json(['success' => true, 'data' => $transactions]);
    }

    /**
     * Cash Transaction Store (Kas Masuk & Kas Keluar)
     */
    public function storeCashTransaction(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'type' => 'required|in:in,out,transfer',
            'account_id' => 'required|exists:accounts,id',
            'opposite_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        $data['transaction_date'] = $data['transaction_date'] ?? date('Y-m-d H:i:s');

        try {
            $trx = $this->accountingService->recordCashTransaction($data);
            return response()->json([
                'success' => true,
                'message' => 'Transaksi kas berhasil dicatat!',
                'data' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Financial Report: Summary Overview
     */
    public function financialReports(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $profitLoss = $this->reportService->getProfitLossData($startDate, $endDate);
        $balanceSheet = $this->reportService->getBalanceSheetData($endDate);

        return response()->json([
            'success' => true,
            'profit_loss' => $profitLoss,
            'balance_sheet' => $balanceSheet,
        ]);
    }

    /**
     * Helper to authenticate token
     */
    private function getUserFromToken(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $token = substr($header, 7);
        return User::where('remember_token', $token)->first();
    }
}
