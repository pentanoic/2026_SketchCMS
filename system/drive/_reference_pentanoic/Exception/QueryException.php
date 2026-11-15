<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 */

namespace PhpDQuery\Exception;

class QueryException extends DatabaseException
{
    private string $sql;
    private array  $bindings;

    public function __construct(
        string     $message  = '',
        string     $sql      = '',
        array      $bindings = [],
        ?\Throwable $previous = null
    ) {
        $this->sql      = $sql;
        $this->bindings = $bindings;

        parent::__construct($message, 0, $previous);
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Returns the SQL with bindings interpolated (for display/debug only — NOT safe for execution).
     */
    public function getDebugSql(): string
    {
        $sql      = $this->sql;
        $bindings = $this->bindings;

        foreach ($bindings as $value) {
            $replacement = is_null($value)
                ? 'NULL'
                : (is_string($value) ? "'" . addslashes($value) . "'" : (string)$value);

            $pos = strpos($sql, '?');
            if ($pos !== false) {
                $sql = substr_replace($sql, $replacement, $pos, 1);
            }
        }

        return $sql;
    }
}
