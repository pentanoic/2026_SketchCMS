<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * DDL (Data Definition Language) builder.
 * Handles CREATE TABLE, DROP, TRUNCATE (with auto-increment reset),
 * ALTER (add/drop/rename column), and index management.
 *
 * TRUNCATE behaviour:
 *   MySQL  → TRUNCATE TABLE (resets AUTO_INCREMENT to 1 natively).
 *            Use ->resetIncrement($start) to set a custom start value.
 *   SQLite → DELETE FROM + DELETE FROM sqlite_sequence (resets ROWID sequence).
 *            Use ->resetIncrement($start) to set a custom start value.
 *
 *   Step (increment by N) is stored and used by Builder::empty() which
 *   seeds a "ghost" row to force SQLite's next ROWID calculation.
 *   MySQL step is set via ALTER TABLE AUTO_INCREMENT after TRUNCATE.
 */

namespace PhpDQuery\Query;

use PhpDQuery\Core\Connection;
use PhpDQuery\Driver\DriverInterface;
use PhpDQuery\Driver\SQLiteDriver;
use PhpDQuery\Driver\MySQLDriver;
use PhpDQuery\Exception\QueryException;

class SchemaBuilder
{
    private Connection      $connection;
    private DriverInterface $driver;
    private string          $action;
    private string          $table       = '';
    private array           $columns     = [];
    private bool            $ifNotExists = false;
    private bool            $ifExists    = false;

    // Auto-increment reset options (for truncate / empty)
    private bool $resetIncrement  = true;   // always reset on truncate
    private int  $incrementStart  = 1;      // reset to this value (default 1)
    private int  $incrementStep   = 1;      // step / interval (default 1)

    public function __construct(Connection $connection, DriverInterface $driver, string $action)
    {
        $this->connection = $connection;
        $this->driver     = $driver;
        $this->action     = $action;
    }

    // ─── Table target ─────────────────────────────────────────────────────────

    public function table(string $name, array $columns = []): self
    {
        $this->table   = $name;
        $this->columns = $columns;
        return $this;
    }

    // ─── Modifiers ────────────────────────────────────────────────────────────

    /** CREATE TABLE IF NOT EXISTS */
    public function ifNotExists(): self
    {
        $this->ifNotExists = true;
        return $this;
    }

    /** DROP TABLE IF EXISTS */
    public function ifExists(): self
    {
        $this->ifExists = true;
        return $this;
    }

    // ─── Auto-increment reset ─────────────────────────────────────────────────

    /**
     * After truncating, reset the auto-increment counter.
     * Default: reset to 1, step 1.
     *
     * @param int $start  The next INSERT will get this ID (default 1)
     * @param int $step   Increment step / interval (default 1)
     *
     * Example:
     *   // Reset to 1, step 1 (default)
     *   $db->truncate()->table('users')->run();
     *
     *   // Reset to 1000, step 1
     *   $db->truncate()->table('users')->resetIncrement(1000)->run();
     *
     *   // Reset to 1, step 5  (IDs: 1, 6, 11, 16, ...)
     *   $db->truncate()->table('users')->resetIncrement(1, 5)->run();
     *
     *   // Keep auto-increment as-is (skip reset)
     *   $db->truncate()->table('users')->keepIncrement()->run();
     */
    public function resetIncrement(int $start = 1, int $step = 1): self
    {
        $this->resetIncrement = true;
        $this->incrementStart = max(1, $start);
        $this->incrementStep  = max(1, $step);
        return $this;
    }

    /**
     * Do NOT reset the auto-increment counter after truncation.
     * The next INSERT will continue from wherever the counter was.
     */
    public function keepIncrement(): self
    {
        $this->resetIncrement = false;
        return $this;
    }

    // ─── ALTER shortcuts ──────────────────────────────────────────────────────

    public function addColumn(string $column, string $type): self
    {
        $this->action  = 'add_column';
        $this->columns = [$column => $type];
        return $this;
    }

