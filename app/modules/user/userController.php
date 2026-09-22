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

class userController extends Controller
{
    private articlesModel $articlesModel;
    private userModel $userModel;
    private userLibrary $userLibrary;
    private articlesLibrary $articlesLibrary;

    function __construct()
    {
        parent::__construct();
        $this->articlesModel = $this->load->model('articles');
        $this->userModel = $this->load->model('user');
        $this->userLibrary = $this->load->library('user');
        $this->articlesLibrary = $this->load->library('articles');
    }

    public function logout()
    {
        $this->userModel->logout();
        redirect('/');
    }

    public function login()
    {
        if ($this->request->user()->isLogin) {
            redirect('/');
        }

        $error = false;
        $account = $this->request->postVar('account', '');
        $password = $this->request->postVar('password', '');
        $remember = $this->request->postVar('remember', 0);
        $doomcaptcha = $this->request->postVar('doomcaptcha', '');
        $doomcaptcha_confirm = null;

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();
            $doomcaptcha_confirm = substr(sha1($token),5,6);

            if (empty($account) || empty($password)) {
                $error = 'Vui lòng nhập tên tài khoản và mật khẩu';
            } elseif ($doomcaptcha != $doomcaptcha_confirm) {
                $error = 'Vui lòng xác thực captcha';
            } else {
                $error = $this->userLibrary->validateAccount($account);
                if (!$error) {
                    $error = $this->userLibrary->validatePassword($password);
                }

                if (!$error) {
                    $user = $this->userModel->UserDetailWithFields('nick', $account);
                    
                    if ($user) {
                        if ($user['lock_time'] > time()) {
                            $error = 'Tài khoản đang bị khóa do nhập sai mật khẩu quá nhiều lần. Vui lòng thử lại sau ' . ceil(($user['lock_time'] - time()) / 60) . ' phút.';
                        } else {
                            $pdo = Container::get(DB::class);
                            if (verify_password_seamless($password, $user['pass'], $user['id'], $pdo)) {
                                // Reset failed logins
                                if ($user['failed_logins'] > 0) {
                                    $pdo->exec("UPDATE users SET failed_logins = 0, lock_time = 0 WHERE id = {$user['id']}");
                                }
                                
                                // Refetch user to get the new pass if it was migrated
                                $user = $this->userModel->UserDetailWithFields('id', $user['id']);
                                
                                $_SESSION['uid'] = $user['id'];
                                $_SESSION['ups'] = $user['pass'];

                                if ($remember) {
                                    // Generate opaque token
                                    $token = bin2hex(random_bytes(32));
                                    // Hash and save to database
                                    $hashed_token = password_hash($token, PASSWORD_DEFAULT);
                                    $pdo->exec("UPDATE users SET remember_token = '{$hashed_token}' WHERE id = {$user['id']}");
                                    
                                    // Save cookie (365 day)
                                    setcookie('cuid', base64_encode($user['id']), TIME + 31536000, COOKIE_PATH);
                                    setcookie('cups', $token, TIME + 31536000, COOKIE_PATH);
                                }
                                redirect('/');
                            } else {
                                // Sai mật khẩu -> Tăng failed logins
                                $failed = $user['failed_logins'] + 1;
                                $lock = 0;
                                if ($failed >= 5) {
                                    $lock = time() + 15 * 60; // Khoá 15 phút
                                }
                                $pdo->exec("UPDATE users SET failed_logins = $failed, lock_time = $lock WHERE id = {$user['id']}");
                                
                                $error = 'Tên tài khoản hoặc mật khẩu không chính xác';
                            }
                        }
                    } else {
                        $error = 'Tên tài khoản hoặc mật khẩu không chính xác';
                    }
                }
            }
        }

