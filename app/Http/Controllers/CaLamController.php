<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CaLamController extends Controller
{
    public function taoCaLam(Request $request)
    {
        $request->validate([
            'ngay' => 'required|date',
            'ca_lam_id' => 'required|exists:ca_lam,id',
            'so_luong' => 'required|integer|min:1'
        ]);

        $ngay = Carbon::parse($request->ngay);

        // 🔥 1. Check ngày đã mở chưa
        $isOpen = DB::table('ngay_mo')
            ->whereDate('ngay', $ngay)
            ->where('mo_tao_ca', 1)
            ->exists();

        if (!$isOpen) {
            return response()->json([
                'message' => 'Ngày này chưa được mở'
            ], 400);
        }

        // 🔥 2. Lấy thông tin ca
        $ca = DB::table('ca_lam')
            ->where('id', $request->ca_lam_id)
            ->first();

        // 🔥 3. Đếm số ca đã tạo trong ngày
        $count = DB::table('lich_lam')
            ->where('ca_lam_id', $request->ca_lam_id)
            ->whereDate('ngay', $ngay)
            ->count();

        // 🔥 4. Check max nhân viên
        if ($count + $request->so_luong > $ca->max_nhan_vien) {
            return response()->json([
                'message' => 'Vượt quá số lượng nhân viên cho phép'
            ], 400);
        }

        // 🔥 5. Tạo nhiều slot ca (chưa có nhân viên)
        for ($i = 0; $i < $request->so_luong; $i++) {
            DB::table('lich_lam')->insert([
                'nhanvien_id' => null, // chưa có người đăng ký
                'ca_lam_id' => $request->ca_lam_id,
                'ngay' => $ngay->toDateString(),
                'trang_thai' => 'trong', // hoặc 0
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Tạo ca thành công'
        ]);
    }
}
