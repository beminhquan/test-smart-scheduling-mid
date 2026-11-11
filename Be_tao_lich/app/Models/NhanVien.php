<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NhanVien extends Model
{
    use HasFactory;

    protected $table = 'nhan_viens';

    protected $primaryKey = 'nhan_vien_id';

    public $timestamps = true;

    protected $fillable = [
        'hinh_anh',
        'role_tag_id',
        'tai_khoan_id',
        'ho_ten',
        'so_dien_thoai',
        'ngay_sinh',
        'loai_hop_dong',
        'muc_luong_gio',
        'trang_thai',
    ];

    public function roleTag()
    {
        return $this->belongsTo(RoleTag::class, 'role_tag_id', 'role_tag_id');
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id', 'tai_khoan_id');
    }

    public function tuanDangKys()
    {
        return $this->hasMany(TuanDangKy::class, 'nhan_vien_id', 'nhan_vien_id');
    }

    /**
     * Định dạng mã nhân viên chuẩn NVxxx từ ID tự tăng của Database
     */
    public function getMaNhanVienAttribute(): string
    {
        return 'NV' . str_pad($this->nhan_vien_id, 3, '0', STR_PAD_LEFT);
    }
}
