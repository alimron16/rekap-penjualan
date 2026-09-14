<?php

namespace App\Services;

use App\Models\Product;

class HppCalculationService
{
    /**
     * Calculate new moving average HPP for a product based on purchase.
     * Formula: HPP Baru = ((Stok Lama * HPP Lama) + (Qty Beli * Harga Beli Baru)) / (Stok Lama + Qty Beli)
     */
    public function calculateMovingAverage(float $oldStock, float $oldHpp, float $buyQty, float $buyPrice): float
    {
        // If previous stock was zero or negative, the new HPP is the purchase price
        if ($oldStock <= 0) {
            return round($buyPrice, 4);
        }

        $totalOldValue = $oldStock * $oldHpp;
        $totalNewValue = $buyQty * $buyPrice;
        $totalStock = $oldStock + $buyQty;

        if ($totalStock <= 0) {
            return round($buyPrice, 4);
        }

        return round(($totalOldValue + $totalNewValue) / $totalStock, 4);
    }

    /**
     * Apply a purchase to a product: update stock and recalculate HPP.
     * Returns an array with before/after audit values.
     */
    public function applyPurchase(Product $product, float $buyQty, float $buyPrice): array
    {
        $stockBefore = (float) $product->stock;
        $hppBefore = (float) $product->hpp;

        $newHpp = $this->calculateMovingAverage($stockBefore, $hppBefore, $buyQty, $buyPrice);
        $stockAfter = $stockBefore + $buyQty;

        $product->update([
            'stock' => $stockAfter,
            'hpp' => $newHpp,
        ]);

        return [
            'stock_before' => $stockBefore,
            'hpp_before' => $hppBefore,
            'stock_after' => $stockAfter,
            'hpp_after' => $newHpp,
        ];
    }
}
