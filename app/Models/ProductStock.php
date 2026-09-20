<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-outlet stock ledger.
 *
 * @property int    $id
 * @property int    $product_id
 * @property int    $outlet_id
 * @property float  $stock
 * @property int    $min_stock
 */
class ProductStock extends Model
{
    protected $fillable = [
        'product_id',
        'outlet_id',
        'stock',
        'min_stock',
    ];

    protected $casts = [
        'stock'     => 'decimal:2',
        'min_stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get or create a stock record for a product-outlet pair.
     */
    public static function forOutlet(int $productId, int $outletId): self
    {
        return static::firstOrCreate(
            ['product_id' => $productId, 'outlet_id' => $outletId],
            ['stock' => 0, 'min_stock' => 0]
        );
    }
}
