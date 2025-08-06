<?php

use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| World-class authentication route organization with proper naming,
| middleware, and security considerations.
|
*/

Route::middleware('guest')->group(function () {
    // Main authentication entry points
    Route::get('/welcome-login', fn() => view('welcome-login-app'))
        ->name('auth.welcome');
    
    Route::get('/login', fn() => redirect()->route('auth.welcome'))
        ->name('login');
    
    Route::get('/unified-login', fn() => view('auth.unified-login'))
        ->name('auth.unified');
    
    // Alternative entry points
    Route::get('/welcome', fn() => redirect()->route('auth.welcome'))
        ->name('welcome');
    
    Route::get('/animated-login', fn() => redirect()->route('auth.welcome'))
        ->name('auth.animated');
});

// Authentication actions
Route::middleware('throttle:auth')->group(function () {
    Route::post('/unified-login', [UnifiedAuthController::class, 'login'])
        ->name('auth.login.post');
    
    Route::post('/logout', [UnifiedAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('auth.logout');
});