<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TongLuongExport implements FromCollection, WithHeadings
{
    protected $tuNgay;
    protected $denNgay;

    public function __construct($tuNgay, $denNgay)
    {
        $this->tuNgay = $tuNgay;
        $this->denNgay = $denNgay;
    }

    public function collection()
    {
        $data = DB::table('diemdanh')
            ->join('thongtinnhanvien', 'diemdanh.nhanvien_id', '=', 'thongtinnhanvien.id')
            ->whereBetween('diemdanh.ngay', [$this->tuNgay, $this->denNgay])
            ->whereNotNull('diemdanh.gio_ra')
            ->select(
                'thongtinnhanvien.ten_nhan_vien',
                'diemdanh.ngay',
                'diemdanh.gio_vao',
                'diemdanh.gio_ra',
                'diemdanh.so_gio',
                'thongtinnhanvien.chuc_vu',
                'thongtinnhanvien.luong_co_ban',
                DB::raw("
            CASE
                WHEN LOWER(thongtinnhanvien.chuc_vu) = 'part'
                THEN IFNULL(diemdanh.so_gio,0) * thongtinnhanvien.luong_co_ban
                ELSE thongtinnhanvien.luong_co_ban * 8 + 20000
            END AS luong_ngay
        ")
            )
            ->distinct() // loại bỏ duplicate
            ->get();


        return $data;
    }

    public function headings(): array
    {
        return [
            'Tên nhân viên',
            'Ngày',
            'Giờ vào',
            'Giờ ra',
            'Số giờ',
            'Chức vụ',
            'Lương cơ bản',
            'Lương ngày'
        ];
    }
}
