<?php
session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';
$base_dir = dirname(__DIR__);

function removeMarkdown($text)
{
    $text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text);
    $text = preg_replace('/(\*|_)(.*?)\1/', '$2', $text);
    $text = preg_replace('/\#+\s?(.*)/', '$1', $text);
    $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);
    $text = preg_replace('/\-+/', '', $text);
    return nl2br(trim($text));
}

if (file_exists($base_dir . '/install.lock')) {
    die("Hệ thống đã được cài đặt. Vui lòng xóa tệp install.lock để cài lại.");
}

// Check database for existing tables to see if it's already installed
$initPath = $base_dir . '/system/configs/init.php';
if (file_exists($initPath)) {
    $initContent = file_get_contents($initPath);
    preg_match("/define\('DB_HOST',\s*'(.*?)'\);/", $initContent, $hostMatch);
    preg_match("/define\('DB_USER',\s*'(.*?)'\);/", $initContent, $userMatch);
    preg_match("/define\('DB_PASS',\s*'(.*?)'\);/", $initContent, $passMatch);
    preg_match("/define\('DB_NAME',\s*'(.*?)'\);/", $initContent, $nameMatch);

    if (!empty($hostMatch[1]) && !empty($userMatch[1]) && !empty($nameMatch[1])) {
        try {
            // Suppress warnings for connection if DB is not set up yet
            $testConn = @new mysqli($hostMatch[1], $userMatch[1], $passMatch[1] ?? '', $nameMatch[1]);
            if (!$testConn->connect_error) {
                $schemaFile = $base_dir . '/system/configs/schema.json';
                if (file_exists($schemaFile)) {
                    $schemaData = json_decode(file_get_contents($schemaFile), true);
                    $tables = isset($schemaData['tables']) ? $schemaData['tables'] : $schemaData;

                    if (is_array($tables) && count($tables) > 0) {
                        $allTablesExist = true;
                        foreach ($tables as $tName => $tData) {
                            $result = $testConn->query("SHOW TABLES LIKE '$tName'");
                            if (!$result || $result->num_rows === 0) {
                                $allTablesExist = false;
                                break;
                            }
                        }

                        if ($allTablesExist) {
                            die("Hệ thống đã được cài đặt (đã tìm thấy đầy đủ cấu trúc bảng theo schema.json trong CSDL). Vui lòng reset CSDL nếu muốn cài đặt mới.");
                        }
                    }
                }
                $testConn->close();
            }
        } catch (Exception $e) {
            // Ignore connection errors during pre-install check
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 3) {
        $_SESSION['db_host'] = $_POST['db_host'] ?? 'localhost';
        $_SESSION['db_user'] = $_POST['db_user'] ?? 'root';
        $_SESSION['db_pass'] = $_POST['db_pass'] ?? '';
        $_SESSION['db_name'] = $_POST['db_name'] ?? 'sketchcms';

        try {
            $conn = new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
            if ($conn->connect_error) {
                $error = "Không thể kết nối CSDL: " . $conn->connect_error;
            } else {
                $initPath = $base_dir . '/system/configs/init.php';
                if (file_exists($initPath)) {
                    $initContent = file_get_contents($initPath);
                    $initContent = preg_replace("/define\('DB_HOST',\s*'.*?'\);/", "define('DB_HOST', '" . $_SESSION['db_host'] . "');", $initContent);
                    $initContent = preg_replace("/define\('DB_USER',\s*'.*?'\);/", "define('DB_USER', '" . $_SESSION['db_user'] . "');", $initContent);
                    $initContent = preg_replace("/define\('DB_PASS',\s*'.*?'\);/", "define('DB_PASS', '" . $_SESSION['db_pass'] . "');", $initContent);
                    $initContent = preg_replace("/define\('DB_NAME',\s*'.*?'\);/", "define('DB_NAME', '" . $_SESSION['db_name'] . "');", $initContent);
                    file_put_contents($initPath, $initContent);
                }
                header('Location: ?step=4');
                exit;
            }
        } catch (Exception $e) {
            $error = "Không thể kết nối CSDL: " . $e->getMessage();
        }
    } elseif ($step === 4) {
        $admin_user = $_POST['admin_user'] ?? '';
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';

        if (empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
            $error = "Vui lòng nhập đầy đủ thông tin Admin!";
        } else {
            $conn = new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
            $schemaFile = $base_dir . '/system/configs/schema.json';
            if (file_exists($schemaFile)) {
                $schemaData = json_decode(file_get_contents($schemaFile), true);
                $tables = isset($schemaData['tables']) ? $schemaData['tables'] : $schemaData;
                foreach ($tables as $tName => $tData) {
                    if (isset($tData['create_sql'])) {
                        $conn->query("DROP TABLE IF EXISTS `$tName`");
                        $conn->query($tData['create_sql']);
                    }
                }
                $hashPass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (nick, name, pass, email, level, reg) VALUES (?, ?, ?, ?, 127, ?)");
                $time = time();
                if ($stmt) {
                    $stmt->bind_param("ssssi", $admin_user, $admin_user, $hashPass, $admin_email, $time);
                    $stmt->execute();
                }

                // Insert SystemBOT
                $bot_nick = 'SystemBOT';
                $bot_email = 'systembot@localhost';
                $stmtBot = $conn->prepare("INSERT INTO users (nick, name, pass, email, level, reg) VALUES (?, ?, ?, ?, 127, ?)");
                if ($stmtBot) {
                    $stmtBot->bind_param("ssssi", $bot_nick, $bot_nick, $hashPass, $bot_email, $time);
                    $stmtBot->execute();
                }

                // Insert Default Category (News)
                $stmtCat = $conn->prepare("INSERT INTO articles_category (id, name, slug, content, keyword) VALUES (1, 'Tin tức - Sự kiện', 'tin-tuc-su-kien', 'Chuyên mục tin tức', 'tin tức, sự kiện')");
                if ($stmtCat) {
                    $stmtCat->execute();
                }

                // Insert Default Post
                $postTitle = 'Chào mừng cài đặt mã nguồn SketchCMS thành công';
                $postSlug = 'chao-mung-cai-dat-ma-nguon-sketchcms-thanh-cong';
                $postContent = 'Cài đặt mã nguồn SketchCMS thành công! Đây là bài viết mẫu đầu tiên trong chuyên mục Tin tức - Sự kiện. Chúc bạn có những trải nghiệm tuyệt vời với nền tảng này.';

                $stmtPost = $conn->prepare("INSERT INTO articles_post (id, title, slug, content, time, update_time, category, author) VALUES (1, ?, ?, ?, ?, ?, 1, ?)");
                if ($stmtPost) {
                    $stmtPost->bind_param("sssiis", $postTitle, $postSlug, $postContent, $time, $time, $bot_nick);
                    $stmtPost->execute();
                }

                file_put_contents($base_dir . '/install.lock', 'installed');
                header('Location: ?step=5');
                exit;
            } else {
                $error = "Không tìm thấy file schema.json";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cài đặt SketchCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
        }

        .install-box {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .license-box {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container install-box">
        <h2 class="text-center mb-4 text-primary">Cài đặt SketchCMS</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <h4>Bước 1: Điều khoản sử dụng</h4>
            <?php
            $licensePath = $base_dir . '/LICENSE.md';
            $licenseContent = file_exists($licensePath) ? file_get_contents($licensePath) : 'Không tìm thấy tệp LICENSE.md';
            $cleanLicense = removeMarkdown($licenseContent);
            ?>
            <div class="license-box"><?= $cleanLicense ?></div>
            <div class="d-flex justify-content-between">
                <a href="/" class="btn btn-secondary">Hủy bỏ</a>
                <a href="?step=2" class="btn btn-primary">Tôi đồng ý</a>
            </div>

        <?php elseif ($step === 2): ?>
            <h4>Bước 2: Kiểm tra Môi trường</h4>
            <ul class="list-group mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    PHP Version (>= 8.0)
                    <span class="badge bg-<?= version_compare(PHP_VERSION, '8.0.0', '>=') ? 'success' : 'danger' ?> rounded-pill"><?= PHP_VERSION ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    PDO Extension
                    <span class="badge bg-<?= extension_loaded('pdo') ? 'success' : 'danger' ?> rounded-pill"><?= extension_loaded('pdo') ? 'OK' : 'Missing' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    MySQLi Extension
                    <span class="badge bg-<?= extension_loaded('mysqli') ? 'success' : 'danger' ?> rounded-pill"><?= extension_loaded('mysqli') ? 'OK' : 'Missing' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Thư mục system/configs Ghi được
                    <span class="badge bg-<?= is_writable($base_dir . '/system/configs') ? 'success' : 'danger' ?> rounded-pill"><?= is_writable($base_dir . '/system/configs') ? 'OK' : 'No' ?></span>
                </li>
            </ul>
            <div class="d-flex justify-content-between">
                <a href="?step=1" class="btn btn-secondary">Quay lại</a>
                <a href="?step=3" class="btn btn-primary">Tiếp tục</a>
            </div>

        <?php elseif ($step === 3): ?>
            <h4>Bước 3: Cấu hình Cơ sở dữ liệu</h4>
            <form method="post">
                <div class="mb-3">
                    <label>Database Host</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label>Database User</label>
                    <input type="text" name="db_user" class="form-control" value="root" required>
                </div>
                <div class="mb-3">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Database Name</label>
                    <input type="text" name="db_name" class="form-control" value="sketchcms" required>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="?step=2" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu và Tiếp tục</button>
                </div>
            </form>

        <?php elseif ($step === 4): ?>
            <h4>Bước 4: Tạo tài khoản Quản trị viên (Level 127)</h4>
            <p class="text-muted">Hệ thống sẽ tiến hành khởi tạo các bảng dữ liệu dựa trên schema.json và tạo tài khoản Developer gốc.</p>
            <form method="post">
                <div class="mb-3">
                    <label>Tài khoản (Username)</label>
                    <input type="text" name="admin_user" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Mật khẩu</label>
                    <input type="password" name="admin_pass" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger w-100">Bắt đầu Cài đặt & Tạo Admin</button>
            </form>

        <?php elseif ($step === 5): ?>
            <div class="text-center">
                <h1 class="text-success display-1"><i class="fa fa-check-circle"></i></h1>
                <h4 class="mt-3">Cài đặt hoàn tất!</h4>
                <p>Hệ thống đã được cài đặt thành công. Vì lý do bảo mật, file <code>install.lock</code> đã được tạo. Vui lòng tự đổi tên hoặc xóa thư mục <code>install/</code> trên máy chủ của bạn.</p>
                <a href="/" class="btn btn-success mt-3">Đến Trang Chủ</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>