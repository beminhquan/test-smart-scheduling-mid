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
        Schema::create('tuan_dang_kis', function (Blueprint $table) {
            $table->id('tuan_dang_ky_id');
            $table->unsignedBigInteger('nhan_vien_id');
            $table->date('tu_ngay');
            $table->date('den_ngay');
            $table->string('trang_thai', 30);
            $table->dateTime('thoi_gian_gui')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tuan_dang_kies');
    }
};
