<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CaTrong;
use App\Models\DangKyCaTrong;
use App\Models\CaMau;
use App\Models\RoleTag;
use App\Models\NhuCauCa;
use App\Models\PhanCongCa;
use App\Models\LichLamViec;
use App\Models\YeuCauNghi;
use App\Models\NhanVien;

class CaTrongController extends Controller
{
    /**
     * Helper to resolve current authenticated employee
     */
    private function resolveEmployee(Request $request)
    {
        $user = $request->user();
        if (!$user) return null;
        return $user->nhanVien ?? NhanVien::where('tai_khoan_id', $user->tai_khoan_id)->first();
    }

    /**
     * Map ca_mau_id or code
     */
    private function getShiftCodeMap()
    {
        $caMaus = CaMau::where('trang_thai', 'ACTIVE')->get();
        $map = [];
        foreach ($caMaus as $cm) {
            $code = 'morning';
            if (stripos($cm->ten_ca, 'chiều') !== false) $code = 'afternoon';
            elseif (stripos($cm->ten_ca, 'tối') !== false) $code = 'evening';
            $map[$cm->ca_mau_id] = $code;
        }
        return [$caMaus->keyBy('ca_mau_id'), $map];
    }

    /**
     * Format a CaTrong record to match frontend expectations
     */
    private function formatCaTrong($ct, $caMaus, $codeMap, $currentUserEmployeeId = null)
    {
        $ngayLam = $ct->ngay_lam ? (is_string($ct->ngay_lam) ? substr($ct->ngay_lam, 0, 10) : $ct->ngay_lam->format('Y-m-d')) : ($ct->phanCongCa?->ngay_lam ?? '');
        $cmId = $ct->ca_mau_id ?? $ct->phanCongCa?->ca_mau_id;
        $cm = $caMaus[$cmId] ?? $ct->caMau ?? $ct->phanCongCa?->caMau;
        $sCode = $cmId ? ($codeMap[$cmId] ?? ('shift_' . $cmId)) : 'morning';

        $roleName = $ct->roleTag?->ten_role ?? 'Phục vụ';

        $gioBatDau = $cm ? substr($cm->gio_bat_dau, 0, 5) : '06:00';
        $gioKetThuc = $cm ? substr($cm->gio_ket_thuc, 0, 5) : '12:00';

        // Claims list
        $claims = [];
        $hasClaimedByCurrent = false;
        foreach ($ct->dangKyCaTrongs as $dk) {
            $nv = $dk->nhanVien;
            $empCode = $nv ? ('NV' . str_pad($nv->nhan_vien_id, 3, '0', STR_PAD_LEFT)) : ('NV' . str_pad($dk->nhan_vien_id, 3, '0', STR_PAD_LEFT));
            if ($currentUserEmployeeId && ($empCode === $currentUserEmployeeId || $dk->nhan_vien_id == $currentUserEmployeeId)) {
                $hasClaimedByCurrent = true;
            }
            $claims[] = [
                'id'            => $dk->dang_ky_ca_trong_id,
                'registrationId'=> $dk->dang_ky_ca_trong_id,
                'employeeId'    => $empCode,
                'nhan_vien_id'  => $dk->nhan_vien_id,
                'name'          => $nv ? $nv->ho_ten : ('Nhân viên #' . $dk->nhan_vien_id),
                'phone'         => $nv ? ($nv->so_dien_thoai ?? '') : '',
                'role'          => $nv?->roleTag?->ten_role ?? 'Nhân viên',
                'claimedAt'     => $dk->thoi_gian_dang_ky ? $dk->thoi_gian_dang_ky->format('Y-m-d H:i:s') : null,
                'status'        => strtolower($dk->trang_thai), // 'pending', 'approved', 'rejected'
            ];
        }

        $originText = match ($ct->nguon_goc) {
            'DEFICIENT_SCHEDULE' => 'Thiếu nhân sự từ lịch',
            'LEAVE_REPLACEMENT'  => 'Thay thế nhân viên nghỉ',
            'MANUAL_REPLACEMENT' => 'Tìm người thay thế ca',
            default              => 'Chủ động mở ca',
        };

        return [
            'id'             => 'OS' . str_pad($ct->ca_trong_id, 3, '0', STR_PAD_LEFT),
            'raw_id'         => $ct->ca_trong_id,
            'ca_trong_id'    => $ct->ca_trong_id,
            'date'           => $ngayLam,
            'shiftCode'      => $sCode,
            'shiftName'      => $cm ? $cm->ten_ca : ('Ca #' . $cmId),
            'startTime'      => $gioBatDau,
            'endTime'        => $gioKetThuc,
            'role'           => $roleName,
            'roleTag'        => $roleName,
            'roles'          => [$roleName],
            'role_tag_id'    => $ct->role_tag_id,
            'needed'         => (int)$ct->so_luong_can,
            'claimed'        => (int)$ct->so_luong_da_nhan,
            'bonus'          => (float)$ct->tien_thuong,
            'note'           => $ct->mo_ta ?? '',
            'status'         => strtolower($ct->trang_thai), // 'open', 'filled', 'closed', 'cancelled'
            'cancelled'      => $ct->trang_thai === 'CANCELLED',
            'origin'         => $ct->nguon_goc,
            'origin_text'    => $originText,
            'phan_cong_ca_id'=> $ct->phan_cong_ca_id,
            'yeu_cau_nghi_id'=> $ct->yeu_cau_nghi_id,
            'claims'         => $claims,
            'has_claimed'    => $hasClaimedByCurrent,
            'version'        => $ct->updated_at ? $ct->updated_at->timestamp : 1,
        ];
    }

