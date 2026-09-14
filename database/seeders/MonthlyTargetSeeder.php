<?php

namespace Database\Seeders;

use App\Models\MonthlyTarget;
use Illuminate\Database\Seeder;

class MonthlyTargetSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            ['year' => 2026, 'month' => 1, 'target_profit' => 10000000.0, 'target_vocer' => 1500, 'target_perdana' => 3000, 'target_acc' => 200, 'target_transfer' => 5000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 2, 'target_profit' => 10000000.0, 'target_vocer' => 1600, 'target_perdana' => 3500, 'target_acc' => 250, 'target_transfer' => 6000000.0, 'target_elektrik' => 1500000.0],
            ['year' => 2026, 'month' => 3, 'target_profit' => 12000000.0, 'target_vocer' => 1500, 'target_perdana' => 3000, 'target_acc' => 200, 'target_transfer' => 6000000.0, 'target_elektrik' => 1500000.0],
            ['year' => 2026, 'month' => 4, 'target_profit' => 15000000.0, 'target_vocer' => 300, 'target_perdana' => 500, 'target_acc' => 100, 'target_transfer' => 6000000.0, 'target_elektrik' => 2000000.0],
            ['year' => 2026, 'month' => 5, 'target_profit' => 15000000.0, 'target_vocer' => 500, 'target_perdana' => 500, 'target_acc' => 100, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 6, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 7, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 8, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 9, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 10, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 11, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
            ['year' => 2026, 'month' => 12, 'target_profit' => 15000000.0, 'target_vocer' => 1000, 'target_perdana' => 1000, 'target_acc' => 200, 'target_transfer' => 10000000.0, 'target_elektrik' => 1000000.0],
        ];

        foreach ($targets as $t) {
            MonthlyTarget::updateOrCreate(
                ['year' => $t['year'], 'month' => $t['month']],
                $t
            );
        }
    }
}
