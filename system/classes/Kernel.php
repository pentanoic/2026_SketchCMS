<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 * Email: kioku17@protonmail.com
 * GitHub: https://github.com/pentanoic/
 * Website: https://lab302.ovh/
 *
 * Copyright (c) 2026 valedrat. All rights reserved.
 *
 * This file is part of the SketchCMS source code.
 * Please do not remove or modify this copyright notice.
 */


class Kernel
{
    /**
     * Global HTTP middleware pipeline
     *
     * @var array<int, class-string<MiddlewareInterface>>
     */
    protected array $middleware = [
        UrlDisableMiddleware::class,
        CsrfMiddleware::class,
        RateLimitMiddleware::class,
    ];

    public function run(Request $request)
    {
        $this->removeHeaders();

        /** @var Router */
        $router = Container::get(Router::class);

        $router->setAllowedMethods($request->getAllowedMethods());
        $router->setBasePath((string) Env::get('SITE_PATH', ''));

        (new Pipeline())
            ->send($request)
            ->through($this->middleware)
            ->then(function (Request $req) use ($router) {
                $this->matchRoute($req, $router);
            });

        die('ERROR: 404 Not Found!');
    }

    protected function matchRoute(Request $request, Router $router)
    {
        $route = $request->getRoute();
        $routeWithSlash = $route === '/' ? '/' : '/' . $route;

        // Kiểm tra URL Rewrite
        $rewrites = Env::getJson('URL_RULES_REWRITE', []);
        $callable = null;
        $matchedTarget = null;
        
        if (isset($rewrites[$routeWithSlash])) {
            $matchedTarget = $rewrites[$routeWithSlash];
        } elseif (isset($rewrites[$route])) {
            $matchedTarget = $rewrites[$route];
        }

        if ($matchedTarget) {
            $allowedMethods = explode('|', $matchedTarget['method'] ?? 'GET|POST');
            $currentMethod = $request->getMethod();
            if (!in_array($currentMethod, $allowedMethods)) {
                $this->show404();
                return;
            }
            $controller = $matchedTarget['controller'] ?? '';
            $method = $matchedTarget['action'] ?? '';
            $params = $matchedTarget['params'] ?? [];
            
            // Nếu target là một chuỗi như 'home/faq/terms' được gán bởi managerController
            if (isset($matchedTarget['target']) && empty($controller)) {
                $parts = explode('/', trim($matchedTarget['target'], '/'));
                $controller = (isset($parts[0]) && $parts[0] !== '') ? $parts[0] . 'Controller' : 'homeController';
                $method = (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : 'index';
                $params = array_slice($parts, 2);
            }

            $callable = [
                'callback' => ['controller' => $controller, 'method' => $method],
                'params' => $params
            ];
        } else {
            $router->match($request->getMethod(), $route);
            $callable = $router->getRequestParams();
        }

        if ($callable) {
            $callback = $callable['callback'];
            $result = null;

            if (is_array($callback)) {
                $controllerObj = Container::get($callback['controller']);

                if ($controllerObj) {
                    if (method_exists($controllerObj, $callback['method'])) {
                        $result = call_user_func_array([$controllerObj, $callback['method']], $callable['params']);
                    }
                }
            } else {
                $result = call_user_func($callback, ...$callable['params']);
            }

            if ($result) {
                if (is_array($result)) {
                    header('Content-Type: application/json');
                    echo json_encode($result);
                } elseif ($result instanceof GdImage) {
                    disable_output_compression();
                    header('Content-Type: image/png');
                    imagepng($result);
                    imagedestroy($result);
                } else {
                    $timeLoad = round(microtime(true) - _MVC_START, 4);
                    header('X-Time-Load: ' . $timeLoad . 's');
                    header('X-Time-Render: ' . $timeLoad . 's');
                    echo $result;
                }

                exit;
            }
        }
        $this->show404();
    }

    protected function show404()
    {
        /** @var Template */
        $view = view();

        if ($view->getEngine()->exists('404')) {
            $view->setTitle('404 Not Found');
            header('HTTP/1.1 404 Not Found', true, 404);
            $view->output('404');

            exit;
        }
    }

    protected function removeHeaders()
    {
        foreach (headers_list() as $header) {
            if (strpos(strtolower($header), 'x-powered-by:') !== false) {
                header_remove('x-powered-by');
            }
        }
    }
}