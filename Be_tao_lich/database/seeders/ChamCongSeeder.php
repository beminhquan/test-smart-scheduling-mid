<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChamCongSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $day = date(
                'Y-m-d',
                strtotime('2026-10-19 +' . (($i - 1) % 7) . ' days')
            );

            $clockIn = $day . ' ' . sprintf(
                '%02d:00:00',
                6 + (($i - 1) % 10)
            );

            $clockOut = date(
                'Y-m-d H:i:s',
                strtotime($clockIn . ' +5 hours')
            );

            DB::table('cham_congs')->updateOrInsert(
                ['cham_cong_id' => $i],
                [
                    'phan_cong_ca_id' => $i,
                    'gio_vao' => $i % 4 === 0 ? null : $clockIn,
                    'gio_ra' => $i % 4 === 0 ? null : $clockOut,
                    'vi_tri_vao' => $i % 4 === 0 ? null : 'Cửa hàng',
                    'vi_tri_ra' => $i % 4 === 0 ? null : 'Cửa hàng',
                    'trang_thai' => $i % 4 === 0
                        ? 'PENDING'
                        : 'COMPLETED',
                ]
            );
        }
    }
}
