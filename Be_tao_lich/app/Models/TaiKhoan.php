<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class TaiKhoan extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tai_khoans';

    protected $primaryKey = 'tai_khoan_id';

    public $timestamps = true;

    protected $fillable = [
        'email',
        'mat_khau',
        'vai_tro',
        'trang_thai',
        'must_change_password',
    ];

    protected $casts = [
        'must_change_password' => 'boolean',
    ];

    public function nhanVien()
    {
        return $this->hasOne(NhanVien::class, 'tai_khoan_id', 'tai_khoan_id');
    }

    protected $hidden = [
        'mat_khau',
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }
}
