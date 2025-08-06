<?php

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\ServiceInterface;
use App\Modules\User\DTOs\CreateUserDTO;
use App\Modules\User\DTOs\UpdateUserDTO;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User Service Interface
 * Defines business logic operations for User module
 */
interface UserServiceInterface extends ServiceInterface
{
    /**
     * Get all users with pagination
     */
    public function getAllUsers(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User;

    /**
     * Create new user
     */
    public function createUser(CreateUserDTO $dto): User;

    /**
     * Update existing user
     */
    public function updateUser(int $id, UpdateUserDTO $dto): User;

    /**
     * Delete user
     */
    public function deleteUser(int $id): bool;

    /**
     * Authenticate user
     */
    public function authenticate(string $login, string $password): ?array;

    /**
     * Logout user
     */
    public function logout(User $user): bool;

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool;

    /**
     * Reset user password
     */
    public function resetPassword(int $userId, string $newPassword): bool;

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, string $role): bool;

    /**
     * Remove role from user
     */
    public function removeRole(int $userId, string $role): bool;

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): Collection;

    /**
     * Activate user
     */
    public function activateUser(int $userId): bool;

    /**
     * Deactivate user
     */
    public function deactivateUser(int $userId): bool;

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array;

    /**
     * Check if user has permission
     */
    public function hasPermission(int $userId, string $permission): bool;
}