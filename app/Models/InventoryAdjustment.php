<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'adjustment_number',
        'outlet_id',
        'user_id',
        'type',
        'date',
        'product_id',
        'qty',
        'system_stock',
        'actual_stock',
        'diff_qty',
        'cost_price',
        'total_value',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'qty' => 'decimal:2',
        'system_stock' => 'decimal:2',
        'actual_stock' => 'decimal:2',
        'diff_qty' => 'decimal:2',
        'cost_price' => 'decimal:4',
        'total_value' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
