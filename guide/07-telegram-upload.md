# 07 - Hướng dẫn cấu hình Upload Telegram

SketchCMS hỗ trợ lưu trữ và phân phối tệp đính kèm thông qua **Telegram Bot API** (kết hợp tùy chọn **Cloudflare Worker** làm CDN proxy để tải file nhanh và ẩn Bot Token).

---

## Phần 1: Các bước thực hiện trên Telegram

### Bước 1: Tạo Bot và lấy `BOT_TOKEN`
1. Mở Telegram, tìm kiếm tài khoản chính thức **`@BotFather`** (có dấu tích xanh).
2. Gửi lệnh `/newbot`.
3. Nhập **tên hiển thị** cho Bot (ví dụ: `SketchCMS Storage`).
4. Nhập **username** cho Bot (bắt buộc kết thúc bằng `bot`, ví dụ: `sketchcms_storage_bot`).
5. `@BotFather` sẽ trả về một chuỗi **HTTP API Token** có dạng `1234567890:AAH...`. Hãy lưu lại chuỗi này làm `TELEGRAM_BOT_TOKEN`.

### Bước 2: Tạo Channel/Group lưu trữ và cấp quyền cho Bot
1. Tạo một **Private Channel** (hoặc Private Group) mới trên Telegram để làm kho chứa file.
2. Mở phần **Manage Channel** -> **Administrators** -> **Add Administrator**.
3. Tìm username Bot vừa tạo ở Bước 1 và thêm làm **Admin** với quyền **Post Messages** (đăng tin nhắn/file).

### Bước 3: Lấy `CHAT_ID` của Channel/Group
1. Đăng một tin nhắn bất kỳ vào Channel/Group vừa tạo.
2. Chuyển tiếp (Forward) tin nhắn đó tới bot **`@userinfobot`** hoặc **`@getidsbot`**, hoặc truy cập trình duyệt:
   ```text
   https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getUpdates
   ```
3. Tìm trường `"chat":{"id":-100xxxxxxxxxx,...}`. Giá trị số âm `-100xxxxxxxxxx` chính là `TELEGRAM_CHAT_ID`.

---

## Phần 2: Cấu hình trong mã nguồn SketchCMS

### Cách 1: Cấu hình qua giao diện quản trị (`/manager`)
1. Đăng nhập tài khoản quản trị và truy cập trang cấu hình Media/Hệ thống trong `/manager`.
2. Điền các thông số tương ứng:
   - **`TELEGRAM_UPLOAD_MODE`**: Chế độ upload (`direct` hoặc qua `worker`).
   - **`TELEGRAM_BOT_TOKEN`**: Token lấy từ `@BotFather`.
   - **`TELEGRAM_CHAT_ID`**: ID Channel/Group (ví dụ `-1001234567890`).
   - **`CLOUDFLARE_WORKER_URL`** *(tùy chọn)*: URL Worker dùng làm proxy tải file nếu chọn chế độ qua Cloudflare Worker.
3. Nhập mật khẩu xác nhận admin và lưu lại.

### Cách 2: Cấu hình trực tiếp trong file `.env`
Mở file `.env` ở thư mục gốc và cập nhật các biến sau:

```dotenv
TELEGRAM_UPLOAD_MODE="direct"
TELEGRAM_BOT_TOKEN="1234567890:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
TELEGRAM_CHAT_ID="-1001234567890"
CLOUDFLARE_WORKER_URL=""
```

> **Lưu ý vận hành**: Sau khi cấu hình xong, hãy thử tải lên một tệp đính kèm trong bài viết (`/articles/post-{id}/upload`) và kiểm tra xem tệp đã xuất hiện trong Private Channel trên Telegram chưa.
