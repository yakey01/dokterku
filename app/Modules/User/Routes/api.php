<?php

use Illuminate\Support\Facades\Route;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Controllers\AuthController;

/**
 * User Module API Routes
 */

Route::prefix('api/v2')->middleware('api')->group(function () {
    
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        
        // User management routes
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
            
            // User actions
            Route::post('/{id}/activate', [UserController::class, 'activate']);
            Route::post('/{id}/deactivate', [UserController::class, 'deactivate']);
            Route::get('/{id}/permissions', [UserController::class, 'permissions']);
            Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
            Route::post('/{id}/remove-role', [UserController::class, 'removeRole']);
            Route::post('/{id}/change-password', [UserController::class, 'changePassword']);
            Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
        });
    });
});