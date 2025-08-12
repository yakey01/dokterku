<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-csrf-info', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'cookie_config' => [
            'domain' => config('session.domain'),
            'path' => config('session.path'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'http_only' => config('session.http_only'),
        ],
        'app_url' => config('app.url'),
        'session_driver' => config('session.driver'),
    ]);
});

Route::post('/test-csrf-post', function () {
    return response()->json([
        'message' => 'CSRF validation passed!',
        'received_token' => request()->header('X-CSRF-TOKEN'),
        'session_token' => csrf_token(),
        'match' => request()->header('X-CSRF-TOKEN') === csrf_token(),
    ]);
});

// Test login endpoint
Route::post('/test-login', function () {
    $request = request();
    
    return response()->json([
        'message' => 'Login test successful',
        'csrf_validation' => 'passed',
        'received_data' => [
            'email_or_username' => $request->input('email_or_username'),
            'password' => $request->input('password') ? '***' : 'missing',
            'has_csrf_token' => $request->has('_token'),
            'csrf_token' => $request->input('_token') ? substr($request->input('_token'), 0, 10) . '...' : 'missing',
            'x_csrf_header' => $request->header('X-CSRF-TOKEN') ? substr($request->header('X-CSRF-TOKEN'), 0, 10) . '...' : 'missing',
        ],
        'session_info' => [
            'session_id' => session()->getId(),
            'session_token' => csrf_token(),
            'session_started' => session()->isStarted(),
        ],
        'headers' => $request->headers->all(),
    ]);
});

// Test CSRF token generation
Route::get('/test-csrf-generate', function () {
    // Regenerate CSRF token
    session()->regenerateToken();
    
    return response()->json([
        'message' => 'CSRF token regenerated',
        'new_token' => csrf_token(),
        'session_id' => session()->getId(),
        'timestamp' => now()->toISOString(),
    ]);
});