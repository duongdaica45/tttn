<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DiemDanhController extends Controller
{
    public function checkIn(Request $request)
    {
        $request->validate([
            'nhanvien_id' => 'required|exists:thongtinnhanvien,id'
        ]);

        $nhanvien_id = $request->nhanvien_id;
        $today = Carbon::today();

        // kiểm tra ngày mở
        $ngayMo = DB::table('ngay_mo')
            ->whereDate('ngay', $today)
            ->where('mo_tao_ca', true)
            ->first();

        if (!$ngayMo) {
            return response()->json([
                'message' => 'Hôm nay không cho điểm danh!'
            ], 400);
        }

        // kiểm tra lịch làm
        $lich = DB::table('dang_ky_ca')
            ->join('lich_lam', 'dang_ky_ca.lich_lam_id', '=', 'lich_lam.id')
            ->where('dang_ky_ca.nhanvien_id', $nhanvien_id)
            ->where('dang_ky_ca.trang_thai', 'dang_ky') // hoặc chap_nhan
            ->whereDate('lich_lam.ngay', $today)
            ->first();

        if (!$lich) {
            return response()->json([
                'message' => 'Bạn không có ca làm hôm nay!'
            ], 400);
        }

        // kiểm tra ca làm
        $ca = DB::table('ca_lam')
            ->where('id', $lich->ca_lam_id)
            ->first();

        $now = Carbon::now();

        if ($now->lt(Carbon::parse($ca->gio_bat_dau)->subMinutes(30))) {
            return response()->json([
                'message' => 'Chưa đến giờ check-in!'
            ], 400);
        }

        // kiểm tra đã check-in
        $exists = DB::table('diemdanh')
            ->where('nhanvien_id', $nhanvien_id)
            ->whereDate('ngay', $today)
            ->first();

        if ($exists) {
            return response()->json([
                'message' => 'Hôm nay bạn đã check-in rồi!'
            ], 400);
        }

        DB::table('diemdanh')->insert([
            'nhanvien_id' => $nhanvien_id,
            'ngay' => $today,
            'gio_vao' => $now,
        ]);

        return response()->json([
            'message' => 'Chúc bạn làm việc một ngày vui vẻ! Check-in thành công.'
        ]);
    }

    public function checkOut(Request $request)
    {
        $nhanvien_id = $request->nhanvien_id;
        $today = Carbon::today();

        $record = DB::table('diemdanh')
            ->where('nhanvien_id', $nhanvien_id)
            ->whereDate('ngay', $today)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Bạn chưa check-in!'
            ], 400);
        }

        if ($record->gio_ra) {
            return response()->json([
                'message' => 'Hôm nay bạn đã check-out rồi!'
            ], 400);
        }

        $gio_ra = Carbon::now();
        $gio_vao = Carbon::parse($record->gio_vao);

        // check lỗi
        if ($gio_ra->lessThan($gio_vao)) {
            return response()->json([
                'message' => 'Lỗi thời gian (gio_ra < gio_vao)'
            ], 400);
        }

        // tính giờ
        $so_gio = $gio_vao->diffInSeconds($gio_ra) / 3600;
        $so_gio = round($so_gio, 2);

        // cập nhật điểm danh
        DB::table('diemdanh')
            ->where('id', $record->id)
            ->update([
                'gio_ra' => $gio_ra,
                'so_gio' => $so_gio
            ]);

        // =========================
        // 🔥 TÍNH LƯƠNG NGÀY
        // =========================

        $nv = DB::table('thongtinnhanvien')
            ->where('id', $nhanvien_id)
            ->first();

        $luong_ngay = 0;

        if ($nv->chuc_vu == 'Part') {

            // part-time
            $luong_ngay = $nv->luong_co_ban * $so_gio;
        } else {
            // full-time (có thể nâng cấp sau)
            $luong_ngay = $nv->luong_co_ban * $so_gio + 20000;
        }

        // =========================
        // 🔥 LƯU LƯƠNG NGÀY (khuyến nghị)
        // =========================

        DB::table('luong_ngay')->updateOrInsert(
            [
                'nhanvien_id' => $nhanvien_id,
                'ngay' => $today
            ],
            [
                'so_gio' => $so_gio,
                'luong' => $luong_ngay,
                'created_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Cảm ơn bạn! Check-out thành công.',
            'so_gio' => $so_gio,
            'luong_ngay' => $luong_ngay
        ]);
    }
}
