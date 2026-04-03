<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NhanVienController;


Route::options('{any}', function() {
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
//Thêm,Xóa,Sửa
Route::post('/nhanvien', [NhanVienController::class, 'store']);
Route::delete('/nhanvien/{id}', [NhanVienController::class, 'destroy']);
Route::put('/nhanvien/{id}', [NhanVienController::class, 'update']);
