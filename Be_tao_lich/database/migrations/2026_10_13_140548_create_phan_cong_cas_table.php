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
        Schema::create('phan_cong_cas', function (Blueprint $table) {
            $table->id('phan_cong_ca_id');
            $table->unsignedBigInteger('lich_id');
            $table->unsignedBigInteger('nhu_cau_ca_id');
            $table->unsignedBigInteger('nhan_vien_id');
            $table->unsignedBigInteger('ca_mau_id');
            $table->date('ngay_lam');
            $table->string('trang_thai', 30);
            $table->boolean('xac_nhan')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phan_cong_cas');
    }
};
