<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 * Email: kioku17@protonmail.com
 * GitHub: https://github.com/pentanoic/
 * Website: https://lab302.ovh/
 *
 * Copyright (c) 2026 valedrat. All rights reserved.
 *
 * This file is part of the SketchCMS source code.
 * Please do not remove or modify this copyright notice.
 */

class managerController extends Controller
{
    private managerModel $managerModel;

    function __construct()
    {
        parent::__construct();
        $this->managerModel = $this->load->model('manager');
        
        // Kiểm tra quyền >= 120
        if (!$this->request->user()->isLogin || $this->request->user()->user['level'] < 120) {
            redirect(url('/'));
        }
    }

    private function verifyAdminAction($expected_module_name, $error_cookie_name, $redirect_url)
    {
        $admin_pass = $_POST['admin_pass'] ?? '';
        $module_name = $_POST['module_name'] ?? '';
        $user = $this->request->user()->user;
        
        $is_valid_module = (strtolower(trim($module_name)) === strtolower($expected_module_name));
        $is_valid_pass = false;

        if (!empty($admin_pass) && !empty($user['pass'])) {
            if (verify_password_seamless($admin_pass, $user['pass'], $user['id'], Container::get(DB::class))) {
                $is_valid_pass = true;
            }
        }

        if (!$is_valid_module || !$is_valid_pass) {
            setcookie($error_cookie_name, 'Thông tin không chính xác. Yêu cầu làm lại!', time() + 5, '/');
            redirect(url($redirect_url));
            return false;
        }

        return true;
    }

    public function index()
    {
        $page_title = 'Bảng điều khiển';
        
        // Lấy thống kê
        $totalUsers = $this->managerModel->GetTotalUsers();
        $totalPosts = $this->managerModel->GetTotalPosts();
        $totalCategories = $this->managerModel->GetTotalCategories();
        $totalShouts = $this->managerModel->GetTotalShouts();

        return view('manager/dashboard', [
            'page_title' => $page_title,
            'totalUsers' => $totalUsers,
            'totalPosts' => $totalPosts,
            'totalCategories' => $totalCategories,
            'totalShouts' => $totalShouts
        ]);
    }

