<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * DatabaseDumper — export a database as SQL dump string or file.
 * Works with both SQLite and MySQL.
 * Zero dependency — generates CREATE TABLE + INSERT statements natively.
 *
 * Usage:
 *   $dumper = $db->dumper();
 *
 *   // Dump to string
 *   $sql = $dumper->dump();
 *
 *   // Dump to file
 *   $dumper->toFile('/var/backups/mydb_' . date('Ymd') . '.sql');
 *
 *   // Dump only specific tables
 *   $dumper->tables(['users','posts'])->dump();
 *
 *   // Dump structure only (no data)
 *   $dumper->schemaOnly()->dump();
 *
 *   // Dump data only (no CREATE TABLE)
 *   $dumper->dataOnly()->dump();
 *
 *   // Chunk inserts (default 500 rows per INSERT)
 *   $dumper->chunkSize(200)->dump();
 */

namespace PhpDQuery\Dev;

use PhpDQuery\Core\Connection;
use PhpDQuery\Driver\DriverInterface;
use PhpDQuery\Driver\MySQLDriver;
use PhpDQuery\Schema\SchemaInspector;

class DatabaseDumper
{
    private Connection      $connection;
    private DriverInterface $driver;
    private SchemaInspector $inspector;
    private bool            $isMySQL;

    private ?array $targetTables = null;
    private bool   $schemaOnly   = false;
    private bool   $dataOnly     = false;
    private int    $chunkSize    = 500;

    public function __construct(Connection $connection, DriverInterface $driver)
    {
        $this->connection = $connection;
        $this->driver     = $driver;
        $this->isMySQL    = $driver instanceof MySQLDriver;
        $this->inspector  = new SchemaInspector($connection, $driver);
    }

    // ─── Options ──────────────────────────────────────────────────────────────

    public function tables(array $tables): self
    {
        $clone               = clone $this;
        $clone->targetTables = $tables;
        return $clone;
    }

    public function schemaOnly(): self
    {
        $clone             = clone $this;
        $clone->schemaOnly = true;
        $clone->dataOnly   = false;
        return $clone;
    }

    public function dataOnly(): self
    {
        $clone             = clone $this;
        $clone->dataOnly   = true;
        $clone->schemaOnly = false;
        return $clone;
    }

    public function chunkSize(int $size): self
    {
        $clone            = clone $this;
        $clone->chunkSize = max(1, $size);
        return $clone;
    }

    // ─── Output ───────────────────────────────────────────────────────────────

    /**
     * Generate the SQL dump and return as a string.
     */
    public function dump(): string
    {
        $tables = $this->targetTables ?? $this->inspector->tables();
        $lines  = [];

        $lines[] = '-- phpDQuery Database Dump';
        $lines[] = '-- Generated: ' . date('Y-m-d H:i:s');
        $lines[] = '-- Driver: ' . ($this->isMySQL ? 'MySQL' : 'SQLite');
        $lines[] = '';

        if ($this->isMySQL) {
            $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
            $lines[] = '';
        }

        foreach ($tables as $table) {
            $lines[] = "-- ── Table: {$table} " . str_repeat('─', max(0, 40 - strlen($table)));

            if (!$this->dataOnly) {
                $lines[] = $this->dumpSchema($table);
                $lines[] = '';
            }

            if (!$this->schemaOnly) {
                $inserts = $this->dumpData($table);
                if ($inserts !== '') {
                    $lines[] = $inserts;
                    $lines[] = '';
                }
            }
        }

        if ($this->isMySQL) {
            $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        }

        return implode("\n", $lines);
    }

    /**
     * Write the dump to a file.
     * @throws \RuntimeException on write failure
     */
    public function toFile(string $path): int
    {
        $sql   = $this->dump();
        $bytes = file_put_contents($path, $sql);

        if ($bytes === false) {
            throw new \RuntimeException("DatabaseDumper: cannot write to '{$path}'.");
        }

        return $bytes;
    }

    // ─── Schema ───────────────────────────────────────────────────────────────

    private function dumpSchema(string $table): string
    {
        if ($this->isMySQL) {
            $row = $this->fetchAll("SHOW CREATE TABLE `{$table}`");
            return ($row[0]['Create Table'] ?? '') . ';';
        }

        // SQLite — read from sqlite_master
        $rows = $this->fetchAll(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name = ?",
            [$table]
        );
        $ddl  = $rows[0]['sql'] ?? '';

        // Also dump CREATE INDEX statements
        $idxRows = $this->fetchAll(
            "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name = ? AND sql IS NOT NULL",
            [$table]
        );

        $parts = [$ddl . ';'];
        foreach ($idxRows as $idx) {
            $parts[] = $idx['sql'] . ';';
        }
        return implode("\n", $parts);
    }

    // ─── Data ─────────────────────────────────────────────────────────────────

    private function dumpData(string $table): string
    {
        $t    = $this->driver->escapeIdentifier($table);
        $stmt = $this->connection->getPDO()->prepare("SELECT * FROM {$t}");
        $stmt->execute();

        $lines  = [];
        $buffer = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $buffer[] = $row;

            if (count($buffer) >= $this->chunkSize) {
                $lines[] = $this->buildInsert($table, $buffer);
                $buffer  = [];
            }
        }

        if (!empty($buffer)) {
            $lines[] = $this->buildInsert($table, $buffer);
        }

        return implode("\n", $lines);
    }

    private function buildInsert(string $table, array $rows): string
    {
        if (empty($rows)) return '';

        $t    = $this->driver->escapeIdentifier($table);
        $cols = array_map(
            fn($c) => $this->driver->escapeIdentifier($c),
            array_keys($rows[0])
        );

        $valueSets = [];
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $this->quoteValue($v), array_values($row));
            $valueSets[] = '(' . implode(', ', $vals) . ')';
        }

        return "INSERT INTO {$t} (" . implode(', ', $cols) . ") VALUES\n"
            . implode(",\n", $valueSets) . ';';
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) return 'NULL';
        if (is_int($value) || is_float($value)) return (string)$value;
        return $this->connection->getPDO()->quote((string)$value);
    }

    private function fetchAll(string $sql, array $bindings = []): array
    {
        $stmt = $this->connection->getPDO()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
