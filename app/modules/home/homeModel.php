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

class homeModel extends Model
{
    public function logout()
    {
        $cookiePath = '/' . ltrim((string) Env::get('SITE_PATH', ''), '/');
        setcookie('cuid', '', TIME - 60, $cookiePath);
        setcookie('cups', '', TIME - 60, $cookiePath);
        unset($_SESSION['uid']);
        unset($_SESSION['ups']);
    }

    // check if user exits for login
    public function getForLogin($type, $email)
    {
        return db('users')->select(['id', 'password'])->where($type, $email)->first();
    }

    // check if account or email is exists for register
    public function checkUsedInfo($account, $email) {
        return db('users')
            ->select(['account', 'email'])
            ->whereRaw('REPLACE(`account`, ".", "") = ?', [str_replace('.', '', $account)])
            ->orWhere('email', $email)
            ->first() ?? false;
    }

    // register
    public function register($account, $password, $email)
    {
        db('users')->insert([
            'account'      => $account,
            'password'     => md5(md5($password)),
            'email'        => $email,
            'join_date'    => TIME,
            'last_login'   => TIME
        ]);

        return $this->db->lastInsertId();
    }
}