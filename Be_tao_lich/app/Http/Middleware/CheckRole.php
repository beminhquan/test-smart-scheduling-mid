<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Chưa đăng nhập hoặc phiên làm việc đã hết hạn.',
            ], 401);
        }

        // Kiểm tra trạng thái tài khoản
        if ($user->trang_thai === 'LOCKED') {
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            return response()->json([
                'status'  => false,
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản lý.',
            ], 403);
        }

        if (empty($roles)) {
            return $next($request);
        }

        $userRoleUpper = strtoupper($user->vai_tro ?? '');
        $allowedRolesUpper = array_map('strtoupper', $roles);

        if (!in_array($userRoleUpper, $allowedRolesUpper, true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Bạn không có quyền truy cập chức năng này.',
            ], 403);
        }

        return $next($request);
    }
}
