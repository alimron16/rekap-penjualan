<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_targets', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('target_profit', 15, 2)->default(0);
            $table->integer('target_vocer')->default(0);
            $table->integer('target_perdana')->default(0);
            $table->integer('target_acc')->default(0);
            $table->decimal('target_transfer', 15, 2)->default(0);
            $table->decimal('target_elektrik', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_targets');
    }
};
