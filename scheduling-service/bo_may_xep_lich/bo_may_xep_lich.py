"""
Module: bo_may_xep_lich.py
Nhiệm vụ: Lớp điều phối BoMayXepLich chạy Solver Google OR-Tools CP-SAT, trích xuất phân công, phát hiện thiếu hụt và tổng hợp thống kê
"""

import time
from typing import Dict, List, Optional
from ortools.sat.python import cp_model
from .xu_ly_du_lieu import (
    DuLieuDauVao,
    KetQuaXepLich,
    PhanCongDauRa,
    NhuCauThieuDauRa,
    ThongKeDauRa,
)
from .mo_hinh_xep_lich import MoHinhXepLich


class BoMayXepLich:
    """
    Bộ máy xếp lịch tự động thông minh sử dụng thuật toán quy hoạch ràng buộc Google OR-Tools CP-SAT
    """

    def __init__(self, du_lieu: DuLieuDauVao):
        self.du_lieu = du_lieu

    def giai(self) -> KetQuaXepLich:
        """
        Thực hiện toàn bộ quy trình: Khởi tạo mô hình -> Chạy Solver -> Trích xuất kết quả
        """
        thoi_gian_bat_dau = time.time()
        warnings: List[str] = []

        # 1. Kiểm tra tính khả thi cơ bản của dữ liệu
        if not self.du_lieu.shifts:
            return KetQuaXepLich(
                status="FEASIBLE",
                warnings=["Danh sách ca mẫu trống."],
            )

        if not self.du_lieu.demands:
            return KetQuaXepLich(
                status="OPTIMAL",
                warnings=["Không có nhu cầu nhân sự nào cần xếp lịch trong tuần này."],
            )

        active_employees = [nv for nv in self.du_lieu.employees if nv.status.upper() == "ACTIVE"]
        if not active_employees:
            unfilled: List[NhuCauThieuDauRa] = []
            map_ca = {c.id: c.name for c in self.du_lieu.shifts}
            map_vt = {vt.id: vt.name for vt in self.du_lieu.roles}

            for nc in self.du_lieu.demands:
                unfilled.append(
                    NhuCauThieuDauRa(
                        date=nc.date,
                        day_index=nc.day_index,
                        shift_id=nc.shift_id,
                        shift_name=map_ca.get(nc.shift_id),
                        role_tag_id=nc.role_tag_id,
                        role_name=map_vt.get(nc.role_tag_id),
                        required_count=nc.required_count,
                        assigned_count=0,
                        missing_count=nc.required_count,
                    )
                )

            tong_cau = sum(nc.required_count for nc in self.du_lieu.demands)
            return KetQuaXepLich(
                status="FEASIBLE",
                unfilled_demands=unfilled,
                statistics=ThongKeDauRa(
                    total_employees=0,
                    total_shifts_demanded=tong_cau,
                    total_assignments=0,
                    total_unfilled=tong_cau,
                    fulfillment_rate=0.0,
                ),
                warnings=["Không có nhân viên active nào để phân công."],
            )

        # 2. Xây dựng mô hình CP-SAT
        mo_hinh = MoHinhXepLich(self.du_lieu)
        model = mo_hinh.xayDungMoHinh()

        # 3. Cấu hình Solver
        solver = cp_model.CpSolver()
        cfg = self.du_lieu.solver_config
        max_time = cfg.max_time_seconds if cfg else 15.0
        num_workers = cfg.num_workers if cfg else 4

        solver.parameters.max_time_in_seconds = float(max_time)
        solver.parameters.num_search_workers = int(num_workers)
        if cfg and cfg.log_search_progress:
            solver.parameters.log_search_progress = True

        # 4. Giải bài toán
        status = solver.Solve(model)
        thoi_gian_chay = round(time.time() - thoi_gian_bat_dau, 3)

        # 5. Xử lý trạng thái kết quả
        if status == cp_model.OPTIMAL:
            trang_thai_chuoi = "OPTIMAL"
        elif status == cp_model.FEASIBLE:
            trang_thai_chuoi = "FEASIBLE"
        elif status == cp_model.INFEASIBLE:
            return KetQuaXepLich(
                status="INFEASIBLE",
                solve_time=thoi_gian_chay,
                warnings=[
                    "Mô hình không tìm thấy bất kỳ phương án nào thỏa mãn các ràng buộc bắt buộc (Hard Constraints)."
                ],
            )
        else:
            return KetQuaXepLich(
                status="UNKNOWN",
                solve_time=thoi_gian_chay,
                warnings=["Bộ giải không thể tìm thấy nghiệm trong thời gian cho phép."],
            )

        # 6. Trích xuất danh sách phân công (Assignments)
        assignments: List[PhanCongDauRa] = []
        for nv in mo_hinh.nhan_viens:
            for d in mo_hinh.cac_ngay:
                date_str = mo_hinh.map_ngay_iso.get(d, f"Day-{d}")
                for c in mo_hinh.ca_maus:
                    for r in mo_hinh.cac_vai_tro:
                        xr_var = mo_hinh.x_role.get((nv.id, d, c.id, r))
                        if xr_var is not None and solver.Value(xr_var) == 1:
                            do_dai_gio = c.duration_hours or round(mo_hinh.thoi_luong_ca_phut[c.id] / 60.0, 2)
                            assignments.append(
                                PhanCongDauRa(
                                    employee_id=nv.id,
                                    employee_code=nv.code,
                                    employee_name=nv.name,
                                    date=date_str,
                                    day_index=d,
                                    shift_id=c.id,
                                    shift_name=c.name,
                                    role_tag_id=r,
                                    hours=do_dai_gio,
                                )
                            )

        # 7. Trích xuất danh sách nhu cầu còn thiếu (Unfilled Demands)
        unfilled_demands: List[NhuCauThieuDauRa] = []
        for nc in self.du_lieu.demands:
            key = (nc.day_index, nc.shift_id, nc.role_tag_id)
            slack_var = mo_hinh.slack.get(key)
            if slack_var is not None:
                so_luong_thieu = solver.Value(slack_var)
                if so_luong_thieu > 0:
                    so_luong_da_gan = nc.required_count - so_luong_thieu
                    shift_obj = mo_hinh.map_ca_mau.get(nc.shift_id)
                    role_name = mo_hinh.map_vai_tro.get(nc.role_tag_id)

                    unfilled_demands.append(
                        NhuCauThieuDauRa(
                            date=nc.date,
                            day_index=nc.day_index,
                            shift_id=nc.shift_id,
                            shift_name=shift_obj.name if shift_obj else None,
                            role_tag_id=nc.role_tag_id,
                            role_name=role_name,
                            required_count=nc.required_count,
                            assigned_count=so_luong_da_gan,
                            missing_count=so_luong_thieu,
                        )
                    )

        # 8. Tính toán thống kê tổng quan
        tong_nhu_cau = sum(nc.required_count for nc in self.du_lieu.demands)
        tong_phan_cong = len(assignments)
        tong_thieu = sum(u.missing_count for u in unfilled_demands)
        ty_le_dap_ung = round((tong_phan_cong / tong_nhu_cau * 100), 2) if tong_nhu_cau > 0 else 100.0

        if tong_thieu > 0:
            warnings.append(
                f"Hệ thống thiếu {tong_thieu} lượt nhân sự so với định biên (tỷ lệ đáp ứng: {ty_le_dap_ung}%)."
            )

        statistics = ThongKeDauRa(
            total_employees=len(active_employees),
            total_shifts_demanded=tong_nhu_cau,
            total_assignments=tong_phan_cong,
            total_unfilled=tong_thieu,
            fulfillment_rate=ty_le_dap_ung,
        )

        return KetQuaXepLich(
            status=trang_thai_chuoi,
            objective_value=round(solver.ObjectiveValue(), 2),
            solve_time=thoi_gian_chay,
            assignments=assignments,
            unfilled_demands=unfilled_demands,
            statistics=statistics,
            warnings=warnings,
        )