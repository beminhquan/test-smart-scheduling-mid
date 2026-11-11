<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LichLamViecSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $start = date(
                'Y-m-d',
                strtotime('2026-10-19 +' . (($i - 1) * 7) . ' days')
            );

            $end = date('Y-m-d', strtotime($start . ' +6 days'));

            DB::table('lich_lam_viecs')->updateOrInsert(
                ['lich_id' => $i],
                [
                    'tu_ngay' => $start,
                    'den_ngay' => $end,
                    'phien_ban' => 1,
                    'trang_thai' => $i === 1 ? 'APPROVED' : 'DRAFT',
                    'tao_boi' => 1,
                ]
            );
        }
    }
}
