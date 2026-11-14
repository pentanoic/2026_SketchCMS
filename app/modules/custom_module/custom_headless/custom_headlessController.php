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

class custom_headlessController extends Controller
{
    private $articlesModel;
    private $homeModel;
    private $mailModel;
    private $managerModel;
    private $mediaModel;
    private $shoutboxModel;
    private $userModel;

    function __construct()
    {
        parent::__construct();
        $this->articlesModel = $this->load->model('articles');
        $this->homeModel = $this->load->model('home');
        $this->mailModel = $this->load->model('mail');
        $this->managerModel = $this->load->model('manager');
        $this->mediaModel = $this->load->model('media');
        $this->shoutboxModel = $this->load->model('shoutbox');
        $this->userModel = $this->load->model('user');
    }

    /**
     * Helper: Trả về JSON Response
     */
    private function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // Cho phép CORS nếu cần thiết cho Headless CMS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-CSRF-Token');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Helper: Loại bỏ các trường nhạy cảm khỏi dữ liệu người dùng (API3 - BOPLA)
     */
    private function sanitizeUser(array $u): array
    {
        unset(
            $u['pass'],
            $u['reset_token'],
            $u['reset_expires'],
            $u['remember_token'],
            $u['failed_logins'],
            $u['lock_time'],
            $u['new_mail'],
            $u['mail_list'],
            $u['blocklist'],
            $u['login']
        );
        return $u;
    }

    /**
     * Xác thực người dùng (Token + Cookie Session)
     */
    private function authenticate()
    {
        // 1. Kiểm tra qua Cookie Session (dành cho client web gọi API)
        if ($this->request->user()->isLogin && !empty($this->request->user()->user['id'])) {
            $sessionUser = $this->request->user()->user;
            if ((int)($sessionUser['level'] ?? 0) < 0) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Account suspended'], 403);
            }
            return $sessionUser;
        }

        // 2. Kiểm tra qua Token (Header Authorization: Bearer <token>)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = verify_api_token($token);
            if ($payload && !empty($payload['id'])) {
                $dbUser = $this->userModel->UserDetailWithFields('id', (int)$payload['id']);
                if ($dbUser && (int)($dbUser['level'] ?? 0) >= 0) {
                    return $dbUser;
                }
            }

