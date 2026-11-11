<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DangKyCaTrong extends Model
{
    use HasFactory;

    protected $table = 'dang_ky_ca_trongs';
    protected $primaryKey = 'dang_ky_ca_trong_id';

    public $timestamps = true;

    protected $fillable = [
        'ca_trong_id',
        'nhan_vien_id',
        'thoi_gian_dang_ky',
        'trang_thai',
        'ghi_chu',
        'ly_do_tu_choi',
    ];

    protected $casts = [
        'thoi_gian_dang_ky' => 'datetime',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'nhan_vien_id', 'nhan_vien_id');
    }

    public function caTrong()
    {
        return $this->belongsTo(CaTrong::class, 'ca_trong_id', 'ca_trong_id');
    }
}
