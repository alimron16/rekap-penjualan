<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->date('date');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_debt', 15, 2)->default(0);
            $table->string('payment_method', 50)->default('Tunai'); // Tunai, Hutang, Transfer
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete(); // Source cash/bank account if paid
            $table->string('status', 30)->default('LUNAS'); // LUNAS, BELUM LUNAS
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('stock_before', 12, 2)->default(0);
            $table->decimal('hpp_before', 15, 4)->default(0);
            $table->decimal('stock_after', 12, 2)->default(0);
            $table->decimal('hpp_after', 15, 4)->default(0);
            $table->string('batch_id')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
