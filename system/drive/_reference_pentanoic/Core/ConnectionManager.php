<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * ConnectionManager — multi-database / read-write split registry.
 *
 * Manages multiple named Database instances. Connections are lazy: the
 * PDO object is not created until the first query on that connection.
 *
 * Usage:
 *   // Register connections (e.g. in bootstrap)
 *   ConnectionManager::add('default', ['driver'=>'mysql','host'=>'master',...]);
 *   ConnectionManager::add('read',    ['driver'=>'mysql','host'=>'replica',...]);
 *   ConnectionManager::add('logs',    ['driver'=>'sqlite','database'=>'logs.db']);
 *
 *   // Get a Database instance
 *   $db     = ConnectionManager::get();           // 'default'
 *   $dbRead = ConnectionManager::get('read');
 *   $dbLogs = ConnectionManager::get('logs');
 *
 *   // Read/Write split at query level
 *   ConnectionManager::get('read')->table('users')->fetchAll();
 *   ConnectionManager::get()->table('users')->insert([...])->run();
 *
 *   // Swap the default connection at runtime (e.g. per-request tenant)
 *   ConnectionManager::setDefault('tenant_42');
 *
 *   // Remove a connection (closes PDO)
 *   ConnectionManager::remove('read');
 *
 *   // Extend with a pre-built Database instance
 *   ConnectionManager::set('custom', $myDb);
 */

namespace PhpDQuery\Core;

use PhpDQuery\Exception\ConnectionException;

class ConnectionManager
{
    /** @var array<string, array> Raw config arrays (lazy init) */
    private static array $configs = [];

    /** @var array<string, Database> Instantiated Database objects */
    private static array $instances = [];

    /** Default connection name */
    private static string $default = 'default';

    // ─── Registration ─────────────────────────────────────────────────────────

    /**
     * Register a named connection from a config array.
     * The actual PDO connection is not opened until first use.
     *
     * @param string $name    Connection name ('default', 'read', 'logs', …)
     * @param array  $config  Same config array as `new Database($config)`
     */
    public static function add(string $name, array $config): void
    {
        static::$configs[$name]   = $config;
        unset(static::$instances[$name]); // invalidate any cached instance
    }

    /**
     * Register a pre-built Database instance directly.
     */
    public static function set(string $name, Database $db): void
    {
        static::$instances[$name] = $db;
    }

    /**
     * Remove a named connection and close its PDO instance if open.
     */
    public static function remove(string $name): void
    {
        unset(static::$configs[$name], static::$instances[$name]);
    }

    /**
     * Change the default connection name.
     */
    public static function setDefault(string $name): void
    {
        static::$default = $name;
    }

    /**
     * Get the current default connection name.
     */
    public static function getDefault(): string
    {
        return static::$default;
    }

    // ─── Access ───────────────────────────────────────────────────────────────

    /**
     * Retrieve a Database instance by name (lazy-initialised).
     *
     * @param string|null $name  Connection name; null = current default
     * @throws ConnectionException if the name was never registered
     */
    public static function get(?string $name = null): Database
    {
        $name ??= static::$default;

        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        }

        if (!isset(static::$configs[$name])) {
            $available = implode(', ', array_keys(static::$configs + static::$instances));
            throw new ConnectionException(
                "Connection '{$name}' is not registered."
                . ($available ? " Available: [{$available}]." : ' No connections have been registered yet.')
            );
        }

        static::$instances[$name] = new Database(static::$configs[$name]);
        return static::$instances[$name];
    }

    /**
     * Returns true if the named connection is registered (config or instance).
     */
    public static function has(string $name): bool
    {
        return isset(static::$configs[$name]) || isset(static::$instances[$name]);
    }

    /**
     * Return all registered connection names.
     * @return string[]
     */
    public static function names(): array
    {
        return array_unique(array_merge(
            array_keys(static::$configs),
            array_keys(static::$instances)
        ));
    }

    /**
     * Close all PDO connections and clear everything.
     */
    public static function flush(): void
    {
        static::$instances = [];
        static::$configs   = [];
        static::$default   = 'default';
    }

    // ─── Convenience proxy ────────────────────────────────────────────────────

    /**
     * Shorthand: start a query on a table using the default connection.
     * ConnectionManager::table('users')->where('id', 1)->first();
     */
    public static function table(string $name): \PhpDQuery\Query\Builder
    {
        return static::get()->table($name);
    }
}
