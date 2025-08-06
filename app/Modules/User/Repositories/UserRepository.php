<?php

namespace App\Modules\User\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Repository Implementation
 * Handles all database operations for User entity
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User
    {
        return $this->model->where('username', $username)->first();
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role): Collection
    {
        return $this->model->role($role)->get();
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get inactive users
     */
    public function getInactiveUsers(): Collection
    {
        return $this->model->where('is_active', false)->get();
    }

    /**
     * Update user's last login
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->model
            ->where('id', $userId)
            ->update(['last_login_at' => now()]);
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = $this->model->where('username', $username);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get users with specific permissions
     */
    public function getUsersWithPermission(string $permission): Collection
    {
        return $this->model->permission($permission)->get();
    }

    /**
     * Soft delete user
     */
    public function softDelete(int $id): bool
    {
        $user = $this->findOrFail($id);
        return $user->delete();
    }

    /**
     * Restore soft deleted user
     */
    public function restore(int $id): bool
    {
        $user = $this->model->withTrashed()->findOrFail($id);
        return $user->restore();
    }

    /**
     * Get users with filters
     */
    public function getUsersWithFilters(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (isset($filters['role'])) {
            $query->whereHas('role', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['work_location_id'])) {
            $query->where('work_location_id', $filters['work_location_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if (isset($filters['order_by'])) {
            $direction = $filters['order_direction'] ?? 'asc';
            $query->orderBy($filters['order_by'], $direction);
        } else {
            $query->latest();
        }

        return $query->get();
    }
}