<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create shift_logs table
        if (!Schema::hasTable('shift_logs')) {
            Schema::create('shift_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();

                // Deposit & Retained Modal breakdown
                $table->decimal('cash_retail_deposited', 15, 2)->default(0);
                $table->decimal('cash_retail_retained', 15, 2)->default(0);
                $table->decimal('cash_multi_deposited', 15, 2)->default(0);
                $table->decimal('cash_multi_retained', 15, 2)->default(0);
                $table->decimal('cash_transfer_deposited', 15, 2)->default(0);
                $table->decimal('cash_transfer_retained', 15, 2)->default(0);
                $table->decimal('total_deposited', 15, 2)->default(0);

                // Performance summary snapshot
                $table->decimal('cash_sales', 15, 2)->default(0);
                $table->decimal('non_cash_sales', 15, 2)->default(0);
                $table->decimal('receivable_sales', 15, 2)->default(0);
                $table->decimal('digital_sales', 15, 2)->default(0);
                $table->decimal('digital_profit', 15, 2)->default(0);
                $table->decimal('transfer_cash', 15, 2)->default(0);
                $table->decimal('transfer_fee', 15, 2)->default(0);
                $table->decimal('withdraw_cash', 15, 2)->default(0);
                $table->decimal('withdraw_fee', 15, 2)->default(0);
                $table->decimal('expenses', 15, 2)->default(0);
                $table->integer('transaction_count')->default(0);

                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['outlet_id', 'end_time']);
            });
        }

        // 2. Add outlet_id and user_id to digital_sales
        if (Schema::hasTable('digital_sales')) {
            Schema::table('digital_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('digital_sales', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->after('id')->constrained('outlets')->nullOnDelete();
                }
                if (!Schema::hasColumn('digital_sales', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('outlet_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 3. Add outlet_id and user_id to cash_transactions
        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('cash_transactions', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->after('id')->constrained('outlets')->nullOnDelete();
                }
                if (!Schema::hasColumn('cash_transactions', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('outlet_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 4. Backfill outlet_id for digital_sales and cash_transactions if empty
        $defaultOutlet = DB::table('outlets')->first();
        if ($defaultOutlet) {
            DB::table('digital_sales')->whereNull('outlet_id')->update(['outlet_id' => $defaultOutlet->id]);
            DB::table('cash_transactions')->whereNull('outlet_id')->update(['outlet_id' => $defaultOutlet->id]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('cash_transactions', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
                if (Schema::hasColumn('cash_transactions', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }

        if (Schema::hasTable('digital_sales')) {
            Schema::table('digital_sales', function (Blueprint $table) {
                if (Schema::hasColumn('digital_sales', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
                if (Schema::hasColumn('digital_sales', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }

        Schema::dropIfExists('shift_logs');
    }
};
