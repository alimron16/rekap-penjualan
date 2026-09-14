<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->enum('type', ['IN', 'OUT', 'OPNAME']); // Item Masuk, Item Keluar, Stok Opname
            $table->dateTime('date');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('system_stock', 12, 2)->default(0);
            $table->decimal('actual_stock', 12, 2)->default(0);
            $table->decimal('diff_qty', 12, 2)->default(0);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
