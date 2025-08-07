<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DokterStatsController;
use App\Http\Controllers\Api\V2\HospitalLocationController;

/*
|--------------------------------------------------------------------------
| Dokter API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for dokter functionality.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Dokter Stats API
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dokter/stats', [DokterStatsController::class, 'stats'])->name('api.dokter.stats');
});

// Alternative routes for different URL patterns
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/stats/dokter', [DokterStatsController::class, 'stats'])->name('api.stats.dokter');
});

// Public stats (with rate limiting)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/public/dokter/stats', [DokterStatsController::class, 'stats'])->name('api.public.dokter.stats');
});

// Hospital Location API
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/hospital/location', [HospitalLocationController::class, 'getLocation'])->name('api.hospital.location');
    Route::get('/hospital/locations', [HospitalLocationController::class, 'getAllLocations'])->name('api.hospital.locations');
    Route::get('/hospital/location/{id}', [HospitalLocationController::class, 'getLocationById'])->name('api.hospital.location.by.id');
});

// Public hospital location (with rate limiting)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/public/hospital/location', [HospitalLocationController::class, 'getLocation'])->name('api.public.hospital.location');
});