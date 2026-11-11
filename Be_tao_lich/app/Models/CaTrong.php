<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaTrong extends Model
{
    use HasFactory;

    protected $table = 'ca_trongs';
    protected $primaryKey = 'ca_trong_id';

    public $timestamps = true;

    protected $fillable = [
        'phan_cong_ca_id',
        'lich_id',
        'ca_mau_id',
        'ngay_lam',
        'role_tag_id',
        'so_luong_can',
        'so_luong_da_nhan',
        'tien_thuong',
        'nguon_goc',
        'yeu_cau_nghi_id',
        'han_dang_ky',
        'mo_ta',
        'trang_thai',
        'nguoi_mo_id',
    ];

    protected $casts = [
        'han_dang_ky'      => 'datetime',
        'ngay_lam'         => 'date:Y-m-d',
        'so_luong_can'     => 'integer',
        'so_luong_da_nhan' => 'integer',
        'tien_thuong'      => 'float',
    ];

    public function phanCongCa()
    {
        return $this->belongsTo(PhanCongCa::class, 'phan_cong_ca_id', 'phan_cong_ca_id');
    }

    public function caMau()
    {
        return $this->belongsTo(CaMau::class, 'ca_mau_id', 'ca_mau_id');
    }

    public function roleTag()
    {
        return $this->belongsTo(RoleTag::class, 'role_tag_id', 'role_tag_id');
    }

    public function yeuCauNghi()
    {
        return $this->belongsTo(YeuCauNghi::class, 'yeu_cau_nghi_id', 'yeu_cau_nghi_id');
    }

    public function lichLamViec()
    {
        return $this->belongsTo(LichLamViec::class, 'lich_id', 'lich_id');
    }

    public function nguoiMo()
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoi_mo_id', 'tai_khoan_id');
    }

    public function dangKyCaTrongs()
    {
        return $this->hasMany(DangKyCaTrong::class, 'ca_trong_id', 'ca_trong_id');
    }
}
