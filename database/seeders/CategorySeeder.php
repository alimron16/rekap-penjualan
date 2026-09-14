<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $physicalTypes = [
            'VOCER', 'PERDANA', 'KABEL', 'CHARGER', 'HEADSET', 
            'FLASHDISK', 'ACC', 'KEPALA', 'ONE SET', 'AUDIO'
        ];

        $physicalBrands = [
            'INDOSAT', 'TRI', 'TELKOMSEL', 'XL', 'AXIS', 'SMARTFREN', 
            'ROBOT', 'VIVAN', 'STARGO', 'GM', 'DAP', 'VDENMENV', 
            'LOG ON', 'O LIKE', 'ELEPHANT', 'FOOMEE', 'V GEN', 'REXY', 'UP', 'MAESTRO'
        ];

        $digitalTypes = [
            'PULSA ELEKTRIK', 'PAKET DATA', 'PAKET TELEPHON', 'PAKET SMS', 
            'INJEK VOCER', 'PULSA TRANSFER', 'MASA AKTIF', 'TOKEN LISTRIK', 
            'E-MONEY', 'GAME'
        ];

        $digitalCategories = [
            'INDOSAT', 'TRI', 'TELKOMSEL', 'XL', 'AXIS', 'SMARTFREN', 
            'PLN', 'DANA', 'GOPAY', 'SHOPEPAY', 'LINK AJA', 'MAXIM', 
            'MOBILE LEGEND', 'FREE FIRE', 'PUBG', 'POINT BLANK', 'ARENA OF VALOR'
        ];

        $expenseCategories = [
            'LISTRIK / WIFI / AIR', 'BIAYA SEWA', 'BIAYA ATK', 'BUNGA PINJAMAN',
            'BIAYA PENGIRIMAN', 'BIAYA MAINTENANCE', 'BIAYA JAJAN', 'KERUGIAN PIUTANG',
            'BIAYA IKLAN', 'BIAYA PROMOSI', 'GAJI KARYAWAN', 'UPAH PEKERJAAN',
            'BIAYA FREELANCE', 'BIAYA KOMISI', 'INSENTIF KARYAWAN', 'BELANJA NON INVENTORY'
        ];

        foreach ($physicalTypes as $name) {
            Category::firstOrCreate(['type' => 'physical_type', 'name' => $name]);
        }

        foreach ($physicalBrands as $name) {
            Category::firstOrCreate(['type' => 'physical_brand', 'name' => $name]);
        }

        foreach ($digitalTypes as $name) {
            Category::firstOrCreate(['type' => 'digital_type', 'name' => $name]);
        }

        foreach ($digitalCategories as $name) {
            Category::firstOrCreate(['type' => 'digital_category', 'name' => $name]);
        }

        foreach ($expenseCategories as $name) {
            Category::firstOrCreate(['type' => 'expense_category', 'name' => $name]);
        }
    }
}
