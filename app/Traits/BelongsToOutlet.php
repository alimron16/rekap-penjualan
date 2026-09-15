<?php

namespace App\Traits;

use App\Models\Outlet;
use Illuminate\Support\Facades\Auth;

trait BelongsToOutlet
{
    /**
     * Boot the trait to auto-assign outlet_id when creating models
     */
    public static function bootBelongsToOutlet(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->isToko() && empty($model->outlet_id)) {
                    $model->outlet_id = $user->outlet_id;
                }
            }
        });
    }

    /**
     * Scope query to specific outlet or auto-scope for toko user
     */
    public function scopeForOutlet($query, $outletId = null)
    {
        $table = $this->getTable();

        // Explicit outlet specified (e.g. from Admin Switcher)
        if (!empty($outletId)) {
            return $query->where("{$table}.outlet_id", $outletId);
        }

        // If user is logged in as Toko, always scope to their own outlet
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isToko() && !empty($user->outlet_id)) {
                return $query->where("{$table}.outlet_id", $user->outlet_id);
            }
        }

        return $query;
    }

    /**
     * Relationship to Outlet
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }
}
