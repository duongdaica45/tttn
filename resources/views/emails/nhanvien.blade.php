@component('mail::message')
# 📩 Bảng lương

**Họ tên:** {{ $data['ten_nhan_vien'] ?? '' }}

**Chức vụ:** {{ $data['chuc_vu'] ?? '' }}

**Số giờ làm:** {{ $data['so_gio_lam'] ?? '' }}

**Lương cơ bản:** {{ number_format($data['luong_co_ban'] ?? 0, 0, ',', '.') }} đ

**Khấu trừ:** {{ number_format($data['khoan_giam_tru'] ?? 0, 0, ',', '.') }} đ

**💰 Lương thực nhận:** **{{ number_format($data['luong_thuc_nhan'] ?? 0, 0, ',', '.') }} đ**
---
Cảm ơn bạn đã cống hiến cho công ty! 💪  
Trân trọng,  
@endcomponent
