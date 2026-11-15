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
        $stmt = $this->db->prepare('
            SELECT
                (SELECT COUNT(*) FROM articles_post WHERE author = :nick) AS count_post,
                (SELECT COUNT(*) FROM articles_chap WHERE author = :nick) AS count_chapter,
                (SELECT COUNT(*) FROM articles_cmt WHERE author = :nick) AS count_comment,
                (SELECT COUNT(*) FROM articles_file WHERE author = :nick) AS count_file
        ');
        $stmt->bindParam(':nick', $nick, \PDO::PARAM_STR);
        $stmt->execute();
        $counts = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $counts;
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
            $stmt = $this->db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $stmt->execute([intval($_SESSION['uid'])]);
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
        $stmt = $this->db->prepare('SELECT `id`, `nick`, `email`, `pass`, `level`, `failed_logins`, `lock_time` FROM `users` WHERE `' . $type . '` = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function recordLoginFailure(array $user): int
    {
        $failed = ((int)($user['failed_logins'] ?? 0)) + 1;
        $lock = ($failed >= 5) ? (time() + 15 * 60) : 0;
        $stmt = $this->db->prepare('UPDATE `users` SET `failed_logins` = :failed, `lock_time` = :lock WHERE `id` = :id');
        $stmt->execute([
            ':failed' => $failed,
            ':lock'   => $lock,
            ':id'     => (int)$user['id']
        ]);
        return $lock;
    }

    public function resetLoginFailures(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE `users` SET `failed_logins` = 0, `lock_time` = 0 WHERE `id` = :id');
        $stmt->execute([':id' => $userId]);
    }

    // check if account or email is exists for register
    public function checkUsedInfo($account, $email)
    {
        $stmt = $this->db->prepare('SELECT `nick`, `email` FROM `users` WHERE nick = :account OR `email` = :email LIMIT 1');
        $stmt->execute(['account' => $account, 'email' => $email]);
        $data = $stmt->fetch();

        return $data;
    }

    // register
    public function register($user)
    {
        $stmt = $this->db->prepare('INSERT INTO `users` SET
            `nick` = :nick,
            `name` = :name,
            `pass` = :pass,
            `sex` = :sex,
            `reg` = :reg
        ');
        $stmt->execute([
            'nick' => $user['nick'],
            'name' => $user['nick'],
            'pass' => hash_password_secure($user['pass']),
            'sex' => $user['sex'],
            'reg' => TIME
        ]);

        return $this->db->lastInsertId();
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
        $select = $this->db->prepare('SELECT id, pass FROM users WHERE LENGTH(pass) < 15 LIMIT :limit');
        $select->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $select->execute();
        $data = $select->fetchAll();

        if (empty($data)) {
            return 0;
        }

        $count = 0;
        foreach ($data as $user) {
            $pass = md5($user['pass']);
            $update = $this->db->prepare('UPDATE users SET pass = :pass WHERE id = :id');
            $update->bindParam(':pass', $pass, \PDO::PARAM_STR);
            $update->bindParam(':id', $user['id'], \PDO::PARAM_INT);
            $update->execute();
            $count++;
        }
        return $count;
    }

    public function UserList($per = 10, $order_by = 'level', $sort = 'desc', $start = 0)
    {
        // Kiểm tra giá trị của $order_by và $sort
        if (!in_array($order_by, config('system.UserList.order_by'))) {
            $order_by = 'level';
        }
        if (!in_array($sort, config('system.UserList.sort'))) {
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
        $sql = 'SELECT users.*, ' . $order_by_sql . ' AS order_value FROM users WHERE level >= 0 ORDER BY order_value ' . $sort . ' LIMIT :start, :per';

        // Chuẩn bị câu lệnh SQL
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':per', $per, \PDO::PARAM_INT);

        // Thực thi câu lệnh
        $stmt->execute();

        // Lấy tất cả kết quả
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function UserListOnline($start = 0, $end = 0)
    {
        $onlimit = date('U') - 300;
        $sql = 'SELECT * FROM users WHERE `on` > :onlimit';
        if ($end > 0) {
            $sql .= ' LIMIT :start, :end';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':onlimit', $onlimit, \PDO::PARAM_INT);
        if ($end > 0) {
            $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
            $stmt->bindParam(':end', $end, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
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
        $sql = "UPDATE `$table` SET ";
        $setParts = [];
        $bindValues = [];
        foreach ($SaveData as $key => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$key)) {
                continue;
            }
            $setParts[] = "`$key` = :$key";
            $bindValues[":$key"] = $value;
        }
        if (empty($setParts)) {
            return 0;
        }
        $sql .= implode(', ', $setParts);
        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $bindValues[':id'] = $MyDetail['id'];

        foreach ($bindValues as $param => $val) {
            $stmt->bindValue($param, $val);
        }

        $stmt->execute();
        return $stmt->rowCount();
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
        $stmt = $this->db->prepare('UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email');
        $stmt->execute([
            'token' => $hashedToken,
            'expires' => $expires,
            'email' => $email
        ]);
        return $stmt->rowCount() > 0;
    }

    public function CheckResetToken($token)
    {
        $hashedToken = hash_identifier((string)$token);
        $stmt = $this->db->prepare('SELECT * FROM users WHERE reset_token = :token AND reset_expires > :time LIMIT 1');
        $stmt->execute([
            'token' => $hashedToken,
            'time' => time()
        ]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function ResetPasswordByToken($token, $new_password)
    {
        $hashedToken = hash_identifier((string)$token);
        $stmt = $this->db->prepare('UPDATE users SET pass = :pass, reset_token = NULL, reset_expires = NULL, failed_logins = 0, lock_time = 0 WHERE reset_token = :token');
        $stmt->execute([
            'pass' => $new_password,
            'token' => $hashedToken
        ]);
        return $stmt->rowCount() > 0;
    }
}
