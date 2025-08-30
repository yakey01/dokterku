<?php

use App\Http\Controllers\Api\ValidationCountsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bendahara (Treasurer) Routes
|--------------------------------------------------------------------------
|
| Essential routes for bendahara validation functionality
|
*/

Route::middleware(['auth', 'role:bendahara'])->prefix('bendahara')->name('bendahara.')->group(function () {
    
    // Validation API Routes for real-time tab updates
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/validation-counts', [ValidationCountsController::class, 'getCounts'])
            ->name('validation.counts');
        Route::get('/validation-stats', [ValidationCountsController::class, 'getDetailedStats'])
            ->name('validation.stats');
        Route::post('/validation-cache/clear', [ValidationCountsController::class, 'clearCache'])
            ->name('validation.cache.clear');
    });
});