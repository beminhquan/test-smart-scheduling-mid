<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleTagSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Quản lý',
            'Pha chế',
            'Thu ngân',
            'Phục vụ',
        ];

        foreach ($roles as $i => $name) {
            DB::table('role_tags')->updateOrInsert(
                ['role_tag_id' => $i + 1],
                [
                    'ten_role' => $name,
                    'mo_ta' => 'Vai trò mẫu: ' . $name,
                    'trang_thai' => 'ACTIVE',
                ]
            );
        }
    }
}

