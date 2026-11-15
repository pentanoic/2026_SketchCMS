<?php

$pathRegister = 'userController@register';
// Chặn IP ngoài vùng Đông Nam Á (chỉ kiểm tra khi truy cập /register và là IP công cộng)
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($reqUri, '/register') === 0) {
    $clientIp = request()->getIp();
    if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true
            ]
        ]);
        $url = "https://ipinfo.io/" . rawurlencode($clientIp) . "/json";
        $response = @file_get_contents($url, false, $ctx);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['country'])) {
                $southeastAsiaCountries = ['ID', 'MY', 'SG', 'TH', 'PH', 'BN', 'KH', 'LA', 'MM', 'TL', 'VN'];
                if (!in_array($data['country'], $southeastAsiaCountries, true)) {
                    $pathRegister = 'userController@login';
                }
            }
        }
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
