<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // AKTIVA
            ['code' => '1-0000', 'name' => 'AKTIVA', 'type' => 'H', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1000', 'name' => 'AKTIVA LANCAR', 'type' => 'H', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1100', 'name' => 'KAS & BANK', 'type' => 'H', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1110', 'name' => 'CASH RETAIL', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 480000.0, 'is_system_locked' => true],
            ['code' => '1-1111', 'name' => 'CASH TRANSFER', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1112', 'name' => 'CASH MULTI', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1113', 'name' => 'SALDO BCA', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 4804663.0, 'is_system_locked' => true],
            ['code' => '1-1120', 'name' => 'SALDO BRI', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 461000.0, 'is_system_locked' => true],
            ['code' => '1-1121', 'name' => 'SALDO MANDIRI', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-1122', 'name' => 'SALDO QRIS', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-1123', 'name' => 'SALDO SEABANK', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-1130', 'name' => 'SALDO DANA', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-1131', 'name' => 'SALDO MULTI', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 229686.0, 'is_system_locked' => true],
            ['code' => '1-1190', 'name' => 'BRANGKAS', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-1200', 'name' => 'PIUTANG', 'type' => 'H', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1210', 'name' => 'PIUTANG PENJUALAN', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-1211', 'name' => 'PIUTANG KARYAWAN', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-1212', 'name' => 'PIUTANG PINJAMAN', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '1-2000', 'name' => 'PERSEDIAAN', 'type' => 'H', 'group' => 'AKTIVA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '1-2010', 'name' => 'PERSEDIAAN BARANG', 'type' => 'D', 'group' => 'AKTIVA', 'initial_balance' => 11405608.0, 'is_system_locked' => true],

            // KEWAJIBAN
            ['code' => '2-0000', 'name' => 'KEWAJIBAN', 'type' => 'H', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '2-1000', 'name' => 'KEWAJIBAN LANCAR', 'type' => 'H', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '2-1100', 'name' => 'HUTANG', 'type' => 'H', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '2-1101', 'name' => 'HUTANG PEMBELIAN', 'type' => 'D', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '2-1102', 'name' => 'HUTANG PINJAMAN', 'type' => 'D', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '2-1200', 'name' => 'HUTANG GAJI', 'type' => 'D', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '2-1300', 'name' => 'PEMBAYARAN DIMUKA', 'type' => 'D', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '2-1310', 'name' => 'DP PEMBELIAN', 'type' => 'D', 'group' => 'KEWAJIBAN', 'initial_balance' => 0, 'is_system_locked' => false],

            // MODAL
            ['code' => '3-0000', 'name' => 'MODAL UTAMA', 'type' => 'H', 'group' => 'MODAL', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '3-1000', 'name' => 'MODAL AWAL', 'type' => 'D', 'group' => 'MODAL', 'initial_balance' => 16885608.0, 'is_system_locked' => true],
            ['code' => '3-2000', 'name' => 'LABA DITAHAN', 'type' => 'D', 'group' => 'MODAL', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '3-3000', 'name' => 'LABA TAHUN BERJALAN', 'type' => 'D', 'group' => 'MODAL', 'initial_balance' => 0, 'is_system_locked' => true],

            // PENDAPATAN
            ['code' => '4-0000', 'name' => 'PENDAPATAN', 'type' => 'H', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '4-1000', 'name' => 'PENDAPATAN RETAIL', 'type' => 'K', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '4-1100', 'name' => 'PENDAPATAN GROSIR', 'type' => 'K', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '4-1200', 'name' => 'PENDAPATAN JASA', 'type' => 'K', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '4-1300', 'name' => 'PENDAPATAN MULTI', 'type' => 'K', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '4-1500', 'name' => 'POTONGAN PENJUALAN', 'type' => 'D', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '4-1600', 'name' => 'RETUR PENJUALAN', 'type' => 'D', 'group' => 'PENDAPATAN', 'initial_balance' => 0, 'is_system_locked' => true],

            // HPP
            ['code' => '5-0000', 'name' => 'HPP', 'type' => 'H', 'group' => 'HPP', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '5-1000', 'name' => 'HPP PENJUALAN', 'type' => 'D', 'group' => 'HPP', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '5-1100', 'name' => 'HPP MULTI', 'type' => 'D', 'group' => 'HPP', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '5-1300', 'name' => 'POTONGAN PEMBELIAN', 'type' => 'D', 'group' => 'HPP', 'initial_balance' => 0, 'is_system_locked' => true],

            // BIAYA
            ['code' => '6-0000', 'name' => 'BIAYA', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '6-1000', 'name' => 'BIAYA UMUM', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '6-1100', 'name' => 'LISTRIK / WIFI / AIR', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1101', 'name' => 'BIAYA SEWA', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1102', 'name' => 'BIAYA ATK', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1103', 'name' => 'BUNGA PINJAMAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1104', 'name' => 'BIAYA PENGIRIMAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1105', 'name' => 'BIAYA MAINTENANCE', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1106', 'name' => 'BIAYA JAJAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-1500', 'name' => 'KERUGIAN PIUTANG', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-2000', 'name' => 'BIAYA PEMASARAN', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-2001', 'name' => 'BIAYA IKLAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-2002', 'name' => 'BIAYA PROMOSI', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-2200', 'name' => 'PENGATURAN STOK', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-2201', 'name' => 'ITEM MASUK', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '6-2202', 'name' => 'ITEM KELUAR', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '6-2203', 'name' => 'STOK OPNAME', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '6-3000', 'name' => 'BIAYA GAJI DAN UPAH', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-3001', 'name' => 'GAJI KARYAWAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-3002', 'name' => 'UPAH PEKERJAAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-3003', 'name' => 'BIAYA FREELANCE', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-3004', 'name' => 'BIAYA KOMISI', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-3005', 'name' => 'INSENTIF KARYAWAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-4000', 'name' => 'BIAYA OPERASIONAL', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-4001', 'name' => 'BELI BAHAN BAKU', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-5000', 'name' => 'BIAYA PENYUSUTAN', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-5001', 'name' => 'PENYUSUTAN', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-9000', 'name' => 'BIAYA NON INVENTORY', 'type' => 'H', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '6-9001', 'name' => 'BELANJA NON INVENTORY', 'type' => 'D', 'group' => 'BIAYA', 'initial_balance' => 0, 'is_system_locked' => false],

            // PENDAPATAN LAIN
            ['code' => '7-0000', 'name' => 'PENDAPATAN LAIN', 'type' => 'H', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '7-1000', 'name' => 'LABA SELISIH KURS', 'type' => 'K', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '7-2000', 'name' => 'PENDAPATAN JASA LAIN', 'type' => 'K', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '7-3000', 'name' => 'PENDAPATAN LAINNYA', 'type' => 'K', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '7-4000', 'name' => 'PENDAPATAN KOMISI', 'type' => 'K', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '7-5000', 'name' => 'PENDAPATAN INSENTIF', 'type' => 'K', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '7-6000', 'name' => 'KERINGANAN HUTANG', 'type' => 'K', 'group' => 'PENDAPATAN LAIN', 'initial_balance' => 0, 'is_system_locked' => false],

            // BIAYA LAIN
            ['code' => '8-0000', 'name' => 'BIAYA LAIN', 'type' => 'H', 'group' => 'BIAYA LAIN', 'initial_balance' => 0, 'is_system_locked' => true],
            ['code' => '8-1000', 'name' => 'BIAYA LAINNYA', 'type' => 'D', 'group' => 'BIAYA LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
            ['code' => '8-2000', 'name' => 'RUGI SELISIH KURS', 'type' => 'D', 'group' => 'BIAYA LAIN', 'initial_balance' => 0, 'is_system_locked' => false],
        ];

        foreach ($accounts as $acc) {
            Account::updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'group' => $acc['group'],
                    'initial_balance' => $acc['initial_balance'],
                    'current_balance' => $acc['initial_balance'],
                    'is_system_locked' => $acc['is_system_locked'],
                ]
            );
        }

        // Generate akun per-outlet untuk setiap cabang yang ada
        $outlets = \App\Models\Outlet::all();
        foreach ($outlets as $outlet) {
            Account::getOutletMultiAccount($outlet->id);
            Account::getOutletCashRetailAccount($outlet->id);

            Account::firstOrCreate(
                ['code' => '1-1113-' . $outlet->id],
                [
                    'outlet_id' => $outlet->id,
                    'name' => 'SALDO BCA ' . strtoupper($outlet->code),
                    'type' => 'D',
                    'group' => 'AKTIVA',
                    'initial_balance' => 0,
                    'current_balance' => 0,
                    'is_system_locked' => false,
                ]
            );

            Account::firstOrCreate(
                ['code' => '1-1120-' . $outlet->id],
                [
                    'outlet_id' => $outlet->id,
                    'name' => 'SALDO BRI ' . strtoupper($outlet->code),
                    'type' => 'D',
                    'group' => 'AKTIVA',
                    'initial_balance' => 0,
                    'current_balance' => 0,
                    'is_system_locked' => false,
                ]
            );
        }
    }
}
