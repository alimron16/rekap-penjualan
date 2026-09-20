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

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }
        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }
        $path = ltrim($this->logo_path, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        return asset('storage/' . $path);
    }
}
