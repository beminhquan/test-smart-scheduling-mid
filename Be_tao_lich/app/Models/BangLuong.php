<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BangLuong extends Model
{
    use HasFactory;

    protected $table = 'bang_luongs';

    public $timestamps = false;

    protected $fillable = [
        'nhan_vien_id',
        'tu_ngay',
        'den_ngay',
        'tong_gio_lam',
        'muc_luong_gio',
        'tong_tien',
        'trang_thai',
    ];
}
