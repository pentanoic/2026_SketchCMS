<?php

# tìm kiếm
$router->add('/search', 'articlesController@Search', 'GET');
# quản lý chuyên mục
$router->add('/articles/category', 'articlesController@CategoryPublish', 'GET|POST');
# viết bài nhanh
$router->add('/articles/post', 'articlesController@PostPublish', 'GET|POST');
# thao tác quản lý dữ liệu đối với bài viết
$router->add('/articles/{CategorySlug:[a-zA-Z0-9\-_]+}/post', 'articlesController@PostPublishOne', 'GET|POST'); # viết bài
$router->add('/articles/post-{:id}/upload', 'articlesController@FileUpload', 'GET|POST'); # upload
$router->add('/articles/post-{:id}/add-chap', 'articlesController@ChapterPublish', 'GET|POST'); # thêm chương
$router->add('/articles/{action:(post|chapter)}-{:id}/edit', 'articlesController@ForumEdit', 'GET|POST'); # sửa bài, chương
$router->add('/articles/{action:(post|chapter)}-{:id}/delete', 'articlesController@ForumDelete', 'GET|POST'); # xóa bài, chương
# thông tin bài viết
$router->add('/articles/{PostSlug:[a-zA-Z0-9\-_]+}', 'articlesController@PostDetail', 'GET|POST');
# thông tin tag
$router->add('/tag/{slug:[a-zA-Z0-9\-_]+}', 'articlesController@TagDetail', 'GET');
$router->add('/articles/{PostSlug:[a-zA-Z0-9\-_]+}.html', 'articlesController@PostDetail', 'GET|POST');
# thông tin chương
$router->add('/view-chap/{ChapterSlug:[a-zA-Z0-9\-_]+}', 'articlesController@ChapterDetail', 'GET');
$router->add('/view-chap/{ChapterSlug:[a-zA-Z0-9\-_]+}.html', 'articlesController@ChapterDetail', 'GET');
# thông tin file
$router->add('/view-file/{:id}', 'articlesController@FileDetail', 'GET|POST');
