<?php

namespace App\Http\Controllers;

use App\Models\CaMau;
use App\Models\DangKyCaKhaDung;
use App\Models\NhanVien;
use App\Models\RangBuoc;
use App\Models\TuanDangKy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AvailabilityController extends Controller
{
    /**
     * Chuẩn hóa tên ca (ví dụ: "Ca sáng " -> "Sáng", "Ca chiều " -> "Chiều")
     */
    protected function normalizeShiftName(string $name): string
    {
        $trimmed = trim($name);
        $withoutCa = trim(preg_replace('/^ca\s+/iu', '', $trimmed));
        return mb_convert_case($withoutCa, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Tính toán ngày thứ Hai của tuần và deadline đăng ký
     */
    protected function resolveWeekAndDeadline(?string $weekMonday): array
    {
        if (empty($weekMonday)) {
            $today = new \DateTime();
            $dayOfWeek = (int)$today->format('N'); // 1 (Thứ 2) .. 7 (Chủ nhật)
            $daysUntilNextMonday = 8 - $dayOfWeek;
            $mondayObj = (clone $today)->modify("+{$daysUntilNextMonday} days");
            $weekMonday = $mondayObj->format('Y-m-d');
        } else {
            $mondayObj = new \DateTime($weekMonday);
        }

        $sundayObj = (clone $mondayObj)->modify('+6 days');
        $weekSunday = $sundayObj->format('Y-m-d');
        $weekLabel = 'Tuần ' . $mondayObj->format('d/m/Y') . ' – ' . $sundayObj->format('d/m/Y');
        $weekRange = $mondayObj->format('d/m') . ' - ' . $sundayObj->format('d/m');

        // Đọc cấu hình hạn chót từ GENERAL_SETTINGS
        $generalSetting = RangBuoc::where('ma_rang_buoc', 'GENERAL_SETTINGS')->first();
        $deadlineDay = 'friday';
        $deadlineTime = '23:59';
        if ($generalSetting && !empty($generalSetting->gia_tri)) {
            $val = is_array($generalSetting->gia_tri) ? $generalSetting->gia_tri : json_decode($generalSetting->gia_tri, true);
            $deadlineDay = $val['availabilityDeadlineDay'] ?? 'friday';
            $deadlineTime = $val['availabilityDeadlineTime'] ?? '23:59';
        }

        $daysDiff = match (strtolower($deadlineDay)) {
            'thursday' => -4,
            'friday'   => -3,
            'saturday' => -2,
            'sunday'   => -1,
            default    => -3,
        };

        $deadlineObj = (clone $mondayObj)->modify("{$daysDiff} days");
        $timeParts = explode(':', $deadlineTime);
        $deadlineObj->setTime((int)($timeParts[0] ?? 23), (int)($timeParts[1] ?? 59), 59);

        $now = new \DateTime();
        $isPastDeadline = $now > $deadlineObj;
        $isLocked = $isPastDeadline;

        // Kiểm tra xem lịch tuần này đã được chốt (CONFIRMED / PUBLISHED) trong lich_lam_viecs chưa
        $scheduleFinalized = DB::table('lich_lam_viecs')
            ->where('tu_ngay', '<=', $weekMonday)
            ->where('den_ngay', '>=', $weekSunday)
            ->whereIn('trang_thai', ['CONFIRMED', 'PUBLISHED', 'DA_XAC_NHAN'])
            ->exists();

        if ($scheduleFinalized) {
            $isLocked = true;
        }

        // Tạo danh sách 7 ngày trong tuần
        $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $dObj = (clone $mondayObj)->modify("+{$i} days");
            $days[] = [
                'dayIndex' => $i,
                'dayName'  => $dayNames[$i],
                'dateIso'  => $dObj->format('Y-m-d'),
                'dateVn'   => $dObj->format('d/m'),
            ];
        }

        // Danh sách ca mẫu đang hoạt động
        $shifts = CaMau::whereIn('trang_thai', ['ACTIVE', 'active'])
            ->orderBy('gio_bat_dau', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'         => $s->ca_mau_id,
                    'name'       => $this->normalizeShiftName($s->ten_ca),
                    'ten_ca'     => $s->ten_ca,
                    'start'      => substr($s->gio_bat_dau, 0, 5),
                    'end'        => substr($s->gio_ket_thuc, 0, 5),
                    'startTime'  => substr($s->gio_bat_dau, 0, 5),
                    'endTime'    => substr($s->gio_ket_thuc, 0, 5),
                    'multiplier' => (float)($s->he_so_luong ?? 1.0),
                    'status'     => 'active',
                ];
            });

        return [
            'weekMonday'     => $weekMonday,
            'weekSunday'     => $weekSunday,
            'weekLabel'      => $weekLabel,
            'weekRange'      => $weekRange,
            'deadlineAt'     => $deadlineObj->format('c'),
            'deadlineFormatted' => $deadlineObj->format('H:i') . ' ' . $dayNames[(int)$deadlineObj->format('N') - 1] . ', ' . $deadlineObj->format('d/m/Y'),
            'isPastDeadline' => $isPastDeadline,
            'isLocked'       => $isLocked,
            'days'           => $days,
            'shifts'         => $shifts,
        ];
    }

    /**
     * 1. [Staff] Lấy dữ liệu đăng ký khả năng làm việc của chính mình cho một tuần
     * Endpoint: GET /api/availability/my
     */
    public function getMyAvailability(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            $nhanVien = $user->nhanVien ?? NhanVien::where('tai_khoan_id', $user->tai_khoan_id)->first();

            if (!$nhanVien) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy hồ sơ nhân viên liên kết với tài khoản này.',
                ], 404);
            }

            $weekInfo = $this->resolveWeekAndDeadline($request->query('weekMonday'));
            $weekMonday = $weekInfo['weekMonday'];
            $empCode = 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT);

            // Tìm tuần đăng ký của nhân viên
            $tuan = TuanDangKy::with('dangKyCaKhaDungs')
                ->where('nhan_vien_id', $nhanVien->nhan_vien_id)
                ->where('tu_ngay', $weekMonday)
                ->first();

            $slots = [];
            $isSubmitted = false;
            $submittedAt = null;

            $activeShifts = CaMau::all();
            $shiftNameMap = [];
            foreach ($activeShifts as $s) {
                $shiftNameMap[$s->ca_mau_id] = $this->normalizeShiftName($s->ten_ca);
            }

            if ($tuan) {
                $isSubmitted = strtoupper($tuan->trang_thai) === 'SUBMITTED';
                $submittedAt = $tuan->thoi_gian_gui ? (new \DateTime($tuan->thoi_gian_gui))->format('c') : null;

                foreach ($tuan->dangKyCaKhaDungs as $rec) {
                    $dayIdx = (int)$rec->thu_trong_tuan - 1;
                    $shiftName = $shiftNameMap[$rec->ca_mau_id] ?? ('Ca ' . $rec->ca_mau_id);
                    $val = in_array(strtoupper($rec->trang_thai), ['AVAILABLE', 'RANH']) ? 'available' : 'busy';
                    $slots["{$dayIdx}_{$shiftName}"] = $val;
                }
            }

            $responsePayload = [
                'id'             => "avail_{$empCode}_{$weekMonday}",
                'type'           => 'availability',
                'employeeId'     => $empCode,
                'nhan_vien_id'   => $nhanVien->nhan_vien_id,
                'weekMonday'     => $weekMonday,
                'weekLabel'      => $weekInfo['weekLabel'],
                'deadlineAt'     => $weekInfo['deadlineAt'],
                'deadlineFormatted' => $weekInfo['deadlineFormatted'],
                'isPastDeadline' => $weekInfo['isPastDeadline'],
                'isLocked'       => $weekInfo['isLocked'],
                'status'         => $isSubmitted ? 'submitted' : 'draft',
                'version'        => 1,
                'submittedAt'    => $submittedAt,
                'updatedAt'      => $tuan?->updated_at ? (new \DateTime($tuan->updated_at))->format('c') : null,
                'slots'          => $slots,
                'shifts'         => $weekInfo['shifts'],
                'days'           => $weekInfo['days'],
            ];

            return response()->json([
                'status' => true,
                'data'   => $responsePayload,
                ...$responsePayload,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lấy thông tin đăng ký: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. [Staff] Lưu đăng ký khả năng làm việc của chính mình
     * Endpoint: POST /api/availability/my
     */
    public function saveMyAvailability(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            $nhanVien = $user->nhanVien ?? NhanVien::where('tai_khoan_id', $user->tai_khoan_id)->first();

            if (!$nhanVien) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy hồ sơ nhân viên liên kết với tài khoản này.',
                ], 403);
            }

            $weekMonday = $request->input('weekMonday');
            if (empty($weekMonday)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chưa chọn tuần làm việc.',
                ], 422);
            }

            $weekInfo = $this->resolveWeekAndDeadline($weekMonday);

            // Kiểm tra hạn chót
            if ($weekInfo['isPastDeadline'] || $weekInfo['isLocked']) {
                return response()->json([
                    'status'  => false,
                    'code'    => 'DEADLINE_EXCEEDED',
                    'message' => "Đã hết hạn đăng ký cho tuần này (Hạn chót: {$weekInfo['deadlineFormatted']}).",
                ], 400);
            }

            $rawSlots = $request->input('slots', []);
            if (!is_array($rawSlots)) {
                $rawSlots = [];
            }

            DB::beginTransaction();
            try {
                // Tạo hoặc cập nhật tuần đăng ký
                $tuan = TuanDangKy::updateOrCreate(
                    [
                        'nhan_vien_id' => $nhanVien->nhan_vien_id,
                        'tu_ngay'      => $weekInfo['weekMonday'],
                    ],
                    [
                        'den_ngay'      => $weekInfo['weekSunday'],
                        'trang_thai'    => 'SUBMITTED',
                        'thoi_gian_gui' => now(),
                    ]
                );

                // Đồng bộ sang bảng tuan_dang_kies nếu có
                if (Schema::hasTable('tuan_dang_kies')) {
                    DB::table('tuan_dang_kies')->updateOrInsert(
                        [
                            'nhan_vien_id' => $nhanVien->nhan_vien_id,
                            'tu_ngay'      => $weekInfo['weekMonday'],
                        ],
                        [
                            'tuan_dang_ky_id' => $tuan->tuan_dang_ky_id,
                            'den_ngay'        => $weekInfo['weekSunday'],
                            'trang_thai'      => 'SUBMITTED',
                            'thoi_gian_gui'   => now(),
                        ]
                    );
                }

                // Xóa các đăng ký chi tiết cũ của tuần này
                DangKyCaKhaDung::where('tuan_dang_ky_id', $tuan->tuan_dang_ky_id)->delete();

                // Lập bản đồ ca mẫu
                $shifts = CaMau::all();

                foreach ($rawSlots as $slotKey => $slotVal) {
                    if (!in_array($slotVal, ['available', 'busy'])) {
                        continue;
                    }

                    $parts = explode('_', (string)$slotKey, 2);
                    if (count($parts) !== 2) {
                        continue;
                    }

                    $dayIdx = (int)$parts[0];
                    $shiftIdent = trim($parts[1]);
                    $thuTrongTuan = $dayIdx + 1; // 1 = Thứ 2 .. 7 = Chủ nhật

                    $caMauId = null;
                    if (is_numeric($shiftIdent) && $shifts->contains('ca_mau_id', (int)$shiftIdent)) {
                        $caMauId = (int)$shiftIdent;
                    } else {
                        $normIdent = mb_strtolower($this->normalizeShiftName($shiftIdent));
                        foreach ($shifts as $s) {
                            if (mb_strtolower($this->normalizeShiftName($s->ten_ca)) === $normIdent ||
                                mb_strtolower(trim($s->ten_ca)) === mb_strtolower($shiftIdent)) {
                                $caMauId = $s->ca_mau_id;
                                break;
                            }
                        }
                    }

                    if (!$caMauId) {
                        continue;
                    }

                    $trangThai = ($slotVal === 'available') ? 'AVAILABLE' : 'UNAVAILABLE';

                    DangKyCaKhaDung::create([
                        'tuan_dang_ky_id' => $tuan->tuan_dang_ky_id,
                        'ca_mau_id'       => $caMauId,
                        'thu_trong_tuan'  => $thuTrongTuan,
                        'trang_thai'      => $trangThai,
                    ]);
                }

                DB::commit();

                $empCode = 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT);
                $savedRecord = [
                    'id'          => "avail_{$empCode}_{$weekMonday}",
                    'employeeId'  => $empCode,
                    'weekMonday'  => $weekMonday,
                    'slots'       => $rawSlots,
                    'status'      => 'submitted',
                    'submittedAt' => now()->format('c'),
                    'version'     => ($request->input('expectedVersion') ?? 1) + 1,
                ];

                return response()->json([
                    'ok'      => true,
                    'status'  => true,
                    'message' => 'Gửi đăng ký lịch rảnh/bận thành công! Dữ liệu đã được cập nhật cho Quản lý.',
                    'data'    => $savedRecord,
                    ...$savedRecord,
                ]);
            } catch (\Exception $ex) {
                DB::rollBack();
                throw $ex;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lưu đăng ký: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. [Manager] Lấy ma trận tổng hợp khả năng làm việc của tất cả nhân viên trong tuần
     * Endpoint: GET /api/availability/weekly
     */
    public function getWeeklyStaffAvailability(Request $request)
    {
        try {
            $weekInfo = $this->resolveWeekAndDeadline($request->query('weekMonday'));
            $weekMonday = $weekInfo['weekMonday'];

            // Lấy danh sách nhân viên STAFF đang làm việc (ACTIVE) qua quan hệ tai_khoan_id, loại bỏ MANAGER
            $activeEmployees = NhanVien::join('tai_khoans', 'nhan_viens.tai_khoan_id', '=', 'tai_khoans.tai_khoan_id')
                ->where('tai_khoans.vai_tro', 'STAFF')
                ->whereIn('nhan_viens.trang_thai', ['ACTIVE', 'active', 'Đang làm việc'])
                ->select('nhan_viens.*')
                ->with('roleTag')
                ->orderBy('nhan_viens.nhan_vien_id', 'asc')
                ->get();

            // Lấy danh sách tuần đăng ký trong tuần này
            $tuanList = TuanDangKy::with('dangKyCaKhaDungs')
                ->where('tu_ngay', $weekMonday)
                ->get()
                ->keyBy('nhan_vien_id');

            $activeShifts = CaMau::all();
            $shiftNameMap = [];
            foreach ($activeShifts as $s) {
                $shiftNameMap[$s->ca_mau_id] = $this->normalizeShiftName($s->ten_ca);
            }

            $totalAvailable = 0;
            $totalBusy = 0;
            $registeredCount = 0;

            $employeeRows = [];

            foreach ($activeEmployees as $emp) {
                $empCode = 'NV' . str_pad($emp->nhan_vien_id, 3, '0', STR_PAD_LEFT);
                $roleName = $emp->roleTag->ten_role ?? 'Nhân viên';

                $tuan = $tuanList->get($emp->nhan_vien_id);
                $isRegistered = $tuan && strtoupper($tuan->trang_thai) === 'SUBMITTED';

                $empSlots = [];
                if ($isRegistered) {
                    $registeredCount++;
                    foreach ($tuan->dangKyCaKhaDungs as $rec) {
                        $dayIdx = (int)$rec->thu_trong_tuan - 1;
                        $shiftName = $shiftNameMap[$rec->ca_mau_id] ?? ('Ca ' . $rec->ca_mau_id);
                        $isAvail = in_array(strtoupper($rec->trang_thai), ['AVAILABLE', 'RANH']);

                        $empSlots["{$dayIdx}_{$shiftName}"] = $isAvail ? 'available' : 'busy';
                        if ($isAvail) {
                            $totalAvailable++;
                        } else {
                            $totalBusy++;
                        }
                    }
                }

                $employeeRows[] = [
                    'id'           => $empCode,
                    'nhan_vien_id' => $emp->nhan_vien_id,
                    'name'         => $emp->ho_ten,
                    'role'         => $roleName,
                    'roleTag'      => $roleName,
                    'roles'        => [$roleName],
                    'vai_tro'      => 'STAFF',
                    'vaiTro'       => 'STAFF',
                    'contract'     => $emp->loai_hop_dong ?? 'full_time',
                    'status'       => $emp->trang_thai,
                    'registered'   => $isRegistered,
                    'submittedAt'  => $tuan?->thoi_gian_gui ? (new \DateTime($tuan->thoi_gian_gui))->format('c') : null,
                    'slots'        => $empSlots,
                ];
            }

            $unregisteredCount = max(0, count($activeEmployees) - $registeredCount);

            $summary = [
                'registeredCount'   => $registeredCount,
                'unregisteredCount' => $unregisteredCount,
                'totalEmployees'    => count($activeEmployees),
                'totalAvailable'    => $totalAvailable,
                'totalBusy'         => $totalBusy,
            ];

            $responsePayload = [
                'weekMonday' => $weekMonday,
                'weekLabel'  => $weekInfo['weekLabel'],
                'weekRange'  => $weekInfo['weekRange'],
                'shifts'     => $weekInfo['shifts'],
                'days'       => $weekInfo['days'],
                'employees'  => $employeeRows,
                'summary'    => $summary,
            ];

            return response()->json([
                'status' => true,
                'data'   => $responsePayload,
                ...$responsePayload,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tổng hợp khả năng làm việc: ' . $e->getMessage(),
            ], 500);
        }
    }
}