<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'store_name',
        'phone',
        'permissions',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isToko(): bool
    {
        return $this->role === 'toko';
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    /**
     * Check if user has permission to a specific module
     */
    public function hasPermission(string $module): bool
    {
        // Super Admin has all permissions unconditionally
        if ($this->isSuperAdmin()) {
            return true;
        }

        // If specific permissions are stored in JSON
        if (is_array($this->permissions) && array_key_exists($module, $this->permissions)) {
            return (bool) $this->permissions[$module];
        }

        // Default role-based permissions fallback
        return match ($this->role) {
            'admin' => in_array($module, ['master', 'purchase', 'pos', 'transfer', 'accounting', 'reports', 'settings']),
            'toko' => in_array($module, ['pos', 'transfer', 'reports_toko']),
            default => false,
        };
    }
}
