<?php

namespace App\Models;

use App\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToOutlet;

    protected $fillable = [
        'invoice_number',
        'sale_type',
        'date',
        'outlet_id',
        'customer_id',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'remaining_receivable',
        'payment_method',
        'account_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_receivable' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getTotalHppAttribute(): float
    {
        return $this->items->sum(fn ($i) => $i->qty * $i->hpp);
    }

    public function getGrossProfitAttribute(): float
    {
        return $this->total - $this->total_hpp;
    }
}
