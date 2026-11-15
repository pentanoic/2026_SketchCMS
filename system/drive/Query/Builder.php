<?php
/**
 * QueryBuilder - Complete Fluent SQL Query Builder (MySQL & SQLite)
 */

class QueryBuilder
{
    private PDO $pdo;
    private DriverInterface $driver;
    private string $table = '';
    private array $columns = ['*'];
    private array $joins = [];
    private array $wheres = [];
    private array $bindings = [];
    private array $groups = [];
    private array $havings = [];
    private array $havingBindings = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(PDO $pdo, DriverInterface $driver)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array|string $columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->columns = [new Raw($expression)];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $type = strtoupper($type);
        $this->joins[] = "{$type} JOIN " . $this->quoteCol($table) . ' ON ' . $this->quoteCol($first) . " {$operator} " . $this->quoteCol($second);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function leftJoinRaw(string $clause): self
    {
        $this->joins[] = 'LEFT JOIN ' . $clause;
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->addWhere('AND', $column, $operatorOrValue, $value);
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->addWhere('OR', $column, $operatorOrValue, $value);
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = ['AND', $sql];
        foreach ($bindings as $b) {
            $this->bindings[] = $b;
        }
        return $this;
    }

    public function orWhereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = ['OR', $sql];
        foreach ($bindings as $b) {
            $this->bindings[] = $b;
        }
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = ['AND', '1 = 0'];
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['AND', $this->quoteCol($column) . " IN ({$placeholders})"];
        foreach ($values as $v) {
            $this->bindings[] = $v;
        }
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = ['AND', $this->quoteCol($column) . ' IS NULL'];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = ['AND', $this->quoteCol($column) . ' IS NOT NULL'];
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $col) {
            $this->groups[] = $this->quoteCol($col);
        }
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $this->havings[] = $this->quoteCol($column) . " {$operator} ?";
        $this->havingBindings[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $this->quoteCol($column) . ' ' . $dir;
        return $this;
    }

    public function orderByRaw(string $sql): self
    {
        $this->orders[] = $sql;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    public function get(): array
    {
        $sql = $this->compileSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($this->bindings, $this->havingBindings));
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function value(string $column): mixed
    {
        $this->select([$column]);
        $row = $this->first();
        return $row[$column] ?? null;
    }

    public function pluck(string $column): array
    {
        $this->select([$column]);
        return array_column($this->get(), $column);
    }

    public function exists(): bool
    {
        return $this->first() !== null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->quoteCol($this->table) . $this->compileJoins() . $this->compileWhere();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return (int) $stmt->fetchColumn();
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = $this->count();
        $items = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function insert(array $data): int
    {
        $cols = array_keys($data);
        $colList = implode(', ', array_map(fn($c) => $this->driver->quoteIdentifier($c), $cols));
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . $this->driver->quoteIdentifier($this->table) . " ({$colList}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        $sets = [];
        $vals = [];
        foreach ($data as $col => $val) {
            if ($val instanceof Raw) {
                $sets[] = $this->driver->quoteIdentifier($col) . ' = ' . $val->getValue();
            } else {
                $sets[] = $this->driver->quoteIdentifier($col) . ' = ?';
                $vals[] = $val;
            }
        }
        $sql = 'UPDATE ' . $this->driver->quoteIdentifier($this->table) . ' SET ' . implode(', ', $sets) . $this->compileWhere();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($vals, $this->bindings));
        return $stmt->rowCount();
    }

    public function increment(string $column, int $amount = 1): int
    {
        $col = $this->driver->quoteIdentifier($column);
        return $this->update([$column => new Raw("{$col} + " . (int) $amount)]);
    }

    public function decrement(string $column, int $amount = 1): int
    {
        $col = $this->driver->quoteIdentifier($column);
        return $this->update([$column => new Raw("{$col} - " . (int) $amount)]);
    }

    public function delete(): int
    {
        $sql = 'DELETE FROM ' . $this->driver->quoteIdentifier($this->table) . $this->compileWhere();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->rowCount();
    }

    private function addWhere(string $boolean, string $column, mixed $operatorOrValue, mixed $value): self
    {
        if ($value === null) {
            $operator = '=';
            $val = $operatorOrValue;
        } else {
            $operator = strtoupper((string) $operatorOrValue);
            $val = $value;
        }
        $this->wheres[] = [$boolean, $this->quoteCol($column) . " {$operator} ?"];
        $this->bindings[] = $val;
        return $this;
    }

    private function quoteCol(mixed $col): string
    {
        if ($col instanceof Raw) {
            return $col->getValue();
        }
        $col = (string) $col;
        if ($col === '*') {
            return '*';
        }
        if (strpos($col, '.') !== false) {
            return implode('.', array_map(fn($p) => $p === '*' ? '*' : $this->driver->quoteIdentifier($p), explode('.', $col)));
        }
        return $this->driver->quoteIdentifier($col);
    }

    private function compileSelect(): string
    {
        $cols = $this->columns === ['*']
            ? '*'
            : implode(', ', array_map(fn($c) => $c instanceof Raw ? $c->getValue() : $this->quoteCol($c), $this->columns));

        $sql = 'SELECT ' . $cols . ' FROM ' . $this->quoteCol($this->table) . $this->compileJoins() . $this->compileWhere();
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
        }
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' ' . $this->driver->compileLimit($this->limit, $this->offset);
        }
        return $sql;
    }

    private function compileJoins(): string
    {
        return empty($this->joins) ? '' : ' ' . implode(' ', $this->joins);
    }

    private function compileWhere(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        $parts = [];
        foreach ($this->wheres as $i => [$boolean, $clause]) {
            $parts[] = ($i === 0 ? '' : "{$boolean} ") . $clause;
        }
        return ' WHERE ' . implode(' ', $parts);
    }
}
