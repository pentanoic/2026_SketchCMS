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
            $relPath = (string) Env::get('DB_SQLITE_PATH', 'app/database/sketchcms.sqlite');
            $dbPath = (strpos($relPath, ':') !== false || strpos($relPath, '/') === 0)
                ? $relPath
                : ROOT . str_replace(['/', '\\'], DS, $relPath);

            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            try {
                $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $pdo->exec('PRAGMA journal_mode = WAL;');
                $pdo->exec('PRAGMA synchronous = NORMAL;');
                $pdo->exec('PRAGMA busy_timeout = 5000;');
                $pdo->exec('PRAGMA foreign_keys = ON;');

                $hasUsers = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
                if (!$hasUsers) {
                    $this->initSqliteSchema($pdo);
                }

                return $pdo;
            } catch (PDOException $e) {
                echo '<h2>SQLite ERROR: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</h2>';
                exit;
            }
        }

        $db_host = (string) Env::get('DB_HOST', '127.0.0.1');
        $db_user = (string) Env::get('DB_USER', '');
        $db_pass = (string) Env::get('DB_PASS', '');
        $db_name = (string) Env::get('DB_NAME', '');

        try {
            $pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4', $db_user, $db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            );
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

        return $pdo;
    }

    private function initSqliteSchema(PDO $pdo): void
    {
        $schemaPath = SYSTEM . 'configs' . DS . 'schema.json';
        if (!is_file($schemaPath)) {
            return;
        }
        $schema = json_decode((string) file_get_contents($schemaPath), true);
        if (!is_array($schema) || empty($schema['tables'])) {
            return;
        }

        $pdo->beginTransaction();
        try {
            foreach ($schema['tables'] as $tableName => $tableDef) {
                $cols = [];
                foreach ($tableDef['columns'] ?? [] as $colName => $colDef) {
                    $type = strtoupper((string) ($colDef['type'] ?? 'TEXT'));
                    $isPk = !empty($colDef['primary']) || (!empty($colDef['auto_increment']));
                    if ($isPk && strpos($type, 'INT') !== false) {
                        $cols[] = "\"{$colName}\" INTEGER PRIMARY KEY AUTOINCREMENT";
                        continue;
                    }
                    if (strpos($type, 'INT') !== false) {
                        $sqlType = 'INTEGER';
                    } elseif (strpos($type, 'FLOAT') !== false || strpos($type, 'DOUBLE') !== false || strpos($type, 'DECIMAL') !== false) {
                        $sqlType = 'REAL';
                    } else {
                        $sqlType = 'TEXT';
                    }
                    $nullable = (isset($colDef['null']) && $colDef['null'] === false) ? ' NOT NULL' : '';
                    $default = array_key_exists('default', $colDef)
                        ? ' DEFAULT ' . (is_numeric($colDef['default']) ? $colDef['default'] : $pdo->quote((string) $colDef['default']))
                        : '';
                    $cols[] = "\"{$colName}\" {$sqlType}{$nullable}{$default}";
                }
                if (!empty($cols)) {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS \"{$tableName}\" (" . implode(', ', $cols) . ")");
                }
            }

            // Seed 2 user đầu tiên giống install/index.php
            $now = defined('TIME') ? TIME : time();
            $adminNick = (string) Env::get('MANAGER_ADMIN_ACCOUNT', 'admin');
            $adminPassRaw = (string) Env::get('MANAGER_ADMIN_PASSWORD', 'admin');
            $adminPassHash = (strpos($adminPassRaw, '$argon2') === 0 || strpos($adminPassRaw, '$2y$') === 0)
                ? $adminPassRaw
                : password_hash($adminPassRaw, PASSWORD_ARGON2ID);
            $adminName = (string) Env::get('MANAGER_ADMIN_NAME', 'Administrator');
            $adminEmail = (string) Env::get('MANAGER_ADMIN_EMAIL', 'admin@sketchcms.test');
            $adminLevel = (int) Env::get('MANAGER_ADMIN_LEVEL', 127);

            $stmtAdmin = $pdo->prepare("INSERT INTO users (nick, name, pass, email, level, reg) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtAdmin->execute([$adminNick, $adminName, $adminPassHash, $adminEmail, $adminLevel, $now]);

            $botNick = (string) Env::get('USER_BOT', 'SystemBOT');
            $botPassHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID);
            $stmtBot = $pdo->prepare("INSERT INTO users (nick, name, pass, email, level, reg) VALUES (?, ?, ?, ?, 127, ?)");
            $stmtBot->execute([$botNick, 'SystemBOT', $botPassHash, 'bot@sketchcms.test', $now]);

            // Seed chuyên mục & bài viết mặc định
            $pdo->exec("INSERT OR IGNORE INTO articles_category (id, name, slug, content, keyword) VALUES (1, 'Tin tức - Sự kiện', 'tin-tuc-su-kien', 'Chuyên mục tin tức', 'tin tức, sự kiện')");
            $stmtPost = $pdo->prepare("INSERT OR IGNORE INTO articles_post (id, title, slug, content, time, update_time, category, author) VALUES (1, ?, ?, ?, ?, ?, 1, ?)");
            $stmtPost->execute(['Chào mừng đến với SketchCMS', 'chao-mung-den-voi-sketchcms', 'Hệ thống đã được khởi tạo thành công trên SQLite.', $now, $now, $adminNick]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
        if (!is_array($schema) || empty($schema['tables'])) {
            return;
        }

        try {
            $mysql = new PDO(
                'mysql:host=' . Env::get('DB_HOST', '127.0.0.1') . ';dbname=' . Env::get('DB_NAME', '') . ';charset=utf8mb4',
                (string) Env::get('DB_USER', ''),
                (string) Env::get('DB_PASS', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (Throwable $e) {
            return;
        }

        $relPath = (string) Env::get('DB_SQLITE_PATH', 'app/database/sketchcms.sqlite');
        $dbPath = (strpos($relPath, ':') !== false || strpos($relPath, '/') === 0)
            ? $relPath
            : ROOT . str_replace(['/', '\\'], DS, $relPath);
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        try {
            $sqlite = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $sqlite->exec('PRAGMA journal_mode = WAL;');
            $sqlite->exec('PRAGMA synchronous = NORMAL;');
            $sqlite->exec('PRAGMA busy_timeout = 5000;');
        } catch (Throwable $e) {
            return;
        }

        $tables = array_keys($schema['tables']);

        if ($fromDriver === 'mysql' && $toDriver === 'sqlite') {
            $hasUsers = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
            if ($hasUsers) {
                $count = (int) $sqlite->query("SELECT COUNT(*) FROM users")->fetchColumn();
                if ($count > 0) {
                    return; // SQLite đã có dữ liệu
                }
            } else {
                (new self())->initSqliteSchema($sqlite);
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
                    return; // MySQL đã có dữ liệu
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
