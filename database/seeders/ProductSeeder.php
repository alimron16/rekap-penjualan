<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data.json');
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            foreach ($data['products'] ?? [] as $p) {
                Product::updateOrCreate(['item_code' => $p['item_code']], $p);
            }
        }
    }
}
