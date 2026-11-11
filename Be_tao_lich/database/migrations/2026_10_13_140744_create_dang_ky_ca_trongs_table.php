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
        Schema::create('dang_ky_ca_trongs', function (Blueprint $table) {
            $table->id('dang_ky_ca_trong_id');
            $table->unsignedBigInteger('ca_trong_id');
            $table->unsignedBigInteger('nhan_vien_id');
            $table->dateTime('thoi_gian_dang_ky');
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dang_ky_ca_trongs');
    }
};
