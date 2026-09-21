<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoreSettingSeeder::class,  // Pengaturan toko
            OutletSeeder::class,        // Outlet/cabang
            AccountSeeder::class,       // Chart of Accounts (COA) — JANGAN dihapus
            CategorySeeder::class,      // Kategori produk
            DigitalProductSeeder::class,// Produk multi/pulsa
            UserSeeder::class,          // Hanya superadmin/owner
            // DemoDataSeeder tidak dijalankan — data bersih
        ]);
    }
}
