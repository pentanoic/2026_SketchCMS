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
        header('Access-Control-Allow-Headers: Authorization, Content-Type');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Xác thực người dùng (Token + Cookie Session)
     */
    private function authenticate()
    {
        global $user;

        // 1. Kiểm tra qua Cookie Session (dành cho client web gọi API)
        if (isset($user) && isset($user['id'])) {
            return $user;
        }

        // 2. Kiểm tra qua Token (Header Authorization: Bearer <token>)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            // TODO: Ở đây có thể kiểm tra token từ DB (ví dụ bảng `api_tokens`)
            // Tạm thời nếu sử dụng token giả lập thì check như sau:
            if ($token === config('system.api_token')) {
                // Return 1 user giả lập hoặc lấy user theo token
                return ['id' => 1, 'nick' => 'admin', 'level' => 100]; // VD
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
        $account = $data['account'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($account) || empty($password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing account or password'], 400);
        }

        $type = filter_var($account, FILTER_VALIDATE_EMAIL) ? 'email' : 'nick';
        $userLogin = $this->userModel->getForLogin($type, $account);

        if (!$userLogin || !password_verify($password, $userLogin['pass'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        // Tạo JWT Token giả lập hoặc token tự chế (để demo, bạn có thể thay bằng thư viện JWT thật)
        $token = base64_encode(json_encode(['id' => $userLogin['id'], 'exp' => time() + 86400 * 30]));

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user_id' => $userLogin['id']
            ]
        ]);
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register()
    {
        $data = $this->getRequestData();
        $account = $data['account'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($account) || empty($email) || empty($password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        $check = $this->userModel->checkUsedInfo($account, $email);
        if ($check) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Account or email already exists'], 409);
        }

        // Tạo user mới
        $user = [
            'nick' => $account,
            'email' => $email,
            'pass' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $account,
            'reg_date' => time()
        ];
        
        $newUserId = $this->userModel->register($user);

        if ($newUserId) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Registered successfully', 'user_id' => $newUserId]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => 'Failed to register'], 500);
        }
    }

    public function docs()
    {
        $this->view('custom_module/custom_headless/index', ['page_title' => 'Tài liệu API Headless']);
    }
        /**
     * GET /api/v1/users
     */
    public function getUsers()
    {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $users = $this->userModel->UserList($limit, 'level', 'desc', $start);

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
        $user = $this->userModel->UserStats($nick);
        
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'data' => $user
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
        $users = $this->userModel->UserListOnline();
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
        $allowedFields = ['name', 'about', 'avatar', 'city'];
        $saveData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $saveData[$field] = $data[$field];
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
     * Helper: Kiểm tra quyền Admin
     */
    private function checkAdmin($user)
    {
        if (!$user || $user['level'] < 120) {
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
        $limit = 20;
        $start = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        $users = $this->managerModel->GetUsers($start, $limit, $search);

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
        $this->managerModel->ToggleBanUser($id);

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
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Query is required'], 400);
        }
        $results = $this->articlesModel->ForumSearch($query);
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
        $category = $data['category'] ?? 0;
        $title = $data['title'] ?? '';
        $slug = $data['slug'] ?? '';
        $content = $data['content'] ?? '';
        $meta_data = $data['meta_data'] ?? null;

        if (empty($title) || empty($content)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Title and content are required'], 400);
        }

        $post_id = $this->articlesModel->PostPublish($category, $title, $slug, $content, $user['nick'], $meta_data);
        if ($post_id) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Article created', 'post_id' => $post_id]);
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
        
        if ($post['author'] !== $user['nick'] && $user['level'] < 120) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $this->articlesModel->PostEdit($id, $data);
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
        
        if ($post['author'] !== $user['nick'] && $user['level'] < 120) {
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
        $data = $this->getRequestData();
        $title = $data['title'] ?? '';
        $slug = $data['slug'] ?? '';
        $content = $data['content'] ?? '';
        $meta_data = $data['meta_data'] ?? null;

        if (empty($title) || empty($content)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Title and content are required'], 400);
        }

        $chapter_id = $this->articlesModel->ChapterPublish($id, $title, $slug, $content, $user['nick'], $meta_data);
        if ($chapter_id) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Chapter created', 'chapter_id' => $chapter_id]);
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
        $chapter = $this->articlesModel->ForumDetailWithFields('articles_chapter', 'id', $id);
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
        $data = $this->getRequestData();
        $email = $data['email'] ?? '';

        if (empty($email)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Email is required'], 400);
        }

        $user = $this->userModel->UserDetailWithFields('email', $email);
        if (!$user) {
            $this->jsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600; // 1 hour
        $this->userModel->SetResetToken($email, $token, $expires);

        $this->jsonResponse([
            'status' => 'success', 
            'message' => 'Reset token generated (simulation).',
            'token' => $token
        ]);
    }

    /**
     * POST /api/v1/auth/password/reset
     */
    public function resetPassword()
    {
        $data = $this->getRequestData();
        $token = $data['token'] ?? '';
        $new_password = $data['password'] ?? '';

        if (empty($token) || empty($new_password)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Token and password are required'], 400);
        }

        $tokenData = $this->userModel->CheckResetToken($token);
        if (!$tokenData) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid or expired token'], 400);
        }

        $this->userModel->ResetPasswordByToken($token, $new_password);
        $this->jsonResponse(['status' => 'success', 'message' => 'Password reset successfully']);
    }
}
