"""
Module: xu_ly_du_lieu.py
Nhiệm vụ: Định nghĩa cấu trúc dữ liệu Pydantic, kiểm tra dữ liệu đầu vào và chuyển đổi định dạng
"""

from typing import List, Dict, Any, Optional
from pydantic import BaseModel, Field
import datetime


class CaMauDauVao(BaseModel):
    id: int
    code: Optional[str] = None
    name: str
    start_time: str  # Định dạng "HH:MM:SS" hoặc "HH:MM"
    end_time: str
    duration_hours: Optional[float] = None
    status: str = "ACTIVE"


class VaiTroDauVao(BaseModel):
    id: int
    name: str


class NhanVienDauVao(BaseModel):
    id: int
    code: str
    name: str
    role_tag_id: int
    contract_type: Optional[str] = "FULLTIME"
    max_hours_week: Optional[float] = 40.0
    status: str = "ACTIVE"


class NhuCauDauVao(BaseModel):
    date: str  # YYYY-MM-DD
    day_index: int  # 0=T2, ..., 6=CN
    shift_id: int
    role_tag_id: int
    required_count: int


class KhaDungDauVao(BaseModel):
    employee_id: int
    date: Optional[str] = None
    day_index: int  # 0=T2, ..., 6=CN
    shift_id: int
    status: str  # "AVAILABLE", "BUSY", "NONE"


class NghiPhepDauVao(BaseModel):
    employee_id: int
    date: str  # YYYY-MM-DD


class RangBuocDauVao(BaseModel):
    code: str
    name: Optional[str] = None
    category: str = "HARD"  # "HARD" hoặc "SOFT"
    enabled: bool = True
    value: Optional[float] = None
    weight: Optional[float] = None


class CauHinhSolver(BaseModel):
    max_time_seconds: float = 15.0
    num_workers: int = 4
    log_search_progress: bool = False


class DuLieuDauVao(BaseModel):
    week_monday: str  # YYYY-MM-DD
    shifts: List[CaMauDauVao]
    roles: List[VaiTroDauVao] = []
    employees: List[NhanVienDauVao]
    demands: List[NhuCauDauVao]
    availabilities: List[KhaDungDauVao] = []
    approved_leaves: List[NghiPhepDauVao] = []
    constraints: List[RangBuocDauVao] = []
    solver_config: Optional[CauHinhSolver] = None


# --- CẤU TRÚC KẾT QUẢ ĐẦU RA ---

class PhanCongDauRa(BaseModel):
    employee_id: int
    employee_code: str
    employee_name: str
    date: str
    day_index: int
    shift_id: int
    shift_name: str
    role_tag_id: int
    hours: float


class NhuCauThieuDauRa(BaseModel):
    date: str
    day_index: int
    shift_id: int
    shift_name: Optional[str] = None
    role_tag_id: int
    role_name: Optional[str] = None
    required_count: int
    assigned_count: int
    missing_count: int


class ThongKeDauRa(BaseModel):
    total_employees: int
    total_shifts_demanded: int
    total_assignments: int
    total_unfilled: int
    fulfillment_rate: float


class KetQuaXepLich(BaseModel):
    status: str  # "OPTIMAL", "FEASIBLE", "INFEASIBLE", "UNKNOWN"
    objective_value: float = 0.0
    solve_time: float = 0.0
    assignments: List[PhanCongDauRa] = []
    unfilled_demands: List[NhuCauThieuDauRa] = []
    statistics: Optional[ThongKeDauRa] = None
    warnings: List[str] = []


class KiemTraDuLieu:
    """
    Tiện ích kiểm tra tính hợp lệ và chuẩn hóa dữ liệu
    """

    @staticmethod
    def doiGioSangPhut(time_str: str) -> int:
        """Đổi chuỗi HH:MM hoặc HH:MM:SS thành số phút từ 00:00"""
        parts = time_str.strip().split(":")
        h = int(parts[0])
        m = int(parts[1]) if len(parts) > 1 else 0
        return h * 60 + m

    @staticmethod
    def tinhDoDaiGio(start_time: str, end_time: str) -> float:
        """Tính thời lượng ca theo giờ (hỗ trợ ca qua đêm)"""
        start_min = KiemTraDuLieu.doiGioSangPhut(start_time)
        end_min = KiemTraDuLieu.doiGioSangPhut(end_time)
        if end_min <= start_min:
            end_min += 24 * 60
        return round((end_min - start_min) / 60.0, 2)

    @staticmethod
    def kiemTraHaiCaTrungGio(s1: CaMauDauVao, s2: CaMauDauVao) -> bool:
        """Kiểm tra 2 ca làm việc có bị giao nhau về mặt thời gian trong cùng một ngày không"""
        if s1.id == s2.id:
            return True
        start1 = KiemTraDuLieu.doiGioSangPhut(s1.start_time)
        end1 = KiemTraDuLieu.doiGioSangPhut(s1.end_time)
        if end1 <= start1:
            end1 += 24 * 60

        start2 = KiemTraDuLieu.doiGioSangPhut(s2.start_time)
        end2 = KiemTraDuLieu.doiGioSangPhut(s2.end_time)
        if end2 <= start2:
            end2 += 24 * 60

        return max(start1, start2) < min(end1, end2)

    @staticmethod
    def kiemTraCaDemSangSom(s_truoc: CaMauDauVao, s_sau: CaMauDauVao, min_rest_hours: float) -> bool:
        """
        Kiểm tra ca trước (ngày d) và ca sau (ngày d+1) có vi phạm thời gian nghỉ tối thiểu không.
        Giờ kết thúc ca trước đến giờ bắt đầu ca sau (ngày hôm sau: +24h)
        """
        end_truoc = KiemTraDuLieu.doiGioSangPhut(s_truoc.end_time)
        start_sau = KiemTraDuLieu.doiGioSangPhut(s_sau.start_time) + 24 * 60
        rest_min = start_sau - end_truoc
        return rest_min < int(min_rest_hours * 60)

