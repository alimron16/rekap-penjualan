<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class ResetDemoDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-demo {--fresh : Jalankan migrate:fresh sebelum seed data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset dan buat ulang akun demo serta data dummy POS Elephant Cell';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai penyiapan data demo & dummy Elephant POS...');

        if ($this->option('fresh')) {
            $this->warn('Menjalankan migrate:fresh --seed...');
            $this->call('migrate:fresh', ['--seed' => true]);
        } else {
            $this->info('Menjalankan seeder akun & data demo...');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class]);
        }

        $this->newLine();
        $this->info('====================================================');
        $this->info('   DATA DEMO BERHASIL DIRESET & SIAP DIGUNAKAN');
        $this->info('====================================================');
        $this->line('<fg=yellow;options=bold>[1] AKUN DEMO ADMIN (Kantor Pusat):</>');
        $this->line('    Email    : <fg=green>demo@elephantcell.com</> (atau demo.admin@elephantcell.com)');
        $this->line('    Password : <fg=green>demo12345</>');
        $this->line('    Role     : Super Admin / Admin (Akses Penuh Semua Toko)');
        $this->newLine();
        $this->line('<fg=yellow;options=bold>[2] AKUN DEMO KASIR (Cabang Tambun):</>');
        $this->line('    Email    : <fg=green>demo.kasir@elephantcell.com</>');
        $this->line('    Password : <fg=green>demo12345</>');
        $this->line('    Role     : Kasir Toko (Akses POS, Shift, Retur)');
        $this->newLine();
        $this->line('<fg=yellow;options=bold>[3] AKUN DEMO KASIR (Cabang Cibitung):</>');
        $this->line('    Email    : <fg=green>kasir@elephantcell.com</>');
        $this->line('    Password : <fg=green>demo12345</>');
        $this->line('    Role     : Kasir Toko');
        $this->info('====================================================');

        return self::SUCCESS;
    }
}
