<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * SchemaInspector — read-only introspection of database structure.
 * Works with both MySQL and SQLite via driver-specific queries.
 *
 * Usage:
 *   $inspector = $db->inspect();
 *
 *   $inspector->tables();                      // ['users', 'posts', ...]
 *   $inspector->hasTable('users');             // bool
 *   $inspector->columns('users');             // [['name','type','nullable','default'], ...]
 *   $inspector->hasColumn('users','email');    // bool
 *   $inspector->indexes('users');             // [['name','columns','unique'], ...]
 *   $inspector->primaryKey('users');          // 'id'
 *   $inspector->rowCount('users');            // int
 *   $inspector->tableSize('users');           // bytes (MySQL) or null (SQLite)
 *   $inspector->dump();                       // full schema snapshot array
 */

namespace PhpDQuery\Schema;

use PhpDQuery\Core\Connection;
use PhpDQuery\Driver\DriverInterface;
use PhpDQuery\Driver\MySQLDriver;
use PhpDQuery\Driver\SQLiteDriver;

class SchemaInspector
{
    private Connection      $connection;
    private DriverInterface $driver;
    private bool            $isMySQL;

    public function __construct(Connection $connection, DriverInterface $driver)
    {
        $this->connection = $connection;
        $this->driver     = $driver;
        $this->isMySQL    = $driver instanceof MySQLDriver;
    }

    // ─── Tables ───────────────────────────────────────────────────────────────

    /**
     * List all table names in the current database.
     * @return string[]
     */
    public function tables(): array
    {
        if ($this->isMySQL) {
            $rows = $this->fetchAll('SHOW TABLES');
            return array_map('array_pop', $rows);
        }

        // SQLite
        $rows = $this->fetchAll(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );
        return array_column($rows, 'name');
    }

    /**
     * Return true if the table exists.
     */
    public function hasTable(string $table): bool
    {
        return in_array($table, $this->tables(), true);
    }

    // ─── Columns ──────────────────────────────────────────────────────────────

    /**
     * Return column definitions for a table.
     * Each entry: ['name', 'type', 'nullable', 'default', 'primary']
     *
     * @return array<array{name:string,type:string,nullable:bool,default:mixed,primary:bool}>
     */
    public function columns(string $table): array
    {
        if ($this->isMySQL) {
            $rows = $this->fetchAll("DESCRIBE `{$table}`");
            return array_map(fn($r) => [
                'name'     => $r['Field'],
                'type'     => $r['Type'],
                'nullable' => $r['Null'] === 'YES',
                'default'  => $r['Default'],
                'primary'  => $r['Key'] === 'PRI',
            ], $rows);
        }

        // SQLite
        $rows = $this->fetchAll("PRAGMA table_info(\"{$table}\")");
        return array_map(fn($r) => [
            'name'     => $r['name'],
            'type'     => $r['type'],
            'nullable' => (int)$r['notnull'] === 0,
            'default'  => $r['dflt_value'],
            'primary'  => (int)$r['pk'] === 1,
        ], $rows);
    }

    /**
     * Return column names only for a table.
     * @return string[]
     */
    public function columnNames(string $table): array
    {
        return array_column($this->columns($table), 'name');
    }

    /**
     * Return true if the column exists on the table.
     */
    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columnNames($table), true);
    }

    /**
     * Return the primary key column name (or null if none/composite).
     */
    public function primaryKey(string $table): ?string
    {
        foreach ($this->columns($table) as $col) {
            if ($col['primary']) {
                return $col['name'];
            }
        }
        return null;
    }

    // ─── Indexes ──────────────────────────────────────────────────────────────

    /**
     * List indexes for a table.
     * Each entry: ['name', 'columns', 'unique']
     *
     * @return array<array{name:string,columns:string[],unique:bool}>
     */
    public function indexes(string $table): array
    {
        if ($this->isMySQL) {
            $rows    = $this->fetchAll("SHOW INDEX FROM `{$table}`");
            $indexes = [];
            foreach ($rows as $r) {
                $name = $r['Key_name'];
                if (!isset($indexes[$name])) {
                    $indexes[$name] = [
                        'name'    => $name,
                        'columns' => [],
                        'unique'  => (int)$r['Non_unique'] === 0,
                    ];
                }
                $indexes[$name]['columns'][] = $r['Column_name'];
            }
            return array_values($indexes);
        }

        // SQLite — PRAGMA index_list + PRAGMA index_info
        $lists   = $this->fetchAll("PRAGMA index_list(\"{$table}\")");
        $indexes = [];
        foreach ($lists as $idx) {
            $cols = $this->fetchAll("PRAGMA index_info(\"{$idx['name']}\")");
            $indexes[] = [
                'name'    => $idx['name'],
                'columns' => array_column($cols, 'name'),
                'unique'  => (int)$idx['unique'] === 1,
            ];
        }
        return $indexes;
    }

    /**
     * Return true if an index with the given name exists.
     */
    public function hasIndex(string $table, string $indexName): bool
    {
        foreach ($this->indexes($table) as $idx) {
            if ($idx['name'] === $indexName) return true;
        }
        return false;
    }

    // ─── Size / Stats ─────────────────────────────────────────────────────────

    /**
     * Return the row count of a table (fast — uses COUNT(*)).
     */
    public function rowCount(string $table): int
    {
        $t   = $this->driver->escapeIdentifier($table);
        $row = $this->fetchAll("SELECT COUNT(*) AS cnt FROM {$t}");
        return (int)($row[0]['cnt'] ?? 0);
    }

    /**
     * Return table size in bytes (MySQL only; SQLite returns null).
     */
    public function tableSize(string $table): ?int
    {
        if (!$this->isMySQL) {
            return null;
        }
        $rows = $this->fetchAll("
            SELECT data_length + index_length AS size
            FROM information_schema.TABLES
            WHERE table_name = ?
        ", [$table]);
        return isset($rows[0]['size']) ? (int)$rows[0]['size'] : null;
    }

    // ─── Full snapshot ────────────────────────────────────────────────────────

    /**
     * Return a complete schema snapshot of all tables.
     *
     * @return array<string, array{columns: array, indexes: array, rowCount: int}>
     */
    public function dump(): array
    {
        $snapshot = [];
        foreach ($this->tables() as $table) {
            $snapshot[$table] = [
                'columns'  => $this->columns($table),
                'indexes'  => $this->indexes($table),
                'rowCount' => $this->rowCount($table),
            ];
        }
        return $snapshot;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function fetchAll(string $sql, array $bindings = []): array
    {
        $stmt = $this->connection->getPDO()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }
}
