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
        Schema::create('bang_luongs', function (Blueprint $table) {
            $table->id('bang_luong_id');
            $table->unsignedBigInteger('nhan_vien_id');
            $table->date('tu_ngay');
            $table->date('den_ngay');
            $table->decimal('tong_gio_lam', 8, 2)->default(0);
            $table->decimal('muc_luong_gio', 12, 2);
            $table->decimal('tong_tien', 14, 2)->default(0);
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bang_luongs');
    }
};
