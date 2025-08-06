<?php

namespace App\Modules\User\Controllers;

use App\Core\Base\BaseController;
use App\Modules\User\DTOs\CreateUserDTO;
use App\Modules\User\DTOs\UpdateUserDTO;
use App\Modules\User\Interfaces\UserServiceInterface;
use App\Modules\User\Requests\CreateUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

/**
 * User Controller
 * Handles HTTP requests for User module
 */
class UserController extends BaseController
{
    protected UserServiceInterface $userService;

    /**
     * Constructor
     */
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['role', 'is_active', 'search']);
            
            $users = $this->userService->getAllUsers($perPage, $filters);
            
            return $this->paginated($users, 'Users retrieved successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Store a newly created user
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $dto = CreateUserDTO::fromArray($request->validated());
            $user = $this->userService->createUser($dto);
            
            return $this->created(
                new UserResource($user),
                'User created successfully'
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Display the specified user
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return $this->notFound('User not found');
            }
            
            return $this->success(
                new UserResource($user),
                'User retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $dto = UpdateUserDTO::fromArray($request->validated());
            $user = $this->userService->updateUser($id, $dto);
            
            return $this->success(
                new UserResource($user),
                'User updated successfully'
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->userService->deleteUser($id);
            
            if ($result) {
                return $this->success(null, 'User deleted successfully');
            }
            
            return $this->error('Failed to delete user');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Activate user
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $result = $this->userService->activateUser($id);
            
            if ($result) {
                return $this->success(null, 'User activated successfully');
            }
            
            return $this->error('Failed to activate user');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Deactivate user
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $result = $this->userService->deactivateUser($id);
            
            if ($result) {
                return $this->success(null, 'User deactivated successfully');
            }
            
            return $this->error('Failed to deactivate user');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get user permissions
     */
    public function permissions(int $id): JsonResponse
    {
        try {
            $permissions = $this->userService->getUserPermissions($id);
            
            return $this->success(
                $permissions,
                'User permissions retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['role' => 'required|string']);
            
            $result = $this->userService->assignRole($id, $request->role);
            
            if ($result) {
                return $this->success(null, 'Role assigned successfully');
            }
            
            return $this->error('Failed to assign role');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['role' => 'required|string']);
            
            $result = $this->userService->removeRole($id, $request->role);
            
            if ($result) {
                return $this->success(null, 'Role removed successfully');
            }
            
            return $this->error('Failed to remove role');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);
            
            $result = $this->userService->changePassword(
                $id,
                $request->current_password,
                $request->new_password
            );
            
            if ($result) {
                return $this->success(null, 'Password changed successfully');
            }
            
            return $this->error('Failed to change password');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        try {
            // Check permission
            $this->authorizeAction('reset-user-password');
            
            $request->validate([
                'new_password' => 'required|string|min:8',
            ]);
            
            $result = $this->userService->resetPassword($id, $request->new_password);
            
            if ($result) {
                return $this->success(null, 'Password reset successfully');
            }
            
            return $this->error('Failed to reset password');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}