    /**
     * 1. GET /api/open-shifts (Cho Manager & Staff)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $isManager = $user && ($user->vai_tro === 'MANAGER' || $user->role === 'manager' || $user->role_tag_id === 1);
            $nhanVien = $this->resolveEmployee($request);
            $currentEmpCode = $nhanVien ? ('NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT)) : null;

            [$caMaus, $codeMap] = $this->getShiftCodeMap();

            $query = CaTrong::with(['caMau', 'roleTag', 'phanCongCa.caMau', 'phanCongCa.nhanVien', 'dangKyCaTrongs.nhanVien.roleTag']);

            // Nếu là Staff: chỉ lấy các ca có role_tag khớp với role_tag của Staff (Quy tắc Role_Tag bắt buộc)
            if (!$isManager && $nhanVien) {
                $query->where('role_tag_id', $nhanVien->role_tag_id)
                      ->where('trang_thai', 'OPEN');
            }

            // Lọc theo status nếu có param
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('trang_thai', strtoupper($request->status));
            }

            // Lọc theo role_tag_id nếu có
            if ($request->has('role_tag_id') && $request->role_tag_id !== 'all') {
                $query->where('role_tag_id', $request->role_tag_id);
            }

            // Lọc theo tuần nếu có param
            if ($request->has('week') && !empty($request->week) && $request->week !== 'all') {
                $weekMon = Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $weekSun = Carbon::parse($request->week)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $query->where(function ($q) use ($weekMon, $weekSun) {
                    $q->whereBetween('ngay_lam', [$weekMon, $weekSun])
                      ->orWhereHas('phanCongCa', function ($pq) use ($weekMon, $weekSun) {
                          $pq->whereBetween('ngay_lam', [$weekMon, $weekSun]);
                      });
                });
            }

            // Sắp xếp
            $query->orderBy('ngay_lam', 'desc')->orderBy('ca_trong_id', 'desc');

            $shifts = $query->get();

            $formatted = $shifts->map(function ($ct) use ($caMaus, $codeMap, $currentEmpCode) {
                return $this->formatCaTrong($ct, $caMaus, $codeMap, $currentEmpCode);
            });

            return response()->json([
                'status' => true,
                'data'   => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải danh sách ca trực mở: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. GET /api/employees/open-shifts (Dành riêng cho Staff)
     * Trả về danh sách ca phù hợp với role_tag của Staff và kiểm tra quy tắc trống lịch cả ngày
     */
    public function getForEmployee(Request $request)
    {
        try {
            $user = $request->user();
            $nhanVien = $this->resolveEmployee($request);

            if (!$nhanVien) {
                return response()->json([]);
            }

            $staffRoleId = $nhanVien->role_tag_id;
            $staffId = $nhanVien->nhan_vien_id;
            $empCode = 'NV' . str_pad($staffId, 3, '0', STR_PAD_LEFT);

            [$caMaus, $codeMap] = $this->getShiftCodeMap();

            // 1. Chỉ lấy ca trực mở đúng role_tag của nhân viên và đang OPEN
            $shifts = CaTrong::with(['caMau', 'roleTag', 'phanCongCa.caMau', 'dangKyCaTrongs.nhanVien'])
                ->where('role_tag_id', $staffRoleId)
                ->where('trang_thai', 'OPEN')
                ->where(function ($q) {
                    $q->whereNull('han_dang_ky')->orWhere('han_dang_ky', '>=', now());
                })
                ->orderBy('ngay_lam', 'asc')
                ->get();

            // Lấy toàn bộ các ca làm việc đã được phân công của nhân viên này để kiểm tra quy tắc trống lịch cả ngày
            $assignedDates = PhanCongCa::where('nhan_vien_id', $staffId)
                ->pluck('ngay_lam')
                ->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
                ->toArray();

            $result = [];
            foreach ($shifts as $ct) {
                $formatted = $this->formatCaTrong($ct, $caMaus, $codeMap, $empCode);
                $shiftDate = $formatted['date'];

                // QUY TẮC BẮT BUỘC: Nhân viên phải hoàn toàn chưa có bất kỳ ca làm việc nào trong toàn bộ ngày đó
                $hasShiftInDay = in_array($shiftDate, $assignedDates);

                if ($hasShiftInDay) {
                    $formatted['eligible'] = false;
                    $formatted['reason'] = 'Bạn đã có lịch làm việc trong ngày này nên không thể đăng ký thêm ca trực mở.';
                } elseif ($ct->so_luong_da_nhan >= $ct->so_luong_can) {
                    $formatted['eligible'] = false;
                    $formatted['reason'] = 'Ca này đã đủ người nhận.';
                } elseif ($formatted['has_claimed']) {
                    $formatted['eligible'] = false;
                    $formatted['reason'] = 'Bạn đã đăng ký ca này rồi, đang chờ Quản lý duyệt.';
                } else {
                    $formatted['eligible'] = true;
                    $formatted['reason'] = null;
                }

                $result[] = $formatted;
            }

            return response()->json([
                'status' => true,
                'data'   => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải ca mở cho nhân viên: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. GET /api/open-shifts/sources (Manager lấy danh sách các ca có thể mở)
     * Gồm: 1) Ca thiếu từ lịch tự động; 2) Ca cần thay thế do nghỉ phép đã duyệt; 3) Ca đã phân công có thể thay thế
     */
    public function getSources(Request $request)
    {
        try {
            $weekMonday = $request->query('week') ?? $request->query('weekMonday') ?? '2026-11-02';
            $mon = Carbon::parse($weekMonday)->startOfWeek(Carbon::MONDAY);
            $sun = $mon->copy()->addDays(6);
            $monDate = $mon->format('Y-m-d');
            $sunDate = $sun->format('Y-m-d');

            [$caMaus, $codeMap] = $this->getShiftCodeMap();
            $roleTags = RoleTag::where('trang_thai', 'ACTIVE')->get()->keyBy('role_tag_id');

            // --- NGUỒN 1: Ca còn thiếu từ kết quả tạo lịch tự động ---
            $lich = LichLamViec::where('tu_ngay', '<=', $monDate)
                ->where('den_ngay', '>=', $sunDate)
                ->orderBy('phien_ban', 'desc')
                ->first();

            $deficientShifts = [];
            if ($lich) {
                $demands = NhuCauCa::whereBetween('ngay_lam', [$monDate, $sunDate])
                    ->select('ngay_lam', 'ca_mau_id', 'role_tag_id', DB::raw('SUM(so_luong_can) as total_required'))
                    ->groupBy('ngay_lam', 'ca_mau_id', 'role_tag_id')
                    ->get();

                $assignments = PhanCongCa::where('lich_id', $lich->lich_id)
                    ->whereBetween('ngay_lam', [$monDate, $sunDate])
                    ->select('ngay_lam', 'ca_mau_id', DB::raw('COUNT(*) as total_assigned'))
                    ->groupBy('ngay_lam', 'ca_mau_id')
                    ->get()
                    ->keyBy(fn($item) => $item->ngay_lam . '_' . $item->ca_mau_id);

                foreach ($demands as $d) {
                    $key = $d->ngay_lam . '_' . $d->ca_mau_id;
                    $assignedCount = isset($assignments[$key]) ? (int)$assignments[$key]->total_assigned : 0;
                    $missing = max(0, (int)$d->total_required - $assignedCount);
                    if ($missing > 0) {
                        $cm = $caMaus[$d->ca_mau_id] ?? null;
                        $rt = $roleTags[$d->role_tag_id] ?? null;
                        $deficientShifts[] = [
                            'type'         => 'DEFICIENT_SCHEDULE',
                            'type_label'   => 'Thiếu nhân sự từ lịch',
                            'date'         => $d->ngay_lam,
                            'ca_mau_id'    => $d->ca_mau_id,
                            'shift_name'   => $cm ? $cm->ten_ca : ('Ca #' . $d->ca_mau_id),
                            'role_tag_id'  => $d->role_tag_id,
                            'role_name'    => $rt ? $rt->ten_role : 'Phục vụ',
                            'required'     => (int)$d->total_required,
                            'assigned'     => $assignedCount,
                            'missing'      => $missing,
                            'description'  => "Lịch ngày {$d->ngay_lam} ca {$cm?->ten_ca} còn thiếu {$missing} {$rt?->ten_role}",
                        ];
                    }
                }
            }

            // --- NGUỒN 2: Ca bị ảnh hưởng do nhân viên xin nghỉ đã được duyệt (APPROVED) ---
            $approvedLeaves = YeuCauNghi::with(['nhanVien.roleTag'])
                ->where('trang_thai', 'APPROVED')
                ->where('tu_ngay', '<=', $sunDate)
                ->where('den_ngay', '>=', $monDate)
                ->get();

            $leaveReplacements = [];
            foreach ($approvedLeaves as $leave) {
                $start = max($monDate, $leave->tu_ngay);
                $end = min($sunDate, $leave->den_ngay);

                $shifts = PhanCongCa::with(['caMau'])
                    ->where('nhan_vien_id', $leave->nhan_vien_id)
                    ->whereBetween('ngay_lam', [$start, $end])
                    ->get();

                foreach ($shifts as $s) {
                    $alreadyOpened = CaTrong::where('phan_cong_ca_id', $s->phan_cong_ca_id)
                        ->whereIn('trang_thai', ['OPEN', 'FILLED'])
                        ->exists();

                    $cm = $s->caMau;
                    $nv = $leave->nhanVien;
                    $rt = $nv?->roleTag;

                    $leaveReplacements[] = [
                        'type'             => 'LEAVE_REPLACEMENT',
                        'type_label'       => 'Thay thế nhân viên nghỉ',
                        'phan_cong_ca_id'  => $s->phan_cong_ca_id,
                        'yeu_cau_nghi_id'  => $leave->yeu_cau_nghi_id,
                        'date'             => $s->ngay_lam,
                        'ca_mau_id'        => $s->ca_mau_id,
                        'shift_name'       => $cm ? $cm->ten_ca : ('Ca #' . $s->ca_mau_id),
                        'role_tag_id'      => $nv?->role_tag_id,
                        'role_name'        => $rt ? $rt->ten_role : 'Phục vụ',
                        'employee_name'    => $nv ? $nv->ho_ten : ('NV #' . $leave->nhan_vien_id),
                        'leave_reason'     => $leave->ly_do,
                        'already_opened'   => $alreadyOpened,
                        'description'      => "Thay thế {$nv?->ho_ten} nghỉ ca {$cm?->ten_ca} ngày {$s->ngay_lam}",
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'deficient_shifts'   => $deficientShifts,
                    'leave_replacements' => $leaveReplacements,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải danh mục nguồn mở ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. POST /api/open-shifts (Manager tạo ca trực mở)
     */
    public function store(Request $request)
    {
        $request->validate([
            'date'      => 'required|date_format:Y-m-d',
            'shiftCode' => 'nullable|string',
            'ca_mau_id' => 'nullable|integer',
            'role'      => 'nullable|string',
            'role_tag_id' => 'nullable|integer',
            'needed'    => 'required|integer|min:1',
            'bonus'     => 'nullable|numeric|min:0',
            'note'      => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // 1. Xác định ca_mau_id
            $caMauId = $request->ca_mau_id;
            if (!$caMauId && $request->shiftCode) {
                $code = strtolower($request->shiftCode);
                $cm = CaMau::where('trang_thai', 'ACTIVE')->get()->first(function ($c) use ($code) {
                    if (str_contains($code, 'morn') || str_contains($code, 'sáng')) return stripos($c->ten_ca, 'sáng') !== false;
                    if (str_contains($code, 'after') || str_contains($code, 'chiều')) return stripos($c->ten_ca, 'chiều') !== false;
                    if (str_contains($code, 'eve') || str_contains($code, 'tối')) return stripos($c->ten_ca, 'tối') !== false;
                    return (string)$c->ca_mau_id === (string)$code;
                });
                $caMauId = $cm ? $cm->ca_mau_id : 1;
            }
            if (!$caMauId) $caMauId = 1;

            // 2. Xác định role_tag_id
            $roleTagId = $request->role_tag_id;
            if (!$roleTagId && $request->role) {
                $rt = RoleTag::where('ten_role', 'like', '%' . trim($request->role) . '%')->first();
                $roleTagId = $rt ? $rt->role_tag_id : 4; // Mặc định Phục vụ = 4
            }
            if (!$roleTagId) $roleTagId = 4;

            $origin = $request->origin ?? $request->source_type ?? 'MANUAL';
            $ngayLam = substr($request->date, 0, 10);
            $needed = (int)$request->needed;
            $targetWeek = $request->week ?? $request->weekMonday;

            // KIỂM TRA BẮT BUỘC: Ngày trực phải thuộc phạm vi tuần đang thao tác (Thứ Hai đến Chủ Nhật)
            if ($targetWeek) {
                $weekMon = Carbon::parse($targetWeek)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $weekSun = Carbon::parse($targetWeek)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                if ($ngayLam < $weekMon || $ngayLam > $weekSun) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => "Ngày trực ({$ngayLam}) phải nằm trong phạm vi tuần đang thao tác ({$weekMon} đến {$weekSun}).",
                    ], 422);
                }
            }

            // 3. Ràng buộc theo từng nguồn phát sinh
            if ($origin === 'DEFICIENT_SCHEDULE') {
                // Kiểm tra số lượng còn thiếu thực tế
                $required = (int)NhuCauCa::where('ngay_lam', $ngayLam)->where('ca_mau_id', $caMauId)->sum('so_luong_can');
                $assigned = (int)PhanCongCa::where('ngay_lam', $ngayLam)->where('ca_mau_id', $caMauId)->count();
                $missing = max(0, $required - $assigned);

                $alreadyOpened = (int)CaTrong::where('ca_mau_id', $caMauId)
                    ->where('ngay_lam', $ngayLam)
                    ->where('nguon_goc', 'DEFICIENT_SCHEDULE')
                    ->whereIn('trang_thai', ['OPEN', 'FILLED'])
                    ->sum('so_luong_can');

                $available = max(0, $missing - $alreadyOpened);

                if ($needed > $available) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => "Số lượng ca mở ({$needed}) vượt quá số lượng nhân sự còn thiếu ({$available})!",
                    ], 422);
                }
            } elseif ($origin === 'LEAVE_REPLACEMENT') {
                if ($request->yeu_cau_nghi_id) {
                    $leave = YeuCauNghi::find($request->yeu_cau_nghi_id);
                    if (!$leave || $leave->trang_thai !== 'APPROVED') {
                        DB::rollBack();
                        return response()->json([
                            'status'  => false,
                            'message' => 'Chỉ được mở ca thay thế cho đơn xin nghỉ đã được duyệt (APPROVED)!',
                        ], 422);
                    }
                }
                if ($request->phan_cong_ca_id) {
                    $existing = CaTrong::where('phan_cong_ca_id', $request->phan_cong_ca_id)
                        ->whereIn('trang_thai', ['OPEN', 'FILLED'])
                        ->first();
                    if ($existing) {
                        DB::rollBack();
                        return response()->json([
                            'status'  => false,
                            'message' => 'Vị trí phân công này đã được mở ca thay thế trước đó!',
                        ], 422);
                    }
                }
            }

            // Tìm lịch tương ứng
            $lich = LichLamViec::where('tu_ngay', '<=', $ngayLam)
                ->where('den_ngay', '>=', $ngayLam)
                ->orderBy('phien_ban', 'desc')
                ->first();

            $user = $request->user();
            $managerId = $user ? $user->tai_khoan_id : 1;

            $caTrong = CaTrong::create([
                'lich_id'          => $lich?->lich_id,
                'ca_mau_id'        => $caMauId,
                'ngay_lam'         => $ngayLam,
                'role_tag_id'      => $roleTagId,
                'so_luong_can'     => $needed,
                'so_luong_da_nhan' => 0,
                'tien_thuong'      => $request->bonus ?? 0,
                'nguon_goc'        => $origin,
                'phan_cong_ca_id'  => $request->phan_cong_ca_id,
                'yeu_cau_nghi_id'  => $request->yeu_cau_nghi_id,
                'han_dang_ky'      => $request->deadline ?? $request->han_dang_ky ?? null,
                'mo_ta'            => $request->note ?? $request->mo_ta ?? null,
                'trang_thai'       => 'OPEN',
                'nguoi_mo_id'      => $managerId,
            ]);

            DB::commit();

            [$caMaus, $codeMap] = $this->getShiftCodeMap();
            $formatted = $this->formatCaTrong($caTrong, $caMaus, $codeMap);

            return response()->json([
                'status'  => true,
                'message' => 'Tạo ca trực mở thành công.',
                'data'    => $formatted,
                'shift'   => $formatted,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tạo ca trực mở: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. PUT /api/open-shifts/{id} (Manager chỉnh sửa ca trực mở)
     */
    public function update(Request $request, $id = null)
    {
        try {
            $targetId = $id ?? $request->id ?? $request->shift_id ?? $request->ca_trong_id;
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);
            $caTrong = CaTrong::find($rawId);
            if (!$caTrong) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy ca trực mở.'], 404);
            }

            if ($caTrong->trang_thai !== 'OPEN') {
                return response()->json(['status' => false, 'message' => 'Chỉ có thể chỉnh sửa ca trực đang mở.'], 422);
            }

            if ($request->has('needed')) {
                $newNeeded = (int)$request->needed;
                if ($newNeeded < $caTrong->so_luong_da_nhan) {
                    return response()->json([
                        'status'  => false,
                        'message' => "Số lượng cần tối thiểu là {$caTrong->so_luong_da_nhan} do đã có người được xác nhận.",
                    ], 422);
                }
                $caTrong->so_luong_can = $newNeeded;
            }

            if ($request->has('date')) {
                $newDate = substr($request->date, 0, 10);
                $targetWeek = $request->week ?? $request->weekMonday;
                if ($targetWeek) {
                    $weekMon = Carbon::parse($targetWeek)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                    $weekSun = Carbon::parse($targetWeek)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                    if ($newDate < $weekMon || $newDate > $weekSun) {
                        return response()->json([
                            'status'  => false,
                            'message' => "Ngày trực ({$newDate}) phải nằm trong phạm vi tuần đang thao tác ({$weekMon} đến {$weekSun}).",
                        ], 422);
                    }
                }
                $caTrong->ngay_lam = $newDate;
            }

            if ($request->has('bonus')) $caTrong->tien_thuong = (float)$request->bonus;
            if ($request->has('note')) $caTrong->mo_ta = $request->note;
            $caTrong->save();

            [$caMaus, $codeMap] = $this->getShiftCodeMap();
            $formatted = $this->formatCaTrong($caTrong, $caMaus, $codeMap);

            return response()->json([
                'status'  => true,
                'message' => 'Cập nhật ca trực mở thành công.',
                'data'    => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi cập nhật ca trực mở: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 6. POST /api/open-shifts/{id}/cancel (Manager hủy ca trực mở)
     */
    public function cancel(Request $request, $id = null)
    {
        try {
            $targetId = $id ?? $request->id ?? $request->shift_id ?? $request->ca_trong_id;
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);
            $caTrong = CaTrong::find($rawId);
            if (!$caTrong) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy ca trực mở.'], 404);
            }

            $caTrong->trang_thai = 'CANCELLED';
            $caTrong->save();

            // Từ chối các đăng ký đang pending
            DangKyCaTrong::where('ca_trong_id', $rawId)
                ->where('trang_thai', 'PENDING')
                ->update(['trang_thai' => 'REJECTED', 'ly_do_tu_choi' => 'Ca trực mở đã bị Quản lý hủy.']);

            return response()->json([
                'status'  => true,
                'action'  => 'cancelled',
                'message' => 'Đã hủy ca trực mở thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi khi hủy ca trực mở: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 7. POST /api/employees/open-shifts/{id}/claim (Staff đăng ký nhận ca)
     * TUYỆT ĐỐI TUÂN THỦ:
     * - Role_Tag phải trùng khớp.
     * - Nhân viên phải TRỐNG LỊCH TOÀN BỘ NGÀY ĐÓ (Không có bất kỳ ca sáng, chiều, tối nào).
     */
    public function claim(Request $request, $id = null)
    {
        try {
            DB::beginTransaction();

            $targetId = $id ?? $request->id ?? $request->shift_id ?? $request->ca_trong_id;
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);
            $caTrong = CaTrong::where('ca_trong_id', $rawId)->lockForUpdate()->first();

            if (!$caTrong) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Không tìm thấy ca trực mở.'], 404);
            }

            if ($caTrong->trang_thai !== 'OPEN') {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Ca này đã đóng hoặc đã bị hủy.'], 422);
            }

            if ($caTrong->so_luong_da_nhan >= $caTrong->so_luong_can) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Ca này đã đủ người nhận.'], 422);
            }

            // Hạn đăng ký
            if ($caTrong->han_dang_ky && Carbon::now()->greaterThan($caTrong->han_dang_ky)) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Ca trực mở này đã hết hạn đăng ký.'], 422);
            }

            // Xác định nhân viên
            $nhanVien = $this->resolveEmployee($request);
            if (!$nhanVien) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Không tìm thấy thông tin nhân viên.'], 403);
            }

            $nhanVienId = $nhanVien->nhan_vien_id;

            // ĐIỀU KIỆN 1: Vai trò (role_tag) phải trùng khớp
            if ($caTrong->role_tag_id && (int)$nhanVien->role_tag_id !== (int)$caTrong->role_tag_id) {
                DB::rollBack();
                $reqRole = $caTrong->roleTag?->ten_role ?? 'vị trí chỉ định';
                $curRole = $nhanVien->roleTag?->ten_role ?? 'vai trò hiện tại';
                return response()->json([
                    'status'  => false,
                    'message' => "Bạn không thể nhận ca này: Ca này yêu cầu vị trí \"{$reqRole}\". Bạn đang đảm nhận: {$curRole}.",
                ], 422);
            }

            // ĐIỀU KIỆN 2: Chưa đăng ký ca này trước đó
            $alreadyClaimed = DangKyCaTrong::where('ca_trong_id', $rawId)
                ->where('nhan_vien_id', $nhanVienId)
                ->where('trang_thai', '!=', 'REJECTED')
                ->exists();
            if ($alreadyClaimed) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Bạn đã đăng ký nhận ca này rồi, vui lòng chờ Quản lý xét duyệt.',
                ], 422);
            }

            // ĐIỀU KIỆN 3 (BẮT BUỘC): Nhân viên hoàn toàn chưa có bất kỳ ca làm việc nào trong toàn bộ ngày đó
            $shiftDate = $caTrong->ngay_lam ? (is_string($caTrong->ngay_lam) ? substr($caTrong->ngay_lam, 0, 10) : $caTrong->ngay_lam->format('Y-m-d')) : ($caTrong->phanCongCa?->ngay_lam ?? null);

            if ($shiftDate) {
                $hasShiftInDay = PhanCongCa::where('nhan_vien_id', $nhanVienId)
                    ->where('ngay_lam', $shiftDate)
                    ->exists();

                if ($hasShiftInDay) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => 'Bạn đã có lịch làm việc trong ngày này nên không thể đăng ký thêm ca trực mở.',
                    ], 422);
                }
            }

