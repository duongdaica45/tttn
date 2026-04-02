<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhanVien extends Model
{
    protected $table = 'thongtinnhanvien';

    public $timestamps = false; // 🔥 QUAN TRỌNG

    protected $fillable = [
        'ten_nhan_vien',
        'chuc_vu',
        'luong_co_ban',
        'email',
        'password',
        'ca_lam_id'
    ];

    protected $hidden = ['password'];
}
