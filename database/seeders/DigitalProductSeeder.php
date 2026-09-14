<?php

namespace Database\Seeders;

use App\Models\DigitalProduct;
use Illuminate\Database\Seeder;

class DigitalProductSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data.json');
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            foreach ($data['multi_products'] ?? [] as $mp) {
                DigitalProduct::updateOrCreate(['product_code' => $mp['product_code']], $mp);
            }
        }
    }
}
