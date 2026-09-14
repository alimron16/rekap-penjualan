<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data.json');
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);

            foreach ($data['suppliers'] ?? [] as $s) {
                Supplier::updateOrCreate(['name' => $s['name']], $s);
            }

            foreach ($data['customers'] ?? [] as $c) {
                Customer::updateOrCreate(['name' => $c['name']], $c);
            }
        }

        // Ensure default UMUM customer exists
        Customer::firstOrCreate(
            ['name' => 'UMUM'],
            [
                'phone' => '081200000000',
                'address' => 'Pelanggan Walk-in Retail',
                'status' => 'Aktif',
            ]
        );
    }
}
