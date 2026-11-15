<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Optional global helper functions.
 * Include this file once to get `raw()` as a global shorthand.
 *
 * require __DIR__ . '/src/Support/helpers.php';
 * $db->table('orders')->select(['id', raw('SUM(amount) AS total')])->fetchAll();
 */

use PhpDQuery\Support\RawExpression;

if (!function_exists('raw')) {
    function raw(string $expression): RawExpression
    {
        return RawExpression::of($expression);
    }
}
