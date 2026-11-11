<?php

namespace Tests\Feature;

use App\Models\CaMau;
use App\Models\NhanVien;
use App\Models\RangBuoc;
use App\Models\RoleTag;
use App\Models\TaiKhoan;
use Database\Seeders\CaMauSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RangBuocSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShiftConfigTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
        $this->seed(CaMauSeeder::class);
        $this->seed(RangBuocSeeder::class);

        $this->manager = TaiKhoan::where('vai_tro', 'MANAGER')->first();
        $this->staff = TaiKhoan::where('vai_tro', 'STAFF')->first();
    }

    /**
     * Test 1: Phân quyền RBAC - Staff bị chặn 403, Manager được truy cập 200
     */
    public function test_rbac_authorization()
    {
        // Staff truy cập shift-config -> 403
        Sanctum::actingAs($this->staff);
        $res = $this->getJson('/api/shift-config');
        $res->assertStatus(403);

        // Staff gọi role-tags -> 403
        $resRole = $this->postJson('/api/role-tags', ['name' => 'Hack Role']);
        $resRole->assertStatus(403);

        // Manager truy cập shift-config -> 200
        Sanctum::actingAs($this->manager);
        $resMgr = $this->getJson('/api/shift-config');
        $resMgr->assertStatus(200);
        $resMgr->assertJsonStructure([
            'status',
            'shifts',
            'constraints',
            'generalSettings',
        ]);
    }

    /**
     * Test 2: Tab 1 - CRUD Ca mẫu và tính năng an toàn dữ liệu
     */
    public function test_shift_templates_crud_and_safety()
    {
        Sanctum::actingAs($this->manager);

        // A. Tạo ca mẫu mới
        $createRes = $this->postJson('/api/shifts', [
            'name'       => 'Ca Đêm',
            'startTime'  => '22:00',
            'endTime'    => '06:00',
            'multiplier' => 1.3,
            'status'     => 'active',
        ]);
        $this->assertContains($createRes->status(), [200, 201]);
        $shiftId = $createRes->json('data.id') ?? $createRes->json('data.ca_mau_id');
        $this->assertNotNull($shiftId);

        // B. Cập nhật ca mẫu
        $updateRes = $this->putJson("/api/shifts/{$shiftId}", [
            'name'       => 'Ca Đêm Cập Nhật',
            'startTime'  => '22:00',
            'endTime'    => '06:00',
            'multiplier' => 1.4,
            'status'     => 'active',
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals(1.4, (float)$updateRes->json('data.multiplier'));

        // C. Xóa ca mẫu chưa dùng
        $delRes = $this->deleteJson("/api/shifts/{$shiftId}");
        $delRes->assertStatus(200);
        $this->assertNull(CaMau::find($shiftId));
    }

    /**
     * Test 3: Tab 2 - CRUD Role_Tag & kiểm tra ràng buộc khóa ngoại nhân viên
     */
    public function test_role_tags_crud_and_foreign_key_protection()
    {
        Sanctum::actingAs($this->manager);

        // A. Thêm role đã tồn tại -> 422
        $dupRes = $this->postJson('/api/role-tags', ['name' => 'Pha chế']);
        $dupRes->assertStatus(422);

        // B. Thêm role mới -> 200
        $addRes = $this->postJson('/api/role-tags', ['name' => 'Bảo vệ']);
        $addRes->assertStatus(200);
        $this->assertTrue(RoleTag::where('ten_role', 'Bảo vệ')->exists());

        // C. Đổi tên role mới -> 200
        $updRes = $this->putJson('/api/role-tags', [
            'oldRole' => 'Bảo vệ',
            'newRole' => 'An ninh',
        ]);
        $updRes->assertStatus(200);
        $this->assertTrue(RoleTag::where('ten_role', 'An ninh')->exists());

        // D. Xóa role đang gán cho nhân viên -> 422
        $usedRole = RoleTag::whereHas('nhanViens')->first();
        if ($usedRole) {
            $delUsedRes = $this->deleteJson('/api/role-tags', ['roleName' => $usedRole->ten_role]);
            $delUsedRes->assertStatus(422);
        }

        // E. Xóa role mới không có nhân viên -> 200
        $delNewRes = $this->deleteJson('/api/role-tags', ['roleName' => 'An ninh']);
        $delNewRes->assertStatus(200);
        $this->assertFalse(RoleTag::where('ten_role', 'An ninh')->exists());
    }

    /**
     * Test 4: Tab 3 - Bật/tắt 11 ràng buộc (cho phép tắt cả Hard constraints và lưu MySQL)
     */
    public function test_scheduling_constraints_toggle_and_persist()
    {
        Sanctum::actingAs($this->manager);

        // Lấy cấu hình hiện tại
        $initRes = $this->getJson('/api/shift-config');
        $constraints = $initRes->json('constraints');
        $this->assertCount(11, $constraints);

        // Tắt 1 Hard constraint và sửa 1 Soft constraint
        $updated = array_map(function ($c) {
            if ($c['id'] === 'rest_between_shifts') {
                $c['enabled'] = false; // Tắt Hard constraint
            }
            if ($c['id'] === 'max_hours_per_day') {
                $c['value'] = 9;
            }
            if ($c['id'] === 'fair_hours_distribution') {
                $c['weight'] = 2.5;
            }
            return $c;
        }, $constraints);

        $saveRes = $this->putJson('/api/shift-config', ['constraints' => $updated]);
        $saveRes->assertStatus(200);

        // Verify lại bằng GET
        $verifyRes = $this->getJson('/api/shift-config');
        $verifyConstraints = collect($verifyRes->json('constraints'));

        $restConstraint = $verifyConstraints->firstWhere('id', 'rest_between_shifts');
        $this->assertNotNull($restConstraint);
        $this->assertFalse($restConstraint['enabled']); // Hard constraint đã tắt thành công

        $maxHours = $verifyConstraints->firstWhere('id', 'max_hours_per_day');
        $this->assertNotNull($maxHours);
        $this->assertEquals(9, $maxHours['value']);
    }

    /**
     * Test 5: Tab 4 - Cấu hình chung (Hạn chót, Ân hạn chấm công, Danh mục ngày lễ)
     */
    public function test_general_settings_crud_and_holidays()
    {
        Sanctum::actingAs($this->manager);

        $newGeneral = [
            'availabilityDeadlineDay'  => 'thursday',
            'availabilityDeadlineTime' => '21:30',
            'gracePeriodMin'           => 15,
            'holidayMultiplier'        => 1.5,
            'holidays'                 => [
                ['id' => 'h_custom', 'date' => '2026-11-20', 'name' => 'Ngày Nhà giáo Việt Nam', 'multiplier' => 2.0],
            ],
        ];

        $saveRes = $this->putJson('/api/shift-config', ['generalSettings' => $newGeneral]);
        $saveRes->assertStatus(200);

        // Verify lại
        $verifyRes = $this->getJson('/api/shift-config');
        $general = $verifyRes->json('generalSettings');

        $this->assertEquals('thursday', $general['availabilityDeadlineDay']);
        $this->assertEquals('21:30', $general['availabilityDeadlineTime']);
        $this->assertEquals(15, $general['gracePeriodMin']);
        $this->assertCount(1, $general['holidays']);
        $this->assertEquals('Ngày Nhà giáo Việt Nam', $general['holidays'][0]['name']);
        $this->assertEquals(2.0, (float)$general['holidays'][0]['multiplier']);

        // Validation test: gracePeriodMin > 30 -> 422
        $invalidRes = $this->putJson('/api/shift-config', [
            'generalSettings' => ['gracePeriodMin' => 45],
        ]);
        $invalidRes->assertStatus(422);
    }
}
