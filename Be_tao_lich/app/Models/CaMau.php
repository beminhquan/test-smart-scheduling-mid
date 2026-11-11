<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaMau extends Model
{
    use HasFactory;

    protected $table = 'ca_maus';

    protected $primaryKey = 'ca_mau_id';

    public $timestamps = true;

    protected $fillable = [
        'ten_ca',
        'gio_bat_dau',
        'gio_ket_thuc',
        'he_so_luong',
        'trang_thai',
    ];

    protected $casts = [
        'he_so_luong' => 'float',
    ];

    /**
     * Quan hệ với Nhu cầu ca
     */
    public function nhuCauCas()
    {
        return $this->hasMany(NhuCauCa::class, 'ca_mau_id', 'ca_mau_id');
    }

    /**
     * Quan hệ với Phân công ca
     */
    public function phanCongCas()
    {
        return $this->hasMany(PhanCongCa::class, 'ca_mau_id', 'ca_mau_id');
    }

    /**
     * Quan hệ với Đăng ký ca khả dụng của nhân viên
     */
    public function dangKyCaKhaDungs()
    {
        return $this->hasMany(DangKyCaKhaDung::class, 'ca_mau_id', 'ca_mau_id');
    }

    /**
     * Chuyển chuỗi HH:mm hoặc HH:mm:ss sang phút
     */
    public static function timeToMinutes(?string $timeStr): int
    {
        if (empty($timeStr)) {
            return 0;
        }
        $parts = explode(':', $timeStr);
        $hours = isset($parts[0]) ? (int)$parts[0] : 0;
        $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
        return ($hours * 60) + $minutes;
    }

    /**
     * Tính thời lượng ca (phút), hỗ trợ ca qua đêm (ví dụ 22:00 -> 06:00 = 8h = 480 phút)
     */
    public function getThoiLuongPhutAttribute(): int
    {
        $start = self::timeToMinutes($this->gio_bat_dau);
        $end = self::timeToMinutes($this->gio_ket_thuc);
        if ($start === $end) {
            return 0;
        }
        return ($end - $start + 1440) % 1440;
    }

    /**
     * Kiểm tra ca có phải là ca qua đêm không
     */
    public function getIsOvernightAttribute(): bool
    {
        $start = self::timeToMinutes($this->gio_bat_dau);
        $end = self::timeToMinutes($this->gio_ket_thuc);
        return $end < $start;
    }

    /**
     * Định dạng thời lượng (ví dụ 360 phút -> "6h", 330 phút -> "5h30")
     */
    public function getThoiLuongFormattedAttribute(): string
    {
        $min = $this->thoi_luong_phut;
        $hours = floor($min / 60);
        $remainingMin = $min % 60;
        return $hours . 'h' . ($remainingMin ? str_pad((string)$remainingMin, 2, '0', STR_PAD_LEFT) : '');
    }

    /**
     * Kiểm tra ca đã được sử dụng trong hệ thống chưa (nhu cầu, phân công, đăng ký)
     */
    public function isUsedInSchedule(): bool
    {
        return $this->nhuCauCas()->exists()
            || $this->phanCongCas()->exists()
            || $this->dangKyCaKhaDungs()->exists();
    }
}
