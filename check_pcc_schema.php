<?php
require __DIR__ . '/Be_tao_lich/vendor/autoload.php';
$app = require_once __DIR__ . '/Be_tao_lich/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (DB::select("DESCRIBE phan_cong_cas") as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Default: " . json_encode($c->Default) . "\n";
}

