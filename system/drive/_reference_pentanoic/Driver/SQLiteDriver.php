<?php
/**
 * Author: valedrat
 * Product: phpSketch
 */

namespace PhpDQuery\Driver;

use PhpDQuery\Core\Config;

class SQLiteDriver implements DriverInterface
{
    public function getDsn(Config $config): string
    {
        $database = $config->get('database');
        return "sqlite:{$database}";
    }

    public function getConnectionOptions(): array
    {
        return [];
    }

    public function escapeIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }
        $parts = explode('.', $identifier);
        foreach ($parts as &$part) {
            if ($part !== '*') {
                $part = '"' . str_replace('"', '""', $part) . '"';
            }
        }
        return implode('.', $parts);
    }
}
