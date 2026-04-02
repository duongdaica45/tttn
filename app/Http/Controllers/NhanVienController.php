<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NhanVien;

class NhanVienController extends Controller
{
    // Lấy danh sách nhân viên
    public function index()
    {
        $nhanviens = NhanVien::select(
            'id',
            'ten_nhan_vien',
            'email',
            'chuc_vu',
            'luong_co_ban',
            'ca_lam_id'
        )->get();

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách nhân viên thành công',
            'data' => $nhanviens
        ]);
    }

    public function store(Request $request)
    {
        // Validate
        $request->validate([
            'ten_nhan_vien' => 'required|string|max:255',
            'email' => 'required|email|unique:thongtinnhanvien,email',
            'password' => 'required|min:6',
            'chuc_vu' => 'required|in:Manager,Full,Part',
            'luong_co_ban' => 'required|numeric'
        ]);

        // Tạo nhân viên
        $nhanvien = NhanVien::create([
            'ten_nhan_vien' => $request->ten_nhan_vien,
            'email' => $request->email,
            'password' => md5($request->password),
            'chuc_vu' => $request->chuc_vu,
            'luong_co_ban' => $request->luong_co_ban,
            'ca_lam_id' => 1 // 🔥 mặc định
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thêm nhân viên thành công',
            'data' => $nhanvien
        ], 201);
    }
    public function destroy($id)
    {
        // 1. Tìm nhân viên
        $nhanvien = NhanVien::find($id);

        if (!$nhanvien) {
            return response()->json([
                'status' => false,
                'message' => 'Nhân viên không tồn tại'
            ], 404);
        }

        // 2. Xóa
        $nhanvien->delete();

        // 3. Trả kết quả
        return response()->json([
            'status' => true,
            'message' => 'Xóa nhân viên thành công'
        ]);
    }
    public function update(Request $request, $id)
    {
        // 1. Tìm nhân viên
        $nhanvien = NhanVien::find($id);

        if (!$nhanvien) {
            return response()->json([
                'status' => false,
                'message' => 'Nhân viên không tồn tại'
            ], 404);
        }

        // 2. Validate
        $request->validate([
            'ten_nhan_vien' => 'required|string|max:255',
            'email' => 'required|email|unique:thongtinnhanvien,email,' . $id,
            'chuc_vu' => 'required|in:Manager,Full,Part',
            'luong_co_ban' => 'required|numeric'
        ]);

        // 3. Cập nhật dữ liệu
        $nhanvien->ten_nhan_vien = $request->ten_nhan_vien;
        $nhanvien->email = $request->email;
        $nhanvien->chuc_vu = $request->chuc_vu;
        $nhanvien->luong_co_ban = $request->luong_co_ban;

        // 🔥 Nếu có nhập password mới thì update
        if ($request->filled('password')) {
            $nhanvien->password = md5($request->password);
        }

        $nhanvien->save();

        // 4. Trả kết quả
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật nhân viên thành công',
            'data' => $nhanvien
        ]);
    }
}
