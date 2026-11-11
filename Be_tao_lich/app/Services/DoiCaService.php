<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\YeuCauDoiCa;
use App\Models\PhanCongCa;
use App\Models\NhanVien;
use App\Models\CaMau;
use App\Models\TaiKhoan;

class DoiCaService
{
    /**
     * Map tên ca sang mã shiftId (morning, afternoon, evening)
     */
    public function getShiftCode($tenCa, $gioBatDau = null): string
    {
        $h = $gioBatDau ? (int)substr($gioBatDau, 0, 2) : 6;
        if (stripos($tenCa, 'chiều') !== false || ($h >= 12 && $h < 17)) {
            return 'afternoon';
        }
        if (stripos($tenCa, 'tối') !== false || $h >= 17) {
            return 'evening';
        }
        return 'morning';
    }

    /**
     * Lấy mã Thứ trong tuần (T2, T3, T4, T5, T6, T7, CN)
     */
    public function getDayId(Carbon $date): string
    {
        $dayOfWeek = $date->dayOfWeekIso; // 1 = Monday, 7 = Sunday
        $map = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];
        return $map[$dayOfWeek] ?? 'T2';
    }

    /**
     * Format ngày thành chuỗi tiếng Việt chuẩn: "Thứ 6, 30/10/2026"
     */
    public function formatVietnameseDate(Carbon $date): string
    {
        $dayOfWeek = $date->dayOfWeekIso;
        $dayNames = [
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            7 => 'Chủ nhật',
        ];
        $prefix = $dayNames[$dayOfWeek] ?? 'Thứ 2';
        return "{$prefix}, {$date->format('d/m/Y')}";
    }

    /**
     * Format đối tượng PhanCongCa thành cấu trúc shift object mà frontend React yêu cầu
     */
    public function formatShift(?PhanCongCa $pc): ?array
    {
        if (!$pc) return null;

        $date = Carbon::parse($pc->ngay_lam);
        $cm = $pc->caMau;
        $startTime = $cm ? substr($cm->gio_bat_dau, 0, 5) : '06:00';
        $endTime = $cm ? substr($cm->gio_ket_thuc, 0, 5) : '12:00';
        $tenCa = $cm ? $cm->ten_ca : 'Ca làm việc';
        $shiftId = $this->getShiftCode($tenCa, $startTime);
        $roleName = $pc->nhanVien?->roleTag?->ten_role ?? 'Phục vụ';

        return [
            'id'              => 'shift_' . $pc->phan_cong_ca_id,
            'phan_cong_ca_id' => $pc->phan_cong_ca_id,
            'date'            => $this->formatVietnameseDate($date),
            'isoDate'         => $date->format('Y-m-d'),
            'dayId'           => $this->getDayId($date),
            'shiftId'         => $shiftId,
            'shiftName'       => $tenCa,
            'time'            => "{$startTime} - {$endTime}",
            'startTime'       => $startTime,
            'endTime'         => $endTime,
            'roleTag'         => $roleName,
        ];
    }

    /**
     * Chuẩn hóa trạng thái nội bộ sang trạng thái frontend kỳ vọng
     */
    public function normalizeStatus(string $rawStatus): string
    {
        $status = strtoupper(trim($rawStatus));
        return match ($status) {
            'PENDING_PEER', 'CHO_DONG_NGHIEP', 'WAITING_PEER', 'PENDING_EMPLOYEE' => 'pending_peer',
            'PENDING_MANAGER', 'CHO_QUAN_LY_DUYET', 'WAITING_MANAGER', 'PENDING'   => 'pending_manager',
            'APPROVED', 'DA_DUYET'                                                 => 'approved',
            'REJECTED', 'TU_CHOI', 'BI_TU_CHOI'                                   => 'rejected',
            'DECLINED', 'PEER_REJECTED', 'DONG_NGHIEP_TU_CHOI'                   => 'declined',
            'CANCELLED', 'DA_HUY'                                                  => 'cancelled',
            default                                                                => strtolower($rawStatus),
        };
    }

    /**
     * Format bản ghi YeuCauDoiCa thành JSON theo đúng định dạng các component React kỳ vọng
     */
    public function formatExchangeRequest(YeuCauDoiCa $req): array
    {
        $nguoiGui = $req->nguoiGui;
        $nguoiNhan = $req->nguoiNhan;

        $senderCode = $nguoiGui ? ('NV' . str_pad($nguoiGui->nhan_vien_id, 3, '0', STR_PAD_LEFT)) : 'N/A';
        $recipientCode = $nguoiNhan ? ('NV' . str_pad($nguoiNhan->nhan_vien_id, 3, '0', STR_PAD_LEFT)) : 'N/A';

        $senderRole = $nguoiGui?->roleTag?->ten_role ?? 'Phục vụ';
        $recipientRole = $nguoiNhan?->roleTag?->ten_role ?? 'Phục vụ';

        $reqShift = $this->formatShift($req->caGui);
        $targetShift = ($req->ca_nhan_id && $req->ca_nhan_id > 0) ? $this->formatShift($req->caNhan) : null;
        $exchangeType = $targetShift ? 'swap' : 'cover';

        $statusNormalized = $this->normalizeStatus($req->trang_thai);
        $createdAtStr = $req->created_at ? $req->created_at->format('d/m/Y H:i') : ($req->thoi_gian_duyet ?? 'N/A');

        return [
            'id'                     => 'EX-' . str_pad($req->doi_ca_id, 3, '0', STR_PAD_LEFT),
            'doi_ca_id'              => $req->doi_ca_id,
            // Thông tin người xin đổi (Requester / Sender)
            'requesterId'            => $senderCode,
            'requesterName'          => $nguoiGui?->ho_ten ?? ('Nhân viên #' . $req->nguoi_gui_id),
            'requesterRole'          => $senderRole,
            'requesterRoleTag'       => $senderRole,
            'sender_id'              => $senderCode,
            'sender_nhan_vien_id'    => $req->nguoi_gui_id,
            'sender_name'            => $nguoiGui?->ho_ten ?? '',
            'sender_email'           => $nguoiGui?->taiKhoan?->email ?? '',
            'requesterShift'         => $reqShift,
            'shift'                  => $reqShift,
            // Thông tin người nhận đổi (Recipient / Target)
            'targetEmployeeId'       => $recipientCode,
            'targetEmployeeName'     => $nguoiNhan?->ho_ten ?? ('Nhân viên #' . $req->nguoi_nhan_id),
            'targetEmployeeRole'     => $recipientRole,
            'targetEmployeeRoleTag'  => $recipientRole,
            'targetRoleTag'          => $recipientRole,
            'recipient_id'           => $recipientCode,
            'recipient_nhan_vien_id' => $req->nguoi_nhan_id,
            'recipient_name'         => $nguoiNhan?->ho_ten ?? '',
            'recipient_email'        => $nguoiNhan?->taiKhoan?->email ?? '',
            'targetShift'            => $targetShift,
            // Nội dung & Trạng thái
            'exchangeType'           => $exchangeType,
            'reason'                 => $req->ly_do ?? '',
            'note'                   => $req->ly_do ?? '',
            'status'                 => $statusNormalized,
            'raw_status'             => $req->trang_thai,
            'createdAt'              => $createdAtStr,
            'sent_at'                => $createdAtStr,
            'peerRespondedAt'        => $req->thoi_gian_phan_hoi,
            'approvedAt'             => $req->thoi_gian_duyet ? Carbon::parse($req->thoi_gian_duyet)->format('d/m/Y H:i') : null,
            'managerNote'            => $statusNormalized === 'approved' ? 'Đã duyệt' : ($req->thoi_gian_duyet ? 'Đã xử lý' : ''),
            'rejectReason'           => $statusNormalized === 'rejected' || $statusNormalized === 'declined' ? ($req->thoi_gian_phan_hoi ?? $req->ly_do ?? '') : null,
        ];
    }

    /**
     * Kiểm tra toàn diện các điều kiện nghiệp vụ đổi ca
     *
     * @param PhanCongCa $caGui Ca làm của người gửi
     * @param NhanVien $nguoiNhan Người nhận ca
     * @param PhanCongCa|null $caNhan Ca làm của người nhận (nếu có hoán đổi 2 chiều)
     * @return array [bool $valid, string|null $error]
     */
    public function kiemTraDieuKienDoiCa(PhanCongCa $caGui, NhanVien $nguoiNhan, ?PhanCongCa $caNhan = null): array
    {
        $now = Carbon::now();
        $cmGui = $caGui->caMau;
        $ngayLamGui = Carbon::parse($caGui->ngay_lam);
        $gioBatDauGui = $cmGui ? substr($cmGui->gio_bat_dau, 0, 5) : '06:00';
        $dtBatDauGui = Carbon::parse($caGui->ngay_lam . ' ' . $gioBatDauGui);

        // 1. Kiểm tra ca đã diễn ra chưa
        if ($dtBatDauGui->lte($now)) {
            return [false, "Ca làm việc ngày {$caGui->ngay_lam} ({$cmGui?->ten_ca}) đã hoặc đang diễn ra, không thể đổi ca."];
        }

        // 2. Kiểm tra vai trò tương thích
        $nguoiGui = $caGui->nhanVien;
        $vaiTroGuiId = $caGui->nhuCauCa?->role_tag_id ?? $nguoiGui?->role_tag_id;
        $vaiTroNhanId = $nguoiNhan->role_tag_id;

        if ($vaiTroGuiId && $vaiTroNhanId && $vaiTroGuiId !== $vaiTroNhanId) {
            $tenRoleGui = $nguoiGui?->roleTag?->ten_role ?? 'vai trò của ca';
            $tenRoleNhan = $nguoiNhan->roleTag?->ten_role ?? 'vai trò khác';
            return [false, "Đồng nghiệp {$nguoiNhan->ho_ten} có vai trò ({$tenRoleNhan}) không tương thích với vai trò yêu cầu của ca ({$tenRoleGui})."];
        }

        // 3. Kiểm tra trùng ca trong ngày của người nhận
        $trungCaNhan = PhanCongCa::where('nhan_vien_id', $nguoiNhan->nhan_vien_id)
            ->where('ngay_lam', $caGui->ngay_lam)
            ->where('phan_cong_ca_id', '!=', $caNhan?->phan_cong_ca_id ?? 0)
            ->exists();

        if ($trungCaNhan) {
            return [false, "Đồng nghiệp {$nguoiNhan->ho_ten} đã có ca làm việc khác trong ngày {$caGui->ngay_lam}."];
        }

        // 4. Kiểm tra thời gian nghỉ tối thiểu (>= 12 giờ)
        // Nếu ca gửi là ca Sáng (06:00 - 12:00), kiểm tra xem người nhận có làm ca Tối hôm trước không
        $sCodeGui = $this->getShiftCode($cmGui?->ten_ca ?? '', $gioBatDauGui);
        if ($sCodeGui === 'morning') {
            $homTruoc = $ngayLamGui->copy()->subDay()->format('Y-m-d');
            $toiHomTruoc = PhanCongCa::where('nhan_vien_id', $nguoiNhan->nhan_vien_id)
                ->where('ngay_lam', $homTruoc)
                ->whereHas('caMau', function ($q) {
                    $q->where('ten_ca', 'like', '%tối%')
                      ->orWhereTime('gio_bat_dau', '>=', '16:00:00');
                })
                ->exists();

            if ($toiHomTruoc) {
                return [false, "Đồng nghiệp {$nguoiNhan->ho_ten} làm ca tối ngày hôm trước ({$homTruoc}), không đảm bảo thời gian nghỉ tối thiểu 12 giờ trước ca sáng ngày {$caGui->ngay_lam}."];
            }
        }

        // Nếu ca gửi là ca Tối, kiểm tra xem người nhận có làm ca Sáng ngày hôm sau không
        if ($sCodeGui === 'evening') {
            $homSau = $ngayLamGui->copy()->addDay()->format('Y-m-d');
            $sangHomSau = PhanCongCa::where('nhan_vien_id', $nguoiNhan->nhan_vien_id)
                ->where('ngay_lam', $homSau)
                ->whereHas('caMau', function ($q) {
                    $q->where('ten_ca', 'like', '%sáng%')
                      ->orWhereTime('gio_bat_dau', '<=', '08:00:00');
                })
                ->exists();

            if ($sangHomSau) {
                return [false, "Đồng nghiệp {$nguoiNhan->ho_ten} có ca sáng ngày hôm sau ({$homSau}), không đảm bảo thời gian nghỉ tối thiểu 12 giờ sau ca tối ngày {$caGui->ngay_lam}."];
            }
        }

        // 5. Nếu có ca của người nhận (Hoán đổi 2 chiều): kiểm tra tương tự cho người gửi
        if ($caNhan) {
            $cmNhan = $caNhan->caMau;
            $ngayLamNhan = Carbon::parse($caNhan->ngay_lam);
            $gioBatDauNhan = $cmNhan ? substr($cmNhan->gio_bat_dau, 0, 5) : '06:00';
            $dtBatDauNhan = Carbon::parse($caNhan->ngay_lam . ' ' . $gioBatDauNhan);

            if ($dtBatDauNhan->lte($now)) {
                return [false, "Ca nhận ngày {$caNhan->ngay_lam} ({$cmNhan?->ten_ca}) đã hoặc đang diễn ra, không thể đổi ca."];
            }

            if ($nguoiGui) {
                $trungCaGui = PhanCongCa::where('nhan_vien_id', $nguoiGui->nhan_vien_id)
                    ->where('ngay_lam', $caNhan->ngay_lam)
                    ->where('phan_cong_ca_id', '!=', $caGui->phan_cong_ca_id)
                    ->exists();

                if ($trungCaGui) {
                    return [false, "Người xin đổi {$nguoiGui->ho_ten} đã có ca làm việc khác trong ngày {$caNhan->ngay_lam}."];
                }
            }
        }

        return [true, null];
    }

    /**
     * Lấy danh sách đồng nghiệp rảnh ca và đủ điều kiện để gợi ý khi tạo yêu cầu đổi ca
     */
    public function getEligibleCandidates(PhanCongCa $caGui, int $excludeNhanVienId): array
    {
        $allActiveStaff = NhanVien::with(['roleTag', 'taiKhoan'])
            ->where('trang_thai', 'ACTIVE')
            ->where('nhan_vien_id', '!=', $excludeNhanVienId)
            ->whereHas('taiKhoan', function ($q) {
                $q->where('vai_tro', 'STAFF');
            })
            ->get();

        $candidates = [];
        foreach ($allActiveStaff as $nv) {
            [$valid, $reason] = $this->kiemTraDieuKienDoiCa($caGui, $nv);
            if ($valid) {
                $empCode = 'NV' . str_pad($nv->nhan_vien_id, 3, '0', STR_PAD_LEFT);
                $rName = $nv->roleTag?->ten_role ?? 'Phục vụ';
                $candidates[] = [
                    'id'          => $empCode,
                    'employeeId'  => $empCode,
                    'nhan_vien_id'=> $nv->nhan_vien_id,
                    'name'        => $nv->ho_ten,
                    'roles'       => [$rName],
                    'roleTag'     => $rName,
                    'email'       => $nv->taiKhoan?->email ?? '',
                    'isEligible'  => true,
                ];
            }
        }

        return $candidates;
    }

    /**
     * Quản lý phê duyệt yêu cầu đổi ca & Cập nhật ma trận phân công bằng Database Transaction
     */
    public function pheDuyetYeuCau(YeuCauDoiCa $req, int $managerTaiKhoanId, ?string $managerNote = null): array
    {
        $statusNorm = $this->normalizeStatus($req->trang_thai);
        if ($statusNorm !== 'pending_manager') {
            return [false, "Yêu cầu đổi ca #{$req->doi_ca_id} hiện không ở trạng thái chờ Quản lý duyệt (Trạng thái hiện tại: {$req->trang_thai})."];
        }

        $caGui = $req->caGui;
        $nguoiGui = $req->nguoiGui;
        $nguoiNhan = $req->nguoiNhan;

        if (!$caGui) {
            return [false, "Ca làm việc gốc của người gửi (Mã #{$req->ca_gui_id}) không còn tồn tại trên hệ thống."];
        }
        if (!$nguoiNhan) {
            return [false, "Không tìm thấy hồ sơ người nhận ca trên hệ thống."];
        }

        $caNhan = ($req->ca_nhan_id && $req->ca_nhan_id > 0) ? $req->caNhan : null;

        // Kiểm tra lại toàn bộ điều kiện trước khi commit cập nhật
        [$valid, $error] = $this->kiemTraDieuKienDoiCa($caGui, $nguoiNhan, $caNhan);
        if (!$valid) {
            return [false, $error];
        }

        // BẮT ĐẦU TRANSACTION
        DB::beginTransaction();
        try {
            // 1. Cập nhật phân công ca gửi: chuyển sang cho người nhận
            $caGui->nhan_vien_id = $nguoiNhan->nhan_vien_id;
            $caGui->save();

            // 2. Nếu có ca nhận (hoán đổi 2 chiều): chuyển sang cho người gửi
            if ($caNhan && $nguoiGui) {
                $caNhan->nhan_vien_id = $nguoiGui->nhan_vien_id;
                $caNhan->save();
            }

            // 3. Cập nhật trạng thái yêu cầu đổi ca
            $req->trang_thai = 'APPROVED';
            $req->nguoi_duyet_id = $managerTaiKhoanId;
            $req->thoi_gian_duyet = Carbon::now();
            $req->save();

            DB::commit();

            return [true, null];
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, "Lỗi cập nhật lịch làm việc khi phê duyệt đổi ca: " . $e->getMessage()];
        }
    }

    /**
     * Quản lý từ chối yêu cầu đổi ca
     */
    public function tuChoiYeuCau(YeuCauDoiCa $req, int $managerTaiKhoanId, ?string $rejectReason = null): array
    {
        $statusNorm = $this->normalizeStatus($req->trang_thai);
        if ($statusNorm !== 'pending_manager' && $statusNorm !== 'pending_peer') {
            return [false, "Yêu cầu đổi ca #{$req->doi_ca_id} đã được giải quyết trước đó (Trạng thái hiện tại: {$req->trang_thai})."];
        }

        DB::beginTransaction();
        try {
            $req->trang_thai = 'REJECTED';
            $req->nguoi_duyet_id = $managerTaiKhoanId;
            $req->thoi_gian_duyet = Carbon::now();
            if ($rejectReason) {
                $req->thoi_gian_phan_hoi = $rejectReason;
            }
            $req->save();

            DB::commit();
            return [true, null];
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, "Lỗi cập nhật trạng thái từ chối: " . $e->getMessage()];
        }
    }
}

