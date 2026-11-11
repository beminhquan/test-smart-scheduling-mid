"""
Dịch vụ Python Scheduling Service cho Smart Scheduling
Sử dụng FastAPI và Google OR-Tools CP-SAT
"""

import sys

# Xử lý chế độ CLI (--solve) độc lập ngay tại đầu file để KHÔNG import FastAPI/AnyIO/asyncio/_overlapped
# Điều này giúp loại bỏ hoàn toàn lỗi Winsock [WinError 10106] khi được gọi từ PHP Process trên Windows
if __name__ == "__main__" and len(sys.argv) > 1 and sys.argv[1] == "--solve":
    import json
    try:
        from bo_may_xep_lich import DuLieuDauVao, BoMayXepLich
        input_bytes = sys.stdin.buffer.read()
        input_text = input_bytes.decode("utf-8-sig", errors="replace")
        data_dict = json.loads(input_text)
        input_obj = DuLieuDauVao(**data_dict)
        solver = BoMayXepLich(input_obj)
        result = solver.giai()
        
        output_json = result.model_dump_json(indent=2)
        sys.stdout.buffer.write(output_json.encode("utf-8"))
        sys.stdout.buffer.flush()
        sys.exit(0)
    except Exception as e:
        err_result = {
            "status": "ERROR",
            "warnings": [f"Lỗi CLI: {str(e)}"],
        }
        err_json = json.dumps(err_result, ensure_ascii=False)
        sys.stdout.buffer.write(err_json.encode("utf-8"))
        sys.stdout.buffer.flush()
        sys.exit(1)

import json
import logging
from typing import Dict, Any
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from bo_may_xep_lich import DuLieuDauVao, KetQuaXepLich, BoMayXepLich

# Cấu hình logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("scheduling-service")

app = FastAPI(
    title="Smart Scheduling Service (OR-Tools CP-SAT)",
    version="1.0.0",
    description="Dịch vụ giải bài toán phân công lịch làm việc tự động tối ưu cho Smart Scheduling",
)

# Cấu hình CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
@app.get("/health")
def kiemTraTrangThai() -> Dict[str, Any]:
    return {
        "status": "ok",
        "service": "Smart Scheduling Python OR-Tools CP-SAT Service",
        "engine": "Google OR-Tools CP-SAT",
        "version": "1.0.0",
    }


@app.post("/api/v1/xep-lich", response_model=KetQuaXepLich)
def taoLichTuDong(du_lieu: DuLieuDauVao) -> KetQuaXepLich:
    """
    Endpoint nhận dữ liệu từ Laravel, thiết lập mô hình và giải bài toán xếp lịch tối ưu
    """
    try:
        logger.info(
            f"Nhận yêu cầu xếp lịch tuần {du_lieu.week_monday} "
            f"({len(du_lieu.employees)} nhân viên, {len(du_lieu.shifts)} ca, {len(du_lieu.demands)} nhu cầu)"
        )
        bo_may = BoMayXepLich(du_lieu)
        ket_qua = bo_may.giai()
        logger.info(
            f"Hoàn thành giải bài toán tuần {du_lieu.week_monday}: "
            f"Status={ket_qua.status}, Phân công={len(ket_qua.assignments)}, "
            f"Thiếu={len(ket_qua.unfilled_demands)}, Thời gian={ket_qua.solve_time}s"
        )
        return ket_qua
    except Exception as e:
        logger.error(f"Lỗi trong quá trình xếp lịch: {str(e)}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Lỗi bộ máy xếp lịch: {str(e)}")


def main():
    import uvicorn
    uvicorn.run("main:app", host="127.0.0.1", port=8001, reload=False)


if __name__ == "__main__":
    main()
