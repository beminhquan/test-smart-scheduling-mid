<?php
require __DIR__ . '/Be_tao_lich/vendor/autoload.php';
$app = require_once __DIR__ . '/Be_tao_lich/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CA TRONGS ===\n";
foreach (DB::select("DESCRIBE ca_trongs") as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Default: " . json_encode($c->Default) . "\n";
}

echo "\n=== YEU CAU NGHIS ===\n";
foreach (DB::select("DESCRIBE yeu_cau_nghis") as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Default: " . json_encode($c->Default) . "\n";
}

echo "\n=== CA TRONGS ROWS (First 5) ===\n";
print_r(DB::table('ca_trongs')->limit(5)->get()->toArray());

echo "\n=== YEU CAU NGHIS ROWS ===\n";
print_r(DB::table('yeu_cau_nghis')->get()->toArray());

echo "\n=== LICH LAM VIEC (02/11/2026 - 08/11/2026) ===\n";
$lich = DB::table('lich_lam_viecs')->where('tu_ngay', '<=', '2026-11-08')->where('den_ngay', '>=', '2026-11-02')->get();
print_r($lich->toArray());

echo "\n=== PHAN CONG CA ON 2026-11-03 FOR NV 15 ===\n";
print_r(DB::table('phan_cong_cas')->where('ngay_lam', '2026-11-03')->where('nhan_vien_id', 15)->get()->toArray());

echo "\n=== ALL PHAN CONG CA FOR LICH 3 (2026-11-02 to 2026-11-08) ===\n";
echo "Count: " . DB::table('phan_cong_cas')->where('lich_id', 3)->count() . "\n";
print_r(DB::table('phan_cong_cas')->where('lich_id', 3)->limit(5)->get()->toArray());

echo "\n=== NHU CAU CA FOR WEEK 2026-11-02 to 2026-11-08 ===\n";
echo "Count: " . DB::table('nhu_cau_cas')->whereBetween('ngay_lam', ['2026-11-02', '2026-11-08'])->count() . "\n";
print_r(DB::table('nhu_cau_cas')->whereBetween('ngay_lam', ['2026-11-02', '2026-11-08'])->get()->toArray());