<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'qty',
        'selling_price',
        'hpp',
        'subtotal',
        'stock_before',
        'stock_after',
        'batch_id',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'hpp' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getGrossProfitAttribute(): float
    {
        return ($this->selling_price - $this->hpp) * $this->qty;
    }
}
