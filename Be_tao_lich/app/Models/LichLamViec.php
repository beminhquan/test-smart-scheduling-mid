<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LichLamViec extends Model
{
    use HasFactory;

    protected $table = 'lich_lam_viecs';
    protected $primaryKey = 'lich_id';

    public $timestamps = true;

    protected $fillable = [
        'tu_ngay',
        'den_ngay',
        'phien_ban',
        'trang_thai',
        'tao_boi',
    ];

    public function phanCongCas()
    {
        return $this->hasMany(PhanCongCa::class, 'lich_id', 'lich_id');
    }
}
