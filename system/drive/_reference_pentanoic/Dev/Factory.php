<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Factory — define blueprints for generating row data.
 * Used together with Seeder for populating tables in dev/test environments.
 *
 * The factory closure receives only the current row index ($index, 1-based).
 * For fake/random data, use any external faker library of your choice
 * (e.g. FakerPHP: composer require fakerphp/faker) or plain PHP random functions.
 *
 * Example with FakerPHP:
 *   $fk = \Faker\Factory::create('vi_VN');
 *   $factory->define('users', function(int $index) use ($fk) {
 *       return [
 *           'name'   => $fk->name(),
 *           'email'  => $fk->unique()->safeEmail(),
 *           'role'   => ['user','mod','admin'][array_rand(['user','mod','admin'])],
 *           'score'  => rand(0, 100),
 *           'status' => 1,
 *       ];
 *   });
 *
 * Example without external library:
 *   $factory->define('users', function(int $index) {
 *       return [
 *           'name'   => "User {$index}",
 *           'email'  => "user{$index}@example.com",
 *           'role'   => 'user',
 *           'score'  => rand(0, 100),
 *           'status' => 1,
 *       ];
 *   });
 */

namespace PhpDQuery\Dev;

use PhpDQuery\Core\Database;

class Factory
{
    /** @var array<string, \Closure> */
    private array $definitions = [];

    // ─── Definition ───────────────────────────────────────────────────────────

    /**
     * Register a factory definition for a table.
     *
     * @param string   $table  Table name
     * @param \Closure $fn     fn(int $index): array  — $index is 1-based
     */
    public function define(string $table, \Closure $fn): void
    {
        $this->definitions[$table] = $fn;
    }

    public function hasDefinition(string $table): bool
    {
        return isset($this->definitions[$table]);
    }

    // ─── Generate ─────────────────────────────────────────────────────────────

    /**
     * Generate N rows without inserting.
     *
     * @param array $overrides  Column values to override for every generated row
     * @return array[]
     */
    public function make(string $table, int $count = 1, array $overrides = []): array
    {
        $this->assertDefined($table);
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $row    = ($this->definitions[$table])($i);
            $rows[] = array_merge($row, $overrides);
        }

        return $rows;
    }

    /**
     * Generate and INSERT N rows via Database.
     * Uses insertMany() in chunks for efficiency.
     *
     * @param int   $chunkSize  Rows per INSERT statement (default 500)
     * @return int  Total rows inserted
     */
    public function create(
        Database $db,
        string   $table,
        int      $count     = 1,
        array    $overrides = [],
        int      $chunkSize = 500
    ): int {
        $rows  = $this->make($table, $count, $overrides);
        $total = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $db->table($table)->insertMany($chunk)->run();
            $total += count($chunk);
        }

        return $total;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function assertDefined(string $table): void
    {
        if (!isset($this->definitions[$table])) {
            throw new \InvalidArgumentException(
                "No factory definition registered for table '{$table}'."
            );
        }
    }
}
