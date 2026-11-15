<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Collection — immutable, chainable wrapper around a rows array.
 *
 * Returned by Builder::collect() and Result::collect().
 * All transformation methods return NEW Collection instances (immutable).
 *
 * Example:
 *   $col = $db->table('users')->where('status',1)->collect();
 *
 *   $col->filter(fn($u) => $u['score'] > 80)
 *       ->map(fn($u) => array_merge($u, ['label' => strtoupper($u['name'])]))
 *       ->sortBy('score', 'DESC')
 *       ->take(3)
 *       ->pluck('label')
 *       ->each(fn($l) => print($l . "\n"));
 */

namespace PhpDQuery\Support;

class Collection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    private array $items;

    public function __construct(array $items = [], private bool $preserveKeys = false)
    {
        $this->items = $preserveKeys ? $items : array_values($items);
    }

    // ─── Static constructors ──────────────────────────────────────────────────

    public static function make(array $items): self
    {
        return new self($items);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    // ─── Transformations (return new Collection) ──────────────────────────────

    /**
     * Filter rows by a callback. Keeps rows where callback returns truthy.
     */
    public function filter(callable $fn): self
    {
        return new self(array_values(array_filter($this->items, $fn)));
    }

    /**
     * Reject (inverse filter) — keeps rows where callback returns falsy.
     */
    public function reject(callable $fn): self
    {
        return $this->filter(fn($item) => !$fn($item));
    }

    /**
     * Transform each row via callback.
     */
    public function map(callable $fn): self
    {
        return new self(array_map($fn, $this->items));
    }

    /**
     * Sort by a column name or callback.
     * @param string|callable $key        Column name or comparator function
     * @param string          $direction  'ASC' or 'DESC' (used only when $key is a string)
     */
    public function sortBy(string|callable $key, string $direction = 'ASC'): self
    {
        $items = $this->items;

        if (is_callable($key)) {
            usort($items, $key);
        } else {
            $desc = strtoupper($direction) === 'DESC';
            usort($items, function($a, $b) use ($key, $desc) {
                $va = $a[$key] ?? null;
                $vb = $b[$key] ?? null;
                $cmp = $va <=> $vb;
                return $desc ? -$cmp : $cmp;
            });
        }

        return new self($items);
    }

    /**
     * Sort by a callback that returns a comparable value.
     */
    public function sortByDesc(string $key): self
    {
        return $this->sortBy($key, 'DESC');
    }

    /**
     * Take the first N items.
     */
    public function take(int $n): self
    {
        return new self(array_slice($this->items, 0, $n));
    }

    /**
     * Skip the first N items.
     */
    public function skip(int $n): self
    {
        return new self(array_slice($this->items, $n));
    }

    /**
     * Return a new Collection with only unique rows (by column name or identity).
     */
    public function unique(?string $column = null): self
    {
        if ($column === null) {
            return new self(array_unique($this->items, SORT_REGULAR));
        }

        $seen  = [];
        $items = [];
        foreach ($this->items as $item) {
            $val = $item[$column] ?? null;
            if (!in_array($val, $seen, true)) {
                $seen[]  = $val;
                $items[] = $item;
            }
        }
        return new self($items);
    }

    /**
     * Extract a flat array of values from a single column.
     * Optionally key the result by another column.
     */
    public function pluck(string $column, ?string $keyColumn = null): self
    {
        if ($keyColumn !== null) {
            $result = [];
            foreach ($this->items as $item) {
                $result[$item[$keyColumn]] = $item[$column];
            }
            return new self($result, true);
        }

        return new self(array_column($this->items, $column));
    }

    /**
     * Group rows by a column value. Returns Collection of arrays keyed by group value.
     */
    public function groupBy(string $column): self
    {
        $groups = [];
        foreach ($this->items as $item) {
            $key = $item[$column] ?? '';
            $groups[$key][] = $item;
        }
        return new self($groups, true);
    }

    /**
     * Key the collection by a column value — like pluck but keeps entire row.
     */
    public function keyBy(string $column): self
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[$item[$column]] = $item;
        }
        return new self($result, true);
    }

    /**
     * Return only specified columns from each row.
     */
    public function only(array $columns): self
    {
        return $this->map(fn($row) => array_intersect_key($row, array_flip($columns)));
    }

    /**
     * Remove specified columns from each row.
     */
    public function except(array $columns): self
    {
        return $this->map(fn($row) => array_diff_key($row, array_flip($columns)));
    }

    /**
     * Merge additional rows or another Collection.
     */
    public function merge(array|self $other): self
    {
        $extra = $other instanceof self ? $other->all() : $other;
        return new self(array_merge($this->items, $extra));
    }

    /**
     * Split collection into chunks of given size.
     * Returns a Collection of arrays.
     */
    public function chunk(int $size): self
    {
        return new self(array_chunk($this->items, $size));
    }

    /**
     * Flatten one level (useful after chunk or groupBy).
     */
    public function flatten(): self
    {
        $flat = [];
        foreach ($this->items as $item) {
            if (is_array($item)) {
                array_push($flat, ...$item);
            } else {
                $flat[] = $item;
            }
        }
        return new self($flat);
    }

    /**
     * Reverse the collection.
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    /**
     * Zip two collections together — pairs items at same index.
     */
    public function zip(array|self $other): self
    {
        $otherArr = $other instanceof self ? $other->all() : $other;
        return new self(array_map(null, $this->items, $otherArr));
    }

    // ─── Aggregates ───────────────────────────────────────────────────────────

    public function sum(string $column): int|float
    {
        return array_sum(array_column($this->items, $column));
    }

    public function avg(string $column): float|null
    {
        return $this->count() > 0 ? $this->sum($column) / $this->count() : null;
    }

    public function min(string $column): mixed
    {
        $vals = array_column($this->items, $column);
        return empty($vals) ? null : min($vals);
    }

    public function max(string $column): mixed
    {
        $vals = array_column($this->items, $column);
        return empty($vals) ? null : max($vals);
    }

    // ─── Terminals (do not return Collection) ────────────────────────────────

    /**
     * Iterate each item — returns self for use after a terminal inspection.
     */
    public function each(callable $fn): self
    {
        foreach ($this->items as $key => $item) {
            if ($fn($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function first(?callable $fn = null): mixed
    {
        if ($fn === null) {
            return $this->items[0] ?? null;
        }
        foreach ($this->items as $item) {
            if ($fn($item)) {
                return $item;
            }
        }
        return null;
    }

    public function last(?callable $fn = null): mixed
    {
        if ($fn === null) {
            return !empty($this->items) ? end($this->items) : null;
        }
        foreach (array_reverse($this->items) as $item) {
            if ($fn($item)) {
                return $item;
            }
        }
        return null;
    }

    public function contains(callable|string $keyOrFn, mixed $value = null): bool
    {
        if (is_callable($keyOrFn)) {
            foreach ($this->items as $item) {
                if ($keyOrFn($item)) return true;
            }
            return false;
        }
        foreach ($this->items as $item) {
            if (($item[$keyOrFn] ?? null) === $value) return true;
        }
        return false;
    }

    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $fn, $initial);
    }

    // ─── Access ───────────────────────────────────────────────────────────────

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->items, $flags);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    // ─── Interfaces ───────────────────────────────────────────────────────────

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
