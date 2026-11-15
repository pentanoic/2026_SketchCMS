<?php
/**
 * SQLiteDriver - SQLite Driver with WAL & Auto-Schema
 */

class SQLiteDriver implements DriverInterface
{
    public static function resolvePath(): string
    {
        $relPath = (string) Env::get('DB_SQLITE_PATH', 'app/database/sketchcms.sqlite');
        return (strpos($relPath, ':') !== false || strpos($relPath, '/') === 0)
            ? $relPath
            : ROOT . str_replace(['/', '\\'], DS, $relPath);
    }

    public function connect(): PDO
    {
        $dbPath = self::resolvePath();
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->sqliteCreateFunction('RAND', static fn(): float => mt_rand() / mt_getrandmax(), 0);

        $hasUsers = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
        if (!$hasUsers) {
            self::initSchema($pdo);
        }

        return $pdo;
    }

    public function getName(): string
    {
        return 'sqlite';
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public function compileLimit(int $limit, ?int $offset = null): string
    {
        $sql = 'LIMIT ' . max(0, $limit);
        if ($offset !== null && $offset > 0) {
            $sql .= ' OFFSET ' . $offset;
        }
        return $sql;
    }

    public static function initSchema(PDO $pdo): void
    {
        $schemaPath = SYSTEM . 'configs' . DS . 'schema.json';
        if (!is_file($schemaPath)) {
            return;
        }
        $schema = json_decode((string) file_get_contents($schemaPath), true);
        if (!is_array($schema) || empty($schema)) {
            return;
        }

        $pdo->beginTransaction();
        try {
            foreach ($schema as $tableDef) {
                $createSql = (string) ($tableDef['create_sql'] ?? '');
                if ($createSql === '') {
                    continue;
                }
                $sql = str_replace('`', '"', $createSql);
                $sql = preg_replace('/\s+COLLATE\s+[a-z0-9_]+/i', '', $sql);
                $sql = preg_replace('/\s+CHARACTER SET\s+[a-z0-9_]+/i', '', $sql);
                $sql = preg_replace('/\s+UNSIGNED/i', '', $sql);
                $sql = preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
                $sql = preg_replace('/\)\s*ENGINE=.*?;/is', ');', $sql);
                $sql = preg_replace('/"([a-z0-9_]+)"\s+(?:tiny|small|medium|big)?int(?:\(\d+\))?\s+NOT NULL\s+AUTO_INCREMENT/i', '"$1" INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
                if (stripos($sql, 'PRIMARY KEY AUTOINCREMENT') !== false) {
                    $sql = preg_replace('/,\s*PRIMARY KEY\s*\([^)]+\)/i', '', $sql);
                }
                $sql = preg_replace('/,\s*(?:UNIQUE\s+)?(?:KEY|INDEX)\s+"[^"]+"\s*\([^)]+\)/i', '', $sql);
                $sql = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql);
                $pdo->exec($sql);
            }

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
}
