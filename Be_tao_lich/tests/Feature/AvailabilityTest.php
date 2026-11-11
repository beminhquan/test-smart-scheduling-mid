<?php

namespace Tests\Feature;

use App\Models\CaMau;
use App\Models\DangKyCaKhaDung;
use App\Models\NhanVien;
use App\Models\RangBuoc;
use App\Models\RoleTag;
use App\Models\TaiKhoan;
use App\Models\TuanDangKy;
use Database\Seeders\CaMauSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RangBuocSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $staff1;
    protected $staff2;
    protected $targetWeekMonday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
        $this->seed(CaMauSeeder::class);
        $this->seed(RangBuocSeeder::class);

        // Manager (NV001)
        $this->manager = TaiKhoan::where('vai_tro', 'MANAGER')->first();

        // Staff 1 (NV002) and Staff 2 (NV003)
        $staffAccounts = TaiKhoan::where('vai_tro', 'STAFF')->get();
        $this->staff1 = $staffAccounts[0];
        $this->staff2 = $staffAccounts[1];

        // Chọn tuần mục tiêu trong tương lai (next month monday) để chắc chắn chưa hết hạn
        $nextMonthMonday = new \DateTime('first monday of next month');
        $this->targetWeekMonday = $nextMonthMonday->format('Y-m-d');
    }

    /**
     * Test 1 & 2: Staff đăng ký nhiều ca Rảnh/Bận và lưu thành công vào MySQL
     */
    public function test_staff_submits_availability_and_persists_in_mysql()
    {
        Sanctum::actingAs($this->staff1);

        $slots = [
            '0_Sáng'  => 'available',
            '0_Chiều' => 'busy',
            '1_Sáng'  => 'available',
            '2_Tối'   => 'available',
            '6_Sáng'  => 'busy',
        ];

        $res = $this->postJson('/api/availability/my', [
            'weekMonday' => $this->targetWeekMonday,
            'slots'      => $slots,
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('ok', true);
        $res->assertJsonPath('status', 'submitted');

        // Kiểm tra trong MySQL: bảng tuan_dang_kis
        $nvId = $this->staff1->nhanVien->nhan_vien_id;
        $tuan = TuanDangKy::where('nhan_vien_id', $nvId)
            ->where('tu_ngay', $this->targetWeekMonday)
            ->first();

        $this->assertNotNull($tuan);
        $this->assertEquals('SUBMITTED', $tuan->trang_thai);
        $this->assertNotNull($tuan->thoi_gian_gui);

        // Kiểm tra chi tiết trong bảng dang_ky_ca_kha_dungs
        $records = DangKyCaKhaDung::where('tuan_dang_ky_id', $tuan->tuan_dang_ky_id)->get();
        $this->assertCount(5, $records);

        // Ca sáng thứ 2 (thu_trong_tuan = 1, AVAILABLE)
        $caSang = CaMau::where('ten_ca', 'like', '%sáng%')->first();
        $recSangT2 = $records->where('thu_trong_tuan', 1)->where('ca_mau_id', $caSang->ca_mau_id)->first();
        $this->assertNotNull($recSangT2);
        $this->assertEquals('AVAILABLE', $recSangT2->trang_thai);

        // Ca chiều thứ 2 (thu_trong_tuan = 1, UNAVAILABLE)
        $caChieu = CaMau::where('ten_ca', 'like', '%chiều%')->first();
        $recChieuT2 = $records->where('thu_trong_tuan', 1)->where('ca_mau_id', $caChieu->ca_mau_id)->first();
        $this->assertNotNull($recChieuT2);
        $this->assertEquals('UNAVAILABLE', $recChieuT2->trang_thai);
    }

    /**
     * Test 3: Manager nhìn thấy ngay đăng ký của nhân viên đó
     */
    public function test_manager_sees_registered_availability()
    {
        // Staff1 gửi đăng ký
        Sanctum::actingAs($this->staff1);
        $slots = [
            '0_Sáng'  => 'available',
            '0_Chiều' => 'busy',
        ];
        $this->postJson('/api/availability/my', [
            'weekMonday' => $this->targetWeekMonday,
            'slots'      => $slots,
        ]);

        // Manager vào xem
        Sanctum::actingAs($this->manager);
        $resMgr = $this->getJson("/api/availability/weekly?weekMonday={$this->targetWeekMonday}");

        $resMgr->assertStatus(200);
        $resMgr->assertJsonStructure([
            'status',
            'weekMonday',
            'days',
            'shifts',
            'employees',
            'summary' => ['registeredCount', 'unregisteredCount', 'totalEmployees'],
        ]);

        $emp1Code = 'NV' . str_pad($this->staff1->nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT);
        $employees = collect($resMgr->json('employees'));
        $empRecord = $employees->firstWhere('id', $emp1Code);

        $this->assertNotNull($empRecord);
        $this->assertTrue($empRecord['registered']);
        $this->assertEquals('available', $empRecord['slots']['0_Sáng'] ?? null);
        $this->assertEquals('busy', $empRecord['slots']['0_Chiều'] ?? null);
    }

    /**
     * Test 4: Nhiều nhân viên đăng ký cùng tuần và Manager xem đầy đủ
     */
    public function test_multiple_staff_register_same_week()
    {
        // Staff 1 đăng ký
        Sanctum::actingAs($this->staff1);
        $this->postJson('/api/availability/my', [
            'weekMonday' => $this->targetWeekMonday,
            'slots'      => ['0_Sáng' => 'available'],
        ]);

        // Staff 2 đăng ký
        Sanctum::actingAs($this->staff2);
        $this->postJson('/api/availability/my', [
            'weekMonday' => $this->targetWeekMonday,
            'slots'      => ['0_Chiều' => 'available', '1_Tối' => 'busy'],
        ]);

        // Manager xem
        Sanctum::actingAs($this->manager);
        $res = $this->getJson("/api/availability/weekly?weekMonday={$this->targetWeekMonday}");

        $emp1Code = 'NV' . str_pad($this->staff1->nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT);
        $emp2Code = 'NV' . str_pad($this->staff2->nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT);

        $employees = collect($res->json('employees'));
        $emp1 = $employees->firstWhere('id', $emp1Code);
        $emp2 = $employees->firstWhere('id', $emp2Code);

        $this->assertTrue($emp1['registered']);
        $this->assertTrue($emp2['registered']);
        $this->assertGreaterThanOrEqual(2, $res->json('summary.registeredCount'));
    }

    /**
     * Test 5: Nhân viên chưa gửi hiển thị đúng trạng thái unregistered
     */
    public function test_unregistered_employee_status()
    {
        Sanctum::actingAs($this->manager);
        $res = $this->getJson("/api/availability/weekly?weekMonday={$this->targetWeekMonday}");

        $emp1Code = 'NV' . str_pad($this->staff1->nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT);
        $employees = collect($res->json('employees'));
        $emp1 = $employees->firstWhere('id', $emp1Code);

        $this->assertNotNull($emp1);
        $this->assertFalse($emp1['registered']);
        $this->assertEmpty($emp1['slots']);
    }

    /**
     * Test 6: Chuyển tuần không bị lẫn dữ liệu
     */
    public function test_different_weeks_isolation()
    {
        // Đăng ký cho tuần target
        Sanctum::actingAs($this->staff1);
        $this->postJson('/api/availability/my', [
            'weekMonday' => $this->targetWeekMonday,
            'slots'      => ['0_Sáng' => 'available'],
        ]);

        // Tuần tiếp theo
        $nextWeek = date('Y-m-d', strtotime('+7 days', strtotime($this->targetWeekMonday)));
        $resNextWeek = $this->getJson("/api/availability/my?weekMonday={$nextWeek}");

        $resNextWeek->assertStatus(200);
        $this->assertEquals('draft', $resNextWeek->json('status'));
        $this->assertEmpty($resNextWeek->json('slots'));
    }

    /**
     * Test 7: Tải lại trang (GET /api/availability/my) vẫn giữ đúng dữ liệu
     */
    public function test_reload_page_restores_saved_slots()
    {
        Sanctum::actingAs($this->staff1);
        $slots = [
            '1_Sáng'  => 'available',
            '3_Chiều' => 'busy',
            '5_Tối'   => 'available',
        ];

        $this->postJson('/api/availability/my', [
            'weekMonday' => $this->targetWeekMonday,
            'slots'      => $slots,
        ]);

        // Giả lập reload trang
        $reloadRes = $this->getJson("/api/availability/my?weekMonday={$this->targetWeekMonday}");
        $reloadRes->assertStatus(200);
        $this->assertEquals('submitted', $reloadRes->json('status'));
        $this->assertEquals('available', $reloadRes->json('slots.1_Sáng'));
        $this->assertEquals('busy', $reloadRes->json('slots.3_Chiều'));
        $this->assertEquals('available', $reloadRes->json('slots.5_Tối'));
    }

    /**
     * Test 8: Quá hạn đăng ký thì chỉ được xem (isPastDeadline, POST bị 400)
     */
    public function test_past_deadline_blocks_submission()
    {
        Sanctum::actingAs($this->staff1);

        // Tuần trong quá khứ chắc chắn đã qua hạn chót
        $pastWeekMonday = '2026-01-05';

        $getRes = $this->getJson("/api/availability/my?weekMonday={$pastWeekMonday}");
        $getRes->assertStatus(200);
        $this->assertTrue($getRes->json('isPastDeadline'));
        $this->assertTrue($getRes->json('isLocked'));

        // Thử gửi -> bị 400 DEADLINE_EXCEEDED
        $postRes = $this->postJson('/api/availability/my', [
            'weekMonday' => $pastWeekMonday,
            'slots'      => ['0_Sáng' => 'available'],
        ]);

        $postRes->assertStatus(400);
        $postRes->assertJsonPath('code', 'DEADLINE_EXCEEDED');
    }

    /**
     * Test 9: Staff không thể xem ma trận của Manager (403) và chỉ lưu cho tài khoản của mình
     */
    public function test_staff_cannot_access_manager_matrix_and_cannot_spoof_id()
    {
        Sanctum::actingAs($this->staff1);

        // Gọi API của Manager -> 403
        $matrixRes = $this->getJson("/api/availability/weekly?weekMonday={$this->targetWeekMonday}");
        $matrixRes->assertStatus(403);

        // Giả mạo nhan_vien_id của Staff 2 khi lưu
        $staff2Id = $this->staff2->nhanVien->nhan_vien_id;
        $this->postJson('/api/availability/my', [
            'weekMonday'   => $this->targetWeekMonday,
            'nhan_vien_id' => $staff2Id, // Thử hack sang ID nhân viên khác
            'slots'        => ['0_Sáng' => 'available'],
        ]);

        // Dữ liệu chỉ được lưu cho Staff 1, Staff 2 không bị ảnh hưởng
        $staff1Id = $this->staff1->nhanVien->nhan_vien_id;
        $this->assertTrue(TuanDangKy::where('nhan_vien_id', $staff1Id)->where('tu_ngay', $this->targetWeekMonday)->exists());
        $this->assertFalse(TuanDangKy::where('nhan_vien_id', $staff2Id)->where('tu_ngay', $this->targetWeekMonday)->exists());
    }

    /**
     * Test 10: Tài khoản MANAGER (NV001) bị loại trừ hoàn toàn khỏi ma trận Khả năng làm việc
     */
    public function test_manager_account_is_excluded_from_availability_matrix()
    {
        Sanctum::actingAs($this->manager);

        $res = $this->getJson("/api/availability/weekly?weekMonday={$this->targetWeekMonday}");
        $res->assertStatus(200);

        $employees = collect($res->json('employees'));
        
        // 1. NV001 (Quản lý) tuyệt đối không xuất hiện
        $mgrEmp = $employees->firstWhere('id', 'NV001');
        $this->assertNull($mgrEmp, 'NV001 (MANAGER) không được xuất hiện trong ma trận khả năng làm việc.');

        // 2. NV002 (Staff) xuất hiện bình thường
        $staffEmp = $employees->firstWhere('id', 'NV002');
        $this->assertNotNull($staffEmp, 'NV002 (STAFF) phải xuất hiện trong danh sách.');
        $this->assertFalse($staffEmp['registered'], 'NV002 phải có trạng thái Chưa gửi lịch tuần này.');

        // 3. 100% nhân viên trong danh sách phải có vai trò STAFF
        foreach ($employees as $e) {
            $this->assertEquals('STAFF', $e['vai_tro']);
        }
    }
}