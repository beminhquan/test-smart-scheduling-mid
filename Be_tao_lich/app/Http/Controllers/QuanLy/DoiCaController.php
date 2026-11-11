<?php

namespace App\Http\Controllers\QuanLy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\YeuCauDoiCa;
use App\Services\DoiCaService;

class DoiCaController extends Controller
{
    protected DoiCaService $doiCaService;

    public function __construct(DoiCaService $doiCaService)
    {
        $this->doiCaService = $doiCaService;
    }

    /**
     * 1. GET /api/manager/shift-exchange (Manager xem danh sách tất cả yêu cầu đổi ca)
     */
    public function index(Request $request)
    {
        try {
            $query = YeuCauDoiCa::with([
                'nguoiGui.roleTag',
                'nguoiGui.taiKhoan',
                'nguoiNhan.roleTag',
                'nguoiNhan.taiKhoan',
                'caGui.caMau',
                'caGui.nhanVien.roleTag',
                'caNhan.caMau',
                'caNhan.nhanVien.roleTag',
            ])
            ->orderBy('doi_ca_id', 'desc');

            // Lọc theo trạng thái nếu có
            $statusParam = $request->query('status');
            if ($statusParam && $statusParam !== 'all') {
                if ($statusParam === 'pending_manager') {
                    $query->whereIn('trang_thai', ['PENDING_MANAGER', 'CHO_QUAN_LY_DUYET', 'WAITING_MANAGER', 'PENDING']);
                } elseif ($statusParam === 'pending_peer') {
                    $query->whereIn('trang_thai', ['PENDING_PEER', 'CHO_DONG_NGHIEP', 'WAITING_PEER']);
                } elseif ($statusParam === 'approved') {
                    $query->whereIn('trang_thai', ['APPROVED', 'DA_DUYET']);
                } elseif ($statusParam === 'rejected') {
                    $query->whereIn('trang_thai', ['REJECTED', 'DECLINED', 'CANCELLED', 'PEER_REJECTED', 'TU_CHOI', 'DA_HUY']);
                }
            }

            $items = $query->get();
            $formatted = $items->map(fn($item) => $this->doiCaService->formatExchangeRequest($item));

            return response()->json([
                'status' => true,
                'data'   => $formatted,
                'items'  => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải danh sách yêu cầu đổi ca cho Quản lý: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. GET /api/manager/shift-exchange/{id} (Xem chi tiết yêu cầu đổi ca)
     */
    public function show(Request $request, $id)
    {
        try {
            $rawId = (int)preg_replace('/\D/', '', (string)$id);
            $req = YeuCauDoiCa::with([
                'nguoiGui.roleTag',
                'nguoiGui.taiKhoan',
                'nguoiNhan.roleTag',
                'nguoiNhan.taiKhoan',
                'caGui.caMau',
                'caGui.nhanVien.roleTag',
                'caNhan.caMau',
                'caNhan.nhanVien.roleTag',
            ])->find($rawId);

            if (!$req) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy yêu cầu đổi ca.'], 404);
            }

            $formatted = $this->doiCaService->formatExchangeRequest($req);

            // Kiểm tra điều kiện thực tế
            $checkResults = [];
            if ($req->caGui && $req->nguoiNhan) {
                [$valid, $reason] = $this->doiCaService->kiemTraDieuKienDoiCa($req->caGui, $req->nguoiNhan, $req->caNhan);
                $checkResults = [
                    'valid'  => $valid,
                    'reason' => $reason,
                ];
            }

            return response()->json([
                'status'  => true,
                'data'    => $formatted,
                'checks'  => $checkResults,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi xem chi tiết yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. POST /api/manager/shift-exchange/{id}/approve (Quản lý phê duyệt và cập nhật lịch làm việc)
     */
    public function approve(Request $request, $id = null)
    {
        try {
            $targetId = $id ?? $request->input('id') ?? $request->input('doi_ca_id');
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);

            $req = YeuCauDoiCa::with(['caGui.caMau', 'caNhan.caMau', 'nguoiGui', 'nguoiNhan'])->find($rawId);
            if (!$req) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy yêu cầu đổi ca.'], 404);
            }

            $user = $request->user();
            $managerTaiKhoanId = $user ? $user->tai_khoan_id : 1;
            $managerNote = trim($request->input('managerNote') ?? $request->input('note') ?? '');

            [$success, $error] = $this->doiCaService->pheDuyetYeuCau($req, $managerTaiKhoanId, $managerNote);
            if (!$success) {
                return response()->json([
                    'status'  => false,
                    'message' => $error,
                ], 422);
            }

            $req->refresh();
            $req->load(['nguoiGui.roleTag', 'nguoiNhan.roleTag', 'caGui.caMau', 'caNhan.caMau']);
            $formatted = $this->doiCaService->formatExchangeRequest($req);

            return response()->json([
                'status'  => true,
                'message' => 'Đã phê duyệt yêu cầu đổi ca và cập nhật lịch làm việc thành công.',
                'data'    => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi phê duyệt yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. POST /api/manager/shift-exchange/{id}/reject (Quản lý từ chối yêu cầu đổi ca)
     */
    public function reject(Request $request, $id = null)
    {
        try {
            $targetId = $id ?? $request->input('id') ?? $request->input('doi_ca_id');
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);

            $req = YeuCauDoiCa::find($rawId);
            if (!$req) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy yêu cầu đổi ca.'], 404);
            }

            $user = $request->user();
            $managerTaiKhoanId = $user ? $user->tai_khoan_id : 1;
            $rejectReason = trim($request->input('rejectReason') ?? $request->input('reason') ?? 'Quản lý từ chối yêu cầu');

            [$success, $error] = $this->doiCaService->tuChoiYeuCau($req, $managerTaiKhoanId, $rejectReason);
            if (!$success) {
                return response()->json([
                    'status'  => false,
                    'message' => $error,
                ], 422);
            }

            $req->refresh();
            $req->load(['nguoiGui.roleTag', 'nguoiNhan.roleTag', 'caGui.caMau', 'caNhan.caMau']);
            $formatted = $this->doiCaService->formatExchangeRequest($req);

            return response()->json([
                'status'  => true,
                'message' => 'Đã từ chối yêu cầu đổi ca.',
                'data'    => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi từ chối yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }
}

