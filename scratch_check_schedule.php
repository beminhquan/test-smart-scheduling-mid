<?php
require __DIR__ . '/Be_tao_lich/vendor/autoload.php';
$app = require_once __DIR__ . '/Be_tao_lich/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\XepLichController;
use Illuminate\Http\Request;

$ctrl = app(XepLichController::class);
$req = new Request(['date' => '2026-11-02']);
$res = $ctrl->layLichTheoTuan($req);
$data = json_decode($res->getContent(), true);

echo "has_schedule: " . ($data['has_schedule'] ? 'true' : 'false') . "\n";
echo "lich_id: " . ($data['lich_id'] ?? 'null') . "\n";
echo "trang_thai: " . ($data['trang_thai'] ?? 'null') . "\n";
echo "total_assignments: " . ($data['total_assignments'] ?? '0') . "\n";
echo "total_required: " . ($data['total_required'] ?? '0') . "\n";
echo "deficient_shifts: " . ($data['deficient_shifts'] ?? '0') . "\n";

echo "\n--- First 3 cells in schedules ---\n";
$i = 0;
foreach ($data['schedules'] as $k => $cell) {
    echo "Cell $k: Ca={$cell['ca_ten']} ({$cell['ca_mau_id']}), Day={$cell['day_ten']} ({$cell['date']}), Req={$cell['required']}, Assigned={$cell['assigned_count']}, Missing={$cell['missing']}, Satisfied=" . ($cell['is_satisfied'] ? 'true' : 'false') . "\n";
    if (!empty($cell['role_requirements'])) {
        echo "   Role requirements: " . json_encode($cell['role_requirements']) . "\n";
    }
    if ($i++ >= 3) break;
}

