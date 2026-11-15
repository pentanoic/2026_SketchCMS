# 06 - Hướng dẫn sử dụng Trang tĩnh (`static_pages`)

**Trang tĩnh (`static_pages`)** cho phép quản trị viên tạo, chỉnh sửa và quản lý các trang nội dung độc lập (như *Giới thiệu*, *Điều khoản*, *Hướng dẫn*...) trực tiếp trong khu vực quản trị `/manager/static_pages` mà không cần tạo bài viết trong chuyên mục hay viết thêm Controller/Router mới.

---

## 1. Quản lý Trang tĩnh trong `/manager/static_pages`
Yêu cầu tài khoản quản trị có quyền truy cập `/manager/static_pages`:

1. **Danh sách trang tĩnh (`/manager/static_pages`)**: Hiển thị toàn bộ các trang tĩnh hiện có cùng đường dẫn truy cập tương ứng.
2. **Thêm trang tĩnh (`/manager/static_pages/add`)**:
   - Nhập **Tiêu đề**, **Slug** (định danh URL, ví dụ `about`, `terms`, `help`) và **Nội dung** (hỗ trợ HTML/BBCode tùy theo cấu hình template).
   - Xác thực CSRF và mật khẩu admin (nếu được yêu cầu) trước khi lưu.
3. **Chỉnh sửa & Xóa**:
   - Sửa trực tiếp nội dung/tiêu đề của trang tĩnh đã tạo.
   - Xóa trang tĩnh thông qua `verifyAdminAction('static_pages', ...)` để đảm bảo an toàn.

---

## 2. Kết hợp `static_pages` với `url_rules`
Trang tĩnh thường được kết hợp với tính năng **Rewrite URL** trong `/manager/url_rules` để tạo đường dẫn ngắn gọn, chuẩn SEO:

```php
'url_rules' => [
    'rewrite' => [
        '/faq/terms' => [
            'target' => '/home/faq/terms',
            'method' => 'GET|POST',
        ],
        '/faq/about' => [
            'target' => '/home/faq/about',
            'method' => 'GET|POST',
        ],
    ],
],
```

Nhờ cơ chế này, bạn có thể tạo mới nội dung ở `static_pages` và gán bất kỳ đường dẫn thân thiện nào cho người dùng cuối truy cập.