    public function settings()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 126) {
            redirect(url('/manager'));
        }
        $page_title = 'Cài đặt Website & SEO';
        $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
        $systemConfig = require $systemConfigPath;
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyAdminAction('settings', 'manager_settings_error', '/manager/settings')) return;

            $systemConfig['app']['name'] = $_POST['app_name'] ?? $systemConfig['app']['name'];
            $systemConfig['app']['description'] = $_POST['app_description'] ?? $systemConfig['app']['description'];
            $systemConfig['app']['keyword'] = $_POST['app_keyword'] ?? $systemConfig['app']['keyword'];
            $systemConfig['app']['site']['scheme'] = $_POST['app_scheme'] ?? $systemConfig['app']['site']['scheme'];
            $systemConfig['app']['site']['host'] = $_POST['app_host'] ?? $systemConfig['app']['site']['host'];
            
            // Format lại mảng
            $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
            $content = str_replace(["array (", ")"], ["[", "]"], $content);

            file_put_contents($systemConfigPath, $content);
            $success = 'Cập nhật cấu hình thành công!';
        }

        return view('manager/settings', [
            'page_title' => $page_title,
            'systemConfig' => $systemConfig,
            'success' => $success
        ]);
    }

    public function telegramConfig()
    {
        // Chặn request không phải POST hoặc không có quyền
        if ($this->request->getMethod() !== 'POST') {
            return ['status' => 'error', 'message' => 'Method not allowed'];
        }

        if ($this->request->user()->user['level'] < 120) {
            return ['status' => 'error', 'message' => 'Bạn không có quyền thực hiện chức năng này'];
        }

        $mode = $this->request->postVar('TELEGRAM_UPLOAD_MODE', '');
        $token = $this->request->postVar('TELEGRAM_BOT_TOKEN', '');
        $chat_id = $this->request->postVar('TELEGRAM_CHAT_ID', '');
        $worker_url = $this->request->postVar('CLOUDFLARE_WORKER_URL', '');

        if (empty($mode) || empty($token) || empty($chat_id)) {
            return ['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'];
        }

        $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
        if (!is_writable($systemConfigPath)) {
            return ['status' => 'error', 'message' => 'File system.php không có quyền ghi.'];
        }

        $systemConfig = require $systemConfigPath;
        if (!isset($systemConfig['media'])) {
            $systemConfig['media'] = [];
        }

        $systemConfig['media']['telegram_upload_mode'] = $mode;
        $systemConfig['media']['telegram_bot_token'] = $token;
        $systemConfig['media']['telegram_chat_id'] = $chat_id;
        $systemConfig['media']['cloudflare_worker_url'] = $worker_url;

        // Format lại mảng
        $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
        $content = str_replace(["array (", ")"], ["[", "]"], $content);

        if (file_put_contents($systemConfigPath, $content)) {
            return ['status' => 'success', 'message' => 'Lưu cấu hình thành công'];
        } else {
            return ['status' => 'error', 'message' => 'Không thể ghi file system.php'];
        }
    }

    public function templates()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        $page_title = 'Quản lý Giao diện';
        $valid_templates = [];
        $dirs = scandir(TEMPLATES);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            $full_path = TEMPLATES . $dir . DIRECTORY_SEPARATOR;
            if (is_dir($full_path) && file_exists($full_path . 'desc.json')) {
                $desc = json_decode(file_get_contents($full_path . 'desc.json'), true);
                $desc['folder'] = $dir;
                $valid_templates[] = $desc;
            }
        }
        
        $default_template = config('system.app.default_template');
        if (empty($default_template)) {
            $default_template = $valid_templates[0]['folder'] ?? '';
        }

        return view('manager/templates', [
            'page_title' => $page_title,
            'templates' => $valid_templates,
            'default_template' => $default_template
        ]);
    }

    public function template_set_default()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('templates', 'manager_templates_error', '/manager/templates')) return;

        $template = $_POST['template'] ?? '';
        $full_path = TEMPLATES . $template . DIRECTORY_SEPARATOR;
        
        if (is_dir($full_path) && file_exists($full_path . 'desc.json')) {
            $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
            $systemConfig = require $systemConfigPath;
            $systemConfig['app']['default_template'] = $template;
            
            $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
            $content = str_replace(["array (", ")"], ["[", "]"], $content);
            file_put_contents($systemConfigPath, $content);
        }
        
        redirect(url('/manager/templates'));
    }

    public function template_download()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('templates', 'manager_templates_error', '/manager/templates')) return;

        $template = $_POST['template'] ?? '';
        $full_path = TEMPLATES . $template . DIRECTORY_SEPARATOR;
        
        if (is_dir($full_path) && file_exists($full_path . 'desc.json')) {
            $zip_file = sys_get_temp_dir() . '/' . $template . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($full_path),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($full_path));
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.basename($zip_file).'"');
                header('Content-Length: ' . filesize($zip_file));
                readfile($zip_file);
                unlink($zip_file);
                exit;
            }
        }
        redirect(url('/manager/templates'));
    }

    public function template_view()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        $template = $_GET['t'] ?? '';
        $subpath = $_GET['f'] ?? '';
        
        $base_path = TEMPLATES . $template;
        if (empty($template) || !is_dir($base_path) || !file_exists($base_path . DIRECTORY_SEPARATOR . 'desc.json')) {
            flash('error', 'Giao diện không tồn tại.');
            redirect(url('/manager/templates'));
        }

        // Ngăn chặn Path Traversal
        if (strpos($subpath, '..') !== false) {
            $subpath = '';
        }

        $target_path = rtrim($base_path . DIRECTORY_SEPARATOR . ltrim($subpath, '/\\'), '/\\');
        
        $file_content = null;
        $is_file = false;
        
        if (is_file($target_path)) {
            $is_file = true;
            // Chỉ đọc file văn bản cơ bản
            $ext = pathinfo($target_path, PATHINFO_EXTENSION);
            $readable_exts = ['php', 'css', 'js', 'json', 'txt', 'html', 'md'];
            if (in_array(strtolower($ext), $readable_exts)) {
                $file_content = file_get_contents($target_path);
            } else {
                $file_content = "Không thể xem trước định dạng tệp này.";
            }
        }
        
        // Liệt kê thư mục hiện tại hoặc cha
        $dir_path = $is_file ? dirname($target_path) : $target_path;
        
        $items = [];
        if (is_dir($dir_path)) {
            $files = scandir($dir_path);
            foreach ($files as $f) {
                if ($f === '.') continue;
                if ($f === '..' && $dir_path === $base_path) continue;
                
                $f_path = $dir_path . DIRECTORY_SEPARATOR . $f;
                $rel_path = str_replace($base_path, '', $f_path);
                $rel_path = ltrim(str_replace('\\', '/', $rel_path), '/');
                
                $items[] = [
                    'name' => $f,
                    'is_dir' => is_dir($f_path),
                    'path' => $rel_path
                ];
            }
        }
        
        // Sắp xếp: thư mục lên trước
        usort($items, function($a, $b) {
            if ($a['is_dir'] && !$b['is_dir']) return -1;
            if (!$a['is_dir'] && $b['is_dir']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });

        return view('manager/template_view', [
            'page_title' => 'Xem giao diện: ' . $template,
            'template' => $template,
            'items' => $items,
            'is_file' => $is_file,
            'file_content' => $file_content,
            'current_file' => basename($target_path)
        ]);
    }

    public function users()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 120) {
            redirect(url('/manager'));
        }
        $page_title = 'Quản lý Người dùng';
        $search = $_GET['q'] ?? '';
        $sort_by = $_GET['sort'] ?? 'id';
        
        $totalItems = $this->managerModel->GetTotalUsersSearch($search);
        
        // Cấu hình phân trang
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        
        $totalPages = ceil($totalItems / $limit);
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        
        $start = ($page - 1) * $limit;
        
        $users = $this->managerModel->GetUsers($start, $limit, $search, $sort_by);
        
        return view('manager/users', [
            'page_title' => $page_title,
            'users' => $users,
            'search' => $search,
            'sort_by' => $sort_by,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems
        ]);
    }

    public function user_ban()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 121) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('users', 'manager_users_error', '/manager/users')) return;

        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $this->managerModel->ToggleBanUser($id);
        }
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? url('/manager/users');
        redirect($redirect_url);
    }

    public function user_reset_pass()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 122) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('users', 'manager_users_error', '/manager/users')) return;

        $target_id = $_POST['target_id'] ?? 0;
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_id = $this->request->user()->user['id'] ?? 0;

        if ($target_id > 0 && !empty($admin_pass)) {
            $result = $this->managerModel->ResetPassword($target_id, $admin_id, $admin_pass);
            if ($result['status']) {
                setcookie('manager_users_success', $result['message'], time() + 5, '/');
            } else {
                setcookie('manager_users_error', $result['message'], time() + 5, '/');
            }
        }
        
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? url('/manager/users');
        redirect($redirect_url);
    }

    public function user_edit()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 120) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('users', 'manager_users_error', '/manager/users')) return;

        $target_id = $_POST['target_id'] ?? 0;
        $name = $_POST['user_name'] ?? '';
        $level = $_POST['user_level'] ?? 0;
        $xu = $_POST['user_xu'] ?? 0;
        
        $admin_id = $this->request->user()->user['id'] ?? 0;

        if ($target_id > 0) {
            $data = [
                'name' => $name,
                'level' => (int)$level,
                'xu' => (int)$xu
            ];
            $result = $this->managerModel->EditUser($target_id, $admin_id, $data);
            if ($result['status']) {
                setcookie('manager_users_success', $result['message'], time() + 5, '/');
            } else {
                setcookie('manager_users_error', $result['message'], time() + 5, '/');
            }
        }
        
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? url('/manager/users');
        redirect($redirect_url);
    }

    public function shoutbox()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 120) {
            redirect(url('/manager'));
        }
        $page_title = 'Làm sạch Chatbox';
        $totalShouts = $this->managerModel->GetTotalShouts();
        
        return view('manager/shoutbox', [
            'page_title' => $page_title,
            'totalShouts' => $totalShouts
        ]);
    }

    public function shoutbox_clean()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 120) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('shoutbox', 'manager_shoutbox_error', '/manager/shoutbox')) return;

        // Chức năng này xóa toàn bộ dữ liệu bảng chat (chỉ giữ lại 50 tin nhắn cuối)
        $this->managerModel->CleanShoutbox();
        setcookie('manager_shoutbox_success', 'Đã làm sạch Chatbox và giữ lại 50 tin nhắn cuối thành công!', time() + 5, '/');
        
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? url('/manager/shoutbox');
        redirect($redirect_url);
    }

    public function articles()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 126) {
            redirect(url('/manager'));
        }
        $page_title = 'Quản lý Diễn đàn';
        $tab = $_GET['tab'] ?? 'categories';
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $categories = [];
        $posts = [];
        $chapters = [];
        $total_posts = 0;
        $total_chapters = 0;

        if ($tab === 'categories') {
            $categories = $this->managerModel->GetArticlesCategories();
        } elseif ($tab === 'posts') {
            $posts = $this->managerModel->GetArticlesPosts($start, $limit, $search);
            $total_posts = $this->managerModel->GetTotalArticlesPosts($search);
        } else {
            // tab chapters
            $chapters = $this->managerModel->GetArticlesChapters($start, $limit, $search);
            $total_chapters = $this->managerModel->GetTotalArticlesChapters($search);
        }

        $total_pages = 1;
        if ($tab === 'posts') $total_pages = ceil($total_posts / $limit);
        if ($tab === 'chapters') $total_pages = ceil($total_chapters / $limit);

        return view('manager/articles', [
            'page_title' => $page_title,
            'tab' => $tab,
            'categories' => $categories,
            'posts' => $posts,
            'chapters' => $chapters,
            'search' => $search,
            'page' => $page,
            'total_pages' => max(1, $total_pages)
        ]);
    }

    public function articles_cat_add()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 126) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('articles', 'manager_articles_error', '/manager/articles?tab=categories')) return;

        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $content = $_POST['content'] ?? '';
        $keyword = $_POST['keyword'] ?? '';
        
        $metaData = [
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_desc' => $_POST['meta_desc'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? ''
        ];
        $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE);
        
        $this->managerModel->AddArticlesCategory(['name' => $name, 'slug' => $slug, 'content' => $content, 'keyword' => $keyword, 'meta_data' => $metaDataJson]);
        setcookie('manager_articles_success', 'Đã thêm chuyên mục mới!', time() + 5, '/');
        redirect(url('/manager/articles?tab=categories'));
    }

    public function articles_cat_edit()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 126) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('articles', 'manager_articles_error', '/manager/articles?tab=categories')) return;

        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $content = $_POST['content'] ?? '';
        $keyword = $_POST['keyword'] ?? '';
        
        $metaData = [
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_desc' => $_POST['meta_desc'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? ''
        ];
        $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE);
        
        if ($id > 0) {
            $this->managerModel->EditArticlesCategory($id, ['name' => $name, 'slug' => $slug, 'content' => $content, 'keyword' => $keyword, 'meta_data' => $metaDataJson]);
            setcookie('manager_articles_success', 'Đã cập nhật chuyên mục!', time() + 5, '/');
        }
        redirect(url('/manager/articles?tab=categories'));
    }

    public function articles_cat_delete()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 126) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('articles', 'manager_articles_error', '/manager/articles?tab=categories')) return;

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->managerModel->DeleteArticlesCategory($id);
            setcookie('manager_articles_success', 'Đã xóa chuyên mục và toàn bộ bài viết bên trong!', time() + 5, '/');
        }
        redirect(url('/manager/articles?tab=categories'));
    }

    public function articles_post_delete()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 121) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('articles', 'manager_articles_error', '/manager/articles?tab=posts')) return;
        
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $this->managerModel->DeleteArticlesPost($id);
            setcookie('manager_articles_success', 'Đã xóa bài viết vĩnh viễn!', time() + 5, '/');
        }
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? url('/manager/articles?tab=posts');
        redirect($redirect_url);
    }

    public function articles_chapter_delete()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 121) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('articles', 'manager_articles_error', '/manager/articles?tab=chapters')) return;
        
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $this->managerModel->DeleteArticlesChapter($id);
            setcookie('manager_articles_success', 'Đã xóa chương vĩnh viễn!', time() + 5, '/');
        }
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? url('/manager/articles?tab=chapters');
        redirect($redirect_url);
    }

    // ==========================================
    // URL RULES MANAGEMENT
    // ==========================================

    public function url_rules()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        $systemPath = SYSTEM . 'configs/autoload/system.php';
        $systemConfig = file_exists($systemPath) ? include $systemPath : [];
        
        $url_rules = $systemConfig['url_rules'] ?? ['rewrite' => [], 'disable' => []];

        return view('manager/url_rules', [
            'page_title' => 'Quản lý Rewrite/Disable URL',
            'panelActive' => 'url_rules',
            'url_rules' => $url_rules
        ]);
    }

    public function url_rules_add()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('url_rules', 'manager_url_error', '/manager/url_rules')) return;
        
        $type = $_POST['rule_type'] ?? '';
        $path = $_POST['rule_path'] ?? '';
        $target = $_POST['rule_target'] ?? '';

        if (empty($path) || substr($path, 0, 1) !== '/') {
            setcookie('manager_url_error', 'Đường dẫn phải bắt đầu bằng dấu /', time() + 5, '/');
            redirect(url('/manager/url_rules'));
            return;
        }

        $forbidden = ['/manager', '/login', '/logout', '/home', '/index'];
        if (in_array(strtolower($path), $forbidden)) {
            setcookie('manager_url_error', 'Không được phép rewrite/disable các URL hệ thống quan trọng!', time() + 5, '/');
            redirect(url('/manager/url_rules'));
            return;
        }

        if ($type === 'rewrite') {
            if (empty($target)) {
                setcookie('manager_url_error', 'Đích đến (Controller@method) không được để trống!', time() + 5, '/');
                redirect(url('/manager/url_rules'));
                return;
            }
            if (preg_match('/\.(php|html|htm)$/i', $path)) {
                setcookie('manager_url_error', 'Không được phép đặt rewrite có đuôi .php, .html, .htm', time() + 5, '/');
                redirect(url('/manager/url_rules'));
                return;
            }
        }

        $systemPath = SYSTEM . 'configs/autoload/system.php';
        $systemConfig = file_exists($systemPath) ? include $systemPath : [];
        if (!isset($systemConfig['url_rules'])) {
            $systemConfig['url_rules'] = ['rewrite' => [], 'disable' => []];
        }

        if ($type === 'disable') {
            if (!in_array($path, $systemConfig['url_rules']['disable'])) {
                $systemConfig['url_rules']['disable'][] = $path;
            }
        } elseif ($type === 'rewrite') {
            if (empty($target)) {
                setcookie('manager_url_error', 'Vui lòng nhập đích đến!', time() + 5, '/');
                redirect(url('/manager/url_rules'));
                return;
            }
            $parts = explode('@', $target);
            $systemConfig['url_rules']['rewrite'][$path] = [
                'controller' => $parts[0] ?? '',
                'action' => $parts[1] ?? '',
                'method' => $method
            ];
        } else {
            redirect(url('/manager/url_rules'));
            return;
        }

        $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
        file_put_contents($systemPath, $content);

        setcookie('manager_url_success', 'Đã thêm quy tắc thành công!', time() + 5, '/');
        redirect(url('/manager/url_rules'));
    }

    public function url_rules_edit()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('url_rules', 'manager_url_error', '/manager/url_rules')) return;

        $old_path = $_POST['old_path'] ?? '';
        $type = $_POST['rule_type'] ?? '';
        $path = $_POST['rule_path'] ?? '';
        $target = $_POST['rule_target'] ?? '';
        $method = $_POST['rule_method'] ?? 'GET|POST';

        $type = strtolower(trim($type));
        $path = strtolower(trim($path));
        $target = trim($target);
        $method = strtoupper(trim($method));
        $method = strtoupper(trim($method));

        if (empty($path) || empty($target) || empty($old_path)) {
            setcookie('manager_url_error', 'Vui lòng nhập đủ thông tin!', time() + 5, '/');
            redirect(url('/manager/url_rules'));
            return;
        }

        if (strpos($path, '/') !== 0) {
            setcookie('manager_url_error', 'Đường dẫn phải bắt đầu bằng /', time() + 5, '/');
            redirect(url('/manager/url_rules'));
            return;
        }

        $forbidden = ['/manager', '/login', '/logout', '/home', '/index'];
        foreach ($forbidden as $f) {
            if (strpos($path, $f) === 0) {
                setcookie('manager_url_error', 'Không được phép thiết lập URL bắt đầu bằng ' . $f, time() + 5, '/');
                redirect(url('/manager/url_rules'));
                return;
            }
        }

        if (preg_match('/\.php$|\.html?$|\.htm$/i', $path)) {
            setcookie('manager_url_error', 'Không được phép đặt URL có đuôi .php, .html, .htm', time() + 5, '/');
            redirect(url('/manager/url_rules'));
            return;
        }

        $systemPath = APP . 'Configs/autoload/system.php';
        if (!file_exists($systemPath)) {
            setcookie('manager_url_error', 'File cấu hình không tồn tại!', time() + 5, '/');
            redirect(url('/manager/url_rules'));
            return;
        }

        $systemConfig = require $systemPath;
        if (!isset($systemConfig['url_rules'])) {
            $systemConfig['url_rules'] = ['disable' => [], 'rewrite' => []];
        }

        // Remove old path
        if (isset($systemConfig['url_rules']['rewrite'][$old_path])) {
            unset($systemConfig['url_rules']['rewrite'][$old_path]);
        }

        // Add new path
        $parts = explode('@', $target);
        $systemConfig['url_rules']['rewrite'][$path] = [
            'controller' => $parts[0] ?? '',
            'action' => $parts[1] ?? '',
            'method' => $method
        ];

        $content = "<?php\n\nreturn " . var_export($systemConfig, true) . ";\n";
        file_put_contents($systemPath, $content);

        setcookie('manager_url_success', 'Cập nhật quy tắc thành công!', time() + 5, '/');
        redirect(url('/manager/url_rules'));
    }

    public function url_rules_delete()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('url_rules', 'manager_url_error', '/manager/url_rules')) return;

        $type = $_POST['rule_type'] ?? '';
        $path = $_POST['rule_path'] ?? '';

        $systemPath = SYSTEM . 'configs/autoload/system.php';
        $systemConfig = file_exists($systemPath) ? include $systemPath : [];
        
        if (isset($systemConfig['url_rules'][$type])) {
            if ($type === 'disable') {
                $key = array_search($path, $systemConfig['url_rules']['disable']);
                if ($key !== false) {
                    unset($systemConfig['url_rules']['disable'][$key]);
                    $systemConfig['url_rules']['disable'] = array_values($systemConfig['url_rules']['disable']); // reindex
                }
            } elseif ($type === 'rewrite') {
                unset($systemConfig['url_rules']['rewrite'][$path]);
            }

            $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
            file_put_contents($systemPath, $content);
            setcookie('manager_url_success', 'Đã xóa quy tắc!', time() + 5, '/');
        }

        redirect(url('/manager/url_rules'));
    }

    // ==========================================
    // MODULE MANAGEMENT
    // ==========================================

    private $core_modules = ['articles', 'home', 'mail', 'manager', 'shoutbox', 'user'];

    public function modules()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        $modulesPath = APP . 'modules/';
        $customModulesPath = APP . 'modules/custom_module/';
        $core_modules_list = [];
        $extended_modules_list = [];

        if (is_dir($modulesPath)) {
            $dirs = array_diff(scandir($modulesPath), ['.', '..']);
            foreach ($dirs as $dir) {
                if ($dir === 'custom_module') continue;
                if (is_dir($modulesPath . $dir)) {
                    $is_core = in_array(strtolower($dir), $this->core_modules);
                    $core_modules_list[] = [
                        'name' => $dir,
                        'is_core' => $is_core
                    ];
                }
            }
        }
        
        if (is_dir($customModulesPath)) {
            $dirs = array_diff(scandir($customModulesPath), ['.', '..']);
            foreach ($dirs as $dir) {
                if (is_dir($customModulesPath . $dir)) {
                    $extended_modules_list[] = [
                        'name' => $dir,
                        'is_core' => false
                    ];
                }
            }
        }

        return view('manager/modules', [
            'page_title' => 'Quản lý Module',
            'panelActive' => 'modules',
            'core_modules' => $core_modules_list,
            'extended_modules' => $extended_modules_list
        ]);
    }

    public function module_add()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;

        $name = $_POST['module_name_new'] ?? '';
        $name = strtolower(trim($name));

        if (!preg_match('/^custom_[a-z0-9_]+$/', $name)) {
            setcookie('manager_module_error', 'Tên module mới phải bắt đầu bằng custom_ và chỉ chứa chữ cái, số, gạch dưới.', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }

        $modulePath = (strpos($name, 'custom_') === 0) ? APP . 'modules/custom_module/' . $name : APP . 'modules/' . $name;
        if (is_dir($modulePath)) {
            setcookie('manager_module_error', 'Module đã tồn tại!', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }

        mkdir($modulePath, 0777, true);
        
        $templateFolderName = $name;
        $templatePath = TEMPLATES . get_template() . '/custom_module/' . $templateFolderName;
        if (!is_dir($templatePath)) {
            mkdir($templatePath, 0777, true);
        }

        $controllerName = $name . 'Controller';
        $modelName = $name . 'Model';
        $libraryName = $name . 'Library';
        $routerName = $name . 'Router';
        $installSql = $name . '_install.sql';
        $uninstallSql = $name . '_uninstall.sql';

        $controllerContent = "<?php\nclass {$controllerName} extends Controller\n{\n    public function index()\n    {\n        return 'Hello from {$name}';\n    }\n}\n";
        $modelContent = "<?php\nclass {$modelName} extends Model\n{\n    \n}\n";
        $libraryContent = "<?php\nclass {$libraryName}\n{\n    \n}\n";
        $routerContent = "<?php\n// Example router\n\$router->add('/" . str_replace('custom_', '', $name) . "', '{$controllerName}@index', 'GET');\n";
        $installSqlContent = "-- SQL commands to run when installing module\n";
        $uninstallSqlContent = "-- SQL commands to run when uninstalling module\n";
        $descContent = json_encode([
            'title' => $name,
            'description' => 'Mô tả cho ' . $name,
            'author' => 'System',
            'version' => '1.0.0'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents($modulePath . '/' . $controllerName . '.php', $controllerContent);
        file_put_contents($modulePath . '/' . $modelName . '.php', $modelContent);
        file_put_contents($modulePath . '/' . $libraryName . '.php', $libraryContent);
        file_put_contents($modulePath . '/' . $routerName . '.php', $routerContent);
        file_put_contents($modulePath . '/' . $installSql, $installSqlContent);
        file_put_contents($modulePath . '/' . $uninstallSql, $uninstallSqlContent);
        file_put_contents($modulePath . '/description.json', $descContent);

        setcookie('manager_module_success', 'Tạo module mới thành công!', time() + 5, '/');
        redirect(url('/manager/modules'));
    }

    public function module_delete()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;

        $name = $_POST['name'] ?? '';
        
        if (in_array(strtolower($name), $this->core_modules)) {
            setcookie('manager_module_error', 'Không thể xóa module gốc!', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }

        $modulePath = (strpos($name, 'custom_') === 0) ? APP . 'modules/custom_module/' . $name : APP . 'modules/' . $name;
        if (is_dir($modulePath) && strpos($name, 'custom_') === 0) {
            // Delete recursively
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($modulePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($modulePath);
            
            // Delete template dir if exists
            $templateFolderName = $name;
            $templatePath = TEMPLATES . get_template() . '/custom_module/' . $templateFolderName;
            if (is_dir($templatePath)) {
                $tfiles = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($templatePath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($tfiles as $tfileinfo) {
                    $todo = ($tfileinfo->isDir() ? 'rmdir' : 'unlink');
                    $todo($tfileinfo->getRealPath());
                }
                rmdir($templatePath);
            }

            setcookie('manager_module_success', 'Đã xóa module thành công!', time() + 5, '/');
        }
        
        redirect(url('/manager/modules'));
    }

    public function module_download()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;

        $name = $_POST['name'] ?? '';
        
        if (in_array(strtolower($name), $this->core_modules)) {
            setcookie('manager_module_error', 'Không thể tải xuống module gốc!', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }

        $desc_name = $_POST['module_desc_name'] ?? $name;
        $desc_func = $_POST['module_desc_func'] ?? 'Không có mô tả';
        $desc_author = $_POST['module_desc_author'] ?? 'Unknown';
        $desc_version = $_POST['module_desc_version'] ?? '1.0.0';

        $modulePath = (strpos($name, 'custom_') === 0) ? APP . 'modules/custom_module/' . $name : APP . 'modules/' . $name;
        $templateFolderName = $name;
        $templatePath = TEMPLATES . get_template() . '/custom_module/' . $templateFolderName;
        
        if (is_dir($modulePath) && strpos($name, 'custom_') === 0) {
            $zipFile = ROOT . 'scratch/' . $name . '.zip';
            if (!is_dir(ROOT . 'scratch')) mkdir(ROOT . 'scratch');
            
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Add app/modules code
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($modulePath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($modulePath) + 1);
                        $zip->addFile($filePath, 'app/modules/' . $name . '/' . str_replace('\\', '/', $relativePath));
                    }
                }

                // Add templates
                if (is_dir($templatePath)) {
                    $tfiles = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($templatePath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    foreach ($tfiles as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($templatePath) + 1);
                            // Zip structure: templates/[active_template]/custom_module/[name_without_custom]
                            $zip->addFile($filePath, 'templates/' . get_template() . '/custom_module/' . $templateFolderName . '/' . str_replace('\\', '/', $relativePath));
                        }
                    }
                }

                // Add module.json
                $moduleData = [
                    'name' => $desc_name,
                    'function' => $desc_func,
                    'author' => $desc_author,
                    'version' => $desc_version,
                    'system_name' => $name,
                    'template' => get_template()
                ];
                $zip->addFromString('module.json', json_encode($moduleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename=' . $name . '_package.zip');
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
                unlink($zipFile);
                exit;
            }
        }
        
        redirect(url('/manager/modules'));
    }

    public function module_upload()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;

        if (!isset($_FILES['module_zip']) || $_FILES['module_zip']['error'] !== UPLOAD_ERR_OK) {
            setcookie('manager_module_error', 'Lỗi tải lên file.', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }

        $tmp_file = $_FILES['module_zip']['tmp_name'];
        $zip = new ZipArchive();
        if ($zip->open($tmp_file) === TRUE) {
            // Check for module.json
            $json_str = $zip->getFromName('module.json');
            if ($json_str === false) {
                setcookie('manager_module_error', 'File zip không hợp lệ (thiếu module.json).', time() + 5, '/');
                $zip->close();
                redirect(url('/manager/modules'));
                return;
            }
            
            $moduleData = json_decode($json_str, true);
            $system_name = $moduleData['system_name'] ?? '';
            
            if (empty($system_name) || strpos($system_name, 'custom_') !== 0) {
                setcookie('manager_module_error', 'Tên hệ thống của module không hợp lệ.', time() + 5, '/');
                $zip->close();
                redirect(url('/manager/modules'));
                return;
            }
            
            if (is_dir((strpos($system_name, 'custom_') === 0) ? APP . 'modules/custom_module/' . $system_name : APP . 'modules/' . $system_name)) {
                setcookie('manager_module_error', 'Module này đã tồn tại trên hệ thống.', time() + 5, '/');
                $zip->close();
                redirect(url('/manager/modules'));
                return;
            }

            // Extract to temporary folder for scanning
            $tmp_dir = ROOT . 'scratch/tmp_upload_' . uniqid();
            mkdir($tmp_dir, 0777, true);
            $zip->extractTo($tmp_dir);
            $zip->close();

            // Scan for malicious shell functions
            $is_safe = true;
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tmp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $content = file_get_contents($file->getRealPath());
                    if (preg_match('/(?:system|exec|shell_exec|passthru|eval|proc_open|popen)\s*\(/i', $content)) {
                        $is_safe = false;
                        break;
                    }
                }
            }

            if (!$is_safe) {
                // Delete tmp dir
                $this->deleteDir($tmp_dir);
                setcookie('manager_module_error', 'Phát hiện hàm nguy hiểm trong mã nguồn! Tải lên bị từ chối.', time() + 5, '/');
                redirect(url('/manager/modules'));
                return;
            }

            // Copy files to actual locations
            // In the ZIP, files are typically packed under app/modules/... and templates/...
            $app_src = $tmp_dir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $system_name;
            $templateFolderName = $system_name;
            // Default template from json or fallback
            $tpl_name = $moduleData['template'] ?? 'mec';
            $tpl_src = $tmp_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $tpl_name . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $templateFolderName;
            
            if (is_dir($app_src)) {
                $app_dest = (strpos($system_name, 'custom_') === 0) ? APP . 'modules' . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $system_name : APP . 'modules' . DIRECTORY_SEPARATOR . $system_name;
                // Chỉ copy các file an toàn
                $this->copyDirSafe($app_src, $app_dest);
            }
            if (is_dir($tpl_src)) {
                $tpl_dest = TEMPLATES . get_template() . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $templateFolderName;
                // Template ko được chứa php độc hại
                $this->copyDirSafe($tpl_src, $tpl_dest, true);
            }

            $this->deleteDir($tmp_dir);
            setcookie('manager_module_success', 'Tải lên và cài đặt module thành công!', time() + 5, '/');
        } else {
            setcookie('manager_module_error', 'Không thể đọc file zip.', time() + 5, '/');
        }

        redirect(url('/manager/modules'));
    }

    public function module_install()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;
        
        $name = $_POST['name'] ?? '';
        $installSql = (strpos($name, 'custom_') === 0) ? APP . 'modules' . DS . 'custom_module' . DS . $name . DS . $name . '_install.sql' : APP . 'modules' . DS . $name . DS . $name . '_install.sql';
        if (file_exists($installSql) && strpos($name, 'custom_') === 0) {
            $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            try {
                $sql = file_get_contents($installSql);
                $queries = explode(';', $sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        try {
                            $db->exec($query);
                        } catch (PDOException $e) {
                            if ($e->getCode() != '42S21' && $e->getCode() != '42S01') {
                                throw $e;
                            }
                        }
                    }
                }
                
                // Save to system config
                $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
                $systemConfig = require $systemConfigPath;
                if (!isset($systemConfig['app']['installed_modules'])) {
                    $systemConfig['app']['installed_modules'] = [];
                }
                if (!in_array($name, $systemConfig['app']['installed_modules'])) {
                    $systemConfig['app']['installed_modules'][] = $name;
                    $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
                    $content = str_replace(["array (", ")"], ["[", "]"], $content);
                    file_put_contents($systemConfigPath, $content);
                }

                setcookie('manager_module_success', 'Đã cài đặt module thành công!', time() + 5, '/');
            } catch (Exception $e) {
                setcookie('manager_module_error', 'Lỗi cài đặt: ' . $e->getMessage(), time() + 5, '/');
            }
        } else {
            setcookie('manager_module_error', 'Không tìm thấy file cài đặt hoặc module không hợp lệ!', time() + 5, '/');
        }
        redirect(url('/manager/modules'));
    }

    public function module_uninstall()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;
        
        $name = $_POST['name'] ?? '';
        $uninstallSql = (strpos($name, 'custom_') === 0) ? APP . 'modules' . DS . 'custom_module' . DS . $name . DS . $name . '_uninstall.sql' : APP . 'modules' . DS . $name . DS . $name . '_uninstall.sql';
        if (file_exists($uninstallSql) && strpos($name, 'custom_') === 0) {
            $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            try {
                $sql = file_get_contents($uninstallSql);
                $queries = explode(';', $sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $db->exec($query);
                    }
                }
                
                // Remove from system config
                $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
                $systemConfig = require $systemConfigPath;
                if (isset($systemConfig['app']['installed_modules'])) {
                    $key = array_search($name, $systemConfig['app']['installed_modules']);
                    if ($key !== false) {
                        unset($systemConfig['app']['installed_modules'][$key]);
                        // Reindex array
                        $systemConfig['app']['installed_modules'] = array_values($systemConfig['app']['installed_modules']);
                        $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
                        $content = str_replace(["array (", ")"], ["[", "]"], $content);
                        file_put_contents($systemConfigPath, $content);
                    }
                }

                setcookie('manager_module_success', 'Đã gỡ cài đặt module thành công!', time() + 5, '/');
            } catch (Exception $e) {
                setcookie('manager_module_error', 'Lỗi gỡ cài đặt: ' . $e->getMessage(), time() + 5, '/');
            }
        } else {
            setcookie('manager_module_error', 'Không tìm thấy file gỡ cài đặt hoặc module không hợp lệ!', time() + 5, '/');
        }
        redirect(url('/manager/modules'));
    }

    public function module_edit_info()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        if (!$this->verifyAdminAction('modules', 'manager_module_error', '/manager/modules')) return;
        
        $name = $_POST['name'] ?? '';
        $newName = trim($_POST['module_new_name'] ?? '');
        $title = $_POST['module_desc_title'] ?? '';
        $desc = $_POST['module_desc_desc'] ?? '';
        $author = $_POST['module_desc_author'] ?? '';
        $version = $_POST['module_desc_version'] ?? '';
        
        if ($name && strpos($name, 'custom_') === 0) {
            $backend_path = APP . 'modules' . DS . $name;
            $frontend_path = TEMPLATES . get_template() . DS . 'custom_module' . DS . $name;
            
            // Handle Renaming Module Core
            if (!empty($newName) && $newName !== $name && strpos($newName, 'custom_') === 0) {
                $new_backend_path = APP . 'modules' . DS . $newName;
                $new_frontend_path = TEMPLATES . get_template() . DS . 'custom_module' . DS . $newName;
                
                if (file_exists($new_backend_path)) {
                    setcookie('manager_module_error', 'Mã module mới đã tồn tại!', time() + 5, '/');
                    redirect(url('/manager/modules'));
                    return;
                }
                
                // Rename backend folder
                rename($backend_path, $new_backend_path);
                
                // Rename frontend folder
                if (file_exists($frontend_path)) {
                    rename($frontend_path, $new_frontend_path);
                }
                
                // Rename files inside new backend folder
                $filesToRename = [
                    $name . 'Controller.php' => $newName . 'Controller.php',
                    $name . 'Model.php' => $newName . 'Model.php',
                    $name . 'Library.php' => $newName . 'Library.php',
                    $name . 'Router.php' => $newName . 'Router.php',
                    $name . '_install.sql' => $newName . '_install.sql',
                    $name . '_uninstall.sql' => $newName . '_uninstall.sql'
                ];
                
                foreach ($filesToRename as $oldFile => $newFile) {
                    if (file_exists($new_backend_path . DS . $oldFile)) {
                        rename($new_backend_path . DS . $oldFile, $new_backend_path . DS . $newFile);
                        
                        // Replace class name in PHP files
                        if (strpos($oldFile, '.php') !== false) {
                            $fc = file_get_contents($new_backend_path . DS . $newFile);
                            $fc = str_replace($name, $newName, $fc);
                            file_put_contents($new_backend_path . DS . $newFile, $fc);
                        }
                    }
                }
                
                // Update system installed list if needed
                $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
                $systemConfig = require $systemConfigPath;
                if (isset($systemConfig['app']['installed_modules']) && in_array($name, $systemConfig['app']['installed_modules'])) {
                    $idx = array_search($name, $systemConfig['app']['installed_modules']);
                    $systemConfig['app']['installed_modules'][$idx] = $newName;
                    $configContent = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
                    file_put_contents($systemConfigPath, $configContent);
                }
                
                $name = $newName; // update for description.json
                $backend_path = $new_backend_path;
            }

            $descFile = $backend_path . DS . 'description.json';
            $data = [];
            if (file_exists($descFile)) {
                $data = json_decode(file_get_contents($descFile), true) ?: [];
            }
            $data['name'] = $name;
            $data['title'] = $title;
            $data['description'] = $desc;
            $data['author'] = $author;
            $data['version'] = $version;
            
            file_put_contents($descFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            setcookie('manager_module_success', 'Cập nhật thông tin module thành công!', time() + 5, '/');
        } else {
            setcookie('manager_module_error', 'Module không hợp lệ!', time() + 5, '/');
        }
        redirect(url('/manager/modules'));
    }
    
    private function copyDir($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0777, true);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->copyDir($src . '/' . $file, $dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function copyDirSafe($src, $dst, $is_template = false) {
        $dir = opendir($src);
        @mkdir($dst, 0777, true);
        $blacklist = ['php', 'php5', 'phtml', 'htaccess']; // Cấm các đuôi file thực thi
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $src_path = $src . '/' . $file;
                $dst_path = $dst . '/' . $file;
                
                if ( is_dir($src_path) ) {
                    $this->copyDirSafe($src_path, $dst_path, $is_template);
                } else {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    // Nếu là thư mục template, TUYỆT ĐỐI chặn đuôi php lọt vào public template
                    if ($is_template && in_array($ext, $blacklist)) {
                        continue; // Bỏ qua file độc hại
                    }
                    copy($src_path, $dst_path);
                }
            }
        }
        closedir($dir);
    }

    private function deleteDir($dirPath) {
        if (!is_dir($dirPath)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($dirPath);
    }


    public function module_template_manager()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        $name = $_GET['name'] ?? '';
        $subpath = $_GET['path'] ?? ''; // relative to virtual roots: backend/ or frontend/

        if (empty($name)) {
            setcookie('manager_module_error', 'Module không hợp lệ.', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }
        
        $is_core = in_array(strtolower($name), $this->core_modules);

        if ($is_core) {
            $backend_base = (strpos($name, 'custom_') === 0) ? APP . 'modules' . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $name : APP . 'modules' . DIRECTORY_SEPARATOR . $name;
            $frontend_base = TEMPLATES . get_template() . DIRECTORY_SEPARATOR . strtolower($name);
        } else {
            $backend_base = (strpos($name, 'custom_') === 0) ? APP . 'modules' . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $name : APP . 'modules' . DIRECTORY_SEPARATOR . $name;
            $frontend_base = TEMPLATES . get_template() . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $name;
        }

        if (!is_dir($backend_base) && !$is_core) mkdir($backend_base, 0777, true);
        if (!is_dir($frontend_base) && !$is_core) mkdir($frontend_base, 0777, true);

        if (strpos($subpath, '..') !== false) $subpath = '';

        $is_file = false;
        $file_content = '';
        $current_path = '';
        $dir_path = '';
        $items = [];

        if (empty($subpath) || $subpath === '/') {
            // Root view: show two virtual directories
            $items = [
                ['name' => 'Backend Code (app)', 'is_dir' => true, 'path' => 'backend'],
                ['name' => 'Giao diện (templates)', 'is_dir' => true, 'path' => 'frontend']
            ];
            $dir_path = '';
            $current_path = '';
        } else {
            $parts = explode('/', str_replace('\\', '/', $subpath), 2);
            $root_type = $parts[0];
            $relative_path = $parts[1] ?? '';

            $base_path = ($root_type === 'backend') ? $backend_base : $frontend_base;
            $current_real_path = rtrim($base_path . DIRECTORY_SEPARATOR . ltrim($relative_path, '/\\'), '/\\');

            if (is_file($current_real_path)) {
                $is_file = true;
                $ext = strtolower(pathinfo($current_real_path, PATHINFO_EXTENSION));
                $readable_exts = ['php', 'css', 'js', 'json', 'txt', 'html', 'md', 'sql'];
                if (in_array($ext, $readable_exts)) {
                    $file_content = file_get_contents($current_real_path);
                } else {
                    $file_content = "Không thể xem trước định dạng tệp này.";
                }
                $dir_real_path = dirname($current_real_path);
            } else {
                $dir_real_path = $current_real_path;
            }

            if (is_dir($dir_real_path)) {
                $files = scandir($dir_real_path);
                foreach ($files as $f) {
                    if ($f === '.') continue;
                    if ($f === '..' && $dir_real_path === $base_path) continue;
                    
                    $f_path = $dir_real_path . DIRECTORY_SEPARATOR . $f;
                    
                    if ($f === '..') {
                        $rel_path = dirname($subpath);
                        if ($rel_path === '.' || $rel_path === '\\') $rel_path = '';
                    } else {
                        $rel_path = ltrim(str_replace($base_path, '', $f_path), '/\\');
                        $rel_path = str_replace('\\', '/', $rel_path);
                        $rel_path = $root_type . '/' . $rel_path;
                    }

                    $items[] = [
                        'name' => $f,
                        'is_dir' => is_dir($f_path),
                        'path' => $rel_path
                    ];
                }
            }

            usort($items, function($a, $b) {
                if ($a['name'] === '..') return -1;
                if ($b['name'] === '..') return 1;
                if ($a['is_dir'] && !$b['is_dir']) return -1;
                if (!$a['is_dir'] && $b['is_dir']) return 1;
                return strcasecmp($a['name'], $b['name']);
            });

            $current_path = $subpath;
            $dir_path = $is_file ? dirname($subpath) : $subpath;
            if ($dir_path === '.' || $dir_path === '\\') $dir_path = '';
        }

        return view('manager/module_template', [
            'page_title' => ($is_core ? 'Xem Code: ' : 'Chỉnh sửa: ') . $name,
            'panelActive' => 'modules',
            'module_name' => $name,
            'is_core' => $is_core,
            'items' => $items,
            'is_file' => $is_file,
            'file_content' => $file_content,
            'current_file' => $is_file ? basename($subpath) : '',
            'current_path' => $current_path,
            'dir_path' => $dir_path
        ]);
    }

    public function module_template_action()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Không có quyền truy cập.']);
                exit;
            }
            redirect(url('/manager'));
            return;
        }
        if (isCSRFTokenValid($_POST['csrf_token'] ?? '') === 'error') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'CSRF Token không hợp lệ. Vui lòng tải lại trang.']);
                exit;
            }
            setcookie('manager_module_error', 'CSRF Token không hợp lệ. Vui lòng tải lại trang.', time() + 5, '/');
            redirect(url('/manager/modules'));
            return;
        }

        $name = $_POST['name'] ?? '';
        $action = $_POST['action'] ?? '';
        $path = $_POST['path'] ?? ''; // e.g. backend/Controller.php or backend/

        $second_pass = $_POST['second_password'] ?? '';
        $correct_second_pass = defined('SECOND_PASSWORD') ? SECOND_PASSWORD : '';
        
        if ($action !== 'save_file' && $correct_second_pass !== '' && $second_pass !== $correct_second_pass) {
            setcookie('manager_module_error', 'Mật khẩu cấp 2 không chính xác.', time() + 5, '/');
            $redirect_url = '/manager/modules';
            if (!empty($_POST['name'])) {
                $redirect_url = '/manager/modules/template?name=' . urlencode($_POST['name']);
                if (!empty($_POST['path'])) {
                    $redirect_url .= '&path=' . urlencode($_POST['path']);
                }
            }
            redirect(url($redirect_url));
            return;
        }
        
        $is_core = in_array(strtolower($name), $this->core_modules);
        
        if ($is_core) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Không thể chỉnh sửa module gốc.']);
                exit;
            }
            setcookie('manager_module_error', 'Không thể chỉnh sửa module gốc.', time() + 5, '/');
            redirect(url('/manager/modules/template?name=' . $name));
            return;
        }

        if (empty($name) || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Tên module không hợp lệ.']);
                exit;
            }
            redirect(url('/manager/modules'));
            return;
        }

        if (strpos($path, '..') !== false) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Đường dẫn không hợp lệ.']);
                exit;
            }
            setcookie('manager_module_error', 'Đường dẫn không hợp lệ.', time() + 5, '/');
            redirect(url('/manager/modules/template?name=' . $name));
            return;
        }

        $parts = explode('/', str_replace('\\', '/', $path), 2);
        $root_type = $parts[0];
        $relative_path = $parts[1] ?? '';

        if (!in_array($root_type, ['backend', 'frontend'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Thư mục gốc không hợp lệ.']);
                exit;
            }
            setcookie('manager_module_error', 'Thư mục gốc không hợp lệ.', time() + 5, '/');
            redirect(url('/manager/modules/template?name=' . $name));
            return;
        }

        $templateFolderName = $name;
        $backend_base = (strpos($name, 'custom_') === 0) ? APP . 'modules' . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $name : APP . 'modules' . DIRECTORY_SEPARATOR . $name;
        $frontend_base = TEMPLATES . get_template() . DIRECTORY_SEPARATOR . 'custom_module' . DIRECTORY_SEPARATOR . $templateFolderName;
        $base_path = ($root_type === 'backend') ? $backend_base : $frontend_base;
        $target_path = rtrim($base_path . DIRECTORY_SEPARATOR . ltrim($relative_path, '/\\'), '/\\');

        switch ($action) {
            case 'save_file':
                $content = $_POST['file_content'] ?? '';
                if (is_file($target_path)) {
                    file_put_contents($target_path, $content);
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'success', 'message' => 'Đã lưu file thành công!']);
                        exit;
                    }
                    setcookie('manager_module_success', 'Đã lưu file thành công!', time() + 5, '/');
                } else {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'message' => 'File không tồn tại hoặc không thể lưu!']);
                        exit;
                    }
                }
                redirect(url('/manager/modules/template?name=' . $name . '&path=' . urlencode($path)));
                break;
                
            case 'create_file':
                if ($root_type === 'backend') {
                    setcookie('manager_module_error', 'Không được phép tạo file trong backend!', time() + 5, '/');
                    break;
                }
                $filename = trim($_POST['filename'] ?? '');
                $filename = basename($filename);
                if (!empty($filename) && strpos($filename, '..') === false) {
                    $new_file = $target_path . DIRECTORY_SEPARATOR . $filename;
                    if (!file_exists($new_file)) {
                        file_put_contents($new_file, '');
                        setcookie('manager_module_success', 'Tạo file thành công!', time() + 5, '/');
                    } else {
                        setcookie('manager_module_error', 'File đã tồn tại!', time() + 5, '/');
                    }
                }
                $dir_path = ltrim(str_replace($base_path, '', $target_path), '/\\');
                $dir_path = $root_type . ($dir_path ? '/' . str_replace('\\', '/', $dir_path) : '');
                redirect(url('/manager/modules/template?name=' . $name . '&path=' . urlencode($dir_path)));
                break;

            case 'create_dir':
                if ($root_type === 'backend') {
                    setcookie('manager_module_error', 'Không được phép tạo thư mục trong backend!', time() + 5, '/');
                    break;
                }
                $dirname = trim($_POST['dirname'] ?? '');
                $dirname = basename($dirname);
                if (!empty($dirname) && strpos($dirname, '..') === false) {
                    $new_dir = $target_path . DIRECTORY_SEPARATOR . $dirname;
                    if (!file_exists($new_dir)) {
                        mkdir($new_dir, 0777, true);
                        setcookie('manager_module_success', 'Tạo thư mục thành công!', time() + 5, '/');
                    } else {
                        setcookie('manager_module_error', 'Thư mục đã tồn tại!', time() + 5, '/');
                    }
                }
                $dir_path = ltrim(str_replace($base_path, '', $target_path), '/\\');
                $dir_path = $root_type . ($dir_path ? '/' . str_replace('\\', '/', $dir_path) : '');
                redirect(url('/manager/modules/template?name=' . $name . '&path=' . urlencode($dir_path)));
                break;

            case 'rename':
                if ($root_type === 'backend') {
                    setcookie('manager_module_error', 'Không được phép đổi tên trong backend!', time() + 5, '/');
                    break;
                }
                $new_name = trim($_POST['new_name'] ?? '');
                $new_name = basename($new_name);
                if (!empty($new_name) && strpos($new_name, '..') === false && file_exists($target_path)) {
                    $new_path = dirname($target_path) . DIRECTORY_SEPARATOR . $new_name;
                    if (!file_exists($new_path)) {
                        rename($target_path, $new_path);
                        setcookie('manager_module_success', 'Đổi tên thành công!', time() + 5, '/');
                    } else {
                        setcookie('manager_module_error', 'Tên mới đã tồn tại!', time() + 5, '/');
                    }
                }
                $parent_path = ltrim(str_replace($base_path, '', dirname($target_path)), '/\\');
                $parent_path = $root_type . ($parent_path ? '/' . str_replace('\\', '/', $parent_path) : '');
                redirect(url('/manager/modules/template?name=' . $name . '&path=' . urlencode($parent_path)));
                break;

            case 'delete':
                if ($root_type === 'backend') {
                    setcookie('manager_module_error', 'Không được phép xóa file trong backend!', time() + 5, '/');
                    break;
                }
                if (file_exists($target_path) && $target_path !== $base_path) {
                    if (is_dir($target_path)) {
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($target_path, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        foreach ($files as $fileinfo) {
                            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                            $todo($fileinfo->getRealPath());
                        }
                        rmdir($target_path);
                    } else {
                        unlink($target_path);
                    }
                    setcookie('manager_module_success', 'Đã xóa thành công!', time() + 5, '/');
                }
                $parent_path = ltrim(str_replace($base_path, '', dirname($target_path)), '/\\');
                $parent_path = $root_type . ($parent_path ? '/' . str_replace('\\', '/', $parent_path) : '');
                redirect(url('/manager/modules/template?name=' . $name . '&path=' . urlencode($parent_path)));
                break;
                
            default:
                redirect(url('/manager/modules/template?name=' . $name));
        }
    }

    public function security()
    {
        // RBAC Check
        if ($this->request->user()->user['level'] < 127) {
            redirect(url('/manager'));
        }
        $page_title = 'Vận hành an toàn';
        $pdo = Container::get(DB::class);
        $e2e = new E2E();
        
        $unencrypted_count = 0;
        $error = $_COOKIE['manager_security_error'] ?? '';
        if (!empty($error)) {
            setcookie('manager_security_error', '', time() - 3600, '/');
        }
        $success = '';
        
        $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
        $systemConfig = require $systemConfigPath;
        
        if ($this->request->getMethod() === 'POST' && isset($_POST['update_security'])) {
            if (!$this->verifyAdminAction('security', 'manager_security_error', '/manager/security')) return;
            
            $rate_limit = (int)($this->request->postVar('rate_limit') ?? 1);
            if ($rate_limit < 0) $rate_limit = 0;
            
            $systemConfig['security']['rate_limit'] = $rate_limit;
            
            $content = "<?php\nreturn " . var_export($systemConfig, true) . ";\n";
            $content = str_replace(["array (", ")"], ["[", "]"], $content);
            file_put_contents($systemConfigPath, $content);
            
            $success = 'Cập nhật cấu hình bảo mật thành công!';
        }
        
        // Đếm tin nhắn chưa mã hóa (Mail + Shoutbox)
        $mails = $pdo->query("SELECT id, nick, content FROM mail")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($mails as $mail) {
            if (!$e2e->isEncrypted($mail['content'], E2E_SECRET_KEY, 'mail' . $mail['nick'])) {
                $unencrypted_count++;
            }
        }
        
        $shouts = $pdo->query("SELECT id, name, comment FROM chat")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($shouts as $shout) {
            if (!$e2e->isEncrypted($shout['comment'], E2E_SECRET_KEY, 'shoutbox' . $shout['name'])) {
                $unencrypted_count++;
            }
        }
        
        $total_unencrypted = $unencrypted_count;
        
        if ($this->request->getMethod() === 'POST' && isset($_POST['ajax_e2e'])) {
            header('Content-Type: application/json');
            
            // Check CSRF
            if (isCSRFTokenValid($_POST['csrf_token'] ?? '') === 'error') {
                echo json_encode(['status' => 'error', 'message' => 'CSRF Token không hợp lệ']);
                exit;
            }
            
            // Check Password manually for AJAX
            $admin_pass = $_POST['admin_pass'] ?? '';
            $user = $this->request->user()->user;
            if (empty($admin_pass) || empty($user['pass']) || !verify_password_seamless($admin_pass, $user['pass'], $user['id'], Container::get(DB::class))) {
                echo json_encode(['status' => 'error', 'message' => 'Mật khẩu xác nhận không chính xác.']);
                exit;
            }
            
            $limit = 50;
            $migrated = 0;
            
            // Process chunks
            foreach ($mails as $mail) {
                if ($migrated >= $limit) break;
                if (!$e2e->isEncrypted($mail['content'], E2E_SECRET_KEY, 'mail' . $mail['nick'])) {
                    $encrypted = $e2e->encrypt($mail['content'], E2E_SECRET_KEY, 'mail' . $mail['nick']);
                    $upd = $pdo->prepare("UPDATE mail SET content = ? WHERE id = ?");
                    $upd->execute([$encrypted, $mail['id']]);
                    $migrated++;
                    $unencrypted_count--;
                }
            }
            
            foreach ($shouts as $shout) {
                if ($migrated >= $limit) break;
                if (!$e2e->isEncrypted($shout['comment'], E2E_SECRET_KEY, 'shoutbox' . $shout['name'])) {
                    $encrypted = $e2e->encrypt($shout['comment'], E2E_SECRET_KEY, 'shoutbox' . $shout['name']);
                    $upd = $pdo->prepare("UPDATE chat SET comment = ? WHERE id = ?");
                    $upd->execute([$encrypted, $shout['id']]);
                    $migrated++;
                    $unencrypted_count--;
                }
            }
            
            if ($unencrypted_count == 0) {
                setcookie('manager_security_success', 'Đã mã hóa E2E thành công toàn bộ tin nhắn!', time() + 5, '/');
            }
            
            echo json_encode([
                'status' => 'success',
                'migrated' => $migrated,
                'remaining' => $unencrypted_count
            ]);
            exit;
        }
        
        $success = $_COOKIE['manager_security_success'] ?? '';
        if (!empty($success)) {
            setcookie('manager_security_success', '', time() - 3600, '/');
        }

        // DB Health Check Logic
        $schemaPath = ROOT . 'system/configs/schema.json';
        $missing_tables = [];
        $missing_columns = [];
        $schema = [];
        
        if (file_exists($schemaPath)) {
            $schema = json_decode(file_get_contents($schemaPath), true) ?: [];
            
            $stmt = $pdo->query("SHOW TABLES");
            $current_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($schema as $tb => $data) {
                if (!in_array($tb, $current_tables)) {
                    $missing_tables[] = $tb;
                } else {
                    $stmt = $pdo->query("SHOW COLUMNS FROM `$tb`");
                    $current_cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $miss = [];
                    foreach ($data['columns'] as $col => $type) {
                        if (!in_array($col, $current_cols)) {
                            $miss[] = $col;
                        }
                    }
                    if (!empty($miss)) {
                        $missing_columns[$tb] = $miss;
                    }
                }
            }
            
            if ($this->request->getMethod() === 'POST' && isset($_POST['fix_db_schema'])) {
                if (!$this->verifyAdminAction('security', 'manager_security_error', '/manager/security')) return;
                
                try {
                    foreach ($missing_tables as $tb) {
                        if (isset($schema[$tb]['create_sql'])) {
                            $pdo->exec($schema[$tb]['create_sql']);
                        }
                    }
                    foreach ($missing_columns as $tb => $cols) {
                        foreach ($cols as $col) {
                            if (isset($schema[$tb]['columns'][$col])) {
                                $type = $schema[$tb]['columns'][$col];
                                $pdo->exec("ALTER TABLE `$tb` ADD COLUMN `$col` $type");
                            }
                        }
                    }
                    setcookie('manager_security_success', 'Đã bổ sung các bảng/cột còn thiếu thành công!', time() + 5, '/');
                } catch(PDOException $e) {
                    setcookie('manager_security_error', 'Lỗi khắc phục CSDL: ' . $e->getMessage(), time() + 5, '/');
                }
                redirect(url('/manager/security'));
                return;
            }
        }
        
        return view('manager/security', [
            'panelActive' => 'security',
            'page_title' => $page_title,
            'unencrypted_count' => $unencrypted_count,
            'total_unencrypted' => $total_unencrypted,
            'error' => $error,
            'success' => $success,
            'systemConfig' => $systemConfig,
            'missing_tables' => $missing_tables,
            'missing_columns' => $missing_columns
        ]);
    }

    public function smtp()
    {
        $smtp = config('system.smtp');
        
        if ($this->request->getMethod() === 'POST') {
            if (!$this->verifyAdminAction('smtp', 'manager_smtp_error', '/manager/smtp')) return;
            
            $host = $this->request->postVar('host', '');
            $port = $this->request->postVar('port', '');
            $user = $this->request->postVar('user', '');
            $pass = $this->request->postVar('pass', '');
            $encrypt = $this->request->postVar('encrypt', '');
            $from_email = $this->request->postVar('from_email', '');
            $from_name = $this->request->postVar('from_name', '');
            
            $new_smtp = [
                'host' => $host,
                'port' => intval($port),
                'user' => $user,
                'pass' => $pass,
                'encrypt' => $encrypt,
                'from_email' => $from_email,
                'from_name' => $from_name,
            ];
            
            $systemFile = SYSTEM . 'configs' . DS . 'autoload' . DS . 'system.php';
            $systemConfig = include($systemFile);
            $systemConfig['smtp'] = $new_smtp;
            
            $export = var_export($systemConfig, true);
            $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
            $export = preg_replace('/^([ ]*)array \(/m', '$1[', $export);
            $export = preg_replace('/^([ ]*)\)/m', '$1]', $export);
            $export = preg_replace('/=> 
[ ]+\[/m', '=> [', $export);
            $export = str_replace('array (', '[', $export);
            $export = str_replace(')', ']', $export);
            
            $file_content = "<?php\nreturn " . $export . ";\n";
            file_put_contents($systemFile, $file_content);
            
            return view('manager/smtp', [
                'page_title' => 'Cấu hình SMTP',
                'smtp' => $new_smtp,
                'success' => 'Đã lưu cấu hình thành công',
                'nav' => 'settings'
            ]);
        }
        
        return view('manager/smtp', [
            'page_title' => 'Cấu hình SMTP',
            'smtp' => $smtp,
            'nav' => 'settings'
        ]);
    }

    public function media()
    {
        $page_title = 'Quản lý Tập tin';
        $action = $this->request->getVar('action', '');
        
        // Handle deletion
        if ($action === 'delete') {
            $id = $this->request->postVar('id', 0);
            if ($this->managerModel->DeleteFile($id)) {
                setcookie('manager_media_success', 'Đã xoá tập tin thành công!', time() + 5, '/');
            } else {
                setcookie('manager_media_error', 'Lỗi khi xoá tập tin!', time() + 5, '/');
            }
            redirect(url('/manager/media'));
            return;
        }

        // Pagination
        $page = $this->request->getVar('page', 1);
        $page = max(1, (int)$page);
        $limit = 20;
        $start = ($page - 1) * $limit;

        // Calculate total pages
        $totalFiles = $this->managerModel->GetTotalFiles();
        $totalPages = ceil($totalFiles / $limit);
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        $start = ($page - 1) * $limit;
        
        $files = $this->managerModel->GetFilesList($start, $limit);
        
        $success = $_COOKIE['manager_media_success'] ?? '';
        if (!empty($success)) setcookie('manager_media_success', '', time() - 3600, '/');
        $error = $_COOKIE['manager_media_error'] ?? '';
        if (!empty($error)) setcookie('manager_media_error', '', time() - 3600, '/');

        return view('manager/media', [
            'page_title' => $page_title,
            'files' => $files,
            'page' => $page,
            'totalPages' => $totalPages,
            'success' => $success,
            'error' => $error,
            'nav' => 'media'
        ]);
    }

    public function static_page()
    {
        $dir = ROOT . 'templates/_assets/static-page/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $pages = [];
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($dir . $file);
                $title = pathinfo($file, PATHINFO_FILENAME);
                if (preg_match('/<!---\s*title:\s*(.*?)\s*-->/i', $content, $matches)) {
                    $title = trim($matches[1]);
                }
                $pages[] = [
                    'file' => $file,
                    'title' => $title,
                    'slug' => pathinfo($file, PATHINFO_FILENAME),
                    'size' => filesize($dir . $file),
                    'mtime' => filemtime($dir . $file)
                ];
            }
        }
        return view('manager/static_page', ['pages' => $pages, 'panelActive' => 'static_pages']);
    }

    public function static_page_add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $this->request->postVar('title', '');
            $slug = $this->request->postVar('slug', '');
            $content = $_POST['content'] ?? '';
            
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
            if (empty($slug)) {
                setcookie('manager_sp_error', 'Slug không hợp lệ', time() + 5, '/');
                redirect(url('/manager/static_pages/add'));
                return;
            }
            
            $file = ROOT . 'templates/_assets/static-page/' . $slug . '.php';
            if (file_exists($file)) {
                setcookie('manager_sp_error', 'Trang tĩnh với slug này đã tồn tại', time() + 5, '/');
                redirect(url('/manager/static_pages/add'));
                return;
            }
            
            $fileContent = "<!--- title: {$title} -->\n" . $content;
            file_put_contents($file, $fileContent);
            setcookie('manager_sp_success', 'Đã thêm trang tĩnh thành công', time() + 5, '/');
            redirect(url('/manager/static_pages'));
            return;
        }
        return view('manager/static_page_edit', ['mode' => 'add', 'panelActive' => 'static_pages']);
    }

    public function static_page_edit()
    {
        $slug = $this->request->getVar('slug', '');
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        $file = ROOT . 'templates/_assets/static-page/' . $slug . '.php';
        
        if (empty($slug) || !file_exists($file)) {
            setcookie('manager_sp_error', 'Không tìm thấy trang tĩnh', time() + 5, '/');
            redirect(url('/manager/static_pages'));
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $this->request->postVar('title', '');
            $content = $_POST['content'] ?? '';
            
            $fileContent = "<!--- title: {$title} -->\n" . $content;
            file_put_contents($file, $fileContent);
            setcookie('manager_sp_success', 'Đã lưu thay đổi trang tĩnh', time() + 5, '/');
            redirect(url('/manager/static_pages'));
            return;
        }
        
        $rawContent = file_get_contents($file);
        $title = $slug;
        $content = $rawContent;
        if (preg_match('/^<!---\s*title:\s*(.*?)\s*-->\r?\n?(.*)$/is', $rawContent, $matches)) {
            $title = trim($matches[1]);
            $content = $matches[2];
        }
        
        return view('manager/static_page_edit', [
            'mode' => 'edit',
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
            'panelActive' => 'static_pages'
        ]);
    }

    public function static_page_delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $admin_pass = $this->request->postVar('admin_pass', '');
            $module_name = $this->request->postVar('module_name', '');
            $user = $this->request->user()->user;
            
            if ($module_name !== 'static_pages') {
                setcookie('manager_sp_error', 'Tên phân hệ không chính xác!', time() + 5, '/');
                redirect(url('/manager/static_pages'));
            }

            if (empty($admin_pass) || empty($user['pass']) || !verify_password_seamless($admin_pass, $user['pass'], $user['id'], Container::get(DB::class))) {
                setcookie('manager_sp_error', 'Mật khẩu xác nhận không chính xác!', time() + 5, '/');
                redirect(url('/manager/static_pages'));
            }

            $slug = $this->request->postVar('slug', '');
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
            $file = ROOT . 'templates/_assets/static-page/' . $slug . '.php';
            
            if (!empty($slug) && file_exists($file)) {
                unlink($file);
                setcookie('manager_sp_success', 'Đã xóa trang tĩnh thành công', time() + 5, '/');
            } else {
                setcookie('manager_sp_error', 'Trang tĩnh không tồn tại', time() + 5, '/');
            }
        }
        redirect(url('/manager/static_pages'));
    }
}
