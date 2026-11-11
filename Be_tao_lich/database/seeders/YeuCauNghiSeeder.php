<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YeuCauNghiSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $day = date(
                'Y-m-d',
                strtotime('2026-10-20 +' . ($i - 1) . ' days')
            );

            DB::table('yeu_cau_nghis')->updateOrInsert(
                ['yeu_cau_nghi_id' => $i],
                [
                    'nhan_vien_id' => $i,
                    'tu_ngay' => $day,
                    'den_ngay' => $day,
                    'ly_do' => 'Lý do nghỉ mẫu #' . $i,
                    'trang_thai' => $i % 3 === 0
                        ? 'APPROVED'
                        : 'PENDING',
                    'nguoi_duyet_id' => $i % 3 === 0 ? 1 : null,
                    'thoi_gian_duyet' => $i % 3 === 0
                        ? '2026-10-18 12:00:00'
                        : null,
                ]
            );
        }
    }
}
