<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->enum('type', ['IN', 'OUT', 'TRANSFER']); // Kas Masuk, Kas Keluar, Kas Transfer
            $table->dateTime('date');
            $table->foreignId('debit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('credit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('admin_fee', 15, 2)->default(0); // Biaya Admin (Kas Transfer income)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
