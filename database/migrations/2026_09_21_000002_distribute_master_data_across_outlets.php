<?php

use App\Models\Customer;
use App\Models\DigitalProduct;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $outlets = Outlet::where('status', 'active')->orderBy('id')->get();
        if ($outlets->isEmpty()) {
            return;
        }

        $outlet1 = $outlets->firstWhere('id', 1) ?? $outlets->first();
        $outlet2 = $outlets->firstWhere('id', 2) ?? ($outlets->count() > 1 ? $outlets[1] : $outlet1);
        $outlet3 = $outlets->firstWhere('id', 3) ?? ($outlets->count() > 2 ? $outlets[2] : $outlet2);

        // -------------------------------------------------------------
        // 1. DISTRIBUTE CUSTOMERS PER OUTLET
        // -------------------------------------------------------------
        // Keep UMUM as Global (null)
        Customer::where('name', 'UMUM')->update(['outlet_id' => null]);

        $customersSeed = [
            // Outlet 1 (Tambun)
            ['name' => 'Budi Tambun Cell', 'phone' => '081234567801', 'address' => 'Jl. Sultan Hasanudin No. 12, Tambun', 'status' => 'Aktif', 'outlet_id' => $outlet1->id],
            ['name' => 'Konter Tambun Berkah', 'phone' => '081234567802', 'address' => 'Pasar Tambun Blok B', 'status' => 'Aktif', 'outlet_id' => $outlet1->id],
            ['name' => 'Ibu Siti Walk-in', 'phone' => '081234567803', 'address' => 'Perum Graha Prima Tambun', 'status' => 'Aktif', 'outlet_id' => $outlet1->id],

            // Outlet 2 (Cibitung)
            ['name' => 'Mitra Cibitung Cell', 'phone' => '085712345601', 'address' => 'Jl. Raya Teuku Umar No. 88, Cibitung', 'status' => 'Aktif', 'outlet_id' => $outlet2->id],
            ['name' => 'Warung Denis Cibitung', 'phone' => '085712345602', 'address' => 'Kawasan MM2100 Cibitung', 'status' => 'Aktif', 'outlet_id' => $outlet2->id],
            ['name' => 'Pak Hendra Grosir Cibitung', 'phone' => '085712345603', 'address' => 'Pasar Induk Cibitung', 'status' => 'Aktif', 'outlet_id' => $outlet2->id],

            // Outlet 3 (Cikarang)
            ['name' => 'Sentosa Phone Cikarang', 'phone' => '087812345601', 'address' => 'Jababeka 2 Blok A, Cikarang', 'status' => 'Aktif', 'outlet_id' => $outlet3->id],
            ['name' => 'Konter Cikarang Raya', 'phone' => '087812345602', 'address' => 'SGC Cikarang Lt. 1', 'status' => 'Aktif', 'outlet_id' => $outlet3->id],
            ['name' => 'Mas Joko Member Cikarang', 'phone' => '087812345603', 'address' => 'Lippo Cikarang', 'status' => 'Aktif', 'outlet_id' => $outlet3->id],
        ];

        foreach ($customersSeed as $c) {
            Customer::updateOrCreate(['name' => $c['name']], $c);
        }

        // -------------------------------------------------------------
        // 2. DISTRIBUTE SUPPLIERS PER OUTLET
        // -------------------------------------------------------------
        $suppliersSeed = [
            ['name' => 'DISTRIBUTOR UTAMA INDONESIA (GLOBAL)', 'phone' => '02188880001', 'address' => 'Roxy Mas Jakarta Pusat', 'outlet_id' => null],
            ['name' => 'GROSIR AKSESORIS TAMBUN JAYA', 'phone' => '02188880002', 'address' => 'Komplek Pertokoan Tambun', 'outlet_id' => $outlet1->id],
            ['name' => 'SUPPLIER SPAREPART CIBITUNG', 'phone' => '02188880003', 'address' => 'Ruko Cibitung Square', 'outlet_id' => $outlet2->id],
            ['name' => 'DISTRIBUTOR VOUCHER & PULSA CIKARANG', 'phone' => '02188880004', 'address' => 'Ruko Cikarang Central City', 'outlet_id' => $outlet3->id],
        ];

        foreach ($suppliersSeed as $s) {
            Supplier::updateOrCreate(['name' => $s['name']], $s);
        }

        // -------------------------------------------------------------
        // 3. DISTRIBUTE DIGITAL PRODUCTS PER OUTLET
        // -------------------------------------------------------------
        $allDigital = DigitalProduct::orderBy('id')->get();
        if ($allDigital->isNotEmpty()) {
            $totalDig = $allDigital->count();
            // Split them distinctly across Global, Outlet 1, Outlet 2, Outlet 3
            // Group 1: 15% Global (Semua Toko)
            // Group 2: 30% Outlet 1 (Tambun)
            // Group 3: 35% Outlet 2 (Cibitung)
            // Group 4: 20% Outlet 3 (Cikarang)
            $idx = 0;
            foreach ($allDigital as $dp) {
                if ($idx < 12) {
                    $dp->update(['outlet_id' => null]); // Global
                } elseif ($idx < 32) {
                    $dp->update(['outlet_id' => $outlet1->id]); // Tambun
                } elseif ($idx < 60) {
                    $dp->update(['outlet_id' => $outlet2->id]); // Cibitung
                } else {
                    $dp->update(['outlet_id' => $outlet3->id]); // Cikarang
                }
                $idx++;
            }
        }

        // -------------------------------------------------------------
        // 4. DISTRIBUTE PHYSICAL PRODUCT STOCKS PER OUTLET
        // -------------------------------------------------------------
        $products = Product::all();
        foreach ($products as $i => $p) {
            // Outlet 1: High stock (20 - 45)
            $stock1 = 20 + (($i * 3) % 25);
            // Outlet 2: Medium stock (5 - 18)
            $stock2 = 5 + (($i * 2) % 14);
            // Outlet 3: Low stock / critical (0 - 6, some 0 for testing)
            $stock3 = ($i % 3 === 0) ? 0 : (1 + ($i % 6));

            ProductStock::updateOrCreate(
                ['product_id' => $p->id, 'outlet_id' => $outlet1->id],
                ['stock' => $stock1, 'min_stock' => 5]
            );

            ProductStock::updateOrCreate(
                ['product_id' => $p->id, 'outlet_id' => $outlet2->id],
                ['stock' => $stock2, 'min_stock' => 5]
            );

            ProductStock::updateOrCreate(
                ['product_id' => $p->id, 'outlet_id' => $outlet3->id],
                ['stock' => $stock3, 'min_stock' => 5]
            );

            $p->syncTotalStock();
        }
    }

    public function down(): void
    {
        // No-op
    }
};
