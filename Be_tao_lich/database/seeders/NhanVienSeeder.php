<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhanVienSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Nguyễn Minh Quân',
            'Trần Minh Anh',
            'Lê Hoàng Nam',
            'Phạm Ngọc Linh',
            'Võ Gia Huy',
            'Đặng Thu Hà',
            'Bùi Quốc Bảo',
            'Đỗ Khánh Vy',
            'Phan Đức Minh',
            'Ngô Thảo My',
            'Huỳnh Nhật Long',
            'Dương Bảo Trân',
            'Trương Thành Đạt',
            'Lý Phương Nhi',
            'Mai Anh Tuấn',
        ];

        for ($i = 1; $i <= 15; $i++) {
            DB::table('nhan_viens')->updateOrInsert(
                ['nhan_vien_id' => $i],
                [
                    'hinh_anh' => null,

                    'role_tag_id' => $i === 1
                        ? 1
                        : (($i % 2) + 2),

                    'tai_khoan_id' => $i,

                    'ho_ten' => $names[$i - 1],

                    'so_dien_thoai' => '090000' .
                        str_pad((string) $i, 4, '0', STR_PAD_LEFT),

                    'ngay_sinh' => sprintf(
                        '%04d-%02d-%02d',
                        1990 + ($i % 10),
                        1 + ($i % 12),
                        1 + ($i % 27)
                    ),

                    'loai_hop_dong' => 'PART_TIME',

                    'muc_luong_gio' => $i === 1
                        ? 40000
                        : 22000 + (($i % 5) * 1000),

                    'trang_thai' => 'ACTIVE',
                ]
            );
        }
    }
}
