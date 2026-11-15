<?php
/**
 * Author: valedrat
 * Product: phpSketch
 */

namespace PhpDQuery\Driver;

use PhpDQuery\Core\Config;

interface DriverInterface
{
    public function getDsn(Config $config): string;
    public function getConnectionOptions(): array;
    public function escapeIdentifier(string $identifier): string;
}
