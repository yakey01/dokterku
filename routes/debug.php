<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Debug Routes
|--------------------------------------------------------------------------
|
| Development and debugging routes. These should be disabled in production
| or protected with appropriate middleware.
|
*/

// Only enable debug routes in non-production environments
if (!app()->environment('production')) {
    
    Route::prefix('debug')->name('debug.')->group(function () {
        
        // Location debugging
        Route::get('/work-location', fn() => view('debug-location-issue'))
            ->name('location');
        
        // Component testing
        Route::get('/welcome-login-test', fn() => view('test-welcome-login'))
            ->name('welcome.test');
        
        // Jaspel debugging routes
        Route::prefix('jaspel')->name('jaspel.')->group(function () {
            Route::get('/bita', 'DebugController@debugBitaJaspel')
                ->name('bita');
            
            Route::get('/flow', 'DebugController@debugJaspelFlow')
                ->name('flow');
            
            Route::get('/consistency', 'DebugController@testParamedisConsistency')
                ->name('consistency');
        });
        
        // API testing routes
        Route::prefix('api-test')->name('api.test.')->group(function () {
            Route::get('/bita', 'DebugController@testBitaApi')
                ->name('bita');
            
            Route::get('/dashboard', 'DebugController@testDashboardApi')
                ->name('dashboard');
            
            Route::get('/schedules', 'DebugController@testSchedulesApi')
                ->name('schedules');
            
            Route::get('/attendance', 'DebugController@testAttendanceApi')
                ->name('attendance');
        });
    });
}