            // Ghi nhận đăng ký
            $dangKy = DangKyCaTrong::create([
                'ca_trong_id'       => $rawId,
                'nhan_vien_id'      => $nhanVienId,
                'thoi_gian_dang_ky' => now(),
                'trang_thai'        => 'PENDING',
                'ghi_chu'           => $request->note ?? null,
            ]);

            DB::commit();

            [$caMaus, $codeMap] = $this->getShiftCodeMap();
            $formatted = $this->formatCaTrong($caTrong, $caMaus, $codeMap, 'NV' . str_pad($nhanVienId, 3, '0', STR_PAD_LEFT));

            return response()->json([
                'status'  => true,
                'ok'      => true,
                'message' => 'Đăng ký nhận ca thành công. Vui lòng chờ Quản lý xác nhận.',
                'shift'   => $formatted,
                'data'    => $dangKy,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi khi đăng ký nhận ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 8. POST /api/open-shifts/{id}/approve (Manager xác nhận nhân viên nhận ca)
     * CẬP NHẬT PHÂN CÔNG VÀ LỊCH LÀM VIỆC THỰC TẾ
     * RÀNG BUỘC NGHIÊM NGẶT:
     * - Nhân viên phải trống lịch toàn bộ ngày (kiểm tra lại trong transaction với lockForUpdate).
     * - Cập nhật trực tiếp vào phan_cong_cas.
     */
    public function approve(Request $request, $id = null)
    {
        try {
            DB::beginTransaction();

            $targetId = $id ?? $request->id ?? $request->shift_id ?? $request->ca_trong_id;
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);
            $regId = (int)($request->registration_id ?? $request->registrationId ?? $request->dang_ky_id ?? $request->claim_id);

            // 1. Khóa bản ghi ca_trongs
            $caTrong = CaTrong::where('ca_trong_id', $rawId)->lockForUpdate()->first();
            if (!$caTrong) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Không tìm thấy ca trực mở.'], 404);
            }

            if ($caTrong->trang_thai !== 'OPEN') {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Ca này đã đủ người hoặc không còn ở trạng thái mở.'], 422);
            }

            if ($caTrong->so_luong_da_nhan >= $caTrong->so_luong_can) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Ca trực này đã đủ số lượng nhân sự được duyệt.'], 422);
            }

            // 2. Khóa bản ghi đăng ký
            $dangKy = DangKyCaTrong::where('dang_ky_ca_trong_id', $regId)
                ->where('ca_trong_id', $rawId)
                ->lockForUpdate()
                ->first();

            if (!$dangKy) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Không tìm thấy thông tin đăng ký nhận ca.'], 404);
            }

