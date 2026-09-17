<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'group',
        'initial_balance',
        'current_balance',
        'is_system_locked',
    ];

    protected $appends = [
        'account_number',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_system_locked' => 'boolean',
    ];

    public function getAccountNumberAttribute(): string
    {
        return (string) ($this->attributes['code'] ?? '');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->current_balance, 0, ',', '.');
    }
}
