<?php
/**
 * RateLimitMiddleware - Global POST rate limiting (Anti-Spam/DDoS)
 */

class RateLimitMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        if ($request->getMethod() === 'POST') {
            $route = $request->getRoute();
            $routeWithSlash = $route === '/' ? '/' : '/' . $route;
            $isApiRoute = strpos($routeWithSlash, '/api/') === 0;
            $isManager = strpos($routeWithSlash, '/manager') === 0;
            $rate_limit_seconds = (int) config('SECURITY_RATE_LIMIT', 1, true);

            if (!$isManager && $rate_limit_seconds > 0 && !checkRateLimit('global_post', $rate_limit_seconds)) {
                if ($isApiRoute || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(429);
                    echo json_encode(['status' => 'error', 'message' => 'Thao tác quá nhanh. Vui lòng chờ ' . $rate_limit_seconds . ' giây!'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $_SESSION['global_toast_error'] = 'Rate Limit Exceeded: Bạn thao tác quá nhanh, vui lòng chờ ít nhất ' . $rate_limit_seconds . ' giây trước khi thử lại.';
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: $referer");
                exit;
            }
        }

        return $next($request);
    }
}
