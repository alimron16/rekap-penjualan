<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'user_id',
        'store_name',
        'bank_name',
        'account_number',
        'account_holder',
        'amount',
        'admin_fee',
        'total_amount',
        'status',
        'processed_by',
        'source_account_id',
        'proof_image',
        'notes',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function sourceAccount()
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }
}
