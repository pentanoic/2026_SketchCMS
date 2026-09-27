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

class Auth
{
    public $id = 0;
    public $level = 0;
    public $isLogin = false;
    public $user = [
        'id' => 0,
        'email' => '',
        'nick' => '',
        'name' => '',
        'pass' => '',
        'sex' => '',
        'avatar' => '',
        'cover' => '',
        'new_mail' => '',
        'mail_list' => '',
        'blocklist' => '',
        'level' => 0,
        'reg' => 0,
        'on' => 0,
        'login' => '',
        'status' => '',
        'karma' => 0
    ];
    public $isLoginHaveWaifu = false;
    public $user_waifu = [];
    public $new_mail_count = 0;
    public $system_notify_count = 0;

    public $settings;

    private $db;

    public function __construct()
    {
        $this->db = Container::get(DB::class);
        $this->authorize();
    }

    private function authorize()
    {
        $id = 0;
        $session_id = 0;
        $cookie_id = 0;
        $cookie_token = '';

        if (isset($_SESSION['uid'])) {
            $session_id = intval(trim($_SESSION['uid']));
        } elseif (isset($_COOKIE['cuid']) && isset($_COOKIE['cups'])) {
            $cookie_id = intval(base64_decode(trim($_COOKIE['cuid'])));
            $cookie_token = trim($_COOKIE['cups']);
        }

        $id = $session_id ?: $cookie_id;

        if ($id) {
            $stmt = $this->db->prepare('SELECT * FROM `users` WHERE `id` = ? LIMIT 1');
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            $stmtWaifu = $this->db->prepare('SELECT * FROM `users_waifu` WHERE `user_id` = ? LIMIT 1');
            $stmtWaifu->execute([$id]);
            $user_waifu = $stmtWaifu->fetch();

            if ($user) {
                $is_valid = false;
                
                if ($session_id) {
                    $is_valid = true;
                } elseif ($cookie_id && $cookie_token && !empty($user['remember_token'])) {
                    if (password_verify($cookie_token, $user['remember_token'])) {
                        $is_valid = true;
                        $_SESSION['uid'] = $id;
                    }
                }

                if ($is_valid) {
                    $this->isLogin = true;
                    $this->id = (int) $user['id'];
                    $this->level = (int) $user['level'];
                    $this->user = $user;
                    $this->new_mail_count = $this->NewMailCount($user);
                    $this->system_notify_count = $this->SystemNotifyCount($user);

                    if ($user['level'] < 0) {
                        die('<div style="text-align: center; font-size: xx-large">'
                            . '<h3 style="color: #dd0000">Account Suspended</h3>'
                            . 'Tài khoản của bạn đã bị dừng hoạt động do vi phạm Nội quy diễn đàn!'
                            . '</div>');
                    }

                    $this->db->prepare('UPDATE `users` SET
                        `on`   = ?
                        WHERE `id` = ? LIMIT 1
                    ')->execute([TIME, $user['id']]);
                    // kiểm tra xem user_id có trong bảng `users_waifu` chưa, nếu chưa thì insert vào
                    $stmtWaifu = $this->db->prepare('
                        SELECT COUNT(*) as count 
                        FROM users_waifu 
                        WHERE user_id =?
                    ');
                    $stmtWaifu->execute([$this->id]);
                    $resultWaifu = $stmtWaifu->fetch(PDO::FETCH_ASSOC);
                    if ($resultWaifu['count'] == 0) {
                        $insertWaifu = $this->db->prepare('INSERT INTO `users_waifu` (`user_id`) VALUES (?)');
                        $insertWaifu->execute([$this->id]);
                    } else {
                        $this->isLoginHaveWaifu = true;
                    }
                    if ($user_waifu) {
                        $this->user_waifu = $user_waifu;
                    }
                } else {
                    $this->unset();
                }
            } else {
                $this->unset();
            }
        }
    }

    private function unset()
    {
        unset($_SESSION['uid']);
        setcookie('cuid', '', TIME - 60, COOKIE_PATH);
        setcookie('cups', '', TIME - 60, COOKIE_PATH);
    }

    public function NewMailCount($MyDetail)
    {
        $count = 0;
        if ($MyDetail['new_mail']) {
            $getMailList = explode('.', $MyDetail['new_mail']);
            $sysBot = defined('UserBot') ? UserBot : 'sei';
            $getMailList = array_filter($getMailList, function ($value) use ($sysBot) {
                return $value !== null && $value !== false && $value !== "" && $value !== $sysBot;
            });
            $count = count($getMailList);
        }
        return $count;
    }

    public function SystemNotifyCount($MyDetail)
    {
        $stmtCount = $this->db->prepare('
            SELECT COUNT(*) as count 
            FROM mail 
            WHERE sender_receiver = ? AND view = ?
        ');
        $stmtCount->execute([$MyDetail['nick'] . '_' . UserBot, 'no']);
        $result = $stmtCount->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}