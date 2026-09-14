<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturn extends Model
{
    protected $table = 'sales_returns';

    protected $fillable = [
        'return_number',
        'date',
        'original_sale_id',
        'customer_id',
        'product_id',
        'qty',
        'amount',
        'refund_account_id',
        'reason',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'qty' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function originalSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function refundAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'refund_account_id');
    }
}
