# 05 - Hướng dẫn sử dụng Rewrite / Disable URL (`url_rules`)

**URL Rules (`url_rules`)** là tính năng đặc trưng của SketchCMS cho phép quản trị viên tùy biến đường dẫn (Rewrite URL) hoặc vô hiệu hóa tạm thời các route (Disable URL) ngay từ giao diện quản trị `/manager/url_rules` mà không cần sửa file `.htaccess`, cấu hình Nginx hay can thiệp vào mã nguồn Router của từng module.

---

## 1. Nơi lưu trữ cấu hình
Toàn bộ quy tắc được lưu tại `system/configs/autoload/system.php` dưới khóa `'url_rules'`:

```php
'url_rules' => [
    'rewrite' => [
        '/faq/terms' => [
            'target' => '/home/faq/terms',
            'method' => 'GET|POST',
        ],
    ],
    'disable' => [
        '/register',
    ],
],
```

Mọi thao tác thêm, sửa, xóa trên `/manager/url_rules` đều được đồng bộ tự động vào `system/configs/autoload/system.php` thông qua `Env::saveSystemConfig()`.

---

## 2. Hai chế độ hoạt động

### 2.1. Rewrite URL (`rewrite`)
Ánh xạ một đường dẫn thân thiện (hoặc đường dẫn tùy chỉnh) sang route đích thực tế trong hệ thống:
- **Đường dẫn nguồn (`path`)**: URL người dùng truy cập (ví dụ `/faq/terms`, `/tin-tuc`).
- **Đích đến (`target`)**: Route gốc của controller/module (ví dụ `/home/faq/terms`, `/articles`).
- **Phương thức (`method`)**: `GET`, `POST`, hoặc `GET|POST`.

**Ứng dụng thực tế**:
- Tạo URL ngắn gọn, chuẩn SEO cho các trang tĩnh hoặc module mở rộng.
- Giữ nguyên liên kết cũ khi thay đổi cấu trúc route nội bộ.

### 2.2. Disable URL (`disable`)
Chặn truy cập trực tiếp vào một đường dẫn cụ thể (ví dụ `/register`, `/shoutbox`):
- Khi đường dẫn nằm trong danh sách `disable`, mọi request tới đường dẫn đó sẽ bị từ chối/chuyển hướng về trang lỗi theo cơ chế định tuyến của hệ thống.
- **Ứng dụng thực tế**: Đóng nhanh chức năng đăng ký thành viên (`/register`) hoặc tạm khóa một tính năng để bảo trì mà không cần tắt cả website.

---

## 3. Thao tác trên `/manager/url_rules`
1. Truy cập `/manager/url_rules` (yêu cầu tài khoản cấp quản trị `level >= 127`).
2. **Thêm quy tắc**: Chọn loại (`rewrite` hoặc `disable`), nhập đường dẫn nguồn, đích đến & method (nếu là `rewrite`), nhập mật khẩu xác nhận admin và lưu.
3. **Sửa / Xóa quy tắc**: Thực hiện trực tiếp trên danh sách quy tắc hiện có; thay đổi có hiệu lực ngay lập tức.
