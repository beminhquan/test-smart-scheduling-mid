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
        if (Schema::hasTable('ca_maus') && !Schema::hasColumn('ca_maus', 'he_so_luong')) {
            Schema::table('ca_maus', function (Blueprint $table) {
                $table->decimal('he_so_luong', 3, 2)->default(1.00)->after('gio_ket_thuc');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ca_maus') && Schema::hasColumn('ca_maus', 'he_so_luong')) {
            Schema::table('ca_maus', function (Blueprint $table) {
                $table->dropColumn('he_so_luong');
            });
        }
    }
};
