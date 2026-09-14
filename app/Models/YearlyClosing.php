<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearlyClosing extends Model
{
    protected $fillable = [
        'year',
        'closing_date',
        'status',
        'net_profit',
        'retained_earnings_account_id',
        'notes',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'net_profit' => 'decimal:2',
    ];

    public function retainedEarningsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'retained_earnings_account_id');
    }
}
