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
        $now = Carbon::now();

        // check ngày mở
        $ngayMo = DB::table('ngay_mo')
            ->whereDate('ngay', $today)
            ->where('mo_tao_ca', true)
            ->first();

        if (!$ngayMo) {
            return response()->json(['message' => 'Hôm nay không cho điểm danh!'], 400);
        }

        // check nghỉ
        $nghi = DB::table('don_xin_nghi')
            ->where('nhan_vien_id', $nhanvien_id)
            ->where('trang_thai', 'chap_nhan')
            ->whereDate('tu_ngay', '<=', $today)
            ->whereDate('den_ngay', '>=', $today)
            ->first();

        if ($nghi) {
            return response()->json(['message' => 'Bạn đang nghỉ phép!'], 400);
        }

        // check lịch
        $lich = DB::table('dang_ky_ca')
            ->join('lich_lam', 'dang_ky_ca.lich_lam_id', '=', 'lich_lam.id')
            ->where('dang_ky_ca.nhanvien_id', $nhanvien_id)
            ->whereIn('dang_ky_ca.trang_thai', ['dang_ky', 'chap_nhan'])
            ->whereDate('lich_lam.ngay', $today)
            ->first();

        if (!$lich) {
            return response()->json(['message' => 'Bạn không có ca làm hôm nay!'], 400);
        }

        // lấy ca
        $ca = DB::table('ca_lam')->where('id', $lich->ca_lam_id)->first();

        if (!$ca) {
            return response()->json(['message' => 'Không tìm thấy ca làm!'], 400);
        }

        // xử lý giờ
        $gioBatDau = Carbon::parse($today . ' ' . $ca->gio_bat_dau);

        if ($now->lt($gioBatDau->copy()->subMinutes(30))) {
            return response()->json(['message' => 'Chưa đến giờ check-in!'], 400);
        }

        if ($now->gt($gioBatDau->copy()->addHours(2))) {
            return response()->json(['message' => 'Đã quá giờ check-in!'], 400);
        }

        // check trùng
        $exists = DB::table('diemdanh')
            ->where('nhanvien_id', $nhanvien_id)
            ->whereDate('ngay', $today)
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Hôm nay bạn đã check-in rồi!'], 400);
        }

        // insert
        DB::table('diemdanh')->insert([
            'nhanvien_id' => $nhanvien_id,
            'ngay' => $today,
            'gio_vao' => $now,
        ]);

        return response()->json([
            'message' => 'Check-in thành công!'
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
