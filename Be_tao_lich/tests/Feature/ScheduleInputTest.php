<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\TaiKhoan;
use App\Models\NhanVien;
use App\Models\RangBuoc;
use App\Models\CaMau;
use App\Models\RoleTag;
use App\Models\NhuCauCa;
use Database\Seeders\CaMauSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RangBuocSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ScheduleInputTest extends TestCase
{
    use RefreshDatabase;

    protected $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
        $this->seed(CaMauSeeder::class);
        $this->seed(RangBuocSeeder::class);

        // Lấy tài khoản MANAGER
        $this->managerUser = TaiKhoan::where('vai_tro', 'MANAGER')->first();
    }

    /**
     * Test 1: Đọc chính xác 11 ràng buộc từ Backend/Database khi gọi /api/schedule/input-config
     */
    public function test_can_get_schedule_input_config_with_constraints_from_mysql()
    {
        Sanctum::actingAs($this->managerUser);

        $response = $this->getJson('/api/schedule/input-config?weekMonday=2026-11-09');

        $response->assertStatus(200)
            ->assertJson(['status' => true])
            ->assertJsonStructure([
                'status',
                'weekMonday',
                'constraints',
                'shifts',
                'roles',
                'demandConfig' => [
                    'weekMonday',
                    'useSameConfigForWeekend',
                    'weekdayDemand',
                    'weekendDemand',
                ],
            ]);

        $constraints = $response->json('constraints');
        $this->assertNotEmpty($constraints);
        $ids = array_column($constraints, 'id');
        $this->assertContains('rest_between_shifts', $ids);
        $this->assertContains('no_clopening', $ids);
        $this->assertContains('max_shifts_per_day', $ids);
        $this->assertContains('max_hours_per_day', $ids);
        $this->assertContains('max_hours_per_week', $ids);
        $this->assertContains('max_consecutive_days', $ids);
        $this->assertContains('respect_approved_leave', $ids);
        $this->assertContains('skill_match', $ids);
        $this->assertContains('fair_hours_distribution', $ids);
        $this->assertContains('fair_weekend_distribution', $ids);
        $this->assertContains('preference_matching', $ids);
    }

    /**
     * Test 2: Thay đổi ràng buộc ở trang Ca & ràng buộc (/api/shift-config) phản ánh ngay tại /api/schedule/input-config
     */
    public function test_shift_config_changes_reflect_in_schedule_input_config()
    {
        Sanctum::actingAs($this->managerUser);

        // Đổi giá trị max_hours_per_week thành 44 và tắt preference_matching
        $savePayload = [
            'constraints' => [
                ['id' => 'max_hours_per_week', 'enabled' => true, 'value' => 44],
                ['id' => 'preference_matching', 'enabled' => false],
            ],
        ];

        $saveResp = $this->postJson('/api/shift-config', $savePayload);
        $saveResp->assertStatus(200);

        // Kiểm tra input-config nhận được giá trị mới
        $inputResp = $this->getJson('/api/schedule/input-config?weekMonday=2026-11-09');
        $inputResp->assertStatus(200);

        $constraints = collect($inputResp->json('constraints'))->keyBy('id');

        $this->assertEquals(44, $constraints['max_hours_per_week']['value']);
        $this->assertTrue($constraints['max_hours_per_week']['enabled']);

        $this->assertFalse($constraints['preference_matching']['enabled']);
    }

    /**
     * Test 3: Thiết lập nhu cầu nhân sự ngày thường (T2–T6) và lưu vào MySQL
     */
    public function test_save_and_verify_weekday_demand()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $weekdayDemand = [
            [
                'id'        => 'morning',
                'shiftId'   => 1,
                'name'      => 'Ca sáng',
                'shortName' => 'Sáng',
                'phucVu'    => 2,
                'phaChe'    => 1,
                'thuNgan'   => 1,
                'total'     => 4,
            ],
            [
                'id'        => 'afternoon',
                'shiftId'   => 2,
                'name'      => 'Ca chiều',
                'shortName' => 'Chiều',
                'phucVu'    => 1,
                'phaChe'    => 1,
                'thuNgan'   => 1,
                'total'     => 3,
            ],
            [
                'id'        => 'evening',
                'shiftId'   => 3,
                'name'      => 'Ca tối',
                'shortName' => 'Tối',
                'phucVu'    => 2,
                'phaChe'    => 1,
                'thuNgan'   => 1,
                'total'     => 4,
            ],
        ];

        $payload = [
            'weekMonday'              => $weekMonday,
            'useSameConfigForWeekend' => true,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => $weekdayDemand,
        ];

        $resp = $this->postJson('/api/schedule/demand', $payload);
        $resp->assertStatus(200)->assertJson(['status' => true]);

        // Kiểm tra trong bảng nhu_cau_cas cho ngày thứ 2 (2026-11-09) và thứ 6 (2026-11-13)
        $t2Morning = NhuCauCa::where('ngay_lam', '2026-11-09')
            ->where('ca_mau_id', 1)
            ->get();
        $this->assertNotEmpty($t2Morning);

        // Kiểm tra tổng số lượng ca sáng ngày thứ 2 = 4 (2 + 1 + 1)
        $this->assertEquals(4, $t2Morning->sum('so_luong_can'));

        // Kiểm tra ngày thứ 6 cũng có cùng số lượng = 4
        $t6Morning = NhuCauCa::where('ngay_lam', '2026-11-13')
            ->where('ca_mau_id', 1)
            ->get();
        $this->assertEquals(4, $t6Morning->sum('so_luong_can'));
    }

    /**
     * Test 4: Chế độ A (Dùng chung cấu hình) - Cuối tuần (T7, CN) kế thừa đúng nhu cầu ngày thường
     */
    public function test_shared_mode_weekend_inherits_weekday_demand()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $weekdayDemand = [
            [
                'id'        => 'morning',
                'shiftId'   => 1,
                'name'      => 'Ca sáng',
                'shortName' => 'Sáng',
                'phucVu'    => 2,
                'phaChe'    => 1,
                'thuNgan'   => 1,
                'total'     => 4,
            ],
        ];

        $payload = [
            'weekMonday'              => $weekMonday,
            'useSameConfigForWeekend' => true,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => [],
        ];

        $resp = $this->postJson('/api/schedule/demand', $payload);
        $resp->assertStatus(200);

        // Thứ 7 (2026-11-14) và Chủ nhật (2026-11-15) phải có đúng nhu cầu giống ngày thường
        $t7Morning = NhuCauCa::where('ngay_lam', '2026-11-14')->where('ca_mau_id', 1)->sum('so_luong_can');
        $cnMorning = NhuCauCa::where('ngay_lam', '2026-11-15')->where('ca_mau_id', 1)->sum('so_luong_can');

        $this->assertEquals(4, $t7Morning);
        $this->assertEquals(4, $cnMorning);
    }

    /**
     * Test 5: Chế độ B (Thiết lập cuối tuần riêng) - T7 & CN có số lượng nhân sự riêng biệt
     */
    public function test_custom_weekend_demand_mode()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $weekdayDemand = [
            [
                'id'        => 'morning',
                'shiftId'   => 1,
                'name'      => 'Ca sáng',
                'shortName' => 'Sáng',
                'phucVu'    => 2,
                'phaChe'    => 1,
                'thuNgan'   => 1,
                'total'     => 4,
            ],
        ];

        // Cuối tuần tăng cường nhân lực: 3 Phục vụ, 2 Pha chế, 1 Thu ngân = 6
        $weekendDemand = [
            [
                'id'        => 'morning',
                'shiftId'   => 1,
                'name'      => 'Ca sáng',
                'shortName' => 'Sáng',
                'phucVu'    => 3,
                'phaChe'    => 2,
                'thuNgan'   => 1,
                'total'     => 6,
            ],
        ];

        $payload = [
            'weekMonday'              => $weekMonday,
            'useSameConfigForWeekend' => false,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => $weekendDemand,
        ];

        $resp = $this->postJson('/api/schedule/demand', $payload);
        $resp->assertStatus(200);

        // Ngày thường = 4
        $t2Morning = NhuCauCa::where('ngay_lam', '2026-11-09')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(4, $t2Morning);

        // Thứ 7 = 6 (riêng)
        $t7Morning = NhuCauCa::where('ngay_lam', '2026-11-14')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(6, $t7Morning);

        // Chủ nhật = 6 (riêng)
        $cnMorning = NhuCauCa::where('ngay_lam', '2026-11-15')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(6, $cnMorning);
    }

    /**
     * Test 6: Chuyển qua lại giữa Chế độ A và Chế độ B bảo toàn dữ liệu thiết lập riêng
     */
    public function test_switching_modes_preserves_custom_weekend_config()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $weekdayDemand = [
            ['id' => 'morning', 'shiftId' => 1, 'name' => 'Ca sáng', 'phucVu' => 2, 'phaChe' => 1, 'thuNgan' => 1, 'total' => 4],
        ];
        $weekendDemand = [
            ['id' => 'morning', 'shiftId' => 1, 'name' => 'Ca sáng', 'phucVu' => 5, 'phaChe' => 3, 'thuNgan' => 2, 'total' => 10],
        ];

        // 1. Lưu ở chế độ B (riêng)
        $this->postJson('/api/schedule/demand', [
            'weekMonday'              => $weekMonday,
            'useSameConfigForWeekend' => false,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => $weekendDemand,
        ])->assertStatus(200);

        // 2. Chuyển sang chế độ A (dùng chung) nhưng vẫn giữ weekendDemand trong payload
        $this->postJson('/api/schedule/demand', [
            'weekMonday'              => $weekMonday,
            'useSameConfigForWeekend' => true,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => $weekendDemand,
        ])->assertStatus(200);

        // Kiểm tra nhu_cau_cas ngày T7 áp dụng theo ngày thường (= 4)
        $t7Count = NhuCauCa::where('ngay_lam', '2026-11-14')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(4, $t7Count);

        // 3. Đọc lại từ API, cấu hình weekendDemand vẫn được bảo toàn nguyên vẹn
        $getResp = $this->getJson("/api/schedule/demand?weekMonday={$weekMonday}");
        $getResp->assertStatus(200);
        $savedWeekend = $getResp->json('data.weekendDemand');
        $this->assertEquals(10, $savedWeekend[0]['total']);
        $this->assertEquals(5, $savedWeekend[0]['phucVu']);

        // 4. Bật lại chế độ B (riêng)
        $this->postJson('/api/schedule/demand', [
            'weekMonday'              => $weekMonday,
            'useSameConfigForWeekend' => false,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => $savedWeekend,
        ])->assertStatus(200);

        // Nhu_cau_cas ngày T7 lập tức phục hồi lại 10
        $t7Restored = NhuCauCa::where('ngay_lam', '2026-11-14')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(10, $t7Restored);
    }

    /**
     * Test 7: Cô lập dữ liệu giữa các tuần khác nhau
     */
    public function test_weekly_demand_isolation()
    {
        Sanctum::actingAs($this->managerUser);

        $week1 = '2026-11-09';
        $week2 = '2026-11-16';

        // Tuần 1: Ca sáng 4 người
        $this->postJson('/api/schedule/demand', [
            'weekMonday'              => $week1,
            'useSameConfigForWeekend' => true,
            'weekdayDemand'           => [
                ['id' => 'morning', 'shiftId' => 1, 'name' => 'Ca sáng', 'phucVu' => 2, 'phaChe' => 1, 'thuNgan' => 1, 'total' => 4],
            ],
            'weekendDemand'           => [],
        ])->assertStatus(200);

        // Tuần 2: Ca sáng 6 người
        $this->postJson('/api/schedule/demand', [
            'weekMonday'              => $week2,
            'useSameConfigForWeekend' => true,
            'weekdayDemand'           => [
                ['id' => 'morning', 'shiftId' => 1, 'name' => 'Ca sáng', 'phucVu' => 4, 'phaChe' => 1, 'thuNgan' => 1, 'total' => 6],
            ],
            'weekendDemand'           => [],
        ])->assertStatus(200);

        // Kiểm tra tuần 1 không bị tuần 2 ghi đè
        $w1Morning = NhuCauCa::where('ngay_lam', '2026-11-09')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(4, $w1Morning);

        $w2Morning = NhuCauCa::where('ngay_lam', '2026-11-16')->where('ca_mau_id', 1)->sum('so_luong_can');
        $this->assertEquals(6, $w2Morning);
    }

    /**
     * Test 8: Validate không cho phép số lượng âm
     */
    public function test_cannot_save_negative_demand()
    {
        Sanctum::actingAs($this->managerUser);

        $resp = $this->postJson('/api/schedule/demand', [
            'weekMonday'              => '2026-11-09',
            'useSameConfigForWeekend' => true,
            'weekdayDemand'           => [
                ['id' => 'morning', 'shiftId' => 1, 'phucVu' => -1, 'phaChe' => 1, 'thuNgan' => 1],
            ],
        ]);

        $resp->assertStatus(422)
            ->assertJson(['status' => false]);
    }

    /**
     * Test 9: Kiểm tra tổng nhân sự theo từng ca luôn bằng tổng các vai trò
     */
    public function test_shift_total_always_equals_sum_of_roles()
    {
        Sanctum::actingAs($this->managerUser);

        $inputResp = $this->getJson('/api/schedule/input-config?weekMonday=2026-11-09');
        $inputResp->assertStatus(200);

        $weekdayDemand = $inputResp->json('demandConfig.weekdayDemand');
        $this->assertNotEmpty($weekdayDemand);

        foreach ($weekdayDemand as $shift) {
            $sum = ($shift['phucVu'] ?? 0) + ($shift['phaChe'] ?? 0) + ($shift['thuNgan'] ?? 0);
            $this->assertEquals($sum, $shift['total'], "Tổng của ca {$shift['name']} phải bằng tổng các vai trò");
        }
    }

    /**
     * Test 10: Chỉ MANAGER mới được quyền truy cập và lưu cấu hình tạo lịch (Staff bị chặn 403)
     */
    public function test_staff_cannot_access_or_modify_schedule_input_config()
    {
        $staffUser = TaiKhoan::where('vai_tro', 'STAFF')->first();
        Sanctum::actingAs($staffUser);

        $getResp = $this->getJson('/api/schedule/input-config?weekMonday=2026-11-09');
        $getResp->assertStatus(403);

        $postResp = $this->postJson('/api/schedule/demand', [
            'weekMonday'    => '2026-11-09',
            'weekdayDemand' => [],
        ]);
        $postResp->assertStatus(403);
    }
}