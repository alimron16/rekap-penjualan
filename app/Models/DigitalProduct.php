<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalProduct extends Model
{
    protected $fillable = [
        'product_code',
        'name',
        'trx_type',
        'category',
        'hpp',
        'selling_price',
        'status',
        'outlet_id',
    ];

    protected $casts = [
        'hpp' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function outlet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(DigitalSale::class);
    }

    public function getMarginAttribute(): float
    {
        return round($this->selling_price - $this->hpp, 2);
    }
}
