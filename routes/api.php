<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NhanVienController;
use App\Http\Controllers\LichController;
use App\Http\Controllers\DangKyCaController;
use App\Http\Controllers\DonXinNghiController;
use App\Http\Controllers\DiemDanhController;
use App\Http\Controllers\LuongController;
use Illuminate\Support\Facades\DB;


Route::options('{any}', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
})->where('any', '.*');


//Đăng nhập
Route::post('/login', [AuthController::class, 'login']);
//Admin
//List
Route::get('/nhanvien', [NhanVienController::class, 'index']);
Route::get('/nhan-vien-full', [NhanVienController::class, 'getNhanVienFull']);
//Thêm,Xóa,Sửa
Route::post('/nhanvien', [NhanVienController::class, 'store']);
Route::delete('/nhanvien/{id}', [NhanVienController::class, 'destroy']);
Route::put('/nhanvien/{id}', [NhanVienController::class, 'update']);
//Mở ngày 
Route::post('/next-week', [LichController::class, 'getNextWeek']);
Route::post('/current-week', [LichController::class, 'getCurrentWeek']);
Route::post('/toggle-ngay', [LichController::class, 'toggleNgay']);
Route::post('/lich-lam', [LichController::class, 'store']);
Route::get('/ngay-mo', function () {
    $data = DB::table('ngay_mo')
        ->where('mo_tao_ca', true)
        ->pluck('ngay'); // chỉ lấy danh sách ngày

    return response()->json($data);
});
Route::get('/lich-lam', [LichController::class, 'index']);
Route::delete('/lich-lam/{id}', [LichController::class, 'destroy']);
Route::get('/ca-trong-tuan', [LichController::class, 'caTrongTuan']);

//Đăng kí ca 
Route::post('/dang-ky-ca', [DangKyCaController::class, 'store']);
Route::post('/huy-dang-ky-ca', [DangKyCaController::class, 'huyDangKy']);
Route::get('/check-dang-ky', [DangKyCaController::class, 'checkTrangThai']);
Route::post('/dang-ky-ca-part', [DangKyCaController::class, 'dangKyCaPartTime']);
//Đơn xin nghỉ
Route::post('/don-xin-nghi', [DonXinNghiController::class, 'taoDonXinNghi']);
Route::get('/don-xin-nghi/{id}', [DonXinNghiController::class, 'danhSachDonTrongThang']);
Route::post('/huy-don-xin-nghi', [DonXinNghiController::class, 'huyDonXinNghi']);
Route::post('/duyet-don-xin-nghi', [DonXinNghiController::class, 'duyetDon']);
Route::get('/don-cho-duyet', [DonXinNghiController::class, 'donChoDuyet']);
//Điểm danh
Route::post('/check-in', [DiemDanhController::class, 'checkIn']);
Route::post('/check-out', [DiemDanhController::class, 'checkOut']);
//Tính lương 
Route::post('/tinh-luong', [LuongController::class, 'tinhLuong']);
Route::get('/luong-nam', [LuongController::class, 'luongNam']);
Route::get('/lich-su-cham-cong', [LuongController::class, 'lichSuChamCong']);
