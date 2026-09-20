<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\DigitalSale;
use App\Models\InventoryAdjustment;
use App\Models\Product;
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
        protected AccountingService $accountingService,
        protected HppCalculationService $hppService
    ) {}

    /**
     * Generate unique transaction code: PREFIX-YYYYMMDD-His
     */
    public function generateTransactionNumber(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, date('Ymd'), date('His') . rand(10, 99));
    }

    /**
     * Execute POS Checkout (Retail or Wholesale) within an ACID transaction.
     *
     * @param array $data ['sale_type' => 'retail'|'grosir', 'customer_id' => int, 'items' => [...], 'discount' => float, 'paid_amount' => float, 'payment_method' => string, 'account_id' => int, 'notes' => string]
     */
    public function checkoutPos(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $saleType = $data['sale_type'] ?? 'retail';
            $prefix = $saleType === 'grosir' ? 'PG' : 'PR';
            $invoiceNumber = $this->generateTransactionNumber($prefix);

            $customerId = $data['customer_id'] ?? null;
            if (!$customerId) {
                $defaultCust = Customer::firstOrCreate(['name' => 'UMUM']);
                $customerId = $defaultCust->id;
            }

            $discount = (float) ($data['discount'] ?? 0);
            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'Tunai';
            $accountId = $data['account_id'] ?? null;

            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new Exception("Keranjang belanja tidak boleh kosong!");
            }

            $subtotal = 0;
            $saleItemsToInsert = [];

            // 1. Process items and update stock
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = (float) ($item['qty'] ?? 1);
                if ($qty <= 0) {
                    continue;
                }

                $sellingPrice = (float) ($item['price'] ?? ($saleType === 'grosir' ? $product->wholesale_price : $product->retail_price));
                $itemSubtotal = $qty * $sellingPrice;
                $subtotal += $itemSubtotal;

                $stockBefore = (float) $product->stock;
                if ($stockBefore < $qty) {
                    throw new Exception("Stok barang [{$product->name}] tidak mencukupi! Sisa stok saat ini: " . (int)$stockBefore . " pcs, diminta: " . (int)$qty . " pcs.");
                }

                $stockAfter = $stockBefore - $qty;

                // Update product stock
                $product->update(['stock' => $stockAfter]);

                $saleItemsToInsert[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'selling_price' => $sellingPrice,
                    'hpp' => (float) $product->hpp,
                    'subtotal' => $itemSubtotal,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'batch_id' => 'B-' . date('Ymd'),
                ];
            }

            $total = max(0, $subtotal - $discount);
            $remainingReceivable = max(0, $total - $paidAmount);
            $status = $remainingReceivable <= 0 ? 'LUNAS' : 'BELUM LUNAS';

            // 2. Create Sale record
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'sale_type' => $saleType,
                'date' => now(),
                'customer_id' => $customerId,
                'outlet_id' => $data['outlet_id'] ?? (auth()->check() ? auth()->user()->outlet_id : null),
                'user_id' => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'remaining_receivable' => $remainingReceivable,
                'payment_method' => $paymentMethod,
                'account_id' => $accountId,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            // 3. Create Sale Items
            foreach ($saleItemsToInsert as $itemData) {
                $itemData['sale_id'] = $sale->id;
                SaleItem::create($itemData);
            }

            // 4. Create automated double-entry journal
            $this->accountingService->recordSale($sale);

            return $sale->load(['items.product', 'customer', 'account']);
        });
    }

    /**
     * Process Digital Sale (Elektrik / Multi / Pulsa / PLN).
     */
    public function processDigitalSale(array $data): DigitalSale
    {
        return DB::transaction(function () use ($data) {
            $digitalProduct = DigitalProduct::findOrFail($data['digital_product_id']);
            $sellingPrice = (float) ($data['selling_price'] ?? $digitalProduct->selling_price);
            $hpp = (float) ($data['hpp'] ?? $digitalProduct->hpp);
            $margin = $sellingPrice - $hpp;

            $trxNumber = $this->generateTransactionNumber('PE');

            $digitalSale = DigitalSale::create([
                'transaction_number' => $trxNumber,
                'date' => now(),
                'digital_product_id' => $digitalProduct->id,
                'customer_number' => $data['customer_number'],
                'outlet_id' => $data['outlet_id'] ?? (auth()->check() ? auth()->user()->outlet_id : null),
                'user_id' => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'selling_price' => $sellingPrice,
                'hpp' => $hpp,
                'profit_margin' => $margin,
                'deposit_account_id' => $data['deposit_account_id'],
                'cash_account_id' => $data['cash_account_id'],
                'status' => 'SUKSES',
                'notes' => $data['notes'] ?? null,
            ]);

            // Post journal
            $this->accountingService->recordDigitalSale($digitalSale);

            return $digitalSale;
        });
    }

    /**
     * Reverse a Digital Sale when status changed to GAGAL.
     */
    public function reverseDigitalSale(DigitalSale $sale): DigitalSale
    {
        return DB::transaction(function () use ($sale) {
            if ($sale->status === 'GAGAL') {
                throw new Exception("Transaksi ini sudah berstatus GAGAL!");
            }

            $sale->update(['status' => 'GAGAL']);
            $this->accountingService->recordDigitalSaleReversal($sale);

            return $sale;
        });
    }

    /**
     * Process Purchase of physical products.
     */
    public function processPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $invoiceNumber = $data['invoice_number'] ?? $this->generateTransactionNumber('PB');
            $supplierId = $data['supplier_id'] ?? null;
            $items = $data['items'] ?? [];
            $discount = (float) ($data['discount'] ?? 0);
            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'Tunai';
            $accountId = $data['account_id'] ?? null;

            $subtotal = 0;
            $purchaseItemsData = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = (float) ($item['qty'] ?? 1);
                $buyPrice = (float) ($item['buy_price'] ?? $product->hpp);
                $lineSubtotal = $qty * $buyPrice;
                $subtotal += $lineSubtotal;

                // Moving average HPP & Stock update
                $audit = $this->hppService->applyPurchase($product, $qty, $buyPrice);

                $purchaseItemsData[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'buy_price' => $buyPrice,
                    'subtotal' => $lineSubtotal,
                    'stock_before' => $audit['stock_before'],
                    'hpp_before' => $audit['hpp_before'],
                    'stock_after' => $audit['stock_after'],
                    'hpp_after' => $audit['hpp_after'],
                    'batch_id' => 'B-' . date('Ymd'),
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $total = max(0, $subtotal - $discount);
            $remainingDebt = max(0, $total - $paidAmount);
            $status = $remainingDebt <= 0 ? 'LUNAS' : 'BELUM LUNAS';

            $purchase = Purchase::create([
                'invoice_number' => $invoiceNumber,
                'date' => $data['date'] ?? now()->format('Y-m-d'),
                'supplier_id' => $supplierId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'remaining_debt' => $remainingDebt,
                'payment_method' => $paymentMethod,
                'account_id' => $accountId,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($purchaseItemsData as $pid) {
                $pid['purchase_id'] = $purchase->id;
                PurchaseItem::create($pid);
            }

            $this->accountingService->recordPurchase($purchase);

            return $purchase;
        });
    }

    /**
     * Process Kas Transfer (Transfer Tunai / Tarik Tunai with admin fee).
     */
    public function processKasTransfer(array $data): CashTransaction
    {
        return DB::transaction(function () use ($data) {
            $trxNumber = $this->generateTransactionNumber('KT');
            $amount = (float) ($data['amount'] ?? 0);
            $adminFee = (float) ($data['admin_fee'] ?? 0);

            $trx = CashTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'TRANSFER',
                'date' => now(),
                'outlet_id' => $data['outlet_id'] ?? (auth()->check() ? auth()->user()->outlet_id : null),
                'user_id' => $data['user_id'] ?? (auth()->check() ? auth()->id() : null),
                'debit_account_id' => $data['debit_account_id'], // CASH TRANSFER / Destination
                'credit_account_id' => $data['credit_account_id'], // Source bank / cash
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'notes' => $data['notes'] ?? 'Kas Transfer',
            ]);

            $this->accountingService->recordCashTransaction($trx);

            return $trx;
        });
    }

    /**
     * Process Inventory Adjustment (Item Masuk, Item Keluar, Stok Opname).
     */
    public function processInventoryAdjustment(array $data): InventoryAdjustment
    {
        return DB::transaction(function () use ($data) {
            $type = $data['type']; // IN, OUT, OPNAME
            $prefix = $type === 'IN' ? 'IM' : ($type === 'OUT' ? 'IK' : 'SO');
            $adjNumber = $this->generateTransactionNumber($prefix);

            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $currentStock = (float) $product->stock;
            $hpp = (float) $product->hpp;

            if ($type === 'IN') {
                $qty = (float) $data['qty'];
                $costPrice = (float) ($data['cost_price'] ?? $hpp);
                $newStock = $currentStock + $qty;
                $totalVal = $qty * $costPrice;
                $diffQty = $qty;
                $product->update(['stock' => $newStock]);
            } elseif ($type === 'OUT') {
                $qty = (float) $data['qty'];
                $costPrice = $hpp;
                $newStock = $currentStock - $qty;
                $totalVal = $qty * $costPrice;
                $diffQty = -$qty;
                $product->update(['stock' => $newStock]);
            } else {
                // OPNAME
                $actualStock = (float) $data['actual_stock'];
                $diffQty = $actualStock - $currentStock;
                $costPrice = $hpp;
                $totalVal = abs($diffQty) * $costPrice;
                $newStock = $actualStock;
                $product->update(['stock' => $newStock]);
            }

            $adj = InventoryAdjustment::create([
                'adjustment_number' => $adjNumber,
                'type' => $type,
                'date' => now(),
                'product_id' => $product->id,
                'qty' => $type === 'OPNAME' ? abs($diffQty) : (float) $data['qty'],
                'system_stock' => $currentStock,
                'actual_stock' => $newStock,
                'diff_qty' => $diffQty,
                'cost_price' => $costPrice,
                'total_value' => $totalVal,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->accountingService->recordInventoryAdjustment($adj);

            return $adj;
        });
    }

    /**
     * Process Sales Return (Retur Penjualan) within an ACID transaction.
     */
    public function processSaleReturn(array $data): SaleReturn
    {
        return DB::transaction(function () use ($data) {
            $returnNumber = $this->generateTransactionNumber('RTP');

            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $qty = (float) $data['qty'];
            $refundAmount = (float) ($data['refund_amount'] ?? $data['amount'] ?? 0);
            $accountId = $data['account_id'] ?? $data['refund_account_id'] ?? null;

            // Restock returned product
            $product->stock += $qty;
            $product->save();

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'date' => $data['date'] ?? now()->format('Y-m-d'),
                'original_sale_id' => $data['sale_id'] ?? $data['original_sale_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'product_id' => $product->id,
                'qty' => $qty,
                'amount' => $refundAmount,
                'refund_account_id' => $accountId,
                'reason' => $data['reason'] ?? 'RETUR PENJUALAN',
                'notes' => $data['notes'] ?? null,
            ]);

            // If refund is paid from cash/bank account, record cash transaction and update balance
            if ($refundAmount > 0 && $accountId) {
                $cashAcc = Account::find($accountId);
                if ($cashAcc) {
                    $trxNumber = $this->generateTransactionNumber('KK');
                    $returAcc = Account::where('code', '4-1600')->first() ?: Account::where('name', 'like', '%RETUR%')->first() ?: Account::where('group', 'BEBAN')->first();

                    $trx = CashTransaction::create([
                        'transaction_number' => $trxNumber,
                        'type' => 'OUT',
                        'date' => $data['date'] ?? now(),
                        'debit_account_id' => $returAcc?->id ?? $cashAcc->id,
                        'credit_account_id' => $cashAcc->id,
                        'amount' => $refundAmount,
                        'admin_fee' => 0,
                        'notes' => "Pengembalian dana retur {$returnNumber} ({$product->name})",
                    ]);

                    try {
                        $this->accountingService->recordCashTransaction($trx);
                    } catch (\Exception $e) {
                        // Direct balance update fallback if accounting journal fails
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
