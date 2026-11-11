<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaMauSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['Ca sáng ', '06:00:00', '12:00:00'],
            ['Ca chiều ', '12:00:00', '17:00:00'],
            ['Ca tối', '17:00:00', '21:00:00'],
        ];

        foreach ($shifts as $i => [$name, $start, $end]) {
            DB::table('ca_maus')->updateOrInsert(
                ['ca_mau_id' => $i + 1],
                [
                    'ten_ca' => $name,
                    'gio_bat_dau' => $start,
                    'gio_ket_thuc' => $end,
                    'trang_thai' => 'ACTIVE',
                ]
            );
        }
    }
}
