<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NgayMo extends Model
{
    use HasFactory;

    // Tên bảng chính xác trong SQL
    protected $table = 'ngay_mo';

    /**
     * Các trường có thể gán dữ liệu hàng loạt (Mass Assignable)
     * id, ngay, mo_tao_ca, created_at, updated_at
     */
    protected $fillable = [
        'ngay',
        'mo_tao_ca',
    ];

    /**
     * Ép kiểu dữ liệu (Casting)
     * mo_tao_ca là kiểu boolean (f/t trong PostgreSQL)
     * ngay nên được ép kiểu sang date để Carbon xử lý dễ hơn
     */
    protected $casts = [
        'ngay' => 'date',
        'mo_tao_ca' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}