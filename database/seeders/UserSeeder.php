<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Buat hanya akun Owner / Super Admin.
     * Semua akun kasir dan demo TIDAK dibuat di sini.
     * Tambah kasir lewat menu Users di aplikasi.
     */
    public function run(): void
    {
        $outlet = Outlet::where('code', 'OUT-001')->first() ?? Outlet::first();

        User::updateOrCreate(
            ['email' => 'owner@elephantcell.com'],
            [
                'name'       => 'Owner',
                'password'   => Hash::make('owner123'),
                'role'       => 'super_admin',
                'store_name' => 'Kantor Pusat',
                'phone'      => '081234567890',
                'outlet_id'  => $outlet?->id,
                'permissions' => [
                    'master'          => true,
                    'purchase'        => true,
                    'pos'             => true,
                    'transfer'        => true,
                    'cash_withdrawal' => true,
                    'digital'         => true,
                    'accounting'      => true,
                    'reports'         => true,
                    'settings'        => true,
                    'users'           => true,
                ],
                'is_active' => true,
            ]
        );
    }
}
