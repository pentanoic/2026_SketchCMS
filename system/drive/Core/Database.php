<?php
/**
 * Database - Fluent Database Facade for SketchCMS
 */

class Database
{
    public static function driver(): DriverInterface
    {
        $connection = strtolower((string) Env::get('DB_CONNECTION', 'mysql'));
        return $connection === 'sqlite' ? new SQLiteDriver() : new MySQLDriver();
    }

    public static function pdo(): PDO
    {
        return Container::get(DB::class);
    }

    public static function table(string $table): QueryBuilder
    {
        return (new QueryBuilder(self::pdo(), self::driver()))->table($table);
    }

    public static function raw(string $expression): Raw
    {
        return new Raw($expression);
    }

    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $val) {
            $param = is_int($key) ? $key + 1 : (strpos((string)$key, ':') === 0 ? $key : ':' . $key);
            $stmt->bindValue($param, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
