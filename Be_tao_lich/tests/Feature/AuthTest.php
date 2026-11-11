<?php

namespace Tests\Feature;

use App\Models\TaiKhoan;
use Database\Seeders\TaiKhoanSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RoleTagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
    }

    public function test_login_success_and_logout()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'token',
            'user' => ['id', 'email', 'name', 'ho_ten', 'role', 'vai_tro'],
        ]);

        $data = $response->json();
        $token = $data['token'];
        $this->assertEquals('manager', $data['user']['role']);
        $this->assertEquals('MANAGER', $data['user']['vai_tro']);

        // Test GET /api/user with Sanctum token
        $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
        
        $userResponse->assertStatus(200);
        $userResponse->assertJsonPath('user.email', 'manager@example.com');

        $this->assertEquals(1, \Laravel\Sanctum\PersonalAccessToken::count());

        // Test Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200);

        // Verify token deleted in database
        $this->assertEquals(0, \Laravel\Sanctum\PersonalAccessToken::count());

        // Clear cached guard in test runner
        app('auth')->forgetGuards();

        $afterLogoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $afterLogoutResponse->assertStatus(401);
    }

    public function test_login_wrong_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
    }

    public function test_login_locked_account()
    {
        // Khóa tài khoản
        TaiKhoan::where('email', 'manager@example.com')->update(['trang_thai' => 'LOCKED']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString('bị khóa', $response->json('message'));
    }

    public function test_staff_cannot_access_manager_routes()
    {
        // Login as Staff (nhanvien1@example.com)
        $loginRes = $this->postJson('/api/auth/login', [
            'email' => 'nhanvien1@example.com',
            'password' => 'Password@123',
        ]);

        $loginRes->assertStatus(200);
        $this->assertEquals('staff', $loginRes->json('user.role'));
        $token = $loginRes->json('token');

        // Staff tries to access GET /api/employees (Manager-only)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/employees');

        $response->assertStatus(403);
        $this->assertStringContainsString('không có quyền', $response->json('message'));
    }

    public function test_manager_can_access_manager_routes()
    {
        // Login as Manager
        $loginRes = $this->postJson('/api/auth/login', [
            'email' => 'manager@example.com',
            'password' => 'Password@123',
        ]);

        $loginRes->assertStatus(200);
        $token = $loginRes->json('token');

        // Manager accesses GET /api/employees
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/employees');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
    }

    public function test_unauthenticated_request_to_protected_routes()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);

        $empResponse = $this->getJson('/api/employees');
        $empResponse->assertStatus(401);
    }

    public function test_invalid_token_request()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-12345')
            ->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_login_with_employee_code_uppercase()
    {
        $response = $this->postJson('/api/auth/login', [
            'login'    => 'NV002',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('user.role', 'staff');
        $response->assertJsonPath('user.employeeCode', 'NV002');
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_employee_code_lowercase()
    {
        $response = $this->postJson('/api/auth/login', [
            'login'    => 'nv002',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('user.role', 'staff');
        $response->assertJsonPath('user.employeeCode', 'NV002');
    }

    public function test_login_with_non_existent_employee_code()
    {
        $response = $this->postJson('/api/auth/login', [
            'login'    => 'NV999',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString('không tồn tại', $response->json('message'));
    }

    public function test_login_with_non_existent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'login'    => 'notfound@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString('không tồn tại', $response->json('message'));
    }

    public function test_login_inactive_employee()
    {
        \App\Models\NhanVien::where('nhan_vien_id', 2)->update(['trang_thai' => 'INACTIVE']);

        $response = $this->postJson('/api/auth/login', [
            'login'    => 'NV002',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString('nghỉ việc', $response->json('message'));
    }
}
