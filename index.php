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

define('_MVC_START', microtime(true));

if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: /install/');
    exit;
}

require('./system/bootstrap.php');

/** @var Kernel */
$kernel = Container::get(Kernel::class);

$kernel->run(Container::get(Request::class));
