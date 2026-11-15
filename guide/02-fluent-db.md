# 02 - Hướng dẫn sử dụng Fluent DB (`db()`)

SketchCMS sử dụng Fluent Query Builder tại `system/drive/Query/Builder.php` thông qua helper toàn cục `db(string $table)`. Tất cả truy vấn đều tự động bind tham số chống SQL Injection và chạy đồng nhất trên cả **MySQL** và **SQLite**.

## 1. Truy vấn đọc (`SELECT`)

```php
// Lấy tất cả bản ghi
$rows = db('articles_category')->get();

// Chọn cột cụ thể, lọc, sắp xếp, phân trang
$posts = db('articles_post')
    ->select(['id', 'title', 'slug', 'view'])
    ->where('category', 1)
    ->where('view', '>', 10)
    ->orderBy('update_time', 'DESC')
    ->limit(10)
    ->offset(0)
    ->get();

// Lấy 1 bản ghi đầu tiên (trả về array hoặc null)
$user = db('users')->where('nick', 'admin')->first();

// Lấy giá trị 1 cột duy nhất hoặc mảng 1 cột
$nick = db('users')->where('id', 1)->value('nick');
$ids  = db('articles_post')->where('category', 1)->pluck('id');

// Đếm & kiểm tra tồn tại
$total  = db('articles_post')->where('author', 'admin')->count();
$exists = db('articles_tag')->where('slug', 'php')->exists();
```

## 2. Điều kiện nâng cao (`orWhere`, `whereIn`, `whereNull`, `Raw`)

```php
// OR WHERE
$users = db('users')
    ->where('id', $search)
    ->orWhere('nick', 'LIKE', "%{$search}%")
    ->get();

// WHERE IN / NULL / NOT NULL
$list = db('articles_post')->whereIn('id', [1, 2, 3])->get();
$noCat = db('articles_post')->whereNull('category')->get();

// Biểu thức Raw an toàn với bindings
$rows = db('articles_post')
    ->selectRaw('articles_post.*, articles_category.name AS category_name')
    ->leftJoin('articles_category', 'articles_post.category', '=', 'articles_category.id')
    ->whereRaw('LENGTH(articles_post.title) > ?', [5])
    ->orderByRaw('RAND()')
    ->get();

// LEFT JOIN với điều kiện phức tạp
$files = db('articles_file')
    ->selectRaw('articles_file.*, users.name AS user_name')
    ->leftJoinRaw('users ON (articles_file.author = users.id OR articles_file.author = users.nick)')
    ->get();
```

## 3. Thêm, sửa, tăng/giảm, xóa

```php
// INSERT (trả về ID vừa chèn)
$id = db('articles_category')->insert([
    'name' => 'Công nghệ',
    'slug' => 'cong-nghe',
]);

// UPDATE (trả về số dòng bị ảnh hưởng)
db('articles_post')->where('id', $id)->update([
    'title' => 'Tiêu đề mới',
]);

// INCREMENT / DECREMENT
db('articles_post')->where('id', $id)->increment('view');
db('articles_tag')->where('id', $tagId)->where('count', '>', 0)->decrement('count');

// DELETE
db('articles_post')->where('id', $id)->delete();
```

## 4. Lưu ý tương thích MySQL & SQLite
- **Không** dùng `->db->prepare(...)` hay `Database::select(...)` trong `app/modules/`.
- **Không** dùng các hàm chỉ có ở MySQL như `INSERT IGNORE`, `GREATEST()`, `SHOW TABLES` trong module; hãy dùng `exists()` + `insert()`, `where('col', '>', 0)->decrement('col')`, hoặc kiểm tra driver PDO.
