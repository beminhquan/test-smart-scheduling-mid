<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RangBuoc;
use App\Models\CaMau;
use App\Models\RoleTag;
use App\Models\NhuCauCa;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Danh mục định nghĩa ràng buộc chuẩn của hệ thống
     */
    protected function getConstraintMeta(): array
    {
        return [
            'MIN_REST_HOURS' => [
                'id'           => 'rest_between_shifts',
                'title'        => 'Thời gian nghỉ tối thiểu',
                'name'         => 'Thời gian nghỉ tối thiểu giữa 2 ngày làm',
                'desc'         => 'Từ lúc hết ca hôm trước đến lúc bắt đầu ca hôm sau phải cách nhau ít nhất số giờ này (Điều 109 BLLĐ 2019, tối thiểu 12 giờ).',
                'category'     => 'hard',
                'unit'         => 'giờ',
                'defaultValue' => 12,
                'min'          => 12,
                'max'          => 24,
                'type'         => 'coffee',
                'color'        => 'orange',
            ],
            'NO_NIGHT_TO_MORNING' => [
                'id'       => 'no_clopening',
                'title'    => 'Không xếp ca tối rồi ca sáng hôm sau',
                'name'     => 'Không xếp ca tối rồi ca sáng hôm sau',
                'desc'     => 'Nhân viên đóng ca tối muộn thì hôm sau không bị xếp ca mở cửa sáng sớm.',
                'category' => 'hard',
                'type'     => 'clock',
                'color'    => 'indigo',
            ],
            'MAX_SHIFTS_PER_DAY' => [
                'id'           => 'max_shifts_per_day',
                'title'        => 'Số ca tối đa trong ngày',
                'name'         => 'Số ca tối đa trong một ngày',
                'desc'         => 'Mỗi nhân viên chỉ được phân công tối đa số ca này trong một ngày.',
                'category'     => 'hard',
                'unit'         => 'ca/ngày',
                'defaultValue' => 1,
                'min'          => 1,
                'max'          => 2,
                'type'         => 'users',
                'color'        => 'blue',
            ],
            'MAX_HOURS_PER_DAY' => [
                'id'           => 'max_hours_per_day',
                'title'        => 'Số giờ làm tối đa mỗi ngày',
                'name'         => 'Số giờ làm tối đa mỗi ngày',
                'desc'         => 'Tổng giờ làm trong một ngày không vượt quá mức này (thông thường là 8 giờ).',
                'category'     => 'hard',
                'unit'         => 'giờ/ngày',
                'defaultValue' => 8,
                'min'          => 4,
                'max'          => 10,
                'type'         => 'timer',
                'color'        => 'green',
            ],
            'MAX_HOURS_PER_WEEK' => [
                'id'           => 'max_hours_per_week',
                'title'        => 'Giới hạn giờ làm việc tuần',
                'name'         => 'Số giờ làm tối đa mỗi tuần',
                'desc'         => 'Không cho nhân viên vượt quá số giờ làm tối đa mỗi tuần (tối đa 48 giờ/tuần).',
                'category'     => 'hard',
                'unit'         => 'giờ/tuần',
                'defaultValue' => 40,
                'min'          => 20,
                'max'          => 48,
                'type'         => 'timer',
                'color'        => 'green',
            ],
            'MAX_CONSECUTIVE_DAYS' => [
                'id'           => 'max_consecutive_days',
                'title'        => 'Số ngày làm liên tiếp tối đa',
                'name'         => 'Số ngày làm liên tiếp tối đa',
                'desc'         => 'Đảm bảo mỗi tuần có ít nhất 24h nghỉ ngơi liên tục (Bộ luật Lao động).',
                'category'     => 'hard',
                'unit'         => 'ngày',
                'defaultValue' => 6,
                'min'          => 3,
                'max'          => 6,
                'type'         => 'alert',
                'color'        => 'red',
            ],
            'NO_SHIFT_ON_APPROVED_LEAVE' => [
                'id'       => 'respect_approved_leave',
                'title'    => 'Không xếp ca vào ngày nghỉ đã duyệt',
                'name'     => 'Không xếp ca vào ngày nghỉ đã duyệt',
                'desc'     => 'Nhân viên có đơn xin nghỉ đã được Manager duyệt sẽ không bị xếp ca.',
                'category' => 'hard',
                'type'     => 'alert',
                'color'    => 'red',
            ],
            'MATCH_ROLE_TAG' => [
                'id'       => 'skill_match',
                'title'    => 'Theo vai trò chuyên môn (Role_Tag)',
                'name'     => 'Đúng vai trò chuyên môn (Role_Tag)',
                'desc'     => 'Chỉ phân công nhân viên vào ca phù hợp với vai trò yêu cầu (Phục vụ, Pha chế, Thu ngân).',
                'category' => 'hard',
                'type'     => 'shield',
                'color'    => 'amber',
            ],
            'FAIR_WORKING_HOURS' => [
                'id'       => 'fair_hours_distribution',
                'title'    => 'Phân bổ giờ làm công bằng',
                'name'     => 'Phân bổ giờ làm công bằng',
                'desc'     => 'Cố gắng chia đều tổng số giờ làm việc giữa các nhân viên trong tuần để đảm bảo công bằng.',
                'category' => 'soft',
                'type'     => 'timer',
                'color'    => 'green',
            ],
            'BALANCE_WEEKEND_SHIFTS' => [
                'id'       => 'fair_weekend_distribution',
                'title'    => 'Chia đều ca cuối tuần',
                'name'     => 'Chia đều ca cuối tuần',
                'desc'     => 'Chia đều các ca Thứ 7 và Chủ nhật giữa các nhân viên trong nhóm.',
                'category' => 'soft',
                'type'     => 'calendar',
                'color'    => 'blue',
            ],
            'PREFER_STAFF_AVAILABILITY' => [
                'id'       => 'preference_matching',
                'title'    => 'Theo nguyện vọng nhân viên',
                'name'     => 'Ưu tiên nguyện vọng của nhân viên',
                'desc'     => 'Cố gắng xếp đúng ca mong muốn và thời gian rảnh mà nhân viên đã đăng ký.',
                'category' => 'soft',
                'type'     => 'calendar',
                'color'    => 'orange',
            ],
            'NO_OVERLAP' => [
                'id'       => 'no_overlap',
                'title'    => 'Không trùng ca',
                'name'     => 'Không xếp trùng ca',
                'desc'     => 'Đảm bảo nhân viên không được phân công các ca bị trùng thời gian.',
                'category' => 'hard',
                'type'     => 'clock',
                'color'    => 'indigo',
            ],
        ];
    }

    /**
     * Lấy danh sách ràng buộc đã format với dữ liệu thực từ MySQL
     */
    protected function getFormattedConstraints(): array
    {
        $meta = $this->getConstraintMeta();
        $dbRecords = RangBuoc::where('ma_rang_buoc', '!=', 'GENERAL_SETTINGS')
            ->where('ma_rang_buoc', 'not like', 'WEEKLY_DEMAND_%')
            ->get()
            ->keyBy('ma_rang_buoc');

        $result = [];

        foreach ($meta as $code => $info) {
            $dbRow = $dbRecords->get($code);
            $valData = [];
            if ($dbRow) {
                $valData = is_array($dbRow->gia_tri) ? $dbRow->gia_tri : (json_decode($dbRow->gia_tri, true) ?: []);
            }

            $enabled = true;
            if (isset($valData['enabled'])) {
                $enabled = (bool) $valData['enabled'];
            }

            $value = isset($valData['value']) ? (int) $valData['value'] : ($info['defaultValue'] ?? null);

            $item = array_merge($info, [
                'code'    => $code,
                'name'    => $dbRow ? $dbRow->ten_rang_buoc : $info['name'],
                'enabled' => $enabled,
            ]);

            if ($value !== null) {
                $item['value'] = $value;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Sinh cấu hình nhu cầu nhân sự mặc định theo ca và vai trò
     */
    protected function getDefaultDemand(array $shifts, array $roles, bool $isWeekend = false): array
    {
        $demands = [];
        foreach ($shifts as $s) {
            $shiftId = $s['id'];
            $shortName = $s['shortName'] ?? $s['name'];

            $roleValues = [];
            $total = 0;

            foreach ($roles as $r) {
                $roleName = $r['name'];
                $val = 1;
                if ($roleName === 'Phục vụ') {
                    $val = $isWeekend ? 3 : 2;
                } elseif ($roleName === 'Pha chế') {
                    $val = $isWeekend ? 2 : 1;
                } elseif ($roleName === 'Thu ngân') {
                    $val = 1;
                }
                $roleValues[$r['id']] = $val;
                $roleValues[strtolower(str_replace(' ', '', $roleName))] = $val;
                $total += $val;
            }

            // Chuẩn hóa tên trường tiếng Việt cho frontend
            $phucVu = $roleValues['phucvu'] ?? 1;
            $phaChe = $roleValues['phache'] ?? 1;
            $thuNgan = $roleValues['thungan'] ?? 1;

            $demands[] = [
                'id'        => $s['code'] ?? "shift_{$shiftId}",
                'shiftId'   => $shiftId,
                'name'      => $s['name'] . ' (' . substr($s['startTime'], 0, 5) . ' - ' . substr($s['endTime'], 0, 5) . ')',
                'shortName' => $shortName,
                'time'      => substr($s['startTime'], 0, 5) . ' - ' . substr($s['endTime'], 0, 5),
                'phucVu'    => $phucVu,
                'phaChe'    => $phaChe,
                'thuNgan'   => $thuNgan,
                'roles'     => $roleValues,
                'total'     => $phucVu + $phaChe + $thuNgan,
                'color'     => $s['color'] ?? 'blue',
            ];
        }

        return $demands;
    }

    /**
     * 1. GET /api/schedule/input-config
     * Cung cấp toàn bộ dữ liệu đầu vào: Ràng buộc, Ca mẫu, RoleTag, Nhu cầu nhân sự tuần
     */
    public function getInputConfig(Request $request)
    {
        try {
            $weekMonday = $request->query('weekMonday');
            if (!$weekMonday || !strtotime($weekMonday)) {
                $weekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            }

            // 1. Ràng buộc từ MySQL
            $constraints = $this->getFormattedConstraints();

            // 2. Ca mẫu hoạt động từ MySQL
            $shifts = CaMau::where('trang_thai', 'ACTIVE')
                ->orderBy('gio_bat_dau', 'asc')
                ->get()
                ->map(function ($s) {
                    $code = 'morning';
                    $shortName = 'Sáng';
                    $color = 'blue';

                    if (stripos($s->ten_ca, 'chiều') !== false) {
                        $code = 'afternoon';
                        $shortName = 'Chiều';
                        $color = 'amber';
                    } elseif (stripos($s->ten_ca, 'tối') !== false) {
                        $code = 'evening';
                        $shortName = 'Tối';
                        $color = 'indigo';
                    }

                    return [
                        'id'         => $s->ca_mau_id,
                        'code'       => $code,
                        'name'       => $s->ten_ca,
                        'shortName'  => $shortName,
                        'startTime'  => $s->gio_bat_dau,
                        'endTime'    => $s->gio_ket_thuc,
                        'multiplier' => (float)$s->he_so_luong,
                        'status'     => strtolower($s->trang_thai),
                        'color'      => $color,
                    ];
                })
                ->toArray();

            // 3. Danh mục RoleTag của nhân viên (loại trừ Quản lý)
            $roles = RoleTag::where('trang_thai', 'ACTIVE')
                ->whereNotIn('ten_role', ['Quản lý', 'Manager'])
                ->orderBy('role_tag_id', 'asc')
                ->get()
                ->map(function ($r) {
                    return [
                        'id'   => $r->role_tag_id,
                        'name' => $r->ten_role,
                        'desc' => $r->mo_ta,
                    ];
                })
                ->toArray();

            // 4. Lấy cấu hình nhu cầu của tuần (từ rang_buoc hoặc nhu_cau_cas)
            $demandKey = "WEEKLY_DEMAND_{$weekMonday}";
            $savedDemandMeta = RangBuoc::where('ma_rang_buoc', $demandKey)->first();

            $useSameConfigForWeekend = true;
            $weekdayDemand = null;
            $weekendDemand = null;
            $saturdayDemand = null;
            $sundayDemand = null;

            if ($savedDemandMeta && !empty($savedDemandMeta->gia_tri)) {
                $val = is_array($savedDemandMeta->gia_tri) ? $savedDemandMeta->gia_tri : json_decode($savedDemandMeta->gia_tri, true);
                $useSameConfigForWeekend = isset($val['useSameConfigForWeekend']) ? (bool)$val['useSameConfigForWeekend'] : true;
                $weekdayDemand = $val['weekdayDemand'] ?? null;
                $weekendDemand = $val['weekendDemand'] ?? null;
                $saturdayDemand = $val['saturdayDemand'] ?? null;
                $sundayDemand = $val['sundayDemand'] ?? null;
            }

            // Nếu chưa có, khởi tạo mặc định
            if (!$weekdayDemand) {
                $weekdayDemand = $this->getDefaultDemand($shifts, $roles, false);
            }
            if (!$weekendDemand) {
                $weekendDemand = $this->getDefaultDemand($shifts, $roles, true);
            }

            return response()->json([
                'status'       => true,
                'weekMonday'   => $weekMonday,
                'constraints'  => $constraints,
                'shifts'       => $shifts,
                'roles'        => $roles,
                'demandConfig' => [
                    'weekMonday'              => $weekMonday,
                    'useSameConfigForWeekend' => $useSameConfigForWeekend,
                    'weekdayDemand'           => $weekdayDemand,
                    'weekendDemand'           => $weekendDemand,
                    'saturdayDemand'          => $saturdayDemand,
                    'sundayDemand'            => $sundayDemand,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lấy cấu hình đầu vào tạo lịch: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. GET /api/schedule/demand
     * Lấy cấu hình nhu cầu nhân sự cho một tuần cụ thể
     */
    public function getDemand(Request $request)
    {
        try {
            $weekMonday = $request->query('weekMonday');
            if (!$weekMonday || !strtotime($weekMonday)) {
                $weekMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            }

            $demandKey = "WEEKLY_DEMAND_{$weekMonday}";
            $savedDemandMeta = RangBuoc::where('ma_rang_buoc', $demandKey)->first();

            if ($savedDemandMeta && !empty($savedDemandMeta->gia_tri)) {
                $val = is_array($savedDemandMeta->gia_tri) ? $savedDemandMeta->gia_tri : json_decode($savedDemandMeta->gia_tri, true);
                return response()->json([
                    'status' => true,
                    'data'   => array_merge(['weekMonday' => $weekMonday], $val),
                ]);
            }

            // Nếu chưa lưu ở meta, trả về cấu hình mặc định
            $inputResp = $this->getInputConfig($request);
            $data = $inputResp->getData(true);

            return response()->json([
                'status' => true,
                'data'   => $data['demandConfig'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải nhu cầu nhân sự: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. POST /api/schedule/demand
     * Lưu cấu hình nhu cầu nhân sự tuần vào rang_buoc và cập nhật trực tiếp nhu_cau_cas
     */
    public function saveDemand(Request $request)
    {
        $weekMonday = $request->input('weekMonday');
        if (!$weekMonday || !strtotime($weekMonday)) {
            return response()->json([
                'status'  => false,
                'message' => 'Ngày bắt đầu tuần (weekMonday) không hợp lệ (định dạng YYYY-MM-DD).',
            ], 422);
        }

        $useSameConfigForWeekend = (bool)$request->input('useSameConfigForWeekend', true);
        $weekdayDemand = $request->input('weekdayDemand');
        $weekendDemand = $request->input('weekendDemand');
        $saturdayDemand = $request->input('saturdayDemand');
        $sundayDemand = $request->input('sundayDemand');

        if (!is_array($weekdayDemand) || empty($weekdayDemand)) {
            return response()->json([
                'status'  => false,
                'message' => 'Dữ liệu nhu cầu nhân sự ngày thường không được để trống.',
            ], 422);
        }

        // Kiểm tra hợp lệ các số lượng
        foreach ($weekdayDemand as $item) {
            $total = ($item['phucVu'] ?? 0) + ($item['phaChe'] ?? 0) + ($item['thuNgan'] ?? 0);
            if (($item['phucVu'] ?? 0) < 0 || ($item['phaChe'] ?? 0) < 0 || ($item['thuNgan'] ?? 0) < 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Số lượng nhân viên cho mỗi vai trò không được là số âm.',
                ], 422);
            }
        }

        if (is_array($weekendDemand)) {
            foreach ($weekendDemand as $item) {
                if (($item['phucVu'] ?? 0) < 0 || ($item['phaChe'] ?? 0) < 0 || ($item['thuNgan'] ?? 0) < 0) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Số lượng nhân viên cuối tuần không được là số âm.',
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $demandKey = "WEEKLY_DEMAND_{$weekMonday}";

            $metaPayload = [
                'useSameConfigForWeekend' => $useSameConfigForWeekend,
                'weekdayDemand'           => $weekdayDemand,
                'weekendDemand'           => $weekendDemand,
                'saturdayDemand'          => $saturdayDemand,
                'sundayDemand'            => $sundayDemand,
                'updated_at'              => Carbon::now()->toIso8601String(),
            ];

            RangBuoc::updateOrCreate(
                ['ma_rang_buoc' => $demandKey],
                [
                    'ten_rang_buoc'  => "Cấu hình nhu cầu nhân sự tuần {$weekMonday}",
                    'loai_rang_buoc' => 'SOFT',
                    'gia_tri'        => $metaPayload,
                ]
            );

            // Cập nhật bảng nhu_cau_cas cho 7 ngày của tuần
            // Tìm roleTagIds từ MySQL
            $roleTags = RoleTag::where('trang_thai', 'ACTIVE')->get()->keyBy(function ($r) {
                $name = strtolower(trim($r->ten_role));
                if (stripos($name, 'phục vụ') !== false) return 'phucVu';
                if (stripos($name, 'pha chế') !== false) return 'phaChe';
                if (stripos($name, 'thu ngân') !== false) return 'thuNgan';
                return $r->role_tag_id;
            });

            $mon = Carbon::parse($weekMonday);
            $sun = $mon->copy()->addDays(6);

            // Xóa các bản ghi cũ của tuần đó
            NhuCauCa::whereBetween('ngay_lam', [$mon->format('Y-m-d'), $sun->format('Y-m-d')])->delete();

            $shifts = CaMau::where('trang_thai', 'ACTIVE')->get()->keyBy('ca_mau_id');

            // 7 ngày trong tuần: 0=T2, 1=T3, 2=T4, 3=T5, 4=T6, 5=T7, 6=CN
            for ($dayIdx = 0; $dayIdx < 7; $dayIdx++) {
                $currentDate = $mon->copy()->addDays($dayIdx)->format('Y-m-d');
                $isWeekend = ($dayIdx === 5 || $dayIdx === 6);

                $activeDemandList = $weekdayDemand;

                if ($isWeekend) {
                    if ($useSameConfigForWeekend) {
                        // Chế độ A: Dùng chung cấu hình ngày thường cho cuối tuần
                        $activeDemandList = $weekdayDemand;
                    } else {
                        // Chế độ B: Thiết lập cuối tuần riêng
                        if ($dayIdx === 5 && is_array($saturdayDemand) && !empty($saturdayDemand)) {
                            $activeDemandList = $saturdayDemand;
                        } elseif ($dayIdx === 6 && is_array($sundayDemand) && !empty($sundayDemand)) {
                            $activeDemandList = $sundayDemand;
                        } elseif (is_array($weekendDemand) && !empty($weekendDemand)) {
                            $activeDemandList = $weekendDemand;
                        }
                    }
                }

                foreach ($activeDemandList as $dItem) {
                    $shiftId = $dItem['shiftId'] ?? null;
                    if (!$shiftId) {
                        // Khớp theo code / tên ca
                        $code = $dItem['id'] ?? '';
                        foreach ($shifts as $s) {
                            if ($code === 'morning' && stripos($s->ten_ca, 'sáng') !== false) $shiftId = $s->ca_mau_id;
                            elseif ($code === 'afternoon' && stripos($s->ten_ca, 'chiều') !== false) $shiftId = $s->ca_mau_id;
                            elseif ($code === 'evening' && stripos($s->ten_ca, 'tối') !== false) $shiftId = $s->ca_mau_id;
                        }
                    }

                    if (!$shiftId || !$shifts->has($shiftId)) {
                        continue;
                    }

                    $roleCounts = [
                        'phucVu'  => (int)($dItem['phucVu'] ?? 0),
                        'phaChe'  => (int)($dItem['phaChe'] ?? 0),
                        'thuNgan' => (int)($dItem['thuNgan'] ?? 0),
                    ];

                    foreach ($roleCounts as $roleKey => $count) {
                        $roleTag = $roleTags->get($roleKey);
                        $roleTagId = $roleTag ? $roleTag->role_tag_id : ($roleKey === 'phucVu' ? 4 : ($roleKey === 'phaChe' ? 2 : 3));

                        NhuCauCa::create([
                            'ca_mau_id'    => $shiftId,
                            'ngay_lam'     => $currentDate,
                            'so_luong_can' => $count,
                            'role_tag_id'  => $roleTagId,
                            'ghi_chu'      => "Nhu cầu tuần {$weekMonday} - " . ($isWeekend ? ($useSameConfigForWeekend ? 'Cuối tuần (dùng chung)' : 'Cuối tuần (riêng)') : 'Ngày thường'),
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Lưu cấu hình nhu cầu nhân sự thành công',
                'data'    => $metaPayload,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi lưu cấu hình nhu cầu nhân sự: ' . $e->getMessage(),
            ], 500);
        }
    }
}

