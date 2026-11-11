<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DangKyCaKhaDungSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            DB::table('dang_ky_ca_kha_dungs')->updateOrInsert(
                ['dang_ky_kha_dung_id' => $i],
                [
                    'tuan_dang_ky_id' => $i,
                    'ca_mau_id' => (($i - 1) % 15) + 1,
                    'thu_trong_tuan' => (($i - 1) % 7) + 1,
                    'trang_thai' => $i % 5 === 0
                        ? 'UNAVAILABLE'
                        : 'AVAILABLE',
                ]
            );
        }
    }
}