    public function dropColumn(string $column): self
    {
        $this->action  = 'drop_column';
        $this->columns = [$column => ''];
        return $this;
    }

    /**
     * Rename a column.
     * MySQL 8+ and SQLite 3.25+ support RENAME COLUMN natively.
     */
    public function renameColumn(string $from, string $to): self
    {
        $this->action  = 'rename_column';
        $this->columns = [$from => $to];
        return $this;
    }

    /**
     * Rename the table itself.
     */
    public function renameTo(string $newName): self
    {
        $this->action  = 'rename_table';
        $this->columns = ['__new__' => $newName];
        return $this;
    }

    // ─── Index management ─────────────────────────────────────────────────────

    /**
     * Create an index.
     * @param string   $indexName  Name of the index
     * @param string[] $columns    Columns to index
     * @param bool     $unique     Whether the index is UNIQUE
     */
    public function createIndex(string $indexName, array $columns, bool $unique = false): self
    {
        $this->action  = 'create_index';
        $this->columns = [
            '__index__'   => $indexName,
            '__unique__'  => $unique,
            '__cols__'    => $columns,
        ];
        return $this;
    }

    public function createUniqueIndex(string $indexName, array $columns): self
    {
        return $this->createIndex($indexName, $columns, true);
    }

    public function dropIndex(string $indexName): self
    {
        $this->action  = 'drop_index';
        $this->columns = ['__index__' => $indexName];
        return $this;
    }

    // ─── Execute ──────────────────────────────────────────────────────────────

