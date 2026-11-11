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
        Schema::create('rang_buoc', function (Blueprint $table) {
             $table->id('id_rang_buoc');

            $table->string('ma_rang_buoc', 60)->unique();

            $table->string('ten_rang_buoc', 255);

            $table->enum('loai_rang_buoc', ['HARD', 'SOFT']);

            $table->json('gia_tri');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rang_buoc');
    }
};
