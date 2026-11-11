<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChamCong extends Model
{
    use HasFactory;

    protected $table = 'cham_congs';

    public $timestamps = false;

    protected $fillable = [
        'phan_cong_ca_id',
        'gio_vao',
        'gio_ra',
        'vi_tri_vao',
        'vi_tri_ra',
        'trang_thai',
    ];
}
