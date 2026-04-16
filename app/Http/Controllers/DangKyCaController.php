<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class DangKyCaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|integer',
            'lich_lam_id' => 'required|integer',
        ]);

        try {

            // ================================
            // 🔥 CHECK 0: CHỨC VỤ
            // ================================
            $nhanVien = DB::table('thongtinnhanvien')
                ->where('id', $request->nhanvien_id)
                ->first();

            if (!$nhanVien) {
                return response()->json([
                    'message' => 'Nhân viên không tồn tại'
                ], 404);
            }

            if ($nhanVien->chuc_vu !== 'Full') {
                return response()->json([
                    'message' => 'Chỉ nhân viên Full mới được đăng ký ca'
                ], 403);
            }

            // 🔥 Lấy thông tin lịch làm
            $lichLam = DB::table('lich_lam')
                ->where('id', $request->lich_lam_id)
                ->first();

            if (!$lichLam) {
                return response()->json([
                    'message' => 'Lịch làm không tồn tại'
                ], 404);
            }
            // ================================
            // ❌ CHECK: Không cho đăng ký quá khứ & hôm nay
            // ================================
            $ngayCa = \Carbon\Carbon::parse($lichLam->ngay)->startOfDay();
            $homNay = \Carbon\Carbon::today();

            if ($ngayCa->lte($homNay)) {
                return response()->json([
                    'message' => 'Chỉ được đăng ký ca từ ngày mai trở đi'
                ], 400);
            }

            // ================================
            // ❌ CHECK 1: Đã đăng ký ca trong ngày chưa
            // ================================
            $daDangKyTrongNgay = DB::table('dang_ky_ca as dk')
                ->join('lich_lam as ll', 'dk.lich_lam_id', '=', 'll.id')
                ->where('dk.nhanvien_id', $request->nhanvien_id)
                ->where('ll.ngay', $lichLam->ngay)
                ->exists();

            if ($daDangKyTrongNgay) {
                return response()->json([
                    'message' => 'Bạn đã đăng ký ca trong ngày này rồi'
                ], 400);
            }

            // ================================
            // ❌ CHECK 2: Đã đủ người chưa
            // ================================
            $soLuongDangKy = DB::table('dang_ky_ca')
                ->where('lich_lam_id', $request->lich_lam_id)
                ->count();

            if ($soLuongDangKy >= $lichLam->max_nhan_vien) {
                return response()->json([
                    'message' => 'Ca này đã đủ nhân viên'
                ], 400);
            }

            // ================================
            // ✅ INSERT đăng ký
            // ================================
            DB::table('dang_ky_ca')->insert([
                'nhanvien_id' => $request->nhanvien_id,
                'lich_lam_id' => $request->lich_lam_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Đăng ký ca thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function huyDangKy(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|integer',
            'lich_lam_id' => 'required|integer',
        ]);

        try {
            // 🔥 1. Kiểm tra có đăng ký chưa
            $dangKy = DB::table('dang_ky_ca')
                ->where('nhanvien_id', $request->nhanvien_id)
                ->where('lich_lam_id', $request->lich_lam_id)
                ->first();

            if (!$dangKy) {
                return response()->json([
                    'message' => 'Bạn chưa đăng ký ca này'
                ], 400);
            }

            // 🔥 2. Xóa đăng ký
            DB::table('dang_ky_ca')
                ->where('nhanvien_id', $request->nhanvien_id)
                ->where('lich_lam_id', $request->lich_lam_id)
                ->delete();

            return response()->json([
                'message' => 'Hủy đăng ký ca thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkTrangThai(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|integer',
            'lich_lam_id' => 'required|integer',
        ]);

        try {
            // 🔥 Lấy lịch làm
            $lichLam = DB::table('lich_lam')
                ->where('id', $request->lich_lam_id)
                ->first();

            if (!$lichLam) {
                return response()->json([
                    'message' => 'Lịch làm không tồn tại'
                ], 404);
            }

            // ================================
            // ✅ CHECK 1: Đã đăng ký chưa
            // ================================
            $daDangKy = DB::table('dang_ky_ca')
                ->where('nhanvien_id', $request->nhanvien_id)
                ->where('lich_lam_id', $request->lich_lam_id)
                ->exists();

            // ================================
            // ✅ CHECK 2: Ca đã đầy chưa
            // ================================
            $soLuong = DB::table('dang_ky_ca')
                ->where('lich_lam_id', $request->lich_lam_id)
                ->count();

            $daDay = $soLuong >= $lichLam->max_nhan_vien;

            // ================================
            // 🎯 TRẠNG THÁI
            // ================================
            return response()->json([
                'da_dang_ky' => $daDangKy,
                'da_day' => $daDay,
                'so_luong_hien_tai' => $soLuong,
                'max_nhan_vien' => $lichLam->max_nhan_vien,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function dangKyCaPartTime(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|integer',
            'lich_lam_id' => 'required|integer',
        ]);

        try {
            // 🔥 1. Lấy lịch làm
            $lichLam = DB::table('lich_lam')
                ->where('id', $request->lich_lam_id)
                ->first();

            if (!$lichLam) {
                return response()->json([
                    'message' => 'Lịch làm không tồn tại'
                ], 404);
            }
            $ngayCa = \Carbon\Carbon::parse($lichLam->ngay)->startOfDay();
            if ($ngayCa->isPast() || $ngayCa->isToday()) {
                return response()->json([
                    'message' => 'Không thể đăng ký ca trong quá khứ hoặc hôm nay'
                ], 400);
            }

            // ================================
            // ❌ CHECK 1: Đã đăng ký ca trong ngày chưa
            // ================================
            $daDangKyTrongNgay = DB::table('dang_ky_ca as dk')
                ->join('lich_lam as ll', 'dk.lich_lam_id', '=', 'll.id')
                ->where('dk.nhanvien_id', $request->nhanvien_id)
                ->whereDate('ll.ngay', $lichLam->ngay)
                ->exists();

            if ($daDangKyTrongNgay) {
                return response()->json([
                    'message' => 'Bạn chỉ được đăng ký 1 ca trong ngày'
                ], 400);
            }

            // ================================
            // ❌ CHECK 2: Đã đăng ký ca này chưa
            // ================================
            $daDangKyCa = DB::table('dang_ky_ca')
                ->where('nhanvien_id', $request->nhanvien_id)
                ->where('lich_lam_id', $request->lich_lam_id)
                ->exists();

            if ($daDangKyCa) {
                return response()->json([
                    'message' => 'Bạn đã đăng ký ca này rồi'
                ], 400);
            }

            // ================================
            // ❌ CHECK 3: Ca đã full chưa
            // ================================
            $soLuongDangKy = DB::table('dang_ky_ca')
                ->where('lich_lam_id', $request->lich_lam_id)
                ->count();

            if ($soLuongDangKy >= $lichLam->max_nhan_vien) {
                return response()->json([
                    'message' => 'Ca này đã đủ người'
                ], 400);
            }

            // ================================
            // ✅ INSERT
            // ================================
            DB::table('dang_ky_ca')->insert([
                'nhanvien_id' => $request->nhanvien_id,
                'lich_lam_id' => $request->lich_lam_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Đăng ký ca thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
