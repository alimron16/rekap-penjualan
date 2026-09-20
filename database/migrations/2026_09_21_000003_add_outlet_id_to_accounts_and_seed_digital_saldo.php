<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add outlet_id (nullable) to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('outlet_id')->nullable()->after('id');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('set null');
        });

        // 2. Grab current global balances before we split them
        $globalSaldoMulti  = (float) (DB::table('accounts')->where('code', '1-1131')->value('current_balance') ?? 0);
        $globalSaldoBca    = (float) (DB::table('accounts')->where('code', '1-1113')->value('current_balance') ?? 0);
        $globalCashRetail  = (float) (DB::table('accounts')->where('code', '1-1110')->value('current_balance') ?? 0);
        $globalSaldoBri    = (float) (DB::table('accounts')->where('code', '1-1120')->value('current_balance') ?? 0);

        // 3. Iterate over all active outlets — first outlet gets the existing balance, rest start at 0
        $outlets   = DB::table('outlets')->where('status', 'active')->orderBy('id')->get();
        $isFirst   = true;
        $now       = now();

        foreach ($outlets as $outlet) {
            $saldoMultiBalance  = $isFirst ? $globalSaldoMulti : 0;
            $saldoBcaBalance    = $isFirst ? $globalSaldoBca   : 0;
            $cashRetailBalance  = $isFirst ? $globalCashRetail : 0;
            $saldoBriBalance    = $isFirst ? $globalSaldoBri   : 0;

            $shortName = strtoupper($outlet->code); // e.g. OUT-001, OUT-002

            // SALDO MULTI per outlet  (deposit account for digital top-up server)
            DB::table('accounts')->insert([
                'outlet_id'       => $outlet->id,
                'code'            => '1-1131-' . $outlet->id,
                'name'            => 'SALDO MULTI ' . $shortName,
                'type'            => 'D',
                'group'           => 'AKTIVA',
                'initial_balance' => $saldoMultiBalance,
                'current_balance' => $saldoMultiBalance,
                'is_system_locked'=> false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            // SALDO BCA per outlet  (BCA account used as digital deposit source)
            DB::table('accounts')->insert([
                'outlet_id'       => $outlet->id,
                'code'            => '1-1113-' . $outlet->id,
                'name'            => 'SALDO BCA ' . $shortName,
                'type'            => 'D',
                'group'           => 'AKTIVA',
                'initial_balance' => $saldoBcaBalance,
                'current_balance' => $saldoBcaBalance,
                'is_system_locked'=> false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            // CASH RETAIL per outlet  (physical cash drawer of each store)
            DB::table('accounts')->insert([
                'outlet_id'       => $outlet->id,
                'code'            => '1-1110-' . $outlet->id,
                'name'            => 'CASH RETAIL ' . $shortName,
                'type'            => 'D',
                'group'           => 'AKTIVA',
                'initial_balance' => $cashRetailBalance,
                'current_balance' => $cashRetailBalance,
                'is_system_locked'=> false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            // SALDO BRI per outlet
            DB::table('accounts')->insert([
                'outlet_id'       => $outlet->id,
                'code'            => '1-1120-' . $outlet->id,
                'name'            => 'SALDO BRI ' . $shortName,
                'type'            => 'D',
                'group'           => 'AKTIVA',
                'initial_balance' => $saldoBriBalance,
                'current_balance' => $saldoBriBalance,
                'is_system_locked'=> false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $isFirst = false;
        }
    }

    public function down(): void
    {
        // Remove all per-outlet accounts created by this migration
        DB::table('accounts')->whereNotNull('outlet_id')->delete();

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn('outlet_id');
        });
    }
};
