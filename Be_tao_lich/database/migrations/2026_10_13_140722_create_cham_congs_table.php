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
        Schema::create('cham_congs', function (Blueprint $table) {
            $table->id('cham_cong_id');
            $table->unsignedBigInteger('phan_cong_ca_id');
            $table->dateTime('gio_vao')->nullable();
            $table->dateTime('gio_ra')->nullable();
            $table->string('vi_tri_vao')->nullable();
            $table->string('vi_tri_ra')->nullable();
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cham_congs');
    }
};
