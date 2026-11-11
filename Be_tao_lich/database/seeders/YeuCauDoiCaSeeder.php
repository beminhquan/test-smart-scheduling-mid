<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YeuCauDoiCaSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $receiver = ($i % 15) + 1;

            DB::table('yeu_cau_doi_cas')->updateOrInsert(
                ['doi_ca_id' => $i],
                [
                    'nguoi_gui_id' => $i,
                    'nguoi_nhan_id' => $receiver,
                    'ca_gui_id' => $i,
                    'ca_nhan_id' => $receiver,
                    'ly_do' => 'Yêu cầu đổi ca mẫu #' . $i,
                    'trang_thai' => $i % 4 === 0
                        ? 'APPROVED'
                        : 'PENDING',
                    'thoi_gian_phan_hoi' => null,
                    'nguoi_duyet_id' => $i % 4 === 0 ? 1 : null,
                    'thoi_gian_duyet' => $i % 4 === 0
                        ? '2026-10-18 12:00:00'
                        : null,
                ]
            );
        }
    }
}
