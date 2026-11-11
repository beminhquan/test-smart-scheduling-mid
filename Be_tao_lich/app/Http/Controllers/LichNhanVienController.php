<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LichNhanVienService;

class LichNhanVienController extends Controller
{
    protected LichNhanVienService $lichNhanVienService;

    public function __construct(LichNhanVienService $lichNhanVienService)
    {
        $this->lichNhanVienService = $lichNhanVienService;
    }

    /**
     * Lấy lịch làm việc cá nhân của nhân viên đăng nhập (Staff - US13)
     * Endpoint: GET /api/staff/schedule/my hoặc /api/schedule/my
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLichCuaToi(Request $request)
    {
        try {
            $taiKhoan = $request->user();
            if (!$taiKhoan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chưa đăng nhập hoặc phiên làm việc đã hết hạn.',
                ], 401);
            }

            $weekMonday = $request->query('weekMonday') 
                ?? $request->query('week_monday') 
                ?? $request->query('week_start')
                ?? $request->query('weekStart');

            $result = $this->lichNhanVienService->getLichCuaToi($taiKhoan, $weekMonday);

            if (!$result['success']) {
                return response()->json([
                    'status'  => false,
                    'message' => $result['error'] ?? 'Không thể tải lịch cá nhân.',
                ], $result['statusCode'] ?? 400);
            }

            return response()->json($result['data'], $result['statusCode'] ?? 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải lịch làm việc cá nhân: ' . $e->getMessage(),
            ], 500);
        }
    }
}

