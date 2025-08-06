<?php

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\RepositoryInterface;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Repository Interface
 * Extends base repository with user-specific methods
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User;

    /**
     * Find users by role
     */
    public function findByRole(string $role): Collection;

    /**
     * Get active users
     */
    public function getActiveUsers(): Collection;

    /**
     * Get inactive users
     */
    public function getInactiveUsers(): Collection;

    /**
     * Update user's last login
     */
    public function updateLastLogin(int $userId): bool;

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool;

    /**
     * Get users with specific permissions
     */
    public function getUsersWithPermission(string $permission): Collection;

    /**
     * Soft delete user
     */
    public function softDelete(int $id): bool;

    /**
     * Restore soft deleted user
     */
    public function restore(int $id): bool;
}