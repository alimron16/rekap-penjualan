<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 50)->unique();
            $table->string('name');
            $table->string('trx_type', 50)->default('PULSA ELEKTRIK'); // TOKEN LISTRIK, PULSA ELEKTRIK, etc.
            $table->string('category', 50)->default('TELKOMSEL'); // PLN, INDOSAT, TELKOMSEL, etc.
            $table->decimal('hpp', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('status', 20)->default('OPEN'); // OPEN, CLOSE
            $table->timestamps();

            $table->index(['trx_type', 'category']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_products');
    }
};