            if ($dangKy->trang_thai === 'APPROVED') {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Đăng ký này đã được xác nhận trước đó.'], 422);
            }

            $nhanVien = NhanVien::find($dangKy->nhan_vien_id);
            if (!$nhanVien) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Không tìm thấy thông tin nhân viên đăng ký.'], 404);
            }

            // 3. Kiểm tra vai trò
            if ($caTrong->role_tag_id && (int)$nhanVien->role_tag_id !== (int)$caTrong->role_tag_id) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Không thể xác nhận: Vai trò của nhân viên không khớp với vị trí ca mở.',
                ], 422);
            }

            // 4. KIỂM TRA LẠI TOÀN BỘ NGÀY TRỐNG LỊCH TRONG TRANSACTION (QUY TẮC ĐẶC BIỆT BẮT BUỘC)
            $shiftDate = $caTrong->ngay_lam ? (is_string($caTrong->ngay_lam) ? substr($caTrong->ngay_lam, 0, 10) : $caTrong->ngay_lam->format('Y-m-d')) : ($caTrong->phanCongCa?->ngay_lam ?? null);

            if ($shiftDate) {
                $hasShiftInDay = PhanCongCa::where('nhan_vien_id', $nhanVien->nhan_vien_id)
                    ->where('ngay_lam', $shiftDate)
                    ->lockForUpdate()
                    ->exists();

                if ($hasShiftInDay) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => 'Không thể xác nhận nhân viên này vì họ đã có lịch làm việc trong ngày được chọn. Vui lòng chọn nhân viên khác.',
                    ], 422);
                }
            }

            // 5. CẬP NHẬT PHÂN CÔNG VÀ LỊCH LÀM VIỆC THỰC TẾ
            if ($caTrong->phan_cong_ca_id) {
                // Trường hợp ca thay thế: Cập nhật nhân viên mới vào phân công cũ
                $affected = PhanCongCa::where('phan_cong_ca_id', $caTrong->phan_cong_ca_id)
                    ->lockForUpdate()
                    ->update([
                        'nhan_vien_id' => $nhanVien->nhan_vien_id,
                        'trang_thai'   => 'ASSIGNED',
                        'updated_at'   => now(),
                    ]);
            } else {
                // Trường hợp ca mở mới / thiếu từ lịch tự động: Thêm mới bản ghi phân công vào lịch hiện tại
                $nhuCau = NhuCauCa::where('ngay_lam', $shiftDate)
                    ->where('ca_mau_id', $caTrong->ca_mau_id)
                    ->when($caTrong->role_tag_id, fn($q) => $q->where('role_tag_id', $caTrong->role_tag_id))
                    ->first();
                
                if (!$nhuCau) {
                    $nhuCau = NhuCauCa::where('ca_mau_id', $caTrong->ca_mau_id)->first();
                }
                $nhuCauId = $nhuCau ? $nhuCau->nhu_cau_ca_id : 1;

                PhanCongCa::create([
                    'lich_id'        => $caTrong->lich_id ?? 3,
                    'nhu_cau_ca_id'  => $nhuCauId,
                    'ca_mau_id'      => $caTrong->ca_mau_id,
                    'ngay_lam'       => $shiftDate,
                    'nhan_vien_id'   => $nhanVien->nhan_vien_id,
                    'trang_thai'     => 'ASSIGNED',
                    'xac_nhan'       => 1,
                ]);
            }

            // 6. Cập nhật trạng thái đăng ký sang APPROVED
            $dangKy->trang_thai = 'APPROVED';
            $dangKy->save();

            // 7. Tăng số lượng đã nhận của ca mở
            $caTrong->so_luong_da_nhan += 1;
            if ($caTrong->so_luong_da_nhan >= $caTrong->so_luong_can) {
                $caTrong->trang_thai = 'FILLED';

                // Tự động từ chối các đơn đăng ký còn lại của ca này
                DangKyCaTrong::where('ca_trong_id', $rawId)
                    ->where('dang_ky_ca_trong_id', '!=', $regId)
                    ->where('trang_thai', 'PENDING')
                    ->update([
                        'trang_thai'    => 'REJECTED',
                        'ly_do_tu_choi' => 'Ca trực mở đã đủ số lượng người nhận.',
                    ]);
            }
            $caTrong->save();

            DB::commit();

            [$caMaus, $codeMap] = $this->getShiftCodeMap();
            $formatted = $this->formatCaTrong($caTrong, $caMaus, $codeMap);

            return response()->json([
                'status'  => true,
                'message' => "Đã xác nhận nhân viên {$nhanVien->ho_ten} nhận ca thành công và cập nhật lịch làm việc.",
                'data'    => $formatted,
                'shift'   => $formatted,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi khi xác nhận nhân viên nhận ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 9. POST /api/open-shifts/{id}/reject (Manager từ chối nhân viên nhận ca)
     */
    public function reject(Request $request, $id = null)
    {
        try {
            $targetId = $id ?? $request->id ?? $request->shift_id ?? $request->ca_trong_id;
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);
            $regId = (int)($request->registration_id ?? $request->registrationId ?? $request->dang_ky_id ?? $request->claim_id);

            $dangKy = DangKyCaTrong::where('dang_ky_ca_trong_id', $regId)
                ->where('ca_trong_id', $rawId)
                ->first();

            if (!$dangKy) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy đăng ký nhận ca.'], 404);
            }

            $dangKy->trang_thai = 'REJECTED';
            $dangKy->ly_do_tu_choi = $request->reason ?? 'Quản lý từ chối yêu cầu nhận ca.';
            $dangKy->save();

            $caTrong = CaTrong::with(['caMau', 'roleTag', 'phanCongCa.caMau', 'dangKyCaTrongs.nhanVien.roleTag'])->find($rawId);
            [$caMaus, $codeMap] = $this->getShiftCodeMap();
            $formatted = $this->formatCaTrong($caTrong, $caMaus, $codeMap);

            return response()->json([
                'status'  => true,
                'message' => 'Đã từ chối đăng ký nhận ca.',
                'data'    => $formatted,
                'shift'   => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi khi từ chối đăng ký: ' . $e->getMessage(),
            ], 500);
        }
    }
}