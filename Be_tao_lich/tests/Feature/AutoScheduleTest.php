<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\TaiKhoan;
use App\Models\NhanVien;
use App\Models\RangBuoc;
use App\Models\CaMau;
use App\Models\RoleTag;
use App\Models\NhuCauCa;
use App\Models\LichLamViec;
use App\Models\PhanCongCa;
use Database\Seeders\CaMauSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RangBuocSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class AutoScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $managerUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
        $this->seed(CaMauSeeder::class);
        $this->seed(RangBuocSeeder::class);

        // Lấy tài khoản MANAGER và STAFF
        $this->managerUser = TaiKhoan::where('vai_tro', 'MANAGER')->first();
        $this->staffUser = TaiKhoan::where('vai_tro', 'STAFF')->first();
    }

    /**
     * Helper tạo nhu cầu mẫu cho tuần để Solver có yêu cầu xếp ca
     */
    protected function taoNhuCauMau(string $weekMonday)
    {
        $mon = Carbon::parse($weekMonday);
        $shifts = CaMau::where('trang_thai', 'ACTIVE')->get();
        $roles = RoleTag::all();

        for ($d = 0; $d < 7; $d++) {
            $date = $mon->copy()->addDays($d)->format('Y-m-d');
            foreach ($shifts as $shift) {
                // Mỗi ca cần 1 Phục vụ, 1 Pha chế
                foreach ($roles as $role) {
                    if ($role->ten_role !== 'Quản lý') {
                        NhuCauCa::create([
                            'ngay_lam'     => $date,
                            'ca_mau_id'    => $shift->ca_mau_id,
                            'role_tag_id'  => $role->role_tag_id,
                            'so_luong_can' => 1,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Test 1: Manager tạo lịch tự động thành công (trạng thái DRAFT, lưu lich_lam_viecs và phan_cong_cas)
     */
    public function test_manager_can_auto_generate_schedule()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $this->taoNhuCauMau($weekMonday);

        $response = $this->postJson('/api/schedule/auto-generate', [
            'weekMonday' => $weekMonday,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'     => true,
                'trang_thai' => 'DRAFT',
                'weekMonday' => $weekMonday,
            ])
            ->assertJsonStructure([
                'status',
                'solver_status',
                'lich_id',
                'trang_thai',
                'weekMonday',
                'weekSunday',
                'assignments_count',
                'assignments',
                'statistics',
                'solve_time',
            ]);

        // Kiểm tra database: bảng lich_lam_viecs có trạng thái DRAFT
        $this->assertDatabaseHas('lich_lam_viecs', [
            'tu_ngay'    => '2026-11-09',
            'den_ngay'   => '2026-11-15',
            'trang_thai' => 'DRAFT',
            'phien_ban'  => 1,
        ]);

        // Kiểm tra bảng phan_cong_cas có bản ghi
        $lich = LichLamViec::where('tu_ngay', '2026-11-09')->first();
        $this->assertNotNull($lich);
        $this->assertGreaterThan(0, PhanCongCa::where('lich_id', $lich->lich_id)->count());
    }

    /**
     * Test 2: Staff không có quyền tạo lịch (bị chặn 403 Forbidden)
     */
    public function test_staff_cannot_auto_generate_schedule()
    {
        Sanctum::actingAs($this->staffUser);

        $response = $this->postJson('/api/schedule/auto-generate', [
            'weekMonday' => '2026-11-09',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 3: Tạo lại lịch khi đã có bản nháp DRAFT sẽ cập nhật bản nháp cũ, tăng phien_ban và không nhân bản ca
     */
    public function test_regenerating_draft_schedule_updates_existing_draft()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $this->taoNhuCauMau($weekMonday);

        // Lần 1
        $res1 = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $weekMonday]);
        $res1->assertStatus(200);

        $lich1 = LichLamViec::where('tu_ngay', '2026-11-09')->first();
        $this->assertEquals(1, $lich1->phien_ban);
        $count1 = PhanCongCa::where('lich_id', $lich1->lich_id)->count();

        // Lần 2: Tạo lại
        $res2 = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $weekMonday]);
        $res2->assertStatus(200);

        // Chỉ có 1 bản ghi lịch cho tuần đó
        $totalLich = LichLamViec::where('tu_ngay', '2026-11-09')->count();
        $this->assertEquals(1, $totalLich);

        $lich2 = LichLamViec::where('tu_ngay', '2026-11-09')->first();
        $this->assertEquals(2, $lich2->phien_ban);

        // Số lượng phân công vẫn tương đương, không bị x2
        $count2 = PhanCongCa::where('lich_id', $lich2->lich_id)->count();
        $this->assertEquals($count1, $count2);
    }

    /**
     * Test 4: Không được tự ý ghi đè nếu lịch tuần đã được xác nhận (CONFIRMED)
     */
    public function test_cannot_overwrite_confirmed_schedule()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $this->taoNhuCauMau($weekMonday);

        // Tạo lịch lần 1
        $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $weekMonday]);

        // Xác nhận lịch
        $this->postJson('/api/schedule/confirm', ['weekMonday' => $weekMonday]);

        $this->assertDatabaseHas('lich_lam_viecs', [
            'tu_ngay'    => '2026-11-09',
            'trang_thai' => 'CONFIRMED',
        ]);

        // Thử tạo lại -> Báo lỗi 400
        $response = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $weekMonday]);
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
            ]);
    }

    /**
     * Test 5: Xem lịch tuần (GET /api/schedule/weekly) trả về cấu trúc ma trận ca
     */
    public function test_can_get_weekly_schedule()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-09';
        $this->taoNhuCauMau($weekMonday);

        // Tạo lịch
        $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $weekMonday]);

        $response = $this->getJson('/api/schedule/weekly?weekMonday=' . $weekMonday);

        $response->assertStatus(200)
            ->assertJson([
                'status'       => true,
                'has_schedule' => true,
                'trang_thai'   => 'DRAFT',
            ])
            ->assertJsonStructure([
                'status',
                'has_schedule',
                'lich_id',
                'trang_thai',
                'phien_ban',
                'weekMonday',
                'weekSunday',
                'total_assignments',
                'schedules',
            ]);
    }

    /**
     * Test 6: Alias tiếng Việt /api/xep-lich/tao-tu-dong hoạt động đồng bộ
     */
    public function test_vietnamese_alias_endpoint()
    {
        Sanctum::actingAs($this->managerUser);

        $weekMonday = '2026-11-16';
        $this->taoNhuCauMau($weekMonday);

        $response = $this->postJson('/api/xep-lich/tao-tu-dong', [
            'weekMonday' => $weekMonday,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);
    }
}
