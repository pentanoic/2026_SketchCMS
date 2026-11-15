# 01 - Cài đặt & Vận hành SketchCMS

## 1. Yêu cầu môi trường
- **PHP**: 8.0+ (khuyến nghị 8.1+)
- **CSDL**: MySQL 5.7+ / MariaDB 10.3+ **hoặc** SQLite 3
- **Extensions**: `pdo`, `pdo_mysql` / `pdo_sqlite`, `gd`, `openssl`, `mbstring`, `curl`
- **Web Server**: Apache (`mod_rewrite`) hoặc Nginx

## 2. Cài đặt mã nguồn
1. Giải nén mã nguồn vào thư mục web root (ví dụ `public_html` hoặc `d:\laragon\www\sketchcms`).
2. Cài đặt thư viện Composer (nếu cần):
   ```bash
   cd app
   composer install
   ```
3. Cấu hình rewrite URL:
   - **Apache**: Đảm bảo `.htaccess` ở gốc có `RewriteRule ^(.*)$ index.php [QSA,L]`.
   - **Nginx**:
     ```nginx
     location / {
         try_files $uri $uri/ /index.php?$query_string;
     }
     ```
4. Chạy trình cài đặt tại `http://ten-mien-cua-ban/install/` để khởi tạo bảng và dữ liệu mẫu ban đầu.

## 3. Cấu hình `.env` & `system/configs/autoload/system.php`
- **`.env`**: Lưu thông tin kết nối CSDL (`DB_CONNECTION=mysql` hoặc `sqlite`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), khóa `E2E_SECRET_KEY`, `SMTP_*`, `TELEGRAM_*`, `MANAGER_ADMIN_ACCOUNT`.
- **`system/configs/autoload/system.php`**: Lưu cấu hình ứng dụng (`app`) và quy tắc định tuyến (`url_rules` gồm `rewrite` và `disable`). Mọi thay đổi từ `/manager/url_rules` sẽ được đồng bộ trực tiếp vào file này thông qua `Env::saveSystemConfig()`.

## 4. Vận hành & Bảo mật (`/manager/security`)
Trang `/manager/security` cung cấp 4 công cụ quản trị cốt lõi:
1. **Mã hóa E2E dữ liệu cũ**: Quét và mã hóa các tin nhắn cũ chưa được mã hóa đầu cuối.
2. **Chuyển đổi CSDL (`mysql` / `sqlite`)**: Đồng bộ và chuyển đổi kết nối CSDL đang dùng.
3. **Kiểm tra cấu trúc CSDL**: So sánh bảng/cột thực tế với `system/configs/schema.json` (hỗ trợ cả MySQL và SQLite) và tự động bổ sung phần còn thiếu.
4. **Khởi tạo lại dữ liệu ban đầu**: Xóa sạch dữ liệu các bảng (giữ nguyên toàn bộ bảng `users`), đồng thời tạo lại chuyên mục mẫu (`Tin tức - Sự kiện`) và bài viết mẫu (`Chào mừng đến với SketchCMS`) với tác giả là admin. Yêu cầu nhập tên module (`security`) và mật khẩu admin để xác nhận.

## 5. Công cụ kiểm thử & Benchmark
- **Chạy Unit & Integration Tests**:
  ```bash
  php system/bin/tests
  php system/bin/tests --filter=QueryBuilder
  ```
- **Đo hiệu năng hệ thống**:
  ```bash
  php system/bin/benchmark --iterations=5
  ```
- **Kiểm thử E2E trên trình duyệt**: Mở `http://sketchcms.test/run-nomal.php` khi đang đăng nhập để kiểm thử hàng loạt route `GET` và `POST` thực tế.
- **Kiểm thử tin nhắn riêng bằng cURL**:
  ```bash
  php tests/run-normal/mail_between_users_test.php
  ```
