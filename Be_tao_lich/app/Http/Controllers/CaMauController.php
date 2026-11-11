<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\CreateCaMauRequest;
use App\Http\Requests\Admin\UpdateCaMauRequest;
use App\Models\CaMau;
use App\Models\RangBuoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaMauController extends Controller
{
    /**
     * Chuẩn hóa dữ liệu ca mẫu cho response (hỗ trợ cả camelCase cho React và snake_case cho Database)
     */
    protected function formatShift(CaMau $item): array
    {
        $statusLower = strtolower($item->trang_thai ?? 'active');
        $statusNormalized = match ($statusLower) {
            'paused', 'tam_dung' => 'paused',
            'inactive', 'nghi'   => 'inactive',
            default              => 'active',
        };

        $startTimeFormatted = substr($item->gio_bat_dau, 0, 5);
        $endTimeFormatted   = substr($item->gio_ket_thuc, 0, 5);
        $durationMin        = $item->thoi_luong_phut;
        $isUsed             = $item->isUsedInSchedule();

        return [
            'id'                 => $item->ca_mau_id,
            'ca_mau_id'          => $item->ca_mau_id,
            'code'               => 'shift_' . $item->ca_mau_id,
            'name'               => $item->ten_ca,
            'ten_ca'             => $item->ten_ca,
            'startTime'          => $startTimeFormatted,
            'gio_bat_dau'        => $startTimeFormatted,
            'endTime'            => $endTimeFormatted,
            'gio_ket_thuc'       => $endTimeFormatted,
            'duration'           => $durationMin,
            'thoi_luong_phut'    => $durationMin,
            'thoi_luong'         => $item->thoi_luong_formatted,
            'duration_formatted' => $item->thoi_luong_formatted,
            'is_overnight'       => $item->is_overnight,
            'isOvernight'        => $item->is_overnight,
            'multiplier'         => (float) ($item->he_so_luong ?? 1.0),
            'he_so_luong'        => (float) ($item->he_so_luong ?? 1.0),
            'status'             => $statusNormalized,
            'trang_thai'         => strtoupper($item->trang_thai ?? 'ACTIVE'),
            'is_used'            => $isUsed,
            'isUsed'             => $isUsed,
            'created_at'         => $item->created_at,
            'updated_at'         => $item->updated_at,
        ];
    }

    /**
     * 1. Lấy danh sách ca mẫu (hỗ trợ tìm kiếm theo tên, lọc trạng thái, phân trang)
     */
    public function index(Request $request)
    {
        try {
            $query = CaMau::query();

            // Tìm kiếm theo tên ca (name, ten_ca, q, keyword, search)
            $keyword = trim($request->input('q') ?? $request->input('keyword') ?? $request->input('ten_ca') ?? $request->input('name') ?? $request->input('search') ?? '');
            if ($keyword !== '') {
                $query->where('ten_ca', 'like', '%' . $keyword . '%');
            }

            // Lọc theo trạng thái (status, trang_thai)
            $status = trim($request->input('status') ?? $request->input('trang_thai') ?? 'all');
            if ($status !== '' && strtolower($status) !== 'all') {
                $statusUpper = strtoupper($status);
                if ($statusUpper === 'ACTIVE') {
                    $query->whereIn('trang_thai', ['ACTIVE', 'active']);
                } elseif (in_array($statusUpper, ['PAUSED', 'TAM_DUNG'])) {
                    $query->whereIn('trang_thai', ['PAUSED', 'paused', 'TAM_DUNG']);
                } elseif (in_array($statusUpper, ['INACTIVE', 'NGHI'])) {
                    $query->whereIn('trang_thai', ['INACTIVE', 'inactive']);
                } else {
                    $query->where('trang_thai', $statusUpper);
                }
            }

            $query->orderBy('gio_bat_dau', 'asc')->orderBy('ca_mau_id', 'asc');

            // Phân trang: per_page hoặc pageSize
            $perPageParam = $request->input('per_page') ?? $request->input('pageSize');

            // Nếu yêu cầu lấy toàn bộ không phân trang
            if ($perPageParam === 'all' || $perPageParam === '-1') {
                $items = $query->get();
                $formatted = $items->map(fn($item) => $this->formatShift($item));

                return response()->json([
                    'status'  => true,
                    'message' => 'Lấy danh sách ca mẫu thành công',
                    'data'    => $formatted,
                    'total'   => $formatted->count(),
                ]);
            }

            $perPage = max(1, min(100, (int) ($perPageParam ?: 10)));
            $paginator = $query->paginate($perPage);

            $formatted = collect($paginator->items())->map(fn($item) => $this->formatShift($item));

            return response()->json([
                'status'     => true,
                'message'    => 'Lấy danh sách ca mẫu thành công',
                'data'       => $formatted,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
                'total'      => $paginator->total(),
                'page'       => $paginator->currentPage(),
                'pageSize'   => $paginator->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lấy danh sách ca mẫu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. Xem chi tiết ca mẫu theo ID
     */
    public function show($id)
    {
        try {
            $shiftId = (int) preg_replace('/[^0-9]/', '', (string)$id);
            $shift = CaMau::find($shiftId);

            if (!$shift) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy ca làm việc với ID ' . $id,
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Lấy chi tiết ca mẫu thành công',
                'data'    => $this->formatShift($shift),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi xem chi tiết ca mẫu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. Thêm ca mẫu mới
     */
    public function store(CreateCaMauRequest $request)
    {
        DB::beginTransaction();
        try {
            $tenCa      = trim($request->input('ten_ca') ?? $request->input('name'));
            $gioBatDau  = trim($request->input('gio_bat_dau') ?? $request->input('startTime'));
            $gioKetThuc = trim($request->input('gio_ket_thuc') ?? $request->input('endTime'));
            $heSoLuong  = $request->input('he_so_luong') ?? $request->input('multiplier') ?? 1.0;
            $rawStatus  = $request->input('trang_thai') ?? $request->input('status') ?? 'ACTIVE';

            // Chuẩn hóa giờ sang HH:mm:ss nếu chỉ có HH:mm
            if (strlen($gioBatDau) === 5) $gioBatDau .= ':00';
            if (strlen($gioKetThuc) === 5) $gioKetThuc .= ':00';

            $status = strtoupper($rawStatus);
            if ($status === 'PAUSED') $status = 'PAUSED';
            else $status = 'ACTIVE';

            $shift = CaMau::create([
                'ten_ca'       => $tenCa,
                'gio_bat_dau'  => $gioBatDau,
                'gio_ket_thuc' => $gioKetThuc,
                'he_so_luong'  => (float)$heSoLuong,
                'trang_thai'   => $status,
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Đã thêm ca mẫu “' . $shift->ten_ca . '” thành công',
                'data'    => $this->formatShift($shift),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tạo ca mẫu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. Cập nhật thông tin ca mẫu
     */
    public function update(UpdateCaMauRequest $request, $id = null)
    {
        DB::beginTransaction();
        try {
            $rawId = $id ?? $request->input('ca_mau_id') ?? $request->input('id');
            $shiftId = (int) preg_replace('/[^0-9]/', '', (string)$rawId);

            $shift = CaMau::find($shiftId);
            if (!$shift) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy ca làm việc cần cập nhật',
                ], 404);
            }

            $updateData = [];

            if ($request->has('ten_ca') || $request->has('name')) {
                $tenCa = trim($request->input('ten_ca') ?? $request->input('name'));
                if ($tenCa !== '') $updateData['ten_ca'] = $tenCa;
            }

            if ($request->has('gio_bat_dau') || $request->has('startTime')) {
                $start = trim($request->input('gio_bat_dau') ?? $request->input('startTime'));
                if (strlen($start) === 5) $start .= ':00';
                $updateData['gio_bat_dau'] = $start;
            }

            if ($request->has('gio_ket_thuc') || $request->has('endTime')) {
                $end = trim($request->input('gio_ket_thuc') ?? $request->input('endTime'));
                if (strlen($end) === 5) $end .= ':00';
                $updateData['gio_ket_thuc'] = $end;
            }

            if ($request->has('he_so_luong') || $request->has('multiplier')) {
                $mult = $request->input('he_so_luong') ?? $request->input('multiplier');
                $updateData['he_so_luong'] = (float)$mult;
            }

            if ($request->has('trang_thai') || $request->has('status')) {
                $st = strtoupper($request->input('trang_thai') ?? $request->input('status'));
                $updateData['trang_thai'] = in_array($st, ['PAUSED', 'INACTIVE']) ? 'PAUSED' : 'ACTIVE';
            }

            $shift->update($updateData);
            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Đã cập nhật ca mẫu “' . $shift->ten_ca . '” thành công',
                'data'    => $this->formatShift($shift),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi cập nhật ca mẫu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. Xóa ca mẫu (kiểm tra quan hệ với nhu_cau_cas, phan_cong_cas, dang_ky_ca_kha_dungs)
     */
    public function destroy(Request $request, $id = null)
    {
        try {
            $rawId = $id ?? $request->input('ca_mau_id') ?? $request->input('id');
            $shiftId = (int) preg_replace('/[^0-9]/', '', (string)$rawId);

            $shift = CaMau::find($shiftId);
            if (!$shift) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy ca làm việc cần xóa',
                ], 404);
            }

            // Kiểm tra ràng buộc quan hệ thực tế
            if ($shift->isUsedInSchedule()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ca này đã được sử dụng trong nhu cầu ca, lịch làm việc hoặc phân công. Không thể xóa vĩnh viễn để bảo vệ toàn vẹn dữ liệu. Vui lòng chuyển sang trạng thái Tạm dừng.',
                    'is_used' => true,
                ], 409);
            }

            $shiftName = $shift->ten_ca;
            $shift->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Đã xóa ca mẫu “' . $shiftName . '” thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi xóa ca mẫu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 6. Cập nhật trạng thái ca mẫu (Bật / Tạm dừng)
     */
    public function changeStatus(Request $request, $id = null)
    {
        try {
            $rawId = $id ?? $request->input('ca_mau_id') ?? $request->input('id');
            $shiftId = (int) preg_replace('/[^0-9]/', '', (string)$rawId);

            $shift = CaMau::find($shiftId);
            if (!$shift) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy ca làm việc',
                ], 404);
            }

            if ($request->has('status') || $request->has('trang_thai')) {
                $requested = strtoupper($request->input('status') ?? $request->input('trang_thai'));
                $newStatus = in_array($requested, ['PAUSED', 'INACTIVE']) ? 'PAUSED' : 'ACTIVE';
            } else {
                $newStatus = ($shift->trang_thai === 'ACTIVE') ? 'PAUSED' : 'ACTIVE';
            }

            // Không cho tạm dừng nếu đây là ca duy nhất đang hoạt động
            if ($newStatus === 'PAUSED') {
                $activeCount = CaMau::whereIn('trang_thai', ['ACTIVE', 'active'])
                    ->where('ca_mau_id', '!=', $shift->ca_mau_id)
                    ->count();
                if ($activeCount === 0) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Phải có ít nhất một ca đang hoạt động trong hệ thống.',
                    ], 422);
                }
            }

            $shift->update(['trang_thai' => $newStatus]);

            $label = $newStatus === 'ACTIVE' ? 'Đã kích hoạt ca' : 'Đã chuyển ca sang tạm dừng';

            return response()->json([
                'status'     => true,
                'message'    => "{$label} “{$shift->ten_ca}” thành công",
                'new_status' => strtolower($newStatus),
                'data'       => $this->formatShift($shift),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi thay đổi trạng thái ca mẫu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cấu hình chung mặc định
     */
    protected function getDefaultGeneralSettings(): array
    {
        return [
            'availabilityDeadlineDay'  => 'friday',
            'availabilityDeadlineTime' => '23:59',
            'gracePeriodMin'           => 5,
            'holidayMultiplier'        => 1.5,
            'holidays'                 => [
                ['id' => 'h1', 'date' => '2026-01-01', 'name' => 'Tết Dương Lịch', 'multiplier' => 1.5],
                ['id' => 'h2', 'date' => '2026-04-30', 'name' => 'Ngày Giải phóng miền Nam', 'multiplier' => 1.5],
                ['id' => 'h3', 'date' => '2026-05-01', 'name' => 'Ngày Quốc tế Lao động', 'multiplier' => 1.5],
                ['id' => 'h4', 'date' => '2026-09-02', 'name' => 'Quốc khánh', 'multiplier' => 1.5],
            ],
        ];
    }

    /**
     * Lấy danh sách ràng buộc đã format cho Frontend
     */
    protected function getFormattedConstraints(): array
    {
        $records = RangBuoc::where('ma_rang_buoc', '!=', 'GENERAL_SETTINGS')->get();
        $formatted = [];

        foreach ($records as $r) {
            $frontendId = RangBuoc::toFrontendId($r->ma_rang_buoc);
            $valData = is_array($r->gia_tri) ? $r->gia_tri : (json_decode($r->gia_tri ?? '', true) ?: []);

            $enabled = true;
            if (isset($valData['enabled'])) {
                $enabled = (bool) $valData['enabled'];
            }

            $item = [
                'id'       => $frontendId,
                'code'     => $r->ma_rang_buoc,
                'name'     => $r->ten_rang_buoc,
                'category' => strtolower($r->loai_rang_buoc),
                'enabled'  => $enabled,
            ];

            if (isset($valData['value'])) {
                $item['value'] = is_numeric($valData['value']) ? (int) $valData['value'] : $valData['value'];
            }
            if (isset($valData['weight'])) {
                $item['weight'] = is_numeric($valData['weight']) ? (float) $valData['weight'] : $valData['weight'];
            }

            $formatted[] = $item;
        }

        return $formatted;
    }

    /**
     * Lấy cấu hình chung hệ thống
     */
    protected function getGeneralSettings(): array
    {
        $settingRec = RangBuoc::where('ma_rang_buoc', 'GENERAL_SETTINGS')->first();
        if ($settingRec && !empty($settingRec->gia_tri)) {
            $val = is_array($settingRec->gia_tri) ? $settingRec->gia_tri : json_decode($settingRec->gia_tri, true);
            return array_merge($this->getDefaultGeneralSettings(), (array)$val);
        }
        return $this->getDefaultGeneralSettings();
    }

    /**
     * 7. Hỗ trợ endpoint tổng hợp /api/shift-config để Frontend load và lưu đồng bộ
     */
    public function getShiftConfig(Request $request)
    {
        try {
            $shifts = CaMau::orderBy('gio_bat_dau', 'asc')->get()->map(fn($s) => $this->formatShift($s));
            $constraints = $this->getFormattedConstraints();
            $generalSettings = $this->getGeneralSettings();
            $roleTags = \App\Models\RoleTag::where('trang_thai', 'ACTIVE')->orderBy('role_tag_id')->get()->map(function ($r) {
                return [
                    'id'   => $r->role_tag_id,
                    'name' => $r->ten_role,
                    'ten_role' => $r->ten_role,
                ];
            });
            $roleNames = \App\Models\RoleTag::where('trang_thai', 'ACTIVE')->orderBy('role_tag_id')->pluck('ten_role')->toArray();

            return response()->json([
                'status'          => true,
                'shifts'          => $shifts,
                'constraints'     => $constraints,
                'generalSettings' => $generalSettings,
                'roleTags'        => $roleTags,
                'roles'           => $roleNames,
                'data'            => [
                    'shifts'          => $shifts,
                    'constraints'     => $constraints,
                    'generalSettings' => $generalSettings,
                    'roleTags'        => $roleTags,
                    'roles'           => $roleNames,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải cấu hình ca & ràng buộc: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lưu tổng hợp từ /api/shift-config
     */
    public function saveShiftConfig(Request $request)
    {
        // 1. Validation ràng buộc nếu có
        $rawConstraints = $request->input('constraints');
        if (is_array($rawConstraints)) {
            $bounds = [
                'rest_between_shifts'  => ['min' => 12, 'max' => 24, 'name' => 'Thời gian nghỉ tối thiểu giữa 2 ngày làm'],
                'max_shifts_per_day'   => ['min' => 1,  'max' => 2,  'name' => 'Số ca tối đa trong một ngày'],
                'max_hours_per_day'    => ['min' => 4,  'max' => 10, 'name' => 'Số giờ làm tối đa mỗi ngày'],
                'max_hours_per_week'   => ['min' => 20, 'max' => 48, 'name' => 'Số giờ làm tối đa mỗi tuần'],
                'max_consecutive_days' => ['min' => 3,  'max' => 6,  'name' => 'Số ngày làm liên tiếp tối đa'],
            ];

            foreach ($rawConstraints as $c) {
                $id = $c['id'] ?? '';
                $enabled = isset($c['enabled']) ? (bool)$c['enabled'] : true;
                if ($enabled && isset($bounds[$id]) && isset($c['value'])) {
                    $val = $c['value'];
                    if (!is_numeric($val) || $val < $bounds[$id]['min'] || $val > $bounds[$id]['max']) {
                        return response()->json([
                            'status'  => false,
                            'message' => "Giá trị của “{$bounds[$id]['name']}” phải từ {$bounds[$id]['min']} đến {$bounds[$id]['max']}.",
                        ], 422);
                    }
                }
            }
        }

        // 2. Validation cấu hình chung nếu có
        $rawGeneral = $request->input('generalSettings');
        if (is_array($rawGeneral)) {
            if (isset($rawGeneral['gracePeriodMin'])) {
                $grace = $rawGeneral['gracePeriodMin'];
                if (!is_numeric($grace) || $grace < 0 || $grace > 30) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Thời gian ân hạn chấm công phải từ 0 đến 30 phút.',
                    ], 422);
                }
            }
            if (isset($rawGeneral['holidays']) && is_array($rawGeneral['holidays'])) {
                $seenDates = [];
                foreach ($rawGeneral['holidays'] as $h) {
                    $hName = trim($h['name'] ?? '');
                    $hDate = trim($h['date'] ?? '');
                    $hMult = $h['multiplier'] ?? 1.5;

                    if ($hName === '') {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Tên ngày lễ không được để trống.',
                        ], 422);
                    }
                    if ($hDate === '') {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Ngày áp dụng của ngày lễ không được để trống.',
                        ], 422);
                    }
                    if (!is_numeric($hMult) || $hMult < 1.0 || $hMult > 3.0) {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Hệ số lương ngày lễ phải từ 1.0 đến 3.0.',
                        ], 422);
                    }
                    if (in_array($hDate, $seenDates)) {
                        return response()->json([
                            'status'  => false,
                            'message' => "Đã có ngày lễ trùng lặp cho ngày {$hDate}.",
                        ], 422);
                    }
                    $seenDates[] = $hDate;
                }
            }
        }

        DB::beginTransaction();
        try {
            // A. Lưu ca mẫu nếu có
            $rawShifts = $request->input('shifts');
            if (is_array($rawShifts)) {
                foreach ($rawShifts as $s) {
                    $name  = trim($s['name'] ?? $s['ten_ca'] ?? '');
                    $start = trim($s['startTime'] ?? $s['gio_bat_dau'] ?? '06:00');
                    $end   = trim($s['endTime'] ?? $s['gio_ket_thuc'] ?? '12:00');
                    $mult  = (float) ($s['multiplier'] ?? $s['he_so_luong'] ?? 1.0);
                    $st    = strtoupper($s['status'] ?? $s['trang_thai'] ?? 'ACTIVE');
                    $stNorm = in_array($st, ['PAUSED', 'INACTIVE']) ? 'PAUSED' : 'ACTIVE';

                    if (strlen($start) === 5) $start .= ':00';
                    if (strlen($end) === 5) $end .= ':00';

                    $shiftId = isset($s['id']) && is_numeric($s['id']) && $s['id'] < 1000000000 ? (int)$s['id'] : null;

                    if ($shiftId && CaMau::where('ca_mau_id', $shiftId)->exists()) {
                        CaMau::where('ca_mau_id', $shiftId)->update([
                            'ten_ca'       => $name,
                            'gio_bat_dau'  => $start,
                            'gio_ket_thuc' => $end,
                            'he_so_luong'  => $mult,
                            'trang_thai'   => $stNorm,
                        ]);
                    } elseif (!empty($name)) {
                        CaMau::create([
                            'ten_ca'       => $name,
                            'gio_bat_dau'  => $start,
                            'gio_ket_thuc' => $end,
                            'he_so_luong'  => $mult,
                            'trang_thai'   => $stNorm,
                        ]);
                    }
                }
            }

            // B. Lưu ràng buộc nếu có
            if (is_array($rawConstraints)) {
                foreach ($rawConstraints as $c) {
                    $frontendId = $c['id'] ?? '';
                    if ($frontendId === '') continue;

                    $backendCode = RangBuoc::toBackendCode($frontendId);
                    $enabled = isset($c['enabled']) ? (bool)$c['enabled'] : true;

                    $existing = RangBuoc::where('ma_rang_buoc', $backendCode)->first();
                    $valData = $existing && is_array($existing->gia_tri) ? $existing->gia_tri : [];

                    $valData['enabled'] = $enabled;
                    if (isset($c['value'])) {
                        $valData['value'] = (int)$c['value'];
                    }
                    if (isset($c['weight'])) {
                        $valData['weight'] = (float)$c['weight'];
                    }

                    if ($existing) {
                        $existing->update(['gia_tri' => $valData]);
                    } else {
                        RangBuoc::create([
                            'ma_rang_buoc'  => $backendCode,
                            'ten_rang_buoc' => $c['name'] ?? $frontendId,
                            'loai_rang_buoc'=> strtoupper($c['category'] ?? 'HARD'),
                            'gia_tri'       => $valData,
                        ]);
                    }
                }
            }

            // C. Lưu cấu hình chung nếu có
            if (is_array($rawGeneral)) {
                $curGeneral = $this->getGeneralSettings();
                $mergedGeneral = array_merge($curGeneral, $rawGeneral);
                if (isset($rawGeneral['holidays']) && is_array($rawGeneral['holidays'])) {
                    $mergedGeneral['holidays'] = $rawGeneral['holidays'];
                }

                RangBuoc::updateOrCreate(
                    ['ma_rang_buoc' => 'GENERAL_SETTINGS'],
                    [
                        'ten_rang_buoc'  => 'Cấu hình chung hệ thống',
                        'loai_rang_buoc' => 'SOFT',
                        'gia_tri'        => $mergedGeneral,
                    ]
                );
            }

            DB::commit();

            $updatedShifts = CaMau::orderBy('gio_bat_dau', 'asc')->get()->map(fn($s) => $this->formatShift($s));
            $updatedConstraints = $this->getFormattedConstraints();
            $updatedGeneral = $this->getGeneralSettings();

            return response()->json([
                'status'          => true,
                'message'         => 'Lưu cấu hình thành công',
                'shifts'          => $updatedShifts,
                'constraints'     => $updatedConstraints,
                'generalSettings' => $updatedGeneral,
                'data'            => [
                    'shifts'          => $updatedShifts,
                    'constraints'     => $updatedConstraints,
                    'generalSettings' => $updatedGeneral,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lưu cấu hình: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 8. Lấy danh mục Role_Tag
     */
    public function getRoleTags(Request $request)
    {
        try {
            $data = \App\Models\RoleTag::where('trang_thai', 'ACTIVE')->orderBy('role_tag_id')->get();
            $formatted = $data->map(function ($r) {
                return [
                    'id'       => $r->role_tag_id,
                    'name'     => $r->ten_role,
                    'ten_role' => $r->ten_role,
                ];
            });
            $names = $data->pluck('ten_role')->toArray();

            return response()->json([
                'status'  => true,
                'message' => 'Lấy danh mục vai trò thành công',
                'data'    => $formatted,
                'roles'   => $names,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lấy danh mục vai trò: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 9. Thêm Role_Tag mới
     */
    public function storeRoleTag(Request $request)
    {
        try {
            $name = trim($request->input('name') ?? $request->input('roleName') ?? $request->input('ten_role') ?? '');
            if (empty($name)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tên vai trò không được để trống.',
                ], 422);
            }

            $exists = \App\Models\RoleTag::whereRaw('LOWER(ten_role) = ?', [mb_strtolower($name)])->exists();
            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => "Role_Tag “{$name}” đã tồn tại trong hệ thống.",
                ], 422);
            }

            \App\Models\RoleTag::create([
                'ten_role'   => $name,
                'trang_thai' => 'ACTIVE',
            ]);

            return $this->getRoleTags($request);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi thêm vai trò: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 10. Đổi tên Role_Tag
     */
    public function updateRoleTag(Request $request, $id = null)
    {
        try {
            $oldName = trim($request->input('oldRole') ?? $request->input('old_name') ?? '');
            $newName = trim($request->input('newRole') ?? $request->input('name') ?? $request->input('ten_role') ?? '');
            $roleId = $id ? (int)$id : null;

            if (empty($newName)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tên vai trò mới không được để trống.',
                ], 422);
            }

            $roleTag = null;
            if ($roleId) {
                $roleTag = \App\Models\RoleTag::find($roleId);
            } elseif (!empty($oldName)) {
                $roleTag = \App\Models\RoleTag::where('ten_role', $oldName)->first();
            }

            if (!$roleTag) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy vai trò cần chỉnh sửa.',
                ], 404);
            }

            $exists = \App\Models\RoleTag::where('role_tag_id', '!=', $roleTag->role_tag_id)
                ->whereRaw('LOWER(ten_role) = ?', [mb_strtolower($newName)])
                ->exists();
            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => "Role_Tag “{$newName}” đã tồn tại trong hệ thống.",
                ], 422);
            }

            $roleTag->update(['ten_role' => $newName]);

            return $this->getRoleTags($request);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi cập nhật vai trò: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 11. Xóa Role_Tag (Kiểm tra ràng buộc an toàn dữ liệu)
     */
    public function destroyRoleTag(Request $request, $id = null)
    {
        try {
            $name = trim($request->input('roleName') ?? $request->input('name') ?? $request->input('ten_role') ?? '');
            $roleId = $id ? (int)$id : null;

            $roleTag = null;
            if ($roleId) {
                $roleTag = \App\Models\RoleTag::find($roleId);
            } elseif (!empty($name)) {
                $roleTag = \App\Models\RoleTag::where('ten_role', $name)->first();
            }

            if (!$roleTag) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy vai trò cần xóa.',
                ], 404);
            }

            // Kiểm tra nhân viên đang sử dụng
            if (\App\Models\NhanVien::where('role_tag_id', $roleTag->role_tag_id)->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Role_Tag đang được sử dụng bởi nhân viên, không thể xóa để bảo toàn dữ liệu.',
                ], 422);
            }

            // Kiểm tra định biên nhu cầu ca
            if (\App\Models\NhuCauCa::where('role_tag_id', $roleTag->role_tag_id)->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Role_Tag đang được sử dụng trong cấu hình nhu cầu ca, không thể xóa.',
                ], 422);
            }

            $roleTag->delete();

            return $this->getRoleTags($request);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi xóa vai trò: ' . $e->getMessage(),
            ], 500);
        }
    }
}
