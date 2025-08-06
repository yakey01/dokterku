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