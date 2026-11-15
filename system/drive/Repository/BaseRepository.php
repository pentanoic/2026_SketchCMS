<?php
/**
 * BaseRepository - Base Fluent Repository for SketchCMS
 */

abstract class BaseRepository
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    protected function query(): QueryBuilder
    {
        return Database::table($this->table);
    }

    public function find(mixed $id): ?array
    {
        return $this->query()->where($this->primaryKey, $id)->first();
    }

    public function all(): array
    {
        return $this->query()->get();
    }

    public function create(array $data): int
    {
        return $this->query()->insert($data);
    }

    public function updateById(mixed $id, array $data): int
    {
        return $this->query()->where($this->primaryKey, $id)->update($data);
    }

    public function deleteById(mixed $id): int
    {
        return $this->query()->where($this->primaryKey, $id)->delete();
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        return $this->query()->paginate($page, $perPage);
    }
}