            $staticToken = config('system.api_token');
            if (!empty($staticToken) && is_string($staticToken) && hash_equals($staticToken, $token)) {
                $adminUser = $this->userModel->UserDetailWithFields('id', 1);
                if ($adminUser) {
                    return $adminUser;
                }
            }
        }

        // Nếu không có quyền
        $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    /**
     * GET /api/v1/ping
     */
    public function ping()
    {
        $this->jsonResponse([
            'status' => 'success',
            'message' => 'pong',
            'time' => time()
        ]);
    }



    /**
     * GET /api/v1/articles
     */
    public function getArticles()
    {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $articles = $this->articlesModel->PostList(0, $limit, 'update_time', 'desc', $start);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $articles,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * GET /api/v1/articles/{id}
     */
    public function getArticle($id)
    {
        $id = intval($id);

        $article = $this->articlesModel->PostDetail($id);

        if (!$article) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Article not found'], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'data' => $article
        ]);
    }

    /**
     * GET /api/v1/forums
     */
    public function getForums()
    {
        $forums = $this->articlesModel->CategoryList(100);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $forums
        ]);
    }

    /**
     * GET /api/docs
     * View trang tài liệu API
     */
    /**
     * Helper: Lấy dữ liệu POST (JSON hoặc Form-data)
     */
    private function getRequestData()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : $_POST;
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login()
    {
        $data = $this->getRequestData();
        $account = trim((string)($data['account'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if (empty($account) || empty($password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing account or password'], 400);
        }

        $type = filter_var($account, FILTER_VALIDATE_EMAIL) ? 'email' : 'nick';
        $userLogin = $this->userModel->getForLogin($type, $account);

        if (!$userLogin) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        if ((int)($userLogin['lock_time'] ?? 0) > time()) {
            $waitMin = ceil(($userLogin['lock_time'] - time()) / 60);
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Account is temporarily locked due to too many failed login attempts. Try again in {$waitMin} minute(s)."
            ], 423);
        }

        if (!verify_password_seamless($password, $userLogin['pass'], $userLogin['id'], Container::get(DB::class))) {
            $this->userModel->recordLoginFailure($userLogin);
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        if ((int)($userLogin['failed_logins'] ?? 0) > 0 || (int)($userLogin['lock_time'] ?? 0) > 0) {
            $this->userModel->resetLoginFailures((int)$userLogin['id']);
        }

        if ((int)($userLogin['level'] ?? 0) < 0) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Account suspended'], 403);
        }

        $token = generate_api_token((int)$userLogin['id'], 86400 * 30);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user_id' => (int)$userLogin['id']
            ]
        ]);
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register()
    {
        $disabledRoutes = config('system.url_rules.disable', []);
        if (in_array('/register', $disabledRoutes, true) || in_array('register', $disabledRoutes, true)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Registration is currently disabled'], 403);
        }

        if (!checkRateLimit('api_register', 10)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Too many registration attempts. Please wait.'], 429);
        }

        $data = $this->getRequestData();
        $account = mb_strtolower(trim((string)($data['account'] ?? '')));
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        if (empty($account) || empty($email) || empty($password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        if (!preg_match('/^[a-z0-9_]{3,20}$/', $account)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid account format'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid email format'], 400);
        }
        if (strlen($password) < 6 || strlen($password) > 32) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Password must be between 6 and 32 characters'], 400);
        }

        $check = $this->userModel->checkUsedInfo($account, $email);
        if ($check) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Account or email already exists'], 409);
        }

        $sex = in_array($data['sex'] ?? '', ['boy', 'girl'], true) ? $data['sex'] : 'girl';

        // Tạo user mới
        $user = [
            'nick' => $account,
            'email' => $email,
            'pass' => $password,
            'name' => $account,
            'sex' => $sex,
            'reg_date' => time()
        ];
        
        $newUserId = $this->userModel->register($user);

        if ($newUserId) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Registered successfully', 'user_id' => (int)$newUserId]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Failed to register'], 500);
        }
    }

    public function docs()
    {
        return view('custom_module/custom_headless/index', ['page_title' => 'Tài liệu API Headless']);
    }
    /**
     * GET /api/v1/users
     */
    public function getUsers()
    {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
        $start = ($page - 1) * $limit;

        $users = array_map([$this, 'sanitizeUser'], $this->userModel->UserList($limit, 'level', 'desc', $start));

        $this->jsonResponse([
            'status' => 'success',
            'data' => $users,
            'page' => $page,
            'limit' => $limit
        ]);
    }


    /**
     * GET /api/v1/users/{nick}
     */
    public function getUser($nick)
    {
        $userDetail = $this->userModel->UserDetailWithFields('nick', mb_strtolower(trim((string)$nick)));
        if (!$userDetail) {
            $this->jsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $stats = $this->userModel->UserStats($userDetail['nick']);
        $safeUser = $this->sanitizeUser($userDetail);
        $safeUser['stats'] = $stats;

        $this->jsonResponse([
            'status' => 'success',
            'data' => $safeUser
        ]);
    }

    /**
     * GET /api/v1/shoutbox
     */
    public function getShoutbox()
    {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 50;
        $start = ($page - 1) * $limit;

        $chats = $this->shoutboxModel->ChatList($start, $limit);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $chats,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * GET /api/v1/shoutbox/count
     */
    public function getShoutboxCount()
    {
        $stats = $this->articlesModel->ForumStats();
        $count = $stats['count_chat'] ?? 0;
        $this->jsonResponse(['status' => 'success', 'data' => ['count' => $count]]);
    }

    /**
     * GET /api/v1/shoutbox/([0-9]+)
     */
    public function getShoutboxEle($id)
    {
        $id = intval($id);
        $chat = $this->shoutboxModel->ChatDetail($id);
        if (!$chat) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Chat not found'], 404);
        }
        $this->jsonResponse(['status' => 'success', 'data' => $chat]);
    }

    /**
     * POST /api/v1/shoutbox/send
     */
    public function sendShoutbox()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $data = $this->getRequestData();
        $msg = $data['msg'] ?? '';

        if (empty($msg)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Message is required'], 400);
        }

        $this->shoutboxModel->ChatSend($user['nick'], $msg);
        $this->jsonResponse(['status' => 'success', 'message' => 'Message sent']);
    }

    /**
     * GET /api/v1/users/online
     */
    public function getUsersOnline()
    {
        $users = array_map([$this, 'sanitizeUser'], $this->userModel->UserListOnline(0, 50));
        $this->jsonResponse([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * POST /api/v1/users/me/update
     */
    public function updateMe()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $data = $this->getRequestData();
        $allowedFields = ['name', 'status', 'avatar', 'sex'];
        $saveData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && is_scalar($data[$field])) {
                $saveData[$field] = _e((string)$data[$field]);
            }
        }

        if (empty($saveData)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'No valid fields provided to update'], 400);
        }

        // Thực hiện update (UserDetailEdit cần truyền MyDetail (mảng user) và SaveData)
        $this->userModel->UserDetailEdit($user, $saveData);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * GET /api/v1/mail/conversations
     */
    public function getMailConversations()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $conversations = $this->mailModel->MailList($user);
        
        $data = [];
        foreach ($conversations as $partnerNick) {
            $latest = $this->mailModel->LatestMailDetail($user['nick'], $partnerNick);
            $data[] = [
                'partner' => $partnerNick,
                'latest_message' => $latest
            ];
        }

        $this->jsonResponse([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * GET /api/v1/mail/conversations/{user_id} (thực ra là {nick})
     */
    public function getMailDetail($partnerNick)
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $messages = $this->mailModel->MailDetailList($user['nick'], $partnerNick, $limit, $start);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $messages,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * POST /api/v1/mail/send
     */
    public function sendMail()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $data = $this->getRequestData();
        $receiverNick = $data['receiver'] ?? '';
        $content = $data['content'] ?? '';

        if (empty($receiverNick) || empty($content)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Receiver and content are required'], 400);
        }

        $receiverDetail = $this->userModel->UserDetailWithFields('nick', $receiverNick);
        if (!$receiverDetail) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Receiver not found'], 404);
        }

        $this->mailModel->MailSend($user, $receiverDetail, $content);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Mail sent successfully'
        ]);
    }

    /**
     * GET /api/v1/mail/system
     */
    public function getSystemMail()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $sysMails = $this->mailModel->MailSystemList($user, $limit, $start);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $sysMails,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * Helper: Kiểm tra quyền Admin (Moderator trở lên: level >= 121)
     */
    private function checkAdmin($user)
    {
        if (!$user || (int)($user['level'] ?? 0) < 121) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden: Admins only'], 403);
        }
    }

    /**
     * GET /api/v1/admin/stats
     */
    public function getAdminStats()
    {
        $user = $this->authenticate();
        $this->checkAdmin($user);

        $stats = [
            'total_users' => $this->managerModel->GetTotalUsers(),
            'total_posts' => $this->managerModel->GetTotalPosts(),
            'total_categories' => $this->managerModel->GetTotalCategories(),
            'total_shouts' => $this->managerModel->GetTotalShouts(),
            'total_files' => $this->managerModel->GetTotalFiles()
        ];

        $this->jsonResponse([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * GET /api/v1/admin/users
     */
    public function getAdminUsers()
    {
        $user = $this->authenticate();
        $this->checkAdmin($user);

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
        $start = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        $users = array_map([$this, 'sanitizeUser'], $this->managerModel->GetUsers($start, $limit, $search));

        $this->jsonResponse([
            'status' => 'success',
            'data' => $users,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * POST /api/v1/admin/users/{id}/ban
     */
    public function toggleBanUser($id)
    {
        $user = $this->authenticate();
        $this->checkAdmin($user);

        $id = intval($id);
        $ok = $this->managerModel->ToggleBanUser($id, (int)$user['id']);
        if (!$ok) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Cannot toggle ban for this user (insufficient privilege or self-ban)'
            ], 403);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'User ban status toggled successfully'
        ]);
    }

    /**
     * GET /api/v1/admin/files
     */
    public function getAdminFiles()
    {
        $user = $this->authenticate();
        $this->checkAdmin($user);

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $files = $this->managerModel->GetFilesList($start, $limit);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $files,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * GET /api/v1/media/library
     */
    public function getMediaLibrary()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 50; // default for getLibrary

        $mediaList = $this->mediaModel->getLibrary($user['nick'], $limit);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $mediaList,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * POST /api/v1/media/upload
     * API này nhận thông tin file đã upload (như filecate - Telegram file_id) hoặc base64
     * Để đơn giản, ưu tiên nhận JSON payload chứa filename, filesize, filecate
     */
    public function uploadMedia()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $data = $this->getRequestData();
        $filename = $data['filename'] ?? '';
        $filesize = $data['filesize'] ?? 0;
        $filecate = $data['filecate'] ?? ''; 
        $blogid = isset($data['blogid']) ? (int)$data['blogid'] : 0;

        if (empty($filename) || empty($filecate)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'filename and filecate are required'], 400);
        }

        $insertData = [
            'time' => time(),
            'filename' => $filename,
            'filecate' => $filecate,
            'filesize' => $filesize,
            'type' => 'telegram',
            'author' => $user['nick'],
            'status' => 'public',
            'price' => 0,
            'blogid' => $blogid
        ];

        $file_id = $this->mediaModel->insertMedia($insertData);

        if ($file_id) {
            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Media saved successfully',
                'file_id' => $file_id
            ]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Failed to save media'], 500);
        }
    }

    /**
     * GET /api/v1/articles/{id}/chapters
     */
    public function getArticleChapters($id)
    {
        $id = intval($id);
        $chapters = $this->articlesModel->ChapterList($id);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $chapters
        ]);
    }

    /**
     * GET /api/v1/forums/{id}/articles
     */
    public function getForumArticles($forumId)
    {
        $forumId = intval($forumId);
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $articles = $this->articlesModel->PostList($forumId, $limit, 'update_time', 'desc', $start);

        $this->jsonResponse([
            'status' => 'success',
            'data' => $articles,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * GET /api/v1/search
     */
    public function searchArticles()
    {
        $query = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($query, 'UTF-8') < 2 || mb_strlen($query, 'UTF-8') > 100) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Query must be between 2 and 100 characters'], 400);
        }
        $results = $this->articlesModel->ForumSearch($query);
        $results['result'] = array_slice($results['result'] ?? [], 0, 50);
        $this->jsonResponse(['status' => 'success', 'data' => $results]);
    }

    /**
     * POST /api/v1/articles
     */
    public function createArticle()
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $data = $this->getRequestData();
        $category = (int)($data['category'] ?? 0);
        $title = trim((string)($data['title'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        $content = trim((string)($data['content'] ?? ''));
        $meta_data = isset($data['meta_data']) && is_string($data['meta_data']) ? $data['meta_data'] : null;

        if (empty($title) || empty($content)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Title and content are required'], 400);
        }

        if (!$this->articlesModel->CategoryExist($category)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Category not found'], 404);
        }

        // BFLA (API5): Chặn thành viên thường đăng bài vào chuyên mục Tin tức (NewsID)
        if (defined('NewsID') && $category === (int)NewsID && (int)($user['level'] ?? 0) < 120) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden: Only Contributors/Admins can publish in News'], 403);
        }

        if (empty($slug)) {
            $articlesLibrary = $this->load->library('articles');
            $slug = $articlesLibrary->slug($title);
        }

        $post_id = $this->articlesModel->PostPublish($category, _e($title), $slug, _e($content), $user['nick'], $meta_data);
        if ($post_id) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Article created', 'post_id' => (int)$post_id]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Failed to create article'], 500);
        }
    }

    /**
     * POST /api/v1/articles/{id}/edit
     */
    public function editArticle($id)
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $id = intval($id);
        $data = $this->getRequestData();
        
        $post = $this->articlesModel->PostDetail($id);
        if (!$post) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Article not found'], 404);
        }
        
        if ($post['author'] !== $user['nick'] && (int)($user['level'] ?? 0) < 120) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $category = isset($data['category']) ? (int)$data['category'] : (int)$post['category'];
        if (defined('NewsID') && $category === (int)NewsID && (int)($user['level'] ?? 0) < 120) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden: Cannot move article to News category'], 403);
        }

        $editData = [
            'category'  => $category,
            'title'     => isset($data['title']) ? _e(trim((string)$data['title'])) : $post['title'],
            'slug'      => isset($data['slug']) ? trim((string)$data['slug']) : $post['slug'],
            'content'   => isset($data['content']) ? _e(trim((string)$data['content'])) : $post['content'],
            'meta_data' => isset($data['meta_data']) && is_string($data['meta_data']) ? $data['meta_data'] : ($post['meta_data'] ?? null)
        ];

        $this->articlesModel->PostEdit($id, $editData);
        $this->jsonResponse(['status' => 'success', 'message' => 'Article updated']);
    }

    /**
     * POST /api/v1/articles/{id}/delete
     */
    public function deleteArticle($id)
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $id = intval($id);
        $post = $this->articlesModel->PostDetail($id);
        if (!$post) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Article not found'], 404);
        }
        
        if ($post['author'] !== $user['nick'] && (int)($user['level'] ?? 0) < 120) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $this->articlesModel->PostDelete($id);
        $this->jsonResponse(['status' => 'success', 'message' => 'Article deleted']);
    }

    /**
     * POST /api/v1/articles/{id}/chapters
     */
    public function createChapter($id)
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $id = intval($id);
        // BOLA / IDOR (API1): Kiểm tra bài viết tồn tại và quyền sở hữu trước khi thêm chương
        $post = $this->articlesModel->PostDetail($id);
        if (!$post) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Article not found'], 404);
        }
        if ($post['author'] !== $user['nick'] && (int)($user['level'] ?? 0) < 120) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden: You do not own this article'], 403);
        }

        $data = $this->getRequestData();
        $title = trim((string)($data['title'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        $content = trim((string)($data['content'] ?? ''));
        $meta_data = isset($data['meta_data']) && is_string($data['meta_data']) ? $data['meta_data'] : null;

        if (empty($title) || empty($content)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Title and content are required'], 400);
        }

        if (empty($slug)) {
            $articlesLibrary = $this->load->library('articles');
            $slug = $articlesLibrary->slug($title);
        }

        $chapter_id = $this->articlesModel->ChapterPublish($id, $title, $slug, $content, $user['nick'], $meta_data);
        if ($chapter_id) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Chapter created', 'chapter_id' => (int)$chapter_id]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Failed to create chapter'], 500);
        }
    }

    /**
     * GET /api/v1/chapters/{id}
     */
    public function getChapter($id)
    {
        $id = intval($id);
        $chapter = $this->articlesModel->ForumDetailWithFields('articles_chap', 'id', $id);
        if (!$chapter) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Chapter not found'], 404);
        }
        $this->jsonResponse(['status' => 'success', 'data' => $chapter]);
    }

    /**
     * POST /api/v1/articles/{id}/comments
     */
    public function createComment($id)
    {
        $user = $this->authenticate();
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $id = intval($id);
        $data = $this->getRequestData();
        $comment = $data['comment'] ?? '';

        if (empty($comment)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Comment is required'], 400);
        }

        $post = $this->articlesModel->PostDetail($id);
        if (!$post) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Article not found'], 404);
        }

        $this->articlesModel->CommentSend($post, $user['nick'], $comment);
        $this->jsonResponse(['status' => 'success', 'message' => 'Comment posted']);
    }

    /**
     * POST /api/v1/auth/password/forgot
     */
    public function forgotPassword()
    {
        if (!checkRateLimit('api_forgot_password', 15)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Too many requests. Please wait before trying again.'], 429);
        }

        $data = $this->getRequestData();
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Valid email is required'], 400);
        }

        $user = $this->userModel->UserDetailWithFields('email', $email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 hour
            $this->userModel->SetResetToken($email, $token, $expires);

            // Gửi email nếu đã cấu hình SMTP (không bao giờ trả raw token về JSON response - chống API6 Account Takeover)
            $smtpConfig = config('system.smtp');
            if (!empty($smtpConfig['host'])) {
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $smtpConfig['host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtpConfig['user'];
                    $mail->Password   = $smtpConfig['pass'];
                    if ($smtpConfig['encrypt'] === 'tls') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } elseif ($smtpConfig['encrypt'] === 'ssl') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    }
                    $mail->Port       = $smtpConfig['port'];
                    $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Yeu cau dat lai mat khau';
                    $resetLink = url('/login/password/reset?token=' . $token);
                    $mail->Body    = "Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng click vào link sau để đổi mật khẩu: <br><a href='{$resetLink}'>{$resetLink}</a><br>Link có hiệu lực trong 1 giờ.";
                    $mail->send();
                } catch (\Throwable $e) {
                    // Không tiết lộ lỗi SMTP chi tiết ra ngoài
                }
            }
        }

        // Phản hồi đồng nhất tránh dò tìm email (User Enumeration) và không trả về token
        $this->jsonResponse([
            'status' => 'success',
            'message' => 'If the email exists in our system, password reset instructions have been sent.'
        ]);
    }

    /**
     * POST /api/v1/auth/password/reset
     */
    public function resetPassword()
    {
        $data = $this->getRequestData();
        $token = trim((string)($data['token'] ?? ''));
        $new_password = (string)($data['password'] ?? '');

        if (empty($token) || empty($new_password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Token and password are required'], 400);
        }

        if (strlen($new_password) < 6 || strlen($new_password) > 32) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Password must be between 6 and 32 characters'], 400);
        }

        $tokenData = $this->userModel->CheckResetToken($token);
        if (!$tokenData) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid or expired token'], 400);
        }

        $hashedPassword = hash_password_secure($new_password);
        $this->userModel->ResetPasswordByToken($token, $hashedPassword);
        $this->jsonResponse(['status' => 'success', 'message' => 'Password reset successfully']);
    }
}
