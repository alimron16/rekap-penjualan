<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoreSettingSeeder::class,
            OutletSeeder::class,
            AccountSeeder::class,
            CategorySeeder::class,
            SupplierCustomerSeeder::class,
            ProductSeeder::class,
            DigitalProductSeeder::class,
            MonthlyTargetSeeder::class,
            UserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
