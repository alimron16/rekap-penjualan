<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->enum('sale_type', ['retail', 'grosir'])->default('retail');
            $table->dateTime('date');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_receivable', 15, 2)->default(0);
            $table->string('payment_method', 50)->default('Tunai'); // Tunai, Piutang, Transfer
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete(); // Destination cash/bank account
            $table->string('status', 30)->default('LUNAS'); // LUNAS, BELUM LUNAS
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'sale_type', 'status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('hpp', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('stock_before', 12, 2)->default(0);
            $table->decimal('stock_after', 12, 2)->default(0);
            $table->string('batch_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
