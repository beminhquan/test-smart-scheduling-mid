<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NhanVienController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaMauController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\XepLichController;
use App\Http\Controllers\CaTrongController;
use App\Http\Controllers\LichNhanVienController;
use App\Http\Controllers\NhanVien\DoiCaController as NhanVienDoiCaController;
use App\Http\Controllers\QuanLy\DoiCaController as QuanLyDoiCaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. CÁC ROUTE CÔNG KHAI (Không cần Token)
// ==========================================
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'login']);

// ==========================================
// 2. CÁC ROUTE YÊU CẦU XÁC THỰC (Sanctum Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    // Thông tin người dùng hiện tại
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Đổi mật khẩu cho tài khoản đăng nhập (US02)
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Đăng xuất thu hồi token
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // =========================================================================
    // CÁC ROUTE NGHIỆP VỤ (Yêu cầu tài khoản đã hoàn thành đổi mật khẩu lần đầu)
    // =========================================================================
    Route::middleware('must_change_password')->group(function () {
        // ==========================================
        // 3. ĐĂNG KÝ KHẢ NĂNG LÀM VIỆC & LỊCH CỦA NHÂN VIÊN (Staff)
        // ==========================================
        Route::get('/availability/my', [AvailabilityController::class, 'getMyAvailability']);
        Route::post('/availability/my', [AvailabilityController::class, 'saveMyAvailability']);

        // --- LỊCH CỦA TÔI (LichNhanVienController - US13) ---
        Route::prefix('staff/schedule')->group(function () {
            Route::get('/my', [LichNhanVienController::class, 'getLichCuaToi']);
            Route::get('/weekly', [LichNhanVienController::class, 'getLichCuaToi']);
        });
        Route::get('/schedule/my', [LichNhanVienController::class, 'getLichCuaToi']);
        Route::get('/schedule/my-schedule', [LichNhanVienController::class, 'getLichCuaToi']);
        Route::get('/xep-lich/ca-nhan', [LichNhanVienController::class, 'getLichCuaToi']);

        // Ca trực mở cho nhân viên xem và đăng ký nhận ca
        Route::get('/employees/open-shifts', [CaTrongController::class, 'getForEmployee']);
        Route::post('/employees/open-shifts/{id?}/claim', [CaTrongController::class, 'claim']);
        Route::post('/employees/open-shifts/claim', [CaTrongController::class, 'claim']);
        Route::post('/open-shifts/{id?}/claim', [CaTrongController::class, 'claim']);
        Route::post('/open-shifts/claim', [CaTrongController::class, 'claim']);

        // ==========================================
        // 3.C. ĐỔI CA LÀM VIỆC (Staff - US15, US16, US17)
        // ==========================================
        Route::prefix('staff/shift-exchange')->group(function () {
            Route::get('/', [NhanVienDoiCaController::class, 'index']);
            Route::get('/candidates', [NhanVienDoiCaController::class, 'getCandidates']);
            Route::post('/', [NhanVienDoiCaController::class, 'store']);
            Route::post('/{id}/respond', [NhanVienDoiCaController::class, 'respond']);
            Route::post('/{id}/cancel', [NhanVienDoiCaController::class, 'cancel']);
        });
        Route::get('/shift-exchange', [NhanVienDoiCaController::class, 'index']);
        Route::get('/shift-exchange/candidates', [NhanVienDoiCaController::class, 'getCandidates']);
        Route::post('/shift-exchange', [NhanVienDoiCaController::class, 'store']);
        Route::post('/shift-exchange/{id?}/respond', [NhanVienDoiCaController::class, 'respond']);
        Route::post('/shift-exchange/{id?}/cancel', [NhanVienDoiCaController::class, 'cancel']);
        Route::post('/shift-exchange/respond', [NhanVienDoiCaController::class, 'respond']);
        Route::post('/shift-exchange/cancel', [NhanVienDoiCaController::class, 'cancel']);

        // ==========================================
        // 4. CÁC ROUTE NGHIỆP VỤ CHO MANAGER 
        // ==========================================
        Route::middleware('role:MANAGER')->group(function () {
            // --- A. QUẢN LÝ NHÂN VIÊN ---
            // Chuẩn Action-based
            Route::prefix('admin')->group(function () {
                Route::get('/nhan-vien/next-code', [NhanVienController::class, 'getNextEmployeeCode']);
                Route::get('/nhan-vien/data', [NhanVienController::class, 'getDataNhanVien']);
                Route::post('/nhan-vien/search', [NhanVienController::class, 'searchNhanVien']);
                Route::post('/nhan-vien/create', [NhanVienController::class, 'storeNhanVien']);
                Route::post('/nhan-vien/delete', [NhanVienController::class, 'destroyNhanVien']);
                Route::post('/nhan-vien/update', [NhanVienController::class, 'updateNhanVien']);
                Route::post('/nhan-vien/change-status', [NhanVienController::class, 'changeStatusNhanVien']);
                Route::post('/nhan-vien/grant-account', [NhanVienController::class, 'grantAccountNhanVien']);
                Route::post('/nhan-vien/reset-password', [NhanVienController::class, 'resetPasswordNhanVien']);
                Route::get('/nhan-vien/future-shifts/{id}', [NhanVienController::class, 'getFutureShifts']);
                Route::get('/role-tag/data', [NhanVienController::class, 'getDataRoleTag']);

                // Quản lý Ca mẫu (Chuẩn Action-based)
                Route::get('/ca-mau/data', [CaMauController::class, 'index']);
                Route::post('/ca-mau/search', [CaMauController::class, 'index']);
                Route::post('/ca-mau/create', [CaMauController::class, 'store']);
                Route::get('/ca-mau/detail/{id}', [CaMauController::class, 'show']);
                Route::post('/ca-mau/update', [CaMauController::class, 'update']);
                Route::post('/ca-mau/change-status', [CaMauController::class, 'changeStatus']);
                Route::post('/ca-mau/delete', [CaMauController::class, 'destroy']);
            });

            // Chuẩn RESTful cho Nhân viên
            Route::get('/employees/next-code', [NhanVienController::class, 'getNextEmployeeCode']);
            Route::get('/employees', [NhanVienController::class, 'getDataNhanVien']);
            Route::post('/employees', [NhanVienController::class, 'storeNhanVien']);
            Route::put('/employees/{id}', [NhanVienController::class, 'updateNhanVien']);
            Route::patch('/employees/{id}/lock', [NhanVienController::class, 'changeStatusNhanVien']);
            Route::post('/employees/{id}/grant-account', [NhanVienController::class, 'grantAccountNhanVien']);
            Route::post('/employees/{id}/reset-password', [NhanVienController::class, 'resetPasswordNhanVien']);
            Route::delete('/employees/{id}', [NhanVienController::class, 'destroyNhanVien']);

        // --- B. QUẢN LÝ DANH MỤC CA (Chuẩn RESTful) ---
        Route::get('/shifts', [CaMauController::class, 'index']);
        Route::post('/shifts', [CaMauController::class, 'store']);
        Route::get('/shifts/{id}', [CaMauController::class, 'show']);
        Route::put('/shifts/{id}', [CaMauController::class, 'update']);
        Route::patch('/shifts/{id}', [CaMauController::class, 'update']);
        Route::patch('/shifts/{id}/status', [CaMauController::class, 'changeStatus']);
        Route::delete('/shifts/{id}', [CaMauController::class, 'destroy']);

        // Alias tương đương cho /ca-mau
        Route::get('/ca-mau', [CaMauController::class, 'index']);
        Route::post('/ca-mau', [CaMauController::class, 'store']);
        Route::get('/ca-mau/{id}', [CaMauController::class, 'show']);
        Route::put('/ca-mau/{id}', [CaMauController::class, 'update']);
        Route::delete('/ca-mau/{id}', [CaMauController::class, 'destroy']);

        // --- C. HỖ TRỢ TƯƠNG THÍCH MÀN HÌNH "CA & RÀNG BUỘC" (/shift-config) ---
        Route::get('/shift-config', [CaMauController::class, 'getShiftConfig']);
        Route::put('/shift-config', [CaMauController::class, 'saveShiftConfig']);
        Route::post('/shift-config', [CaMauController::class, 'saveShiftConfig']);

        // --- D. QUẢN LÝ DANH MỤC VAI TRÒ (Role_Tag) ---
        Route::get('/role-tags', [CaMauController::class, 'getRoleTags']);
        Route::post('/role-tags', [CaMauController::class, 'storeRoleTag']);
        Route::put('/role-tags/{id?}', [CaMauController::class, 'updateRoleTag']);
        Route::delete('/role-tags/{id?}', [CaMauController::class, 'destroyRoleTag']);

        // --- E. TỔNG HỢP KHẢ NĂNG LÀM VIỆC (Manager) ---
        Route::get('/availability/weekly', [AvailabilityController::class, 'getWeeklyStaffAvailability']);

        // --- F. CẤU HÌNH ĐẦU VÀO TẠO LỊCH & NHU CẦU NHÂN SỰ ---
        Route::get('/schedule/input-config', [ScheduleController::class, 'getInputConfig']);
        Route::get('/schedule/demand', [ScheduleController::class, 'getDemand']);
        Route::post('/schedule/demand', [ScheduleController::class, 'saveDemand']);
        Route::put('/schedule/demand', [ScheduleController::class, 'saveDemand']);

        // --- G. TỰ ĐỘNG XẾP LỊCH BẰNG GOOGLE OR-TOOLS (PYTHON SCHEDULING SERVICE) ---
        Route::post('/schedule/auto-generate', [XepLichController::class, 'taoLichTuDong']);
        Route::post('/xep-lich/tao-tu-dong', [XepLichController::class, 'taoLichTuDong']);
        Route::get('/schedule/weekly', [XepLichController::class, 'layLichTheoTuan']);
        Route::get('/xep-lich/theo-tuan', [XepLichController::class, 'layLichTheoTuan']);
        Route::post('/schedule/confirm', [XepLichController::class, 'xacNhanLich']);
        Route::post('/xep-lich/xac-nhan', [XepLichController::class, 'xacNhanLich']);

        // --- H. QUẢN LÝ CA TRỰC MỞ (Manager) ---
        Route::get('/open-shifts', [CaTrongController::class, 'index']);
        Route::get('/open-shifts/sources', [CaTrongController::class, 'getSources']);
        Route::post('/open-shifts', [CaTrongController::class, 'store']);
        Route::put('/open-shifts/{id?}', [CaTrongController::class, 'update']);
        Route::delete('/open-shifts/{id?}', [CaTrongController::class, 'cancel']);
        Route::post('/open-shifts/{id?}/cancel', [CaTrongController::class, 'cancel']);
        Route::post('/open-shifts/cancel', [CaTrongController::class, 'cancel']);
        Route::post('/open-shifts/{id?}/approve', [CaTrongController::class, 'approve']);
        Route::post('/open-shifts/approve', [CaTrongController::class, 'approve']);
        Route::post('/open-shifts/{id?}/reject', [CaTrongController::class, 'reject']);
        Route::post('/open-shifts/reject', [CaTrongController::class, 'reject']);

        // --- I. QUẢN LÝ ĐỔI CA (Manager - US15, US16, US17) ---
        Route::prefix('manager/shift-exchange')->group(function () {
            Route::get('/', [QuanLyDoiCaController::class, 'index']);
            Route::get('/{id}', [QuanLyDoiCaController::class, 'show']);
            Route::post('/{id}/approve', [QuanLyDoiCaController::class, 'approve']);
            Route::post('/approve', [QuanLyDoiCaController::class, 'approve']);
            Route::post('/{id}/reject', [QuanLyDoiCaController::class, 'reject']);
            Route::post('/reject', [QuanLyDoiCaController::class, 'reject']);
        });
        Route::get('/admin/shift-exchange', [QuanLyDoiCaController::class, 'index']);
        Route::get('/admin/shift-exchange/{id}', [QuanLyDoiCaController::class, 'show']);
        Route::post('/admin/shift-exchange/{id?}/approve', [QuanLyDoiCaController::class, 'approve']);
        Route::post('/admin/shift-exchange/approve', [QuanLyDoiCaController::class, 'approve']);
        Route::post('/admin/shift-exchange/{id?}/reject', [QuanLyDoiCaController::class, 'reject']);
        Route::post('/admin/shift-exchange/reject', [QuanLyDoiCaController::class, 'reject']);
    });
    });
});
