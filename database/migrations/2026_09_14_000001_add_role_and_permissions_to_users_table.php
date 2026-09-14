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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('toko')->after('email');
            $table->string('store_name')->nullable()->after('role');
            $table->string('phone', 30)->nullable()->after('store_name');
            $table->json('permissions')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'store_name', 'phone', 'permissions', 'is_active']);
        });
    }
};
