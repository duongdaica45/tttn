 <?php

use App\Mail\NhanVienMail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongTinNhanVienMail;
use App\Models\NhanVien;

Route::get('/test-mail', function () {
    $mailData = [
        'ten_nhan_vien' => 'Nguyen Thai Duong',
        'chuc_vu' => 'Crew',
        'so_gio_lam' => 72,
        'luong_co_ban' => 25000,
        'khoan_giam_tru' => 0,
        'luong_thuc_nhan' => 1800000,
    ];

    Mail::to('ntduong.ti1@gmail.com')->send(new ThongTinNhanVienMail($mailData));

    return "✅ Đã gửi mail thành công!";
});
/*

*/