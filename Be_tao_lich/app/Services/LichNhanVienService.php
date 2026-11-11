<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\LichLamViec;
use App\Models\PhanCongCa;
use App\Models\CaMau;
use App\Models\YeuCauNghi;
use App\Models\NhanVien;
use App\Models\TaiKhoan;

class LichNhanVienService
{
    /**
     * Danh sách trạng thái được tính là lịch đã ban hành chính thức
     */
    private const PUBLISHED_STATUSES = ['CONFIRMED', 'PUBLISHED', 'APPROVED', 'DA_XAC_NHAN'];

    /**
     * Lấy lịch làm việc cá nhân của nhân viên theo tuần
     *
     * @param TaiKhoan $taiKhoan Tài khoản của người dùng đăng nhập
     * @param string|null $weekMonday Ngày Thứ Hai đầu tuần (YYYY-MM-DD)
     * @return array [bool $success, int $statusCode, array $data, string|null $error]
     */
    public function getLichCuaToi(TaiKhoan $taiKhoan, ?string $weekMonday = null): array
    {
        // 1. Tự động xác định hồ sơ nhân viên từ tài khoản đang đăng nhập
        $nhanVien = $taiKhoan->nhanVien;
        if (!$nhanVien) {
            $nhanVien = NhanVien::where('tai_khoan_id', $taiKhoan->tai_khoan_id)->first();
        }

        if (!$nhanVien) {
            return [
                'success'    => false,
                'statusCode' => 404,
                'error'      => 'Tài khoản hiện tại chưa được liên kết với hồ sơ nhân viên nào.',
            ];
        }

        $nhanVienId = $nhanVien->nhan_vien_id;
        $empCode = 'NV' . str_pad($nhanVienId, 3, '0', STR_PAD_LEFT);
        $empName = $nhanVien->ho_ten;
        $roleName = $nhanVien->roleTag?->ten_role ?? 'Nhân viên';

        // 2. Chuẩn hóa ngày Thứ Hai đầu tuần và Chủ Nhật cuối tuần
        if (!$weekMonday || !strtotime($weekMonday)) {
            $weekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        $mon = Carbon::parse($weekMonday)->startOfWeek(Carbon::MONDAY);
        $sun = $mon->copy()->addDays(6);
        $monDate = $mon->format('Y-m-d');
        $sunDate = $sun->format('Y-m-d');

        // 3. Khởi tạo danh sách 7 ngày trong tuần cho frontend
        $days = $this->buildWeekDays($mon);

        // 4. Lấy cấu hình các ca mẫu đang hoạt động
        $caMaus = CaMau::where('trang_thai', 'ACTIVE')->get()->keyBy('ca_mau_id');
        $shiftsConfig = $this->buildShiftsConfig($caMaus);

        // 5. Kiểm tra lịch làm việc của tuần
        $lich = LichLamViec::where(function ($q) use ($monDate, $sunDate) {
                $q->whereBetween('tu_ngay', [$monDate, $sunDate])
                  ->orWhereBetween('den_ngay', [$monDate, $sunDate])
                  ->orWhere(function ($sub) use ($monDate, $sunDate) {
                      $sub->where('tu_ngay', '<=', $monDate)
                          ->where('den_ngay', '>=', $sunDate);
                  });
            })
            ->orderBy('phien_ban', 'desc')
            ->first();

        $isPublished = $lich && in_array(strtoupper($lich->trang_thai), self::PUBLISHED_STATUSES);

        // 6. Lấy danh sách ngày nghỉ phép đã duyệt của nhân viên trong tuần này
        $approvedLeaves = $this->getApprovedLeaves($nhanVienId, $monDate, $sunDate);

        // TRƯỜNG HỢP 1: Lịch chưa được ban hành (hoặc chưa có lịch)
        if (!$isPublished) {
            return [
                'success'    => true,
                'statusCode' => 200,
                'data'       => [
                    'status'            => true,
                    'published'         => false,
                    'trang_thai'        => $lich ? $lich->trang_thai : 'CHUA_CO_LICH',
                    'message'           => 'Lịch tuần này chưa được công bố. Vui lòng quay lại sau khi Quản lý ban hành chính thức.',
                    'weekStart'         => $monDate,
                    'weekEnd'           => $sunDate,
                    'weekLabel'         => 'Tuần ' . $mon->format('d/m') . ' – ' . $sun->format('d/m/Y'),
                    'employee'          => [
                        'id'            => $empCode,
                        'nhan_vien_id'  => $nhanVienId,
                        'name'          => $empName,
                        'role'          => $roleName,
                    ],
                    'days'              => $days,
                    'shiftsConfig'      => $shiftsConfig,
                    'myShifts'          => [],
                    'shifts'            => [],
                    'approvedLeaves'    => $approvedLeaves,
                    'calendarEvents'    => [],
                    'stats'             => [
                        'totalHours'    => 0,
                        'contractHours' => (float)($nhanVien->max_hours_week ?? 40),
                        'shiftCount'    => 0,
                        'published'     => false,
                    ],
                ],
            ];
        }

        // TRƯỜNG HỢP 2 & 3: Lịch đã ban hành chính thức
        // Tối ưu truy vấn: CHỈ truy vấn các ca phân công của đúng nhân viên này, eager loading caMau để tránh N+1
        $phanCongs = PhanCongCa::with(['caMau'])
            ->where('lich_id', $lich->lich_id)
            ->where('nhan_vien_id', $nhanVienId)
            ->whereBetween('ngay_lam', [$monDate, $sunDate])
            ->orderBy('ngay_lam', 'asc')
            ->get();

        $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
        $dayKeys = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

        $myShifts = [];
        $totalHours = 0;

        foreach ($phanCongs as $pc) {
            $cm = $pc->caMau ?? ($caMaus[$pc->ca_mau_id] ?? null);
            $sMin = $cm ? (int)substr($cm->gio_bat_dau, 0, 2) * 60 + (int)substr($cm->gio_bat_dau, 3, 2) : 360;
            $eMin = $cm ? (int)substr($cm->gio_ket_thuc, 0, 2) * 60 + (int)substr($cm->gio_ket_thuc, 3, 2) : 720;
            if ($eMin <= $sMin) $eMin += 1440;
            $durH = round(($eMin - $sMin) / 60.0, 1);

            $cCode = 'morning';
            $sH = $cm ? (int)substr($cm->gio_bat_dau, 0, 2) : 6;
            if ($cm && (stripos($cm->ten_ca, 'chiều') !== false || ($sH >= 12 && $sH < 17))) {
                $cCode = 'afternoon';
            } elseif ($cm && (stripos($cm->ten_ca, 'tối') !== false || $sH >= 17)) {
                $cCode = 'evening';
            }

            $ngayLam = Carbon::parse($pc->ngay_lam);
            $dayDiff = $mon->diffInDays($ngayLam);
            $dayId = $dayKeys[$dayDiff] ?? 'T2';

            $startTime = $cm ? substr($cm->gio_bat_dau, 0, 5) : '06:00';
            $endTime = $cm ? substr($cm->gio_ket_thuc, 0, 5) : '12:00';

            $shiftItem = [
                'id'               => 'shift_' . $pc->phan_cong_ca_id,
                'phan_cong_ca_id'  => $pc->phan_cong_ca_id,
                'date'             => $pc->ngay_lam,
                'dateIso'          => $pc->ngay_lam,
                'dayId'            => $dayId,
                'dayName'          => $dayNames[$dayDiff] ?? 'Thứ 2',
                'shiftId'          => $cCode,
                'code'             => $cCode,
                'ca_mau_id'        => $pc->ca_mau_id,
                'shiftName'        => $cm ? $cm->ten_ca : ('Ca ' . $cCode),
                'startTime'        => $startTime,
                'endTime'          => $endTime,
                'hours'            => $durH,
                'role'             => $roleName,
                'roleTag'          => $roleName,
                'employeeId'       => $empCode,
                'nhan_vien_id'     => $nhanVienId,
                'employeeName'     => $empName,
                'isMyShift'        => true,
                'is_official'      => true,
                'status'           => 'scheduled',
            ];

            $myShifts[] = $shiftItem;
            $totalHours += $durH;
        }

        // 7. Xác định ca làm việc tiếp theo (Next Shift)
        $nextShift = $this->getCaTiepTheo($myShifts);

        return [
            'success'    => true,
            'statusCode' => 200,
            'data'       => [
                'status'            => true,
                'published'         => true,
                'trang_thai'        => $lich->trang_thai,
                'message'           => count($myShifts) > 0 ? 'Tải lịch cá nhân thành công.' : 'Bạn không có ca làm việc trong tuần này.',
                'weekStart'         => $monDate,
                'weekEnd'           => $sunDate,
                'weekLabel'         => 'Tuần ' . $mon->format('d/m') . ' – ' . $sun->format('d/m/Y'),
                'employee'          => [
                    'id'            => $empCode,
                    'nhan_vien_id'  => $nhanVienId,
                    'name'          => $empName,
                    'role'          => $roleName,
                ],
                'days'              => $days,
                'shiftsConfig'      => $shiftsConfig,
                'myShifts'          => $myShifts,
                'shifts'            => $myShifts,
                'approvedLeaves'    => $approvedLeaves,
                'nextShift'         => $nextShift,
                'stats'             => [
                    'totalHours'    => $totalHours,
                    'contractHours' => (float)($nhanVien->max_hours_week ?? 40),
                    'shiftCount'    => count($myShifts),
                    'published'     => true,
                ],
            ],
        ];
    }

    /**
     * Xác định ca làm việc tiếp theo (hoặc ca đang diễn ra)
     */
    public function getCaTiepTheo(array $myShifts): ?array
    {
        $now = Carbon::now();
        $nextShift = null;

        $sortedShifts = collect($myShifts)->sortBy(function ($s) {
            return $s['date'] . ' ' . $s['startTime'];
        });

        foreach ($sortedShifts as $s) {
            $startDt = Carbon::parse($s['date'] . ' ' . $s['startTime']);
            $endDt = Carbon::parse($s['date'] . ' ' . $s['endTime']);

            if ($now->between($startDt, $endDt)) {
                $nextShift = $s;
                $nextShift['isOngoing'] = true;
                break;
            }

            if ($startDt->gt($now)) {
                $nextShift = $s;
                $nextShift['isOngoing'] = false;
                break;
            }
        }

        return $nextShift;
    }

    /**
     * Lấy danh sách ngày nghỉ phép đã được phê duyệt trong tuần
     */
    private function getApprovedLeaves(int $nhanVienId, string $monDate, string $sunDate): array
    {
        return YeuCauNghi::where('nhan_vien_id', $nhanVienId)
            ->whereIn('trang_thai', ['APPROVED', 'DA_DUYET'])
            ->where('tu_ngay', '<=', $sunDate)
            ->where('den_ngay', '>=', $monDate)
            ->get()
            ->flatMap(function ($l) use ($monDate, $sunDate) {
                $res = [];
                $cur = Carbon::parse(max($l->tu_ngay, $monDate));
                $end = Carbon::parse(min($l->den_ngay, $sunDate));
                while ($cur->lte($end)) {
                    $iso = $cur->format('Y-m-d');
                    $res[] = [
                        'id'         => $l->yeu_cau_id ?? $l->id,
                        'isoDate'    => $iso,
                        'date'       => $iso,
                        'dateIso'    => $iso,
                        'reason'     => $l->ly_do,
                        'status'     => 'approved',
                    ];
                    $cur->addDay();
                }
                return $res;
            })
            ->values()
            ->toArray();
    }

    /**
     * Dựng danh sách 7 ngày trong tuần
     */
    private function buildWeekDays(Carbon $mon): array
    {
        $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
        $dayKeys = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $todayIso = Carbon::now()->format('Y-m-d');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $cur = $mon->copy()->addDays($i);
            $curIso = $cur->format('Y-m-d');
            $days[] = [
                'id'        => $dayKeys[$i],
                'dayIndex'  => $i,
                'dayName'   => $dayNames[$i],
                'iso'       => $curIso,
                'dateIso'   => $curIso,
                'dateVn'    => $cur->format('d/m'),
                'label'     => $dayKeys[$i],
                'isToday'   => $curIso === $todayIso,
            ];
        }

        return $days;
    }

