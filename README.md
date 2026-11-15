# SketchCMS

**SketchCMS** là một mã nguồn quản trị nội dung (CMS) nhẹ, linh hoạt và dễ mở rộng, được xây dựng theo kiến trúc **HMVC** trên nền tảng PHP 8.0+, hỗ trợ đồng thời **MySQL** và **SQLite** thông qua hệ thống **Fluent Query Builder (`db()`)**.

## Tính năng nổi bật

- **Bài viết & Chuyên mục**: Hỗ trợ SEO, BBCode, syntax highlighting, ghim bài, lượt thích, bình luận, từ khóa (tags), chương (chapter), tìm kiếm và `sitemap.xml`.
- **Tệp đính kèm**: Lưu trữ và phân phối qua Telegram Bot API / Cloudflare Worker.
- **Thành viên & Tương tác**: Hồ sơ cá nhân, phòng chat thời gian thực (`shoutbox`), tin nhắn riêng tư tích hợp mã hóa đầu cuối **E2E**.
- **Quản trị linh hoạt (`/manager`)**: Quản lý module mở rộng (`custom_module`), trang tĩnh (`static_pages`), tùy biến đường dẫn (`url_rules`: Rewrite/Disable URL không cần sửa `.htaccess`), cấu hình SMTP, kiểm tra/chuyển đổi CSDL và bảo mật.
- **Vận hành an toàn**: CSRF token, rate limit, phân quyền RBAC, bcrypt và security headers.

## Tài liệu hướng dẫn

Toàn bộ tài liệu chi tiết được lưu trong thư mục `guide/` (**[guide/README.md](guide/README.md)**):

1. **[01 - Cài đặt & Vận hành](guide/01-install-and-operation.md)**
2. **[02 - Hướng dẫn sử dụng Fluent DB (`db()`)](guide/02-fluent-db.md)**
3. **[03 - Module mở rộng hợp lệ & Cách viết Module](guide/03-custom-module.md)**
4. **[04 - Hướng dẫn viết Template](guide/04-template.md)**
5. **[05 - Hướng dẫn sử dụng Rewrite / Disable URL (`url_rules`)](guide/05-url-rules.md)**
6. **[06 - Hướng dẫn sử dụng Trang tĩnh (`static_pages`)](guide/06-static-pages.md)**
7. **[07 - Hướng dẫn cấu hình Upload Telegram](guide/07-telegram-upload.md)**
8. **[08 - Hướng dẫn cấu hình SMTP](guide/08-smtp-config.md)**
9. **[09 - Hướng dẫn vận hành các Module mặc định](guide/09-core-modules.md)**
