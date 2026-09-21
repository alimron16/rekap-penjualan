<?php

namespace Database\Seeders;

use App\Models\Outlet;
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
        $defaultPassword = Hash::make('Imron@0458');
        $demoPassword = Hash::make('demo12345');

        $outletTambun = Outlet::where('code', 'OUT-001')->first() ?? Outlet::first();
        $outletCibitung = Outlet::where('code', 'OUT-002')->first() ?? Outlet::skip(1)->first() ?? $outletTambun;

        // 1. Super Admin (Produksi)
        User::updateOrCreate(
            ['email' => 'superadmin@elephantcell.com'],
            [
                'name' => 'Super Admin',
                'password' => $defaultPassword,
                'role' => 'super_admin',
                'store_name' => 'Kantor Pusat',
                'phone' => '081234567890',
                'outlet_id' => $outletTambun?->id,
                'permissions' => [
                    'master' => true,
                    'purchase' => true,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
                    'accounting' => true,
                    'reports' => true,
                    'settings' => true,
                    'users' => true,
                ],
                'is_active' => true,
            ]
        );

        // 2. Admin Operasional (Produksi)
        User::updateOrCreate(
            ['email' => 'admin@elephantcell.com'],
            [
                'name' => 'Admin Operasional',
                'password' => $defaultPassword,
                'role' => 'admin',
                'store_name' => 'Kantor Pusat',
                'phone' => '081234567891',
                'outlet_id' => $outletTambun?->id,
                'permissions' => [
                    'master' => true,
                    'purchase' => true,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
                    'accounting' => true,
                    'reports' => true,
                    'settings' => true,
                    'users' => true,
                ],
                'is_active' => true,
            ]
        );

        // 3. Toko (Kasir Cabang Produksi)
        User::updateOrCreate(
            ['email' => 'toko@elephantcell.com'],
            [
                'name' => 'Kasir Toko Tambun',
                'password' => $defaultPassword,
                'role' => 'toko',
                'store_name' => 'Toko Tambun Selatan',
                'phone' => '081234567892',
                'outlet_id' => $outletTambun?->id,
                'permissions' => [
                    'master' => false,
                    'purchase' => false,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
                    'accounting' => false,
                    'reports' => true,
                    'settings' => false,
                    'users' => false,
                ],
                'is_active' => true,
            ]
        );

        // 4. DEMO ACCOUNT: Demo Admin (Kantor Pusat)
        User::updateOrCreate(
            ['email' => 'demo@elephantcell.com'],
            [
                'name' => 'Demo Admin (Pusat)',
                'password' => $demoPassword,
                'role' => 'admin',
                'store_name' => 'Kantor Pusat (Demo)',
                'phone' => '081299001100',
                'outlet_id' => $outletTambun?->id,
                'permissions' => [
                    'master' => true,
                    'purchase' => true,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
                    'accounting' => true,
                    'reports' => true,
                    'settings' => true,
                    'users' => true,
                ],
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo.admin@elephantcell.com'],
            [
                'name' => 'Demo Administrator',
                'password' => $demoPassword,
                'role' => 'admin',
                'store_name' => 'Kantor Pusat (Demo)',
                'phone' => '081299001101',
                'outlet_id' => $outletTambun?->id,
                'permissions' => [
                    'master' => true,
                    'purchase' => true,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
                    'accounting' => true,
                    'reports' => true,
                    'settings' => true,
                    'users' => true,
                ],
                'is_active' => true,
            ]
        );

        // 5. DEMO ACCOUNT: Demo Kasir (Cabang)
        User::updateOrCreate(
            ['email' => 'demo.kasir@elephantcell.com'],
            [
                'name' => 'Demo Kasir (Cabang Tambun)',
                'password' => $demoPassword,
                'role' => 'toko',
                'store_name' => 'Elephant Cell Tambun (Demo)',
                'phone' => '081299001102',
                'outlet_id' => $outletTambun?->id,
                'permissions' => [
                    'master' => false,
                    'purchase' => false,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
                    'accounting' => false,
                    'reports' => true,
                    'settings' => false,
                    'users' => false,
                ],
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kasir@elephantcell.com'],
            [
                'name' => 'Demo Kasir (Cabang Cibitung)',
                'password' => $demoPassword,
                'role' => 'toko',
                'store_name' => 'Elephant Cell Cibitung (Demo)',
                'phone' => '081299001103',
                'outlet_id' => $outletCibitung?->id ?? $outletTambun?->id,
                'permissions' => [
                    'master' => false,
                    'purchase' => false,
                    'pos' => true,
                    'transfer' => true,
                    'cash_withdrawal' => true,
                    'digital' => true,
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
