<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 *
 * MiddlewareInterface for Kernel HTTP Pipeline
 */

interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, callable $next);
}
