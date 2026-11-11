<?php

namespace App\Http\Controllers\NhanVien;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\YeuCauDoiCa;
use App\Models\PhanCongCa;
use App\Models\NhanVien;
use App\Services\DoiCaService;

class DoiCaController extends Controller
{
    protected DoiCaService $doiCaService;

    public function __construct(DoiCaService $doiCaService)
    {
        $this->doiCaService = $doiCaService;
    }

    /**
     * Xác định hồ sơ nhân viên từ tài khoản đang đăng nhập
     */
    private function resolveNhanVien(Request $request): ?NhanVien
    {
        $user = $request->user();
        if (!$user) return null;
        return $user->nhanVien ?? NhanVien::where('tai_khoan_id', $user->tai_khoan_id)->first();
    }

    /**
     * 1. GET /api/staff/shift-exchange (Xem danh sách yêu cầu của tôi & gửi đến tôi)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if ($user && $user->vai_tro === 'MANAGER') {
                $managerCtrl = app(\App\Http\Controllers\QuanLy\DoiCaController::class);
                return $managerCtrl->index($request);
            }

            $nhanVien = $this->resolveNhanVien($request);
            if (!$nhanVien) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy hồ sơ nhân viên.'], 404);
            }

            $nhanVienId = $nhanVien->nhan_vien_id;

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
            ->where(function ($q) use ($nhanVienId) {
                $q->where('nguoi_gui_id', $nhanVienId)
                  ->orWhere('nguoi_nhan_id', $nhanVienId);
            })
            ->orderBy('doi_ca_id', 'desc');

            $items = $query->get();

            $formattedAll = [];
            $sent = [];
            $received = [];

            foreach ($items as $item) {
                $f = $this->doiCaService->formatExchangeRequest($item);
                $formattedAll[] = $f;
                if ($item->nguoi_gui_id == $nhanVienId) {
                    $sent[] = $f;
                }
                if ($item->nguoi_nhan_id == $nhanVienId) {
                    $received[] = $f;
                }
            }

            return response()->json([
                'status'   => true,
                'data'     => [
                    'sent'     => $sent,
                    'received' => $received,
                ],
                'sent'     => $sent,
                'received' => $received,
                'items'    => $formattedAll,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải danh sách yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. GET /api/staff/shift-exchange/candidates (Lấy danh sách đồng nghiệp rảnh ca, đủ điều kiện)
     */
    public function getCandidates(Request $request)
    {
        try {
            $nhanVien = $this->resolveNhanVien($request);
            if (!$nhanVien) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy hồ sơ nhân viên.'], 404);
            }

            $rawShiftId = $request->query('shift_id') ?? $request->query('shiftId');
            if (!$rawShiftId) {
                return response()->json(['status' => false, 'message' => 'Vui lòng cung cấp mã ca muốn đổi.'], 400);
            }

            $pcId = (int)preg_replace('/\D/', '', (string)$rawShiftId);
            $caGui = PhanCongCa::with(['caMau', 'nhanVien'])->find($pcId);

            if (!$caGui) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy ca làm việc.'], 404);
            }

            $candidates = $this->doiCaService->getEligibleCandidates($caGui, $nhanVien->nhan_vien_id);

