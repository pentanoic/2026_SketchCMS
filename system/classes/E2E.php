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

class E2E
{
    private $method = 'aes-256-cbc';
    
    /**
     * Mã hóa tin nhắn
     * @param string $data Nội dung tin nhắn cần mã hóa
     * @param string $key E2E_SECRET_KEY
     * @param string $salt Tên module + tên người gửi
     * @return string Chuỗi base64 đã mã hóa
     */
    public function encrypt($data, $key, $salt)
    {
        // Tạo IV cố định (16 bytes) dựa trên $salt để đảm bảo cùng $salt thì sinh ra cùng IV
        $iv = substr(hash('sha256', $salt), 0, 16);
        $encrypted = openssl_encrypt($data, $this->method, $key, 0, $iv);
        return base64_encode($encrypted);
    }
    
    /**
     * Giải mã tin nhắn
     * @param string $data Chuỗi base64 mã hóa
     * @param string $key E2E_SECRET_KEY
     * @param string $salt Tên module + tên người gửi
     * @return string Nội dung ban đầu (hoặc false nếu thất bại)
     */
    public function decrypt($data, $key, $salt)
    {
        $decoded = base64_decode($data);
        if ($decoded === false) return false;
        
        $iv = substr(hash('sha256', $salt), 0, 16);
        $decrypted = openssl_decrypt($decoded, $this->method, $key, 0, $iv);
        return $decrypted;
    }

    /**
     * Kiểm tra nội dung đã được mã hóa hay chưa
     * @param string $data Chuỗi cần kiểm tra
     * @param string $key E2E_SECRET_KEY
     * @param string $salt Tên module + tên người gửi
     * @return bool true nếu đã mã hóa thành công, ngược lại false
     */
    public function isEncrypted($data, $key, $salt)
    {
        if (empty($data)) {
            return false;
        }
        
        // Kiểm tra xem có đúng định dạng Base64 hay không
        if (base64_encode(base64_decode($data, true)) !== $data) {
            return false;
        }

        // Thử giải mã, nếu giải mã không lỗi (trả về giá trị !== false) thì là chuỗi đã mã hóa đúng
        $decrypted = $this->decrypt($data, $key, $salt);
        return $decrypted !== false;
    }
}
