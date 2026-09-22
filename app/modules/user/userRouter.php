<?php

$pathRegister = 'userController@register';
// Chặn IP ngoài vùng Đông Nam Á
$url = "https://ipinfo.io/" . (request()->getIp()) . "/json";
$response = @file_get_contents($url);
$data = json_decode($response, true);
if (isset($data['country'])) {
    $southeastAsiaCountries = ['ID', 'MY', 'SG', 'TH', 'PH', 'BN', 'KH', 'LA', 'MM', 'TL', 'VN'];
    if (!in_array($data['country'], $southeastAsiaCountries)) {
        $pathRegister = 'userController@login';
    }
}
$router->add('/register', $pathRegister, 'GET|POST');
$router->add('/login', 'userController@login', 'GET|POST');
$router->add('/logout', 'userController@logout', 'GET|POST');
$router->add('/login/password/forgot', 'userController@forgot_password', 'GET|POST');
$router->add('/login/password/reset', 'userController@reset_password', 'GET|POST');
# danh sách hồ sơ
$router->add('/users', 'userController@UserList', 'GET|POST');
# thông tin hồ sơ
$router->add('/user{UriAccount:[a-zA-Z0-9\-_\\/]*}', 'userController@UserDetail', 'GET|POST');
