<?php
/**
 * Author: valedrat
 * Product: phpSketch
 */

namespace PhpDQuery\Driver;

use PhpDQuery\Core\Config;
use PDO;

class MySQLDriver implements DriverInterface
{
    public function getDsn(Config $config): string
    {
        $host = $config->get('host', 'localhost');
        $port = $config->get('port', 3306);
        $database = $config->get('database');
        $charset = $config->get('charset', 'utf8mb4');

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    public function getConnectionOptions(): array
    {
        return [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
    }

    public function escapeIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }
        $parts = explode('.', $identifier);
        foreach ($parts as &$part) {
            if ($part !== '*') {
                $part = '`' . str_replace('`', '``', $part) . '`';
            }
        }
        return implode('.', $parts);
    }
}
