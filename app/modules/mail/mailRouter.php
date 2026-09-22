<?php

$router->add('/mail', 'mailController@MailList', 'GET');
$router->add('/mail/send/{uri_receiver:[a-zA-Z0-9\-_]+}', 'mailController@MailSend', 'GET|POST');
# tin nhắn hệ thống
$router->add('/mail/system', 'mailController@MailSystem', 'GET|POST');
