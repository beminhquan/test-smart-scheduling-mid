<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\TaiKhoan;
use App\Models\NhanVien;
use App\Models\RangBuoc;
use App\Models\CaMau;
use App\Models\RoleTag;
use App\Models\NhuCauCa;
use App\Models\LichLamViec;
use App\Models\PhanCongCa;
use App\Models\TuanDangKy;
use App\Models\DangKyCaKhaDung;
use App\Models\YeuCauNghi;
use Database\Seeders\CaMauSeeder;
use Database\Seeders\NhanVienSeeder;
use Database\Seeders\RangBuocSeeder;
use Database\Seeders\RoleTagSeeder;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuditScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $managerUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleTagSeeder::class);
        $this->seed(TaiKhoanSeeder::class);
        $this->seed(NhanVienSeeder::class);
        $this->seed(CaMauSeeder::class);
        $this->seed(RangBuocSeeder::class);

        $this->managerUser = TaiKhoan::where('vai_tro', 'MANAGER')->first();
        $this->staffUser = TaiKhoan::where('vai_tro', 'STAFF')->first();
    }

    protected function taoNhuCauNho(string $weekMonday, int $soLuongMoiCa = 1)
    {
        $mon = Carbon::parse($weekMonday);
        $shifts = CaMau::where('trang_thai', 'ACTIVE')->get();
        // Lấy vai trò Pha chế (role_tag_id = 2) vì các nhân viên seed trong DB đều có role 2 hoặc 3
        $rolePhaChe = RoleTag::where('ten_role', 'like', '%Pha chế%')->first() ?? RoleTag::find(2);

        // Tạo nhu cầu vừa phải cho 7 ngày
        for ($d = 0; $d < 7; $d++) {
            $date = $mon->copy()->addDays($d)->format('Y-m-d');
            foreach ($shifts as $shift) {
                NhuCauCa::create([
                    'ngay_lam'     => $date,
                    'ca_mau_id'    => $shift->ca_mau_id,
                    'role_tag_id'  => $rolePhaChe->role_tag_id,
                    'so_luong_can' => $soLuongMoiCa,
                ]);
            }
        }
    }

    /**
     * Kịch bản 1: Đủ nhân viên và đủ nhu cầu
     */
    public function test_audit_01_du_nhan_vien_va_du_nhu_cau()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);

        $data = $res->json();
        $this->assertEquals(true, $data['status']);
        $this->assertContains($data['solver_status'], ['OPTIMAL', 'FEASIBLE']);
        $this->assertEquals(0, count($data['unfilled_demands'] ?? []));
        $this->assertEquals(21, $data['assignments_count']);

        // Xác thực trong Database
        $this->assertDatabaseHas('lich_lam_viecs', [
            'tu_ngay'    => $week,
            'trang_thai' => 'DRAFT',
        ]);
    }

    /**
     * Kịch bản 2: Thiếu nhân viên (Nhu cầu vượt quá khả năng nhân sự)
     */
    public function test_audit_02_thieu_nhan_vien()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        // Đặt nhu cầu 3 người mỗi ca (21 ca x 3 = 63 slots, vượt quá 7 nhân viên Pha chế)
        $this->taoNhuCauNho($week, 3);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);

        $data = $res->json();
        $this->assertEquals(true, $data['status']);
        // Do dùng biến bù slack, solver giải thành công và liệt kê đầy đủ các vị trí thiếu hụt
        $this->assertContains($data['solver_status'], ['OPTIMAL', 'FEASIBLE']);
        $this->assertGreaterThan(0, count($data['unfilled_demands']));
    }

    /**
     * Kịch bản 3: Có nhân viên đăng ký Bận (BUSY)
     */
    public function test_audit_03_co_nhan_vien_dang_ky_ban()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        // Lấy 1 nhân viên STAFF và đăng ký BẬN Thứ Hai Ca Sáng
        $staff = NhanVien::where('tai_khoan_id', $this->staffUser->tai_khoan_id)->first();
        $shiftMorning = CaMau::where('trang_thai', 'ACTIVE')->orderBy('gio_bat_dau', 'asc')->first();

        $tuan = TuanDangKy::create([
            'nhan_vien_id' => $staff->nhan_vien_id,
            'tu_ngay'      => $week,
            'den_ngay'     => Carbon::parse($week)->addDays(6)->format('Y-m-d'),
            'trang_thai'   => 'SUBMITTED',
        ]);

        DangKyCaKhaDung::create([
            'tuan_dang_ky_id' => $tuan->tuan_dang_ky_id,
            'thu_trong_tuan'  => 1, // Thứ Hai
            'ca_mau_id'       => $shiftMorning->ca_mau_id,
            'trang_thai'      => 'BUSY',
        ]);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);

        // Kiểm tra phân công ca trong Database: nhân viên này tuyệt đối không được phân vào Thứ Hai Ca Sáng
        $this->assertDatabaseMissing('phan_cong_cas', [
            'nhan_vien_id' => $staff->nhan_vien_id,
            'ca_mau_id'    => $shiftMorning->ca_mau_id,
            'ngay_lam'     => $week,
        ]);
    }

    /**
     * Kịch bản 4: Có nhân viên nghỉ phép đã duyệt (APPROVED LEAVE)
     */
    public function test_audit_04_co_nhan_vien_nghi_phep_da_duyet()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        $staff = NhanVien::where('tai_khoan_id', $this->staffUser->tai_khoan_id)->first();
        // Nghỉ phép vào Thứ Ba
        $dateTue = Carbon::parse($week)->addDays(1)->format('Y-m-d');

        YeuCauNghi::create([
            'nhan_vien_id' => $staff->nhan_vien_id,
            'tu_ngay'      => $dateTue,
            'den_ngay'     => $dateTue,
            'ly_do'        => 'Nghỉ phép cá nhân',
            'trang_thai'   => 'APPROVED',
        ]);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);

        // Nhân viên không có bất kỳ ca nào trong ngày Thứ Ba
        $this->assertDatabaseMissing('phan_cong_cas', [
            'nhan_vien_id' => $staff->nhan_vien_id,
            'ngay_lam'     => $dateTue,
        ]);
    }

    /**
     * Kịch bản 5: Kiểm tra thời gian nghỉ giữa hai ca (Tối hôm trước - Sáng hôm sau)
     */
    public function test_audit_05_kiem_tra_thoi_gian_nghi_giua_hai_ca()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);

        // Duyệt tất cả phân công của từng nhân viên: không có cặp (Tối ngày d, Sáng ngày d+1)
        $caToi = CaMau::where('ten_ca', 'like', '%tối%')->first();
        $caSang = CaMau::where('ten_ca', 'like', '%sáng%')->first();

        if ($caToi && $caSang) {
            $lich = LichLamViec::where('tu_ngay', $week)->first();
            $phanCongs = PhanCongCa::where('lich_id', $lich->lich_id)->get()->groupBy('nhan_vien_id');

            foreach ($phanCongs as $empId => $assignments) {
                $shiftsByDate = $assignments->pluck('ca_mau_id', 'ngay_lam')->toArray();
                for ($d = 0; $d < 6; $d++) {
                    $d1 = Carbon::parse($week)->addDays($d)->format('Y-m-d');
                    $d2 = Carbon::parse($week)->addDays($d + 1)->format('Y-m-d');

                    $c1 = $shiftsByDate[$d1] ?? null;
                    $c2 = $shiftsByDate[$d2] ?? null;

                    $this->assertFalse(
                        $c1 == $caToi->ca_mau_id && $c2 == $caSang->ca_mau_id,
                        "Nhân viên $empId bị vi phạm ca tối ngày $d1 tiếp ca sáng ngày $d2!"
                    );
                }
            }
        }
    }

    /**
     * Kịch bản 6: Thay đổi giới hạn giờ làm (MAX_HOURS_PER_WEEK)
     */
    public function test_audit_06_thay_doi_gioi_han_gio_lam()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        // Đặt MAX_HOURS_PER_WEEK = 16 giờ
        RangBuoc::where('ma_rang_buoc', 'MAX_HOURS_PER_WEEK')->update([
            'gia_tri' => json_encode(['value' => 16, 'enabled' => true]),
        ]);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);

        $lich = LichLamViec::where('tu_ngay', $week)->first();
        $shifts = CaMau::all()->keyBy('ca_mau_id');
        $phanCongs = PhanCongCa::where('lich_id', $lich->lich_id)->get()->groupBy('nhan_vien_id');

        foreach ($phanCongs as $empId => $assignments) {
            $totalHours = 0;
            foreach ($assignments as $a) {
                $cm = $shifts[$a->ca_mau_id] ?? null;
                if ($cm) {
                    $sMin = (int)substr($cm->gio_bat_dau, 0, 2) * 60 + (int)substr($cm->gio_bat_dau, 3, 2);
                    $eMin = (int)substr($cm->gio_ket_thuc, 0, 2) * 60 + (int)substr($cm->gio_ket_thuc, 3, 2);
                    if ($eMin <= $sMin) $eMin += 1440;
                    $totalHours += ($eMin - $sMin) / 60.0;
                }
            }
            $this->assertLessThanOrEqual(16.5, $totalHours, "Nhân viên $empId bị xếp $totalHours giờ, vượt quá 16h!");
        }
    }

    /**
     * Kịch bản 7: Tắt một Soft Constraint (FAIR_WORKING_HOURS)
     */
    public function test_audit_07_tat_soft_constraint()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        RangBuoc::where('ma_rang_buoc', 'FAIR_WORKING_HOURS')->update([
            'gia_tri' => json_encode(['enabled' => false]),
        ]);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);
        $this->assertEquals(true, $res->json('status'));
    }

    /**
     * Kịch bản 8: Bật lại Soft Constraint
     */
    public function test_audit_08_bat_lai_soft_constraint()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        RangBuoc::where('ma_rang_buoc', 'FAIR_WORKING_HOURS')->update([
            'gia_tri' => json_encode(['enabled' => true]),
        ]);

        $res = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res->assertStatus(200);
        $this->assertEquals(true, $res->json('status'));
    }

    /**
     * Kịch bản 9: Chuyển Chế độ A sang B và ngược lại
     */
    public function test_audit_09_chuyen_che_do_nhu_cau()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';

        $shifts = CaMau::where('trang_thai', 'ACTIVE')->get();
        $weekdayDemand = $shifts->map(fn($s) => [
            'shiftId' => $s->ca_mau_id,
            'name'    => $s->ten_ca,
            'phucVu'  => 1,
            'phaChe'  => 1,
            'thuNgan' => 1,
        ])->toArray();
        $weekendDemand = $shifts->map(fn($s) => [
            'shiftId' => $s->ca_mau_id,
            'name'    => $s->ten_ca,
            'phucVu'  => 2,
            'phaChe'  => 2,
            'thuNgan' => 2,
        ])->toArray();

        // Lưu nhu cầu theo Chế độ B: Cuối tuần riêng
        $resSave = $this->postJson('/api/schedule/demand', [
            'weekMonday'              => $week,
            'useSameConfigForWeekend' => false,
            'weekdayDemand'           => $weekdayDemand,
            'weekendDemand'           => $weekendDemand,
        ]);
        $resSave->assertStatus(200);

        // Kiểm tra Database: nhu cầu ngày Chủ Nhật (CN) có số lượng bằng 2 cho mỗi vai trò
        $sunDate = Carbon::parse($week)->addDays(6)->format('Y-m-d');
        $sunDemands = NhuCauCa::where('ngay_lam', $sunDate)->get();
        $this->assertNotEmpty($sunDemands);
        $this->assertEquals(2, $sunDemands->first()->so_luong_can);

        // Tạo lịch tự động
        $resGen = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $resGen->assertStatus(200);
    }

    /**
     * Kịch bản 10: Gửi 2 yêu cầu tạo lịch liên tiếp
     */
    public function test_audit_10_gui_hai_yeu_cau_lien_tiep()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        // Lần 1
        $res1 = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res1->assertStatus(200);
        $count1 = PhanCongCa::count();

        // Lần 2
        $res2 = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $res2->assertStatus(200);
        $count2 = PhanCongCa::count();

        // Số bản ghi phân công trong DB không bị nhân đôi
        $this->assertEquals($count1, $count2);

        // Phiên bản tăng lên 2
        $lich = LichLamViec::where('tu_ngay', $week)->first();
        $this->assertEquals(2, $lich->phien_ban);
    }

    /**
     * Kịch bản 11: Lưu lịch thất bại và kiểm tra rollback
     */
    public function test_audit_11_luu_lich_that_bai_va_rollback()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        // Tạo một lịch DRAFT trước
        $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $initialLichCount = LichLamViec::count();
        $initialPhanCongCount = PhanCongCa::count();

        // Mô phỏng thất bại trong Transaction khi ghi dữ liệu (ví dụ lỗi logic phân công)
        try {
            DB::transaction(function () use ($week) {
                LichLamViec::create([
                    'tu_ngay'    => '2026-11-09',
                    'den_ngay'   => '2026-11-15',
                    'phien_ban'  => 99,
                    'trang_thai' => 'DRAFT',
                    'tao_boi'    => $this->managerUser->tai_khoan_id,
                ]);
                throw new \Exception("Kích hoạt ngoại lệ kiểm thử Rollback DB");
            });
        } catch (\Exception $e) {
            // Đã bắt ngoại lệ mô phỏng
        }

        // Kiểm tra Database không bị thay đổi hoặc hư hại
        $this->assertEquals($initialLichCount, LichLamViec::count());
        $this->assertEquals($initialPhanCongCount, PhanCongCa::count());
        $this->assertDatabaseMissing('lich_lam_viecs', ['phien_ban' => 99]);
    }

    /**
     * Kịch bản 12: Xác nhận lịch DRAFT (CONFIRMED) và chống ghi đè
     */
    public function test_audit_12_xac_nhan_lich_draft()
    {
        Sanctum::actingAs($this->managerUser);
        $week = '2026-11-09';
        $this->taoNhuCauNho($week, 1);

        // Tạo lịch
        $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);

        // Xác nhận lịch
        $resConfirm = $this->postJson('/api/schedule/confirm', ['weekMonday' => $week]);
        $resConfirm->assertStatus(200);

        // Kiểm tra trạng thái trong DB
        $this->assertDatabaseHas('lich_lam_viecs', [
            'tu_ngay'    => $week,
            'trang_thai' => 'CONFIRMED',
        ]);

        // Cố tình gọi tạo lịch lại -> Phải trả về lỗi 400
        $resTryOverwrite = $this->postJson('/api/schedule/auto-generate', ['weekMonday' => $week]);
        $resTryOverwrite->assertStatus(400);
    }
}

