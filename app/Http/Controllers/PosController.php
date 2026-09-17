<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\PosTransactionService;
use Exception;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected PosTransactionService $posService
    ) {}

    /**
     * Penjualan Retail POS Dual-Pane
     */
    public function retail()
    {
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $customers = Customer::where('status', 'Aktif')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        $recentSales = Sale::where('sale_type', 'retail')
            ->with(['customer', 'items.product'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('pos.retail', compact('products', 'customers', 'accounts', 'recentSales'));
    }

    /**
     * Penjualan Grosir POS Dual-Pane
     */
    public function wholesale()
    {
        $products = Product::where('status', 'Masih Dijual')->orderBy('name')->get();
        $customers = Customer::where('status', 'Aktif')->orderBy('name')->get();
        $accounts = Account::where('group', 'AKTIVA')->whereIn('type', ['D'])->get();

        $recentSales = Sale::where('sale_type', 'grosir')
            ->with(['customer', 'items.product'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('pos.wholesale', compact('products', 'customers', 'accounts', 'recentSales'));
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
}
