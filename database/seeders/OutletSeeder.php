<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        $outlets = [
            [
                'code' => 'OUT-001',
                'name' => 'Elephant Cell Tambun (Pusat)',
                'address' => 'Jl. Sultan Hasanudin No. 12, Tambun Selatan, Bekasi',
                'phone' => '081288991122',
                'status' => 'active',
            ],
            [
                'code' => 'OUT-002',
                'name' => 'Elephant Cell Cibitung (Cabang 2)',
                'address' => 'Jl. Bosih Raya No. 45, Cibitung, Bekasi',
                'phone' => '081288991133',
                'status' => 'active',
            ],
            [
                'code' => 'OUT-003',
                'name' => 'Elephant Cell Cikarang (Cabang 3)',
                'address' => 'Kawasan Industri Jababeka 2, Cikarang Utara',
                'phone' => '081288991144',
                'status' => 'active',
            ],
        ];

        foreach ($outlets as $data) {
            Outlet::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
