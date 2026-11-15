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

// Time zone
date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));

ini_set('session.use_trans_sid', '0');
ini_set('arg_separator.output', '&amp;');
mb_internal_encoding('UTF-8');

if ((bool) Env::get('APP_DEBUG', true)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(-1);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

if (extension_loaded('zlib') && !in_array('ob_gzhandler', ob_list_handlers(), true)) {
    $zlibCurrent = strtolower((string) ini_get('zlib.output_compression'));
    if ($zlibCurrent !== '1' && $zlibCurrent !== 'on') {
        ini_set('zlib.output_compression', 'On');
    }
    ini_set('zlib.output_compression_level', 3);
}
