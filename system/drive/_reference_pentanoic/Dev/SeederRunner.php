<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * SeederRunner — orchestrates running one or more Seeder instances.
 */

namespace PhpDQuery\Dev;

use PhpDQuery\Core\Database;

class SeederRunner
{
    private Database $db;
    private Factory  $factory;

    /** @var array<array{type:'class'|'inline', payload:string|\Closure, label:string}> */
    private array $queue = [];

    private bool  $verbose         = true;
    private bool  $truncate        = false;
    private array $truncateTables  = [];

    public function __construct(Database $db, ?Factory $factory = null)
    {
        $this->db      = $db;
        $this->factory = $factory ?? new Factory();
    }

    public function verbose(bool $v = true): self { $this->verbose = $v; return $this; }

    public function truncate(array $tables): self
    {
        $this->truncate       = true;
        $this->truncateTables = $tables;
        return $this;
    }

    public function queue(string ...$classes): self
    {
        foreach ($classes as $class) {
            $this->queue[] = ['type'=>'class','payload'=>$class,'label'=>class_basename($class)];
        }
        return $this;
    }

    public function inline(string $label, \Closure $fn): self
    {
        $this->queue[] = ['type'=>'inline','payload'=>$fn,'label'=>$label];
        return $this;
    }

    public function execute(bool $useTransaction = true): void
    {
        $run = function() {
            if ($this->truncate) {
                foreach ($this->truncateTables as $table) {
                    $this->db->execute("DELETE FROM {$table}");
                    $this->log("  Truncated: {$table}");
                }
            }
            foreach ($this->queue as $item) {
                $start = microtime(true);
                $this->log("  Running: {$item['label']}...");
                if ($item['type'] === 'class') {
                    (new $item['payload']())->run($this->db, $this->factory);
                } else {
                    ($item['payload'])($this->db, $this->factory);
                }
                $ms = round((microtime(true) - $start) * 1000, 1);
                $this->log("  ✓ {$item['label']} done ({$ms}ms)");
            }
        };

        $useTransaction ? $this->db->transaction($run) : $run();
    }

    public function run(string ...$classes): void
    {
        $this->queue(...$classes)->execute();
    }

    public function factory(): Factory { return $this->factory; }

    private function log(string $msg): void
    {
        if ($this->verbose) echo $msg . PHP_EOL;
    }
}

function class_basename(string $class): string
{
    $parts = explode('\\', $class);
    return end($parts);
}
