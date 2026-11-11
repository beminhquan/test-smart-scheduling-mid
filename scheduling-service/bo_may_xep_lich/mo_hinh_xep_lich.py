"""
Module: mo_hinh_xep_lich.py
Nhiệm vụ: Xây dựng biến quyết định, ràng buộc cứng (Hard), ràng buộc mềm (Soft) và hàm mục tiêu bằng Google OR-Tools CP-SAT
"""

from typing import Dict, List, Tuple, Any, Optional
from ortools.sat.python import cp_model
from .xu_ly_du_lieu import DuLieuDauVao, KiemTraDuLieu, CaMauDauVao, NhanVienDauVao


class MoHinhXepLich:
    """
    Lớp khởi tạo và đóng gói mô hình toán học CP-SAT cho bài toán xếp lịch Smart Scheduling
    """

    def __init__(self, du_lieu: DuLieuDauVao):
        self.du_lieu = du_lieu
        self.model = cp_model.CpModel()

        # Danh sách lọc đối tượng hoạt động
        self.nhan_viens: List[NhanVienDauVao] = [
            nv for nv in du_lieu.employees if nv.status.upper() == "ACTIVE"
        ]
        self.ca_maus: List[CaMauDauVao] = [
            c for c in du_lieu.shifts if c.status.upper() == "ACTIVE"
        ]

        # Ánh xạ ID sang đối tượng
        self.map_nhan_vien = {nv.id: nv for nv in self.nhan_viens}
        self.map_ca_mau = {c.id: c for c in self.ca_maus}
        self.map_vai_tro = {vt.id: vt.name for vt in du_lieu.roles}

        # Tính thời lượng ca làm việc theo phút
        self.thoi_luong_ca_phut: Dict[int, int] = {}
        for c in self.ca_maus:
            do_dai_gio = c.duration_hours or KiemTraDuLieu.tinhDoDaiGio(c.start_time, c.end_time)
            self.thoi_luong_ca_phut[c.id] = int(round(do_dai_gio * 60))

        # Tập hợp các ngày trong tuần (0=T2, ..., 6=CN)
        self.cac_ngay: List[int] = list(range(7))

        # Ánh xạ ràng buộc theo mã
        self.map_rang_buoc = {rb.code.upper(): rb for rb in du_lieu.constraints}

        # Ánh xạ đăng ký khả dụng: (employee_id, day_index, shift_id) -> status
        self.map_kha_dung: Dict[Tuple[int, int, int], str] = {}
        for kd in du_lieu.availabilities:
            self.map_kha_dung[(kd.employee_id, kd.day_index, kd.shift_id)] = kd.status.upper()

        # Ánh xạ đơn nghỉ phép đã duyệt: (employee_id, date_str) -> bool
        self.map_nghi_phep: Dict[Tuple[int, str], bool] = {}
        for np in du_lieu.approved_leaves:
            self.map_nghi_phep[(np.employee_id, np.date)] = True

        # Ánh xạ ngày thứ sang ngày ISO YYYY-MM-DD
        self.map_ngay_iso: Dict[int, str] = {}
        for nc in du_lieu.demands:
            self.map_ngay_iso[nc.day_index] = nc.date

        # Tập hợp các vai trò xuất hiện trong demands
        self.cac_vai_tro: List[int] = sorted(list(set(nc.role_tag_id for nc in du_lieu.demands)))
        if not self.cac_vai_tro and du_lieu.roles:
            self.cac_vai_tro = [vt.id for vt in du_lieu.roles]

        # Biến quyết định:
        # x_role[(emp_id, day_idx, shift_id, role_id)]: 1 nếu nhân viên làm ca đó với vai trò đó
        self.x_role: Dict[Tuple[int, int, int, int], cp_model.IntVar] = {}

        # x_shift[(emp_id, day_idx, shift_id)]: 1 nếu nhân viên làm ca đó
        self.x_shift: Dict[Tuple[int, int, int], cp_model.IntVar] = {}

        # w[(emp_id, day_idx)]: 1 nếu nhân viên có làm việc trong ngày đó
        self.w: Dict[Tuple[int, int], cp_model.IntVar] = {}

        # slack[(day_idx, shift_id, role_tag_id)]: số lượng thiếu hụt nhân sự cho nhu cầu
        self.slack: Dict[Tuple[int, int, int], cp_model.IntVar] = {}

        # Danh sách các thành phần đưa vào hàm mục tiêu (Objective)
        self.muc_tieu_phat: List[Any] = []
        self.muc_tieu_thuong: List[Any] = []

    def layRangBuoc(self, ma_rang_buoc: str) -> Optional[Any]:
        return self.map_rang_buoc.get(ma_rang_buoc.upper())

    def kiemTraRangBuocBat(self, ma_rang_buoc: str) -> bool:
        rb = self.layRangBuoc(ma_rang_buoc)
        # Nếu chưa cấu hình trong list, mặc định kiểm tra các ràng buộc luật định
        if rb is None:
            if ma_rang_buoc in ["MATCH_ROLE_TAG", "NO_OVERLAP"]:
                return True
            return False
        return bool(rb.enabled)

    def layGiaTriRangBuoc(self, ma_rang_buoc: str, mac_dinh: float) -> float:
        rb = self.layRangBuoc(ma_rang_buoc)
        if rb is not None and rb.value is not None:
            return float(rb.value)
        return mac_dinh

    def khoiTaoBienQuyetDinh(self):
        """Khởi tạo các biến quyết định x_role, x_shift, w và slack"""
        for nv in self.nhan_viens:
            for d in self.cac_ngay:
                self.w[(nv.id, d)] = self.model.NewBoolVar(f"w_nv{nv.id}_d{d}")

                for c in self.ca_maus:
                    # x_shift biểu diễn nhân viên có làm ca c vào ngày d hay không
                    self.x_shift[(nv.id, d, c.id)] = self.model.NewBoolVar(f"x_nv{nv.id}_d{d}_c{c.id}")

                    # x_role cho từng vai trò
                    role_vars = []
                    for r in self.cac_vai_tro:
                        var = self.model.NewBoolVar(f"xr_nv{nv.id}_d{d}_c{c.id}_r{r}")
                        self.x_role[(nv.id, d, c.id, r)] = var
                        role_vars.append(var)

                    # Một nhân viên trong một ca làm việc chỉ có thể đảm nhận tối đa 1 vai trò:
                    # sum(x_role[nv, d, c, r]) == x_shift[nv, d, c]
                    self.model.Add(sum(role_vars) == self.x_shift[(nv.id, d, c.id)])

                # w[nv, d] = 1 <=> có ít nhất 1 ca trong ngày d
                ca_trong_ngay = [self.x_shift[(nv.id, d, c.id)] for c in self.ca_maus]
                self.model.AddMaxEquality(self.w[(nv.id, d)], ca_trong_ngay)

        # Khởi tạo biến thiếu hụt slack cho từng nhu cầu
        for nc in self.du_lieu.demands:
            key = (nc.day_index, nc.shift_id, nc.role_tag_id)
            if key not in self.slack:
                self.slack[key] = self.model.NewIntVar(
                    0, nc.required_count, f"slack_d{nc.day_index}_c{nc.shift_id}_r{nc.role_tag_id}"
                )

    def thietLapRangBuocNhuCauVaVaiTro(self):
        """
        1. Đáp ứng nhu cầu nhân sự theo từng ngày, ca và vai trò (có biến bù slack)
        2. Ràng buộc đúng vai trò chuyên môn (MATCH_ROLE_TAG)
        """
        match_role_enabled = self.kiemTraRangBuocBat("MATCH_ROLE_TAG")

        # Gom nhóm nhu cầu theo (day_index, shift_id, role_tag_id)
        nhu_cau_nhom: Dict[Tuple[int, int, int], int] = {}
        for nc in self.du_lieu.demands:
            key = (nc.day_index, nc.shift_id, nc.role_tag_id)
            nhu_cau_nhom[key] = nhu_cau_nhom.get(key, 0) + nc.required_count

        for (d, s, r), so_luong_can in nhu_cau_nhom.items():
            slack_var = self.slack[(d, s, r)]

            # Các nhân viên có thể đảm nhận vai trò r
            bien_gan = []
            for nv in self.nhan_viens:
                xr_var = self.x_role.get((nv.id, d, s, r))
                if xr_var is not None:
                    # Nếu bật MATCH_ROLE_TAG: Nhân viên khác role không thể gán vào vai trò r
                    if match_role_enabled and nv.role_tag_id != r:
                        self.model.Add(xr_var == 0)
                    else:
                        bien_gan.append(xr_var)

            # sum(x_role) + slack == so_luong_can
            self.model.Add(sum(bien_gan) + slack_var == so_luong_can)

            # Phạt nặng cho mỗi nhân sự còn thiếu (Ưu tiên số 1 tuyệt đối của bài toán)
            HE_SO_PHAT_THIEU_NGUOI = 100000
            self.muc_tieu_phat.append(slack_var * HE_SO_PHAT_THIEU_NGUOI)

        # Nếu vai trò r không có nhu cầu trong ca (d, s), ép x_role = 0
        for nv in self.nhan_viens:
            for d in self.cac_ngay:
                for c in self.ca_maus:
                    for r in self.cac_vai_tro:
                        if nhu_cau_nhom.get((d, c.id, r), 0) <= 0:
                            xr_var = self.x_role.get((nv.id, d, c.id, r))
                            if xr_var is not None:
                                self.model.Add(xr_var == 0)

    def thietLapRangBuocKhaDungVaNghiPhep(self):
        """
        3. Không xếp nhân viên vào ca đã đăng ký Bận (BUSY)
        4. Tôn trọng đơn nghỉ phép đã duyệt (NO_SHIFT_ON_APPROVED_LEAVE)
        5. Ưu tiên nguyện vọng rảnh (PREFER_STAFF_AVAILABILITY - Soft)
        """
        respect_leave_enabled = self.kiemTraRangBuocBat("NO_SHIFT_ON_APPROVED_LEAVE")
        prefer_avail_enabled = self.kiemTraRangBuocBat("PREFER_STAFF_AVAILABILITY")

        for nv in self.nhan_viens:
            for d in self.cac_ngay:
                date_str = self.map_ngay_iso.get(d)

                # Ràng buộc nghỉ phép đã duyệt (Hard)
                if respect_leave_enabled and date_str:
                    if self.map_nghi_phep.get((nv.id, date_str), False):
                        self.model.Add(self.w[(nv.id, d)] == 0)

                # Ràng buộc đăng ký Rảnh / Bận
                for c in self.ca_maus:
                    kd_status = self.map_kha_dung.get((nv.id, d, c.id), "NONE")

                    # Khi nhân viên đăng ký BẬN (BUSY): Không được phép xếp ca (Hard Constraint)
                    if kd_status == "BUSY":
                        self.model.Add(self.x_shift[(nv.id, d, c.id)] == 0)

                    # Khi nhân viên đăng ký RẢNH (AVAILABLE): Thưởng điểm ưu tiên xếp ca (Soft Constraint)
                    elif kd_status == "AVAILABLE" and prefer_avail_enabled:
                        HE_SO_THUONG_RANH = 100
                        self.muc_tieu_thuong.append(self.x_shift[(nv.id, d, c.id)] * HE_SO_THUONG_RANH)

    def thietLapRangBuocTrungCaVaGioiHanNgay(self):
        """
        6. Không xếp trùng ca hoặc ca giao giờ nhau (NO_OVERLAP)
        7. Số ca tối đa trong một ngày (MAX_SHIFTS_PER_DAY)
        8. Số giờ làm tối đa mỗi ngày (MAX_HOURS_PER_DAY)
        """
        no_overlap_enabled = self.kiemTraRangBuocBat("NO_OVERLAP")
        max_shifts_enabled = self.kiemTraRangBuocBat("MAX_SHIFTS_PER_DAY")
        max_shifts_val = int(self.layGiaTriRangBuoc("MAX_SHIFTS_PER_DAY", 1))

        max_hours_day_enabled = self.kiemTraRangBuocBat("MAX_HOURS_PER_DAY")
        max_hours_day_val = self.layGiaTriRangBuoc("MAX_HOURS_PER_DAY", 8.0)
        max_minutes_day_val = int(round(max_hours_day_val * 60))

        # Kiểm tra trùng ca giữa các cặp ca
        if no_overlap_enabled:
            for i, c1 in enumerate(self.ca_maus):
                for j, c2 in enumerate(self.ca_maus):
                    if i < j and KiemTraDuLieu.kiemTraHaiCaTrungGio(c1, c2):
                        for nv in self.nhan_viens:
                            for d in self.cac_ngay:
                                self.model.Add(
                                    self.x_shift[(nv.id, d, c1.id)] + self.x_shift[(nv.id, d, c2.id)] <= 1
                                )

        # Số ca tối đa mỗi ngày
        if max_shifts_enabled:
            for nv in self.nhan_viens:
                for d in self.cac_ngay:
                    ca_trong_ngay = [self.x_shift[(nv.id, d, c.id)] for c in self.ca_maus]
                    self.model.Add(sum(ca_trong_ngay) <= max_shifts_val)

        # Số giờ tối đa mỗi ngày
        if max_hours_day_enabled:
            for nv in self.nhan_viens:
                for d in self.cac_ngay:
                    phut_trong_ngay = [
                        self.x_shift[(nv.id, d, c.id)] * self.thoi_luong_ca_phut[c.id]
                        for c in self.ca_maus
                    ]
                    self.model.Add(sum(phut_trong_ngay) <= max_minutes_day_val)

    def thietLapRangBuocNghiGiuaCaVaNgayLienTiep(self):
        """
        9. Thời gian nghỉ tối thiểu giữa 2 ngày làm (MIN_REST_HOURS)
        10. Không xếp ca tối rồi ca sáng hôm sau (NO_NIGHT_TO_MORNING)
        11. Số ngày làm liên tiếp tối đa (MAX_CONSECUTIVE_DAYS)
        """
        min_rest_enabled = self.kiemTraRangBuocBat("MIN_REST_HOURS")
        min_rest_val = self.layGiaTriRangBuoc("MIN_REST_HOURS", 12.0)

        no_clopening_enabled = self.kiemTraRangBuocBat("NO_NIGHT_TO_MORNING")

        for d in range(6):  # Từ T2 (0) đến T7 (5), xét ngày d và d+1
            for c_truoc in self.ca_maus:
                for c_sau in self.ca_maus:
                    vi_pham_nghi = KiemTraDuLieu.kiemTraCaDemSangSom(c_truoc, c_sau, min_rest_val)

                    is_night_morning = (
                        c_truoc.code == "evening" or "tối" in c_truoc.name.lower()
                    ) and (
                        c_sau.code == "morning" or "sáng" in c_sau.name.lower()
                    )

                    ap_dung = False
                    if min_rest_enabled and vi_pham_nghi:
                        ap_dung = True
                    if no_clopening_enabled and is_night_morning:
                        ap_dung = True

                    if ap_dung:
                        for nv in self.nhan_viens:
                            self.model.Add(
                                self.x_shift[(nv.id, d, c_truoc.id)] + self.x_shift[(nv.id, d + 1, c_sau.id)] <= 1
                            )

        # Số ngày làm liên tiếp tối đa
        max_consec_enabled = self.kiemTraRangBuocBat("MAX_CONSECUTIVE_DAYS")
        max_consec_val = int(self.layGiaTriRangBuoc("MAX_CONSECUTIVE_DAYS", 6))

        if max_consec_enabled and max_consec_val < 7:
            do_dai_cua_so = max_consec_val + 1
            for start_day in range(7 - do_dai_cua_so + 1):
                for nv in self.nhan_viens:
                    ngay_lam_lien_tiep = [self.w[(nv.id, start_day + i)] for i in range(do_dai_cua_so)]
                    self.model.Add(sum(ngay_lam_lien_tiep) <= max_consec_val)

    def thietLapRangBuocGioTuanVaCongBang(self):
        """
        12. Giới hạn số giờ làm việc tối đa trong tuần (MAX_HOURS_PER_WEEK - Hard)
        13. Phân bổ giờ làm và số ca công bằng theo từng vai trò (FAIR_WORKING_HOURS - Soft)
        14. Chia đều ca cuối tuần (BALANCE_WEEKEND_SHIFTS - Soft)
        """
        max_hours_week_enabled = self.kiemTraRangBuocBat("MAX_HOURS_PER_WEEK")
        max_hours_week_val = self.layGiaTriRangBuoc("MAX_HOURS_PER_WEEK", 40.0)
        max_minutes_week_val = int(round(max_hours_week_val * 60))

        tong_phut_nv: Dict[int, Any] = {}
        tong_ca_nv: Dict[int, Any] = {}

        for nv in self.nhan_viens:
            danh_sach_phut = []
            danh_sach_ca = []
            for d in self.cac_ngay:
                for c in self.ca_maus:
                    x_var = self.x_shift[(nv.id, d, c.id)]
                    danh_sach_ca.append(x_var)
                    danh_sach_phut.append(x_var * self.thoi_luong_ca_phut[c.id])

            tong_phut = sum(danh_sach_phut)
            tong_ca = sum(danh_sach_ca)
            tong_phut_nv[nv.id] = tong_phut
            tong_ca_nv[nv.id] = tong_ca

            # Giới hạn giờ tối đa mỗi tuần (Hard constraint)
            if max_hours_week_enabled:
                gio_tran_nv = min(max_minutes_week_val, int(round((nv.max_hours_week or 40.0) * 60)))
                self.model.Add(tong_phut <= gio_tran_nv)

        # Phân bổ công bằng theo từng nhóm VAI TRÒ (Role-based Fairness - Soft constraint)
        fair_hours_enabled = self.kiemTraRangBuocBat("FAIR_WORKING_HOURS")
        if fair_hours_enabled:
            # Nhóm nhân viên theo role_tag_id
            nv_theo_role: Dict[int, List[NhanVienDauVao]] = {}
            for nv in self.nhan_viens:
                nv_theo_role.setdefault(nv.role_tag_id, []).append(nv)

            # Tổng nhu cầu ca và phút theo role_tag_id
            ca_can_theo_role: Dict[int, int] = {}
            phut_can_theo_role: Dict[int, int] = {}
            for nc in self.du_lieu.demands:
                thoi_luong = self.thoi_luong_ca_phut.get(nc.shift_id, 300)
                ca_can_theo_role[nc.role_tag_id] = ca_can_theo_role.get(nc.role_tag_id, 0) + nc.required_count
                phut_can_theo_role[nc.role_tag_id] = phut_can_theo_role.get(nc.role_tag_id, 0) + thoi_luong * nc.required_count

            for r_id, role_emps in nv_theo_role.items():
                so_nv_role = len(role_emps)
                if so_nv_role <= 1:
                    continue

                tong_ca_role = ca_can_theo_role.get(r_id, 0)
                tong_phut_role = phut_can_theo_role.get(r_id, 0)

                target_shifts_floor = tong_ca_role // so_nv_role
                target_shifts_ceil = (tong_ca_role + so_nv_role - 1) // so_nv_role
                target_phut_tb = tong_phut_role // so_nv_role

                # Biến max và min số ca trong nhóm vai trò để phạt chênh lệch (Disparity)
                max_shifts_var = self.model.NewIntVar(0, 14, f"max_s_r{r_id}")
                min_shifts_var = self.model.NewIntVar(0, 14, f"min_s_r{r_id}")

                for nv in role_emps:
                    # Đếm số ca khả dụng thực tế của nhân viên (không trùng đơn nghỉ phép, không đánh dấu BUSY)
                    so_ca_kha_dung = sum(
                        1 for d in self.cac_ngay
                        if not (self.kiemTraRangBuocBat("NO_SHIFT_ON_APPROVED_LEAVE") and self.map_ngay_iso.get(d) and self.map_nghi_phep.get((nv.id, self.map_ngay_iso.get(d)), False))
                        for c in self.ca_maus
                        if self.map_kha_dung.get((nv.id, d, c.id), "NONE") != "BUSY"
                    )

                    # 1. Ràng buộc min/max disparity cho nhân viên có đủ khả dụng
                    if so_ca_kha_dung >= target_shifts_floor:
                        self.model.Add(tong_ca_nv[nv.id] <= max_shifts_var)
                        self.model.Add(tong_ca_nv[nv.id] >= min_shifts_var)

                    # 2. Phạt nếu nhân viên có khả dụng nhưng bị gán 0 ca (Tránh bỏ sót nhân viên, chống 0 ca)
                    if so_ca_kha_dung > 0:
                        is_zero = self.model.NewBoolVar(f"zero_s_nv{nv.id}")
                        self.model.Add(tong_ca_nv[nv.id] == 0).OnlyEnforceIf(is_zero)
                        self.model.Add(tong_ca_nv[nv.id] >= 1).OnlyEnforceIf(is_zero.Not())
                        HE_SO_PHAT_KHONG_CA = 5000
                        self.muc_tieu_phat.append(is_zero * HE_SO_PHAT_KHONG_CA)

                    # 3. Phạt lệch số ca so với target_shifts [floor, ceil]
                    dev_over = self.model.NewIntVar(0, 14, f"dev_over_nv{nv.id}")
                    dev_under = self.model.NewIntVar(0, 14, f"dev_under_nv{nv.id}")
                    self.model.Add(tong_ca_nv[nv.id] - target_shifts_ceil <= dev_over)
                    self.model.Add(target_shifts_floor - tong_ca_nv[nv.id] <= dev_under)
                    HE_SO_LECH_CA = 300
                    self.muc_tieu_phat.append((dev_over + dev_under) * HE_SO_LECH_CA)

                    # 4. Phạt lệch phút làm việc so với giờ trung bình nhóm vai trò
                    dev_phut = self.model.NewIntVar(0, 100000, f"dev_phut_nv{nv.id}")
                    self.model.AddAbsEquality(dev_phut, tong_phut_nv[nv.id] - target_phut_tb)
                    HE_SO_LECH_PHUT = 2
                    self.muc_tieu_phat.append(dev_phut * HE_SO_LECH_PHUT)

                # Phạt chênh lệch Max - Min giữa các nhân viên trong cùng vai trò
                chênh_lech = self.model.NewIntVar(0, 14, f"disparity_r{r_id}")
                self.model.Add(chênh_lech == max_shifts_var - min_shifts_var)
                HE_SO_DISPARITY = 800
                self.muc_tieu_phat.append(chênh_lech * HE_SO_DISPARITY)

        # Chia đều ca cuối tuần (T7 d=5, CN d=6) (Soft constraint)
        fair_weekend_enabled = self.kiemTraRangBuocBat("BALANCE_WEEKEND_SHIFTS")
        if fair_weekend_enabled and len(self.nhan_viens) > 1:
            so_ca_cuoi_tuan_nv = {}
            for nv in self.nhan_viens:
                ca_t7_cn = [
                    self.x_shift[(nv.id, d, c.id)]
                    for d in [5, 6]
                    for c in self.ca_maus
                ]
                so_ca_cuoi_tuan_nv[nv.id] = sum(ca_t7_cn)

            tong_ca_cuoi_tuan_can = sum(
                nc.required_count
                for nc in self.du_lieu.demands
                if nc.day_index in [5, 6]
            )
            tb_cuoi_tuan = max(1, int(round(tong_ca_cuoi_tuan_can / len(self.nhan_viens))))

            for nv in self.nhan_viens:
                lech_t7cn = self.model.NewIntVar(0, 7, f"dev_wknd_nv{nv.id}")
                self.model.AddAbsEquality(lech_t7cn, so_ca_cuoi_tuan_nv[nv.id] - tb_cuoi_tuan)

                HE_SO_LECH_CUOI_TUAN = 50
                self.muc_tieu_phat.append(lech_t7cn * HE_SO_LECH_CUOI_TUAN)

    def thietLapHamMucTieu(self):
        """
        Thiết lập hàm mục tiêu tối ưu:
        Minimize: (Tổng phạt thiếu người + Tổng phạt lệch giờ + Tổng phạt lệch cuối tuần)
                  - (Tổng thưởng đăng ký rảnh)
        """
        tong_phat = sum(self.muc_tieu_phat) if self.muc_tieu_phat else 0
        tong_thuong = sum(self.muc_tieu_thuong) if self.muc_tieu_thuong else 0
        self.model.Minimize(tong_phat - tong_thuong)

    def xayDungMoHinh(self) -> cp_model.CpModel:
        """Thực hiện tuần tự tất cả các bước dựng mô hình CP-SAT"""
        self.khoiTaoBienQuyetDinh()
        self.thietLapRangBuocNhuCauVaVaiTro()
        self.thietLapRangBuocKhaDungVaNghiPhep()
        self.thietLapRangBuocTrungCaVaGioiHanNgay()
        self.thietLapRangBuocNghiGiuaCaVaNgayLienTiep()
        self.thietLapRangBuocGioTuanVaCongBang()
        self.thietLapHamMucTieu()
        return self.model