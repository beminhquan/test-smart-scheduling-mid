<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMustChangePassword
{
    /**
     * Chặn truy cập các chức năng nghiệp vụ khi tài khoản bắt buộc đổi mật khẩu lần đầu (US02)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !empty($user->must_change_password)) {
            // Cho phép đổi mật khẩu, xem thông tin tài khoản và đăng xuất
            if (
                $request->is('api/auth/change-password') ||
                $request->is('api/change-password') ||
                $request->is('api/auth/me') ||
                $request->is('api/user') ||
                $request->is('api/auth/logout') ||
                $request->is('api/logout')
            ) {
                return $next($request);
            }

            return response()->json([
                'status'  => false,
                'code'    => 'MUST_CHANGE_PASSWORD',
                'message' => 'Tài khoản bắt buộc phải đổi mật khẩu lần đầu trước khi sử dụng các chức năng hệ thống.',
            ], 403);
        }

        return $next($request);
    }
}

