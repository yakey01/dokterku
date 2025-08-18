<?php

namespace App\Repositories\Admin;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Admin User Repository
 * 
 * Database abstraction layer for admin user operations
 * with optimized queries and consistent data access patterns.
 */
class AdminUserRepository
{
    /**
     * Get user by ID with relationships
     *
     * @param int $id
     * @param array $relations
     * @return User|null
     */
    public function findById(int $id, array $relations = ['role']): ?User
    {
        return User::with($relations)->find($id);
    }

    /**
     * Get user by email with relationships
     *
     * @param string $email
     * @param array $relations
     * @return User|null
     */
    public function findByEmail(string $email, array $relations = ['role']): ?User
    {
        return User::with($relations)->where('email', $email)->first();
    }

    /**
     * Get user by NIP with relationships
     *
     * @param string $nip
     * @param array $relations
     * @return User|null
     */
    public function findByNip(string $nip, array $relations = ['role']): ?User
    {
        return User::with($relations)->where('nip', $nip)->first();
    }

    /**
     * Get paginated users with optional filtering
     *
     * @param int $perPage
     * @param array $filters
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $relations = ['role']): LengthAwarePaginator
    {
        $query = User::with($relations);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all users with relationships
     *
     * @param array $relations
     * @return Collection
     */
    public function getAll(array $relations = ['role']): Collection
    {
        return User::with($relations)->orderBy('name')->get();
    }

    /**
     * Search users by multiple criteria
     *
     * @param string $searchTerm
     * @param array $searchFields
     * @param array $relations
     * @param int $limit
     * @return Collection
     */
    public function search(string $searchTerm, array $searchFields = ['name', 'email', 'nip'], array $relations = ['role'], int $limit = 20): Collection
    {
        $query = User::with($relations);

        $query->where(function (Builder $q) use ($searchTerm, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', '%' . $searchTerm . '%');
            }
        });

        return $query->limit($limit)->get();
    }

    /**
     * Get users by role
     *
     * @param string|int $role
     * @param array $relations
     * @return Collection
     */
    public function getByRole($role, array $relations = ['role']): Collection
    {
        $query = User::with($relations);

        if (is_string($role)) {
            $query->whereHas('role', function (Builder $q) use ($role) {
                $q->where('name', $role);
            });
        } else {
            $query->where('role_id', $role);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get users created within date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $relations
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate, array $relations = ['role']): Collection
    {
        return User::with($relations)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recently created users
     *
     * @param int $days
     * @param int $limit
     * @param array $relations
     * @return Collection
     */
    public function getRecent(int $days = 7, int $limit = 10, array $relations = ['role']): Collection
    {
        return User::with($relations)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active users (verified email)
     *
     * @param array $relations
     * @return Collection
     */
    public function getActive(array $relations = ['role']): Collection
    {
        return User::with($relations)
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get inactive users (unverified email)
     *
     * @param array $relations
     * @return Collection
     */
    public function getInactive(array $relations = ['role']): Collection
    {
        return User::with($relations)
            ->whereNull('email_verified_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create new user
     *
     * @param array $userData
     * @return User
     */
    public function create(array $userData): User
    {
        return User::create($userData);
    }

    /**
     * Update user
     *
     * @param User $user
     * @param array $userData
     * @return bool
     */
    public function update(User $user, array $userData): bool
    {
        return $user->update($userData);
    }

    /**
     * Delete user (soft delete)
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Permanently delete user
     *
     * @param User $user
     * @return bool
     */
    public function forceDelete(User $user): bool
    {
        return $user->forceDelete();
    }

    /**
     * Restore soft deleted user
     *
     * @param User $user
     * @return bool
     */
    public function restore(User $user): bool
    {
        return $user->restore();
    }

    /**
     * Get user statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::whereNotNull('email_verified_at')->count(),
            'inactive' => User::whereNull('email_verified_at')->count(),
            'recent' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'soft_deleted' => User::onlyTrashed()->count(),
            'by_role' => $this->getUserCountByRole()
        ];
    }

    /**
     * Get user count grouped by role
     *
     * @return array
     */
    public function getUserCountByRole(): array
    {
        return User::select('role_id')
            ->selectRaw('count(*) as count')
            ->with('role:id,name')
            ->groupBy('role_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->role->name ?? 'Unknown' => $item->count];
            })
            ->toArray();
    }

    /**
     * Check if email exists
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = User::where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if NIP exists
     *
     * @param string $nip
     * @param int|null $excludeId
     * @return bool
     */
    public function nipExists(string $nip, ?int $excludeId = null): bool
    {
        $query = User::where('nip', $nip);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Bulk update users
     *
     * @param array $userIds
     * @param array $updateData
     * @return int
     */
    public function bulkUpdate(array $userIds, array $updateData): int
    {
        return User::whereIn('id', $userIds)->update($updateData);
    }

    /**
     * Bulk delete users
     *
     * @param array $userIds
     * @return int
     */
    public function bulkDelete(array $userIds): int
    {
        return User::whereIn('id', $userIds)->delete();
    }

    /**
     * Get users with specific permissions
     *
     * @param string $permission
     * @param array $relations
     * @return Collection
     */
    public function getByPermission(string $permission, array $relations = ['role']): Collection
    {
        return User::with($relations)
            ->whereHas('role', function (Builder $q) use ($permission) {
                $q->whereHas('permissions', function (Builder $q2) use ($permission) {
                    $q2->where('name', $permission);
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get monthly user registration counts
     *
     * @param int $months
     * @return array
     */
    public function getMonthlyRegistrations(int $months = 12): array
    {
        $registrations = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = User::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $registrations[] = [
                'month' => $date->format('M Y'),
                'count' => $count
            ];
        }
        
        return $registrations;
    }

    /**
     * Apply filters to query
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%')
                  ->orWhere('nip', 'like', '%' . $searchTerm . '%');
            });
        }

        // Role filter
        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'inactive':
                    $query->whereNull('email_verified_at');
                    break;
                case 'trashed':
                    $query->onlyTrashed();
                    break;
            }
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Sort filter
        if (!empty($filters['sort_by'])) {
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $sortDirection);
        }
    }

    /**
     * Get users with their last login information
     *
     * @param int $limit
     * @return Collection
     */
    public function getUsersWithLastLogin(int $limit = 20): Collection
    {
        return User::with('role')
            ->select(['id', 'name', 'email', 'role_id', 'last_login_at', 'created_at'])
            ->orderBy('last_login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get users who haven't logged in for specified days
     *
     * @param int $days
     * @return Collection
     */
    public function getInactiveUsers(int $days = 30): Collection
    {
        return User::with('role')
            ->where(function (Builder $q) use ($days) {
                $q->where('last_login_at', '<', now()->subDays($days))
                  ->orWhereNull('last_login_at');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}