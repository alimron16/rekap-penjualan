<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftLog extends Model
{
    protected $fillable = [
        'outlet_id',
        'user_id',
        'start_time',
        'end_time',
        'cash_retail_deposited',
        'cash_retail_retained',
        'cash_multi_deposited',
        'cash_multi_retained',
        'cash_transfer_deposited',
        'cash_transfer_retained',
        'total_deposited',
        'cash_sales',
        'non_cash_sales',
        'receivable_sales',
        'digital_sales',
        'digital_profit',
        'transfer_cash',
        'transfer_fee',
        'withdraw_cash',
        'withdraw_fee',
        'expenses',
        'transaction_count',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cash_retail_deposited' => 'decimal:2',
        'cash_retail_retained' => 'decimal:2',
        'cash_multi_deposited' => 'decimal:2',
        'cash_multi_retained' => 'decimal:2',
        'cash_transfer_deposited' => 'decimal:2',
        'cash_transfer_retained' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'non_cash_sales' => 'decimal:2',
        'receivable_sales' => 'decimal:2',
        'digital_sales' => 'decimal:2',
        'digital_profit' => 'decimal:2',
        'transfer_cash' => 'decimal:2',
        'transfer_fee' => 'decimal:2',
        'withdraw_cash' => 'decimal:2',
        'withdraw_fee' => 'decimal:2',
        'expenses' => 'decimal:2',
        'transaction_count' => 'integer',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
