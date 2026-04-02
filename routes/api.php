<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NhanVienController;


//Đăng nhập
Route::post('/login', [AuthController::class, 'login']);
//Admin
//List
Route::get('/nhanvien', [NhanVienController::class, 'index']);
//Thêm,Xóa,Sửa
Route::post('/nhanvien', [NhanVienController::class, 'store']);
Route::delete('/nhanvien/{id}', [NhanVienController::class, 'destroy']);
Route::put('/nhanvien/{id}', [NhanVienController::class, 'update']);
