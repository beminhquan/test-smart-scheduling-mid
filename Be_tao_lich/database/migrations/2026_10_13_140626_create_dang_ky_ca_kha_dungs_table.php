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
        Schema::create('dang_ky_ca_kha_dungs', function (Blueprint $table) {
            $table->id('dang_ky_kha_dung_id');
            $table->unsignedBigInteger('tuan_dang_ky_id');
            $table->unsignedBigInteger('ca_mau_id');
            $table->unsignedTinyInteger('thu_trong_tuan');
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dang_ky_ca_kha_dungs');
    }
};
