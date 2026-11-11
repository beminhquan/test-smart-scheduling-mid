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
        Schema::create('ca_maus', function (Blueprint $table) {
            $table->id('ca_mau_id');
            $table->string('ten_ca', 100);
            $table->time('gio_bat_dau');
            $table->time('gio_ket_thuc');
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ca_maus');
    }
};
