# Tài liệu Hướng dẫn SketchCMS (`guide/`)

Chào mừng bạn đến với bộ tài liệu hướng dẫn cài đặt, phát triển và vận hành mã nguồn **SketchCMS** (kiến trúc HMVC, PHP 8.0+, hỗ trợ MySQL & SQLite).

## Mục lục

1. **[01 - Cài đặt & Vận hành](01-install-and-operation.md)**
   - Yêu cầu hệ thống, cấu hình Web Server (Apache/Nginx), `.env`, `install/`
   - Chuyển đổi MySQL / SQLite, quản lý `url_rules`, bảo mật & reset dữ liệu (`/manager/security`)
   - Công cụ CLI (`system/bin/tests`, `system/bin/benchmark`) & E2E Browser Runner (`/run-nomal.php`)
2. **[02 - Hướng dẫn sử dụng Fluent DB (`db()`)](02-fluent-db.md)**
   - Toàn bộ API của `system/drive/Query/Builder.php`
   - Truy vấn đọc, JOIN, biểu thức Raw, ghi/cập nhật/xóa, phân trang
   - Quy tắc tương thích song song MySQL & SQLite
3. **[03 - Module mở rộng hợp lệ & Cách viết Module](03-custom-module.md)**
   - Cấu trúc chuẩn của một module trong `app/modules/` và `app/modules/custom_module/`
   - Ví dụ hoàn chỉnh: Router, Controller, Model (dùng `db()`), Library
4. **[04 - Hướng dẫn viết Template](04-template.md)**
   - Cấu trúc thư mục `templates/<template_name>/`
   - Layout chính, view theo module, các helper thường dùng (`view()`, `url()`, `getCSRFToken()`, `ago()`)
5. **[05 - Hướng dẫn sử dụng Rewrite / Disable URL (`url_rules`)](05-url-rules.md)**
   - Cơ chế hoạt động của `rewrite` và `disable` không cần sửa `.htaccess`/Nginx
   - Cấu trúc trong `system/configs/autoload/system.php` và thao tác trên `/manager/url_rules`
6. **[06 - Hướng dẫn sử dụng Trang tĩnh (`static_pages`)](06-static-pages.md)**
   - Tạo, sửa, xóa trang tĩnh tại `/manager/static_pages`
   - Kết hợp `static_pages` với `url_rules` để tạo đường dẫn chuẩn SEO
7. **[07 - Hướng dẫn cấu hình Upload Telegram](07-telegram-upload.md)**
   - Tạo Bot với `@BotFather`, cấp quyền Channel/Group, lấy `CHAT_ID`
   - Cấu hình `TELEGRAM_UPLOAD_MODE`, `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `CLOUDFLARE_WORKER_URL`
8. **[08 - Hướng dẫn cấu hình SMTP](08-smtp-config.md)**
   - Tạo App Password trên Gmail / cấu hình máy chủ mail
   - Cấu hình qua `/manager/smtp` hoặc `.env`
9. **[09 - Hướng dẫn vận hành các Module mặc định](09-core-modules.md)**
   - Tổng hợp toàn bộ 7 module lõi: `home`, `articles`, `user`, `mail`, `shoutbox`, `media`, `manager`

---

## Tổng quan cấu trúc mã nguồn

| Thư mục / File | Vai trò |
|---|---|
| `index.php` | Điểm vào (Entry point) của toàn bộ ứng dụng |
| `app/modules/` | Các module HMVC chính (`home`, `articles`, `user`, `mail`, `shoutbox`, `media`, `manager`) |
| `app/modules/custom_module/` | Nơi chứa các module mở rộng do người dùng tự phát triển |
| `system/` | Lõi framework: `bootstrap.php`, `classes/` (`Env`, `DB`, `E2E`, `Request`, `Router`...), `drive/` (Fluent Query Builder & Drivers) |
| `system/configs/autoload/system.php` | Cấu hình hệ thống (`app`, `url_rules`...) được quản lý bởi `/manager` |
| `system/bin/` | Các công cụ CLI: `tests`, `benchmark`, `cms` |
| `templates/default/` | Giao diện mặc định của hệ thống và trang quản trị `/manager` |
| `tests/` | Bộ kiểm thử tự động (`Unit/`, `Integration/`, `run-normal/`) |
| `install/` | Trình cài đặt cơ sở dữ liệu và dữ liệu mẫu ban đầu |
| `.env` | Biến môi trường (kết nối DB, SMTP, Telegram CDN, E2E key, cấu hình phân trang/bảo mật) |
