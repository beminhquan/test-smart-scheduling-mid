<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhuCauCaSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            DB::table('nhu_cau_cas')->updateOrInsert(
                ['nhu_cau_ca_id' => $i],
                [
                    'ca_mau_id' => (($i - 1) % 15) + 1,
                    'ngay_lam' => date(
                        'Y-m-d',
                        strtotime('2026-10-19 +' . (($i - 1) % 7) . ' days')
                    ),
                    'so_luong_can' => 1 + ($i % 3),
                    'role_tag_id' => 2 + ($i % 2),
                    'ghi_chu' => 'Nhu cầu nhân sự mẫu #' . $i,
                ]
            );
        }
    }
}
