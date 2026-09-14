<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyTarget extends Model
{
    protected $fillable = [
        'year',
        'month',
        'target_profit',
        'target_vocer',
        'target_perdana',
        'target_acc',
        'target_transfer',
        'target_elektrik',
    ];

    protected $casts = [
        'target_profit' => 'decimal:2',
        'target_transfer' => 'decimal:2',
        'target_elektrik' => 'decimal:2',
    ];
}
