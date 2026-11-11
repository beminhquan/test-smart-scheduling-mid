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
        if (Schema::hasTable('rang_buoc')) {
            Schema::table('rang_buoc', function (Blueprint $table) {
                if (Schema::hasColumn('rang_buoc', 'id')) {
                    $table->renameColumn('id', 'id_rang_buoc');
                }
                if (!Schema::hasColumn('rang_buoc', 'ma_rang_buoc')) {
                    $table->string('ma_rang_buoc', 60)->unique();
                }
                if (!Schema::hasColumn('rang_buoc', 'ten_rang_buoc')) {
                    $table->string('ten_rang_buoc', 255);
                }
                if (!Schema::hasColumn('rang_buoc', 'loai_rang_buoc')) {
                    $table->enum('loai_rang_buoc', ['HARD', 'SOFT']);
                }
                if (!Schema::hasColumn('rang_buoc', 'gia_tri')) {
                    $table->json('gia_tri')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rang_buoc')) {
            Schema::table('rang_buoc', function (Blueprint $table) {
                // rollback safe
            });
        }
    }
};
