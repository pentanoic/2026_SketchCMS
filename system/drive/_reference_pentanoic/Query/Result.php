<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Wraps a raw PDOStatement returned by Database::query() (raw SQL execution).
 * Builder::fetchAll() / first() return plain arrays — this class is only used
 * when calling $db->query('SELECT ...') directly.
 */

namespace PhpDQuery\Query;

use PDOStatement;
use PhpDQuery\Query\Caster;

class Result implements \IteratorAggregate, \Countable
{
    private PDOStatement $statement;
    private ?array       $cache = null;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    // ─── Fetch ────────────────────────────────────────────────────────────────

    public function fetchAll(): array
    {
        return $this->cache ??= $this->statement->fetchAll();
    }

    public function fetch(): array|false
    {
        return $this->statement->fetch();
    }

    public function first(): ?array
    {
        $rows = $this->fetchAll();
        return $rows[0] ?? null;
    }

    /**
     * Fetch a flat array of values from one column.
     * @param string      $column    Column name to extract
     * @param string|null $keyColumn Optional column to use as array key
     */
    public function pluck(string $column, ?string $keyColumn = null): array
    {
        $rows = $this->fetchAll();
        if ($keyColumn !== null) {
            $result = [];
            foreach ($rows as $row) {
                $result[$row[$keyColumn]] = $row[$column];
            }
            return $result;
        }
        return array_column($rows, $column);
    }

    /**
     * Fetch a single scalar value (top-left cell).
     */
    public function value(string $column): mixed
    {
        $row = $this->first();
        return $row[$column] ?? null;
    }

    /**
     * Chunk results and pass each chunk to a callback.
     * Useful when iterating a very large in-memory result from raw SQL.
     */
    public function chunk(int $size, callable $callback): void
    {
        $all = $this->fetchAll();
        foreach (array_chunk($all, $size) as $chunk) {
            if ($callback($chunk) === false) {
                break;
            }
        }
    }

    // ─── Countable / Iterable ─────────────────────────────────────────────────

    public function count(): int
    {
        return count($this->fetchAll());
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fetchAll());
    }

    // ─── DX Helpers ───────────────────────────────────────────────────────────

    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->fetchAll(), $flags);
    }


    /**
     * Fetch all rows and wrap in a Collection.
     */
    public function collect(): \PhpDQuery\Support\Collection
    {
        return \PhpDQuery\Support\Collection::make($this->fetchAll());
    }

    public function dd(): never
    {
        echo '<pre>';
        print_r($this->fetchAll());
        echo '</pre>';
        exit(0);
    }

    /**
     * Apply type casts to all rows.
     * @param array<string,string> $casts
     */
    public function cast(array $casts): array
    {
        return (new Caster($casts))->castAll($this->fetchAll());
    }

    // ─── Metadata ─────────────────────────────────────────────────────────────

    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * Return column names of the result set.
     */
    public function columns(): array
    {
        $cols = [];
        for ($i = 0; $i < $this->columnCount(); $i++) {
            $meta   = $this->statement->getColumnMeta($i);
            $cols[] = $meta['name'] ?? "column_{$i}";
        }
        return $cols;
    }
}
