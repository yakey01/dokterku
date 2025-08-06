<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Paramedis Routes
|--------------------------------------------------------------------------
|
| World-class route organization for paramedis functionality with proper
| middleware, naming conventions, and security.
|
*/

Route::middleware(['auth', 'role:paramedis'])->prefix('paramedis')->name('paramedis.')->group(function () {
    
    // Dashboard routes
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', fn() => view('paramedis.welcome'))
            ->name('index');
        
        Route::get('/new', fn() => view('paramedis.dashboard-new'))
            ->name('new');
        
        Route::get('/card', fn() => view('paramedis.new-dashboard-card', ['user' => Auth::user()]))
            ->name('card');
        
        Route::get('/ujicoba', fn() => view('paramedis.dashboards.ujicoba-dashboard'))
            ->name('ujicoba');
    });
    
    // Attendance (Presensi) routes
    Route::prefix('presensi')->name('presensi.')->group(function () {
        Route::get('/', fn() => view('paramedis.presensi.dashboard'))
            ->name('index');
        
        Route::get('/gps', fn() => view('paramedis.presensi.gps-attendance'))
            ->name('gps');
    });
    
    // Schedule (Jadwal Jaga) routes
    Route::prefix('jadwal-jaga')->name('jadwal.')->group(function () {
        Route::get('/', fn() => view('paramedis.jadwal-jaga'))
            ->name('index');
    });
    
    // Jaspel routes
    Route::prefix('jaspel')->name('jaspel.')->group(function () {
        Route::get('/', fn() => view('paramedis.jaspel'))
            ->name('index');
    });
    
    // Report routes
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', fn() => view('paramedis.laporan'))
            ->name('index');
    });
});