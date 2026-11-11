<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan;
use App\Models\NhanVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Đăng nhập hệ thống (dùng chung cho Manager và Staff)
     * Hỗ trợ 2 hình thức:
     * 1. Đăng nhập bằng Email (nhận diện qua email hoặc field login chứa @)
     * 2. Đăng nhập bằng Mã nhân viên (VD: NV001, NV018, nv018, NV1000...)
     */
    public function login(Request $request)
    {
        $loginInput = trim($request->input('login') ?? $request->input('email') ?? '');
        $password   = $request->input('password');

        if (empty($loginInput)) {
            return response()->json([
                'status'  => false,
                'message' => 'Vui lòng nhập email hoặc mã nhân viên.',
                'errors'  => [
                    'login' => ['Vui lòng nhập email hoặc mã nhân viên.'],
                ],
            ], 422);
        }

        if (empty($password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Vui lòng nhập mật khẩu.',
                'errors'  => [
                    'password' => ['Vui lòng nhập mật khẩu.'],
                ],
            ], 422);
        }

        $taiKhoan = null;

        // 1. Kiểm tra nếu người dùng nhập Mã nhân viên (VD: NV018, nv018, NV001...)
        if (preg_match('/^NV([0-9]+)$/i', $loginInput, $matches)) {
            $nhanVienId = (int) $matches[1];
            $nhanVien = NhanVien::find($nhanVienId);

            if (!$nhanVien) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Mã nhân viên không tồn tại trong hệ thống.',
                ], 404);
            }

            if (!$nhanVien->tai_khoan_id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nhân viên chưa được liên kết tài khoản đăng nhập.',
                ], 403);
            }

            $taiKhoan = TaiKhoan::with('nhanVien')->find($nhanVien->tai_khoan_id);

            if (!$taiKhoan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không tìm thấy tài khoản tương ứng với nhân viên này.',
                ], 404);
            }
        } else {
            // 2. Người dùng nhập Email
            $taiKhoan = TaiKhoan::with('nhanVien')->where('email', $loginInput)->first();

            if (!$taiKhoan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Email không tồn tại trong hệ thống.',
                ], 404);
            }
        }

        // 3. Kiểm tra mật khẩu bằng Hash::check
        if (!Hash::check($password, $taiKhoan->mat_khau)) {
            return response()->json([
                'status'  => false,
                'message' => 'Mật khẩu không chính xác.',
            ], 401);
        }

        // 4. Kiểm tra trạng thái tài khoản
        if ($taiKhoan->trang_thai === 'LOCKED') {
            return response()->json([
                'status'  => false,
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản lý.',
            ], 403);
        }

        // 5. Kiểm tra trạng thái nhân viên nếu có hồ sơ liên kết
        if ($taiKhoan->nhanVien && $taiKhoan->nhanVien->trang_thai === 'INACTIVE') {
            return response()->json([
                'status'  => false,
                'message' => 'Nhân viên đã nghỉ việc. Không thể đăng nhập vào hệ thống.',
            ], 403);
        }

        // 6. Tạo Sanctum Bearer Token
        $token = $taiKhoan->createToken('token_auth')->plainTextToken;
        $role = strtoupper($taiKhoan->vai_tro) === 'MANAGER' ? 'manager' : 'staff';
        $nhanVien = $taiKhoan->nhanVien;

        $employeeCode = $nhanVien ? 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT) : null;
        $displayName = $nhanVien ? $nhanVien->ho_ten : ($role === 'manager' ? 'Quản lý' : 'Nhân viên');

        $userData = [
            'id'            => $employeeCode ?? ('USR' . $taiKhoan->tai_khoan_id),
            'tai_khoan_id'  => $taiKhoan->tai_khoan_id,
            'nhan_vien_id'  => $nhanVien ? $nhanVien->nhan_vien_id : null,
            'employeeId'    => $employeeCode,
            'employeeCode'  => $employeeCode,
            'name'          => $displayName,
            'ho_ten'        => $displayName,
            'email'         => $taiKhoan->email,
            'role'          => $role,
            'vai_tro'       => $taiKhoan->vai_tro,
            'status'        => $taiKhoan->trang_thai,
            'mustChangePassword' => (bool)$taiKhoan->must_change_password,
            'must_change_password' => (bool)$taiKhoan->must_change_password,
        ];

        return response()->json([
            'status'  => true,
            'message' => 'Đăng nhập thành công',
            'token'   => $token,
            'user'    => $userData,
            'data'    => [
                'token' => $token,
                'user'  => $userData,
            ],
        ]);
    }

    /**
     * Lấy thông tin user hiện tại qua token Sanctum
     */
    public function me(Request $request)
    {
        $taiKhoan = $request->user();

        if (!$taiKhoan) {
            return response()->json([
                'status'  => false,
                'message' => 'Chưa đăng nhập hoặc phiên làm việc đã hết hạn.',
            ], 401);
        }

        $taiKhoan->loadMissing('nhanVien');

        if ($taiKhoan->trang_thai === 'LOCKED') {
            if ($taiKhoan->currentAccessToken()) {
                $taiKhoan->currentAccessToken()->delete();
            }
            return response()->json([
                'status'  => false,
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản lý.',
            ], 403);
        }

        if ($taiKhoan->nhanVien && $taiKhoan->nhanVien->trang_thai === 'INACTIVE') {
            if ($taiKhoan->currentAccessToken()) {
                $taiKhoan->currentAccessToken()->delete();
            }
            return response()->json([
                'status'  => false,
                'message' => 'Nhân viên đã nghỉ việc. Không thể truy cập hệ thống.',
            ], 403);
        }

        $role = strtoupper($taiKhoan->vai_tro) === 'MANAGER' ? 'manager' : 'staff';
        $nhanVien = $taiKhoan->nhanVien;
        $employeeCode = $nhanVien ? 'NV' . str_pad($nhanVien->nhan_vien_id, 3, '0', STR_PAD_LEFT) : null;
        $displayName = $nhanVien ? $nhanVien->ho_ten : ($role === 'manager' ? 'Quản lý' : 'Nhân viên');

        $userData = [
            'id'            => $employeeCode ?? ('USR' . $taiKhoan->tai_khoan_id),
            'tai_khoan_id'  => $taiKhoan->tai_khoan_id,
            'nhan_vien_id'  => $nhanVien ? $nhanVien->nhan_vien_id : null,
            'employeeId'    => $employeeCode,
            'employeeCode'  => $employeeCode,
            'name'          => $displayName,
            'ho_ten'        => $displayName,
            'email'         => $taiKhoan->email,
            'role'          => $role,
            'vai_tro'       => $taiKhoan->vai_tro,
            'status'        => $taiKhoan->trang_thai,
            'mustChangePassword' => (bool)$taiKhoan->must_change_password,
            'must_change_password' => (bool)$taiKhoan->must_change_password,
        ];

        return response()->json([
            'status' => true,
            'user'   => $userData,
            'data'   => [
                'user' => $userData,
            ],
        ]);
    }

    /**
     * Đổi mật khẩu cho người dùng hiện tại (US02)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Chưa đăng nhập hoặc phiên làm việc đã hết hạn.',
            ], 401);
        }

        $currentPass = $request->input('current_password') ?? $request->input('currentPass') ?? $request->input('currentPassword');
        $newPass = $request->input('new_password') ?? $request->input('newPass') ?? $request->input('newPassword');
        $confirmPass = $request->input('confirm_password') ?? $request->input('confirmPass') ?? $request->input('confirmPassword') ?? $request->input('new_password_confirmation');

        if (empty($currentPass)) {
            return response()->json([
                'status'  => false,
                'message' => 'Vui lòng nhập mật khẩu hiện tại.',
                'field'   => 'currentPassword',
            ], 422);
        }

        if (empty($newPass)) {
            return response()->json([
                'status'  => false,
                'message' => 'Vui lòng nhập mật khẩu mới.',
                'field'   => 'newPassword',
            ], 422);
        }

        if (strlen($newPass) < 8 || !preg_match('/[A-Za-z]/', $newPass) || !preg_match('/\d/', $newPass)) {
            return response()->json([
                'status'  => false,
                'message' => 'Mật khẩu mới phải có tối thiểu 8 ký tự, bao gồm cả chữ và số.',
                'field'   => 'newPassword',
            ], 422);
        }

        if ($newPass === $currentPass) {
            return response()->json([
                'status'  => false,
                'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
                'field'   => 'newPassword',
            ], 422);
        }

        if (!empty($confirmPass) && $newPass !== $confirmPass) {
            return response()->json([
                'status'  => false,
                'message' => 'Mật khẩu xác nhận không khớp.',
                'field'   => 'confirmPassword',
            ], 422);
        }

        if (!Hash::check($currentPass, $user->mat_khau)) {
            return response()->json([
                'status'  => false,
                'message' => 'Mật khẩu hiện tại không đúng.',
                'field'   => 'currentPassword',
            ], 422);
        }

        $user->mat_khau = Hash::make($newPass);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    /**
     * Đăng xuất - Thu hồi token hiện tại
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Đăng xuất thành công',
        ]);
    }
}
