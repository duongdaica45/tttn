<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NhanVien;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
{
    // Validate
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // Tìm user
    $user = NhanVien::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Email không tồn tại'
        ], 404);
    }

    // 🔥 So sánh password bằng MD5
    if (md5($request->password) !== $user->password) {
        return response()->json([
            'status' => false,
            'message' => 'Sai mật khẩu'
        ], 401);
    }

    // Thành công
    return response()->json([
        'status' => true,
        'message' => 'Đăng nhập thành công',
        'user' => [
            'id' => $user->id,
            'ten_nhan_vien' => $user->ten_nhan_vien,
            'email' => $user->email,
            'chuc_vu' => $user->chuc_vu
        ]
    ]);
}

}
