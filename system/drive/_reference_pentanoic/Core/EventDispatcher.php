<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Lightweight event/hook system for phpDQuery lifecycle events.
 * Zero dependency, pure PHP, static registry.
 *
 * Built-in events dispatched by the library:
 *
 *   before:select($sql, $bindings)            → return non-null to short-circuit with cached result
 *   after:select($table, $rows)               → receives final rows array
 *   before:insert($table, $data)
 *   after:insert($table, $data, $lastId)
 *   before:update($table, $data, $wheres)
 *   after:update($table, $affectedRows)
 *   before:delete($table, $wheres)            → throw to prevent deletion
 *   after:delete($table, $affectedRows)
 *   slow:query($sql, $timeMs, $bindings)      → triggered when query exceeds threshold
 *   query:error($exception, $sql, $bindings)  → triggered on QueryException
 *
 * Usage:
 *   EventDispatcher::on('slow:query', function(string $sql, float $ms) {
 *       error_log("Slow query ({$ms}ms): {$sql}");
 *   }, threshold: 50.0);
 *
 *   EventDispatcher::on('before:delete', function(string $table, array $wheres) {
 *       if ($table === 'users' && empty($wheres)) {
 *           throw new \RuntimeException('Bulk delete on users is forbidden.');
 *       }
 *   });
 */

namespace PhpDQuery\Core;

class EventDispatcher
{
    /** @var array<string, array<array{handler:\Closure, options:array}>> */
    private static array $listeners = [];

    // ─── Registration ─────────────────────────────────────────────────────────

    /**
     * Register a listener for an event.
     *
     * @param string   $event     Event name, e.g. 'before:insert', 'slow:query'
     * @param \Closure $handler   Handler callable
     * @param array    $options   Extra options:
     *                            - 'threshold' (float, ms): only for 'slow:query'
     *                            - 'once'      (bool): auto-remove after first call
     */
    public static function on(string $event, \Closure $handler, array $options = []): void
    {
        static::$listeners[$event][] = [
            'handler' => $handler,
            'options' => $options,
        ];
    }

    /**
     * Register a one-time listener (removed after first dispatch).
     */
    public static function once(string $event, \Closure $handler, array $options = []): void
    {
        static::on($event, $handler, array_merge($options, ['once' => true]));
    }

    /**
     * Remove all listeners for a given event (or all events if null).
     */
    public static function off(?string $event = null): void
    {
        if ($event === null) {
            static::$listeners = [];
        } else {
            unset(static::$listeners[$event]);
        }
    }

    /**
     * Return all registered event names.
     * @return string[]
     */
    public static function registeredEvents(): array
    {
        return array_keys(static::$listeners);
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────────

    /**
     * Dispatch an event. Passes all $args to each listener.
     * Listeners may return non-null to short-circuit — useful for caching.
     *
     * @return mixed  First non-null return value from any listener, or null
     */
    public static function dispatch(string $event, mixed ...$args): mixed
    {
        if (empty(static::$listeners[$event])) {
            return null;
        }

        $result  = null;
        $remove  = [];

        foreach (static::$listeners[$event] as $i => $entry) {
            $ret = ($entry['handler'])(...$args);

            if ($ret !== null && $result === null) {
                $result = $ret;  // keep only the FIRST non-null return
            }

            if (!empty($entry['options']['once'])) {
                $remove[] = $i;
            }
        }

        foreach (array_reverse($remove) as $i) {
            array_splice(static::$listeners[$event], $i, 1);
        }

        return $result;
    }

    /**
     * Dispatch a 'slow:query' event — only fires if $timeMs ≥ listener threshold.
     */
    public static function dispatchSlowQuery(string $sql, float $timeMs, array $bindings = []): void
    {
        if (empty(static::$listeners['slow:query'])) {
            return;
        }

        foreach (static::$listeners['slow:query'] as $entry) {
            $threshold = (float)($entry['options']['threshold'] ?? 100.0);
            if ($timeMs >= $threshold) {
                ($entry['handler'])($sql, $timeMs, $bindings);
            }
        }
    }
}
