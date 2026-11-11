<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhanCongCa extends Model
{
    use HasFactory;

    protected $table = 'phan_cong_cas';

    protected $primaryKey = 'phan_cong_ca_id';

    public $timestamps = true;

    protected $fillable = [
        'lich_id',
        'nhu_cau_ca_id',
        'nhan_vien_id',
        'ca_mau_id',
        'ngay_lam',
        'trang_thai',
        'xac_nhan',
    ];

    public function caMau()
    {
        return $this->belongsTo(CaMau::class, 'ca_mau_id', 'ca_mau_id');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'nhan_vien_id', 'nhan_vien_id');
    }

    public function lichLamViec()
    {
        return $this->belongsTo(LichLamViec::class, 'lich_id', 'lich_id');
    }

    public function nhuCauCa()
    {
        return $this->belongsTo(NhuCauCa::class, 'nhu_cau_ca_id', 'nhu_cau_ca_id');
    }
}
