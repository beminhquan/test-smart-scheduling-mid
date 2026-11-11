<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TuanDangKy extends Model
{
    use HasFactory;

    protected $table = 'tuan_dang_kis';

    protected $primaryKey = 'tuan_dang_ky_id';

    public $timestamps = true;

    protected $fillable = [
        'nhan_vien_id',
        'tu_ngay',
        'den_ngay',
        'trang_thai',
        'thoi_gian_gui',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'nhan_vien_id', 'nhan_vien_id');
    }

    public function dangKyCaKhaDungs()
    {
        return $this->hasMany(DangKyCaKhaDung::class, 'tuan_dang_ky_id', 'tuan_dang_ky_id');
    }
}
