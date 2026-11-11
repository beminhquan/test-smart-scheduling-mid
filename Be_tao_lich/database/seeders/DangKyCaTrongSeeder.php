<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DangKyCaTrongSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            DB::table('dang_ky_ca_trongs')->updateOrInsert(
                ['dang_ky_ca_trong_id' => $i],
                [
                    'ca_trong_id' => $i,
                    'nhan_vien_id' => ($i % 15) + 1,
                    'thoi_gian_dang_ky' => date(
                        'Y-m-d H:i:s',
                        strtotime('2026-10-18 09:00:00 +' . $i . ' minutes')
                    ),
                    'trang_thai' => $i % 3 === 0
                        ? 'APPROVED'
                        : 'PENDING',
                ]
            );
        }
    }
}
