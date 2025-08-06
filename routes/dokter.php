<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dokter Routes
|--------------------------------------------------------------------------
|
| World-class route organization for dokter functionality with proper
| middleware, naming conventions, and mobile optimization.
|
*/

Route::middleware(['auth', 'role:dokter'])->prefix('dokter')->name('dokter.')->group(function () {
    
    // Main dokter application
    Route::get('/', fn() => view('mobile.dokter.app'))
        ->name('index');
    
    // Mobile-specific routes
    Route::prefix('mobile')->name('mobile.')->group(function () {
        Route::get('/app', fn() => view('mobile.dokter.app'))
            ->name('app');
        
        Route::get('/app-fixed', fn() => view('mobile.dokter.app-fixed'))
            ->name('app.fixed');
    });
    
    // React-based routes for SPA
    Route::get('/{any}', fn() => view('mobile.dokter.app'))
        ->where('any', '.*')
        ->name('spa.catch-all');
});