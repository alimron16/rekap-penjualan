<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'outlet_id',
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
        'outlet_id' => 'integer',
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_system_locked' => 'boolean',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

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

    /**
     * Get or create dedicated Saldo Multi account for an outlet.
     */
    public static function getOutletMultiAccount(?int $outletId): self
    {
        if (!$outletId) {
            $outletId = Outlet::where('status', 'active')->orderBy('id')->value('id');
        }

        if ($outletId) {
            $code = '1-1131-' . $outletId;

            // 1. Cari jika sudah ada akun untuk outlet ini
            $account = self::where('outlet_id', $outletId)
                ->where(function ($q) use ($code) {
                    $q->where('code', $code)
                      ->orWhere('code', 'like', '1-1131%');
                })
                ->first();

            if ($account) {
                return $account;
            }

            // 2. Jika ada akun dengan kode ini namun outlet_id belum sinkron
            $account = self::where('code', $code)->first();
            if ($account) {
                if ($account->outlet_id !== $outletId) {
                    $account->update(['outlet_id' => $outletId]);
                }
                return $account;
            }

            $outlet = Outlet::find($outletId);
            $shortName = $outlet ? strtoupper($outlet->code) : "OUT-{$outletId}";

            return self::firstOrCreate(
                ['code' => $code],
                [
                    'outlet_id' => $outletId,
                    'name' => 'SALDO MULTI ' . $shortName,
                    'type' => 'D',
                    'group' => 'AKTIVA',
                    'initial_balance' => 0,
                    'current_balance' => 0,
                    'is_system_locked' => false,
                ]
            );
        }

        return self::where('code', '1-1131')->firstOrFail();
    }

    /**
     * Get or create dedicated Cash Retail account for an outlet.
     */
    public static function getOutletCashRetailAccount(?int $outletId): self
    {
        if (!$outletId) {
            $outletId = Outlet::where('status', 'active')->orderBy('id')->value('id');
        }

        if ($outletId) {
            $code = '1-1110-' . $outletId;

            // 1. Cari jika sudah ada akun untuk outlet ini
            $account = self::where('outlet_id', $outletId)
                ->where(function ($q) use ($code) {
                    $q->where('code', $code)
                      ->orWhere('code', 'like', '1-1110%');
                })
                ->first();

            if ($account) {
                return $account;
            }

            // 2. Jika ada akun dengan kode ini namun outlet_id belum sinkron
            $account = self::where('code', $code)->first();
            if ($account) {
                if ($account->outlet_id !== $outletId) {
                    $account->update(['outlet_id' => $outletId]);
                }
                return $account;
            }

            $outlet = Outlet::find($outletId);
            $shortName = $outlet ? strtoupper($outlet->code) : "OUT-{$outletId}";

            return self::firstOrCreate(
                ['code' => $code],
                [
                    'outlet_id' => $outletId,
                    'name' => 'CASH RETAIL ' . $shortName,
                    'type' => 'D',
                    'group' => 'AKTIVA',
                    'initial_balance' => 0,
                    'current_balance' => 0,
                    'is_system_locked' => false,
                ]
            );
        }

        return self::where('code', '1-1110')->firstOrFail();
    }
}
