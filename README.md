# SketchCMS

SketchCMS là một mã nguồn quản trị nội dung miễn phí, còn non trẻ. Mã nguồn được xây dựng với định hướng trở thành một nền tảng nhẹ, linh hoạt và dễ mở rộng dành cho các website và hệ thống quản trị nội dung. Được phát triển dựa trên mô hình kiến trúc HMVC, SketchCMS tổ chức mã nguồn theo từng module độc lập, giúp quá trình phát triển, bảo trì và mở rộng hệ thống trở nên rõ ràng hơn. Mỗi module có thể đảm nhiệm một chức năng riêng, đồng thời có khả năng phối hợp với các thành phần khác trong toàn hệ thống.
---
 
## Tính năng
 
* Bài viết - chuyên mục SEO, BBCode, syntax highlighting, ghim bài, lượt thích, bình luận, tìm kiếm, sitemap XML;
* Chương - chia bài thành nhiều chương, phù hợp đăng truyện hoặc tài liệu nhiều phần;
* Tệp đính kèm - lưu trữ qua Telegram CDN, hỗ trợ mật khẩu;
* Thành viên - hồ sơ cá nhân, avatar, ảnh bìa, waifu, danh sách chặn;
* Tin nhắn riêng tích hợp mã hóa E2E;
* Phòng chat thời gian thực;
* Quản trị - Trang tĩnh, module gốc, module mở rộng, tùy biến template, rewrite/disable url, smpt;
* Vận hành an toàn - CSRF, rate limit, chống flood IP, brute force, bcrypt, security headers;
* API (đang phát triển) - Một custom module định hướng mã nguồn trở thành HeadlessCMS.
---
 
## Yêu cầu môi trường
 
| Thành phần | Yêu cầu |
|---|---|
| PHP | 8.0+ |
| MySQL | 5.7+ hoặc MariaDB 10.3+ |
| PHP Extensions | `pdo`, `pdo_mysql`, `mysqli`, `gd`, `openssl`, `mbstring` |
| Web Server | Apache (mod_rewrite) hoặc Nginx |
| Composer | 2.x |
 
---
 
## Cài đặt
 
### 1. Tải mã nguồn
 
Tải file ZIP từ trang phát hành và giải nén vào thư mục `public_html` hoặc tương đương.
 
### 2. Cài đặt thư viện
 
```bash
cd app
composer install
```
 
### 3. Cấu hình Web Server
 
**Apache** - tạo `.htaccess` ở thư mục gốc:
 
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```
 
**Nginx:**
 
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
 
### 4. Phân quyền thư mục
 
```bash
chmod 755 system/configs/
chmod 755 system/files/
chmod 755 templates/
```
 
### 5. Tạo cơ sở dữ liệu
 
```sql
CREATE DATABASE sketchcms CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```
 
### 6. Chạy trình cài đặt
 
Trỏ trình duyệt đến `/install/` và làm theo các bước:
 
1. Chấp nhận điều khoản GNU GPL v3
2. Kiểm tra môi trường - tất cả mục phải hiển thị OK
3. Nhập thông tin kết nối database
4. Tạo tài khoản Admin (level 127)
5. Hoàn tất
### 7. Sau khi cài đặt
 
Xóa thư mục `install/`:
 
```bash
rm -rf install/
```
 
Tắt chế độ debug trong `system/configs/init.php`:
 
```php
define('APP_DEBUG', false);
ini_set('display_errors', 0);
```
 
Đổi các khóa bảo mật mặc định:
 
```php
define('SECOND_PASSWORD', 'chuoi_ngau_nhien_cua_ban');
define('E2E_SECRET_KEY', 'secret_key_cua_ban');
```