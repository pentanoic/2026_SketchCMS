<?php
/**
 * MySQLDriver - MySQL / MariaDB Driver
 */

class MySQLDriver implements DriverInterface
{
    public function connect(): PDO
    {
        $db_host = (string) Env::get('DB_HOST', '127.0.0.1');
        $db_user = (string) Env::get('DB_USER', '');
        $db_pass = (string) Env::get('DB_PASS', '');
        $db_name = (string) Env::get('DB_NAME', '');

        return new PDO(
            'mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4',
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]
        );
    }

    public function getName(): string
    {
        return 'mysql';
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function compileLimit(int $limit, ?int $offset = null): string
    {
        $sql = 'LIMIT ' . max(0, $limit);
        if ($offset !== null && $offset > 0) {
            $sql .= ' OFFSET ' . $offset;
        }
        return $sql;
    }
}
