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
        Schema::create('lich_lam_viecs', function (Blueprint $table) {
            $table->id('lich_id');
            $table->date('tu_ngay');
            $table->date('den_ngay');
            $table->unsignedInteger('phien_ban')->default(1);
            $table->string('trang_thai', 30);
            $table->unsignedBigInteger('tao_boi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_lam_viecs');
    }
};
