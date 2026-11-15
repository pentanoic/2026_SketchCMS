<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * BuilderMacros — trait mixed into Builder to support runtime method extension.
 *
 * Macros let you register custom fluent methods once (e.g., in bootstrap)
 * and call them anywhere as if they were native Builder methods.
 *
 * Example:
 *   Builder::macro('active', fn() => $this->where('status', 1));
 *   Builder::macro('forRole', fn(string $role) => $this->where('role', $role));
 *
 *   $db->table('users')->active()->forRole('admin')->fetchAll();
 */

namespace PhpDQuery\Query;

use PhpDQuery\Exception\QueryException;

trait BuilderMacros
{
    /** @var array<string, \Closure> */
    private static array $macros = [];

    // ─── Registration ─────────────────────────────────────────────────────────

    /**
     * Register a custom macro on Builder.
     *
     * The closure receives $this (the Builder instance) as its first implicit
     * binding, plus any arguments passed at call time.
     *
     * @param string   $name    Method name to register
     * @param \Closure $macro   Closure — use $this freely inside
     */
    public static function macro(string $name, \Closure $macro): void
    {
        if (method_exists(static::class, $name)) {
            throw new QueryException(
                "Cannot register macro '{$name}': a native method with that name already exists."
            );
        }
        static::$macros[$name] = $macro;
    }

    /**
     * Remove a previously registered macro.
     */
    public static function forgetMacro(string $name): void
    {
        unset(static::$macros[$name]);
    }

    /**
     * Remove all registered macros.
     */
    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    /**
     * Check whether a macro is registered.
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * List all registered macro names.
     * @return string[]
     */
    public static function getMacroNames(): array
    {
        return array_keys(static::$macros);
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────────

    /**
     * Called automatically by PHP when an undefined method is invoked.
     * Binds the macro closure to $this so it can use $this->where() etc.
     */
    public function __call(string $name, array $args): mixed
    {
        if (!isset(static::$macros[$name])) {
            throw new QueryException(
                "Call to undefined Builder method or macro: '{$name}()'."
            );
        }

        $macro = \Closure::bind(static::$macros[$name], $this, static::class);
        return $macro(...$args);
    }
}
