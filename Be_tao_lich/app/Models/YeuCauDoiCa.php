<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class YeuCauDoiCa extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_doi_cas';
    protected $primaryKey = 'doi_ca_id';
    public $timestamps = true;

    protected $fillable = [
        'nguoi_gui_id',
        'nguoi_nhan_id',
        'ca_gui_id',
        'ca_nhan_id',
        'ly_do',
        'trang_thai',
        'thoi_gian_phan_hoi',
        'nguoi_duyet_id',
        'thoi_gian_duyet',
    ];

    public function nguoiGui()
    {
        return $this->belongsTo(NhanVien::class, 'nguoi_gui_id', 'nhan_vien_id');
    }

    public function nguoiNhan()
    {
        return $this->belongsTo(NhanVien::class, 'nguoi_nhan_id', 'nhan_vien_id');
    }

    public function caGui()
    {
        return $this->belongsTo(PhanCongCa::class, 'ca_gui_id', 'phan_cong_ca_id');
    }

    public function caNhan()
    {
        return $this->belongsTo(PhanCongCa::class, 'ca_nhan_id', 'phan_cong_ca_id');
    }

    public function nguoiDuyet()
    {
        return $this->belongsTo(NhanVien::class, 'nguoi_duyet_id', 'nhan_vien_id');
    }
}
