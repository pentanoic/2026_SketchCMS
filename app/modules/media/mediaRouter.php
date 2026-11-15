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

// Module specific routes can be added here
$router->add('/media/upload', 'mediaController@upload', 'POST');
$router->add('/media/download/{file_id}/{file_name}', 'mediaController@telegramDownload', 'GET');
$router->add('/media/library', 'mediaController@library', 'GET');
