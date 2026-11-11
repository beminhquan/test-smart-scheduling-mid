<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Carbon\Carbon;
use App\Models\NhanVien;
use App\Models\CaMau;
use App\Models\RoleTag;
use App\Models\NhuCauCa;
use App\Models\TuanDangKy;
use App\Models\DangKyCaKhaDung;
use App\Models\YeuCauNghi;
use App\Models\RangBuoc;
use App\Models\LichLamViec;
use App\Models\PhanCongCa;

class XepLichController extends Controller
{
    /**
     * 1. Lấy dữ liệu thực tế từ MySQL để chuẩn bị cho Solver Python OR-Tools
     */
    public function layDuLieuXepLich(string $weekMonday): array
    {
        $mon = Carbon::parse($weekMonday);
        $sun = $mon->copy()->addDays(6);
        $monDate = $mon->format('Y-m-d');
        $sunDate = $sun->format('Y-m-d');

        // A. Ca mẫu đang hoạt động
        $shifts = CaMau::where('trang_thai', 'ACTIVE')
            ->orderBy('gio_bat_dau', 'asc')
            ->get()
            ->map(function ($s) {
                $start = $s->gio_bat_dau;
                $end = $s->gio_ket_thuc;
                $code = 'morning';
                if (stripos($s->ten_ca, 'chiều') !== false) $code = 'afternoon';
                elseif (stripos($s->ten_ca, 'tối') !== false) $code = 'evening';

                $sMin = (int)substr($start, 0, 2) * 60 + (int)substr($start, 3, 2);
                $eMin = (int)substr($end, 0, 2) * 60 + (int)substr($end, 3, 2);
                if ($eMin <= $sMin) $eMin += 1440;
                $duration = round(($eMin - $sMin) / 60.0, 2);

                return [
                    'id'             => (int)$s->ca_mau_id,
                    'code'           => $code,
                    'name'           => $s->ten_ca,
                    'start_time'     => $start,
                    'end_time'       => $end,
                    'duration_hours' => $duration,
                    'status'         => 'ACTIVE',
                ];
            })
            ->toArray();

        // B. Danh mục vai trò nhân viên (loại trừ Quản lý)
        $roles = RoleTag::where('trang_thai', 'ACTIVE')
            ->whereNotIn('ten_role', ['Quản lý', 'Manager'])
            ->orderBy('role_tag_id', 'asc')
            ->get()
            ->map(fn($r) => [
                'id'   => (int)$r->role_tag_id,
                'name' => $r->ten_role,
            ])
            ->toArray();

        // C. Danh sách nhân viên STAFF đang hoạt động
        $employees = NhanVien::select('nhan_viens.*')
            ->join('tai_khoans', 'nhan_viens.tai_khoan_id', '=', 'tai_khoans.tai_khoan_id')
            ->where('tai_khoans.vai_tro', 'STAFF')
            ->where('nhan_viens.trang_thai', 'ACTIVE')
            ->orderBy('nhan_viens.nhan_vien_id', 'asc')
            ->get()
            ->map(function ($nv) {
                $empCode = 'NV' . str_pad($nv->nhan_vien_id, 3, '0', STR_PAD_LEFT);
                return [
                    'id'             => (int)$nv->nhan_vien_id,
                    'code'           => $empCode,
                    'name'           => $nv->ho_ten,
                    'role_tag_id'    => (int)$nv->role_tag_id,
                    'contract_type'  => $nv->loai_hop_dong ?? 'FULLTIME',
                    'max_hours_week' => 40.0,
                    'status'         => 'ACTIVE',
                ];
            })
            ->toArray();

        // D. Nhu cầu nhân sự (từ bảng nhu_cau_cas)
        $demandsQuery = NhuCauCa::whereBetween('ngay_lam', [$monDate, $sunDate])->get();

        // Nếu tuần này chưa có nhu cầu, tự động gọi ScheduleController tạo nhu cầu mặc định
        if ($demandsQuery->isEmpty()) {
            $scheduleCtrl = new ScheduleController();
            $scheduleCtrl->getInputConfig(new Request(['weekMonday' => $weekMonday]));
            $demandsQuery = NhuCauCa::whereBetween('ngay_lam', [$monDate, $sunDate])->get();
        }

        $demands = $demandsQuery->map(function ($nc) use ($mon) {
            $dayIdx = $mon->diffInDays(Carbon::parse($nc->ngay_lam));
            return [
                'date'           => $nc->ngay_lam,
                'day_index'      => (int)$dayIdx,
                'shift_id'       => (int)$nc->ca_mau_id,
                'role_tag_id'    => (int)$nc->role_tag_id,
                'required_count' => (int)$nc->so_luong_can,
            ];
        })->toArray();

        // E. Đăng ký khả năng làm việc (Rảnh / Bận)
        $tuanMap = TuanDangKy::where('tu_ngay', $weekMonday)->pluck('tuan_dang_ky_id', 'nhan_vien_id');
        $availabilities = [];

        if ($tuanMap->isNotEmpty()) {
            $dangKys = DangKyCaKhaDung::whereIn('tuan_dang_ky_id', $tuanMap->values())->get();
            $tuanToEmp = array_flip($tuanMap->toArray());

            foreach ($dangKys as $dk) {
                $empId = $tuanToEmp[$dk->tuan_dang_ky_id] ?? null;
                if ($empId) {
                    $dayIdx = (int)$dk->thu_trong_tuan - 1; // 1=T2 -> 0, ..., 7=CN -> 6
                    $st = in_array(strtoupper($dk->trang_thai), ['AVAILABLE', 'RANH']) ? 'AVAILABLE' : 'BUSY';
                    $availabilities[] = [
                        'employee_id' => (int)$empId,
                        'day_index'   => $dayIdx,
                        'shift_id'    => (int)$dk->ca_mau_id,
                        'status'      => $st,
                    ];
                }
            }
        }

        // F. Đơn nghỉ phép đã duyệt
        $leavesQuery = YeuCauNghi::whereIn('trang_thai', ['APPROVED', 'DA_DUYET'])
            ->where('tu_ngay', '<=', $sunDate)
            ->where('den_ngay', '>=', $monDate)
            ->get();

        $approvedLeaves = [];
        foreach ($leavesQuery as $al) {
            $startL = Carbon::parse(max($al->tu_ngay, $monDate));
            $endL = Carbon::parse(min($al->den_ngay, $sunDate));
            while ($startL->lte($endL)) {
                $approvedLeaves[] = [
                    'employee_id' => (int)$al->nhan_vien_id,
                    'date'        => $startL->format('Y-m-d'),
                ];
                $startL->addDay();
            }
        }

        // G. Toàn bộ 11 ràng buộc từ bảng rang_buoc
        $rbs = RangBuoc::where('ma_rang_buoc', '!=', 'GENERAL_SETTINGS')
            ->where('ma_rang_buoc', 'not like', 'WEEKLY_DEMAND_%')
            ->get();

        $constraints = [];
        foreach ($rbs as $rb) {
            $val = is_array($rb->gia_tri) ? $rb->gia_tri : json_decode($rb->gia_tri ?? '', true);
            $enabled = isset($val['enabled']) ? (bool)$val['enabled'] : true;
            $item = [
                'code'     => $rb->ma_rang_buoc,
                'name'     => $rb->ten_rang_buoc,
                'category' => strtoupper($rb->loai_rang_buoc ?? 'HARD'),
                'enabled'  => $enabled,
            ];
            if (isset($val['value'])) $item['value'] = (float)$val['value'];
            if (isset($val['weight'])) $item['weight'] = (float)$val['weight'];
            $constraints[] = $item;
        }

        return [
            'week_monday'     => $weekMonday,
            'shifts'          => $shifts,
            'roles'           => $roles,
            'employees'       => $employees,
            'demands'         => $demands,
            'availabilities'  => $availabilities,
            'approved_leaves' => $approvedLeaves,
            'constraints'     => $constraints,
            'solver_config'   => [
                'max_time_seconds' => 15.0,
                'num_workers'      => 4,
            ],
        ];
    }

