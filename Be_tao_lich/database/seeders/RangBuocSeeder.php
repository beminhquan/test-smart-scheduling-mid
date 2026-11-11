<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RangBuocSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $rangBuocs = [
            [
                'ma_rang_buoc' => 'MIN_REST_HOURS',
                'ten_rang_buoc' => 'Thời gian nghỉ tối thiểu giữa 2 ngày làm',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => 12]),
            ],
            [
                'ma_rang_buoc' => 'NO_NIGHT_TO_MORNING',
                'ten_rang_buoc' => 'Không xếp ca tối rồi ca sáng hôm sau',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => true]),
            ],
            [
                'ma_rang_buoc' => 'MAX_SHIFTS_PER_DAY',
                'ten_rang_buoc' => 'Số ca tối đa trong một ngày',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => 1]),
            ],
            [
                'ma_rang_buoc' => 'MAX_HOURS_PER_DAY',
                'ten_rang_buoc' => 'Số giờ làm tối đa mỗi ngày',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => 8]),
            ],
            [
                'ma_rang_buoc' => 'MAX_HOURS_PER_WEEK',
                'ten_rang_buoc' => 'Số giờ làm tối đa mỗi tuần',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => 40]),
            ],
            [
                'ma_rang_buoc' => 'MAX_CONSECUTIVE_DAYS',
                'ten_rang_buoc' => 'Số ngày làm liên tiếp tối đa',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => 6]),
            ],
            [
                'ma_rang_buoc' => 'NO_SHIFT_ON_APPROVED_LEAVE',
                'ten_rang_buoc' => 'Không xếp ca vào ngày nghỉ đã duyệt',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => true]),
            ],
            [
                'ma_rang_buoc' => 'MATCH_ROLE_TAG',
                'ten_rang_buoc' => 'Xếp ca đúng vai trò chuyên môn',
                'loai_rang_buoc' => 'HARD',
                'gia_tri' => json_encode(['value' => true]),
            ],
            [
                'ma_rang_buoc' => 'FAIR_WORKING_HOURS',
                'ten_rang_buoc' => 'Phân bổ giờ làm công bằng',
                'loai_rang_buoc' => 'SOFT',
                'gia_tri' => json_encode(['enabled' => true]),
            ],
            [
                'ma_rang_buoc' => 'BALANCE_WEEKEND_SHIFTS',
                'ten_rang_buoc' => 'Chia đều ca cuối tuần',
                'loai_rang_buoc' => 'SOFT',
                'gia_tri' => json_encode(['enabled' => true]),
            ],
            [
                'ma_rang_buoc' => 'PREFER_STAFF_AVAILABILITY',
                'ten_rang_buoc' => 'Ưu tiên nguyện vọng của nhân viên',
                'loai_rang_buoc' => 'SOFT',
                'gia_tri' => json_encode(['enabled' => true]),
            ],
        ];

        foreach ($rangBuocs as $rangBuoc) {
            DB::table('rang_buoc')->updateOrInsert(
                ['ma_rang_buoc' => $rangBuoc['ma_rang_buoc']],
                [
                    'ten_rang_buoc' => $rangBuoc['ten_rang_buoc'],
                    'loai_rang_buoc' => $rangBuoc['loai_rang_buoc'],
                    'gia_tri' => $rangBuoc['gia_tri'],
                ]
            );
        }
    }
}
