<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Services\PosTransactionService;
use Exception;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService,
        protected \App\Services\ShiftService $shiftService
    ) {}

    /**
     * Penjualan Retail POS Dual-Pane
     */
    public function retail()
    {
        $user     = auth()->user();
        $outletId = $user?->outlet_id;

        // Products with stock > 0 in this outlet (all shown for admin)
        $products = $this->getProductsForOutlet($outletId);

        // Customers scoped to this outlet
        $customers = Customer::where('status', 'Aktif')
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->orderBy('name')
            ->get();

        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->orderBy('code')->get();
        $expenseAccounts = Account::whereIn('group', ['BIAYA', 'BIAYA LAIN', 'KEWAJIBAN'])
            ->where('type', 'D')
            ->orderBy('code')
            ->get();

        $recentSales = Sale::where('sale_type', 'retail')
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->with(['customer', 'items.product'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('pos.retail', compact('products', 'customers', 'accounts', 'expenseAccounts', 'recentSales'));
    }

    /**
     * Penjualan Grosir POS Dual-Pane
     */
    public function wholesale()
    {
        $user     = auth()->user();
        $outletId = $user?->outlet_id;

        $products  = $this->getProductsForOutlet($outletId);

        $customers = Customer::where('status', 'Aktif')
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->orderBy('name')
            ->get();

        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();
        $expenseAccounts = Account::whereIn('group', ['BIAYA', 'BIAYA LAIN', 'KEWAJIBAN'])
            ->where('type', 'D')
            ->orderBy('code')
            ->get();

        $recentSales = Sale::where('sale_type', 'grosir')
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->with(['customer', 'items.product'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('pos.wholesale', compact('products', 'customers', 'accounts', 'expenseAccounts', 'recentSales'));
    }

    /**
     * Load products for the POS screen.
     * Each product gets an `outlet_stock` attribute = stock in this outlet.
     * Products are sorted: in-stock first, then by name.
     */
    private function getProductsForOutlet(?int $outletId): \Illuminate\Database\Eloquent\Collection
    {
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();

        if ($outletId) {
            $outletStocks = ProductStock::where('outlet_id', $outletId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('stock', 'product_id');

            $products->each(function ($p) use ($outletStocks) {
                $p->outlet_stock = (float) ($outletStocks[$p->id] ?? 0);
            });

            // Sort: in-stock first
            $products = $products->sortByDesc('outlet_stock')->values();
        } else {
            $products->each(fn($p) => $p->outlet_stock = (float) $p->stock);
        }

        return $products;
    }

    /**
     * POS Checkout Action
     */
    public function checkout(Request $request)
    {
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
                'message' => 'Transaksi berhasil disimpan!',
                'sale' => $sale,
                'redirect_url' => route('receipt.thermal', $sale->id),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Tarik Tunai di Kasir POS (Pilih sumber uang kas laci/rekening)
     */
    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'source_account_id' => 'required|exists:accounts,id', // Kas laci / Bank yang berkurang diberikan ke nasabah
            'amount' => 'required|numeric|min:1000',
            'admin_fee' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $fee = (float) ($data['admin_fee'] ?? 0);
        $amount = (float) $data['amount'];
        $customerName = $data['customer_name'] ?? 'Pelanggan';

        // Rekening transit/masuk dari transfer nasabah (CASH TRANSFER atau Rekening Bank penampung)
        $bankAcc = Account::where('code', '1-1113')->first() // default SALDO BCA
            ?: Account::where('code', '1-1111')->first() // CASH TRANSFER
            ?: Account::find($data['source_account_id']);

        try {
            $trxNumber = $this->posService->generateTransactionNumber('TT');

            $trx = \App\Models\CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'TRANSFER',
                'date' => now(),
                'outlet_id' => auth()->user()->outlet_id ?? \App\Models\Outlet::where('status', 'active')->value('id'),
                'user_id' => auth()->id(),
                'debit_account_id' => $bankAcc->id, // Bank/Transit bertambah (Uang transfer nasabah masuk)
                'credit_account_id' => $data['source_account_id'], // Kas Laci berkurang (Uang fisik diberikan ke nasabah)
                'amount' => $amount,
                'admin_fee' => $fee,
                'notes' => "Tarik Tunai [{$customerName}] - " . ($data['notes'] ?? 'POS Kasir'),
            ]);

            app(\App\Services\AccountingService::class)->recordCashTransaction($trx);

            return response()->json([
                'success' => true,
                'message' => "Tarik Tunai Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat!",
                'transaction' => $trx,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses tarik tunai: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Halaman Rekap Shift & Setor Penjualan (Web)
     */
    public function shift(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $outlets = $isAdmin ? \App\Models\Outlet::where('status', 'active')->orderBy('name')->get() : collect([$user->outlet])->filter();

        $selectedOutletId = $isAdmin && $request->has('outlet_id')
            ? ($request->outlet_id ? (int) $request->outlet_id : null)
            : ($user->outlet_id ?? ($outlets->first()?->id));

        $summary = $this->shiftService->getShiftSummary($selectedOutletId, $user->id);
        $history = $this->shiftService->getShiftHistory($selectedOutletId, 20);

        return view('pos.shift', compact('summary', 'history', 'outlets', 'selectedOutletId', 'isAdmin'));
    }

    /**
     * Proses Setor & Ganti Shift via Web
     */
    public function closeShiftWeb(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $outletId = $isAdmin && $request->has('outlet_id')
            ? ($request->outlet_id ? (int) $request->outlet_id : null)
            : $user->outlet_id;

        $retailDeposit = (float) ($request->cash_retail_deposit ?? 0);
        $multiDeposit = (float) ($request->cash_multi_deposit ?? 0);
        $transferDeposit = (float) ($request->cash_transfer_deposit ?? 0);

        $retailRetained = (float) ($request->cash_retail_retained ?? 400000);
        $multiRetained = (float) ($request->cash_multi_retained ?? 0);
        $transferRetained = (float) ($request->cash_transfer_retained ?? 0);

        $totalDeposit = $retailDeposit + $multiDeposit + $transferDeposit;

        try {
            $shiftLog = $this->shiftService->closeShift([
                'cash_retail_deposit' => $retailDeposit,
                'cash_retail_retained' => $retailRetained,
                'cash_multi_deposit' => $multiDeposit,
                'cash_multi_retained' => $multiRetained,
                'cash_transfer_deposit' => $transferDeposit,
                'cash_transfer_retained' => $transferRetained,
                'notes' => $request->notes ?? 'Tutup Shift Web',
            ], $outletId, $user->id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ganti Shift & Setor berhasil! Total disetor: Rp ' . number_format($totalDeposit, 0, ',', '.'),
                    'shift_log' => $shiftLog,
                    'print_url' => route('receipt.thermal_shift', $shiftLog->id),
                ]);
            }

            return redirect()->route('pos.shift', ['outlet_id' => $outletId])
                ->with('success', 'Ganti Shift & Setor Rp ' . number_format($totalDeposit, 0, ',', '.') . ' berhasil!');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal memproses tutup shift: ' . $e->getMessage());
        }
    }
}
