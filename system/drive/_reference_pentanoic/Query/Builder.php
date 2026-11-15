<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Fluent Query Builder — SELECT / INSERT / INSERT MANY / UPDATE / DELETE
 *
 * Features on top of standard query builders:
 *  • Column-level AES-256-CBC encryption / decryption (unique to phpDQuery)
 *  • Per-column or per-call encryption key override
 *  • cursor() generator for memory-efficient large result iteration
 *  • paginate() with clone-safe state (no state mutation bug)
 *  • toSql() / getBindings() for debug without executing
 *  • Aggregate shortcuts: count(), sum(), avg(), min(), max()
 *  • value() — fetch a single scalar
 *  • pluck() — fetch a flat array of one column's values
 *  • whereRaw() / havingRaw() — escape hatch for complex expressions
 *  • groupBy() / having()
 *  • where(array) shorthand
 *  • Nested where groups via closure
 *  • insertMany() — single-trip batch insert
 *  • upsert() — INSERT … ON DUPLICATE KEY UPDATE (MySQL) / INSERT OR REPLACE (SQLite)
 *  • insertGetId() — insert and return last insert id
 *  • increment() / decrement()
 *  • exists() / doesntExist()
 *  • alias() — table alias support
 *  • macro() — runtime method extension (Phase 4.1)
 *  • cast() — automatic result type casting (Phase 4.2)
 *  • softDelete() / withTrashed() / onlyTrashed() / restore() (Phase 4.3)
 *  • blindIndex() — searchable encryption (Phase 4.4)
 *  • dd() / dumpSql() / toJson() — DX helpers (Phase 4.5)
 */

namespace PhpDQuery\Query;

use PhpDQuery\Core\Connection;
use PhpDQuery\Core\EventDispatcher;
use PhpDQuery\Driver\DriverInterface;
use PhpDQuery\Security\Crypt;
use PhpDQuery\Support\RawExpression;
use PhpDQuery\Exception\QueryException;

class Builder implements \IteratorAggregate
{
    use BuilderMacros;
    private Connection      $connection;
    private DriverInterface $driver;
    private string          $table;
    private string          $tableAlias = '';

    // Query type
    private string  $type = 'select';

    // SELECT
    private array   $selects  = ['*'];
    private array   $joins    = [];
    private array   $wheres   = [];
    private array   $groups   = [];
    private array   $havings  = [];
    private array   $orderBys = [];
    private ?int    $limit    = null;
    private ?int    $offset   = null;

    // INSERT / UPDATE data
    private array   $data     = [];

    // Bindings accumulator (only used during compile, not stored between calls)
    private array   $bindings = [];

    // Security — column-level encryption
    private array   $encryptColumns = [];  // columns to encrypt on write
    private array   $decryptColumns = [];  // columns to decrypt on read
    private string  $cryptKey       = '';  // per-Builder key override (optional)
    private array   $blindIndexMap  = [];  // ['col' => 'index_col'] pairs

    // Result casting
    private array   $casts          = [];  // ['col' => 'type']

    // Soft delete
    private bool    $softDeleteMode  = false;
    private string  $deletedAtColumn = 'deleted_at';
    private string  $trashMode       = 'without'; // without | with | only

    // DX
    private bool    $dumpMode = false;

    // ─── Constructor ──────────────────────────────────────────────────────────

    public function __construct(Connection $connection, DriverInterface $driver, string $table)
    {
        $this->connection = $connection;
        $this->driver     = $driver;
        $this->table      = $table;
    }

    // ─── Table alias ─────────────────────────────────────────────────────────

    /**
     * Create a raw SQL expression — bypasses escaping.
     * Shorthand for RawExpression::of().
     *
     * Example:
     *   $db->table('orders')->select(['id', Builder::raw('SUM(amount) AS total')])->groupBy('id')->fetchAll();
     *   $db->table('posts')->update(['views' => Builder::raw('views + 1')])->where('id', 5)->run();
     */
    public static function raw(string $expression): RawExpression
    {
        return RawExpression::of($expression);
    }

    /**
     * Set a table alias.
     * Usage: $db->table('users')->alias('u')->select(['u.name'])
     */
    public function alias(string $alias): self
    {
        $this->tableAlias = $alias;
        return $this;
    }

    // ─── SELECT clause ────────────────────────────────────────────────────────

    public function select(array|string $columns = ['*']): self
    {
        $this->type    = 'select';
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function addSelect(array|string $columns): self
    {
        $extra = is_array($columns) ? $columns : func_get_args();
        $this->selects = array_merge(
            $this->selects === ['*'] ? [] : $this->selects,
            $extra
        );
        return $this;
    }

    // ─── WHERE clause ─────────────────────────────────────────────────────────

    /**
     * Add a WHERE condition.
     *
     * Accepts multiple forms:
     *   ->where('status', 1)                    basic equality
     *   ->where('age', 18, '>=')                with operator
     *   ->where(['status' => 1, 'role' => 'admin'])  array shorthand (AND)
     *   ->where(function($q){ $q->where(...) }) nested group
     */
    public function where(string|array|\Closure $column, mixed $value = null, string $operator = '=', string $boolean = 'AND'): self
    {
        // Array shorthand: ->where(['col' => 'val', ...])
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->where($col, $val, '=', $boolean);
            }
            return $this;
        }

