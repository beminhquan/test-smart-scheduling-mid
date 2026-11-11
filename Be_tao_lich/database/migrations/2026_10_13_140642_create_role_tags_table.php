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
        Schema::create('role_tags', function (Blueprint $table) {
            $table->id('role_tag_id');
            $table->string('ten_role', 100);
            $table->text('mo_ta')->nullable();
            $table->string('trang_thai', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_tags');
    }
};