            return response()->json([
                'status'     => true,
                'data'       => $candidates,
                'candidates' => $candidates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tải danh sách ứng viên đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. POST /api/staff/shift-exchange (Tạo yêu cầu đổi ca mới)
     */
    public function store(Request $request)
    {
        try {
            $nhanVien = $this->resolveNhanVien($request);
            if (!$nhanVien) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy hồ sơ nhân viên.'], 404);
            }

            // Trích xuất mã ca gửi
            $rawShiftId = $request->input('shift_id') ?? $request->input('shift.id') ?? $request->input('ca_gui_id');
            $caGuiId = (int)preg_replace('/\D/', '', (string)$rawShiftId);

            // Trích xuất mã đồng nghiệp nhận ca
            $rawRecipientId = $request->input('recipient_id') 
                ?? $request->input('targetEmployeeId') 
                ?? $request->input('nguoi_nhan_id');
            $recipientId = (int)preg_replace('/\D/', '', (string)$rawRecipientId);

            if (!$caGuiId || !$recipientId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Vui lòng chọn ca làm việc cần đổi và đồng nghiệp nhận ca.',
                ], 422);
            }

            if ($recipientId == $nhanVien->nhan_vien_id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không thể gửi yêu cầu đổi ca cho chính mình.',
                ], 422);
            }

            $caGui = PhanCongCa::with(['caMau', 'nhanVien'])->find($caGuiId);
            if (!$caGui) {
                return response()->json(['status' => false, 'message' => 'Ca làm việc không tồn tại.'], 404);
            }

            if ($caGui->nhan_vien_id != $nhanVien->nhan_vien_id) {
                return response()->json(['status' => false, 'message' => 'Ca làm việc này không thuộc phân công của bạn.'], 403);
            }

            $nguoiNhan = NhanVien::find($recipientId);
            if (!$nguoiNhan) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy thông tin đồng nghiệp nhận ca.'], 404);
            }

            // Kiểm tra điều kiện đổi ca
            [$valid, $error] = $this->doiCaService->kiemTraDieuKienDoiCa($caGui, $nguoiNhan);
            if (!$valid) {
                return response()->json(['status' => false, 'message' => $error], 422);
            }

            // Kiểm tra xem đã có yêu cầu nào đang chờ cho ca này chưa
            $existsPending = YeuCauDoiCa::where('ca_gui_id', $caGuiId)
                ->whereIn('trang_thai', ['PENDING_PEER', 'PENDING_MANAGER', 'WAITING_PEER', 'WAITING_MANAGER'])
                ->exists();

            if ($existsPending) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ca làm việc này đang có một yêu cầu đổi ca khác chờ xử lý.',
                ], 422);
            }

            $note = trim($request->input('reason') ?? $request->input('note') ?? '');

            $req = YeuCauDoiCa::create([
                'nguoi_gui_id'       => $nhanVien->nhan_vien_id,
                'nguoi_nhan_id'      => $recipientId,
                'ca_gui_id'          => $caGuiId,
                'ca_nhan_id'         => 0, // 0 = Trực thay ca (1-way cover)
                'ly_do'              => $note,
                'trang_thai'         => 'PENDING_PEER',
                'thoi_gian_phan_hoi' => null,
                'nguoi_duyet_id'     => null,
                'thoi_gian_duyet'    => null,
            ]);

            $req->load(['nguoiGui.roleTag', 'nguoiNhan.roleTag', 'caGui.caMau']);
            $formatted = $this->doiCaService->formatExchangeRequest($req);

            return response()->json([
                'status'  => true,
                'message' => 'Đã gửi yêu cầu đổi ca thành công. Đang chờ đồng nghiệp phản hồi.',
                'data'    => $formatted,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi tạo yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. POST /api/staff/shift-exchange/{id}/respond (Đồng nghiệp phản hồi: Đồng ý hoặc Từ chối)
     */
    public function respond(Request $request, $id = null)
    {
        try {
            $nhanVien = $this->resolveNhanVien($request);
            if (!$nhanVien) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy hồ sơ nhân viên.'], 404);
            }

            $targetId = $id ?? $request->input('id') ?? $request->input('doi_ca_id');
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);

            $req = YeuCauDoiCa::with(['caGui.caMau', 'nguoiGui', 'nguoiNhan'])->find($rawId);
            if (!$req) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy yêu cầu đổi ca.'], 404);
            }

            if ($req->nguoi_nhan_id != $nhanVien->nhan_vien_id) {
                return response()->json(['status' => false, 'message' => 'Bạn không phải là người nhận của yêu cầu này.'], 403);
            }

            $currentStatus = $this->doiCaService->normalizeStatus($req->trang_thai);
            if ($currentStatus !== 'pending_peer') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Yêu cầu này không còn ở trạng thái chờ bạn phản hồi (Trạng thái: ' . $req->trang_thai . ').',
                ], 422);
            }

            $action = strtolower($request->input('action') ?? $request->input('status') ?? 'accept');

            if ($action === 'accept' || $action === 'accepted') {
                // Kiểm tra lại điều kiện tại thời điểm đồng ý
                if ($req->caGui) {
                    [$valid, $error] = $this->doiCaService->kiemTraDieuKienDoiCa($req->caGui, $nhanVien);
                    if (!$valid) {
                        return response()->json(['status' => false, 'message' => $error], 422);
                    }
                }

                // QUY TRÌNH BẮT BUỘC: Khi đồng ý, yêu cầu chuyển sang PENDING_MANAGER (chờ Quản lý duyệt), KHÔNG tự ý đổi lịch
                $req->trang_thai = 'PENDING_MANAGER';
                $req->thoi_gian_phan_hoi = Carbon::now()->format('d/m/Y H:i');
                $req->save();

                $formatted = $this->doiCaService->formatExchangeRequest($req);
                return response()->json([
                    'status'  => true,
                    'message' => 'Đã chấp nhận yêu cầu đổi ca. Yêu cầu đã được chuyển tới Quản lý để phê duyệt.',
                    'data'    => $formatted,
                ]);
            } else {
                // Đồng nghiệp từ chối
                $declineReason = trim($request->input('reason') ?? $request->input('declineReason') ?? 'Đồng nghiệp từ chối');
                $req->trang_thai = 'DECLINED';
                $req->thoi_gian_phan_hoi = $declineReason ?: Carbon::now()->format('d/m/Y H:i');
                $req->save();

                $formatted = $this->doiCaService->formatExchangeRequest($req);
                return response()->json([
                    'status'  => true,
                    'message' => 'Đã từ chối yêu cầu đổi ca.',
                    'data'    => $formatted,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi phản hồi yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. POST /api/staff/shift-exchange/{id}/cancel (Người gửi hủy yêu cầu đang chờ)
     */
    public function cancel(Request $request, $id = null)
    {
        try {
            $nhanVien = $this->resolveNhanVien($request);
            if (!$nhanVien) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy hồ sơ nhân viên.'], 404);
            }

            $targetId = $id ?? $request->input('id') ?? $request->input('doi_ca_id');
            $rawId = (int)preg_replace('/\D/', '', (string)$targetId);

            $req = YeuCauDoiCa::find($rawId);
            if (!$req) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy yêu cầu đổi ca.'], 404);
            }

            if ($req->nguoi_gui_id != $nhanVien->nhan_vien_id) {
                return response()->json(['status' => false, 'message' => 'Chỉ người gửi mới có quyền hủy yêu cầu này.'], 403);
            }

            $currentStatus = $this->doiCaService->normalizeStatus($req->trang_thai);
            if (!in_array($currentStatus, ['pending_peer', 'pending_manager'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Chỉ có thể hủy yêu cầu khi đang ở trạng thái chờ xử lý.',
                ], 422);
            }

            $req->trang_thai = 'CANCELLED';
            $req->save();

            $formatted = $this->doiCaService->formatExchangeRequest($req);
            return response()->json([
                'status'  => true,
                'message' => 'Đã hủy yêu cầu đổi ca thành công.',
                'data'    => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi hủy yêu cầu đổi ca: ' . $e->getMessage(),
            ], 500);
        }
    }
}