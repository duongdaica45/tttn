<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LichLam;
use App\Models\NgayMo;
use Carbon\Carbon;

class LichController extends Controller
{
    public function getNextWeek(Request $request)
    {
        $request->validate([
            'ngay' => 'required|date'
        ]);

        $ngay = Carbon::parse($request->ngay);

        // ✅ tuần hiện tại
        $startOfWeek = $ngay->copy()->startOfWeek(); // thứ 2
        $endOfWeek = $ngay->copy()->endOfWeek();     // CN

        // ✅ tuần kế tiếp
        $start = $startOfWeek->copy()->addWeek();
        $end = $endOfWeek->copy()->addWeek();

        $days = [];

        while ($start <= $end) {
            $record = DB::table('ngay_mo')
                ->whereDate('ngay', $start)
                ->first();

            $days[] = [
                'ngay' => $start->toDateString(),
                'thu' => $start->translatedFormat('l'),
                'mo_tao_ca' => $record ? $record->mo_tao_ca : false
            ];

            $start->addDay();
        }

        return response()->json($days);
    }
    public function store(Request $request)
    {
        // ✅ 1. Validate
        $request->validate([
            'ngay' => 'required|date',
            'ca_lam_id' => 'required|exists:ca_lam,id',
            'max_nhan_vien' => 'nullable|integer|min:1'
        ]);

        $ngay = $request->ngay;
        $ca_lam_id = $request->ca_lam_id;

        // 🔥 2. Kiểm tra ngày mở ca
        $ngayMo = DB::table('ngay_mo')
            ->where('ngay', $ngay)
            ->where('mo_tao_ca', true)
            ->first();

        if (!$ngayMo) {
            return response()->json([
                'message' => 'Ngày này chưa được mở để tạo ca'
            ], 400);
        }

        // 🔥 3. Kiểm tra trùng (1 ca/ngày)
        $exists = DB::table('lich_lam')
            ->where('ngay', $ngay)
            ->where('ca_lam_id', $ca_lam_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ca này trong ngày đã tồn tại'
            ], 400);
        }

        // 🔥 4. Lấy max_nhan_vien
        $maxNhanVien = $request->max_nhan_vien;

        if (!$maxNhanVien) {
            // 👉 nếu không truyền thì lấy mặc định từ ca_lam
            $caLam = DB::table('ca_lam')->where('id', $ca_lam_id)->first();

            if (!$caLam) {
                return response()->json([
                    'message' => 'Không tìm thấy ca làm'
                ], 404);
            }

            $maxNhanVien = $caLam->max_nhan_vien;
        }

        // ✅ 5. Tạo lịch làm
        $id = DB::table('lich_lam')->insertGetId([
            'ngay' => $ngay,
            'ca_lam_id' => $ca_lam_id,
            'max_nhan_vien' => $maxNhanVien,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tạo lịch làm thành công',
            'data' => [
                'id' => $id,
                'ngay' => $ngay,
                'ca_lam_id' => $ca_lam_id,
                'max_nhan_vien' => $maxNhanVien
            ]
        ], 201);
    }
}
