<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DonXinNghiController extends Controller
{

    public function taoDonXinNghi(Request $request)
    {
        $request->validate([
            'nhan_vien_id' => 'required|integer',
            'tu_ngay' => 'required|date',
            'den_ngay' => 'required|date|after_or_equal:tu_ngay',
            'ly_do' => 'nullable|string'
        ]);

        try {
            $tuNgay = Carbon::parse($request->tu_ngay);
            $denNgay = Carbon::parse($request->den_ngay);

            // ❌ Không cho chọn ngày quá khứ
            if ($tuNgay->isPast()) {
                return response()->json([
                    'message' => 'Không thể xin nghỉ trong quá khứ'
                ], 400);
            }

            // 🔥 TÍNH SỐ NGÀY CỦA ĐƠN MỚI
            $soNgayMoi = $tuNgay->diffInDays($denNgay) + 1;

            // 🔥 LẤY TỔNG SỐ NGÀY ĐÃ NGHỈ TRONG THÁNG
            $dsDon = DB::table('don_xin_nghi')
                ->where('nhan_vien_id', $request->nhan_vien_id)
                ->whereIn('trang_thai', ['cho_duyet', 'chap_nhan']) // ⚠️ sửa lại đúng enum
                ->whereBetween('tu_ngay', [
                    $tuNgay->copy()->startOfMonth(),
                    $tuNgay->copy()->endOfMonth()
                ])
                ->get();

            $tongSoNgay = 0;

            foreach ($dsDon as $don) {
                $start = Carbon::parse($don->tu_ngay);
                $end = Carbon::parse($don->den_ngay);
                $tongSoNgay += $start->diffInDays($end) + 1;
            }

            // ❌ CHECK: quá 3 ngày
            if (($tongSoNgay + $soNgayMoi) > 3) {
                return response()->json([
                    'message' => 'Bạn chỉ được nghỉ tối đa 3 ngày trong tháng'
                ], 400);
            }

            // ❌ CHECK 2: trùng ngày
            $biTrung = DB::table('don_xin_nghi')
                ->where('nhan_vien_id', $request->nhan_vien_id)
                ->whereIn('trang_thai', ['cho_duyet', 'chap_nhan'])
                ->where(function ($query) use ($request) {
                    $query->where('tu_ngay', '<=', $request->den_ngay)
                        ->where('den_ngay', '>=', $request->tu_ngay);
                })
                ->exists();

            if ($biTrung) {
                return response()->json([
                    'message' => 'Khoảng ngày nghỉ bị trùng'
                ], 400);
            }

            // ✅ INSERT
            DB::table('don_xin_nghi')->insert([
                'nhan_vien_id' => $request->nhan_vien_id,
                'tu_ngay' => $tuNgay->toDateString(),
                'den_ngay' => $denNgay->toDateString(),
                'ly_do' => $request->ly_do,
                'trang_thai' => 'cho_duyet',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Tạo đơn xin nghỉ thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function danhSachDonTrongThang(Request $request, $nhanVienId)
    {
        try {
            $month = $request->input('thang', now()->month);
            $year = $request->input('nam', now()->year);

            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = Carbon::create($year, $month, 1)->endOfMonth();

            // 🔥 Lấy danh sách đơn hợp lệ
            $donList = DB::table('don_xin_nghi')
                ->where('nhan_vien_id', $nhanVienId)
                ->whereBetween('tu_ngay', [$start, $end])
                ->whereIn('trang_thai', ['cho_duyet', 'chap_nhan']) // sửa lại cho đúng enum
                ->get();

            // 🔥 TÍNH TỔNG SỐ NGÀY
            $tongSoNgay = 0;

            foreach ($donList as $don) {
                $tu = Carbon::parse($don->tu_ngay);
                $den = Carbon::parse($don->den_ngay);

                $tongSoNgay += $tu->diffInDays($den) + 1;
            }

            // 🔥 trả về full data
            $data = DB::table('don_xin_nghi')
                ->where('nhan_vien_id', $nhanVienId)
                ->whereBetween('tu_ngay', [$start, $end])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $data,
                'tong_so_ngay_nghi' => $tongSoNgay
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function huyDonXinNghi(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        try {
            // 🔥 1. Lấy đơn
            $don = DB::table('don_xin_nghi')
                ->where('id', $request->id)
                ->first();

            if (!$don) {
                return response()->json([
                    'message' => 'Đơn không tồn tại'
                ], 404);
            }

            // ================================
            // ❌ CHECK: không cho hủy nếu đã duyệt hoặc từ chối
            // ================================
            if (in_array($don->trang_thai, ['chap_nhan', 'tu_choi'])) {
                return response()->json([
                    'message' => 'Không thể hủy đơn đã được xử lý'
                ], 400);
            }

            // ================================
            // ✅ XÓA
            // ================================
            DB::table('don_xin_nghi')
                ->where('id', $request->id)
                ->delete();

            return response()->json([
                'message' => 'Hủy đơn thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function duyetDon(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'trang_thai' => 'required|in:chap_nhan,tu_choi',
            'ghi_chu_admin' => 'nullable|string'
        ]);

        try {
            // 🔥 lấy đơn
            $don = DB::table('don_xin_nghi')->where('id', $request->id)->first();

            if (!$don) {
                return response()->json([
                    'message' => 'Đơn không tồn tại'
                ], 404);
            }

            // ❌ không cho duyệt lại nếu đã xử lý
            if (in_array($don->trang_thai, ['chap_nhan', 'tu_choi'])) {
                return response()->json([
                    'message' => 'Đơn này đã được xử lý rồi'
                ], 400);
            }

            // ================================
            // ❌ CHECK: quá 3 ngày nghỉ trong tháng
            // ================================
            if ($request->trang_thai == 'chap_nhan') {

                $tuNgay = Carbon::parse($don->tu_ngay);
                $denNgay = Carbon::parse($don->den_ngay);

                $start = $tuNgay->copy()->startOfMonth();
                $end = $tuNgay->copy()->endOfMonth();

                // 🔥 tổng số ngày đã nghỉ (đã duyệt)
                $tongNgay = DB::table('don_xin_nghi')
                    ->where('nhan_vien_id', $don->nhan_vien_id)
                    ->where('trang_thai', 'chap_nhan')
                    ->whereBetween('tu_ngay', [$start, $end])
                    ->get()
                    ->sum(function ($item) {
                        return Carbon::parse($item->tu_ngay)
                            ->diffInDays(Carbon::parse($item->den_ngay)) + 1;
                    });

                // 🔥 số ngày của đơn hiện tại
                $soNgayDonMoi = $tuNgay->diffInDays($denNgay) + 1;

                if (($tongNgay + $soNgayDonMoi) > 3) {
                    return response()->json([
                        'message' => 'Vượt quá 3 ngày nghỉ trong tháng'
                    ], 400);
                }
            }

            // ================================
            // ✅ UPDATE
            // ================================
            DB::table('don_xin_nghi')
                ->where('id', $request->id)
                ->update([
                    'trang_thai' => $request->trang_thai,
                    'ghi_chu_admin' => $request->ghi_chu_admin,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'message' => 'Cập nhật trạng thái thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function donChoDuyet(Request $request)
    {
        try {
            // 🔥 cho phép truyền tháng/năm (optional)
            $month = $request->input('thang', now()->month);
            $year = $request->input('nam', now()->year);

            // 🔥 khoảng thời gian tháng
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = Carbon::create($year, $month, 1)->endOfMonth();

            // 🔥 lấy danh sách đơn chờ duyệt
            $data = DB::table('don_xin_nghi as d')
                ->join('thongtinnhanvien as nv', 'd.nhan_vien_id', '=', 'nv.id')
                ->where('d.trang_thai', 'cho_duyet')
                ->whereBetween('d.tu_ngay', [$start, $end])
                ->select(
                    'd.id',
                    'd.tu_ngay',
                    'd.den_ngay',
                    'd.ly_do',
                    'd.trang_thai',
                    'd.created_at',
                    'nv.ten_nhan_vien',
                    'nv.chuc_vu'
                )
                ->orderBy('d.created_at', 'desc')
                ->get();


            return response()->json([
                'data' => $data,
                'total' => $data->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
