<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('ELEPHANT CELL GROUP');
            $table->string('phone')->nullable()->default('088212283661');
            $table->string('address', 500)->nullable()->default('Kav. Virlania Tridaya Sakti, Kec. Tambun Selatan Kab. Bekasi');
            $table->string('logo_path')->nullable();
            $table->integer('active_year')->default(2026);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