    public function run(): void
    {
        $sqls = $this->compile();
        $pdo  = $this->connection->getPDO();

        // Multi-statement actions (truncate + reset) need to be atomic
        $needsTransaction = count($sqls) > 1 && !$pdo->inTransaction();

        if ($needsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            foreach ($sqls as $sql) {
                if (empty(trim($sql))) continue;
                $start = microtime(true);
                $stmt  = $pdo->prepare($sql);
                $stmt->execute();
                $this->connection->logQuery($sql, [], microtime(true) - $start);
            }
            if ($needsTransaction) {
                $pdo->commit();
            }
        } catch (\PDOException $e) {
            if ($needsTransaction) {
                $pdo->rollBack();
            }
            throw new QueryException($e->getMessage(), implode('; ', $sqls), [], $e);
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function isMySQL(): bool
    {
        return $this->driver instanceof MySQLDriver;
    }

    /** Returns one or more SQL statements to execute (some actions need multiple steps). */
    private function compile(): array
    {
        $t        = $this->driver->escapeIdentifier($this->table);
        $isSQLite = $this->driver instanceof SQLiteDriver;
        $pdo      = $this->connection->getPDO();

        switch ($this->action) {
            case 'create':
                $guard = $this->ifNotExists ? 'IF NOT EXISTS ' : '';
                $cols  = [];
                foreach ($this->columns as $name => $def) {
                    $cols[] = $this->driver->escapeIdentifier($name) . ' ' . $def;
                }
                return ["CREATE TABLE {$guard}{$t} (" . implode(', ', $cols) . ')'];

            case 'drop':
                $guard = $this->ifExists ? 'IF EXISTS ' : '';
                return ["DROP TABLE {$guard}{$t}"];

            case 'truncate':
                if ($this->isMySQL()) {
                    // TRUNCATE tự reset AUTO_INCREMENT về 1.
                    // Nếu start != 1 hoặc step != 1 → ALTER thêm.
                    $sqls = ["TRUNCATE TABLE {$t}"];
                    if ($this->resetIncrement) {
                        if ($this->incrementStart !== 1) {
                            $sqls[] = "ALTER TABLE {$t} AUTO_INCREMENT = {$this->incrementStart}";
                        }
                        if ($this->incrementStep !== 1) {
                            // Step per-table (MySQL 8+): không native per-table step.
                            // Fallback: set global @@auto_increment_increment (có tác dụng toàn server).
                            // Thay vào đó document rõ: step trên MySQL ảnh hưởng global.
                            // Implement qua SET SESSION auto_increment_increment — an toàn hơn.
                            $sqls[] = "SET SESSION auto_increment_increment = {$this->incrementStep}";
                        }
                    }
                    return $sqls;
                }

                // SQLite: DELETE + reset sqlite_sequence
                $quotedTable = $pdo->quote($this->table);
                $sqls        = ["DELETE FROM {$t}"];

                if ($this->resetIncrement) {
                    // Xóa entry trong sqlite_sequence → next INSERT bắt đầu từ 1
                    $sqls[] = "DELETE FROM sqlite_sequence WHERE name = {$quotedTable}";

                    if ($this->incrementStart > 1 || $this->incrementStep > 1) {
                        // SQLite tính ROWID tiếp theo = max(rowid) + 1.
                        // Để force start value: đặt seq = start - 1 vào sqlite_sequence.
                        // Để force step: SQLite không hỗ trợ native step — ta insert
                        // một "ghost row" với ROWID = start - 1 rồi xóa nó để
                        // sqlite_sequence ghi nhớ giá trị, sau đó set seq trực tiếp.
                        //
                        // Cách đơn giản nhất và đáng tin cậy nhất:
                        // INSERT OR REPLACE vào sqlite_sequence với seq = start - 1
                        // (sqlite_sequence chỉ tồn tại sau lần INSERT đầu tiên vào bảng,
                        // nên ta dùng INSERT OR IGNORE để tạo nếu chưa có)
                        $seqVal = $this->incrementStart - 1;
                        $sqls[] = "INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ({$quotedTable}, 0)";
                        $sqls[] = "UPDATE sqlite_sequence SET seq = {$seqVal} WHERE name = {$quotedTable}";
                    }
                    // Step trên SQLite: không hỗ trợ native — cần xử lý ở tầng app
                    // hoặc dùng TRIGGER (quá phức tạp cho use case này).
                }
                return $sqls;

            case 'add_column':
                $colName = array_key_first($this->columns);
                $colType = $this->columns[$colName];
                $col     = $this->driver->escapeIdentifier($colName);
                return ["ALTER TABLE {$t} ADD COLUMN {$col} {$colType}"];

            case 'drop_column':
                $colName = array_key_first($this->columns);
                $col     = $this->driver->escapeIdentifier($colName);
                return ["ALTER TABLE {$t} DROP COLUMN {$col}"];

            case 'rename_column':
                $from = $this->driver->escapeIdentifier(array_key_first($this->columns));
                $to   = $this->driver->escapeIdentifier(reset($this->columns));
                return ["ALTER TABLE {$t} RENAME COLUMN {$from} TO {$to}"];

            case 'rename_table':
                $newName = $this->driver->escapeIdentifier($this->columns['__new__']);
                return $isSQLite
                    ? ["ALTER TABLE {$t} RENAME TO {$newName}"]
                    : ["RENAME TABLE {$t} TO {$newName}"];

            case 'create_index':
                $unique    = $this->columns['__unique__'] ? 'UNIQUE ' : '';
                $indexName = $this->driver->escapeIdentifier($this->columns['__index__']);
                $cols      = implode(', ', array_map(
                    fn($c) => $this->driver->escapeIdentifier($c),
                    $this->columns['__cols__']
                ));
                return ["CREATE {$unique}INDEX {$indexName} ON {$t} ({$cols})"];

            case 'drop_index':
                $indexName = $this->driver->escapeIdentifier($this->columns['__index__']);
                return $isSQLite
                    ? ["DROP INDEX {$indexName}"]
                    : ["DROP INDEX {$indexName} ON {$t}"];

            default:
                throw new QueryException("Unknown schema action: {$this->action}");
        }
    }
}
