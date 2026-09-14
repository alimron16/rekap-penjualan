<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'ELEPHANT CELL GROUP',
                'phone' => '088212283661',
                'address' => 'Kav. Virlania Tridaya Sakti, Kec. Tambun Selatan Kab. Bekasi',
                'logo_path' => null,
                'active_year' => 2026,
            ]
        );
    }
}
