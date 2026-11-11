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
        Schema::create('nhu_cau_cas', function (Blueprint $table) {
            $table->id('nhu_cau_ca_id');
            $table->unsignedBigInteger('ca_mau_id');
            $table->date('ngay_lam');
            $table->unsignedInteger('so_luong_can');
            $table->unsignedBigInteger('role_tag_id');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhu_cau_cas');
    }
};
