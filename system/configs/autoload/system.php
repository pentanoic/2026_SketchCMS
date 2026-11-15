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

return [
    'app' => [
        'name' => (string) Env::get('APP_NAME', 'LAB302'),
        'description' => (string) Env::get('APP_DESCRIPTION', 'từng bước vững vàng, mở lối tương lai'),
        'keyword' => (string) Env::get('APP_KEYWORD', 'lab302, huce, nuce, sketchcms'),
        'site' => [
            'scheme' => (string) Env::get('SITE_SCHEME', 'http://'),
            'host' => (string) Env::get('SITE_HOST', 'sketchcms.test'),
            'path' => (string) Env::get('SITE_PATH', ''),
        ],
        'default_template' => (string) Env::get('DEFAULT_TEMPLATE', 'default'),
        'installed_modules' => Env::getList('INSTALLED_MODULES', ['custom_headless']),
    ],
    'PostList' => [
        'order_by' => Env::getList('POST_LIST_ORDER_BY', ['id', 'time', 'update_time', 'view']),
        'sort' => Env::getList('POST_LIST_SORT', ['asc', 'desc']),
    ],
    'UserList' => [
        'order_by' => Env::getList('USER_LIST_ORDER_BY', ['level', 'xu', 'post']),
        'sort' => Env::getList('USER_LIST_SORT', ['asc', 'desc']),
    ],
    'Manager' => [
        'allow_country' => Env::getList('MANAGER_ALLOW_COUNTRY', ['ID', 'MY', 'SG', 'TH', 'PH', 'BN', 'KH', 'LA', 'MM', 'TL', 'VN']),
        'assets' => (string) Env::get('MANAGER_ASSETS', 'https://cdn.jsdelivr.net/gh/jesuisnk2/someTPL/sneat-free'),
    ],
    'url_rules' => [
        'rewrite' => Env::getJson('URL_RULES_REWRITE', [
            '/faq/terms' => [
                'target' => '/home/faq/terms',
                'method' => 'GET|POST',
            ],
            '/faq/about' => [
                'target' => '/home/faq/about',
                'method' => 'GET|POST',
            ],
            '/faq/help' => [
                'target' => '/home/faq/help',
                'method' => 'GET|POST',
            ],
        ]),
        'disable' => Env::getList('URL_RULES_DISABLE', ['/register']),
    ],
    'security' => [
        'rate_limit' => (int) Env::get('SECURITY_RATE_LIMIT', 2),
    ],
    'smtp' => [
        'host' => (string) Env::get('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int) Env::get('SMTP_PORT', 465),
        'user' => (string) Env::get('SMTP_USER', 'sketchcms@lab302.ovh'),
        'pass' => (string) Env::get('SMTP_PASS', '123123123'),
        'encrypt' => (string) Env::get('SMTP_ENCRYPT', 'ssl'),
        'from_email' => (string) Env::get('SMTP_FROM_EMAIL', 'sketchcms@lab302.ovh'),
        'from_name' => (string) Env::get('SMTP_FROM_NAME', 'SketchCMS'),
    ],
    'media' => [
        'telegram_upload_mode' => (string) Env::get('TELEGRAM_UPLOAD_MODE', 'direct'),
        'telegram_bot_token' => (string) Env::get('TELEGRAM_BOT_TOKEN', 'your_bot_token_here'),
        'telegram_chat_id' => (string) Env::get('TELEGRAM_CHAT_ID', 'your_chat_id_here'),
        'cloudflare_worker_url' => (string) Env::get('CLOUDFLARE_WORKER_URL', 'https://your-cloudflare-worker-url.com/upload'),
    ],
];
