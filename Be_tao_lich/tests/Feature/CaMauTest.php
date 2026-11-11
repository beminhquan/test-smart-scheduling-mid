<?php

namespace Tests\Feature;

use App\Models\CaMau;
use App\Models\NhuCauCa;
use App\Models\PhanCongCa;
use Database\Seeders\CaMauSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaMauTest extends TestCase
{
    use RefreshDatabase;

    protected string $managerToken;
    protected string $staffToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
        $this->seed(CaMauSeeder::class);

        // Đăng nhập lấy token Manager
        $mgrLogin = $this->postJson('/api/auth/login', [
            'email'    => 'manager@example.com',
            'password' => 'Password@123',
        ]);
        $this->managerToken = $mgrLogin->json('token');

        // Đăng nhập lấy token Staff
        $staffLogin = $this->postJson('/api/auth/login', [
            'email'    => 'nhanvien1@example.com',
            'password' => 'Password@123',
        ]);
        $this->staffToken = $staffLogin->json('token');
    }

    public function test_get_shift_list_with_pagination_and_search()
    {
        // 1. Lấy danh sách mặc định
        $res = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/shifts?per_page=2&page=1');

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'startTime', 'endTime', 'duration', 'thoi_luong', 'multiplier', 'status', 'is_used']
            ],
            'pagination' => ['total', 'per_page', 'current_page', 'last_page']
        ]);
        $this->assertCount(2, $res->json('data'));
        $this->assertEquals(3, $res->json('pagination.total'));

        // 2. Tìm kiếm theo tên
        $searchRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/shifts?q=sáng');
        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertStringContainsString('sáng', mb_strtolower($searchRes->json('data.0.name')));

        // 3. Lọc theo trạng thái
        $filterRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/shifts?status=paused');
        $filterRes->assertStatus(200);
        $this->assertCount(0, $filterRes->json('data'));
    }

    public function test_get_shift_detail_success_and_not_found()
    {
        // Lấy chi tiết ca ID 1
        $res = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/shifts/1');

        $res->assertStatus(200);
        $res->assertJsonPath('status', true);
        $res->assertJsonPath('data.id', 1);
        $res->assertJsonPath('data.startTime', '06:00');
        $res->assertJsonPath('data.endTime', '12:00');

        // Thử ID không tồn tại
        $res404 = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/shifts/9999');
        $res404->assertStatus(404);
        $res404->assertJsonPath('status', false);
    }

    public function test_create_valid_shift_and_overnight_shift()
    {
        // 1. Tạo ca thường
        $createRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/shifts', [
                'name'       => 'Ca trưa',
                'startTime'  => '11:00',
                'endTime'    => '14:30',
                'multiplier' => 1.2,
                'status'     => 'active',
            ]);

        $createRes->assertStatus(201);
        $createRes->assertJsonPath('status', true);
        $createRes->assertJsonPath('data.name', 'Ca trưa');
        $createRes->assertJsonPath('data.duration', 210); // 3h30 = 210 phút
        $createRes->assertJsonPath('data.isOvernight', false);

        // 2. Tạo ca qua đêm (22:00 đến 06:00 sáng hôm sau)
        $nightRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/shifts', [
                'name'       => 'Ca đêm xuyên sáng',
                'startTime'  => '22:00',
                'endTime'    => '06:00',
                'multiplier' => 1.5,
                'status'     => 'active',
            ]);

        $nightRes->assertStatus(201);
        $nightRes->assertJsonPath('data.duration', 480); // 8 giờ = 480 phút
        $nightRes->assertJsonPath('data.isOvernight', true);
        $this->assertDatabaseHas('ca_maus', ['ten_ca' => 'Ca đêm xuyên sáng', 'he_so_luong' => 1.5]);
    }

    public function test_create_shift_validation_errors()
    {
        // 1. Thiếu tên
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/shifts', [
                'name'      => '',
                'startTime' => '08:00',
                'endTime'   => '12:00',
            ]);
        $res1->assertStatus(422);

        // 2. Trùng tên ca đã có
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/shifts', [
                'name'      => 'Ca sáng ',
                'startTime' => '08:00',
                'endTime'   => '12:00',
            ]);
        $res2->assertStatus(422);
        $this->assertStringContainsString('Đã có ca tên', $res2->json('message'));

        // 3. Giờ kết thúc trùng giờ bắt đầu
        $res3 = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/shifts', [
                'name'      => 'Ca 0 phút',
                'startTime' => '08:00',
                'endTime'   => '08:00',
            ]);
        $res3->assertStatus(422);
        $this->assertStringContainsString('trùng với giờ bắt đầu', $res3->json('message'));

        // 4. Hệ số lương ngoài khoảng [1.0, 3.0]
        $res4 = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/shifts', [
                'name'       => 'Ca lương cao',
                'startTime'  => '08:00',
                'endTime'    => '12:00',
                'multiplier' => 5.0,
            ]);
        $res4->assertStatus(422);
    }

    public function test_update_shift()
    {
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->putJson('/api/shifts/1', [
                'name'       => 'Ca sáng cập nhật',
                'multiplier' => 1.3,
            ]);

        $updateRes->assertStatus(200);
        $updateRes->assertJsonPath('data.name', 'Ca sáng cập nhật');
        $updateRes->assertJsonPath('data.multiplier', 1.3);

        $this->assertDatabaseHas('ca_maus', [
            'ca_mau_id' => 1,
            'ten_ca'    => 'Ca sáng cập nhật',
        ]);
    }

    public function test_change_shift_status()
    {
        $res = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->patchJson('/api/shifts/1/status', [
                'status' => 'paused',
            ]);

        $res->assertStatus(200);
        $res->assertJsonPath('data.status', 'paused');
        $this->assertDatabaseHas('ca_maus', [
            'ca_mau_id'  => 1,
            'trang_thai' => 'PAUSED',
        ]);
    }

    public function test_delete_unused_shift_succeeds()
    {
        // Tạo ca mới không gắn vào lịch
        $newShift = CaMau::create([
            'ten_ca'       => 'Ca tạm',
            'gio_bat_dau'  => '14:00:00',
            'gio_ket_thuc' => '16:00:00',
            'he_so_luong'  => 1.0,
            'trang_thai'   => 'ACTIVE',
        ]);

        $deleteRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->deleteJson('/api/shifts/' . $newShift->ca_mau_id);

        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('ca_maus', ['ca_mau_id' => $newShift->ca_mau_id]);
    }

    public function test_delete_used_shift_is_blocked()
    {
        // Gắn ca ID 1 vào nhu_cau_cas
        NhuCauCa::create([
            'ca_mau_id'    => 1,
            'ngay_lam'     => '2026-10-21',
            'so_luong_can' => 2,
            'role_tag_id'  => 1,
            'ghi_chu'      => 'Test',
        ]);

        $deleteRes = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->deleteJson('/api/shifts/1');

        $deleteRes->assertStatus(409);
        $deleteRes->assertJsonPath('status', false);
        $this->assertStringContainsString('đã được sử dụng', $deleteRes->json('message'));
        $this->assertDatabaseHas('ca_maus', ['ca_mau_id' => 1]);
    }

    public function test_staff_cannot_modify_shifts()
    {
        // Staff truy cập danh sách shifts -> 403
        $getRes = $this->withHeader('Authorization', 'Bearer ' . $this->staffToken)
            ->getJson('/api/shifts');
        $getRes->assertStatus(403);

        // Staff tạo ca -> 403
        $postRes = $this->withHeader('Authorization', 'Bearer ' . $this->staffToken)
            ->postJson('/api/shifts', [
                'name'      => 'Ca của staff',
                'startTime' => '07:00',
                'endTime'   => '11:00',
            ]);
        $postRes->assertStatus(403);
    }

    public function test_unauthenticated_request_is_blocked()
    {
        $res = $this->getJson('/api/shifts');
        $res->assertStatus(401);
    }
}
