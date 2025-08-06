<?php

namespace App\Modules\User\Services;

use App\Core\Base\BaseService;
use App\Core\Exceptions\CoreException;
use App\Modules\User\DTOs\CreateUserDTO;
use App\Modules\User\DTOs\UpdateUserDTO;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Interfaces\UserServiceInterface;
use App\Modules\User\Models\User;
use App\Modules\User\Events\UserCreated;
use App\Modules\User\Events\UserUpdated;
use App\Modules\User\Events\UserDeleted;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * User Service Implementation
 * Handles business logic for User module
 */
class UserService extends BaseService implements UserServiceInterface
{
    protected UserRepositoryInterface $userRepository;

    /**
     * Constructor
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->repository = $userRepository;
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = $this->userRepository->with(['role', 'pegawai', 'workLocation']);

            // Apply filters
            if (isset($filters['role'])) {
                $query->where('role_id', $filters['role']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to get users');
            throw new CoreException('Failed to retrieve users', 500);
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User
    {
        try {
            return $this->userRepository->with(['role', 'pegawai', 'workLocation'])->find($id);
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to get user by ID');
            throw new CoreException('Failed to retrieve user', 500);
        }
    }

    /**
     * Create new user
     */
    public function createUser(CreateUserDTO $dto): User
    {
        // Validate DTO
        if (!$dto->isValid()) {
            throw new CoreException('Invalid user data', 422, $dto->validate());
        }

        // Check if email already exists
        if ($this->userRepository->emailExists($dto->email)) {
            throw new CoreException('Email already exists', 422, ['email' => 'Email is already taken']);
        }

        // Check if username already exists
        if ($dto->username && $this->userRepository->usernameExists($dto->username)) {
            throw new CoreException('Username already exists', 422, ['username' => 'Username is already taken']);
        }

        try {
            return $this->executeInTransaction(function () use ($dto) {
                // Create user
                $user = $this->userRepository->create($dto->toModelAttributes());

                // Assign role if provided
                if ($dto->role) {
                    $user->assignRole($dto->role);
                }

                // Log activity
                $this->logActivity('user_created', ['user_id' => $user->id]);

                // Dispatch event
                event(new UserCreated($user));

                return $user;
            });
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to create user');
            throw new CoreException('Failed to create user', 500);
        }
    }

