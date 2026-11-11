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
        Schema::create('yeu_cau_doi_cas', function (Blueprint $table) {
            $table->id('doi_ca_id');
            $table->unsignedBigInteger('nguoi_gui_id');
            $table->unsignedBigInteger('nguoi_nhan_id');
            $table->unsignedBigInteger('ca_gui_id');
            $table->unsignedBigInteger('ca_nhan_id');
            $table->text('ly_do')->nullable();
            $table->string('trang_thai', 30);
            $table->text('thoi_gian_phan_hoi')->nullable();
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
        Schema::dropIfExists('yeu_cau_doi_cas');
    }
};