        // Nested group: ->where(function($q) { $q->where(...)->orWhere(...) })
        if ($column instanceof \Closure) {
            $nested = new self($this->connection, $this->driver, $this->table);
            $column($nested);
            $this->wheres[] = [
                'type'    => 'nested',
                'query'   => $nested,
                'boolean' => strtoupper($boolean),
            ];
            return $this;
        }

        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => strtoupper($operator),
            'value'    => $value,
            'boolean'  => strtoupper($boolean),
        ];
        return $this;
    }

    public function orWhere(string|array|\Closure $column, mixed $value = null, string $operator = '='): self
    {
        return $this->where($column, $value, $operator, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $this->wheres[] = [
            'type'    => 'in',
            'column'  => $column,
            'values'  => $values,
            'boolean' => strtoupper($boolean),
            'not'     => $not,
        ];
        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR', true);
    }

    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): self
    {
        $this->wheres[] = [
            'type'    => 'null',
            'column'  => $column,
            'boolean' => strtoupper($boolean),
            'not'     => $not,
        ];
        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        return $this->whereNull($column, $boolean, true);
    }

    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'OR');
    }

    public function orWhereNotNull(string $column): self
    {
        return $this->whereNull($column, 'OR', true);
    }

    /**
     * Raw WHERE condition — use when SQL functions are needed.
     * Example: ->whereRaw('YEAR(created_at) = ?', [2024])
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type'     => 'raw',
            'sql'      => $sql,
            'bindings' => $bindings,
            'boolean'  => strtoupper($boolean),
        ];
        return $this;
    }

    public function orWhereRaw(string $sql, array $bindings = []): self
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }

    /**
     * WHERE BETWEEN column AND lower AND upper
     */
    public function whereBetween(string $column, mixed $lower, mixed $upper, string $boolean = 'AND', bool $not = false): self
    {
        $this->wheres[] = [
            'type'    => 'between',
            'column'  => $column,
            'lower'   => $lower,
            'upper'   => $upper,
            'boolean' => strtoupper($boolean),
            'not'     => $not,
        ];
        return $this;
    }

    public function whereNotBetween(string $column, mixed $lower, mixed $upper): self
    {
        return $this->whereBetween($column, $lower, $upper, 'AND', true);
    }

    /**
     * WHERE column LIKE '%value%'
     */
    public function whereLike(string $column, string $value, string $boolean = 'AND', bool $not = false): self
    {
        $operator = $not ? 'NOT LIKE' : 'LIKE';
        return $this->where($column, $value, $operator, $boolean);
    }

    public function whereNotLike(string $column, string $value): self
    {
        return $this->whereLike($column, $value, 'AND', true);
    }

    // ─── JOIN clause ──────────────────────────────────────────────────────────

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type'     => strtoupper($type),
            'table'    => $table,
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
        ];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function crossJoin(string $table): self
    {
        $this->joins[] = ['type' => 'CROSS', 'table' => $table];
        return $this;
    }

    // ─── GROUP BY / HAVING ────────────────────────────────────────────────────

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $col) {
            $this->groups[] = $col;
        }
        return $this;
    }

    public function having(string $column, mixed $value, string $operator = '='): self
    {
        $this->havings[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value,
        ];
        return $this;
    }

    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->havings[] = [
            'type'     => 'raw',
            'sql'      => $sql,
            'bindings' => $bindings,
        ];
        return $this;
    }

    // ─── ORDER BY / LIMIT / OFFSET ────────────────────────────────────────────

    public function sort(string $column, string $direction = 'ASC'): self
    {
        $this->orderBys[] = [
            'column'    => $column,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
        ];
        return $this;
    }


    public function sortRaw(string $sql): self
    {
        $this->orderBys[] = ['raw' => $sql];
        return $this;
    }



    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }



    // ─── WRITE operations ─────────────────────────────────────────────────────

    public function insert(array $data): self
    {
        $this->type = 'insert';
        $this->data = $data;
        return $this;
    }

    /**
     * Insert and return the last inserted ID.
     */
    public function insertGetId(array $data): int|string
    {
        $this->insert($data)->run();
        return $this->connection->getPDO()->lastInsertId();
    }

    /**
     * Batch insert — single SQL trip for multiple rows.
     * Example: ->insertMany([['name'=>'A'],['name'=>'B']])->run()
     */
    public function insertMany(array $rows): self
    {
        $this->type = 'insert_many';
        $this->data = $rows;
        return $this;
    }

    /**
     * INSERT … ON DUPLICATE KEY UPDATE (MySQL) / INSERT OR REPLACE (SQLite).
     * @param array $data         Row data to insert
     * @param array $updateKeys   Columns to update on conflict (MySQL only; SQLite replaces whole row)
     */
    public function upsert(array $data, array $updateKeys = []): self
    {
        $this->type           = 'upsert';
        $this->data           = $data;
        $this->bindings['update_keys'] = $updateKeys;
        return $this;
    }

    public function update(array $data): self
    {
        $this->type = 'update';
        $this->data = $data;
        return $this;
    }

    /**
     * Increment a column by $amount (default 1).
     */
    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $col   = $this->driver->escapeIdentifier($column);
        $extra = array_merge($extra, [$column => $this->connection->getPDO()
            ->quote((string)$amount)]);  // placeholder approach below

        return $this->update(array_merge($extra, ["__raw__{$column}" => "{$col} + {$amount}"]))->run();
    }

    /**
     * Decrement a column by $amount (default 1).
     */
    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        $col = $this->driver->escapeIdentifier($column);
        return $this->update(array_merge($extra, ["__raw__{$column}" => "{$col} - {$amount}"]))->run();
    }

    public function delete(): self
    {
        $this->type = 'delete';
        return $this;
    }

    /**
     * Empty the table — delete ALL rows then reset the auto-increment counter.
     * Unlike delete()->run() (which leaves the counter), this method resets
     * the sequence so the next INSERT gets the specified start ID.
     *
     * Runs atomically inside a transaction.
     *
     * @param int $start  Next INSERT will get this ID (default 1)
     * @param int $step   Increment step: IDs will be start, start+step, start+2*step, …
     *                    Note: step > 1 on SQLite is not natively supported —
     *                    the value is recorded for documentation but has no effect
     *                    on SQLite's ROWID algorithm. Use MySQL for reliable step support.
     *
     * Example:
     *   $db->table('logs')->empty()->run();              // reset to 1, step 1
     *   $db->table('orders')->empty(1000)->run();        // reset to 1000
     *   $db->table('invoices')->empty(1, 5)->run();      // IDs: 1, 6, 11, … (MySQL)
     */
    public function empty(int $start = 1, int $step = 1): self
    {
        $this->type = 'empty';
        $this->data = ['__start__' => $start, '__step__' => $step];
        return $this;
    }

    // ─── Encryption / Decryption ──────────────────────────────────────────────

    /**
     * Columns to encrypt on INSERT / UPDATE.
     * @param array  $columns  e.g. ['email', 'phone']
     * @param string $key      Optional per-Builder key (overrides global Crypt key)
     */
    public function encrypt(array $columns, string $key = ''): self
    {
        $this->encryptColumns = $columns;
        if ($key !== '') {
            $this->cryptKey = $key;
        }
        return $this;
    }

    /**
     * Columns to decrypt on SELECT results.
     * @param array  $columns
     * @param string $key     Optional per-Builder key
     */
    public function decrypt(array $columns, string $key = ''): self
    {
        $this->decryptColumns = $columns;
        if ($key !== '') {
            $this->cryptKey = $key;
        }
        return $this;
    }

    /**
     * Set a per-Builder encryption key (for multi-tenant scenarios with different keys).
     */
    public function withKey(string $key): self
    {
        $this->cryptKey = $key;
        return $this;
    }

    // ─── Result Casting ───────────────────────────────────────────────────────

    /**
     * Define automatic type casts applied after fetch.
     *
     * @param array<string,string> $casts  e.g. ['id'=>'int','meta'=>'json','active'=>'bool']
     * Supported: int, float, bool, string, json, array, object, datetime, date, timestamp, base64
     */
    public function cast(array $casts): self
    {
        $this->casts = $casts;
        return $this;
    }

    // ─── Soft Delete ──────────────────────────────────────────────────────────

    /**
     * Enable soft-delete mode for this query.
     * DELETE becomes UPDATE … SET deleted_at = NOW() (or current timestamp).
     * SELECT automatically excludes soft-deleted rows (unless withTrashed/onlyTrashed).
     *
     * @param string $column  The timestamp column (default: 'deleted_at')
     */
    public function softDelete(string $column = 'deleted_at'): self
    {
        $this->softDeleteMode  = true;
        $this->deletedAtColumn = $column;
        $this->trashMode       = 'without'; // default: exclude deleted
        return $this;
    }

    /** Include soft-deleted rows in SELECT results. */
    public function withTrashed(): self
    {
        $this->softDeleteMode = true;
        $this->trashMode      = 'with';
        return $this;
    }

    /** Return ONLY soft-deleted rows. */
    public function onlyTrashed(): self
    {
        $this->softDeleteMode = true;
        $this->trashMode      = 'only';
        return $this;
    }

    /** Exclude soft-deleted rows (default behaviour). */
    public function withoutTrashed(): self
    {
        $this->softDeleteMode = true;
        $this->trashMode      = 'without';
        return $this;
    }

    /**
     * Restore soft-deleted rows: UPDATE … SET deleted_at = NULL WHERE …
     */
    public function restore(): self
    {
        $this->type           = 'restore';
        $this->softDeleteMode = true;
        return $this;
    }

    // ─── Blind Index (Searchable Encryption) ──────────────────────────────────

    /**
     * Map encrypted columns to their blind-index columns.
     * On INSERT/UPDATE: automatically computes and writes the index column.
     * On SELECT: lets you search with Crypt::blindIndex() on the index column.
     *
     * @param array<string,string> $map  e.g. ['email' => 'email_index']
     */
    public function blindIndex(array $map): self
    {
        $this->blindIndexMap = $map;
        return $this;
    }

    // ─── DX Helpers ───────────────────────────────────────────────────────────

    /**
     * Dump result and exit — for debugging.
     */
    public function dd(): never
    {
        $result = $this->fetchAll();
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        exit(0);
    }

    /**
     * Dump compiled SQL (with bindings interpolated) and exit.
     */
    public function dumpSql(): never
    {
        echo $this->toDebugSql() . PHP_EOL;
        exit(0);
    }

    /**
     * Fetch all rows and return as JSON string.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->fetchAll(), $flags);
    }


    /**
     * Fetch all rows and return a Collection.
     */
    public function collect(): \PhpDQuery\Support\Collection
    {
        return \PhpDQuery\Support\Collection::make($this->fetchAll());
    }

    // ─── Fetch methods ────────────────────────────────────────────────────────

    public function fetchAll(): array
    {
        $this->type = 'select';
        [$sql, $bindings] = $this->compile();

        // before:select — listener may return cached rows to skip DB
        $cached = EventDispatcher::dispatch('before:select', $sql, $bindings);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $stmt = $this->executeStatement($sql, $bindings);
        $rows = $this->applyPostProcess($stmt->fetchAll());
        EventDispatcher::dispatch('after:select', $this->table, $rows);
        return $rows;
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->fetchAll();
        return $results[0] ?? null;
    }

    /**
     * Fetch a single column value from the first row.
     * Example: $name = $db->table('users')->where('id', 1)->value('name');
     */
    public function value(string $column): mixed
    {
        $row = $this->select([$column])->first();
        return $row[$column] ?? null;
    }

    /**
     * Fetch a flat array of values from a single column.
     * Example: $ids = $db->table('users')->pluck('id');  // [1, 2, 3]
     *          $map = $db->table('users')->pluck('name', 'id');  // [1=>'Alice', 2=>'Bob']
     */
    public function pluck(string $column, ?string $keyColumn = null): array
    {
        $cols = $keyColumn ? [$column, $keyColumn] : [$column];
        $rows = $this->select($cols)->fetchAll();

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
     * Memory-efficient generator — yields one row at a time.
     * Use for large result sets to avoid loading everything into RAM.
     */
    public function cursor(): \Generator
    {
        $this->type = 'select';
        [$sql, $bindings] = $this->compile();
        $stmt     = $this->executeStatement($sql, $bindings);
        $cryptKey = $this->cryptKey;
        $decCols  = $this->decryptColumns;
        $caster   = !empty($this->casts) ? new Caster($this->casts) : null;

        while ($row = $stmt->fetch()) {
            foreach ($decCols as $col) {
                if (isset($row[$col]) && $row[$col] !== null) {
                    $row[$col] = Crypt::decrypt((string)$row[$col], $cryptKey);
                }
            }
            if ($caster !== null) {
                $row = $caster->castRow($row);
            }
            yield $row;
        }
    }

    // ─── Aggregates ───────────────────────────────────────────────────────────

    public function count(string $column = '*'): int
    {
        $expr = $column === '*' ? 'COUNT(*)' : 'COUNT(' . $this->driver->escapeIdentifier($column) . ')';
        $clone = $this->cloneForAggregate([$expr]);
        [$sql, $bindings] = $clone->compileSelect();
        $stmt = $this->executeStatement($sql, $bindings);
        return (int) $stmt->fetchColumn();
    }

    public function sum(string $column): int|float
    {
        return $this->aggregate('SUM', $column) ?? 0;
    }

    public function avg(string $column): int|float|null
    {
        return $this->aggregate('AVG', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    private function aggregate(string $fn, string $column): mixed
    {
        $expr  = "{$fn}(" . $this->driver->escapeIdentifier($column) . ")";
        $clone = $this->cloneForAggregate([$expr]);
        [$sql, $bindings] = $clone->compileSelect();
        $stmt = $this->executeStatement($sql, $bindings);
        $val  = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    private function cloneForAggregate(array $selects): self
    {
        $clone = clone $this;
        $clone->selects  = $selects;
        $clone->limit    = null;
        $clone->offset   = null;
        $clone->orderBys = [];
        return $clone;
    }

    // ─── Existence checks ─────────────────────────────────────────────────────

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    // ─── Pagination ───────────────────────────────────────────────────────────

    /**
     * Paginate results — uses clone to avoid mutating Builder state.
     * Returns: ['data'=>[], 'total'=>int, 'page'=>int, 'perPage'=>int, 'lastPage'=>int]
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $total   = $this->count();

        $data = (clone $this)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->fetchAll();

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => (int) ceil($total / $perPage),
            'from'     => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
            'to'       => min($page * $perPage, $total),
        ];
    }

    // ─── Execution ────────────────────────────────────────────────────────────

    /**
     * Execute INSERT / UPDATE / DELETE / RESTORE / EMPTY — returns affected row count.
     */
    public function run(): int
    {
        // 'empty' cần multi-statement trong transaction — xử lý riêng
        if ($this->type === 'empty') {
            return $this->runEmpty();
        }

        // Fire before events
        match ($this->type) {
            'insert', 'insert_many', 'upsert'
                => EventDispatcher::dispatch('before:insert', $this->table, $this->data),
            'update'
                => EventDispatcher::dispatch('before:update', $this->table, $this->data, $this->wheres),
            'delete'
                => EventDispatcher::dispatch('before:delete', $this->table, $this->wheres),
            default => null,
        };

        [$sql, $bindings] = $this->compile();
        $stmt             = $this->executeStatement($sql, $bindings);
        $affected         = $stmt->rowCount();

        // Fire after events
        match ($this->type) {
            'insert', 'insert_many', 'upsert'
                => EventDispatcher::dispatch('after:insert', $this->table, $this->data,
                    $this->connection->getPDO()->lastInsertId()),
            'update'
                => EventDispatcher::dispatch('after:update', $this->table, $affected),
            'delete'
                => EventDispatcher::dispatch('after:delete', $this->table, $affected),
            default => null,
        };

        return $affected;
    }

    /**
     * Execute empty() — DELETE all + reset auto-increment, atomically.
     */
    private function runEmpty(): int
    {
        $start = (int)($this->data['__start__'] ?? 1);
        $step  = (int)($this->data['__step__']  ?? 1);
        $pdo   = $this->connection->getPDO();
        $t     = $this->driver->escapeIdentifier($this->table);
        $isMySQL = !($this->driver instanceof \PhpDQuery\Driver\SQLiteDriver);

        $inTx = $pdo->inTransaction();
        if (!$inTx) $pdo->beginTransaction();

        try {
            // 1. DELETE all rows (no WHERE — intentional)
            $deleteStart = microtime(true);
            $stmt = $pdo->prepare("DELETE FROM {$t}");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $this->connection->logQuery("DELETE FROM {$t}", [], microtime(true) - $deleteStart);

            // 2. Reset auto-increment sequence
            $this->resetAutoIncrement($pdo, $t, $start, $step, $isMySQL);

            if (!$inTx) $pdo->commit();

            EventDispatcher::dispatch('after:empty', $this->table, $affected, $start, $step);
            return $affected;

        } catch (\Throwable $e) {
            if (!$inTx) $pdo->rollBack();
            throw new QueryException(
                "empty() failed on table '{$this->table}': " . $e->getMessage(),
                "DELETE FROM {$t} + reset sequence",
                [],
                $e
            );
        }
    }

    /**
     * Reset the auto-increment counter for a table.
     * Handles MySQL and SQLite differently.
     */
    private function resetAutoIncrement(
        \PDO   $pdo,
        string $escapedTable,
        int    $start,
        int    $step,
        bool   $isMySQL
    ): void {
        $t = $escapedTable;

        if ($isMySQL) {
            // MySQL: ALTER TABLE … AUTO_INCREMENT = N resets the counter.
            // TRUNCATE would also work but we already did DELETE to respect WHEREless delete events.
            $sql = "ALTER TABLE {$t} AUTO_INCREMENT = {$start}";
            $s = microtime(true);
            $pdo->exec($sql);
            $this->connection->logQuery($sql, [], microtime(true) - $s);

            if ($step > 1) {
                // Step per-table: MySQL không hỗ trợ native per-table step.
                // SET SESSION auto_increment_increment ảnh hưởng session hiện tại — an toàn.
                $sql2 = "SET SESSION auto_increment_increment = {$step}";
                $s2   = microtime(true);
                $pdo->exec($sql2);
                $this->connection->logQuery($sql2, [], microtime(true) - $s2);
            }
            return;
        }

        // SQLite: dùng sqlite_sequence để kiểm soát ROWID tiếp theo.
        // sqlite_sequence chỉ tồn tại khi bảng có AUTOINCREMENT keyword.
        // Nếu bảng dùng INTEGER PRIMARY KEY (không có AUTOINCREMENT), SQLite
        // tự tính max(rowid)+1 — ta cần insert 1 row "mồi" để thiết lập giá trị.

        $quotedName = $pdo->quote($this->table);

        // Kiểm tra sqlite_sequence có entry cho bảng này không
        $check = $pdo->query("SELECT COUNT(*) FROM sqlite_sequence WHERE name = {$quotedName}");
        $hasSeq = $check && (int)$check->fetchColumn() > 0;

        $seqVal = $start - 1; // sqlite_sequence.seq = last used ID = start - 1

        if ($hasSeq) {
            // Bảng có AUTOINCREMENT — cập nhật sqlite_sequence trực tiếp
            $sql = "UPDATE sqlite_sequence SET seq = {$seqVal} WHERE name = {$quotedName}";
            $s   = microtime(true);
            $pdo->exec($sql);
            $this->connection->logQuery($sql, [], microtime(true) - $s);
        } else {
            // Chưa có entry trong sqlite_sequence → prime trước rồi UPDATE
            $this->primeSQLiteSequence($pdo, $t, $quotedName);

            // Sau khi prime, set seq về giá trị mong muốn
            try {
                $sql = "UPDATE sqlite_sequence SET seq = {$seqVal} WHERE name = {$quotedName}";
                $s   = microtime(true);
                $pdo->exec($sql);
                $this->connection->logQuery($sql, [], microtime(true) - $s);
            } catch (\PDOException) {
                // sqlite_sequence vẫn không tồn tại — bảng không dùng AUTOINCREMENT keyword
                // (dùng INTEGER PRIMARY KEY không có AUTOINCREMENT).
                // Fallback: insert ghost row với ROWID = seqVal rồi xóa.
                if ($seqVal >= 1) {
                    try {
                        $pdo->exec("INSERT INTO {$t} (rowid) VALUES ({$seqVal})");
                        $pdo->exec("DELETE FROM {$t} WHERE rowid = {$seqVal}");
                    } catch (\PDOException) {
                        // Không thể reset — bảng có NOT NULL trên các cột khác.
                        // Silent fail: sequence sẽ bắt đầu từ 1.
                    }
                }
            }
        }

        // SQLite step: không hỗ trợ native. Ghi log warning nếu step > 1.
        if ($step > 1) {
            // Không thể set step trên SQLite. Bỏ qua — caller nên biết điều này.
            // Xem docblock của empty() để biết giới hạn.
        }
    }

    /**
     * Ensure sqlite_sequence has an entry for $table.
     * sqlite_sequence is only created after the first INSERT into an AUTOINCREMENT table.
     * For tables that have never had a row, we must prime the sequence by doing
     * a real INSERT (which creates the entry) then immediately DELETE it.
     */
    private function primeSQLiteSequence(\PDO $pdo, string $escapedTable, string $quotedName): void
    {
        try {
            $check = $pdo->query("SELECT COUNT(*) FROM sqlite_sequence WHERE name = {$quotedName}");
            if ($check && (int)$check->fetchColumn() > 0) {
                return;
            }
        } catch (\PDOException) {
            return;
        }

        // Insert a temporary row to force SQLite to create the sqlite_sequence entry.
        // We use rowid=1 as a safe minimal value — the caller will UPDATE seq afterwards.
        try {
            $pdo->exec("INSERT INTO {$escapedTable} (rowid) VALUES (1)");
            $pdo->exec("DELETE FROM {$escapedTable} WHERE rowid = 1");
        } catch (\PDOException) {
            // Table has NOT NULL constraints — bare insert not possible.
        }
    }

    /**
     * compile() placeholder cho 'empty' — không dùng trực tiếp (runEmpty() xử lý).
     * Chỉ cần để compile dispatch không throw.
     */
    private function compileEmpty(): array
    {
        return ["-- empty handled by runEmpty()", []];
    }

    // ─── Debug / Introspection ────────────────────────────────────────────────

    /**
     * Return the compiled SQL without executing.
     */
    public function toSql(): string
    {
        [$sql,] = $this->compile();
        return $sql;
    }

    /**
     * Return the bound values without executing.
     */
    public function getBindings(): array
    {
        [, $bindings] = $this->compile();
        return $bindings;
    }

    /**
     * Return SQL with bindings interpolated — for display/debug only.
     */
    public function toDebugSql(): string
    {
        $sql      = $this->toSql();
        $bindings = $this->getBindings();

        foreach ($bindings as $value) {
            $replacement = is_null($value)
                ? 'NULL'
                : (is_string($value) ? "'" . addslashes($value) . "'" : (string)$value);

            $pos = strpos($sql, '?');
            if ($pos !== false) {
                $sql = substr_replace($sql, $replacement, $pos, 1);
            }
        }

        return $sql;
    }

    // ─── IteratorAggregate ────────────────────────────────────────────────────

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fetchAll());
    }

    // ─── Compile ──────────────────────────────────────────────────────────────

    private function compile(): array
    {
        return match ($this->type) {
            'select'      => $this->compileSelect(),
            'insert'      => $this->compileInsert(),
            'insert_many' => $this->compileInsertMany(),
            'upsert'      => $this->compileUpsert(),
            'update'      => $this->compileUpdate(),
            'delete'      => $this->softDeleteMode ? $this->compileSoftDelete() : $this->compileDelete(),
            'restore'     => $this->compileRestore(),
            'empty'       => $this->compileEmpty(),
            default       => throw new QueryException("Unknown query type: {$this->type}"),
        };
    }

    public function compileSelect(): array
    {
        $bindings = [];
        $table    = $this->tableRef();

        // SELECT
        $cols = [];
        foreach ($this->selects as $col) {
            $cols[] = $this->compileColumn($col);
        }
        $sql = 'SELECT ' . implode(', ', $cols) . " FROM {$table}";

        // JOIN
        foreach ($this->joins as $join) {
            if ($join['type'] === 'CROSS') {
                $sql .= ' CROSS JOIN ' . $this->driver->escapeIdentifier($join['table']);
            } else {
                $jt = $this->driver->escapeIdentifier($join['table']);
                $j1 = $this->compileColumn($join['first']);
                $j2 = $this->compileColumn($join['second']);
                $sql .= " {$join['type']} JOIN {$jt} ON {$j1} {$join['operator']} {$j2}";
            }
        }

        // Soft-delete automatic WHERE injection
        $wheres = $this->wheres;
        if ($this->softDeleteMode) {
            $col = $this->deletedAtColumn;
            if ($this->trashMode === 'without') {
                $wheres[] = ['type' => 'null', 'column' => $col, 'boolean' => 'AND', 'not' => false];
            } elseif ($this->trashMode === 'only') {
                $wheres[] = ['type' => 'null', 'column' => $col, 'boolean' => 'AND', 'not' => true];
            }
            // 'with' → no extra WHERE
        }

        // WHERE
        [$whereSql, $whereBindings] = $this->compileWheres($wheres);
        $sql      .= $whereSql;
        $bindings  = array_merge($bindings, $whereBindings);

        // GROUP BY
        if (!empty($this->groups)) {
            $groupCols = array_map(fn($g) => $this->driver->escapeIdentifier($g), $this->groups);
            $sql .= ' GROUP BY ' . implode(', ', $groupCols);
        }

        // HAVING
        if (!empty($this->havings)) {
            [$havingSql, $havingBindings] = $this->compileHavings();
            $sql      .= $havingSql;
            $bindings  = array_merge($bindings, $havingBindings);
        }

        // ORDER BY
        if (!empty($this->orderBys)) {
            $orders = [];
            foreach ($this->orderBys as $order) {
                if (isset($order['raw'])) {
                    $orders[] = $order['raw'];
                } else {
                    $orders[] = $this->driver->escapeIdentifier($order['column']) . ' ' . $order['direction'];
                }
            }
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int)$this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . (int)$this->offset;
        }

        return [$sql, $bindings];
    }

    private function compileInsert(): array
    {
        $table    = $this->driver->escapeIdentifier($this->table);
        $cols     = [];
        $ph       = [];
        $bindings = [];

        // Columns that will be auto-filled by the blind index loop — skip from data loop
        $blindIndexCols = array_values($this->blindIndexMap);

        foreach ($this->data as $col => $val) {
            // Skip blind-index columns — they are computed below
            if (in_array($col, $blindIndexCols)) {
                continue;
            }

            $cols[] = $this->driver->escapeIdentifier($col);
            if ($val instanceof RawExpression) {
                $ph[] = $val->getValue();
            } else {
                $ph[] = '?';
                if (in_array($col, $this->encryptColumns)) {
                    $val = Crypt::encrypt((string)$val, $this->cryptKey);
                }
                $bindings[] = $val;
            }
        }

        // Blind index auto-fill — use the plain (pre-encrypt) value from data
        foreach ($this->blindIndexMap as $srcCol => $idxCol) {
            if (array_key_exists($srcCol, $this->data)) {
                $plain  = $this->data[$srcCol]; // plain value before encryption
                $idxVal = Crypt::blindIndex((string)$plain, $this->cryptKey);
                $cols[] = $this->driver->escapeIdentifier($idxCol);
                $ph[]   = '?';
                $bindings[] = $idxVal;
            }
        }

        $sql = "INSERT INTO {$table} (" . implode(', ', $cols) . ') VALUES (' . implode(', ', $ph) . ')';
        return [$sql, $bindings];
    }

    private function compileInsertMany(): array
    {
        if (empty($this->data)) {
            throw new QueryException('insertMany() requires at least one row.');
        }

        $table = $this->driver->escapeIdentifier($this->table);

        // Collect the union of all column names across every row
        $allCols = [];
        foreach ($this->data as $row) {
            foreach (array_keys($row) as $col) {
                $allCols[$col] = true;
            }
        }
        $allCols = array_keys($allCols);
        $escapedCols = array_map(fn($c) => $this->driver->escapeIdentifier($c), $allCols);

        $rowPlaceholders = [];
        $bindings        = [];

        foreach ($this->data as $row) {
            $ph = [];
            foreach ($allCols as $col) {
                $val = $row[$col] ?? null;  // fill missing columns with NULL
                if (in_array($col, $this->encryptColumns) && $val !== null) {
                    $val = Crypt::encrypt((string)$val, $this->cryptKey);
                }
                $ph[]       = '?';
                $bindings[] = $val;
            }
            $rowPlaceholders[] = '(' . implode(', ', $ph) . ')';
        }

        $sql = "INSERT INTO {$table} (" . implode(', ', $escapedCols) . ') VALUES ' . implode(', ', $rowPlaceholders);
        return [$sql, $bindings];
    }

    private function compileUpsert(): array
    {
        [$insertSql, $bindings] = $this->compileInsert();
        $updateKeys = $this->bindings['update_keys'] ?? [];

        $driverName = get_class($this->driver);

        if (str_contains($driverName, 'MySQL') && !empty($updateKeys)) {
            $updates = [];
            foreach ($updateKeys as $col) {
                $escaped  = $this->driver->escapeIdentifier($col);
                $updates[] = "{$escaped} = VALUES({$escaped})";
            }
            $sql = $insertSql . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
        } else {
            // SQLite: INSERT OR REPLACE
            $sql = str_replace('INSERT INTO', 'INSERT OR REPLACE INTO', $insertSql);
        }

        return [$sql, $bindings];
    }

    private function compileUpdate(): array
    {
        $table          = $this->driver->escapeIdentifier($this->table);
        $setParts       = [];
        $bindings       = [];
        $blindIndexCols = array_values($this->blindIndexMap);

        foreach ($this->data as $col => $val) {
            if (in_array($col, $blindIndexCols)) continue; // auto-filled below
            if (str_starts_with($col, '__raw__')) {
                $realCol    = $this->driver->escapeIdentifier(substr($col, 7));
                $setParts[] = "{$realCol} = {$val}";
            } elseif ($val instanceof RawExpression) {
                $setParts[] = $this->driver->escapeIdentifier($col) . ' = ' . $val->getValue();
            } else {
                if (in_array($col, $this->encryptColumns)) {
                    $val = Crypt::encrypt((string)$val, $this->cryptKey);
                }
                $setParts[] = $this->driver->escapeIdentifier($col) . ' = ?';
                $bindings[] = $val;
            }
        }

        // Blind index auto-update — use plain value from data (before encrypt)
        foreach ($this->blindIndexMap as $srcCol => $idxCol) {
            if (array_key_exists($srcCol, $this->data)) {
                $plain      = $this->data[$srcCol];
                $idxVal     = Crypt::blindIndex((string)$plain, $this->cryptKey);
                $setParts[] = $this->driver->escapeIdentifier($idxCol) . ' = ?';
                $bindings[] = $idxVal;
            }
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts);
        [$whereSql, $whereBindings] = $this->compileWheres($this->wheres);
        $sql      .= $whereSql;
        $bindings  = array_merge($bindings, $whereBindings);

        return [$sql, $bindings];
    }

    private function compileDelete(): array
    {
        $table = $this->driver->escapeIdentifier($this->table);
        $sql   = "DELETE FROM {$table}";
        [$whereSql, $bindings] = $this->compileWheres($this->wheres);
        $sql .= $whereSql;
        return [$sql, $bindings];
    }

    // ─── Soft Delete compilers ────────────────────────────────────────────────

    private function compileSoftDelete(): array
    {
        $table    = $this->driver->escapeIdentifier($this->table);
        $col      = $this->driver->escapeIdentifier($this->deletedAtColumn);

        // Use database-agnostic current timestamp
        $now      = date('Y-m-d H:i:s');
        $bindings = [$now];

        $sql = "UPDATE {$table} SET {$col} = ?";
        [$whereSql, $whereBindings] = $this->compileWheres($this->wheres);
        $sql      .= $whereSql;
        $bindings  = array_merge($bindings, $whereBindings);
        return [$sql, $bindings];
    }

    private function compileRestore(): array
    {
        $table    = $this->driver->escapeIdentifier($this->table);
        $col      = $this->driver->escapeIdentifier($this->deletedAtColumn);
        $sql      = "UPDATE {$table} SET {$col} = NULL";
        [$whereSql, $bindings] = $this->compileWheres($this->wheres);
        $sql .= $whereSql;
        return [$sql, $bindings];
    }

    // ─── WHERE compiler ───────────────────────────────────────────────────────

    private function compileWheres(array $wheres): array
    {
        if (empty($wheres)) {
            return ['', []];
        }

        $parts    = [];
        $bindings = [];

        foreach ($wheres as $i => $where) {
            $prefix = $i > 0 ? " {$where['boolean']} " : '';

            switch ($where['type']) {
                case 'basic':
                    $col      = $this->compileColumn($where['column']);
                    if ($where['value'] instanceof RawExpression) {
                        $parts[]  = $prefix . "{$col} {$where['operator']} " . $where['value']->getValue();
                    } else {
                        $parts[]  = $prefix . "{$col} {$where['operator']} ?";
                        $bindings[] = $where['value'];
                    }
                    break;

                case 'in':
                    $col  = $this->compileColumn($where['column']);
                    $op   = $where['not'] ? 'NOT IN' : 'IN';
                    $ph   = implode(', ', array_fill(0, count($where['values']), '?'));
                    $parts[]  = $prefix . "{$col} {$op} ({$ph})";
                    $bindings = array_merge($bindings, $where['values']);
                    break;

                case 'null':
                    $col     = $this->compileColumn($where['column']);
                    $op      = $where['not'] ? 'IS NOT NULL' : 'IS NULL';
                    $parts[] = $prefix . "{$col} {$op}";
                    break;

                case 'raw':
                    $parts[]  = $prefix . $where['sql'];
                    $bindings = array_merge($bindings, $where['bindings']);
                    break;

                case 'between':
                    $col     = $this->compileColumn($where['column']);
                    $op      = $where['not'] ? 'NOT BETWEEN' : 'BETWEEN';
                    $parts[] = $prefix . "{$col} {$op} ? AND ?";
                    $bindings[] = $where['lower'];
                    $bindings[] = $where['upper'];
                    break;

                case 'nested':
                    [$nestedSql, $nestedBindings] = $this->compileWheres($where['query']->wheres);
                    if ($nestedSql !== '') {
                        // Strip leading " WHERE "
                        $inner    = ltrim(substr($nestedSql, 7));
                        $parts[]  = $prefix . "({$inner})";
                        $bindings = array_merge($bindings, $nestedBindings);
                    }
                    break;
            }
        }

        return [' WHERE ' . implode('', $parts), $bindings];
    }

    private function compileHavings(): array
    {
        $parts    = [];
        $bindings = [];

        foreach ($this->havings as $having) {
            if ($having['type'] === 'raw') {
                $parts[]  = $having['sql'];
                $bindings = array_merge($bindings, $having['bindings']);
            } else {
                $col      = $this->driver->escapeIdentifier($having['column']);
                $parts[]  = "{$col} {$having['operator']} ?";
                $bindings[] = $having['value'];
            }
        }

        return [' HAVING ' . implode(' AND ', $parts), $bindings];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function tableRef(): string
    {
        $escaped = $this->driver->escapeIdentifier($this->table);
        return $this->tableAlias !== ''
            ? $escaped . ' AS ' . $this->driver->escapeIdentifier($this->tableAlias)
            : $escaped;
    }

    /**
     * Escape a column name — handles:
     *  • RawExpression  → passed through verbatim
     *  • '*'            → *
     *  • 'table.col'    → `table`.`col`
     *  • 'col AS alias' → `col` AS `alias`
     *  • 'COUNT(*)'     → passed through as-is (raw expression via parens)
     */
    private function compileColumn(string|RawExpression $col): string
    {
        if ($col instanceof RawExpression) {
            return $col->getValue();
        }

        $col = trim($col);

        // Raw expressions (contain parens or spaces = function/alias)
        if (str_contains($col, '(') || str_contains($col, ' ')) {
            return $col;
        }

        // col AS alias
        if (stripos($col, ' as ') !== false) {
            [$expr, $alias] = preg_split('/\s+as\s+/i', $col, 2);
            return $this->driver->escapeIdentifier(trim($expr))
                . ' AS '
                . $this->driver->escapeIdentifier(trim($alias));
        }

        return $this->driver->escapeIdentifier($col);
    }

    private function executeStatement(string $sql, array $bindings): \PDOStatement
    {
        $start = microtime(true);
        try {
            $stmt = $this->connection->getPDO()->prepare($sql);
            $stmt->execute($bindings);
            $elapsed = microtime(true) - $start;
            $this->connection->logQuery($sql, $bindings, $elapsed);
            EventDispatcher::dispatchSlowQuery($sql, $elapsed * 1000, $bindings);
            return $stmt;
        } catch (\PDOException $e) {
            $ex = new QueryException($e->getMessage(), $sql, $bindings, $e);
            EventDispatcher::dispatch('query:error', $ex, $sql, $bindings);
            throw $ex;
        }
    }

    private function applyPostProcess(array $results): array
    {
        if (empty($results)) {
            return $results;
        }

        // 1. Decrypt
        if (!empty($this->decryptColumns)) {
            $key = $this->cryptKey;
            foreach ($results as &$row) {
                foreach ($this->decryptColumns as $col) {
                    if (isset($row[$col]) && $row[$col] !== null) {
                        $row[$col] = Crypt::decrypt((string)$row[$col], $key);
                    }
                }
            }
            unset($row);
        }

        // 2. Cast
        if (!empty($this->casts)) {
            $results = (new Caster($this->casts))->castAll($results);
        }

        return $results;
    }
}
