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

class Error
{
    private static bool $registered = false;

    /**
     * Đăng ký bộ xử lý ngoại lệ và lỗi tập trung để bảo vệ thông tin hệ thống
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Kiểm tra yêu cầu hiện tại có phải là API hoặc AJAX hay không
     */
    public static function expectsJson(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') !== false) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Xử lý ngoại lệ chưa được bắt (Uncaught Throwable)
     */
    public static function handleException(\Throwable $e): void
    {
        $isDebug = defined('APP_DEBUG') && APP_DEBUG === true;
        $message = $isDebug ? $e->getMessage() : 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';

        self::abort(500, $message, self::expectsJson());
    }

    /**
     * Dừng thực thi và trả về phản hồi bảo mật chuẩn hóa
     */
    public static function abort(int $statusCode = 403, string $message = 'Forbidden', ?bool $forceJson = null): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        $isJson = $forceJson !== null ? $forceJson : self::expectsJson();

        if ($isJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status' => 'error',
                'code' => $statusCode,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($statusCode === 404 && function_exists('view')) {
            try {
                $view = view();
                if ($view && $view->getEngine()->exists('404')) {
                    $view->setTitle('404 Not Found');
                    $view->output('404');
                    exit;
                }
            } catch (\Throwable $t) {
                // Fallback bên dưới
            }
        }

        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo '<div style="text-align: center; padding: 40px 20px; font-family: sans-serif;">'
            . '<h3 style="color: #dd0000; margin-bottom: 10px;">HTTP ' . (int)$statusCode . '</h3>'
            . '<p>' . $safeMessage . '</p>'
            . '</div>';
        exit;
    }
}
