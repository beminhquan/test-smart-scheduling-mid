<?php
require __DIR__ . '/Be_tao_lich/vendor/autoload.php';
$app = require_once __DIR__ . '/Be_tao_lich/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LichLamViec;
use App\Models\PhanCongCa;

foreach (LichLamViec::all() as $l) {
    $pcCount = PhanCongCa::where('lich_id', $l->lich_id)->count();
    echo sprintf("ID: %-3d | TuNgay: %s | DenNgay: %s | TrangThai: %-12s | PhienBan: %-3d | PhanCongCount: %d\n",
        $l->lich_id,
        $l->tu_ngay,
        $l->den_ngay,
        $l->trang_thai,
        $l->phien_ban,
        $pcCount
    );
}

