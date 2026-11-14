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

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(__FILE__)) . DS);
define('APP', ROOT . 'app' . DS);
define('SYSTEM', ROOT . 'system' . DS);
define('TEMPLATES', ROOT . 'templates' . DS);
define('TIME', time());

// Security Headers & CSP with Cryptographic Nonce
if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', base64_encode(random_bytes(16)));
}
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self' data: https:; script-src 'self' 'nonce-" . CSP_NONCE . "' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https: http:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self'; base-uri 'self'; object-src 'none';");

if (version_compare(PHP_VERSION, '8.0', '<')) {
    // If the version below 8.0, we stops the script and displays an error
    die('<div style="text-align: center; font-size: xx-large">'
        . '<h3 style="color: #dd0000">ERROR: outdated version of PHP</h3>'
        . 'Your needs PHP 8.0 or higher'
        . '</div>');
}

// Global config
require(SYSTEM . 'configs' . DS . 'init.php');

// Autoload
require(SYSTEM . 'autoload.php');
require(APP . 'vendor' . DS . 'autoload.php');

// global function
require(SYSTEM . 'functions.php');

// Register Centralized Security & Exception Handler
if (class_exists('Error')) {
    Error::register();
}

// Register Services
$config = Container::get(Config::class);
$services = $config->get('services');

foreach ($services as $service) {
    Container::get($service)->register();
}