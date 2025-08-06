<?php

namespace App\Core\Base;

use App\Core\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository implementing common database operations
 * Following Repository Pattern and SOLID principles
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected Builder $query;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * Reset query builder
     */
    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        $result = $this->query->find($id, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find record by ID or throw exception
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        $result = $this->query->findOrFail($id, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find record by attribute
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): ?Model
    {
        $result = $this->query->where($attribute, $value)->first($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Find multiple records by attribute
     */
    public function findManyBy(string $attribute, mixed $value, array $columns = ['*']): Collection
    {
        $result = $this->query->where($attribute, $value)->get($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update existing record
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->findOrFail($id);
        return $record->update($data);
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        $record = $this->findOrFail($id);
        return $record->delete();
    }

    /**
     * Get records with relationships
     */
    public function with(array $relations): self
    {
        $this->query = $this->query->with($relations);
        return $this;
    }

    /**
     * Apply where condition
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        $this->query = $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Order records
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query = $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Get first record
     */
    public function first(array $columns = ['*']): ?Model
    {
        $result = $this->query->first($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * Check if record exists
     */
    public function exists(): bool
    {
        $result = $this->query->exists();
        $this->resetQuery();
        return $result;
    }

    /**
     * Count records
     */
    public function count(): int
    {
        $result = $this->query->count();
        $this->resetQuery();
        return $result;
    }

    /**
     * Begin database transaction
     */
    public function beginTransaction(): void
    {
        \DB::beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public function commit(): void
    {
        \DB::commit();
    }

    /**
     * Rollback database transaction
     */
    public function rollback(): void
    {
        \DB::rollBack();
    }
}