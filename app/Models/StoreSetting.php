<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'receipt_footer',
        'logo_path',
        'active_year',
    ];
}
