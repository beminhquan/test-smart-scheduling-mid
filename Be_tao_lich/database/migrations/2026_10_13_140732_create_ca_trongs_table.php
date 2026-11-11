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
        Schema::create('ca_trongs', function (Blueprint $table) {
            $table->id('ca_trong_id');
            $table->unsignedBigInteger('phan_cong_ca_id');
            $table->dateTime('han_dang_ky');
            $table->text('mo_ta')->nullable();
            $table->string('trang_thai', 30);
            $table->unsignedBigInteger('nguoi_mo_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ca_trongs');
    }
};
