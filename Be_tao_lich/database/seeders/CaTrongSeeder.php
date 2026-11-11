<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaTrongSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            DB::table('ca_trongs')->updateOrInsert(
                ['ca_trong_id' => $i],
                [
                    'phan_cong_ca_id' => $i,
                    'han_dang_ky' => date(
                        'Y-m-d H:i:s',
                        strtotime('2026-10-19 16:00:00 +' . ($i - 1) . ' days')
                    ),
                    'mo_ta' => 'Ca mở mẫu #' . $i,
                    'trang_thai' => $i % 4 === 0 ? 'CLOSED' : 'OPEN',
                    'nguoi_mo_id' => 1,
                ]
            );
        }
    }
}
