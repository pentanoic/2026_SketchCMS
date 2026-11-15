# 03 - Module mở rộng

## 1. Cấu trúc chuẩn của một Module mở rộng hợp lệ

Mọi module mở rộng phải được đặt trong `app/modules/custom_module/custom_<moduleName>/` (tiền tố bắt buộc `custom_`, tên `<moduleName>` viết thường, không dấu, không khoảng trắng).

Ví dụ với module `demo` (`custom_demo`):

```text
app/modules/custom_module/custom_demo/
├── tpl/                               # Thư mục chứa giao diện (view) của module
│   └── index.php
├── custom_demo_install.sql            # Script SQL thực thi khi cài đặt/kích hoạt module
├── custom_demo_uninstall.sql          # Script SQL thực thi khi gỡ bỏ module
├── custom_demoController.php          # Controller xử lý logic & điều phối view
├── custom_demoLibrary.php             # Thư viện hàm tiện ích nội bộ của module
├── custom_demoModel.php               # Model thao tác CSDL qua Fluent DB (db())
├── custom_demoRouter.php              # Khai báo các route của module
└── description.json                   # Metadata hiển thị trong /manager/modules
```

> **Nguyên tắc bắt buộc**: Thiếu một trong các thành phần cốt lõi (`description.json`, `*Router.php`, `*Controller.php`, `*_install.sql`, `*_uninstall.sql`, `tpl/`) hoặc đặt sai quy ước tiền tố `custom_<moduleName>` sẽ khiến trình quản lý `/manager/modules` không nhận diện hoặc không thể cài đặt/gỡ bỏ module.

---

## 2. Chi tiết từng thành phần

### 2.1. `description.json`

Khai báo thông tin nhận diện module:

```json
{
  "title": "Demo Module",
  "description": "Mô tả ngắn gọn chức năng của module mở rộng",
  "author": "YourName",
  "version": "1.0.0"
}
```

### 2.2. `custom_demo_install.sql` & `custom_demo_uninstall.sql`

- `custom_demo_install.sql`: Tạo bảng hoặc dữ liệu khởi tạo khi cài đặt module.
  ```sql
  CREATE TABLE IF NOT EXISTS `custom_demo_items` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `title` VARCHAR(255) NOT NULL,
      `created_at` INTEGER NOT NULL
  );
  ```
- `custom_demo_uninstall.sql`: Dọn dẹp bảng/dữ liệu khi gỡ module.
  ```sql
  DROP TABLE IF EXISTS `custom_demo_items`;
  ```

### 2.3. `custom_demoRouter.php`

Đăng ký route trỏ về Controller:

```php
<?php

$router->add('/demo', 'custom_demoController@index', 'GET');
$router->add('/demo/create', 'custom_demoController@create', 'GET|POST');
```

### 2.4. `custom_demoModel.php`

Kế thừa `Model` và sử dụng Fluent DB `db()` (tương thích cả MySQL và SQLite):

```php
<?php

class custom_demoModel extends Model
{
    public function getItems(int $limit = 20): array
    {
        return db('custom_demo_items')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get();
    }

    public function createItem(string $title): int
    {
        return db('custom_demo_items')->insert([
            'title'      => _e($title),
            'created_at' => TIME,
        ]);
    }
}
```

### 2.5. `custom_demoLibrary.php`

Chứa các hàm xử lý/định dạng dùng chung cho Controller:

```php
<?php

class custom_demoLibrary
{
    public function formatTitle(string $title): string
    {
        return trim($title);
    }
}
```

### 2.6. `custom_demoController.php` & `tpl/index.php`

Controller kế thừa `Controller`, nạp Model/Library và render view từ `tpl/`:

```php
<?php

class custom_demoController extends Controller
{
    public function index()
    {
        $model = $this->load->model('custom_demo');
        $items = $model->getItems();

        return view()
            ->setTitle('Demo Module')
            ->render('custom_demo/tpl/index', [
                'items' => $items,
            ]);
    }
}
```

---

## 3. Quy tắc bắt buộc khi phát triển Module

1. **Truy vấn CSDL**: Chỉ sử dụng Fluent DB `db($table)`; không dùng `->db->prepare()` hay `Database::select()`.
2. **Bảo mật Form**: Mọi form `POST` phải kèm `<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">` và xác thực CSRF tại Controller.
3. **Phân quyền (RBAC)**: Các route quản trị phải kiểm tra `$this->request->user()->user['level']`.
