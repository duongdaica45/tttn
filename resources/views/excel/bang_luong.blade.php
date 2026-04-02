<table border="1">
    <tr>
        <th>Họ tên</th>
        <th>Chức vụ</th>
        <th>Số giờ làm</th>
        <th>Lương cơ bản</th>
        <th>Khấu trừ</th>
        <th>Lương thực nhận</th>
    </tr>
    <tr>
        <td>{{ $data['ten_nhanvien'] }}</td>
        <td>{{ $data['chuc_vu'] }}</td>
        <td>{{ $data['so_gio_lam'] }}</td>
        <td>{{ number_format($data['luong_co_ban']) }}</td>
        <td>{{ number_format($data['khoan_giam_tru']) }}</td>
        <td><b>{{ number_format($data['luong_thuc_nhan']) }}</b></td>
    </tr>
</table>
