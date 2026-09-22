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

class userLibrary
{
    public function validateEmail($email)
    {
        if (empty($email)) {
            return 'Địa chỉ email không được để trống';
        }

        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return 'Địa chỉ email không hợp lệ';
        }

        if (preg_match('/^\.|[^0-9a-z.]|\.\.+|\.$/i', $parts[0])) {
            return 'Địa chỉ email không hợp lệ';
        }

        return false;
    }

    public function validateAccount($account)
    {
        if (empty($account)) {
            return 'Tên tài khoản không được để trống';
        }

        $len = mb_strlen($account);

        if ($len < 3 || $len > 32) {
            return 'Độ dài tên tài khoản phải từ 3 đến 15 ký tự';
        }

        if (preg_match('/[^0-9a-z]/i', $account)) {
            return 'Tên tài khoản chỉ được sử dụng chữ latin, và các chữ số';
        }

        return false;
    }


    public function validatePassword($password)
    {
        if (empty($password)) {
            return 'Mật khẩu không được để trống';
        }

        $len = mb_strlen($password);

        if ($len < 3 || $len > 32) {
            return 'Độ dài mật khẩu phải từ 3 đến 32 ký tự';
        }

        return false;
    }

    public function validatePasswordConfirmation($password, $passwordConfirmation)
    {
        if ($password !== $passwordConfirmation) {
            return 'Mật khẩu không trùng khớp';
        }

        return false;
    }

    public function validateName($name)
    {
        if (empty($name)) {
            return 'Tên hiển thị không được để trống';
        }

        $len = mb_strlen($name);

        if ($len < 4 || $len > 32) {
            return 'Độ dài tên hiển thị phải từ 5 đến 32 ký tự';
        }

        if (preg_match('/[^[:alnum:]\s]/ui', $name)) {
            return 'Tên hiển thị chỉ có thể sử dụng chữ cái (có dấu), chữ số và khoảng trắng';
        }

        return false;
    }
}
