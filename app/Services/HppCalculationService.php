<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;

class HppCalculationService
{
    /**
     * Calculate new moving average HPP.
     *
     * Formula:  HPP_baru = (Stok_lama * HPP_lama + Qty_beli * Harga_beli) / (Stok_lama + Qty_beli)
     * When previous stock is zero we simply adopt the purchase price.
     */
    public function calculateMovingAverage(float $oldStock, float $oldHpp, float $buyQty, float $buyPrice): float
    {
        if ($oldStock <= 0) {
            return round($buyPrice, 4);
        }

        $totalOldValue = $oldStock * $oldHpp;
        $totalNewValue = $buyQty * $buyPrice;
        $totalStock    = $oldStock + $buyQty;

        if ($totalStock <= 0) {
            return round($buyPrice, 4);
        }

        return round(($totalOldValue + $totalNewValue) / $totalStock, 4);
    }

    /**
     * Apply a purchase to a product:
     * - Updates the per-outlet stock in `product_stocks`
     * - Recalculates global HPP using the TOTAL stock across ALL outlets
     *   (moving average considers the whole inventory, not just one outlet)
     * - Syncs `products.stock` = SUM of all outlet stocks
     *
     * Returns an audit array (before / after values) for the purchase_items row.
     */
    public function applyPurchase(Product $product, float $buyQty, float $buyPrice, int $outletId): array
    {
        // --- Global aggregates (for HPP calculation) ---
        $globalStockBefore = (float) $product->stock;   // SUM across all outlets
        $hppBefore         = (float) $product->hpp;

        // --- Per-outlet stock ---
        $outletStock = ProductStock::forOutlet($product->id, $outletId);
        $outletStockBefore = (float) $outletStock->stock;
        $outletStockAfter  = $outletStockBefore + $buyQty;

        // --- Update per-outlet stock ---
        $outletStock->update(['stock' => $outletStockAfter]);

        // --- Recalculate global HPP using total stock across all outlets ---
        $newHpp          = $this->calculateMovingAverage($globalStockBefore, $hppBefore, $buyQty, $buyPrice);
        $globalStockAfter = $globalStockBefore + $buyQty;

        // --- Update product master (global total + new HPP) ---
        $product->update([
            'stock' => $globalStockAfter,
            'hpp'   => $newHpp,
        ]);

        return [
            'stock_before'        => $outletStockBefore,    // outlet stock before
            'hpp_before'          => $hppBefore,
            'stock_after'         => $outletStockAfter,     // outlet stock after
            'hpp_after'           => $newHpp,
            'global_stock_before' => $globalStockBefore,
            'global_stock_after'  => $globalStockAfter,
        ];
    }
}
