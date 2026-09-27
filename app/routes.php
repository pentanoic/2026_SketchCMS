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


/** @var Router */
$router = Container::get(Router::class);

// trang chủ
// Di chuyển về app/modules/home/homeRouter.php

// plugin
$router->add('/plugin/{plugin:.*}', function ($plugin) {
    if (file_exists(TEMPLATES . get_template() . '/plugin/' . $plugin . '.php')) {
        $template = 'plugin/' . $plugin;
    } else {
        $template = '404';
    }
    return view($template);
}, 'GET|POST');
# seo
$router->add('/robots.txt', function () {
    return view('articles/_seo.robots');
});
$router->add('/sitemap.xml', 'articlesController@Sitemap', 'GET');


// hồ sơ người dùng
// Di chuyển về app/modules/user/userRouter.php

// shoutbox
// Di chuyển về app/modules/shoutbox/shoutboxRouter.php

// tin nhắn
// Di chuyển về app/modules/mail/mailRouter.php

// diễn đàn
// Di chuyển về app/modules/articles/articlesRouter.php

// Manager
// Di chuyển về app/modules/manager/managerRouter.php

// Media
// Di chuyển về app/modules/media/mediaRouter.php

// --- AUTOLOAD MODULE ROUTERS ---
// Quét và nạp tất cả các file Router.php của các module (bao gồm cả core và custom)
$allRouters = glob(APP . 'modules/*/*Router.php');
if ($allRouters) {
    foreach ($allRouters as $rPath) {
        $modulePath = basename(dirname($rPath));
        // Kiểm tra nếu module đang được bật thì mới load route
        if (module_menu_check($modulePath)) {
            require_once $rPath;
        }
    }
}

$customRouters = glob(APP . 'modules/custom_module/custom_*/*Router.php');
if ($customRouters) {
    foreach ($customRouters as $cr) {
        $modulePath = 'custom_module/' . basename(dirname($cr));
        if (module_menu_check($modulePath)) {
            require_once $cr;
        }
    }
}
