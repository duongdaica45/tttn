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



    public function toggleNgay(Request $request)
    {
        $request->validate([
            'ngay' => 'required|date',
            'mo_tao_ca' => 'required|boolean'
        ]);

        DB::table('ngay_mo')->updateOrInsert(
            ['ngay' => $request->ngay],
            [
                'mo_tao_ca' => $request->mo_tao_ca,
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Cập nhật thành công'
        ]);
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
    public function index()
    {
        $data = DB::table('lich_lam as ll')
            ->join('ca_lam as cl', 'll.ca_lam_id', '=', 'cl.id')
            ->select(
                'll.id',
                'll.ngay',
                'll.max_nhan_vien',
                'cl.ten_ca'
            )
            ->orderBy('ll.ngay', 'asc')
            ->get();

        return response()->json($data);
    }
    public function destroy($id)
    {
        try {
            // 1. Kiểm tra lịch tồn tại
            $lich = LichLam::find($id);

            if (!$lich) {
                return response()->json([
                    'message' => 'Lịch làm không tồn tại'
                ], 404);
            }

            // 2. Không cho xóa nếu đã hoặc đang diễn ra
            if ($lich->ngay <= now()->toDateString()) {
                return response()->json([
                    'message' => 'Không thể xóa lịch đã hoặc đang diễn ra'
                ], 400);
            }

            // 3. Kiểm tra có nhân viên đăng ký không
            $exists = DB::table('dang_ky_ca')
                ->where('lich_lam_id', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Không thể xóa! Đã có nhân viên đăng ký ca này'
                ], 400);
            }

            // 4. Xóa
            $lich->delete();

            return response()->json([
                'message' => 'Xóa lịch làm thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