    /**
     * Update existing user
     */
    public function updateUser(int $id, UpdateUserDTO $dto): User
    {
        // Validate DTO
        if (!$dto->isValid()) {
            throw new CoreException('Invalid user data', 422, $dto->validate());
        }

        // Check if user exists
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        // Check if email already exists (excluding current user)
        if ($dto->email && $this->userRepository->emailExists($dto->email, $id)) {
            throw new CoreException('Email already exists', 422, ['email' => 'Email is already taken']);
        }

        // Check if username already exists (excluding current user)
        if ($dto->username && $this->userRepository->usernameExists($dto->username, $id)) {
            throw new CoreException('Username already exists', 422, ['username' => 'Username is already taken']);
        }

        try {
            return $this->executeInTransaction(function () use ($id, $dto, $user) {
                // Update user
                $this->userRepository->update($id, $dto->toModelAttributes());
                
                // Refresh user model
                $user->refresh();

                // Log activity
                $this->logActivity('user_updated', ['user_id' => $user->id, 'changes' => $dto->toModelAttributes()]);

                // Dispatch event
                event(new UserUpdated($user));

                return $user;
            });
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to update user');
            throw new CoreException('Failed to update user', 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): bool
    {
        // Check if user exists
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        // Prevent deleting own account
        if (auth()->id() === $id) {
            throw new CoreException('Cannot delete your own account', 403);
        }

        try {
            return $this->executeInTransaction(function () use ($id, $user) {
                // Soft delete user
                $result = $this->userRepository->softDelete($id);

                // Log activity
                $this->logActivity('user_deleted', ['user_id' => $id]);

                // Dispatch event
                event(new UserDeleted($user));

                return $result;
            });
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to delete user');
            throw new CoreException('Failed to delete user', 500);
        }
    }

    /**
     * Authenticate user
     */
    public function authenticate(string $login, string $password): ?array
    {
        try {
            // Find user by email or username
            $user = $this->userRepository->findByEmail($login) 
                    ?? $this->userRepository->findByUsername($login);

            if (!$user) {
                return null;
            }

            // Check password
            if (!Hash::check($password, $user->password)) {
                return null;
            }

            // Check if user is active
            if (!$user->is_active) {
                throw new CoreException('User account is inactive', 403);
            }

            // Update last login
            $this->userRepository->updateLastLogin($user->id);

            // Create token
            $token = $user->createToken('api-token')->plainTextToken;

            // Log activity
            $this->logActivity('user_login', ['user_id' => $user->id]);

            return [
                'user' => $user,
                'token' => $token
            ];
        } catch (Exception $e) {
            $this->handleError($e, 'Authentication failed');
            throw new CoreException('Authentication failed', 401);
        }
    }

    /**
     * Logout user
     */
    public function logout(User $user): bool
    {
        try {
            // Revoke all tokens
            $user->tokens()->delete();

            // Log activity
            $this->logActivity('user_logout', ['user_id' => $user->id]);

            return true;
        } catch (Exception $e) {
            $this->handleError($e, 'Logout failed');
            throw new CoreException('Logout failed', 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            throw new CoreException('Current password is incorrect', 422);
        }

        try {
            return $this->executeInTransaction(function () use ($userId, $newPassword) {
                $result = $this->userRepository->update($userId, [
                    'password' => bcrypt($newPassword)
                ]);

                // Log activity
                $this->logActivity('password_changed', ['user_id' => $userId]);

                return $result;
            });
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to change password');
            throw new CoreException('Failed to change password', 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(int $userId, string $newPassword): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        try {
            return $this->executeInTransaction(function () use ($userId, $newPassword) {
                $result = $this->userRepository->update($userId, [
                    'password' => bcrypt($newPassword)
                ]);

                // Log activity
                $this->logActivity('password_reset', ['user_id' => $userId]);

                return $result;
            });
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to reset password');
            throw new CoreException('Failed to reset password', 500);
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, string $role): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        try {
            $user->assignRole($role);
            
            // Log activity
            $this->logActivity('role_assigned', ['user_id' => $userId, 'role' => $role]);
            
            return true;
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to assign role');
            throw new CoreException('Failed to assign role', 500);
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId, string $role): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        try {
            $user->removeRole($role);
            
            // Log activity
            $this->logActivity('role_removed', ['user_id' => $userId, 'role' => $role]);
            
            return true;
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to remove role');
            throw new CoreException('Failed to remove role', 500);
        }
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): Collection
    {
        try {
            return $this->userRepository->findByRole($role);
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to get users by role');
            throw new CoreException('Failed to get users by role', 500);
        }
    }

    /**
     * Activate user
     */
    public function activateUser(int $userId): bool
    {
        try {
            return $this->userRepository->update($userId, ['is_active' => true]);
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to activate user');
            throw new CoreException('Failed to activate user', 500);
        }
    }

    /**
     * Deactivate user
     */
    public function deactivateUser(int $userId): bool
    {
        try {
            return $this->userRepository->update($userId, ['is_active' => false]);
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to deactivate user');
            throw new CoreException('Failed to deactivate user', 500);
        }
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        try {
            return $user->getAllPermissions()->pluck('name')->toArray();
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to get user permissions');
            throw new CoreException('Failed to get user permissions', 500);
        }
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new CoreException('User not found', 404);
        }

        try {
            return $user->can($permission);
        } catch (Exception $e) {
            $this->handleError($e, 'Failed to check user permission');
            throw new CoreException('Failed to check user permission', 500);
        }
    }
}