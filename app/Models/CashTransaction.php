<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'type',
        'date',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'admin_fee',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
    ];

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }
}
