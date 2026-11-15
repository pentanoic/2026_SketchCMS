# 04 - Hướng dẫn viết Template

Template của SketchCMS nằm trong thư mục `templates/<ten_template>/` (mặc định là `templates/default/`). Bạn có thể chọn hoặc tùy biến template trong `/manager`.

## 1. Cấu trúc thư mục Template
```text
templates/default/
├── layout.php          # Khung giao diện chính (header, navbar, footer, CSS/JS)
├── home.php            # Trang chủ
├── articles/           # Giao diện module articles (list, detail, post, edit...)
├── user/               # Giao diện module user (login, register, profile...)
├── mail/               # Giao diện hộp thư (mail_list.php, mail_send.php, mail_system.php)
├── shoutbox/           # Giao diện phòng chat
└── manager/            # Giao diện trang quản trị (/manager/*)
```

## 2. Cách Controller render View
Trong Controller, dữ liệu được truyền sang template qua `view()`:
```php
return view()
    ->setTitle('Tiêu đề trang')
    ->render('articles/detail', [
        'PostDetail' => $post,
    ]);
```
Hoặc dùng helper ngắn `view('manager/security', [...])`.

## 3. Các Helper thường dùng trong Template
- `url('/duong-dan')`: Tạo URL đầy đủ theo cấu hình site.
- `getCSRFToken()`: Sinh token CSRF cho thẻ `<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">`.
- `ago($timestamp)`: Hiển thị thời gian tương đối (ví dụ: `5 minutes ago`).
- `getAvtUser($UserDetail)`: Lấy đường dẫn ảnh đại diện của thành viên.
- `_e($string)`: Escape HTML chống XSS khi hiển thị dữ liệu do người dùng nhập.

## 4. Tạo Template mới
1. Nhân bản thư mục `templates/default/` thành `templates/my_theme/`.
2. Chỉnh sửa `layout.php` và các file view con theo thiết kế của bạn, giữ nguyên tên biến và `name="..."` của các `<form>` để Controller xử lý chính xác.
3. Kích hoạt template mới trong trang quản trị `/manager` (hoặc cấu hình `DEFAULT_TEMPLATE` trong `.env`).
