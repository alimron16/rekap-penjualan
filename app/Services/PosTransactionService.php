<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Exception;
use Illuminate\Support\Facades\DB;

class PosTransactionService
{
    public function __construct(
        protected AccountingService    $accountingService,
        protected HppCalculationService $hppService
    ) {}

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /** Generate unique transaction code: PREFIX-YYYYMMDD-His */
    public function generateTransactionNumber(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, date('Ymd'), date('His') . rand(10, 99));
    }

    /**
     * Resolve the outlet ID for the currently authenticated user.
     * Falls back to the first active outlet when the user has no outlet assigned.
     */
    private function resolveOutletId(?int $override = null): ?int
    {
        if ($override !== null) {
            return $override;
        }

        if (auth()->check()) {
            return auth()->user()->outlet_id
                ?? Outlet::where('status', 'active')->value('id');
        }

        return Outlet::where('status', 'active')->value('id');
    }

    /**
     * Resolve (or auto-create) the default "UMUM" customer for an outlet.
     * Each outlet owns its own UMUM record so customer scoping is respected.
     */
    private function resolveDefaultCustomer(?int $outletId): int
    {
        $query = Customer::where('name', 'UMUM');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $customer = $query->first();

        if (!$customer) {
            $customer = Customer::create([
                'name'      => 'UMUM',
                'outlet_id' => $outletId,
                'status'    => 'Aktif',
            ]);
        }

        return $customer->id;
    }

    // ----------------------------------------------------------------
    // POS Checkout (Retail & Grosir)
    // ----------------------------------------------------------------

    /**
     * Execute POS Checkout within an ACID transaction.
     *
     * Improvements over the old version:
     * - Stock check and deduction is now per-outlet (product_stocks)
     * - Global products.stock stays in sync (decremented in parallel)
     * - Default customer is outlet-aware
     * - Clearer error messages showing outlet name
     */
    public function checkoutPos(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $outletId = $this->resolveOutletId($data['outlet_id'] ?? null);

            $saleType = $data['sale_type'] ?? 'retail';
            $prefix   = $saleType === 'grosir' ? 'PG' : 'PR';
            $invoiceNumber = $this->generateTransactionNumber($prefix);

            // Resolve customer — honour outlet scope
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId) {
                $customerId = $this->resolveDefaultCustomer($outletId);
            }

            $discount      = (float) ($data['discount'] ?? 0);
            $paidAmount    = (float) ($data['paid_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'Tunai';
            $accountId     = $data['account_id'] ?? null;

            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new Exception('Keranjang belanja tidak boleh kosong!');
            }

            $subtotal          = 0;
            $saleItemsToInsert = [];

            // ----------------------------------------------------------
            // 1. Validate & reserve stock (per-outlet)
            // ----------------------------------------------------------
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty     = (float) ($item['qty'] ?? 1);

                if ($qty <= 0) {
                    continue;
                }

                // Lock the per-outlet stock row
                $outletStock = ProductStock::where('product_id', $product->id)
                    ->where('outlet_id', $outletId)
                    ->lockForUpdate()
                    ->first();

                $stockBefore = $outletStock ? (float) $outletStock->stock : 0.0;

                if ($stockBefore < $qty) {
                    $outletName = $outletId
                        ? (Outlet::find($outletId)?->name ?? "Outlet #{$outletId}")
                        : 'toko ini';

                    throw new Exception(
                        "Stok [{$product->name}] di {$outletName} tidak mencukupi! " .
                        "Sisa: " . (int) $stockBefore . " pcs, diminta: " . (int) $qty . " pcs."
                    );
                }

                $stockAfter = $stockBefore - $qty;

                // Deduct per-outlet stock
                if ($outletStock) {
                    $outletStock->update(['stock' => $stockAfter]);
                } else {
                    ProductStock::create([
                        'product_id' => $product->id,
                        'outlet_id'  => $outletId,
                        'stock'      => $stockAfter,
                    ]);
                }

                // Keep global products.stock in sync (decrement by qty sold)
                $product->decrement('stock', $qty);

                $sellingPrice  = (float) ($item['price'] ?? ($saleType === 'grosir' ? $product->wholesale_price : $product->retail_price));
                $itemSubtotal  = $qty * $sellingPrice;
                $subtotal     += $itemSubtotal;

                $saleItemsToInsert[] = [
                    'product_id'    => $product->id,
                    'qty'           => $qty,
                    'selling_price' => $sellingPrice,
                    'hpp'           => (float) $product->hpp,
                    'subtotal'      => $itemSubtotal,
                    'stock_before'  => $stockBefore,
                    'stock_after'   => $stockAfter,
                    'batch_id'      => 'B-' . date('Ymd'),
                ];
            }

            $total               = max(0, $subtotal - $discount);
            $remainingReceivable = max(0, $total - $paidAmount);
            $status              = $remainingReceivable <= 0 ? 'LUNAS' : 'BELUM LUNAS';

            // ----------------------------------------------------------
            // 2. Create Sale record
            // ----------------------------------------------------------
            $sale = Sale::create([
                'invoice_number'      => $invoiceNumber,
                'sale_type'           => $saleType,
                'date'                => now(),
                'customer_id'         => $customerId,
                'outlet_id'           => $outletId,
                'user_id'             => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'subtotal'            => $subtotal,
                'discount'            => $discount,
                'total'               => $total,
                'paid_amount'         => $paidAmount,
                'remaining_receivable'=> $remainingReceivable,
                'payment_method'      => $paymentMethod,
                'account_id'          => $accountId,
                'status'              => $status,
                'notes'               => $data['notes'] ?? null,
            ]);

            // ----------------------------------------------------------
            // 3. Create Sale Items
            // ----------------------------------------------------------
            foreach ($saleItemsToInsert as $itemData) {
                $itemData['sale_id'] = $sale->id;
                SaleItem::create($itemData);
            }

            // ----------------------------------------------------------
            // 4. Double-entry journal
            // ----------------------------------------------------------
            $this->accountingService->recordSale($sale);

            return $sale->load(['items.product', 'customer', 'account']);
        });
    }

    // ----------------------------------------------------------------
    // Digital Sale
    // ----------------------------------------------------------------

    public function processDigitalSale(array $data): DigitalSale
    {
        return DB::transaction(function () use ($data) {
            $outletId = $this->resolveOutletId($data['outlet_id'] ?? null);

            $digitalProduct = DigitalProduct::findOrFail($data['digital_product_id']);
            $sellingPrice   = (float) ($data['selling_price'] ?? $digitalProduct->selling_price);
            $hpp            = (float) ($data['hpp'] ?? $digitalProduct->hpp);
            $margin         = $sellingPrice - $hpp;

            $trxNumber = $this->generateTransactionNumber('PE');

            $digitalSale = DigitalSale::create([
                'transaction_number' => $trxNumber,
                'date'               => now(),
                'digital_product_id' => $digitalProduct->id,
                'customer_number'    => $data['customer_number'],
                'outlet_id'          => $outletId,
                'user_id'            => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'selling_price'      => $sellingPrice,
                'hpp'                => $hpp,
                'profit_margin'      => $margin,
                'deposit_account_id' => $data['deposit_account_id'],
                'cash_account_id'    => $data['cash_account_id'],
                'status'             => 'SUKSES',
                'notes'              => $data['notes'] ?? null,
            ]);

            $this->accountingService->recordDigitalSale($digitalSale);

            return $digitalSale;
        });
    }

    // ----------------------------------------------------------------
    // Digital Sale Reversal
    // ----------------------------------------------------------------

    public function reverseDigitalSale(DigitalSale $sale): DigitalSale
    {
        return DB::transaction(function () use ($sale) {
            if ($sale->status === 'GAGAL') {
                throw new Exception('Transaksi ini sudah berstatus GAGAL!');
            }

            $sale->update(['status' => 'GAGAL']);
            $this->accountingService->recordDigitalSaleReversal($sale);

            return $sale;
        });
    }

    // ----------------------------------------------------------------
    // Purchase (Kulakan) — now per-outlet
    // ----------------------------------------------------------------

    /**
     * Process a purchase of physical products.
     *
     * Improvements:
     * - outlet_id recorded on the purchase header
     * - Per-outlet stock incremented via HppCalculationService
     * - Global products.stock & HPP kept in sync automatically
     */
    public function processPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $outletId      = $this->resolveOutletId($data['outlet_id'] ?? null);
            $invoiceNumber = $data['invoice_number'] ?? $this->generateTransactionNumber('PB');
            $supplierId    = $data['supplier_id'] ?? null;
            $items         = $data['items'] ?? [];
            $discount      = (float) ($data['discount'] ?? 0);
            $paidAmount    = (float) ($data['paid_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'Tunai';
            $accountId     = $data['account_id'] ?? null;

            $subtotal          = 0;
            $purchaseItemsData = [];

            foreach ($items as $item) {
                $product  = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty      = (float) ($item['qty'] ?? 1);
                $buyPrice = (float) ($item['buy_price'] ?? $product->hpp);

                $lineSubtotal  = $qty * $buyPrice;
                $subtotal     += $lineSubtotal;

                // Moving-average HPP + per-outlet stock update
                $audit = $this->hppService->applyPurchase($product, $qty, $buyPrice, $outletId);

                $purchaseItemsData[] = [
                    'product_id'  => $product->id,
                    'qty'         => $qty,
                    'buy_price'   => $buyPrice,
                    'subtotal'    => $lineSubtotal,
                    'stock_before'=> $audit['stock_before'],
                    'hpp_before'  => $audit['hpp_before'],
                    'stock_after' => $audit['stock_after'],
                    'hpp_after'   => $audit['hpp_after'],
                    'batch_id'    => 'B-' . date('Ymd'),
                    'notes'       => $item['notes'] ?? null,
                ];
            }

            $total         = max(0, $subtotal - $discount);
            $remainingDebt = max(0, $total - $paidAmount);
            $status        = $remainingDebt <= 0 ? 'LUNAS' : 'BELUM LUNAS';

            $purchase = Purchase::create([
                'outlet_id'      => $outletId,
                'user_id'        => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'invoice_number' => $invoiceNumber,
                'date'           => $data['date'] ?? now()->format('Y-m-d'),
                'supplier_id'    => $supplierId,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => $total,
                'paid_amount'    => $paidAmount,
                'remaining_debt' => $remainingDebt,
                'payment_method' => $paymentMethod,
                'account_id'     => $accountId,
                'status'         => $status,
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($purchaseItemsData as $pid) {
                $pid['purchase_id'] = $purchase->id;
                PurchaseItem::create($pid);
            }

            $this->accountingService->recordPurchase($purchase);

            return $purchase;
        });
    }

    // ----------------------------------------------------------------
    // Kas Transfer
    // ----------------------------------------------------------------

    public function processKasTransfer(array $data): CashTransaction
    {
        return DB::transaction(function () use ($data) {
            $outletId  = $this->resolveOutletId($data['outlet_id'] ?? null);
            $trxNumber = $this->generateTransactionNumber('KT');
            $amount    = (float) ($data['amount'] ?? 0);
            $adminFee  = (float) ($data['admin_fee'] ?? 0);

            $trx = CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type'               => 'TRANSFER',
                'date'               => now(),
                'outlet_id'          => $outletId,
                'user_id'            => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'debit_account_id'   => $data['debit_account_id'],
                'credit_account_id'  => $data['credit_account_id'],
                'amount'             => $amount,
                'admin_fee'          => $adminFee,
                'notes'              => $data['notes'] ?? 'Kas Transfer',
            ]);

            $this->accountingService->recordCashTransaction($trx);

            return $trx;
        });
    }

    // ----------------------------------------------------------------
    // Inventory Adjustment (Item Masuk / Item Keluar / Stok Opname)
    // ----------------------------------------------------------------

    /**
     * Process an inventory adjustment for a specific outlet.
     *
     * Improvements:
     * - All stock reads/writes go through product_stocks (per-outlet)
     * - products.stock (global) is kept in sync via syncTotalStock()
     * - outlet_id + user_id stored on every adjustment row for traceability
     */
    public function processInventoryAdjustment(array $data): InventoryAdjustment
    {
        return DB::transaction(function () use ($data) {
            $outletId = $this->resolveOutletId($data['outlet_id'] ?? null);
            $type     = $data['type']; // IN | OUT | OPNAME
            $prefix   = match ($type) { 'IN' => 'IM', 'OUT' => 'IK', default => 'SO' };
            $adjNumber = $this->generateTransactionNumber($prefix);

            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            // Lock the per-outlet stock row
            $outletStock    = ProductStock::forOutlet($product->id, $outletId);
            $currentStock   = (float) $outletStock->stock;
            $hpp            = (float) $product->hpp;

            switch ($type) {
                case 'IN':
                    $qty         = (float) $data['qty'];
                    $costPrice   = (float) ($data['cost_price'] ?? $hpp);
                    $newStock    = $currentStock + $qty;
                    $totalVal    = $qty * $costPrice;
                    $diffQty     = $qty;
                    $outletStock->update(['stock' => $newStock]);
                    // Sync global
                    $product->increment('stock', $qty);
                    break;

                case 'OUT':
                    $qty         = (float) $data['qty'];
                    $costPrice   = $hpp;
                    $newStock    = $currentStock - $qty;
                    $totalVal    = $qty * $costPrice;
                    $diffQty     = -$qty;
                    $outletStock->update(['stock' => $newStock]);
                    // Sync global
                    $product->decrement('stock', $qty);
                    break;

                default: // OPNAME
                    $actualStock = (float) $data['actual_stock'];
                    $diffQty     = $actualStock - $currentStock;
                    $costPrice   = $hpp;
                    $totalVal    = abs($diffQty) * $costPrice;
                    $newStock    = $actualStock;
                    $outletStock->update(['stock' => $newStock]);
                    // Sync global (recalculate SUM of all outlets)
                    $product->syncTotalStock();
                    break;
            }

            $adj = InventoryAdjustment::create([
                'adjustment_number' => $adjNumber,
                'outlet_id'         => $outletId,
                'user_id'           => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'type'              => $type,
                'date'              => now(),
                'product_id'        => $product->id,
                'qty'               => $type === 'OPNAME' ? abs($diffQty) : (float) $data['qty'],
                'system_stock'      => $currentStock,
                'actual_stock'      => $newStock,
                'diff_qty'          => $diffQty,
                'cost_price'        => $costPrice,
                'total_value'       => $totalVal,
                'notes'             => $data['notes'] ?? null,
            ]);

            $this->accountingService->recordInventoryAdjustment($adj);

            return $adj;
        });
    }

    // ----------------------------------------------------------------
    // Sales Return — restocks the originating outlet
    // ----------------------------------------------------------------

    /**
     * Process a Sales Return.
     *
     * Improvement: returned items go back to the outlet that made the sale,
     * not just the global stock.
     */
    public function processSaleReturn(array $data): SaleReturn
    {
        return DB::transaction(function () use ($data) {
            $returnNumber = $this->generateTransactionNumber('RTP');

            $product      = Product::lockForUpdate()->findOrFail($data['product_id']);
            $qty          = (float) $data['qty'];
            $refundAmount = (float) ($data['refund_amount'] ?? $data['amount'] ?? 0);
            $accountId    = $data['account_id'] ?? $data['refund_account_id'] ?? null;

            // Determine the outlet of the return (explicit outlet_id > original sale's outlet > fallback)
            $originalSale = isset($data['sale_id']) ? Sale::find($data['sale_id']) : null;
            $outletId     = $data['outlet_id']
                ?? $originalSale?->outlet_id
                ?? $this->resolveOutletId();

            // Restock — per outlet
            if ($outletId) {
                $outletStock = ProductStock::forOutlet($product->id, $outletId);
                $outletStock->increment('stock', $qty);
            }

            // Keep global stock in sync
            $product->increment('stock', $qty);

            $saleReturn = SaleReturn::create([
                'return_number'    => $returnNumber,
                'date'             => $data['date'] ?? now()->format('Y-m-d'),
                'original_sale_id' => $data['sale_id'] ?? $data['original_sale_id'] ?? null,
                'customer_id'      => $data['customer_id'] ?? null,
                'product_id'       => $product->id,
                'qty'              => $qty,
                'amount'           => $refundAmount,
                'refund_account_id'=> $accountId,
                'reason'           => $data['reason'] ?? 'RETUR PENJUALAN',
                'notes'            => $data['notes'] ?? null,
            ]);

            // Refund cash if applicable
            if ($refundAmount > 0 && $accountId) {
                $cashAcc = Account::find($accountId);
                if ($cashAcc) {
                    // If selected account is generic CASH RETAIL (1-1110) or global, resolve to outlet's CASH RETAIL if available
                    if ($outletId && ($cashAcc->code === '1-1110' || str_starts_with($cashAcc->code, '1-1110-'))) {
                        $outletCashAcc = Account::where('outlet_id', $outletId)
                            ->where('code', 'like', '1-1110%')
                            ->first();
                        if ($outletCashAcc) {
                            $cashAcc = $outletCashAcc;
                        }
                    }

                    $trxNumber = $this->generateTransactionNumber('KK');
                    $returAcc  = Account::where('code', '4-1600')->first()
                        ?: Account::where('name', 'like', '%RETUR%')->first()
                        ?: Account::where('group', 'BEBAN')->first();

                    $trx = CashTransaction::create([
                        'transaction_number' => $trxNumber,
                        'type'               => 'OUT',
                        'date'               => $data['date'] ?? now(),
                        'outlet_id'          => $outletId,
                        'user_id'            => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                        'debit_account_id'   => $returAcc?->id ?? $cashAcc->id,
                        'credit_account_id'  => $cashAcc->id,
                        'amount'             => $refundAmount,
                        'admin_fee'          => 0,
                        'notes'              => "Pengembalian dana retur {$returnNumber} ({$product->name})",
                    ]);

                    try {
                        $this->accountingService->recordCashTransaction($trx);
                    } catch (\Exception $e) {
                        // Direct balance fallback if journal fails
                        if ($cashAcc->type === 'D') {
                            $cashAcc->current_balance -= $refundAmount;
                        } else {
                            $cashAcc->current_balance += $refundAmount;
                        }
                        $cashAcc->save();
                    }
                }
            }

            return $saleReturn;
        });
    }
}