    /**
     * Dựng cấu hình ca làm việc
     */
    private function buildShiftsConfig($caMaus): array
    {
        $shiftsConfig = [];
        foreach ($caMaus as $cm) {
            $sMin = (int)substr($cm->gio_bat_dau, 0, 2) * 60 + (int)substr($cm->gio_bat_dau, 3, 2);
            $eMin = (int)substr($cm->gio_ket_thuc, 0, 2) * 60 + (int)substr($cm->gio_ket_thuc, 3, 2);
            if ($eMin <= $sMin) $eMin += 1440;
            $durH = round(($eMin - $sMin) / 60.0, 1);

            $cCode = 'morning';
            $sH = (int)substr($cm->gio_bat_dau, 0, 2);
            if (stripos($cm->ten_ca, 'chiều') !== false || ($sH >= 12 && $sH < 17)) {
                $cCode = 'afternoon';
            } elseif (stripos($cm->ten_ca, 'tối') !== false || $sH >= 17) {
                $cCode = 'evening';
            }

            $shiftsConfig[] = [
                'id'        => $cCode,
                'code'      => $cCode,
                'ca_mau_id' => $cm->ca_mau_id,
                'name'      => $cm->ten_ca,
                'shiftName' => $cm->ten_ca,
                'startTime' => substr($cm->gio_bat_dau, 0, 5),
                'endTime'   => substr($cm->gio_ket_thuc, 0, 5),
                'hours'     => $durH,
            ];
        }

        return $shiftsConfig;
    }
}

