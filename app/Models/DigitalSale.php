<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalSale extends Model
{
    protected $fillable = [
        'transaction_number',
        'date',
        'digital_product_id',
        'customer_number',
        'selling_price',
        'hpp',
        'profit_margin',
        'deposit_account_id',
        'cash_account_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'selling_price' => 'decimal:2',
        'hpp' => 'decimal:2',
        'profit_margin' => 'decimal:2',
    ];

    public function digitalProduct(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class);
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }
}
