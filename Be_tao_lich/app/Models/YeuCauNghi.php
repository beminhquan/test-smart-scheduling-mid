<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class YeuCauNghi extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_nghis';
    protected $primaryKey = 'yeu_cau_nghi_id';

    public $timestamps = false;

    protected $fillable = [
        'nhan_vien_id',
        'tu_ngay',
        'den_ngay',
        'ly_do',
        'trang_thai',
        'nguoi_duyet_id',
        'thoi_gian_duyet',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'nhan_vien_id', 'nhan_vien_id');
    }
}
