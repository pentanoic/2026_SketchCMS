<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * RawExpression — wraps a raw SQL fragment so the Builder treats it
 * as a literal string instead of escaping it.
 *
 * Usage in Builder:
 *   use PhpDQuery\Support\RawExpression as Raw;
 *
 *   // In select columns
 *   $db->table('orders')->select([
 *       'id',
 *       Raw::of('SUM(amount) AS total'),
 *       Raw::of('UPPER(name) AS name_upper'),
 *   ])->groupBy('id')->fetchAll();
 *
 *   // In where values
 *   $db->table('users')
 *      ->where('updated_at', Raw::of('created_at'), '>')
 *      ->fetchAll();
 *
 *   // In insert/update data
 *   $db->table('posts')->update([
 *       'views'      => Raw::of('views + 1'),
 *       'updated_at' => Raw::of('CURRENT_TIMESTAMP'),
 *   ])->where('id', 5)->run();
 *
 * Shorthand global function (if you load helpers.php):
 *   raw('SUM(amount) AS total')
 */

namespace PhpDQuery\Support;

class RawExpression implements \Stringable
{
    public function __construct(private readonly string $expression) {}

    /** Main factory — cleaner than `new RawExpression(...)` */
    public static function of(string $expression): self
    {
        return new self($expression);
    }

    public function getValue(): string
    {
        return $this->expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
