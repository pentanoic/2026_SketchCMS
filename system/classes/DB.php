<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 * Email: kioku17@protonmail.com
 * GitHub: https://github.com/pentanoic/
 * Website: https://lab302.ovh/
 *
 * Copyright (c) 2026 valedrat. All rights reserved.
 *
 * This file is part of the SketchCMS source code.
 * Please do not remove or modify this copyright notice.
 */

class DB
{
    public function __invoke()
    {
        $connection = strtolower((string) Env::get('DB_CONNECTION', 'mysql'));

        if ($connection === 'sqlite') {
            try {
                return (new SQLiteDriver())->connect();
            } catch (PDOException $e) {
                echo '<h2>SQLite ERROR: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</h2>';
                exit;
            }
        }

        try {
            return (new MySQLDriver())->connect();
        } catch (PDOException $e) {
            echo '<h2>MySQL ERROR: ' . $e->getCode() . '</h2>';
            switch ($e->getCode()) {
                case 1045:
                    exit('Access credentials (username or password) to a database are incorrect');
                case 1049:
                    exit('The name of a database is specified incorrectly');
                case 2002:
                    exit('Invalid database server');
            }
            exit;
        }
    }

    public static function syncDatabaseIfEmpty(string $fromDriver, string $toDriver): void
    {
        if ($fromDriver === $toDriver) {
            return;
        }

        $schemaPath = SYSTEM . 'configs' . DS . 'schema.json';
        if (!is_file($schemaPath)) {
            return;
        }
        $schema = json_decode((string) file_get_contents($schemaPath), true);
        if (!is_array($schema) || empty($schema)) {
            return;
        }

        try {
            $mysql = (new MySQLDriver())->connect();
            $sqlite = (new SQLiteDriver())->connect();
        } catch (Throwable $e) {
            return;
        }

        $tables = array_keys($schema);

        if ($fromDriver === 'mysql' && $toDriver === 'sqlite') {
            $hasUsers = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
            if ($hasUsers) {
                $count = (int) $sqlite->query("SELECT COUNT(*) FROM users")->fetchColumn();
                if ($count > 0) {
                    return;
                }
            } else {
                SqliteDriver::initSchema($sqlite);
            }

            $sqlite->beginTransaction();
            try {
                foreach ($tables as $table) {
                    $sqlite->exec("DELETE FROM \"{$table}\"");
                    $rows = $mysql->query("SELECT * FROM `{$table}`")->fetchAll();
                    if (empty($rows)) {
                        continue;
                    }
                    $cols = array_keys($rows[0]);
                    $colList = implode(', ', array_map(fn($c) => "\"{$c}\"", $cols));
                    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
                    $stmt = $sqlite->prepare("INSERT OR IGNORE INTO \"{$table}\" ({$colList}) VALUES ({$placeholders})");
                    foreach ($rows as $row) {
                        $stmt->execute(array_values($row));
                    }
                }
                $sqlite->commit();
            } catch (Throwable $e) {
                if ($sqlite->inTransaction()) {
                    $sqlite->rollBack();
                }
            }
        } elseif ($fromDriver === 'sqlite' && $toDriver === 'mysql') {
            $hasMysqlUsers = $mysql->query("SHOW TABLES LIKE 'users'")->fetchColumn();
            if ($hasMysqlUsers) {
                $count = (int) $mysql->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
                if ($count > 0) {
                    return;
                }
            }

            $mysql->beginTransaction();
            try {
                foreach ($tables as $table) {
                    $hasTable = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name=" . $sqlite->quote($table))->fetchColumn();
                    if (!$hasTable) {
                        continue;
                    }
                    $rows = $sqlite->query("SELECT * FROM \"{$table}\"")->fetchAll();
                    if (empty($rows)) {
                        continue;
                    }
                    $cols = array_keys($rows[0]);
                    $colList = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
                    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
                    $stmt = $mysql->prepare("INSERT IGNORE INTO `{$table}` ({$colList}) VALUES ({$placeholders})");
                    foreach ($rows as $row) {
                        $stmt->execute(array_values($row));
                    }
                }
                $mysql->commit();
            } catch (Throwable $e) {
                if ($mysql->inTransaction()) {
                    $mysql->rollBack();
                }
            }
        }
    }
}
