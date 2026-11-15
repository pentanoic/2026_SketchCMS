<?php
/**
 * Author: valedrat
 * Product: phpSketch
 */

namespace PhpDQuery\Core;

use PDO;
use PhpDQuery\Exception\ConnectionException;
use PhpDQuery\Driver\DriverInterface;

class Connection
{
    private ?PDO $pdo = null;
    private Config $config;
    private DriverInterface $driver;
    private bool $logging = false;
    private array $queryLog = [];

    public function __construct(Config $config, DriverInterface $driver)
    {
        $this->config = $config;
        $this->driver = $driver;
    }

    public function enableQueryLog(): void
    {
        $this->logging = true;
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function logQuery(string $sql, array $bindings, float $time): void
    {
        if ($this->logging) {
            $this->queryLog[] = [
                'sql' => $sql,
                'bindings' => $bindings,
                'time' => $time
            ];
        }
    }

    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    private function connect(): void
    {
        try {
            $dsn = $this->driver->getDsn($this->config);
            $username = $this->config->get('username', '');
            $password = $this->config->get('password', '');
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Thêm các option đặc thù của driver nếu có
            $options = array_replace($options, $this->driver->getConnectionOptions());
            $options = array_replace($options, $this->config->getOptions());

            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new ConnectionException('Could not connect to database: ' . $e->getMessage(), 0, $e);
        }
    }
}
