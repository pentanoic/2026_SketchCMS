#!/usr/bin/env php
<?php
/**
 * SketchCMS End-to-End (E2E) Functional Test Suite
 * Simulates real browser user interactions across all major features.
 * Usage: php system/bin/run-nomal/run_all.php [--base-url=http://sketchcms.test]
 */

if (PHP_SAPI !== 'cli') {
    exit("This script must be run from the command line.\n");
}

$baseUrl = 'http://sketchcms.test';
foreach ($argv as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = rtrim(substr($arg, 11), '/');
    }
}

$root = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
require $root . 'system' . DIRECTORY_SEPARATOR . 'bootstrap.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'e2e_helper.php';

$client = new E2EClient($baseUrl);
echo "=== SketchCMS E2E Functional Suite ===\n";
echo "Base URL: {$baseUrl}\n\n";

// 1. Guest Navigation
echo "[1] Guest Navigation\n";
$client->check('GET / (Trang chủ)', $client->request('GET', '/'));
$client->check('GET /articles (Bài viết)', $client->request('GET', '/articles'));
$client->check('GET /shoutbox (Phòng chat)', $client->request('GET', '/shoutbox'));
$client->check('GET /user (Thành viên)', $client->request('GET', '/user'));
$client->check('GET /user/login (Form đăng nhập)', $client->request('GET', '/user/login'));
$client->check('GET /user/register (Form đăng ký)', $client->request('GET', '/user/register'));

// 2. Auth Flow (Create temp user in DB, login via form, verify session, cleanup)
echo "\n[2] Auth & User Flow\n";
$testNick = 'e2e_user_' . time();
$testPass = 'TestPass123!';
$testUserId = db('users')->insert([
    'nick'       => $testNick,
    'name'       => 'E2E Test User',
    'pass'       => password_hash($testPass, PASSWORD_BCRYPT),
    'level'      => 1,
    'xu'         => 100,
    'reg'        => TIME,
    'on'         => TIME,
]);

$loginPage = $client->request('GET', '/user/login');
$csrf = $client->extractCsrf($loginPage['body']);
$loginData = [
    'nick' => $testNick,
    'pass' => $testPass,
    'submit' => '1',
];
if ($csrf !== null) {
    $loginData['csrf_token'] = $csrf;
}
$client->check('POST /user/login (Đăng nhập tài khoản test)', $client->request('POST', '/user/login', $loginData));
$client->check('GET /user/profile (Trang cá nhân sau đăng nhập)', $client->request('GET', '/user/profile'));

// 3. Shoutbox Flow
echo "\n[3] Shoutbox Flow\n";
$sbPage = $client->request('GET', '/shoutbox');
$sbCsrf = $client->extractCsrf($sbPage['body']);
$sbData = ['msg' => 'E2E test message ' . date('H:i:s'), 'submit' => '1'];
if ($sbCsrf !== null) {
    $sbData['csrf_token'] = $sbCsrf;
}
$client->check('POST /shoutbox (Gửi tin nhắn)', $client->request('POST', '/shoutbox', $sbData));

// 4. Mail Flow
echo "\n[4] Mail Flow\n";
$client->check('GET /mail (Hộp thư)', $client->request('GET', '/mail'));

// 5. Manager Flow
echo "\n[5] Manager Flow\n";
$client->check('GET /manager (Bảng điều khiển)', $client->request('GET', '/manager'));
$client->check('GET /manager/security (Bảo mật & Schema)', $client->request('GET', '/manager/security'));
$client->check('GET /manager/modules (Quản lý Modules)', $client->request('GET', '/manager/modules'));
$client->check('GET /manager/users (Quản lý Thành viên)', $client->request('GET', '/manager/users'));
$client->check('GET /manager/articles (Quản lý Bài viết)', $client->request('GET', '/manager/articles'));
$client->check('GET /manager/files (Quản lý Tập tin)', $client->request('GET', '/manager/files'));

// Cleanup temp test user & shoutbox message
db('users')->where('id', $testUserId)->delete();
db('chat')->where('name', $testNick)->delete();

echo "\n========================================\n";
echo "Total: " . ($client->passed + $client->failed) . " | Passed: {$client->passed} | Failed: {$client->failed}\n";
exit($client->failed > 0 ? 1 : 0);
