<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoleTag extends Model
{
    use HasFactory;

    protected $table = 'role_tags';

    protected $primaryKey = 'role_tag_id';

    public $timestamps = true;

    protected $fillable = [
        'ten_role',
        'mo_ta',
        'trang_thai',
    ];

    public function nhanViens()
    {
        return $this->hasMany(NhanVien::class, 'role_tag_id', 'role_tag_id');
    }
}
