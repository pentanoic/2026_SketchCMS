<?php

$router->add('/manager', 'managerController@index', 'GET');
$router->add('/manager/settings', 'managerController@settings', 'GET|POST');
$router->add('/manager/smtp', 'managerController@smtp', 'GET|POST');
$router->add('/manager/templates', 'managerController@templates');
$router->add('/manager/templates/set_default', 'managerController@template_set_default', 'POST');
$router->add('/manager/templates/download', 'managerController@template_download', 'POST');
$router->add('/manager/templates/view', 'managerController@template_view');
$router->add('/manager/users', 'managerController@users');
$router->add('/manager/users/ban', 'managerController@user_ban', 'POST');
$router->add('/manager/users/reset_pass', 'managerController@user_reset_pass', 'POST');
$router->add('/manager/users/edit', 'managerController@user_edit', 'POST');
if (module_menu_check('shoutbox')) {
    $router->add('/manager/shoutbox', 'managerController@shoutbox');
    $router->add('/manager/shoutbox/clean', 'managerController@shoutbox_clean', 'POST');
}
if (module_menu_check('articles')) {
    $router->add('/manager/articles', 'managerController@articles');
    $router->add('/manager/articles/cat/add', 'managerController@articles_cat_add', 'POST');
    $router->add('/manager/articles/cat/edit', 'managerController@articles_cat_edit', 'POST');
    $router->add('/manager/articles/cat/delete', 'managerController@articles_cat_delete', 'POST');
    $router->add('/manager/articles/post/delete', 'managerController@articles_post_delete', 'POST');
    $router->add('/manager/articles/chapter/delete', 'managerController@articles_chapter_delete', 'POST');
}
if (module_menu_check('media')) {
    $router->add('/manager/media', 'managerController@media', 'GET|POST');
    $router->add('/manager/media/config', 'managerController@telegramConfig', 'POST');
}
// Static Page Manager
$router->add('/manager/static_pages', 'managerController@static_page');
$router->add('/manager/static_pages/add', 'managerController@static_page_add', 'GET|POST');
$router->add('/manager/static_pages/edit', 'managerController@static_page_edit', 'GET|POST');
$router->add('/manager/static_pages/delete', 'managerController@static_page_delete', 'POST');

// URL Rules
$router->add('/manager/url_rules', 'managerController@url_rules');
$router->add('/manager/url_rules/add', 'managerController@url_rules_add', 'POST');
$router->add('/manager/url_rules/edit', 'managerController@url_rules_edit', 'POST');
$router->add('/manager/url_rules/delete', 'managerController@url_rules_delete', 'POST');

// Modules Manager
$router->add('/manager/modules', 'managerController@modules');
$router->add('/manager/modules/add', 'managerController@module_add', 'POST');
$router->add('/manager/modules/upload', 'managerController@module_upload', 'POST');
$router->add('/manager/modules/edit_info', 'managerController@module_edit_info', 'POST');
$router->add('/manager/modules/delete', 'managerController@module_delete', 'POST');
$router->add('/manager/modules/install', 'managerController@module_install', 'POST');
$router->add('/manager/modules/uninstall', 'managerController@module_uninstall', 'POST');
$router->add('/manager/modules/download', 'managerController@module_download', 'POST');
$router->add('/manager/modules/template', 'managerController@module_template_manager');
$router->add('/manager/modules/template/action', 'managerController@module_template_action', 'POST');
$router->add('/manager/security', 'managerController@security', 'GET|POST');
