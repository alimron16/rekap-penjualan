<?php

namespace App\Models;

use App\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTransfer extends Model
{
    use HasFactory, BelongsToOutlet;

    protected $fillable = [
        'reference_no',
        'user_id',
        'outlet_id',
        'store_name',
        'bank_name',
        'account_number',
        'account_holder',
        'amount',
        'admin_fee',
        'total_amount',
        'status',
        'processed_by',
        'approved_by',
        'source_account_id',
        'proof_image',
        'notes',
        'processed_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sourceAccount()
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }
}
