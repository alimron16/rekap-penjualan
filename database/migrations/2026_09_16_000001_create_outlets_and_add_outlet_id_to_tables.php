<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create outlets / stores master table
        if (!Schema::hasTable('outlets')) {
            Schema::create('outlets', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        // 2. Add outlet_id to users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('outlet_id')->nullable()->after('role')->constrained('outlets')->nullOnDelete();
            });
        }

        // 3. Add outlet_id, approved_by, approved_at to agent_transfers
        if (Schema::hasTable('agent_transfers')) {
            Schema::table('agent_transfers', function (Blueprint $table) {
                if (!Schema::hasColumn('agent_transfers', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->after('user_id')->constrained('outlets')->nullOnDelete();
                }
                if (!Schema::hasColumn('agent_transfers', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('processed_by')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('agent_transfers', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('processed_at');
                }
            });
        }

        // 4. Add outlet_id to sales
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'outlet_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('outlet_id')->nullable()->after('id')->constrained('outlets')->nullOnDelete();
            });
        }

        // 5. Seed default outlets and link existing data
        $outletTambun = DB::table('outlets')->where('code', 'OUT-001')->first();
        if (!$outletTambun) {
            $outletTambunId = DB::table('outlets')->insertGetId([
                'code' => 'OUT-001',
                'name' => 'Elephant Cell Tambun (Pusat)',
                'address' => 'Jl. Sultan Hasanudin No. 12, Tambun Selatan, Bekasi',
                'phone' => '081288991122',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $outletTambunId = $outletTambun->id;
        }

        if (!DB::table('outlets')->where('code', 'OUT-002')->exists()) {
            DB::table('outlets')->insert([
                'code' => 'OUT-002',
                'name' => 'Elephant Cell Cibitung (Cabang 2)',
                'address' => 'Jl. Bosih Raya No. 45, Cibitung, Bekasi',
                'phone' => '081288991133',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Backfill existing toko users with default outlet
        DB::table('users')
            ->where('role', 'toko')
            ->whereNull('outlet_id')
            ->update(['outlet_id' => $outletTambunId]);

        // Backfill existing transfers with default outlet and sync approved_by / approved_at
        DB::table('agent_transfers')
            ->whereNull('outlet_id')
            ->update(['outlet_id' => $outletTambunId]);

        DB::table('agent_transfers')
            ->whereNotNull('processed_by')
            ->whereNull('approved_by')
            ->update([
                'approved_by' => DB::raw('processed_by'),
                'approved_at' => DB::raw('processed_at'),
            ]);

        // Backfill existing sales
        DB::table('sales')
            ->whereNull('outlet_id')
            ->update(['outlet_id' => $outletTambunId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'outlet_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            });
        }

        if (Schema::hasTable('agent_transfers')) {
            Schema::table('agent_transfers', function (Blueprint $table) {
                if (Schema::hasColumn('agent_transfers', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
                if (Schema::hasColumn('agent_transfers', 'approved_by')) {
                    $table->dropForeign(['approved_by']);
                    $table->dropColumn('approved_by');
                }
                if (Schema::hasColumn('agent_transfers', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            });
        }

        Schema::dropIfExists('outlets');
    }
};
