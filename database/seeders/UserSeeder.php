<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('Imron@0458');

        // 1. Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@elephantcell.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'role' => 'super_admin',
                'store_name' => 'Kantor Pusat',
                'phone' => '081234567890',
                'permissions' => [
                    'master' => true,
                    'purchase' => true,
                    'pos' => true,
                    'transfer' => true,
                    'accounting' => true,
                    'reports' => true,
                    'settings' => true,
                    'users' => true,
                ],
                'is_active' => true,
            ]
        );

        // 2. Admin Operasional
        User::updateOrCreate(
            ['email' => 'admin@elephantcell.com'],
            [
                'name' => 'Admin Operasional',
                'password' => $password,
                'role' => 'admin',
                'store_name' => 'Kantor Pusat',
                'phone' => '081234567891',
                'permissions' => [
                    'master' => true,
                    'purchase' => true,
                    'pos' => true,
                    'transfer' => true,
                    'accounting' => true,
                    'reports' => true,
                    'settings' => true,
                    'users' => true,
                ],
                'is_active' => true,
            ]
        );

        // 3. Toko (Kasir Cabang)
        User::updateOrCreate(
            ['email' => 'toko@elephantcell.com'],
            [
                'name' => 'Kasir Toko Tambun',
                'password' => $password,
                'role' => 'toko',
                'store_name' => 'Toko Tambun Selatan',
                'phone' => '081234567892',
                'permissions' => [
                    'master' => false,
                    'purchase' => false,
                    'pos' => true,
                    'transfer' => true,
                    'accounting' => false,
                    'reports' => true,
                    'settings' => false,
                    'users' => false,
                ],
                'is_active' => true,
            ]
        );
    }
}
