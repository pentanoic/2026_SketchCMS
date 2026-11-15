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

class userModel extends Model
{
    private articlesModel $articlesModel;

    function __construct()
    {
        parent::__construct();
        $this->articlesModel = $this->load->model('articles');
    }

    public function UserStats($nick)
    {
        $nick = mb_strtolower($nick);
        return [
            'count_post' => db('articles_post')->where('author', $nick)->count(),
            'count_chapter' => db('articles_chap')->where('author', $nick)->count(),
            'count_comment' => db('articles_cmt')->where('author', $nick)->count(),
            'count_file' => db('articles_file')->where('author', $nick)->count(),
        ];
    }

    public function isLoginredirect($request)
    {
        if (!$request->user()->isLogin) {
            redirect('/');
        }
    }

    public function isAdmin120redirect($request)
    {
        $this->isLoginredirect($request);
        $MyDetail = $request->user()->user;
        if ($MyDetail['level'] < 120) {
            redirect('/404');
        }
    }

    public function logout()
    {
        if (isset($_SESSION['uid'])) {
            db('users')->where('id', intval($_SESSION['uid']))->update(['remember_token' => null]);
        }
        $cookiePath = '/' . ltrim((string) Env::get('SITE_PATH', ''), '/');
        $cookieOpts = [
            'expires'  => TIME - 60,
            'path'     => $cookiePath ?: '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie('cuid', '', $cookieOpts);
        setcookie('cups', '', $cookieOpts);
        unset($_SESSION['uid']);
    }

    // check if user exits for login
    public function getForLogin($type, $email)
    {
        if (!in_array($type, ['id', 'nick', 'email'], true)) {
            $type = 'nick';
        }
        return db('users')
            ->select(['id', 'nick', 'email', 'pass', 'level', 'failed_logins', 'lock_time'])
            ->where($type, $email)
            ->first();
    }

    public function recordLoginFailure(array $user): int
    {
        $failed = ((int)($user['failed_logins'] ?? 0)) + 1;
        $lock = ($failed >= 5) ? (time() + 15 * 60) : 0;
        db('users')->where('id', (int)$user['id'])->update([
            'failed_logins' => $failed,
            'lock_time'     => $lock,
        ]);
        return $lock;
    }

    public function resetLoginFailures(int $userId): void
    {
        db('users')->where('id', $userId)->update([
            'failed_logins' => 0,
            'lock_time'     => 0,
        ]);
    }

    // check if account or email is exists for register
    public function checkUsedInfo($account, $email)
    {
        return db('users')
            ->select(['nick', 'email'])
            ->where('nick', $account)
            ->orWhere('email', $email)
            ->first();
    }

    // register
    public function register($user)
    {
        return db('users')->insert([
            'nick' => $user['nick'],
            'name' => $user['nick'],
            'pass' => hash_password_secure($user['pass']),
            'sex'  => $user['sex'],
            'reg'  => TIME,
        ]);
    }

    public function UserDetailWithFields($fields = 'nick', $value = 'bot')
    {
        if ($value === 'bot') {
            $value = (string) Env::get('USER_BOT', 'SystemBOT');
        }

        $stmt = $this->articlesModel->ForumDetailWithFields('users', $fields, $value);
        return $stmt;
    }

    public function UpdateIfNotMD5($limit = 100)
    {
        $data = db('users')
            ->select(['id', 'pass'])
            ->whereRaw('LENGTH(pass) < 15')
            ->limit((int)$limit)
            ->get();

        if (empty($data)) {
            return 0;
        }

        $count = 0;
        foreach ($data as $user) {
            db('users')->where('id', $user['id'])->update(['pass' => md5($user['pass'])]);
            $count++;
        }
        return $count;
    }

    public function UserList($per = 10, $order_by = 'level', $sort = 'desc', $start = 0)
    {
        // Kiểm tra giá trị của $order_by và $sort
        if (!in_array($order_by, Env::getList('USER_LIST_ORDER_BY', ['level', 'xu', 'post']))) {
            $order_by = 'level';
        }
        if (!in_array($sort, Env::getList('USER_LIST_SORT', ['asc', 'desc']))) {
            $sort = 'desc';
        }

        // Tạo câu SQL dựa trên giá trị của $order_by
        if ($order_by == 'post') {
            $order_by_sql = '(
                (SELECT COUNT(*) FROM articles_post WHERE articles_post.author = users.nick COLLATE utf8mb4_unicode_ci) 
                + (SELECT COUNT(*) FROM articles_chap WHERE articles_chap.author = users.nick COLLATE utf8mb4_unicode_ci) 
                + (SELECT COUNT(*) FROM articles_cmt WHERE articles_cmt.author = users.nick)
            )';
        } else {
            $order_by_sql = 'users.' . $order_by;
        }

        // SQL query để lấy danh sách người dùng theo thứ tự
        return db('users')
            ->selectRaw('users.*, ' . $order_by_sql . ' AS order_value')
            ->where('level', '>=', 0)
            ->orderBy('order_value', $sort)
            ->limit((int)$per)
            ->offset((int)$start)
            ->get();
    }

    public function UserListOnline($start = 0, $end = 0)
    {
        $onlimit = date('U') - 300;
        $query = db('users')->where('on', '>', $onlimit);
        if ($end > 0) {
            $query->limit((int)$end)->offset((int)$start);
        }
        return $query->get();
    }

    public function UserOnlineStatus($UserDetail)
    {
        $onlimit = date('U') - 300;
        $status = 'offline';
        if ($UserDetail['on'] > $onlimit) {
            $status = 'online';
        }
        return $status;
    }

    public function UserDetailEdit($MyDetail, $SaveData, $table = 'users')
    {
        if (!in_array($table, ['users', 'users_waifu'], true)) {
            $table = 'users';
        }
        $cleanData = [];
        foreach ($SaveData as $key => $value) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', (string)$key)) {
                $cleanData[$key] = $value;
            }
        }
        if (empty($cleanData)) {
            return 0;
        }
        return db($table)->where('id', $MyDetail['id'])->update($cleanData);
    }

    public function UserDetailBlockList($UserDetail)
    {
        if (!isset($UserDetail['blocklist']) || !is_string($UserDetail['blocklist'])) {
            return [
                'Get' => [],
                'Count' => 0
            ];
        }
        $BlockList = explode('.', $UserDetail['blocklist']);
        $BlockList = array_filter($BlockList, function ($value) {
            return $value !== '';
        });
        $CountBlocked = count($BlockList);
        return [
            'Get' => array_values($BlockList),
            'Count' => $CountBlocked
        ];
    }

    public function SetResetToken($email, $token, $expires)
    {
        $hashedToken = hash_identifier((string)$token);
        return db('users')->where('email', $email)->update([
            'reset_token'   => $hashedToken,
            'reset_expires' => $expires,
        ]) > 0;
    }

    public function CheckResetToken($token)
    {
        $hashedToken = hash_identifier((string)$token);
        return db('users')
            ->where('reset_token', $hashedToken)
            ->where('reset_expires', '>', time())
            ->first();
    }

    public function ResetPasswordByToken($token, $new_password)
    {
        $hashedToken = hash_identifier((string)$token);
        return db('users')->where('reset_token', $hashedToken)->update([
            'pass'          => $new_password,
            'reset_token'   => null,
            'reset_expires' => null,
            'failed_logins' => 0,
            'lock_time'     => 0,
        ]) > 0;
    }
}
