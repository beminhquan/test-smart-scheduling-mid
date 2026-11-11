<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NhuCauCa extends Model
{
    use HasFactory;

    protected $table = 'nhu_cau_cas';

    protected $primaryKey = 'nhu_cau_ca_id';

    public $timestamps = true;

    protected $fillable = [
        'ca_mau_id',
        'ngay_lam',
        'so_luong_can',
        'role_tag_id',
        'ghi_chu',
    ];

    public function caMau()
    {
        return $this->belongsTo(CaMau::class, 'ca_mau_id', 'ca_mau_id');
    }
}
