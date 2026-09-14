<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('store_name')->nullable();
            $table->string('bank_name', 50);
            $table->string('account_number', 50);
            $table->string('account_holder', 100);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('proof_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_transfers');
    }
};
