<?php
/**
 * DriverInterface - Database Driver Contract (Fluent Architecture)
 */

interface DriverInterface
{
    public function connect(): PDO;

    public function getName(): string;

    public function quoteIdentifier(string $identifier): string;

    public function compileLimit(int $limit, ?int $offset = null): string;
}
