<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DangKyCaKhaDung extends Model
{
    use HasFactory;

    protected $table = 'dang_ky_ca_kha_dungs';

    protected $primaryKey = 'dang_ky_kha_dung_id';

    public $timestamps = true;

    protected $fillable = [
        'tuan_dang_ky_id',
        'ca_mau_id',
        'thu_trong_tuan',
        'trang_thai',
    ];

    public function caMau()
    {
        return $this->belongsTo(CaMau::class, 'ca_mau_id', 'ca_mau_id');
    }

    public function tuanDangKy()
    {
        return $this->belongsTo(TuanDangKy::class, 'tuan_dang_ky_id', 'tuan_dang_ky_id');
    }
}
