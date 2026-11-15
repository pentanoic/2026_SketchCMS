<?php
/**
 * CsrfMiddleware - Global CSRF protection for POST requests
 */

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        if ($request->getMethod() === 'POST') {
            $route = $request->getRoute();
            $routeWithSlash = $route === '/' ? '/' : '/' . $route;
            $isApiRoute = strpos($routeWithSlash, '/api/') === 0;

            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            $csrfHeader = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            $csrfTokenInput = $_POST['csrf_token'] ?? $csrfHeader;

            $hasBearerAuth = $isApiRoute && preg_match('/Bearer\s+\S+/i', $authHeader);
            $isPublicAuthApi = $isApiRoute && in_array($routeWithSlash, [
                '/api/v1/auth/login',
                '/api/v1/auth/register',
                '/api/v1/auth/password/forgot',
                '/api/v1/auth/password/reset'
            ], true);

            if (!$hasBearerAuth && !$isPublicAuthApi) {
                if (isCSRFTokenValid($csrfTokenInput) === 'error') {
                    if ($isApiRoute || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                        header('Content-Type: application/json; charset=utf-8');
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'CSRF Token không hợp lệ. Vui lòng tải lại trang!'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    die('CSRF Token Error: Hành động không hợp lệ hoặc đã hết hạn. Vui lòng quay lại và tải lại trang.');
                }
            }
        }

        return $next($request);
    }
}
