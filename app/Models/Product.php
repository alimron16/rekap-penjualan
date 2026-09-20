<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'item_code',
        'name',
        'type',
        'brand',
        'stock',
        'min_stock',
        'hpp',
        'retail_price',
        'wholesale_price',
        'status',
    ];

    protected $casts = [
        'stock'           => 'decimal:2',
        'min_stock'       => 'integer',
        'hpp'             => 'decimal:4',
        'retail_price'    => 'decimal:2',
        'wholesale_price' => 'decimal:2',
    ];

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /** Per-outlet stock ledger rows. */
    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    // ----------------------------------------------------------------
    // Per-Outlet Stock Helpers
    // ----------------------------------------------------------------

    /**
     * Get the stock for a specific outlet.
     * Returns 0 when no record exists yet (new outlet / product combo).
     */
    public function stockForOutlet(int $outletId): float
    {
        $row = $this->productStocks->firstWhere('outlet_id', $outletId);

        return $row ? (float) $row->stock : 0.0;
    }

    /**
     * Recalculate and persist `products.stock` as the SUM of all
     * per-outlet stocks so the admin dashboard stays accurate.
     */
    public function syncTotalStock(): void
    {
        $total = $this->productStocks()->sum('stock');
        $this->update(['stock' => $total]);
    }

    // ----------------------------------------------------------------
    // Computed Attributes
    // ----------------------------------------------------------------

    public function getSubtotalStockValueAttribute(): float
    {
        return round($this->stock * $this->hpp, 2);
    }
}

