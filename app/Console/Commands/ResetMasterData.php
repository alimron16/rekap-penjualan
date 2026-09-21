<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMasterData extends Command
{
    protected $signature   = 'app:reset-master-data {--force : Langsung hapus tanpa konfirmasi}';
    protected $description = 'Hapus semua data transaksi & master (kecuali COA/akun) dan buat ulang hanya akun owner.';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  PERINGATAN: Semua data akan dihapus (penjualan, pembelian, stok, kas, produk, user, outlet, dll).');
            $this->warn('    Chart of Accounts (COA) TIDAK akan dihapus.');
            if (!$this->confirm('Lanjutkan?', false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Mulai reset data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Urutan: hapus transaksi dulu, baru master
        $tables = [
            // Transaksi keuangan
            'journal_entries',
            'journal_entry_items',
            'cash_transactions',
            'receivable_payments',
            'debt_payments',
            'agent_transfers',
            'shift_logs',
            'yearly_closings',

            // Transaksi penjualan & retur
            'sale_items',
            'sale_returns',
            'sales',

            // Transaksi pembelian
            'purchase_items',
            'purchases',

            // Transaksi digital/pulsa
            'digital_sales',

            // Stok & produk
            'inventory_adjustments',
            'product_stocks',

            // Master data (bisa diisi ulang manual)
            'products',
            'suppliers',
            'customers',
            'monthly_targets',

            // User & outlet (akan diisi ulang oleh seeder)
            'users',
            'outlets',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("   ✓ Truncated: <info>$table</info>");
            } else {
                $this->line("   ⚠ Skip (tidak ada): <comment>$table</comment>");
            }
        }

        // Reset saldo COA ke 0 (tapi COA-nya tetap ada)
        DB::table('accounts')->update([
            'current_balance' => 0,
        ]);
        $this->line('   ✓ Reset saldo semua akun COA ke <info>0</info>');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('✅ Semua tabel berhasil dikosongkan.');

        // Jalankan seeder ulang (outlet, akun, owner user)
        $this->info('🌱 Menjalankan seeder...');
        $this->call('db:seed', ['--class' => 'OutletSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'UserSeeder',   '--force' => true]);
        $this->call('db:seed', ['--class' => 'DigitalProductSeeder', '--force' => true]);

        $this->newLine();
        $this->info('🎉 Selesai! Akun yang aktif:');
        $this->table(
            ['Email', 'Role', 'Password'],
            [['owner@elephantcell.com', 'super_admin', 'owner123']]
        );

        return self::SUCCESS;
    }
}
