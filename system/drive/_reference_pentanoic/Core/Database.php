<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Main entry point.  Instantiate once, reuse everywhere.
 *
 * Usage:
 *   $db = new Database(['driver'=>'mysql', 'host'=>'...', 'database'=>'...', ...]);
 *   $db->table('users')->select()->where('id', 1)->first();
 */

namespace PhpDQuery\Core;

use PDO;
use PhpDQuery\Driver\MySQLDriver;
use PhpDQuery\Driver\SQLiteDriver;
use PhpDQuery\Core\EventDispatcher;
use PhpDQuery\Query\Builder;
use PhpDQuery\Query\Result;
use PhpDQuery\Query\SchemaBuilder;
use PhpDQuery\Exception\QueryException;

class Database
{
    private Connection                      $connection;
    private \PhpDQuery\Driver\DriverInterface $driver;

    public function __construct(array $config)
    {
        $cfg = new Config($config);

        $this->driver = $cfg->getDriver() === 'mysql'
            ? new MySQLDriver()
            : new SQLiteDriver();

        $this->connection = new Connection($cfg, $this->driver);
    }

    // ─── Query Builder entry point ────────────────────────────────────────────

    /**
     * Start a query on a table.
     */
    public function table(string $name): Builder
    {
        return new Builder($this->connection, $this->driver, $name);
    }



    // ─── Schema Builder entry point ───────────────────────────────────────────

    public function create(): SchemaBuilder
    {
        return new SchemaBuilder($this->connection, $this->driver, 'create');
    }

    public function drop(): SchemaBuilder
    {
        return new SchemaBuilder($this->connection, $this->driver, 'drop');
    }

    /**
     * Truncate a table — removes all rows and resets auto-increment.
     * By default resets counter to 1, step 1.
     *
     * Usage:
     *   $db->truncate()->table('users')->run();
     *   $db->truncate()->table('users')->resetIncrement(1000)->run();
     *   $db->truncate()->table('users')->resetIncrement(1, 5)->run();
     *   $db->truncate()->table('users')->keepIncrement()->run();
     */
    public function truncate(): SchemaBuilder
    {
        return new SchemaBuilder($this->connection, $this->driver, 'truncate');
    }

    /**
     * Shorthand to empty a table and reset auto-increment in one call.
     * Uses Builder::empty() which wraps DELETE + sequence reset in a transaction.
     *
     * Difference from truncate():
     *   - truncate() uses DDL (TRUNCATE TABLE / DELETE + sequence)
     *   - emptyTable() uses DML (DELETE FROM via Builder) — fires before/after:delete events,
     *     respects soft-delete setup, and works inside existing transactions.
     *
     * @param int $start  Next INSERT ID (default 1)
     * @param int $step   Increment step (default 1; MySQL only for step > 1)
     *
     * Example:
     *   $db->emptyTable('logs');               // reset to 1
     *   $db->emptyTable('orders', 1000);       // reset to 1000
     *   $db->emptyTable('invoices', 1, 10);    // step 10 (MySQL)
     */
    public function emptyTable(string $table, int $start = 1, int $step = 1): int
    {
        return $this->table($table)->empty($start, $step)->run();
    }


    /**
     * Get a SchemaInspector for introspecting database structure.
     * $db->inspect()->tables();
     * $db->inspect()->columns('users');
     */
    public function inspect(): \PhpDQuery\Schema\SchemaInspector
    {
        return new \PhpDQuery\Schema\SchemaInspector($this->connection, $this->driver);
    }

    /**
     * Get a DatabaseDumper for exporting the database as SQL.
     * $db->dumper()->dump();
     * $db->dumper()->tables(['users'])->toFile('/backup/db.sql');
     */
    public function dumper(): \PhpDQuery\Dev\DatabaseDumper
    {
        return new \PhpDQuery\Dev\DatabaseDumper($this->connection, $this->driver);
    }

    /**
     * Get a QueryProfiler wrapping the current query log.
     * Call $db->enableQueryLog() first.
     *
     * echo $db->profiler()->render();          // HTML panel
     * echo $db->profiler()->slowOnly(50)->render();
     * echo $db->profiler()->toJson();
     */
    public function profiler(): \PhpDQuery\Dev\QueryProfiler
    {
        return new \PhpDQuery\Dev\QueryProfiler($this->getQueryLog());
    }

    // ─── Raw SQL ──────────────────────────────────────────────────────────────

