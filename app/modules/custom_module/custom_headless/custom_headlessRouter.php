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

/**
 * Headless API Router
 */

// Trang tài liệu API
$router->add('/api/docs', 'custom_headlessController@docs', 'GET');

// Các route dành cho API Headless
$router->add('/api/v1/ping', 'custom_headlessController@ping', 'GET');

// API Search
$router->add('/api/v1/search', 'custom_headlessController@searchArticles', 'GET');

// API Articles
$router->add('/api/v1/articles', 'custom_headlessController@getArticles', 'GET');
$router->add('/api/v1/articles', 'custom_headlessController@createArticle', 'POST');
$router->add('/api/v1/articles/([0-9]+)', 'custom_headlessController@getArticle', 'GET');
$router->add('/api/v1/articles/([0-9]+)/edit', 'custom_headlessController@editArticle', 'POST');
$router->add('/api/v1/articles/([0-9]+)/delete', 'custom_headlessController@deleteArticle', 'POST');
$router->add('/api/v1/articles/([0-9]+)/chapters', 'custom_headlessController@getArticleChapters', 'GET');
$router->add('/api/v1/articles/([0-9]+)/chapters', 'custom_headlessController@createChapter', 'POST');
$router->add('/api/v1/articles/([0-9]+)/comments', 'custom_headlessController@createComment', 'POST');
$router->add('/api/v1/chapters/([0-9]+)', 'custom_headlessController@getChapter', 'GET');

$router->add('/api/v1/forums', 'custom_headlessController@getForums', 'GET');
$router->add('/api/v1/forums/([0-9]+)/articles', 'custom_headlessController@getForumArticles', 'GET');

// API Users & Auth
$router->add('/api/v1/auth/login', 'custom_headlessController@login', 'POST');
$router->add('/api/v1/auth/register', 'custom_headlessController@register', 'POST');
$router->add('/api/v1/auth/password/forgot', 'custom_headlessController@forgotPassword', 'POST');
$router->add('/api/v1/auth/password/reset', 'custom_headlessController@resetPassword', 'POST');

$router->add('/api/v1/users', 'custom_headlessController@getUsers', 'GET');
$router->add('/api/v1/users/online', 'custom_headlessController@getUsersOnline', 'GET');
$router->add('/api/v1/users/([a-zA-Z0-9_]+)', 'custom_headlessController@getUser', 'GET');
$router->add('/api/v1/users/me/update', 'custom_headlessController@updateMe', 'POST');

// API Mail
$router->add('/api/v1/mail/conversations', 'custom_headlessController@getMailConversations', 'GET');
$router->add('/api/v1/mail/conversations/([a-zA-Z0-9_]+)', 'custom_headlessController@getMailDetail', 'GET');
$router->add('/api/v1/mail/send', 'custom_headlessController@sendMail', 'POST');
$router->add('/api/v1/mail/system', 'custom_headlessController@getSystemMail', 'GET');

// API Manager (Admin)
$router->add('/api/v1/admin/stats', 'custom_headlessController@getAdminStats', 'GET');
$router->add('/api/v1/admin/users', 'custom_headlessController@getAdminUsers', 'GET');
$router->add('/api/v1/admin/users/([0-9]+)/ban', 'custom_headlessController@toggleBanUser', 'POST');
$router->add('/api/v1/admin/files', 'custom_headlessController@getAdminFiles', 'GET');

// API Media
$router->add('/api/v1/media/library', 'custom_headlessController@getMediaLibrary', 'GET');
$router->add('/api/v1/media/upload', 'custom_headlessController@uploadMedia', 'POST');

// API Shoutbox
$router->add('/api/v1/shoutbox', 'custom_headlessController@getShoutbox', 'GET');
$router->add('/api/v1/shoutbox/count', 'custom_headlessController@getShoutboxCount', 'GET');
$router->add('/api/v1/shoutbox/([0-9]+)', 'custom_headlessController@getShoutboxEle', 'GET');
$router->add('/api/v1/shoutbox/send', 'custom_headlessController@sendShoutbox', 'POST');

// API Docs
$router->add('/api/docs', 'custom_headlessController@docs', 'GET');
