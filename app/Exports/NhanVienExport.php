<?php

namespace App\Exports;

use App\Models\NhanVien;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NhanVienExport implements FromCollection, WithHeadings
{
    // Lấy dữ liệu từ model
    public function collection()
    {
        return NhanVien::select(
            'id',
            'ten_nhan_vien',
            'chuc_vu',
            'so_gio_lam',
            'luong_co_ban',
            'khoan_giam_tru',
            'luong_thuc_nhan',
            'email'
        )->get();
    }

    // Đặt tiêu đề cột cho Excel
    public function headings(): array
    {
        return [
            'ID',
            'Họ và tên',
            'Chức vụ',
            'Số giờ làm',
            'Lương cơ bản',
            'Khoản giảm trừ',
            'Lương thực nhận',
            'Email',
        ];
    }
}
