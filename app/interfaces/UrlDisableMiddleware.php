<?php
/**
 * UrlDisableMiddleware - Block disabled routes early
 */

class UrlDisableMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        $route = $request->getRoute();
        $routeWithSlash = $route === '/' ? '/' : '/' . $route;
        $disabled = Env::getList('URL_RULES_DISABLE', []);

        if (in_array($routeWithSlash, $disabled, true) || in_array($route, $disabled, true)) {
            $view = view();
            if ($view->getEngine()->exists('404')) {
                $view->setTitle('404 Not Found');
                header('HTTP/1.1 404 Not Found', true, 404);
                $view->output('404');
                exit;
            }
            header('HTTP/1.1 404 Not Found', true, 404);
            exit('ERROR: 404 Not Found!');
        }

        return $next($request);
    }
}
