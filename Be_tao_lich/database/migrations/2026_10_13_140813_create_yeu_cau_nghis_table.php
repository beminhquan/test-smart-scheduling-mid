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
        Schema::create('yeu_cau_nghis', function (Blueprint $table) {
            $table->id('yeu_cau_nghi_id');
            $table->unsignedBigInteger('nhan_vien_id');
            $table->date('tu_ngay');
            $table->date('den_ngay');
            $table->text('ly_do');
            $table->string('trang_thai', 30);
            $table->unsignedBigInteger('nguoi_duyet_id')->nullable();
            $table->dateTime('thoi_gian_duyet')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_nghis');
    }
};
