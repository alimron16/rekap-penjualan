<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'outlet_id',
        'name',
        'phone',
        'address',
        'bank_name',
        'account_number',
        'notes',
        'status',
    ];

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

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function receivablePayments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }
}
