<?php

$router->add('/shoutbox', 'shoutboxController@ChatHome', 'GET');
$router->add('/shoutbox/count', 'shoutboxController@ChatCount', 'GET');
$router->add('/shoutbox/ele', 'shoutboxController@ChatEle', 'GET');
$router->add('/shoutbox/list', 'shoutboxController@ChatList', 'GET');
$router->add('/shoutbox/send', 'shoutboxController@ChatSend', 'GET|POST');
