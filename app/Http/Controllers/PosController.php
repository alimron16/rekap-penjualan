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
}
