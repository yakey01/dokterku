<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Admin User Management Service
 * 
 * Centralizes all user management operations for admin panel
 * with enhanced security, logging, and performance optimization.
 */
class AdminUserManagementService
{
    /**
     * Get paginated users with relationships
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['role']);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('nip', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereNull('deleted_at');
            } elseif ($filters['status'] === 'inactive') {
                $query->whereNotNull('deleted_at');
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new user with validation and audit logging
     *
     * @param array $userData
     * @return User
     * @throws Exception
     */
    public function createUser(array $userData): User
    {
        DB::beginTransaction();
        
        try {
            // Validate role exists
            $role = Role::findOrFail($userData['role_id']);

            // Hash password
            $userData['password'] = Hash::make($userData['password']);

            // Generate unique NIP if not provided
            if (empty($userData['nip'])) {
                $userData['nip'] = $this->generateUniqueNip();
            }

            $user = User::create($userData);

            // Clear user-related cache
            $this->clearUserCache();

            // Log activity
            Log::info('Admin created user', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
                'role' => $role->name
            ]);

            DB::commit();
            return $user;

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to create user', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $userData
            ]);
            throw $e;
        }
    }

    /**
     * Update user with validation and audit logging
     *
     * @param User $user
     * @param array $userData
     * @return User
     * @throws Exception
     */
    public function updateUser(User $user, array $userData): User
    {
        DB::beginTransaction();
        
        try {
            $originalData = $user->toArray();

            // Validate role exists if being updated
            if (!empty($userData['role_id'])) {
                $role = Role::findOrFail($userData['role_id']);
            }

            // Hash password if provided
            if (!empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                unset($userData['password']);
            }

            $user->update($userData);

            // Clear user-related cache
            $this->clearUserCache();

            // Log activity
            Log::info('Admin updated user', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'updated_user_id' => $user->id,
                'updated_user_email' => $user->email,
                'changes' => array_diff_assoc($userData, $originalData)
            ]);

            DB::commit();
            return $user->fresh(['role']);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to update user', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $userData
            ]);
            throw $e;
        }
    }

    /**
     * Soft delete user with audit logging
     *
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function deleteUser(User $user): bool
    {
        DB::beginTransaction();
        
        try {
            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                throw new Exception('Cannot delete your own account');
            }

            // Prevent deletion of super admin
            if ($user->hasRole('super_admin')) {
                throw new Exception('Cannot delete super admin account');
            }

            $user->delete();

            // Clear user-related cache
            $this->clearUserCache();

            // Log activity
            Log::info('Admin deleted user', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
                'deleted_user_name' => $user->name
            ]);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to delete user', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk operations for users
     *
     * @param array $userIds
     * @param string $action
     * @param array $data
     * @return array
     */
    public function bulkAction(array $userIds, string $action, array $data = []): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($userIds as $userId) {
            try {
                $user = User::findOrFail($userId);
                
                switch ($action) {
                    case 'delete':
                        $this->deleteUser($user);
                        break;
                    case 'activate':
                        $user->update(['email_verified_at' => now()]);
                        break;
                    case 'deactivate':
                        $user->update(['email_verified_at' => null]);
                        break;
                    case 'change_role':
                        $this->updateUser($user, ['role_id' => $data['role_id']]);
                        break;
                    default:
                        throw new Exception("Unknown action: {$action}");
                }
                
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][$userId] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get user statistics for admin dashboard
     *
     * @return array
     */
    public function getUserStatistics(): array
    {
        return Cache::remember('admin.user.statistics', 300, function () {
            return [
                'total_users' => User::count(),
                'active_users' => User::whereNotNull('email_verified_at')->count(),
                'inactive_users' => User::whereNull('email_verified_at')->count(),
                'recent_users' => User::where('created_at', '>=', now()->subDays(7))->count(),
                'users_by_role' => User::select('role_id')
                    ->selectRaw('count(*) as count')
                    ->with('role:id,name')
                    ->groupBy('role_id')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->role->name ?? 'Unknown' => $item->count];
                    })
                    ->toArray()
            ];
        });
    }

    /**
     * Get recent user activities
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentUsers(int $limit = 5): Collection
    {
        return User::with(['role'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Search users with advanced filters
     *
     * @param string $term
     * @param array $filters
     * @return Collection
     */
    public function searchUsers(string $term, array $filters = []): Collection
    {
        $query = User::with(['role'])
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%')
                  ->orWhere('nip', 'like', '%' . $term . '%');
            });

        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        return $query->limit(20)->get();
    }

    /**
     * Generate unique NIP for user
     *
     * @return string
     */
    private function generateUniqueNip(): string
    {
        do {
            $nip = 'USR' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (User::where('nip', $nip)->exists());

        return $nip;
    }

    /**
     * Clear user-related cache
     *
     * @return void
     */
    private function clearUserCache(): void
    {
        Cache::forget('admin.user.statistics');
        Cache::tags(['users'])->flush();
    }

    /**
     * Validate user data for business rules
     *
     * @param array $userData
     * @param User|null $existingUser
     * @return array
     */
    public function validateUserData(array $userData, ?User $existingUser = null): array
    {
        $errors = [];

        // Check for duplicate email
        $emailQuery = User::where('email', $userData['email']);
        if ($existingUser) {
            $emailQuery->where('id', '!=', $existingUser->id);
        }
        if ($emailQuery->exists()) {
            $errors['email'] = 'Email already exists';
        }

        // Check for duplicate NIP if provided
        if (!empty($userData['nip'])) {
            $nipQuery = User::where('nip', $userData['nip']);
            if ($existingUser) {
                $nipQuery->where('id', '!=', $existingUser->id);
            }
            if ($nipQuery->exists()) {
                $errors['nip'] = 'NIP already exists';
            }
        }

        return $errors;
    }
}