    /**
     * 2. Gọi Python Scheduling Service (qua HTTP hoặc CLI fallback)
     */
    public function goiPythonSolver(array $payload): array
    {
        $pythonServiceUrl = env('PYTHON_SCHEDULING_URL', 'http://127.0.0.1:8001/api/v1/xep-lich');
        $httpTimeout = (int)env('PYTHON_SCHEDULING_TIMEOUT', 30);

        // 1. Thử gọi qua HTTP tới FastAPI service (với connectTimeout ngắn 3s để fallback nhanh nếu service chưa bật)
        try {
            $response = Http::connectTimeout(3)->timeout($httpTimeout)->post($pythonServiceUrl, $payload);
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data['status'])) {
                    return $data;
                }
                throw new \Exception('Kết quả JSON từ Python FastAPI không hợp lệ: ' . $response->body());
            }

            Log::warning("FastAPI service tại {$pythonServiceUrl} trả về HTTP {$response->status()}: " . $response->body());
        } catch (\Illuminate\Http\Client\ConnectionException $connEx) {
            Log::info("Không kết nối được tới Python FastAPI service tại {$pythonServiceUrl} (" . $connEx->getMessage() . "). Chuyển sang CLI fallback.");
        } catch (\Exception $httpException) {
            Log::warning("Lỗi khi gọi HTTP tới Python service (" . $httpException->getMessage() . "). Chuyển sang CLI fallback.");
        }

        // 2. Fallback: Chạy Python CLI trực tiếp (không import FastAPI để tránh lỗi Winsock 10106 trên Windows)
        $serviceDir = base_path('../scheduling-service');
        if (!file_exists($serviceDir . '/main.py')) {
            $serviceDir = base_path('scheduling-service');
        }

        $pythonExecutable = env('PYTHON_PATH', 'python');
        $process = new Process([$pythonExecutable, 'main.py', '--solve'], $serviceDir);
        $process->setInput(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $process->setTimeout(45);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Lỗi thực thi Python OR-Tools Solver (CLI fallback): ' . $process->getErrorOutput());
        }

        $output = json_decode($process->getOutput(), true);
        if (!$output || !isset($output['status'])) {
            throw new \Exception('Kết quả trả về từ Python Solver không hợp lệ: ' . $process->getOutput());
        }

        return $output;
    }

    /**
     * 3. API Tạo lịch tự động bằng Python OR-Tools
     * Endpoint: POST /api/schedule/auto-generate hoặc /api/xep-lich/tao-tu-dong
     */
    public function taoLichTuDong(Request $request)
    {
        $weekMonday = $request->input('weekMonday') ?? $request->input('week_monday');
        if (!$weekMonday || !strtotime($weekMonday)) {
            $weekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        $mon = Carbon::parse($weekMonday);
        $sun = $mon->copy()->addDays(6);
        $monDate = $mon->format('Y-m-d');
        $sunDate = $sun->format('Y-m-d');

        // Kiểm tra xem lịch tuần đã được xác nhận chính thức chưa
        $confirmedSchedule = LichLamViec::where('tu_ngay', '<=', $monDate)
            ->where('den_ngay', '>=', $sunDate)
            ->whereIn('trang_thai', ['CONFIRMED', 'PUBLISHED', 'DA_XAC_NHAN'])
            ->first();

        if ($confirmedSchedule) {
            return response()->json([
                'status'  => false,
                'message' => 'Lịch tuần này đã được xác nhận chính thức, không thể tạo lại.',
            ], 400);
        }

        try {
            // A. Chuẩn bị dữ liệu đầu vào thực tế từ MySQL
            $duLieuDauVao = $this->layDuLieuXepLich($weekMonday);

            if (empty($duLieuDauVao['employees'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy nhân viên (STAFF) nào đang hoạt động trong hệ thống.',
                ], 422);
            }

            if (empty($duLieuDauVao['shifts'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chưa cấu hình ca làm việc mẫu đang hoạt động.',
                ], 422);
            }

            if (empty($duLieuDauVao['demands'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chưa có định biên / nhu cầu nhân sự cho tuần từ ' . Carbon::parse($monDate)->format('d/m/Y') . ' đến ' . Carbon::parse($sunDate)->format('d/m/Y') . '. Vui lòng thiết lập nhu cầu trước khi tạo lịch.',
                ], 422);
            }

            if (empty($duLieuDauVao['availabilities'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chưa có nhân viên nào đăng ký lịch rảnh/bận cho tuần từ ' . Carbon::parse($monDate)->format('d/m/Y') . ' đến ' . Carbon::parse($sunDate)->format('d/m/Y') . '. Vui lòng kiểm tra và gửi đăng ký trước khi tạo lịch.',
                ], 422);
            }

            // B. Gửi sang Python OR-Tools CP-SAT
            $ketQuaSolver = $this->goiPythonSolver($duLieuDauVao);

            $status = $ketQuaSolver['status'] ?? 'UNKNOWN';

            if ($status === 'INFEASIBLE') {
                return response()->json([
                    'status'        => false,
                    'solver_status' => 'INFEASIBLE',
                    'message'       => 'Không tìm thấy phương án thỏa mãn các ràng buộc bắt buộc.',
                    'warnings'      => $ketQuaSolver['warnings'] ?? [],
                ], 422);
            }

            if (!in_array($status, ['OPTIMAL', 'FEASIBLE'])) {
                return response()->json([
                    'status'        => false,
                    'solver_status' => $status,
                    'message'       => 'Lỗi giải bài toán: ' . implode('; ', $ketQuaSolver['warnings'] ?? []),
                ], 500);
            }

            $user = $request->user();
            $managerId = $user ? $user->tai_khoan_id : 1;

            // C. Lưu lịch vào MySQL bằng Database Transaction
            DB::beginTransaction();

            // Tìm hoặc tạo mới bản ghi LichLamViec với trạng thái DRAFT
            $lich = LichLamViec::where('tu_ngay', $monDate)
                ->where('den_ngay', $sunDate)
                ->where('trang_thai', 'DRAFT')
                ->first();

            if ($lich) {
                // Xóa các phân công cũ của bản nháp để tránh trùng lặp
                PhanCongCa::where('lich_id', $lich->lich_id)->delete();
                $lich->update([
                    'phien_ban' => $lich->phien_ban + 1,
                    'updated_at'=> Carbon::now(),
                ]);
            } else {
                $lich = LichLamViec::create([
                    'tu_ngay'    => $monDate,
                    'den_ngay'   => $sunDate,
                    'phien_ban'  => 1,
                    'trang_thai' => 'DRAFT',
                    'tao_boi'    => $managerId,
                ]);
            }

            // Lấy danh sách nhu_cau_cas để gắn khóa ngoại nhu_cau_ca_id
            $nhuCauCas = NhuCauCa::whereBetween('ngay_lam', [$monDate, $sunDate])->get();
            $mapNhuCau = [];
            foreach ($nhuCauCas as $nc) {
                $mapNhuCau["{$nc->ngay_lam}_{$nc->ca_mau_id}_{$nc->role_tag_id}"] = $nc->nhu_cau_ca_id;
            }

            $assignments = $ketQuaSolver['assignments'] ?? [];
            $batchPhanCong = [];

            foreach ($assignments as $a) {
                $dateA = $a['date'];
                $shiftId = (int)$a['shift_id'];
                $roleId = (int)$a['role_tag_id'];
                $nhuCauId = $mapNhuCau["{$dateA}_{$shiftId}_{$roleId}"] ?? ($nhuCauCas->first()?->nhu_cau_ca_id ?? 1);

                $batchPhanCong[] = [
                    'lich_id'        => $lich->lich_id,
                    'nhu_cau_ca_id'  => $nhuCauId,
                    'nhan_vien_id'   => (int)$a['employee_id'],
                    'ca_mau_id'      => $shiftId,
                    'ngay_lam'       => $dateA,
                    'trang_thai'     => 'ASSIGNED',
                    'xac_nhan'       => 0,
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now(),
                ];
            }

            if (!empty($batchPhanCong)) {
                PhanCongCa::insert($batchPhanCong);
            }

            DB::commit();

            // D. Tạo ma trận đầy đủ 21 ô schedules kèm demand từ MySQL để React hiển thị tức thì
            $dayKeys = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $shiftKeys = ['morning', 'afternoon', 'evening'];
            $schedulesMap = [];

            // 1. Khởi tạo sẵn 21 ô
            foreach ($shiftKeys as $sKey) {
                foreach ($dayKeys as $idx => $dKey) {
                    $curDate = $mon->copy()->addDays($idx)->format('Y-m-d');
                    $sName = $sKey === 'morning' ? 'Ca sáng (06:00 - 12:00)' : ($sKey === 'afternoon' ? 'Ca chiều (12:00 - 17:00)' : 'Ca tối (17:00 - 22:00)');
                    $sShort = $sKey === 'morning' ? 'Sáng' : ($sKey === 'afternoon' ? 'Chiều' : 'Tối');
                    $sTime = $sKey === 'morning' ? '06:00 - 12:00' : ($sKey === 'afternoon' ? '12:00 - 17:00' : '17:00 - 22:00');

                    $key = "{$sKey}_{$dKey}";
                    $schedulesMap[$key] = [
                        'key'           => $key,
                        'shiftId'       => $sKey,
                        'dayId'         => $dKey,
                        'date'          => $curDate,
                        'required'      => 0,
                        'total'         => 0,
                        'phucVu'        => 0,
                        'phaChe'        => 0,
                        'thuNgan'       => 0,
                        'assigned'      => [],
                        'assigned_count'=> 0,
                        'missing'       => 0,
                        'is_satisfied'  => true,
                        'demand'        => [
                            'id'        => $sKey,
                            'name'      => $sName,
                            'shortName' => $sShort,
                            'time'      => $sTime,
                            'total'     => 0,
                            'phucVu'    => 0,
                            'phaChe'    => 0,
                            'thuNgan'   => 0,
                        ],
                    ];
                }
            }

            // 2. Điền định biên nhu cầu từ bảng nhu_cau_cas
            $caMauList = CaMau::where('trang_thai', 'ACTIVE')->get();
            $caCodeMap = [];
            foreach ($caMauList as $cm) {
                $cCode = 'morning';
                if (stripos($cm->ten_ca, 'chiều') !== false) $cCode = 'afternoon';
                elseif (stripos($cm->ten_ca, 'tối') !== false) $cCode = 'evening';
                $caCodeMap[$cm->ca_mau_id] = $cCode;
            }

            foreach ($nhuCauCas as $nc) {
                $dayDiff = $mon->diffInDays(Carbon::parse($nc->ngay_lam));
                $dKey = $dayKeys[$dayDiff] ?? 'T2';
                $cCode = $caCodeMap[$nc->ca_mau_id] ?? 'morning';
                $cellKey = "{$cCode}_{$dKey}";

                if (isset($schedulesMap[$cellKey])) {
                    $soLuong = (int)$nc->so_luong_can;
                    $schedulesMap[$cellKey]['required'] += $soLuong;
                    $schedulesMap[$cellKey]['total'] += $soLuong;
                    $schedulesMap[$cellKey]['demand']['total'] += $soLuong;

                    $rId = (int)$nc->role_tag_id;
                    if ($rId === 1 || $rId === 4) {
                        $schedulesMap[$cellKey]['phucVu'] += $soLuong;
                        $schedulesMap[$cellKey]['demand']['phucVu'] += $soLuong;
                    } elseif ($rId === 2) {
                        $schedulesMap[$cellKey]['phaChe'] += $soLuong;
                        $schedulesMap[$cellKey]['demand']['phaChe'] += $soLuong;
                    } elseif ($rId === 3) {
                        $schedulesMap[$cellKey]['thuNgan'] += $soLuong;
                        $schedulesMap[$cellKey]['demand']['thuNgan'] += $soLuong;
                    }
                }
            }

            // 3. Phân bổ danh sách nhân viên vào từng ca
            $shiftsById = collect($duLieuDauVao['shifts'])->keyBy('id');
            $employeesById = collect($duLieuDauVao['employees'])->keyBy('id');
            $rolesById = collect($duLieuDauVao['roles'])->keyBy('id');

            foreach ($assignments as $a) {
                $dayDiff = (int)$a['day_index'];
                $dayId = $dayKeys[$dayDiff] ?? 'T2';
                $shiftObj = $shiftsById[$a['shift_id']] ?? null;
                $cCode = $shiftObj['code'] ?? 'morning';

                $key = "{$cCode}_{$dayId}";
                $emp = $employeesById[$a['employee_id']] ?? null;
                if ($emp && isset($schedulesMap[$key])) {
                    $rName = $rolesById[$a['role_tag_id']]['name'] ?? 'Nhân viên';
                    $schedulesMap[$key]['assigned'][] = [
                        'id'           => $emp['code'],
                        'nhan_vien_id' => $emp['id'],
                        'name'         => $emp['name'],
                        'role'         => $rName,
                        'roles'        => [$rName],
                        'roleTagId'    => $a['role_tag_id'],
                    ];
                }
            }

            // 4. Tính toán trạng thái thiếu/đủ cho từng ô
            $deficientCount = 0;
            foreach ($schedulesMap as $k => &$cell) {
                $cell['assigned_count'] = count($cell['assigned']);
                $cell['missing'] = max(0, $cell['required'] - $cell['assigned_count']);
                $cell['is_satisfied'] = $cell['assigned_count'] >= $cell['required'];
                if (!$cell['is_satisfied']) {
                    $deficientCount++;
                }
            }
            unset($cell);

            // 5. Danh sách nhân viên STAFF active
            $staffList = collect($duLieuDauVao['employees'])->map(function ($e) use ($rolesById) {
                $rName = $rolesById[$e['role_tag_id']]['name'] ?? 'Nhân viên';
                return [
                    'id'           => $e['code'],
                    'nhan_vien_id' => $e['id'],
                    'name'         => $e['name'],
                    'role'         => $rName,
                    'roles'        => [$rName],
                    'roleTagId'    => $e['role_tag_id'],
                ];
            })->values()->toArray();

            return response()->json([
                'status'            => true,
                'solver_status'     => $status,
                'lich_id'           => $lich->lich_id,
                'trang_thai'        => 'DRAFT',
                'phien_ban'         => $lich->phien_ban,
                'weekMonday'        => $monDate,
                'weekSunday'        => $sunDate,
                'assignments_count' => count($assignments),
                'assignments'       => $assignments,
                'schedules'         => (object)$schedulesMap,
                'employees'         => $staffList,
                'total_required'    => array_sum(array_column($schedulesMap, 'required')),
                'deficient_shifts'  => $deficientCount,
                'unfilled_demands'  => $ketQuaSolver['unfilled_demands'] ?? [],
                'statistics'        => $ketQuaSolver['statistics'] ?? null,
                'solve_time'        => $ketQuaSolver['solve_time'] ?? 0.0,
                'warnings'          => $ketQuaSolver['warnings'] ?? [],
                'message'           => "Tạo lịch tự động bằng Google OR-Tools thành công (Bản nháp DRAFT: " . count($assignments) . " phân công).",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tạo lịch tự động: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. Lấy lịch làm việc đã tạo của một tuần
     * Endpoint: GET /api/schedule/weekly hoặc /api/xep-lich/theo-tuan
     */
    public function layLichTheoTuan(Request $request)
    {
        try {
            $weekMonday = $request->query('weekMonday') ?? $request->query('week_monday');
            if (!$weekMonday || !strtotime($weekMonday)) {
                $weekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            }

            $mon = Carbon::parse($weekMonday);
            $sun = $mon->copy()->addDays(6);
            $monDate = $mon->format('Y-m-d');
            $sunDate = $sun->format('Y-m-d');

            $lich = LichLamViec::where('tu_ngay', '<=', $monDate)
                ->where('den_ngay', '>=', $sunDate)
                ->orderBy('phien_ban', 'desc')
                ->first();

            if (!$lich) {
                return response()->json([
                    'status'       => true,
                    'has_schedule' => false,
                    'weekMonday'   => $monDate,
                    'message'      => 'Chưa có lịch làm việc cho tuần này.',
                    'schedules'    => new \stdClass(),
                ]);
            }

            $phanCongs = PhanCongCa::with(['nhanVien.roleTag', 'caMau'])
                ->where('lich_id', $lich->lich_id)
                ->get();

            if ($phanCongs->isEmpty()) {
                return response()->json([
                    'status'       => true,
                    'has_schedule' => false,
                    'weekMonday'   => $monDate,
                    'message'      => 'Lịch tuần này chưa có dữ liệu phân công ca.',
                    'schedules'    => new \stdClass(),
                ]);
            }

            // Nhóm theo shiftKey dạng morning_T2, afternoon_T3... để tương thích với React
            $dayKeys = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $shiftKeys = ['morning', 'afternoon', 'evening'];
            $schedulesMap = [];

            // 1. Khởi tạo sẵn toàn bộ 21 ô ma trận
            foreach ($shiftKeys as $sKey) {
                foreach ($dayKeys as $idx => $dKey) {
                    $curDate = $mon->copy()->addDays($idx)->format('Y-m-d');
                    $sName = $sKey === 'morning' ? 'Ca sáng (06:00 - 12:00)' : ($sKey === 'afternoon' ? 'Ca chiều (12:00 - 17:00)' : 'Ca tối (17:00 - 22:00)');
                    $sShort = $sKey === 'morning' ? 'Sáng' : ($sKey === 'afternoon' ? 'Chiều' : 'Tối');
                    $sTime = $sKey === 'morning' ? '06:00 - 12:00' : ($sKey === 'afternoon' ? '12:00 - 17:00' : '17:00 - 22:00');

                    $key = "{$sKey}_{$dKey}";
                    $schedulesMap[$key] = [
                        'key'           => $key,
                        'shiftId'       => $sKey,
                        'dayId'         => $dKey,
                        'date'          => $curDate,
                        'required'      => 0,
                        'total'         => 0,
                        'phucVu'        => 0,
                        'phaChe'        => 0,
                        'thuNgan'       => 0,
                        'assigned'      => [],
                        'assigned_count'=> 0,
                        'missing'       => 0,
                        'is_satisfied'  => true,
                        'demand'        => [
                            'id'        => $sKey,
                            'name'      => $sName,
                            'shortName' => $sShort,
                            'time'      => $sTime,
                            'total'     => 0,
                            'phucVu'    => 0,
                            'phaChe'    => 0,
                            'thuNgan'   => 0,
                        ],
                    ];
                }
            }

            // 2. Điền định biên nhu cầu từ bảng nhu_cau_cas
            $caMauList = CaMau::where('trang_thai', 'ACTIVE')->get();
            $caCodeMap = [];
            foreach ($caMauList as $cm) {
                $cCode = 'morning';
                if (stripos($cm->ten_ca, 'chiều') !== false) $cCode = 'afternoon';
                elseif (stripos($cm->ten_ca, 'tối') !== false) $cCode = 'evening';
                $caCodeMap[$cm->ca_mau_id] = $cCode;
            }

            $nhuCaus = NhuCauCa::whereBetween('ngay_lam', [$monDate, $sunDate])->get();

            foreach ($nhuCaus as $nc) {
                $dayDiff = $mon->diffInDays(Carbon::parse($nc->ngay_lam));
                $dKey = $dayKeys[$dayDiff] ?? 'T2';
                $cCode = $caCodeMap[$nc->ca_mau_id] ?? 'morning';
                $cellKey = "{$cCode}_{$dKey}";

                if (isset($schedulesMap[$cellKey])) {
                    $soLuong = (int)$nc->so_luong_can;
                    $schedulesMap[$cellKey]['required'] += $soLuong;
                    $schedulesMap[$cellKey]['total'] += $soLuong;
                    $schedulesMap[$cellKey]['demand']['total'] += $soLuong;

                    $rId = (int)$nc->role_tag_id;
                    if ($rId === 1 || $rId === 4) {
                        $schedulesMap[$cellKey]['phucVu'] += $soLuong;
                        $schedulesMap[$cellKey]['demand']['phucVu'] += $soLuong;
                    } elseif ($rId === 2) {
                        $schedulesMap[$cellKey]['phaChe'] += $soLuong;
                        $schedulesMap[$cellKey]['demand']['phaChe'] += $soLuong;
                    } elseif ($rId === 3) {
                        $schedulesMap[$cellKey]['thuNgan'] += $soLuong;
                        $schedulesMap[$cellKey]['demand']['thuNgan'] += $soLuong;
                    }
                }
            }

            // 3. Phân bổ các phân công từ bảng phan_cong_cas
            foreach ($phanCongs as $pc) {
                $dayDiff = $mon->diffInDays(Carbon::parse($pc->ngay_lam));
                $dayId = $dayKeys[$dayDiff] ?? 'T2';
                $cCode = $caCodeMap[$pc->ca_mau_id] ?? 'morning';
                $key = "{$cCode}_{$dayId}";

                $nv = $pc->nhanVien;
                if ($nv && isset($schedulesMap[$key])) {
                    $empCode = 'NV' . str_pad($nv->nhan_vien_id, 3, '0', STR_PAD_LEFT);
                    $roleName = $nv->roleTag?->ten_role ?? 'Nhân viên';

                    $schedulesMap[$key]['assigned'][] = [
                        'id'           => $empCode,
                        'nhan_vien_id' => $nv->nhan_vien_id,
                        'name'         => $nv->ho_ten,
                        'role'         => $roleName,
                        'roles'        => [$roleName],
                        'roleTagId'    => $nv->role_tag_id,
                    ];
                }
            }

            // 4. Tính toán trạng thái thiếu/đủ cho từng ô
            $deficientCount = 0;
            foreach ($schedulesMap as $k => &$cell) {
                $cell['assigned_count'] = count($cell['assigned']);
                $cell['missing'] = max(0, $cell['required'] - $cell['assigned_count']);
                $cell['is_satisfied'] = $cell['assigned_count'] >= $cell['required'];
                if (!$cell['is_satisfied']) {
                    $deficientCount++;
                }
            }
            unset($cell);

            // 5. Lấy danh sách nhân viên STAFF active từ MySQL để phục vụ GridView / ListView
            $activeStaffs = NhanVien::with('roleTag')
                ->where('trang_thai', 'ACTIVE')
                ->whereHas('taiKhoan', function ($q) {
                    $q->where('vai_tro', 'STAFF');
                })
                ->orderBy('nhan_vien_id', 'asc')
                ->get()
                ->map(function ($nv) {
                    $rName = $nv->roleTag?->ten_role ?? 'Phục vụ';
                    return [
                        'id'           => 'NV' . str_pad($nv->nhan_vien_id, 3, '0', STR_PAD_LEFT),
                        'nhan_vien_id' => $nv->nhan_vien_id,
                        'name'         => $nv->ho_ten,
                        'role'         => $rName,
                        'roles'        => [$rName],
                        'roleTagId'    => $nv->role_tag_id,
                        'avatar'       => null,
                    ];
                });

            return response()->json([
                'status'            => true,
                'has_schedule'      => true,
                'lich_id'           => $lich->lich_id,
                'trang_thai'        => $lich->trang_thai,
                'phien_ban'         => $lich->phien_ban,
                'weekMonday'        => $monDate,
                'weekSunday'        => $sunDate,
                'total_assignments' => $phanCongs->count(),
                'total_required'    => array_sum(array_column($schedulesMap, 'required')),
                'deficient_shifts'  => $deficientCount,
                'schedules'         => (object)$schedulesMap,
                'employees'         => $activeStaffs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải lịch làm việc tuần: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. Xác nhận công bố lịch chính thức
     * Endpoint: POST /api/schedule/confirm hoặc /api/xep-lich/xac-nhan
     */
    public function xacNhanLich(Request $request)
    {
        try {
            $weekMonday = $request->input('weekMonday') ?? $request->input('week_monday');
            if (!$weekMonday) {
                return response()->json(['status' => false, 'message' => 'Thiếu weekMonday.'], 422);
            }

            $mon = Carbon::parse($weekMonday)->startOfWeek(Carbon::MONDAY);
            $sun = $mon->copy()->addDays(6);
            $monDate = $mon->format('Y-m-d');
            $sunDate = $sun->format('Y-m-d');

            $lich = LichLamViec::where(function ($q) use ($monDate, $sunDate) {
                    $q->where('tu_ngay', $monDate)
                      ->orWhere(function ($sub) use ($monDate, $sunDate) {
                          $sub->where('tu_ngay', '<=', $monDate)->where('den_ngay', '>=', $sunDate);
                      });
                })
                ->orderBy('phien_ban', 'desc')
                ->first();

            if (!$lich) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy lịch tuần để xác nhận.'], 404);
            }

            $lich->update([
                'trang_thai' => 'CONFIRMED',
                'updated_at' => Carbon::now(),
            ]);

            // Đồng bộ trạng thái toàn bộ các phân công ca thuộc lịch sang CONFIRMED
            PhanCongCa::where('lich_id', $lich->lich_id)->update([
                'trang_thai' => 'CONFIRMED',
                'xac_nhan'   => 1,
                'updated_at' => Carbon::now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Lịch làm việc tuần đã được xác nhận chính thức (CONFIRMED).',
                'data'    => $lich,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi xác nhận lịch: ' . $e->getMessage()], 500);
        }
    }

}
