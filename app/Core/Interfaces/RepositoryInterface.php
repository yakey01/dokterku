<?php

namespace App\Core\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface following SOLID principles
 * All repository implementations should extend this interface
 */
interface RepositoryInterface
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find record by ID or throw exception
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find record by attribute
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): ?Model;

    /**
     * Find multiple records by attribute
     */
    public function findManyBy(string $attribute, mixed $value, array $columns = ['*']): Collection;

    /**
     * Create new record
     */
    public function create(array $data): Model;

    /**
     * Update existing record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete record
     */
    public function delete(int $id): bool;

    /**
     * Get records with relationships
     */
    public function with(array $relations): self;

    /**
     * Apply where condition
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): self;

    /**
     * Order records
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * Get first record
     */
    public function first(array $columns = ['*']): ?Model;

    /**
     * Check if record exists
     */
    public function exists(): bool;

    /**
     * Count records
     */
    public function count(): int;
}