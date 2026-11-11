<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BangLuongSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $hours = 80 + (($i * 7) % 61);
            $rate = $i === 1 ? 40000 : 22000 + (($i % 5) * 1000);

            DB::table('bang_luongs')->updateOrInsert(
                ['bang_luong_id' => $i],
                [
                    'nhan_vien_id' => $i,
                    'tu_ngay' => '2026-10-01',
                    'den_ngay' => '2026-10-31',
                    'tong_gio_lam' => $hours,
                    'muc_luong_gio' => $rate,
                    'tong_tien' => $hours * $rate,
                    'trang_thai' => 'DRAFT',
                ]
            );
        }
    }
}
