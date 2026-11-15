<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * Caster — automatic result type casting for fetchAll() / first() results.
 *
 * Supported cast types:
 *   'int'       | 'integer'    → (int)
 *   'float'     | 'double'     → (float)
 *   'bool'      | 'boolean'    → (bool)
 *   'string'                   → (string)
 *   'json'      | 'array'      → json_decode(, true)
 *   'object'                   → json_decode(, false)
 *   'datetime'                 → \DateTimeImmutable (full datetime string)
 *   'date'                     → \DateTimeImmutable (date only, time 00:00:00)
 *   'timestamp'                → (int) Unix timestamp from datetime string
 *   'base64'                   → base64_decode()
 *
 * Usage in Builder:
 *   $db->table('users')
 *      ->cast(['id' => 'int', 'score' => 'float', 'meta' => 'json', 'active' => 'bool'])
 *      ->fetchAll();
 */

namespace PhpDQuery\Query;

use PhpDQuery\Exception\QueryException;

class Caster
{
    private array $map;

    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * Apply casts to a single row (key-value array).
     */
    public function castRow(array $row): array
    {
        foreach ($this->map as $col => $type) {
            if (!array_key_exists($col, $row)) {
                continue;
            }
            $row[$col] = $this->castValue($row[$col], $type, $col);
        }
        return $row;
    }

    /**
     * Apply casts to every row in a result set.
     */
    public function castAll(array $rows): array
    {
        if (empty($rows) || empty($this->map)) {
            return $rows;
        }
        foreach ($rows as &$row) {
            $row = $this->castRow($row);
        }
        return $rows;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function castValue(mixed $value, string $type, string $col): mixed
    {
        // NULL stays NULL for all cast types
        if ($value === null) {
            return null;
        }

        return match (strtolower($type)) {
            'int', 'integer'    => (int) $value,
            'float', 'double'   => (float) $value,
            'bool', 'boolean'   => (bool)(int) $value,
            'string'            => (string) $value,

            'json', 'array'     => $this->decodeJson($value, true, $col),
            'object'            => $this->decodeJson($value, false, $col),

            'datetime'          => $this->toDatetime($value, $col),
            'date'              => $this->toDate($value, $col),
            'timestamp'         => $this->toTimestamp($value, $col),

            'base64'            => base64_decode($value, true) ?: $value,

            default => throw new QueryException(
                "Unknown cast type '{$type}' for column '{$col}'."
            ),
        };
    }

    private function decodeJson(mixed $value, bool $assoc, string $col): mixed
    {
        if (is_array($value) || is_object($value)) {
            return $value; // already decoded
        }
        $decoded = json_decode((string) $value, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new QueryException(
                "Failed to JSON-decode column '{$col}': " . json_last_error_msg()
            );
        }
        return $decoded;
    }

    private function toDatetime(mixed $value, string $col): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            throw new QueryException("Cannot cast column '{$col}' value '{$value}' to datetime.");
        }
    }

    private function toDate(mixed $value, string $col): \DateTimeImmutable
    {
        try {
            return (new \DateTimeImmutable((string) $value))->setTime(0, 0, 0);
        } catch (\Throwable) {
            throw new QueryException("Cannot cast column '{$col}' value '{$value}' to date.");
        }
    }

    private function toTimestamp(mixed $value, string $col): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        try {
            return (new \DateTimeImmutable((string) $value))->getTimestamp();
        } catch (\Throwable) {
            throw new QueryException("Cannot cast column '{$col}' value '{$value}' to timestamp.");
        }
    }
}
