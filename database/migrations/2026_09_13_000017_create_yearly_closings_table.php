<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yearly_closings', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->date('closing_date');
            $table->string('status', 30)->default('BELUM DIPROSES'); // BELUM DIPROSES, DIPROSES
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->foreignId('retained_earnings_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yearly_closings');
    }
};
