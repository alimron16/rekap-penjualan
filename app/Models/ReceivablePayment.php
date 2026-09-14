<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivablePayment extends Model
{
    protected $fillable = [
        'payment_number',
        'date',
        'customer_id',
        'sale_id',
        'account_id',
        'discount',
        'amount_paid',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'discount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
