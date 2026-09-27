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

$sysConfig = require __DIR__ . '/autoload/system.php';

// URL
define('SITE_SCHEME', $sysConfig['app']['site']['scheme'] ?? 'http://');
define('SITE_HOST', $sysConfig['app']['site']['host'] ?? 'localhost');
define('SITE_PATH', $sysConfig['app']['site']['path'] ?? '');
define('SITE_URL', SITE_SCHEME . SITE_HOST . SITE_PATH);
// Cookie
define('COOKIE_PATH', '/' . SITE_PATH);
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sketchcms');
define('UserBot', 'SystemBOT');
define('NewsID', '1');

// Security
define('SECOND_PASSWORD', '244466666');

// Time zone
date_default_timezone_set('Asia/Ho_Chi_Minh');

ini_set('session.use_trans_sid', '0');
ini_set('arg_separator.output', '&amp;');
mb_internal_encoding('UTF-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

if (extension_loaded('zlib')) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', 3);
}

// E2E Secret Key
define('E2E_SECRET_KEY', 'SketchCMS_E2E_localhost');

// Debug Mode (true = dev, false = active)
define('APP_DEBUG', true);
