<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-Outlet Inventory, Customer & Purchase Isolation
 *
 * Changes:
 * 1. Create `product_stocks` — per-outlet stock ledger
 * 2. Add `outlet_id` to `customers`
 * 3. Add `outlet_id` to `purchases`
 * 4. Add `outlet_id` + `user_id` to `inventory_adjustments`
 * 5. Seed existing data into default outlet (OUT-001)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // 1. product_stocks — per-outlet stock ledger
        // ----------------------------------------------------------------
        if (!Schema::hasTable('product_stocks')) {
            Schema::create('product_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->decimal('stock', 12, 2)->default(0);
                $table->integer('min_stock')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'outlet_id']);
                $table->index(['outlet_id', 'stock']);
            });
        }

        // ----------------------------------------------------------------
        // 2. outlet_id on customers
        // ----------------------------------------------------------------
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'outlet_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('outlet_id')->nullable()->after('id')->constrained('outlets')->nullOnDelete();
                $table->index('outlet_id');
            });
        }

        // ----------------------------------------------------------------
        // 3. outlet_id + user_id on purchases
        // ----------------------------------------------------------------
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->after('id')->constrained('outlets')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchases', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('outlet_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        // ----------------------------------------------------------------
        // 4. outlet_id + user_id on inventory_adjustments
        // ----------------------------------------------------------------
        if (Schema::hasTable('inventory_adjustments')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_adjustments', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->after('adjustment_number')->constrained('outlets')->nullOnDelete();
                }
                if (!Schema::hasColumn('inventory_adjustments', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('outlet_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        // ----------------------------------------------------------------
        // 5. Seed existing data → default outlet (OUT-001)
        // ----------------------------------------------------------------
        $defaultOutlet = DB::table('outlets')->where('code', 'OUT-001')->first()
            ?? DB::table('outlets')->orderBy('id')->first();

        if (!$defaultOutlet) {
            return; // No outlets yet; skip seeding
        }

        $oid = $defaultOutlet->id;

        // Seed product_stocks from products.stock for the default outlet
        $products = DB::table('products')->get(['id', 'stock', 'min_stock']);
        foreach ($products as $product) {
            DB::table('product_stocks')->insertOrIgnore([
                'product_id' => $product->id,
                'outlet_id'  => $oid,
                'stock'      => $product->stock,
                'min_stock'  => $product->min_stock ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Backfill customers that have no outlet_id → default outlet
        DB::table('customers')->whereNull('outlet_id')->update(['outlet_id' => $oid]);

        // Backfill purchases without outlet_id → default outlet
        DB::table('purchases')->whereNull('outlet_id')->update(['outlet_id' => $oid]);

        // Backfill inventory_adjustments without outlet_id → default outlet
        DB::table('inventory_adjustments')->whereNull('outlet_id')->update(['outlet_id' => $oid]);
    }

    public function down(): void
    {
        // inventory_adjustments
        if (Schema::hasTable('inventory_adjustments')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_adjustments', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
                if (Schema::hasColumn('inventory_adjustments', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }

        // purchases
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
                if (Schema::hasColumn('purchases', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }

        // customers
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'outlet_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            });
        }

        Schema::dropIfExists('product_stocks');
    }
};
