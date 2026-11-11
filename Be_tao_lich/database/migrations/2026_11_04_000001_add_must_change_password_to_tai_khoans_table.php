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
        if (Schema::hasTable('tai_khoans') && !Schema::hasColumn('tai_khoans', 'must_change_password')) {
            Schema::table('tai_khoans', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false)->after('trang_thai');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tai_khoans') && Schema::hasColumn('tai_khoans', 'must_change_password')) {
            Schema::table('tai_khoans', function (Blueprint $table) {
                $table->dropColumn('must_change_password');
            });
        }
    }
};

