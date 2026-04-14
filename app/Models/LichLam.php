<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichLam extends Model
{
    protected $table = 'lich_lam';

    public function caLam()
    {
        return $this->belongsTo(CaLam::class, 'ca_lam_id');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'nhanvien_id');
    }
}