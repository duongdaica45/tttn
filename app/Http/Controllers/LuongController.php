<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LuongController extends Controller
{
    public function tinhLuong(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|exists:thongtinnhanvien,id',
            'thang' => 'required|integer',
            'nam' => 'required|integer',
        ]);
        $nhanvien_id = $request->nhanvien_id;
        $thang = $request->thang;
        $nam = $request->nam;
        $nv = DB::table('thongtinnhanvien')
            ->where('id', $nhanvien_id)
            ->first();
        $diemdanh = DB::table('diemdanh')
            ->where('nhanvien_id', $nhanvien_id)
            ->whereMonth('ngay', $thang)
            ->whereYear('ngay', $nam)
            ->get();
        if ($diemdanh->isEmpty()) {
            return response()->json([
                'message' => 'Không có dữ liệu điểm danh'
            ], 400);
        }
        // tổng giờ
        $tong_gio = $diemdanh->sum('so_gio');
        // số ngày làm
        $so_ngay_lam = $diemdanh->count();
        // tổng ca làm trong tháng
        $tong_ca = DB::table('dang_ky_ca')
            ->join('lich_lam', 'dang_ky_ca.lich_lam_id', '=', 'lich_lam.id')
            ->where('dang_ky_ca.nhanvien_id', $nhanvien_id)
            ->whereMonth('lich_lam.ngay', $thang)
            ->whereYear('lich_lam.ngay', $nam)
            ->count();
        // nghỉ phép
        $nghi_phep = DB::table('don_xin_nghi')
            ->where('nhan_vien_id', $nhanvien_id)
            ->where('trang_thai', 'chap_nhan')
            ->where(function ($query) use ($thang, $nam) {
                $query->whereMonth('tu_ngay', $thang)
                    ->orWhereMonth('den_ngay', $thang);
            })
            ->count();
        // nghỉ không phép
        $so_ngay_nghi_khong_phep = max(0, $tong_ca - $so_ngay_lam - $nghi_phep);
        // giảm trừ
        $khoan_giam_tru = $so_ngay_nghi_khong_phep * 100000;
        $luong = 0;
        if ($nv->chuc_vu == 'Part') {

            $luong = $nv->luong_co_ban * $tong_gio;
        } else {
            if ($so_ngay_lam >= 26) {
                $luong = ($nv->luong_co_ban + 25000) * $tong_gio;
            } else {
                $luong = ($nv->luong_co_ban * $tong_gio);
            }
            $luong -= $khoan_giam_tru;
        }

        // chống âm
        $luong = max(0, $luong);

        DB::table('bang_luong')->updateOrInsert(
            [
                'nhanvien_id' => $nhanvien_id,
                'thang' => $thang,
                'nam' => $nam
            ],
            [
                'so_gio_lam' => $tong_gio,
                'so_ngay_lam' => $so_ngay_lam,
                'so_ngay_nghi_khong_phep' => $so_ngay_nghi_khong_phep,
                'khoan_giam_tru' => $khoan_giam_tru,
                'luong_thuc_nhan' => $luong,
                'created_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Tính lương thành công',
            'luong' => $luong,
            'tong_gio' => $tong_gio,
            'so_ngay_lam' => $so_ngay_lam,
            'nghi_khong_phep' => $so_ngay_nghi_khong_phep
        ]);
    }
    public function luongNam(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required',
            'nam' => 'required|integer'
        ]);

        $data = DB::table('bang_luong')
            ->where('nhanvien_id', $request->nhanvien_id)
            ->where('nam', $request->nam)
            ->orderBy('thang')
            ->get(['thang', 'luong_thuc_nhan']);
        return response()->json($data);
    }
    public function lichSuChamCong(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|exists:thongtinnhanvien,id',
            'thang' => 'required|integer',
            'nam' => 'required|integer',
        ]);

        $nhanvien_id = $request->nhanvien_id;
        $thang = $request->thang;
        $nam = $request->nam;

        $data = DB::table('luong_ngay')
            ->where('nhanvien_id', $nhanvien_id)
            ->whereMonth('ngay', $thang)
            ->whereYear('ngay', $nam)
            ->orderBy('ngay', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'Không có dữ liệu tháng này'
            ], 404);
        }

        return response()->json([
            'message' => 'Lấy dữ liệu thành công',
            'tong_luong' => $data->sum('luong'),
            'tong_gio' => $data->sum('so_gio'),
            'data' => $data
        ]);
    }
}
