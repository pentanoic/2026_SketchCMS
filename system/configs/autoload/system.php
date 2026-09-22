<?php
return array (
  'app' => 
  array (
    'name' => 'SketchCMS',
    'description' => 'SketchCMS - Mã nguồn quản trị nội dung, mã nguồn mở miễn phí dựa trên mô hình HMVC',
    'keyword' => 'open source code, cms, content, php, mysql, sketchcms',
    'site' => 
    array (
      'scheme' => 'http://',
      'host' => 'sketchcms.test',
      'path' => '',
    ),
    'default_template' => 'default',
    'installed_modules' => 
    array (
      0 => 'custom_headless',
    ),
  ),
  'PostList' => 
  array (
    'order_by' => 
    array (
      0 => 'id',
      1 => 'time',
      2 => 'update_time',
      3 => 'view',
    ),
    'sort' => 
    array (
      0 => 'asc',
      1 => 'desc',
    ),
  ),
  'UserList' => 
  array (
    'order_by' => 
    array (
      0 => 'level',
      1 => 'xu',
      2 => 'post',
    ),
    'sort' => 
    array (
      0 => 'asc',
      1 => 'desc',
    ),
  ),
  'Manager' => 
  array (
    'allow_country' => 
    array (
      0 => 'ID',
      1 => 'MY',
      2 => 'SG',
      3 => 'TH',
      4 => 'PH',
      5 => 'BN',
      6 => 'KH',
      7 => 'LA',
      8 => 'MM',
      9 => 'TL',
      10 => 'VN',
    ),
    'assets' => 'https://cdn.jsdelivr.net/gh/jesuisnk2/someTPL/sneat-free',
  ),
  'url_rules' => 
  array (
    'rewrite' => 
    array (
      '/faq/terms' => 
      array (
        'target' => '/home/faq/terms',
        'method' => 'GET|POST',
      ),
      '/faq/about' => 
      array (
        'target' => '/home/faq/about',
        'method' => 'GET|POST',
      ),
      '/faq/help' => 
      array (
        'target' => '/home/faq/help',
        'method' => 'GET|POST',
      ),
    ),
    'disable' => 
    array (
      0 => '/register',
    ),
  ),
  'security' => 
  array (
    'rate_limit' => 2,
  ),
  'smtp' => 
  array (
    'host' => 'smtp.gmail.com',
    'port' => 465,
    'user' => 'sketchcms@lab302.ovh',
    'pass' => '123123123',
    'encrypt' => 'ssl',
    'from_email' => 'sketchcms@lab302.ovh',
    'from_name' => 'SketchCMS',
  ),
  'media' => 
  array (
    'telegram_upload_mode' => 'direct',
    'telegram_bot_token' => 'your_bot_token_here',
    'telegram_chat_id' => 'your_chat_id_here',
    'cloudflare_worker_url' => 'https://your-cloudflare-worker-url.com/upload',
  ),
);
