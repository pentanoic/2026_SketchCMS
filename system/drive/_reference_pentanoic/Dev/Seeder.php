<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Seeder — abstract base class for class-based seeders.
 *
 * Usage:
 *   class UserSeeder extends Seeder {
 *       public function run(Database $db, Factory $factory): void {
 *           $factory->create($db, 'users', 50);
 *       }
 *   }
 *
 *   $runner = new SeederRunner($db);
 *   $runner->run(UserSeeder::class);
 */

namespace PhpDQuery\Dev;

use PhpDQuery\Core\Database;

abstract class Seeder
{
    abstract public function run(Database $db, Factory $factory): void;
}
