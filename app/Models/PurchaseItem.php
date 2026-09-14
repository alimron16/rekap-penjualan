<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'qty',
        'buy_price',
        'subtotal',
        'stock_before',
        'hpp_before',
        'stock_after',
        'hpp_after',
        'batch_id',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'hpp_before' => 'decimal:4',
        'stock_after' => 'decimal:2',
        'hpp_after' => 'decimal:4',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
