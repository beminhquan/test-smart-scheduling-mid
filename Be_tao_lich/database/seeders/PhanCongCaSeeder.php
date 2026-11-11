<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhanCongCaSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            DB::table('phan_cong_cas')->updateOrInsert(
                ['phan_cong_ca_id' => $i],
                [
                    'lich_id' => 1,
                    'nhu_cau_ca_id' => $i,
                    'nhan_vien_id' => $i,
                    'ca_mau_id' => (($i - 1) % 15) + 1,
                    'ngay_lam' => date(
                        'Y-m-d',
                        strtotime('2026-10-19 +' . (($i - 1) % 7) . ' days')
                    ),
                    'trang_thai' => 'ASSIGNED',
                    'xac_nhan' => $i % 2 === 0,
                ]
            );
        }
    }
}
