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
        Schema::create('nhan_viens', function (Blueprint $table) {
            $table->id('nhan_vien_id');
            $table->string('hinh_anh')->nullable();
            $table->unsignedBigInteger('role_tag_id');
            $table->unsignedBigInteger('tai_khoan_id')->nullable()->unique();
            $table->string('ho_ten', 255);
            $table->string('so_dien_thoai', 20)->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->string('loai_hop_dong', 50);
            $table->decimal('muc_luong_gio', 12, 2);
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhan_viens');
    }
};
