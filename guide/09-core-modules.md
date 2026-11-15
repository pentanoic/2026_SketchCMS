# 09 - Hướng dẫn vận hành các Module mặc định

Hệ thống SketchCMS bao gồm 7 module lõi nằm trong `app/modules/`. Mỗi module đảm nhiệm một nhóm chức năng độc lập theo kiến trúc HMVC và thao tác dữ liệu qua Fluent DB `db()`.

---

## 1. Module `home` (`app/modules/home/`)
- **Vai trò**: Điều phối trang chủ (`/`), hiển thị bài viết mới nhất, bài ghim, chuyên mục và các trang thông tin tĩnh (`/home/faq/...`).
- **Vận hành**:
  - Có thể ánh xạ các đường dẫn thân thiện (`/faq/terms`, `/faq/about`, `/faq/help`) vào `home` thông qua `/manager/url_rules`.

## 2. Module `articles` (`app/modules/articles/`)
- **Vai trò**: Quản lý chuyên mục, bài viết, chương (chapter), bình luận, lượt thích, từ khóa (tags), tìm kiếm (`/search`) và `sitemap`.
- **Các route chính**:
  - `/articles` & `/articles/{PostSlug}.html`: Danh sách và chi tiết bài viết.
  - `/articles/post`: Đăng bài viết mới.
  - `/articles/category`: Quản lý chuyên mục.
  - `/articles/post-{id}/add-chap`: Thêm chương cho bài viết.
  - `/articles/post-{id}/upload`: Đính kèm tệp lên bài viết.
  - `/articles/{post|chapter}-{id}/edit` & `/delete`: Sửa / xóa bài viết hoặc chương.
- **Lưu ý vận hành**: Khi xóa chuyên mục, hệ thống tự động xóa toàn bộ bài viết, bình luận, chương, tệp và liên kết tag thuộc chuyên mục đó.

## 3. Module `user` (`app/modules/user/`)
- **Vai trò**: Quản lý thành viên, đăng nhập (`/user/login`), đăng ký (`/user/register`), danh sách thành viên (`/user`), hồ sơ cá nhân, avatar, ảnh bìa, trạng thái online và điểm thưởng (`xu`).
- **Vận hành**:
  - Có thể đóng nhanh chức năng đăng ký bằng cách thêm `/register` vào mục `disable` trong `/manager/url_rules`.
  - Hỗ trợ tự động nâng cấp mật khẩu cũ sang chuẩn băm an toàn.

## 4. Module `mail` (`app/modules/mail/`)
- **Vai trò**: Hệ thống tin nhắn riêng tư giữa các thành viên và thông báo hệ thống (`SystemBOT`), tích hợp mã hóa đầu cuối **E2E** (`E2E_SECRET_KEY`).
- **Các route chính**:
  - `/mail`: Danh sách hộp thư đến/đi.
  - `/mail/send/{nick}`: Nhắn tin trực tiếp với thành viên `{nick}` và chặn/mở chặn người dùng.
  - `/mail/system`: Xem thông báo từ hệ thống (`SystemBOT`).
- **Cơ chế trạng thái**: Tin nhắn mới gửi mặc định có trạng thái **Đã gửi** (`view = 'no'`); khi người nhận mở xem, hệ thống tự động đồng bộ trạng thái **Đã xem** (`view = 'yes'`) cho cả hai phía.

## 5. Module `shoutbox` (`app/modules/shoutbox/`)
- **Vai trò**: Phòng chat thời gian thực (`/shoutbox`) cho phép thành viên trò chuyện nhanh, trích dẫn tin nhắn và nhận thông báo khi được nhắc tên.
- **Vận hành**: Hỗ trợ kiểm soát tần suất gửi tin (rate limit) và lọc nội dung trước khi lưu vào bảng `chat`.

## 6. Module `media` (`app/modules/media/`)
- **Vai trò**: Quản lý tệp đính kèm và tích hợp lưu trữ qua **Telegram Bot API** / **Cloudflare Worker**.
- **Vận hành**: Cấu hình thông số kết nối tại `/manager` hoặc `.env` (xem chi tiết tại [07 - Hướng dẫn cấu hình Upload Telegram](07-telegram-upload.md)).

## 7. Module `manager` (`app/modules/manager/`)
- **Vai trò**: Trung tâm quản trị hệ thống (`/manager`), yêu cầu phân quyền quản trị viên (`level >= 126` hoặc `127` tùy chức năng) và xác thực mật khẩu admin (`verifyAdminAction`) cho các thao tác nhạy cảm.
- **Các phân khu chính**:
  - `/manager/users`: Quản lý, tìm kiếm, sắp xếp và phân quyền thành viên.
  - `/manager/articles`: Quản lý bài viết và chương trên toàn hệ thống.
  - `/manager/files`: Quản lý tệp đính kèm.
  - `/manager/modules`: Cài đặt, kích hoạt hoặc gỡ bỏ module mở rộng (`custom_module`).
  - `/manager/url_rules`: Quản lý Rewrite URL và Disable URL.
  - `/manager/static_pages`: Quản lý trang tĩnh.
  - `/manager/smtp`: Cấu hình máy chủ gửi email SMTP.
  - `/manager/security`: Mã hóa E2E dữ liệu cũ, chuyển đổi MySQL/SQLite, kiểm tra/sửa schema CSDL và khởi tạo lại dữ liệu ban đầu (giữ nguyên toàn bộ `users`).
