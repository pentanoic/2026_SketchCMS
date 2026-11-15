#!/usr/bin/env php
<?php
/**
 * SketchCMS E2E Private Mail Test using cURL
 * Usage: php tests/run-normal/mail_between_users_test.php [--base-url=http://sketchcms.test]
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

$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
require $root . 'system' . DIRECTORY_SEPARATOR . 'bootstrap.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'e2e_helper.php';

echo "=== SketchCMS E2E Private Mail Test (cURL) ===\n";
echo "Base URL: {$baseUrl}\n\n";

$testerUser = db('users')->where('nick', 'tester')->first();
if (!$testerUser) {
    db('users')->insert([
        'nick'  => 'tester',
        'name'  => 'Tester',
        'pass'  => md5('Tester123!'),
        'level' => 1,
        'xu'    => 100,
        'reg'   => TIME,
        'on'    => TIME,
    ]);
} else {
    db('users')->where('id', $testerUser['id'])->update([
        'pass' => md5('Tester123!'),
    ]);
}

$submitForm = function (E2EClient $client, string $path, array $overrides): array {
    $page = $client->request('GET', $path);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $page['body']);
    $forms = $dom->getElementsByTagName('form');
    $targetForm = null;
    foreach ($forms as $f) {
        if (strtolower($f->getAttribute('method')) === 'post') {
            $targetForm = $f;
            break;
        }
    }
    if (!$targetForm && $forms->length > 0) {
        $targetForm = $forms->item(0);
    }
    $data = [];
    if ($targetForm) {
        foreach ($targetForm->getElementsByTagName('input') as $inp) {
            $name = $inp->getAttribute('name');
            if ($name !== '') {
                $data[$name] = $inp->getAttribute('value');
            }
        }
        foreach ($targetForm->getElementsByTagName('textarea') as $ta) {
            $name = $ta->getAttribute('name');
            if ($name !== '') {
                $data[$name] = $overrides['content'] ?? $overrides['msg'] ?? '';
            }
        }
        foreach ($targetForm->getElementsByTagName('button') as $btn) {
            $name = $btn->getAttribute('name');
            if ($name !== '' && strtolower($btn->getAttribute('type')) === 'submit') {
                $data[$name] = $btn->getAttribute('value') ?: '1';
            }
        }
        $action = $targetForm->getAttribute('action') ?: $path;
    } else {
        $action = $path;
    }

    foreach ($overrides as $k => $v) {
        if (array_key_exists($k, $data)) {
            $data[$k] = $v;
        }
    }
    if (isset($overrides['nick']) && !isset($data['nick'])) {
        foreach ($data as $k => $v) {
            if (stripos($k, 'user') !== false || stripos($k, 'nick') !== false || stripos($k, 'account') !== false) {
                $data[$k] = $overrides['nick'];
            }
        }
    }
    if (isset($overrides['pass']) && !isset($data['pass'])) {
        foreach ($data as $k => $v) {
            if (stripos($k, 'pass') !== false) {
                $data[$k] = $overrides['pass'];
            }
        }
    }

    $postPath = parse_url($action, PHP_URL_PATH) ?: $path;
    return $client->request('POST', $postPath, $data);
};

$testerClient = new E2EClient($baseUrl);

// 1. Login as tester via cURL
$loginRes = $submitForm($testerClient, '/user/login', [
    'nick' => 'tester',
    'pass' => 'Tester123!',
]);
$testerClient->check('cURL POST /user/login (tester)', $loginRes);

// 2. Send private mail from tester -> admin via cURL
$msgFromTester = 'cURL Private Mail ' . date('H:i:s');
$mailRes = $submitForm($testerClient, '/mail/send/admin', [
    'content' => $msgFromTester,
    'msg'     => $msgFromTester,
]);
$testerClient->check('cURL POST /mail/send/admin (gửi tin nhắn riêng)', $mailRes);

// 3. Verify in DB
$row = db('mail')->where('nick', 'tester')->orderBy('id', 'DESC')->first();
if ($row && strpos((string)$row['content'], 'cURL Private Mail') !== false) {
    echo "  [PASS] Tin nhắn riêng đã lưu vào DB (id={$row['id']})\n";
    $testerClient->passed++;
} else {
    echo "  [FAIL] Không tìm thấy tin nhắn riêng trong DB\n";
    $testerClient->failed++;
}

// 4. Verify tester sees the sent message on /mail/send/admin
$inboxRes = $testerClient->request('GET', '/mail/send/admin');
$testerClient->check('cURL GET /mail/send/admin (hiển thị tin nhắn)', $inboxRes, $msgFromTester);

echo "\n========================================\n";
$total = $testerClient->passed + $testerClient->failed;
echo "Total: {$total} | Passed: {$testerClient->passed} | Failed: {$testerClient->failed}\n";
exit($testerClient->failed > 0 ? 1 : 0);
