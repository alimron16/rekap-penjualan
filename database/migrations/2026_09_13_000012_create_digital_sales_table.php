<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_sales', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->dateTime('date');
            $table->foreignId('digital_product_id')->constrained('digital_products')->restrictOnDelete();
            $table->string('customer_number', 50); // No HP / No Meter PLN / ID Game
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('hpp', 15, 2)->default(0);
            $table->decimal('profit_margin', 15, 2)->default(0);
            $table->foreignId('deposit_account_id')->constrained('accounts')->restrictOnDelete(); // Source deposit account (e.g. SALDO MULTI)
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete(); // Destination cash account (e.g. CASH RETAIL)
            $table->enum('status', ['SUKSES', 'GAGAL'])->default('SUKSES');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_sales');
    }
};
