<?php

$router->add('/', 'homeController@index', 'GET');
$router->add('/home', 'homeController@index', 'GET');
$router->add('/index.html', 'homeController@index', 'GET');
$router->add('/articles', 'homeController@index', 'GET');
$router->add('/{:slug}.html', 'homeController@staticPage', 'GET');