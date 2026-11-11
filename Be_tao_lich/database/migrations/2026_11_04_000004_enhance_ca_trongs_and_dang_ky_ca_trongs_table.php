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
        Schema::table('ca_trongs', function (Blueprint $table) {
            $table->unsignedBigInteger('lich_id')->nullable()->after('phan_cong_ca_id');
            $table->unsignedBigInteger('ca_mau_id')->nullable()->after('lich_id');
            $table->date('ngay_lam')->nullable()->after('ca_mau_id');
            $table->unsignedBigInteger('role_tag_id')->nullable()->after('ngay_lam');
            $table->integer('so_luong_can')->default(1)->after('role_tag_id');
            $table->integer('so_luong_da_nhan')->default(0)->after('so_luong_can');
            $table->decimal('tien_thuong', 12, 2)->default(0)->after('so_luong_da_nhan');
            $table->string('nguon_goc', 30)->default('MANUAL')->after('tien_thuong');
            $table->unsignedBigInteger('yeu_cau_nghi_id')->nullable()->after('nguon_goc');
        });

        Schema::table('dang_ky_ca_trongs', function (Blueprint $table) {
            $table->text('ghi_chu')->nullable()->after('trang_thai');
            $table->text('ly_do_tu_choi')->nullable()->after('ghi_chu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ca_trongs', function (Blueprint $table) {
            $table->dropColumn([
                'lich_id',
                'ca_mau_id',
                'ngay_lam',
                'role_tag_id',
                'so_luong_can',
                'so_luong_da_nhan',
                'tien_thuong',
                'nguon_goc',
                'yeu_cau_nghi_id',
            ]);
        });

        Schema::table('dang_ky_ca_trongs', function (Blueprint $table) {
            $table->dropColumn([
                'ghi_chu',
                'ly_do_tu_choi',
            ]);
        });
    }
};