    /**
     * Execute a SELECT (or any statement that returns rows).
     * Returns a Result object you can iterate / fetchAll / first.
     */
    public function query(string $sql, array $bindings = []): Result
    {
        $start = microtime(true);
        try {
            $stmt = $this->connection->getPDO()->prepare($sql);
            $stmt->execute($bindings);
            $this->connection->logQuery($sql, $bindings, microtime(true) - $start);
            return new Result($stmt);
        } catch (\PDOException $e) {
            throw new QueryException($e->getMessage(), $sql, $bindings, $e);
        }
    }

    /**
     * Execute a non-SELECT statement (INSERT / UPDATE / DELETE).
     * Returns affected row count.
     */
    public function execute(string $sql, array $bindings = []): int
    {
        $start = microtime(true);
        try {
            $stmt = $this->connection->getPDO()->prepare($sql);
            $stmt->execute($bindings);
            $elapsed = microtime(true) - $start;
            $this->connection->logQuery($sql, $bindings, $elapsed);
            EventDispatcher::dispatchSlowQuery($sql, $elapsed * 1000, $bindings);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            $ex = new QueryException($e->getMessage(), $sql, $bindings, $e);
            EventDispatcher::dispatch('query:error', $ex, $sql, $bindings);
            throw $ex;
        }
    }

    /**
     * Fetch a single scalar value from raw SQL.
     * Example: $count = $db->scalar('SELECT COUNT(*) FROM users');
     */
    public function scalar(string $sql, array $bindings = []): mixed
    {
        $start = microtime(true);
        try {
            $stmt = $this->connection->getPDO()->prepare($sql);
            $stmt->execute($bindings);
            $this->connection->logQuery($sql, $bindings, microtime(true) - $start);
            $val = $stmt->fetchColumn();
            return $val === false ? null : $val;
        } catch (\PDOException $e) {
            throw new QueryException($e->getMessage(), $sql, $bindings, $e);
        }
    }

    // ─── Transactions ─────────────────────────────────────────────────────────

    /**
     * Run a closure inside a transaction.
     * Auto-commits on success; auto-rollback on any exception.
     *
     * Example:
     *   $db->transaction(function($db) {
     *       $db->table('accounts')->update(['balance' => 500])->where('id', 1)->run();
     *       $db->table('accounts')->update(['balance' => 300])->where('id', 2)->run();
     *   });
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        return $this->connection->getPDO()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->getPDO()->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->getPDO()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->connection->getPDO()->inTransaction();
    }

    // ─── Events ───────────────────────────────────────────────────────────────

    /**
     * Register a lifecycle event listener.
     *
     * Built-in events:
     *   before:select($sql, $bindings)           → return array to bypass DB (cache)
     *   after:select($table, $rows)
     *   before:insert($table, $data)
     *   after:insert($table, $data, $lastId)
     *   before:update($table, $data, $wheres)
     *   after:update($table, $affectedRows)
     *   before:delete($table, $wheres)           → throw to cancel
     *   after:delete($table, $affectedRows)
     *   slow:query($sql, $timeMs, $bindings)
     *   query:error($exception, $sql, $bindings)
     *
     * @param array $options  ['threshold' => float (ms, for slow:query), 'once' => bool]
     */
    public static function on(string $event, \Closure $handler, array $options = []): void
    {
        EventDispatcher::on($event, $handler, $options);
    }

    public static function once(string $event, \Closure $handler, array $options = []): void
    {
        EventDispatcher::once($event, $handler, $options);
    }

    public static function off(?string $event = null): void
    {
        EventDispatcher::off($event);
    }

    // ─── Query log / Profiler ─────────────────────────────────────────────────

    public function enableQueryLog(): self
    {
        $this->connection->enableQueryLog();
        return $this;
    }

    public function getQueryLog(): array
    {
        return $this->connection->getQueryLog();
    }

    /**
     * Return the last executed query log entry, or null if log is empty.
     */
    public function lastQuery(): ?array
    {
        $log = $this->getQueryLog();
        return !empty($log) ? end($log) : null;
    }

    /**
     * Return total execution time of all logged queries (in ms).
     */
    public function totalQueryTime(): float
    {
        return array_sum(array_column($this->getQueryLog(), 'time')) * 1000;
    }

    // ─── Misc ─────────────────────────────────────────────────────────────────

    /**
     * Escape hatch — access the raw PDO instance.
     */
    public function pdo(): PDO
    {
        return $this->connection->getPDO();
    }

    /**
     * Return the last inserted ID.
     */
    public function lastInsertId(): int|string
    {
        return $this->connection->getPDO()->lastInsertId();
    }

    /**
     * Quick check whether the database connection is alive.
     */
    public function ping(): bool
    {
        try {
            $this->connection->getPDO()->query('SELECT 1');
            return true;
        } catch (\PDOException) {
            return false;
        }
    }
}
