<?php

namespace App\Modules\User\Controllers;

use App\Core\Base\BaseController;
use App\Modules\User\DTOs\CreateUserDTO;
use App\Modules\User\Interfaces\UserServiceInterface;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Exception;

/**
 * Authentication Controller
 * Handles authentication operations
 */
class AuthController extends BaseController
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
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'login' => 'required|string', // email or username
                'password' => 'required|string',
                'device_name' => 'nullable|string',
            ]);

            $authData = $this->userService->authenticate(
                $request->login,
                $request->password
            );

            if (!$authData) {
                return $this->unauthorized('Invalid credentials');
            }

            return $this->success([
                'user' => new UserResource($authData['user']),
                'token' => $authData['token'],
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Register new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'nullable|string|unique:users,username',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
            ]);

            $dto = CreateUserDTO::fromArray($request->all());
            $user = $this->userService->createUser($dto);

            // Auto-login after registration
            $authData = $this->userService->authenticate(
                $request->email,
                $request->password
            );

            return $this->created([
                'user' => new UserResource($user),
                'token' => $authData['token'],
                'token_type' => 'Bearer',
            ], 'Registration successful');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->userService->logout($user);

            return $this->success(null, 'Logout successful');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['role', 'pegawai', 'workLocation']);

            return $this->success(
                new UserResource($user),
                'User retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $user->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('api-token')->plainTextToken;

            return $this->success([
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return $this->success(null, 'Password reset link sent to your email');
            }

            return $this->error('Unable to send password reset link');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $this->userService->resetPassword($user->id, $password);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->success(null, 'Password reset successful');
            }

            return $this->error('Unable to reset password');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}