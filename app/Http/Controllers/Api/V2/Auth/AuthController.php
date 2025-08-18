<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\TokenService;
use App\Services\BiometricService;
use App\Services\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints for login, logout, and user management"
 * )
 */
class AuthController extends BaseApiController
{
    protected TokenService $tokenService;
    protected BiometricService $biometricService;
    protected SessionManager $sessionManager;

    public function __construct(
        TokenService $tokenService,
        BiometricService $biometricService,
        SessionManager $sessionManager
    ) {
        $this->tokenService = $tokenService;
        $this->biometricService = $biometricService;
        $this->sessionManager = $sessionManager;
    }
    /**
     * @OA\Post(
     *     path="/api/v2/auth/login",
     *     summary="User login with email/username",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "password"},
     *             @OA\Property(property="login", type="string", example="user@example.com", description="Email or username"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 13", description="Device name for token"),
     *             @OA\Property(property="device_fingerprint", type="string", description="Unique device identifier"),
     *             @OA\Property(property="fcm_token", type="string", description="Firebase Cloud Messaging token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login berhasil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=43200)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
            'device_id' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|in:mobile,tablet,desktop',
            'platform' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'client_type' => 'nullable|string|in:mobile_app,web_app,api_client',
            'push_token' => 'nullable|string',
            'biometric_data' => 'nullable|string',
            'biometric_type' => 'nullable|string|in:fingerprint,face,voice,iris',
            'location' => 'nullable|array',
            'location.latitude' => 'nullable|numeric|between:-90,90',
            'location.longitude' => 'nullable|numeric|between:-180,180',
            'location.country' => 'nullable|string|max:100',
            'location.city' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Determine if login is email or username
            $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            
            $credentials = [
                $loginField => $request->login,
                'password' => $request->password,
            ];

            if (!Auth::attempt($credentials)) {
                return $this->errorResponse(
                    'Invalid credentials', 
                    401, 
                    null, 
                    'INVALID_CREDENTIALS'
                );
            }

            $user = Auth::user();

            // Check if user is active
            if (!$user->is_active) {
                return $this->errorResponse(
                    'Account is disabled',
                    403,
                    null,
                    'ACCOUNT_DISABLED'
                );
            }

            // Extract device information
            $deviceInfo = UserDevice::extractDeviceInfo($request);

            // Register or update device
            $device = UserDevice::autoRegisterDevice($user->id, $deviceInfo);
            
            if (!$device) {
                return $this->errorResponse(
                    'Device registration failed. Maximum device limit reached.',
                    403,
                    null,
                    'DEVICE_LIMIT_EXCEEDED'
                );
            }

            // Get location data
            $locationData = $request->has('location') ? $request->location : [];

            // Create session data
            $sessionData = [
                'login_method' => 'password',
                'user_agent' => $request->userAgent(),
                'device_info' => $deviceInfo,
            ];

            // Handle biometric verification if provided
            if ($request->has('biometric_data') && $request->has('biometric_type')) {
                $biometricResult = $this->biometricService->verifyBiometric(
                    $user,
                    $request->biometric_type,
                    $request->biometric_data,
                    $device
                );

                $sessionData['biometric_verification'] = $biometricResult;
                $sessionData['login_method'] = 'password_biometric';
            }

            // Create authentication tokens
            $tokenData = $this->tokenService->createAuthTokens(
                $user,
                $device,
                $request->client_type ?? 'mobile_app',
                [], // Use default scopes
                $sessionData,
                $locationData
            );

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role?->name,
                    'phone' => $user->no_telepon,
                    'is_active' => $user->is_active,
                    'last_login' => now()->toISOString(),
                ],
                'authentication' => $tokenData,
                'device' => [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'is_primary' => $device->is_primary,
                    'status' => $device->status,
                    'biometric_enabled' => $device->biometric_enabled,
                    'security_score' => $device->security_score,
                ],
                'biometric_capabilities' => $this->biometricService->checkDeviceBiometricCapabilities($device),
            ], 'Login successful');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Login failed',
                500,
                null,
                'LOGIN_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/logout",
     *     summary="User logout",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout berhasil")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke current session and token
            $this->tokenService->revokeTokens($user, null, false, 'logout');

            return $this->successResponse(null, 'Logout successful');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Logout failed',
                500,
                null,
                'LOGOUT_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/change-password",
     *     summary="Change user password",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password"),
     *             @OA\Property(property="new_password", type="string", format="password"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password berhasil diubah")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Current password incorrect")
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation failed',
                    400,
                    $validator->errors(),
                    'VALIDATION_ERROR'
                );
            }

            $user = $request->user();
            
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse(
                    'Current password is incorrect',
                    401,
                    null,
                    'INVALID_CURRENT_PASSWORD'
                );
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->password_changed_at = now();
            $user->save();

            // Revoke all other tokens for security
            $this->tokenService->revokeTokens($user, $request->bearerToken(), true, 'password_change');

            return $this->successResponse(null, 'Password berhasil diubah');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Password change failed',
                500,
                null,
                'PASSWORD_CHANGE_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/setup-2fa",
     *     summary="Setup Two-Factor Authentication",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="2FA setup initiated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="2FA setup berhasil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="qr_code", type="string"),
     *                 @OA\Property(property="secret", type="string"),
     *                 @OA\Property(property="backup_codes", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function setup2FA(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $twoFactorService = app(\App\Services\TwoFactorAuthService::class);
            
            // Use proper TwoFactorAuthService instead of manual implementation
            $secret = $twoFactorService->generateSecret($user);
            $qrCode = $twoFactorService->generateQrCode($user, $secret);
            $backupCodes = $twoFactorService->getUnusedRecoveryCodes($user);
            
            // Ensure QR code is properly base64 encoded to avoid JSON parsing issues
            $qrCodeEncoded = base64_encode($qrCode);
            
            return $this->successResponse([
                'qr_code' => $qrCode,  // SVG QR code that can be scanned
                'qr_code_base64' => $qrCodeEncoded,  // Base64 encoded version as fallback
                'secret' => $secret,   // Google2FA compatible secret
                'backup_codes' => $backupCodes
            ], '2FA setup berhasil diinisialisasi');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('2FA Setup Error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                '2FA setup failed: ' . $e->getMessage(),
                500,
                null,
                'TWO_FACTOR_SETUP_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/verify-2fa",
     *     summary="Verify and enable 2FA",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     )
     * )
     */
    public function verify2FA(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation failed',
                    400,
                    $validator->errors(),
                    'VALIDATION_ERROR'
                );
            }

            $user = $request->user();
            $twoFactorService = app(\App\Services\TwoFactorAuthService::class);
            
            // Use REAL TOTP verification instead of accepting any 6-digit code
            $success = $twoFactorService->enable($user, $request->code);
            
            if ($success) {
                return $this->successResponse(null, '2FA berhasil diaktifkan');
            } else {
                return $this->errorResponse(
                    'Invalid verification code',
                    400,
                    null,
                    'INVALID_2FA_CODE'
                );
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('2FA Verification Error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                '2FA verification failed: ' . $e->getMessage(),
                500,
                null,
                'TWO_FACTOR_VERIFY_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/disable-2fa",
     *     summary="Disable Two-Factor Authentication",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     )
     * )
     */
    public function disable2FA(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation failed',
                    400,
                    $validator->errors(),
                    'VALIDATION_ERROR'
                );
            }

            $user = $request->user();
            
            // Verify password before disabling 2FA
            if (!Hash::check($request->password, $user->password)) {
                return $this->errorResponse(
                    'Password is incorrect',
                    401,
                    null,
                    'INVALID_PASSWORD'
                );
            }

            // Disable 2FA
            $user->two_factor_enabled = false;
            $user->two_factor_secret = null;
            $user->two_factor_backup_codes = null;
            $user->two_factor_verified_at = null;
            $user->save();

            return $this->successResponse(null, '2FA berhasil dinonaktifkan');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '2FA disable failed',
                500,
                null,
                'TWO_FACTOR_DISABLE_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/logout-all",
     *     summary="Logout from all devices",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout from all devices successful"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke all tokens and sessions
            $result = $this->tokenService->revokeTokens($user, null, true, 'logout_all');

            return $this->successResponse($result, 'Logout from all devices successful');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Logout from all devices failed',
                500,
                null,
                'LOGOUT_ALL_FAILED'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v2/auth/me",
     *     summary="Get current user profile",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role?->name,
                'phone' => $user->phone,
                'avatar' => $user->avatar_url,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at->toISOString(),
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ]
        ], 'User profile retrieved');
    }

    /**
     * @OA\Put(
     *     path="/api/v2/auth/profile",
     *     summary="Update user profile",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="phone", type="string", example="+628123456789"),
     *             @OA\Property(property="avatar", type="string", format="base64", description="Base64 encoded avatar image")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|string', // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $updateData = $request->only(['name', 'phone']);

        if ($request->has('avatar') && $request->avatar) {
            // Handle avatar upload (implement file storage logic)
            $updateData['avatar'] = $this->handleAvatarUpload($request->avatar, $user->id);
        }

        $user->update($updateData);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role?->name,
                'phone' => $user->phone,
                'avatar' => $user->avatar_url,
                'is_active' => $user->is_active,
            ]
        ], 'Profile updated successfully');
    }


    /**
     * @OA\Post(
     *     path="/api/v2/auth/refresh",
     *     summary="Refresh access token using refresh token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", description="Refresh token"),
     *             @OA\Property(property="device_id", type="string", description="Device ID for validation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer"),
     *                 @OA\Property(property="expires_at", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid refresh token"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Find device if device_id provided
            $device = null;
            if ($request->device_id) {
                $device = UserDevice::where('device_id', $request->device_id)->first();
            }

            // Refresh the token
            $tokenData = $this->tokenService->refreshAccessToken(
                $request->refresh_token,
                $device
            );

            return $this->successResponse($tokenData, 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to refresh token',
                401,
                null,
                'REFRESH_TOKEN_INVALID'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v2/auth/sessions",
     *     summary="Get active user sessions",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active sessions retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getSessions(Request $request): JsonResponse
    {
        try {
            $sessions = $this->sessionManager->getActiveSessions($request->user());
            $statistics = $this->sessionManager->getSessionStatistics($request->user());

            return $this->successResponse([
                'sessions' => $sessions,
                'statistics' => $statistics,
            ], 'Active sessions retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve sessions',
                500,
                null,
                'SESSION_RETRIEVAL_FAILED'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/auth/sessions/{session_id}",
     *     summary="End specific session",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="session_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session ended successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function endSession(Request $request, string $sessionId): JsonResponse
    {
        try {
            $ended = $this->sessionManager->endSession($sessionId, 'user_request');

            if (!$ended) {
                return $this->errorResponse(
                    'Session not found',
                    404,
                    null,
                    'SESSION_NOT_FOUND'
                );
            }

            return $this->successResponse(null, 'Session ended successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to end session',
                500,
                null,
                'SESSION_END_FAILED'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v2/devices",
     *     summary="Get user devices",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User devices retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $devices = UserDevice::where('user_id', $user->id)
                ->orderByDesc('last_activity_at')
                ->get()
                ->map(function ($device) {
                    return [
                        'device_id' => $device->device_id,
                        'device_name' => $device->device_name,
                        'device_type' => $device->device_type,
                        'platform' => $device->platform,
                        'formatted_info' => $device->formatted_device_info,
                        'is_primary' => $device->is_primary,
                        'is_active' => $device->is_active,
                        'status' => $device->status,
                        'biometric_enabled' => $device->biometric_enabled,
                        'biometric_types' => $device->biometric_types,
                        'security_score' => $device->security_score,
                        'requires_admin_approval' => $device->requires_admin_approval,
                        'last_login_at' => $device->last_login_at?->toISOString(),
                        'last_activity_at' => $device->last_activity_at?->toISOString(),
                        'created_at' => $device->created_at?->toISOString(),
                    ];
                });

            return $this->successResponse([
                'devices' => $devices,
                'total_devices' => $devices->count(),
                'active_devices' => $devices->where('is_active', true)->count(),
            ], 'User devices retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve devices',
                500,
                null,
                'DEVICE_RETRIEVAL_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/devices/register",
     *     summary="Register new device",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id"},
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="device_name", type="string"),
     *             @OA\Property(property="device_type", type="string"),
     *             @OA\Property(property="platform", type="string"),
     *             @OA\Property(property="os_version", type="string"),
     *             @OA\Property(property="push_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device registered successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|in:mobile,tablet,desktop',
            'platform' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'push_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $user = $request->user();
            $deviceInfo = UserDevice::extractDeviceInfo($request);

            $device = UserDevice::autoRegisterDevice($user->id, $deviceInfo);

            if (!$device) {
                return $this->errorResponse(
                    'Device registration failed. Maximum device limit reached.',
                    403,
                    null,
                    'DEVICE_LIMIT_EXCEEDED'
                );
            }

            return $this->successResponse([
                'device' => [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'is_primary' => $device->is_primary,
                    'status' => $device->status,
                    'requires_admin_approval' => $device->requires_admin_approval,
                ],
            ], 'Device registered successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to register device',
                500,
                null,
                'DEVICE_REGISTRATION_FAILED'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/devices/{device_id}",
     *     summary="Revoke device",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="device_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device revoked successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Device not found")
     * )
     */
    public function revokeDevice(Request $request, string $deviceId): JsonResponse
    {
        try {
            $user = $request->user();
            $device = UserDevice::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->first();

            if (!$device) {
                return $this->errorResponse(
                    'Device not found',
                    404,
                    null,
                    'DEVICE_NOT_FOUND'
                );
            }

            // Revoke device and associated tokens/sessions
            $this->tokenService->revokeTokens($user, $device, false, 'device_revoked');
            $device->revoke();

            return $this->successResponse(null, 'Device revoked successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to revoke device',
                500,
                null,
                'DEVICE_REVOKE_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/biometric/setup",
     *     summary="Setup biometric authentication",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"biometric_type", "biometric_data"},
     *             @OA\Property(property="biometric_type", type="string", enum={"fingerprint", "face", "voice", "iris"}),
     *             @OA\Property(property="biometric_data", type="string", description="Biometric template data"),
     *             @OA\Property(property="device_id", type="string", description="Device ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Biometric setup successful"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function setupBiometric(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'biometric_type' => 'required|string|in:fingerprint,face,voice,iris',
            'biometric_data' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $user = $request->user();
            
            // Find device if provided
            $device = null;
            if ($request->device_id) {
                $device = UserDevice::where('user_id', $user->id)
                    ->where('device_id', $request->device_id)
                    ->first();
            }

            $result = $this->biometricService->enrollBiometric(
                $user,
                $request->biometric_type,
                $request->biometric_data,
                $device
            );

            return $this->successResponse($result, 'Biometric setup successful');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Biometric setup failed',
                500,
                null,
                'BIOMETRIC_SETUP_FAILED'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v2/auth/biometric/verify",
     *     summary="Verify biometric authentication",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"biometric_type", "biometric_data"},
     *             @OA\Property(property="biometric_type", type="string", enum={"fingerprint", "face", "voice", "iris"}),
     *             @OA\Property(property="biometric_data", type="string", description="Biometric data to verify"),
     *             @OA\Property(property="device_id", type="string", description="Device ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Biometric verification result"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function verifyBiometric(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'biometric_type' => 'required|string|in:fingerprint,face,voice,iris',
            'biometric_data' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $user = $request->user();
            
            // Find device if provided
            $device = null;
            if ($request->device_id) {
                $device = UserDevice::where('user_id', $user->id)
                    ->where('device_id', $request->device_id)
                    ->first();
            }

            $result = $this->biometricService->verifyBiometric(
                $user,
                $request->biometric_type,
                $request->biometric_data,
                $device
            );

            return $this->successResponse($result, 'Biometric verification completed');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Biometric verification failed',
                500,
                null,
                'BIOMETRIC_VERIFICATION_FAILED'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v2/auth/biometric",
     *     summary="Get user biometric templates",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Biometric templates retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getBiometrics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $biometrics = $this->biometricService->getUserBiometrics($user);
            $recommendations = $this->biometricService->getBiometricSecurityRecommendations($user);

            return $this->successResponse([
                'biometrics' => $biometrics,
                'recommendations' => $recommendations,
            ], 'Biometric templates retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve biometrics',
                500,
                null,
                'BIOMETRIC_RETRIEVAL_FAILED'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/auth/biometric/{biometric_type}",
     *     summary="Remove biometric template",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="biometric_type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", enum={"fingerprint", "face", "voice", "iris"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Biometric template removed successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Biometric template not found")
     * )
     */
    public function removeBiometric(Request $request, string $biometricType): JsonResponse
    {
        try {
            $user = $request->user();
            
            $result = $this->biometricService->removeBiometric(
                $user,
                $biometricType,
                'user_request'
            );

            return $this->successResponse($result, 'Biometric template removed');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to remove biometric template',
                500,
                null,
                'BIOMETRIC_REMOVAL_FAILED'
            );
        }
    }

    /**
     * Register or update user device
     */
    private function registerOrUpdateDevice(User $user, Request $request): void
    {
        $deviceData = [
            'user_id' => $user->id,
            'device_fingerprint' => $request->device_fingerprint,
            'device_name' => $request->device_name,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'fcm_token' => $request->fcm_token,
            'last_used_at' => now(),
            'status' => 'active',
        ];

        UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_fingerprint' => $request->device_fingerprint,
            ],
            $deviceData
        );
    }

    /**
     * Handle avatar upload (placeholder - implement actual file storage)
     */
    private function handleAvatarUpload(string $base64Image, int $userId): ?string
    {
        // TODO: Implement actual file storage logic
        // For now, return null or a placeholder
        return null;
    }
}