<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'outlet_id',
        'name',
        'phone',
        'address',
        'bank_name',
        'account_number',
        'account_name',
    ];

    public function outlet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    protected function accountNumber(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value) {
                if (!$value) return $value;
                if (preg_match('/^[0-9]+(\.[0-9]+)?[eE][\+\-]?[0-9]+$/i', trim($value))) {
                    return sprintf('%.0f', (float)$value);
                }
                return $value;
            },
            set: function (?string $value) {
                if (!$value) return $value;
                if (preg_match('/^[0-9]+(\.[0-9]+)?[eE][\+\-]?[0-9]+$/i', trim($value))) {
                    return sprintf('%.0f', (float)$value);
                }
                return $value;
            }
        );
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function debtPayments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }
}
