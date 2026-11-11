<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TaiKhoanSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            DB::table('tai_khoans')->updateOrInsert(
                ['tai_khoan_id' => $i],
                [
                    'email' => $i === 1
                        ? 'manager@example.com'
                        : 'nhanvien' . ($i - 1) . '@example.com',
                    'mat_khau' => Hash::make('Password@123'),
                    'vai_tro' => $i === 1 ? 'MANAGER' : 'STAFF',
                    'trang_thai' => 'ACTIVE',
                ]
            );
        }
    }
}
