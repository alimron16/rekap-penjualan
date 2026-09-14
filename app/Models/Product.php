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
        'stock' => 'decimal:2',
        'min_stock' => 'integer',
        'hpp' => 'decimal:4',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getSubtotalStockValueAttribute(): float
    {
        return round($this->stock * $this->hpp, 2);
    }
}
