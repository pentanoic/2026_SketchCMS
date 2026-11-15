<?php

return function (TestRunner $t, string $root): void {
    // Load core models and verify their primary read methods execute without SQL errors
    $models = [
        'homeModel' => $root . 'app/modules/home/homeModel.php',
        'userModel' => $root . 'app/modules/user/userModel.php',
        'articlesModel' => $root . 'app/modules/articles/articlesModel.php',
        'managerModel' => $root . 'app/modules/manager/managerModel.php',
        'mailModel' => $root . 'app/modules/mail/mailModel.php',
        'shoutboxModel' => $root . 'app/modules/shoutbox/shoutboxModel.php',
        'mediaModel' => $root . 'app/modules/media/mediaModel.php',
    ];

    foreach ($models as $name => $path) {
        if (file_exists($path)) {
            require_once $path;
        }
        $t->assertTrue(class_exists($name), "Class {$name} loaded");
    }

    $user = new userModel();
    $t->assertIsArray($user->UserList(5, 'level', 'desc', 0), 'userModel::UserList() returns array');
    $t->assertIsArray($user->UserListOnline(0, 5), 'userModel::UserListOnline() returns array');

    $articles = new articlesModel();
    $t->assertIsArray($articles->CategoryList(5), 'articlesModel::CategoryList() returns array');
    $t->assertIsArray($articles->PostList(0, 5), 'articlesModel::PostList() returns array');
    $t->assertIsArray($articles->PostListSticked(5), 'articlesModel::PostListSticked() returns array');
    $t->assertIsArray($articles->ChapterList(0), 'articlesModel::ChapterList() returns array');
    $t->assertIsArray($articles->FileList(5, 0), 'articlesModel::FileList() returns array');

    $manager = new managerModel();
    $t->assertIsArray($manager->GetUsers(0, 5, '', 'id'), 'managerModel::GetUsers() returns array');
    $t->assertIsArray($manager->GetFilesList(0, 5), 'managerModel::GetFilesList() returns array');
    $t->assertIsArray($manager->GetArticlesPosts(0, 5, ''), 'managerModel::GetArticlesPosts() returns array');
    $t->assertIsArray($manager->GetArticlesChapters(0, 5, ''), 'managerModel::GetArticlesChapters() returns array');
};
