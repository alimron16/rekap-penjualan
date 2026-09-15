<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AgentTransfer;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\MonthlyTarget;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\YearlyClosing;
use App\Services\AccountingService;
use App\Services\FinancialReportService;
use App\Services\PosTransactionService;
use App\Services\YearlyClosingService;
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
        protected FinancialReportService $reportService,
        protected YearlyClosingService $closingService
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

        $totalSalesToday = Sale::whereDate('date', $today)->sum('total');
        $trxCountToday = Sale::whereDate('date', $today)->count();
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

    // ==========================================
    // 1. MASTER DATA CRUD ENDPOINTS
    // ==========================================

    public function products(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $search = $request->query('q') ?? $request->query('search');
        $type = $request->query('type');
        $brand = $request->query('brand');

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'ALL' && $type !== 'SEMUA') {
            $query->where('type', $type);
        }

        if ($brand && $brand !== 'ALL') {
            $query->where('brand', $brand);
        }

        $products = $query->orderBy('name')->get();
        $categories = Category::whereIn('type', ['physical_type', 'physical_brand'])->orderBy('type')->orderBy('name')->get();
        $types = Category::where('type', 'physical_type')->pluck('name');
        $brands = Category::where('type', 'physical_brand')->pluck('name');

        return response()->json([
            'success' => true,
            'data' => $products,
            'categories' => $categories,
            'types' => $types,
            'brands' => $brands,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'item_code' => 'required|string|unique:products,item_code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'brand' => 'nullable|string',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'hpp' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Masih Dijual,Tidak Dijual',
        ]);

        if (empty($data['wholesale_price'])) {
            $data['wholesale_price'] = $data['retail_price'];
        }

        // Auto create category if not exists
        if (!empty($data['type'])) {
            Category::firstOrCreate(['type' => 'physical_type', 'name' => strtoupper(trim($data['type']))]);
        }
        if (!empty($data['brand'])) {
            Category::firstOrCreate(['type' => 'physical_brand', 'name' => strtoupper(trim($data['brand']))]);
        }

        $product = Product::create($data);
        return response()->json(['success' => true, 'message' => 'Produk berhasil ditambahkan!', 'data' => $product]);
    }

    public function multiProducts(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $products = DigitalProduct::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    public function customers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $customers = Customer::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function storeCustomer(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        $customer = Customer::create($data);
        return response()->json(['success' => true, 'message' => 'Pelanggan berhasil disimpan!', 'data' => $customer]);
    }

    public function suppliers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $suppliers = Supplier::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    public function storeSupplier(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
        ]);

        $supplier = Supplier::create($data);
        return response()->json(['success' => true, 'message' => 'Supplier berhasil disimpan!', 'data' => $supplier]);
    }

    public function accounts(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $accounts = Account::orderBy('code')->get();
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function storeAccount(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'code' => 'required|string|unique:accounts,code|max:20',
            'name' => 'required|string|max:100',
            'group' => 'required|string|in:AKTIVA,KEWAJIBAN,MODAL,PENDAPATAN,HPP,BIAYA,PENDAPATAN LAIN,BIAYA LAIN',
            'type' => 'required|string|in:H,D,K',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $initial = (float) ($data['initial_balance'] ?? 0);
        $account = Account::create([
            'code' => strtoupper($data['code']),
            'name' => strtoupper($data['name']),
            'group' => $data['group'],
            'type' => $data['type'],
            'initial_balance' => $initial,
            'current_balance' => $initial,
            'is_system_locked' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Akun COA berhasil dibuat!', 'data' => $account]);
    }

    // ==========================================
    // 2. POS & PENJUALAN
    // ==========================================

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

    // ==========================================
    // 3. DIGITAL PULSA & PPOB
    // ==========================================

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
            return response()->json(['success' => true, 'message' => 'Transaksi Pulsa / Elektrik berhasil!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 4. PIUTANG & RETUR PENJUALAN
    // ==========================================

    public function receivables(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $unpaidSales = Sale::where('status', 'BELUM LUNAS')->with('customer')->orderByDesc('date')->get();
        $payments = ReceivablePayment::with(['customer', 'account'])->latest()->take(30)->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        return response()->json([
            'success' => true,
            'unpaid_sales' => $unpaidSales,
            'payments' => $payments,
            'accounts' => $accounts,
        ]);
    }

    public function storeReceivablePayment(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'sale_id' => 'required|exists:sales,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processReceivablePayment($data);
            return response()->json(['success' => true, 'message' => 'Pembayaran piutang berhasil dicatat!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function returns(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $returns = SaleReturn::with(['sale', 'customer', 'product', 'account'])->latest()->take(30)->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $customers = Customer::where('status', 'Aktif')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'returns' => $returns,
            'products' => $products,
            'accounts' => $accounts,
            'customers' => $customers,
        ]);
    }

    public function storeReturn(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'refund_amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processSaleReturn($data);
            return response()->json(['success' => true, 'message' => 'Retur penjualan berhasil dicatat!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 5. PEMBELIAN & PEMBAYARAN HUTANG
    // ==========================================

    public function purchases(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $purchases = Purchase::with(['supplier', 'items.product'])->latest()->take(30)->get();
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $debts = Purchase::where('status', 'BELUM LUNAS')->with('supplier')->get();

        return response()->json([
            'success' => true,
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'products' => $products,
            'accounts' => $accounts,
            'debts' => $debts,
        ]);
    }

    public function storePurchase(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|exists:accounts,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.buy_price' => 'required|numeric|min:0',
        ]);

        try {
            $purchase = $this->posService->processPurchase($data);
            return response()->json(['success' => true, 'message' => 'Faktur pembelian berhasil disimpan!', 'data' => $purchase]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeDebtPayment(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'date' => 'required|date',
            'purchase_id' => 'required|exists:purchases,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processDebtPayment($data);
            return response()->json(['success' => true, 'message' => 'Pembayaran hutang berhasil dicatat!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 6. PERSEDIAAN STOK (INVENTORY)
    // ==========================================

    public function inventoryAdjustments(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $adjustments = InventoryAdjustment::with('product')->latest()->take(50)->get();
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'adjustments' => $adjustments,
            'products' => $products,
        ]);
    }

    public function storeInventoryAdjustment(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'type' => 'required|in:IN,OUT',
            'cost_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->posService->processInventoryAdjustment($data);
            return response()->json(['success' => true, 'message' => 'Penyesuaian stok berhasil disimpan!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 7. TRANSFER ANTAR AGEN & BANK
    // ==========================================

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

    public function approveTransfer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transfer = AgentTransfer::findOrFail($id);

        $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'proof_image' => 'nullable|image|max:10240',
            'notes' => 'nullable|string|max:255',
        ]);

        $path = $request->hasFile('proof_image')
            ? $request->file('proof_image')->store('proofs', 'public')
            : null;

        $sourceAccount = Account::findOrFail($request->source_account_id);

        $transfer->update([
            'status' => 'approved',
            'processed_by' => $user->id,
            'source_account_id' => $sourceAccount->id,
            'proof_image' => $path ?? $transfer->proof_image,
            'notes' => $request->notes ?? $transfer->notes,
            'processed_at' => Carbon::now(),
        ]);

        $sourceAccount->current_balance -= $transfer->total_amount;
        $sourceAccount->save();

        return response()->json([
            'success' => true,
            'message' => 'Transfer berhasil disetujui & bukti tersimpan!',
            'data' => $transfer,
        ]);
    }

    public function rejectTransfer(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transfer = AgentTransfer::findOrFail($id);
        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $transfer->update([
            'status' => 'rejected',
            'processed_by' => $user->id,
            'notes' => $request->notes,
            'processed_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan transfer telah ditolak.',
            'data' => $transfer,
        ]);
    }

    public function checkPendingTransfers(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $pendingCount = AgentTransfer::where('status', 'pending')->count();
        $latest = AgentTransfer::where('status', 'pending')->with('user')->latest()->first();

        return response()->json([
            'success' => true,
            'count' => $pendingCount,
            'latest' => $latest ? [
                'id' => $latest->id,
                'ref' => $latest->reference_no,
                'user' => $latest->user?->name ?? 'Kasir Agen',
                'bank' => $latest->bank_name,
                'amount' => (float) $latest->amount,
                'time' => $latest->created_at->diffForHumans(),
            ] : null,
        ]);
    }

    // ==========================================
    // 8. AKUNTANSI & KAS
    // ==========================================

    public function cashTransactions(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $type = $request->query('type');
        $query = CashTransaction::with(['account', 'oppositeAccount'])->latest();

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->take(50)->get();
        return response()->json(['success' => true, 'data' => $transactions]);
    }

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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ==========================================
    // 9. LAPORAN KEUANGAN LENGKAP & CEPAT
    // ==========================================

    public function financialReports(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $profitLoss = $this->reportService->getProfitAndLoss($startDate, $endDate);
        $balanceSheet = $this->reportService->getBalanceSheet($endDate);

        $salesSummary = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->sum('total');
        $purchaseSummary = Purchase::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])->sum('total');

        return response()->json([
            'success' => true,
            'profit_loss' => $profitLoss,
            'balance_sheet' => $balanceSheet,
            'sales_summary' => (float) $salesSummary,
            'purchase_summary' => (float) $purchaseSummary,
        ]);
    }

    public function reportSales(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));
        $saleType = $request->query('sale_type', 'all');

        $query = Sale::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['customer', 'items.product']);

        if ($saleType !== 'all') {
            $query->where('sale_type', $saleType);
        }

        $sales = $query->orderByDesc('date')->take(100)->get();

        $totalQty = 0;
        $totalSubtotal = 0;
        $totalDiscount = 0;
        $totalFinal = 0;
        $totalPaid = 0;
        $totalReceivable = 0;

        foreach ($sales as $s) {
            $totalQty += $s->items->sum('qty');
            $totalSubtotal += $s->subtotal;
            $totalDiscount += $s->discount;
            $totalFinal += $s->total;
            $totalPaid += $s->paid_amount;
            $totalReceivable += $s->remaining_receivable;
        }

        return response()->json([
            'success' => true,
            'data' => $sales,
            'summary' => [
                'total_qty' => $totalQty,
                'total_subtotal' => (float) $totalSubtotal,
                'total_discount' => (float) $totalDiscount,
                'total_final' => (float) $totalFinal,
                'total_paid' => (float) $totalPaid,
                'total_receivable' => (float) $totalReceivable,
            ]
        ]);
    }

    public function reportPurchases(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $purchases = Purchase::whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['supplier', 'items.product'])
            ->orderByDesc('date')
            ->take(100)
            ->get();

        $totalQty = 0;
        $totalSubtotal = 0;
        $totalDiscount = 0;
        $totalPaid = 0;
        $totalDebt = 0;

        foreach ($purchases as $p) {
            $totalQty += $p->items->sum('qty');
            $totalSubtotal += $p->subtotal;
            $totalDiscount += $p->discount;
            $totalPaid += $p->paid_amount;
            $totalDebt += $p->remaining_debt;
        }

        return response()->json([
            'success' => true,
            'data' => $purchases,
            'summary' => [
                'total_qty' => $totalQty,
                'total_subtotal' => (float) $totalSubtotal,
                'total_discount' => (float) $totalDiscount,
                'total_paid' => (float) $totalPaid,
                'total_debt' => (float) $totalDebt,
            ]
        ]);
    }

    public function reportCash(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $kasMasuk = CashTransaction::where('type', 'IN')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])
            ->orderByDesc('date')
            ->get();

        $kasKeluar = CashTransaction::where('type', 'OUT')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])
            ->orderByDesc('date')
            ->get();

        $kasTransfer = CashTransaction::where('type', 'TRANSFER')
            ->whereBetween('date', ["$startDate 00:00:00", "$endDate 23:59:59"])
            ->with(['debitAccount', 'creditAccount'])
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'success' => true,
            'kas_masuk' => $kasMasuk,
            'kas_keluar' => $kasKeluar,
            'kas_transfer' => $kasTransfer,
            'summary' => [
                'total_masuk' => (float) $kasMasuk->sum('amount'),
                'total_keluar' => (float) $kasKeluar->sum('amount'),
                'total_transfer' => (float) $kasTransfer->sum('amount'),
            ]
        ]);
    }

    public function reportProfitLoss(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $pl = $this->reportService->getProfitAndLoss($startDate, $endDate);
        return response()->json(['success' => true, 'data' => $pl]);
    }

    public function reportBalanceSheet(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $asOfDate = $request->query('as_of_date', date('Y-m-d'));
        $bs = $this->reportService->getBalanceSheet($asOfDate);
        return response()->json(['success' => true, 'data' => $bs]);
    }

    public function reportDebtsReceivables(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $debts = Purchase::where('status', 'BELUM LUNAS')
            ->where('remaining_debt', '>', 0)
            ->with('supplier')
            ->orderByDesc('date')
            ->get();

        $receivables = Sale::where('status', 'BELUM LUNAS')
            ->where('remaining_receivable', '>', 0)
            ->with('customer')
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'success' => true,
            'debts' => $debts,
            'total_debts' => (float) $debts->sum('remaining_debt'),
            'receivables' => $receivables,
            'total_receivables' => (float) $receivables->sum('remaining_receivable'),
        ]);
    }

    // ==========================================
    // 10. PENGATURAN, TUTUP BUKU & USERS
    // ==========================================

    public function users(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $users = User::orderByRaw("FIELD(role, 'super_admin', 'admin', 'toko')")->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function storeUser(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$user->isAdmin()) return response()->json(['error' => 'Unauthorized'], 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,admin,toko',
            'store_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'store_name' => $validated['store_name'],
            'phone' => $validated['phone'],
            'is_active' => true,
            'permissions' => [
                'master' => true,
                'purchase' => true,
                'pos' => true,
                'transfer' => true,
                'accounting' => true,
                'reports' => true,
                'settings' => true,
                'users' => true,
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Pengguna berhasil dibuat!', 'data' => $newUser]);
    }

    public function storeSettings(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $setting = StoreSetting::first();
        $closingHistory = YearlyClosing::latest()->take(5)->get();

        return response()->json(['success' => true, 'setting' => $setting, 'closing_history' => $closingHistory]);
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
