<?php
/**
 * Author: valedrat
 * Product: phpSketch
 */

namespace PhpDQuery\Core;

use PhpDQuery\Exception\DatabaseException;

class Config
{
    private string $driver;
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private array $options;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = $config['driver'] ?? 'mysql';
        $this->host = $config['host'] ?? '127.0.0.1';
        $this->database = $config['database'] ?? '';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';
        $this->options = $config['options'] ?? [];
        
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->driver)) {
            throw new DatabaseException('Configuration "driver" is missing.');
        }

        if (!in_array($this->driver, ['mysql', 'sqlite'])) {
            throw new DatabaseException('Unsupported driver: ' . $this->driver);
        }

        if ($this->driver === 'sqlite' && empty($this->database)) {
            throw new DatabaseException('SQLite requires a "database" path in configuration.');
        }

        if ($this->driver === 'mysql' && empty($this->database)) {
            throw new DatabaseException('MySQL requires a "database" name in configuration.');
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
