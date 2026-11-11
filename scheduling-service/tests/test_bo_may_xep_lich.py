"""
Unit tests cho BoMayXepLich (Google OR-Tools CP-SAT)
Kiểm tra các trường hợp nghiệp vụ, ràng buộc cứng, ràng buộc mềm và xử lý thiếu hụt nhân sự
"""

import unittest
from bo_may_xep_lich import (
    DuLieuDauVao,
    CaMauDauVao,
    VaiTroDauVao,
    NhanVienDauVao,
    NhuCauDauVao,
    KhaDungDauVao,
    NghiPhepDauVao,
    RangBuocDauVao,
    BoMayXepLich,
)


class TestBoMayXepLich(unittest.TestCase):

    def setUp(self):
        # Thiết lập dữ liệu cơ bản dùng chung
        self.shifts = [
            CaMauDauVao(id=1, code="morning", name="Ca sáng", start_time="06:00:00", end_time="12:00:00", duration_hours=6.0),
            CaMauDauVao(id=2, code="afternoon", name="Ca chiều", start_time="12:00:00", end_time="17:00:00", duration_hours=5.0),
            CaMauDauVao(id=3, code="evening", name="Ca tối", start_time="17:00:00", end_time="22:00:00", duration_hours=5.0),
        ]
        self.roles = [
            VaiTroDauVao(id=4, name="Phục vụ"),
            VaiTroDauVao(id=2, name="Pha chế"),
            VaiTroDauVao(id=3, name="Thu ngân"),
        ]
        # 6 nhân viên (2 phục vụ, 2 pha chế, 2 thu ngân)
        self.employees = [
            NhanVienDauVao(id=1, code="NV001", name="Phục vụ 1", role_tag_id=4, max_hours_week=40.0),
            NhanVienDauVao(id=2, code="NV002", name="Phục vụ 2", role_tag_id=4, max_hours_week=40.0),
            NhanVienDauVao(id=3, code="NV003", name="Pha chế 1", role_tag_id=2, max_hours_week=40.0),
            NhanVienDauVao(id=4, code="NV004", name="Pha chế 2", role_tag_id=2, max_hours_week=40.0),
            NhanVienDauVao(id=5, code="NV005", name="Thu ngân 1", role_tag_id=3, max_hours_week=40.0),
            NhanVienDauVao(id=6, code="NV006", name="Thu ngân 2", role_tag_id=3, max_hours_week=40.0),
        ]

    def test_01_xep_lich_du_nhan_vien_theo_nhu_cau(self):
        """1. Xếp lịch đủ nhân viên theo nhu cầu khi có đủ nhân lực"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=4, required_count=1),
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=2, required_count=1),
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=3, required_count=1),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
            ],
        )
        bo_may = BoMayXepLich(du_lieu)
        ket_qua = bo_may.giai()

        self.assertIn(ket_qua.status, ["OPTIMAL", "FEASIBLE"])
        self.assertEqual(len(ket_qua.assignments), 3)
        self.assertEqual(len(ket_qua.unfilled_demands), 0)

    def test_02_khong_xep_sai_vai_tro(self):
        """2. Nhân viên chỉ được phân công đúng vai trò chuyên môn (MATCH_ROLE_TAG)"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=2, required_count=1),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 1)
        assigned_emp_id = ket_qua.assignments[0].employee_id
        self.assertIn(assigned_emp_id, [3, 4])

    def test_03_khong_xep_nhan_vien_vao_ca_dang_ky_ban(self):
        """3. Không xếp nhân viên vào ca đã đăng ký Bận (BUSY)"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=4, required_count=1),
        ]
        availabilities = [
            KhaDungDauVao(employee_id=1, day_index=0, shift_id=1, status="BUSY"),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            availabilities=availabilities,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 1)
        self.assertEqual(ket_qua.assignments[0].employee_id, 2)

    def test_04_khong_xep_trung_ca_trong_ngay(self):
        """4. Không xếp trùng ca cho cùng một nhân viên trong cùng một ngày (NO_OVERLAP)"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=4, required_count=1),
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=2, role_tag_id=4, required_count=1),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="MAX_SHIFTS_PER_DAY", category="HARD", enabled=True, value=1),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        assigned_ids = [a.employee_id for a in ket_qua.assignments]
        self.assertEqual(len(set(assigned_ids)), 2)

    def test_05_dam_bao_thoi_gian_nghi_giua_hai_ca(self):
        """5. Đảm bảo thời gian nghỉ tối thiểu (MIN_REST_HOURS) giữa ca tối hôm trước và ca sáng hôm sau"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=3, role_tag_id=4, required_count=1),
            NhuCauDauVao(date="2026-11-10", day_index=1, shift_id=1, role_tag_id=4, required_count=1),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="MIN_REST_HOURS", category="HARD", enabled=True, value=12),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        nv_toi = next(a.employee_id for a in ket_qua.assignments if a.day_index == 0)
        nv_sang = next(a.employee_id for a in ket_qua.assignments if a.day_index == 1)
        self.assertNotEqual(nv_toi, nv_sang)

    def test_06_gioi_han_gio_lam_tuan(self):
        """6. Giới hạn số giờ làm việc tối đa trong tuần (MAX_HOURS_PER_WEEK)"""
        demands = [
            NhuCauDauVao(date=f"2026-11-{9+d:02d}", day_index=d, shift_id=1, role_tag_id=4, required_count=1)
            for d in range(7)
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="MAX_HOURS_PER_WEEK", category="HARD", enabled=True, value=20),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        from collections import Counter
        emp_counts = Counter(a.employee_id for a in ket_qua.assignments)
        for emp_id, count in emp_counts.items():
            self.assertLessEqual(count * 6.0, 20.0)

    def test_07_so_ngay_lam_lien_tiep_toi_da(self):
        """7. Số ngày làm liên tiếp tối đa (MAX_CONSECUTIVE_DAYS)"""
        demands = [
            NhuCauDauVao(date=f"2026-11-{9+d:02d}", day_index=d, shift_id=1, role_tag_id=4, required_count=1)
            for d in range(7)
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="MAX_CONSECUTIVE_DAYS", category="HARD", enabled=True, value=5),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        from collections import Counter
        emp_counts = Counter(a.employee_id for a in ket_qua.assignments)
        for emp_id, count in emp_counts.items():
            self.assertLessEqual(count, 5)

    def test_08_ton_trong_nghi_phep_da_duyet(self):
        """8. Không xếp ca vào ngày nhân viên có đơn nghỉ phép đã duyệt (NO_SHIFT_ON_APPROVED_LEAVE)"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=4, required_count=1),
        ]
        approved_leaves = [
            NghiPhepDauVao(employee_id=1, date="2026-11-09"),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            approved_leaves=approved_leaves,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="NO_SHIFT_ON_APPROVED_LEAVE", category="HARD", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 1)
        self.assertEqual(ket_qua.assignments[0].employee_id, 2)

    def test_09_tat_rang_buoc_thay_doi_hanh_vi_solver(self):
        """9. Tắt một ràng buộc (ví dụ MIN_REST_HOURS = False) cho phép xếp ca liên tiếp nếu cần"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=3, role_tag_id=4, required_count=1),
            NhuCauDauVao(date="2026-11-10", day_index=1, shift_id=1, role_tag_id=4, required_count=1),
        ]
        only_one_emp = [self.employees[0]]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=only_one_emp,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="MIN_REST_HOURS", category="HARD", enabled=False),
                RangBuocDauVao(code="NO_NIGHT_TO_MORNING", category="HARD", enabled=False),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 2)

    def test_10_thay_doi_gia_tri_rang_buoc_nhan_gia_tri_moi(self):
        """10. Thay đổi giá trị ràng buộc MAX_SHIFTS_PER_DAY = 2 cho phép làm 2 ca trong ngày"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=4, required_count=1),
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=3, role_tag_id=4, required_count=1),
        ]
        only_one_emp = [self.employees[0]]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=only_one_emp,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="MAX_SHIFTS_PER_DAY", category="HARD", enabled=True, value=2),
                RangBuocDauVao(code="NO_OVERLAP", category="HARD", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 2)
        self.assertEqual(ket_qua.assignments[0].employee_id, 1)
        self.assertEqual(ket_qua.assignments[1].employee_id, 1)

    def test_11_xu_ly_thieu_nhan_vien_tra_ve_slack(self):
        """11. Xử lý trường hợp thiếu nhân viên: Không vi phạm ràng buộc cứng, trả về unfilled_demands"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=2, required_count=3),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 2)
        self.assertEqual(len(ket_qua.unfilled_demands), 1)
        self.assertEqual(ket_qua.unfilled_demands[0].missing_count, 1)
        self.assertEqual(ket_qua.unfilled_demands[0].assigned_count, 2)

    def test_12_uu_tien_nhan_vien_dang_ky_ranh_soft_constraint(self):
        """12. Ưu tiên nhân viên đăng ký Rảnh (PREFER_STAFF_AVAILABILITY - Soft)"""
        demands = [
            NhuCauDauVao(date="2026-11-09", day_index=0, shift_id=1, role_tag_id=4, required_count=1),
        ]
        # NV002 đăng ký AVAILABLE, NV001 không đăng ký (NONE)
        availabilities = [
            KhaDungDauVao(employee_id=2, day_index=0, shift_id=1, status="AVAILABLE"),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            availabilities=availabilities,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="PREFER_STAFF_AVAILABILITY", category="SOFT", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 1)
        # Solver ưu tiên xếp NV002 vì có nguyện vọng Rảnh
        self.assertEqual(ket_qua.assignments[0].employee_id, 2)

    def test_13_phan_bo_cong_bang_cuoi_tuan(self):
        """13. Chia đều ca cuối tuần (BALANCE_WEEKEND_SHIFTS - Soft)"""
        # Thứ 7 (d=5) cần 1 Phục vụ, Chủ nhật (d=6) cần 1 Phục vụ
        demands = [
            NhuCauDauVao(date="2026-11-14", day_index=5, shift_id=1, role_tag_id=4, required_count=1),
            NhuCauDauVao(date="2026-11-15", day_index=6, shift_id=1, role_tag_id=4, required_count=1),
        ]
        du_lieu = DuLieuDauVao(
            week_monday="2026-11-09",
            shifts=self.shifts,
            roles=self.roles,
            employees=self.employees,
            demands=demands,
            constraints=[
                RangBuocDauVao(code="MATCH_ROLE_TAG", category="HARD", enabled=True),
                RangBuocDauVao(code="BALANCE_WEEKEND_SHIFTS", category="SOFT", enabled=True),
            ],
        )
        ket_qua = BoMayXepLich(du_lieu).giai()
        self.assertEqual(len(ket_qua.assignments), 2)
        # Chia đều 2 ca cho 2 nhân viên phục vụ (mỗi người 1 ca)
        assigned_ids = [a.employee_id for a in ket_qua.assignments]
        self.assertEqual(set(assigned_ids), {1, 2})


if __name__ == "__main__":
    unittest.main()
