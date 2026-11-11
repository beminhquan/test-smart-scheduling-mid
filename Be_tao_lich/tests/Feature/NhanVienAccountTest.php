<?php

namespace Tests\Feature;

use App\Models\NhanVien;
use App\Models\TaiKhoan;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NhanVienAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
    }

    protected function getManagerToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'manager@example.com',
            'password' => 'Password@123',
        ]);
        return $response->json('token');
    }

    public function test_grant_account_without_name_succeeds_using_existing_employee_profile()
    {
        $token = $this->getManagerToken();

        // Tạo một nhân viên chưa có tài khoản
        $nhanVien = NhanVien::create([
            'ho_ten'        => 'Bảo Lê',
            'so_dien_thoai' => '0988888888',
            'ngay_sinh'     => '2000-10-14',
            'loai_hop_dong' => 'PART_TIME',
            'muc_luong_gio' => 28000,
            'role_tag_id'   => 4,
            'tai_khoan_id'  => null,
            'trang_thai'    => 'ACTIVE',
        ]);

        $grantPayload = [
            'nhan_vien_id' => 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT),
            'id'           => 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT),
            'is_grant'     => true,
            'email'        => 'baole@example.com',
            'password'     => 'Password@123',
            'account'      => [
                'email'                    => 'baole@example.com',
                'password'                 => 'Password@123',
                'status'                   => 'active',
                'changePasswordFirstLogin' => true,
            ],
        ];

        // Gửi request cấp tài khoản (không truyền ho_ten hay name)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/nhan-vien/update', $grantPayload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.name', 'Bảo Lê');
        $response->assertJsonPath('data.account.email', 'baole@example.com');

        // Xác minh tài khoản được tạo trong database
        $nhanVien->refresh();
        $this->assertNotNull($nhanVien->tai_khoan_id);

        $taiKhoan = TaiKhoan::find($nhanVien->tai_khoan_id);
        $this->assertNotNull($taiKhoan);
        $this->assertEquals('baole@example.com', $taiKhoan->email);
        $this->assertEquals('STAFF', $taiKhoan->vai_tro);
        $this->assertEquals('ACTIVE', $taiKhoan->trang_thai);
        $this->assertTrue(Hash::check('Password@123', $taiKhoan->mat_khau));

        // Xác minh nhân viên đăng nhập thành công bằng tài khoản vừa cấp
        $loginRes = $this->postJson('/api/auth/login', [
            'email'    => 'baole@example.com',
            'password' => 'Password@123',
        ]);
        $loginRes->assertStatus(200);
        $loginRes->assertJsonPath('status', true);
        $loginRes->assertJsonPath('user.email', 'baole@example.com');
        $loginRes->assertJsonPath('user.role', 'staff');
    }

    public function test_grant_account_fails_if_employee_already_has_account()
    {
        $token = $this->getManagerToken();

        // Nhân viên số 2 đã có tài khoản từ seeder
        $nhanVien = NhanVien::find(2);
        $this->assertNotNull($nhanVien->tai_khoan_id);

        $grantPayload = [
            'nhan_vien_id' => 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT),
            'id'           => 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT),
            'is_grant'     => true,
            'email'        => 'newemail@example.com',
            'password'     => 'Password@123',
            'account'      => [
                'email'    => 'newemail@example.com',
                'password' => 'Password@123',
                'status'   => 'active',
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/nhan-vien/update', $grantPayload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Nhân viên này đã có tài khoản trong hệ thống.');
    }

    public function test_grant_account_fails_if_email_is_already_taken()
    {
        $token = $this->getManagerToken();

        $nhanVien = NhanVien::create([
            'ho_ten'        => 'Bảo Lê 2',
            'so_dien_thoai' => '0988888889',
            'loai_hop_dong' => 'PART_TIME',
            'muc_luong_gio' => 28000,
            'role_tag_id'   => 4,
            'tai_khoan_id'  => null,
            'trang_thai'    => 'ACTIVE',
        ]);

        // Email manager@example.com đã tồn tại
        $grantPayload = [
            'nhan_vien_id' => $nhanVien->nhan_vien_id,
            'is_grant'     => true,
            'email'        => 'manager@example.com',
            'password'     => 'Password@123',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/nhan-vien/update', $grantPayload);

        $response->assertStatus(422);
        $this->assertStringContainsString('đã được dùng', $response->json('message'));
    }

    public function test_update_employee_fails_if_ho_ten_is_explicitly_blank()
    {
        $token = $this->getManagerToken();

        $nhanVien = NhanVien::find(2);

        $updatePayload = [
            'nhan_vien_id' => $nhanVien->nhan_vien_id,
            'ho_ten'       => '',
            'so_dien_thoai'=> '0901234567',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/nhan-vien/update', $updatePayload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Họ và tên không được để trống.');
    }
}