        return view()
            ->setTitle('Đăng nhập')
            ->render('user/login', [
                'error' => $error,
                'inputAccount' => _e($account),
                'inputRemember' => $remember,
                'inputCaptcha' => $doomcaptcha
            ]);
    }

    public function register()
    {
        if ($this->request->user()->isLogin) {
            redirect('/');
        }

        $error = [];
        $captcha = Container::get(Captcha::class);
        $account = $this->request->postVar('account', '');
        $password = $this->request->postVar('password', '');
        $re_password = $this->request->postVar('re_password', '');
        $sex = $this->request->postVar('sex', 'girl');

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            // check account
            $check = $this->userLibrary->validateAccount($account);
            if ($check) {
                $error[] = $check;
            }

            // check password
            $check = $this->userLibrary->validatePassword($password);
            if ($check) {
                $error[] = $check;
            }

            // check repeat password
            $check = $this->userLibrary->validatePasswordConfirmation($password, $re_password);
            if ($check) {
                $error[] = $check;
            }

            // check captcha
            if ($captcha->check() !== true) {
                $error[] = 'Mã bảo vệ không chính xác';
            }

            if (!$error) {
                $check = $this->userModel->UserDetailWithFields('nick', $account);
                if ($check) {
                    $error[] = 'Tên tài khoản đã được sử dụng';
                }
            }

            $sex = mb_strtolower($sex);
            $account = mb_strtolower($account);

            if (!$error) {
                if (!in_array($sex, ['boy', 'girl'])) {
                    $error[] = 'Giới tính được chọn không hợp lệ';
                }
            }

            if (!$error) {
                $user_id = $this->userModel->register([
                    'nick' => $account,
                    'pass' => $password,
                    'sex' => $sex
                ]);

                $_SESSION['uid'] = $user_id;
                $_SESSION['ups'] = e_pass($password);
                redirect('/');
            } else {
                $error = display_error($error);
            }
        }

        return view()
            ->setTitle('Đăng ký')
            ->render('user/register', [
                'error' => $error,
                'inputAccount' => _e($account),
                'inputSex' => _e($sex)
            ]);
    }

    public function UserList()
    {
        // lọc bài viết
        $UserCount = $this->articlesModel->ForumStats()['count_user'];
        $page_query = '?';
        # danh sách thành viên có trên 1 trang
        $per = 12;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        # lọc theo trường
        $order_by = $this->request->getVar('order_by', 'level');
        $order_by = mb_strtolower($order_by);
        $order_by = htmlspecialchars($order_by);
        if (in_array($order_by, config('system.UserList.order_by'))) {
            $page_query .= 'order_by=' . $order_by . '&';
        } else {
            $order_by = 'level';
        }
        # hạn chế lọc đối với != level
        if ($order_by != 'level') {
            $page = 1;
            $sort = 'desc';
        }
        # sắp xếp bài viết theo trường đã lọc
        $sort = $this->request->getVar('sort', 'desc');
        $sort = mb_strtolower($sort);
        $sort = htmlspecialchars($sort);
        if (in_array($sort, config('system.UserList.sort'))) {
            $page_query .= 'sort=' . $sort . '&';
        } else {
            $sort = 'desc';
        }
        # thêm số trang
        if ($page >= 1) {
            $page_query .= 'page=';
        }

        $page_max = ceil($UserCount / $per);
        $start = ($page - 1) * $per;
        $end = $start + $per;
        if ($end >= $UserCount) {
            $end = $UserCount;
        }

        // lấy danh sách thành viên theo bộ lọc và sắp xếp
        $getUserList = $this->userModel->UserList($per, $order_by, $sort, $start);
        //die(print_r($getUserList));
        $UserList = [];
        foreach ($getUserList as $UserDetail) {
            $UserStats = $this->userModel->UserStats($UserDetail['nick']);
            $UserDetail['UserStats'] = $UserStats;
            $UserStatsInfo = [
                'level' => $UserDetail['level'],
                'xu' => $UserDetail['xu'],
                'post' => $UserStats['count_post'] + $UserStats['count_chapter'] + $UserStats['count_comment']
            ];
            //die(print_r($UserStatsInfo));
            $UserDetail['UserStatsInfo'] = ucfirst($order_by) . ': <b>' . $UserStatsInfo[$order_by] . '</b>';

            $UserList[] = $UserDetail;
        }
        $page_title = [
            'level' => 'Thành viên',
            'xu' => 'Top xu',
            'post' => 'Top diễn đàn'
        ];
        $page_title = $page_title[$order_by];

        return view()
            ->setTitle($page_title)
            ->render('user/user_list', [
                'page_title' => $page_title,
                'UserCount' => $UserCount,
                'UserList' => $UserList,
                'UserListPaging' => paging($page_query, $page, $page_max),
                'UserListConfig' => [
                    'allow_order_by' => config('system.UserList.order_by'),
                    'allow_sort' => config('system.UserList.sort'),
                    'order_by' => $order_by,
                    'sort' => $sort
                ],
            ]);
    }

    public function UserDetail($UriAccount)
    {
        if (!$this->request->user()->isLogin) {
            redirect('/login');
        }

        /**
         * MyDetail: thông tin của thành viên đăng nhập
         * YourDetail: thông tin của trang cá nhân đang truy cập
         */

        $template = 'user/user_detail';
        $MyDetail = $this->request->user()->user;
        if (empty($UriAccount)) {
            $YourDetail = $this->request->user()->user;
        } else {
            $UserDetailUri = explode('/', $UriAccount);
            // thông tin hồ sơ
            $account = $UserDetailUri[1];
            $account = trim($account);
            $account = mb_strtolower($account);
            $YourDetail = $this->userModel->UserDetailWithFields('nick', $account);
            if (isset($account) && !$YourDetail) {
                redirect('/404');
            }
            // chỉnh sửa hồ sơ
            $UserDetailEditType = isset($UserDetailUri[2]) ? $UserDetailUri[2] : null;
            if (
                isset($UserDetailEditType)
                && in_array($UserDetailEditType, ['info', 'avatar', 'cover', 'password', 'blocklist', 'waifu-header', 'waifu-rleft', 'waifu-rright'])
                && $MyDetail['nick'] == $YourDetail['nick']
            ) {
                $YourDetail = $MyDetail;
                $template = 'user/user_detail_edit';
                if (in_array($UserDetailEditType, ['waifu-header', 'waifu-rleft', 'waifu-rright'])) {
                    $ArrayData = $this->UserWaifuEdit($MyDetail, $UserDetailEditType, $this->request);
                } else {
                    $ArrayData = $this->UserDetailEdit($MyDetail, $UserDetailEditType, $this->request);
                }
                $ArrayData = array_merge($ArrayData, [
                    'MyDetail' => $MyDetail
                ]);
            }
            if (
                isset($UserDetailEditType)
                && $UserDetailEditType == 'ban'
                && $MyDetail['nick'] != $YourDetail['nick']
                && $MyDetail['level'] >= 120
                && $MyDetail['level'] > $YourDetail['level']
            ) {
                $ArrayData = $this->UserDetailEdit($YourDetail, 'ban', $this->request);
                redirect('/user/'.$YourDetail['nick']);
            }
        }

        $action = $this->request->getVar('action');
        $PostCount = $this->articlesModel->PostCountUser($YourDetail['nick']);
        $getPostList = $this->articlesModel->PostListUser($YourDetail['nick'], 0, 10);
        $PostList = [];
        foreach ($getPostList as $PostDetail) {
            $author = mb_strtolower($PostDetail['author']);
            $PostDetail['UserDetail'] = $this->userModel->UserDetailWithFields('nick', $author);
            $PostDetail['chapter'] = $this->articlesModel->ForumStats('count_chapter_in_post', $PostDetail['id']);
            $PostDetail['comment'] = $this->articlesModel->ForumStats('count_comment_in_post', $PostDetail['id']);

            $PostList[] = $PostDetail;
        }

        if ($template == 'user/user_detail') {
            $ArrayData = [
                'MyDetail' => $MyDetail,
                'YourDetail' => $YourDetail,

                'action' => $action,
                'PostList' => $PostList,
                'PostCount' => $PostCount
            ];
        }

        return view()
            ->setTitle('Trang cá nhân: ' . $YourDetail['name'])
            ->render($template, $ArrayData);
    }

    private function UserDetailEdit($MyDetail, $UserDetailEditType)
    {
        /**
         * Chỉnh sửa hồ sơ
         * $this->UserDetail => $MyDetail
         * type: info, avatar, cover, change.blocklist, change.password
         */

        $error = null;

        $status = $this->request->postVar('status', '');
        $status = isset($status) ? $status : $MyDetail['status'];
        $avatar = $this->request->postVar('avatar', '');
        $cover = $this->request->postVar('cover', $MyDetail['cover']);
        $name = $this->request->postVar('name', $MyDetail['name']);
        $sex = $this->request->postVar('sex', $MyDetail['sex']);

        $old_password = $this->request->postVar('old_password', '');
        $new_password = $this->request->postVar('new_password', '');
        $re_password = $this->request->postVar('re_password', '');

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();
        }
        
        // Ban
        if ($UserDetailEditType == 'ban') {
            if ($MyDetail['level'] >= 0) {
                $SaveData = ['level' => '-1'];
            } else {
                $SaveData = ['level' => '0'];
            }
            $this->userModel->UserDetailEdit($MyDetail, $SaveData);
            return array_merge($SaveData, [
                'error' => null,
                'action' => null
            ]);
        }

        // Info
        if ($UserDetailEditType == 'info') {
            $SaveData = [
                'status' => htmlspecialchars($status),
                'name' => htmlspecialchars($name),
                'avatar' => htmlspecialchars($avatar) ? htmlspecialchars($avatar) : $MyDetail['avatar'],
                'sex' => htmlspecialchars($sex)
            ];

            if ($this->request->getMethod() === 'POST') {
                $this->userLibrary->validateName($name);
                $len_status = $this->articlesLibrary->ContentLen($status);
                if (isset($status) && $len_status > 100) {
                    $error = 'Độ dài status không được vượt quá 100 ký tự, hiện tại: <b>' . $len_status . '</b>';
                }
                if (!in_array($sex, ['boy', 'girl'])) {
                    $sex = 'girl';
                }
                $intavt = intval($avatar);
                if (($avatar)) {
                    if ($intavt < 1 || $intavt > 29) {
                        $error = 'Ảnh đại diện được chọn không hợp lệ';
                    }
                }

                if (!$error) {
                    $this->userModel->UserDetailEdit($MyDetail, $SaveData);
                    redirect('/user/' . $MyDetail['nick']);
                }
            }

            return array_merge($SaveData, [
                'error' => $error,
                'action' => 'info'
            ]);
        }

        // avatar, cover
        $AvatarCoverParttern = '/^https?:\/\/(i\.)?imgur\.com\/[a-zA-Z0-9]+(\.jpg|\.jpeg|\.png|\.gif)?$|^https?:\/\/nosineup\.stockage\.workers\.dev\/[a-zA-Z0-9_.\/-]+$/i';
        if ($UserDetailEditType == 'avatar') {
            $SaveData = [
                'avatar' => htmlspecialchars($avatar) ? htmlspecialchars($avatar) : $MyDetail['avatar'],
            ];
            if ($this->request->getMethod() === 'POST') {
                if (empty($avatar)) {
                    $error = 'Có lỗi xảy ra khi tải lên ảnh đại diện';
                }
                if (!preg_match($AvatarCoverParttern, $avatar)) {
                    $error = 'Ảnh đại diện được tải lên không hợp lệ';
                }
                if (!$error) {
                    $this->userModel->UserDetailEdit($MyDetail, $SaveData);
                    redirect('/user/' . $MyDetail['nick']);
                }
            }
            return array_merge($SaveData, [
                'error' => $error,
                'action' => 'avatar'
            ]);
        } elseif ($UserDetailEditType == 'cover') {
            $SaveData = [
                'cover' => htmlspecialchars($cover) ? htmlspecialchars($cover) : $MyDetail['cover'],
            ];
            if ($this->request->getMethod() === 'POST') {
                if (empty($cover)) {
                    $error = 'Có lỗi xảy ra khi tải lên ảnh bìa';
                }
                if (!preg_match($AvatarCoverParttern, $cover)) {
                    $error = 'Ảnh bìa được tải lên không hợp lệ';
                }
                if (!$error) {
                    $this->userModel->UserDetailEdit($MyDetail, $SaveData);
                    redirect('/user/' . $MyDetail['nick']);
                }
            }
            return array_merge($SaveData, [
                'error' => $error,
                'action' => 'cover'
            ]);
        }
        // password
        if ($UserDetailEditType == 'password') {
            $SaveData = [
                'pass' => e_pass($new_password)
            ];
            if ($this->request->getMethod() === 'POST') {
                $error = [];
                // check old password
                if (e_pass($old_password) != $MyDetail['pass']) {
                    $error[] = 'Mật khẩu cũ không đúng';
                }
                // check new password
                $check = $this->userLibrary->validatePassword($new_password);
                if ($check) {
                    $error[] = $check;
                }
                // check repeat password
                $check = $this->userLibrary->validatePasswordConfirmation($new_password, $re_password);
                if ($check) {
                    $error[] = $check;
                }
                if (!$error) {
                    $this->userModel->UserDetailEdit($MyDetail, $SaveData);
                    $this->logout();
                } else {
                    $error = display_error($error);
                }
            }
            return array_merge($SaveData, [
                'error' => $error,
                'action' => 'password'
            ]);
        }

        // blocklist
        if ($UserDetailEditType == 'blocklist') {
            $SaveData = [
                'blocklist' => $MyDetail['blocklist']
            ];
            if ($this->request->getMethod() === 'POST') {
                $block = '';
                $postBlockList = $_POST['block'] ?? [];
                foreach ($postBlockList as $value) {
                    $value = htmlspecialchars($value);
                    $dub = $this->userModel->UserDetailWithFields('nick', $value);

                    if ($dub && $dub['id']) {
                        $block .= $value . '.';
                    }
                }
                $SaveData['blocklist'] = str_replace($block, '', $MyDetail['blocklist']);
                $this->userModel->UserDetailEdit($MyDetail, $SaveData);
                redirect('/user/' . $MyDetail['nick'] . '/blocklist');
            }

            $UserList = [];
            $BlockList = $SaveData['blocklist'] ? explode('.', $SaveData['blocklist']) : [];
            $BlockList = array_filter($BlockList, fn($value) => $value !== '');
            $CountBlocked = count($BlockList);
            foreach ($BlockList as $nick) {
                $nick = mb_strtolower($nick);
                $UserList[] = $this->userModel->UserDetailWithFields('nick', $nick);
            }
            return [
                'blocklist' => $UserList,
                'count_blocked' => $CountBlocked,
                'error' => $error,
                'action' => 'blocklist'
            ];
        }
    }

    private function UserWaifuEdit($MyDetail, $UserDetailEditType)
    {
        $SaveData = [];
        $error = null;
        $AvatarCoverParttern = '/^https?:\/\/(i\.)?imgur\.com\/[a-zA-Z0-9]+(\.jpg|\.jpeg|\.png|\.gif)?$|^https?:\/\/nosineup\.stockage\.workers\.dev\/[a-zA-Z0-9_.\/-]+$/i';
        $CategoryModel = $this->load->model('articles');
        $MyWaifu = $CategoryModel->ForumDetailWithFields('users_waifu', 'user_id', $MyDetail['id']);

        $waifu = $this->request->postVar($UserDetailEditType, '');
        $column = str_replace('waifu-', '', $UserDetailEditType);
        $SaveData = [
            $column => htmlspecialchars($waifu) ? htmlspecialchars($waifu) : $MyWaifu[$column],
        ];
        if ($this->request->getMethod() === 'POST') {
            if (empty($waifu)) {
                $error = 'Có lỗi xảy ra khi tải lên ảnh waifu';
            }
            if (!preg_match($AvatarCoverParttern, $waifu)) {
                $error = 'Ảnh waifu được tải lên không hợp lệ';
            }
            if (!$error) {
                $this->userModel->UserDetailEdit($MyWaifu, $SaveData, 'users_waifu');
                redirect('/user/' . $MyDetail['nick']);
            }
        }

        return array_merge($SaveData, [
            'error' => $error,
            'action' => $UserDetailEditType
        ]);
    }



    public function forgot_password()
    {
        if ($this->request->user()->isLogin) {
            redirect('/');
        }

        $error = false;
        $success = false;

        if ($this->request->getMethod() === 'POST') {
            $email = $this->request->postVar('email', '');
            
            // Check CSRF
            $token = $this->request->postVar('csrf_token', '');
            if (!isCSRFTokenValid($token)) {
                $error = 'Invalid token';
            } else {
                // Check if email exists
                $userDetail = $this->userModel->UserDetailWithFields('email', $email);
                if (!$userDetail) {
                    $error = 'Email không tồn tại trong hệ thống.';
                } else {
                    $reset_token = bin2hex(random_bytes(32));
                    $reset_expires = time() + 3600; // 1 hour
                    
                    if ($this->userModel->SetResetToken($email, $reset_token, $reset_expires)) {
                        // Send email
                        $smtpConfig = config('system.smtp');
                        if (!empty($smtpConfig['host'])) {
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            try {
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
                                $resetLink = url('/login/password/reset?token=' . $reset_token);
                                $mail->Body    = "Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng click vào link sau để đổi mật khẩu: <br><a href='{$resetLink}'>{$resetLink}</a><br>Link có hiệu lực trong 1 giờ.";

                                $mail->send();
                                $success = 'Một email hướng dẫn đã được gửi tới địa chỉ của bạn.';
                            } catch (Exception $e) {
                                $error = "Không thể gửi email. Mailer Error: {$mail->ErrorInfo}";
                            }
                        } else {
                            $error = 'Hệ thống chưa cấu hình gửi email (SMTP). Vui lòng liên hệ Admin.';
                        }
                    } else {
                        $error = 'Lỗi hệ thống, vui lòng thử lại sau.';
                    }
                }
            }
        }

        return view('user/forgot_password', [
            'page_title' => 'Quên mật khẩu',
            'error' => $error,
            'success' => $success
        ]);
    }

    public function reset_password()
    {
        if ($this->request->user()->isLogin) {
            redirect('/');
        }

        $error = false;
        $success = false;
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            redirect('/login');
        }
        
        $user = $this->userModel->CheckResetToken($token);
        if (!$user) {
            return view('user/reset_password', [
                'page_title' => 'Đặt lại mật khẩu',
                'error' => 'Token không hợp lệ hoặc đã hết hạn.'
            ]);
        }

        if ($this->request->getMethod() === 'POST') {
            $password = $this->request->postVar('password', '');
            $password_confirm = $this->request->postVar('password_confirm', '');
            
            $csrf = $this->request->postVar('csrf_token', '');
            if (!isCSRFTokenValid($csrf)) {
                $error = 'Invalid token';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
            } elseif ($password !== $password_confirm) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } else {
                $new_hash = md5($password);
                if ($this->userModel->ResetPasswordByToken($token, $new_hash)) {
                    $success = 'Mật khẩu đã được đặt lại thành công! Bạn có thể đăng nhập ngay bây giờ.';
                } else {
                    $error = 'Có lỗi xảy ra, không thể đổi mật khẩu.';
                }
            }
        }

        return view('user/reset_password', [
            'page_title' => 'Đặt lại mật khẩu',
            'error' => $error,
            'success' => $success
        ]);
    }
}
