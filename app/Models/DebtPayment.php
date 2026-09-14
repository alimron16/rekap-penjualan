<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'date',
        'supplier_id',
        'purchase_id',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
