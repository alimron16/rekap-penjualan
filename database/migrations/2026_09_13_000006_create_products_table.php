<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name');
            $table->string('type', 50)->default('VOCER'); // VOCER, PERDANA, ACC, etc.
            $table->string('brand', 50)->nullable(); // INDOSAT, TELKOMSEL, etc.
            $table->decimal('stock', 12, 2)->default(0);
            $table->integer('min_stock')->default(0);
            $table->decimal('hpp', 15, 4)->default(0);
            $table->decimal('retail_price', 15, 2)->default(0);
            $table->decimal('wholesale_price', 15, 2)->default(0);
            $table->string('status', 50)->default('Masih Dijual'); // Masih Dijual / Tidak Dijual
            $table->timestamps();

            $table->index(['type', 'brand']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
