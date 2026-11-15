# 08 - Hướng dẫn cấu hình SMTP (Gửi Email)

SketchCMS sử dụng giao thức **SMTP** để gửi email hệ thống (xác thực, khôi phục mật khẩu, thông báo...). Toàn bộ thông số được quản lý tại `/manager/smtp` và đồng bộ vào `.env`.

---

## Phần 1: Chuẩn bị thông tin trên máy chủ Mail

### Trường hợp A: Sử dụng Gmail SMTP
1. Đăng nhập tài khoản Google dùng để gửi mail.
2. Bật **Xác minh 2 bước (2-Step Verification)** trong phần *Bảo mật (Security)* của Google Account.
3. Truy cập mục **Mật khẩu ứng dụng (App Passwords)**, tạo một mật khẩu mới cho ứng dụng (ví dụ tên `SketchCMS`).
4. Google sẽ cấp mật khẩu gồm **16 ký tự** (ví dụ `abcd efgh ijkl mnop`). Sử dụng chuỗi này làm `SMTP_PASS` (không dùng mật khẩu đăng nhập Gmail thông thường).
5. Thông số kết nối Gmail:
   - **Host**: `smtp.gmail.com`
   - **Port / Encrypt**: `465` (`ssl`) hoặc `587` (`tls`)

### Trường hợp B: Sử dụng Mail Server riêng / Dịch vụ SMTP (Zoho, Brevo, Mailgun...)
1. Lấy thông tin máy chủ SMTP từ nhà cung cấp: **SMTP Host**, **Port** (`465` SSL hoặc `587` TLS), **Username** và **Password/API Key**.
2. Đảm bảo tên miền gửi mail (`SMTP_FROM_EMAIL`) đã được cấu hình bản ghi **SPF**, **DKIM** trên DNS để tránh email bị rơi vào hòm thư rác (Spam).

---

## Phần 2: Cấu hình trong mã nguồn SketchCMS

### Cách 1: Cấu hình qua giao diện quản trị (`/manager/smtp`)
1. Đăng nhập tài khoản quản trị và truy cập `/manager/smtp`.
2. Điền đầy đủ các trường:
   - **SMTP Host**: `smtp.gmail.com`
   - **SMTP Port**: `465` (hoặc `587`)
   - **SMTP Encrypt**: `ssl` (với cổng `465`) hoặc `tls` (với cổng `587`)
   - **SMTP User**: Email đăng nhập SMTP (ví dụ `yourmail@gmail.com`)
   - **SMTP Pass**: Mật khẩu ứng dụng 16 ký tự (hoặc mật khẩu SMTP)
   - **From Email**: Địa chỉ email hiển thị ở mục người gửi
   - **From Name**: Tên hiển thị người gửi (ví dụ `SketchCMS`)
3. Nhập mật khẩu xác nhận admin và nhấn lưu.

### Cách 2: Cấu hình trực tiếp trong file `.env`
Mở `.env` ở thư mục gốc và cập nhật:

```dotenv
SMTP_HOST="smtp.gmail.com"
SMTP_PORT="465"
SMTP_USER="sketchcms@lab302.ovh"
SMTP_PASS="your-16-char-app-password"
SMTP_ENCRYPT="ssl"
SMTP_FROM_EMAIL="sketchcms@lab302.ovh"
SMTP_FROM_NAME="SketchCMS"
```

> **Lưu ý**: Đảm bảo PHP đã bật extension `openssl` và tường lửa/hosting không chặn kết nối ra ngoài tới cổng `465` hoặc `587`.
