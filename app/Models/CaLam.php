<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaLam extends Model
{
    use HasFactory;

    // Tên bảng trong SQL của bạn là ca_lam
    protected $table = 'ca_lam';

    // Vì file SQL không có timestamps (created_at, updated_at) cho bảng này
    public $timestamps = false;

    protected $fillable = [
        'ten_ca',
        'gio_bat_dau',
        'gio_ket_thuc',
        'so_gio_quy_dinh',
        'max_nhan_vien'
    ];

    // Quan hệ: Một ca làm có thể có nhiều lịch làm việc
    public function lichLams()
    {
        return $this->hasMany(LichLam::class, 'ca_lam_id');
    }
}