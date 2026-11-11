<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TuanDangKySeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $start = date(
                'Y-m-d',
                strtotime('2026-10-19 +' . ((($i - 1) % 3) * 7) . ' days')
            );

            DB::table('tuan_dang_kis')->updateOrInsert(
                ['tuan_dang_ky_id' => $i],
                [
                    'nhan_vien_id' => $i,
                    'tu_ngay' => $start,
                    'den_ngay' => date(
                        'Y-m-d',
                        strtotime($start . ' +6 days')
                    ),
                    'trang_thai' => $i % 4 === 0 ? 'DRAFT' : 'SUBMITTED',
                    'thoi_gian_gui' => $i % 4 === 0
                        ? null
                        : '2026-10-18 09:00:00',
                ]
            );
        }
    }
}
