<?php

use App\Http\Controllers\Petugas\StaffDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Petugas (Staff) Routes
|--------------------------------------------------------------------------
|
| World-class route organization for staff functionality with enhanced
| features and proper access control.
|
*/

Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    
    // Dashboard - Handled by Filament Panel Provider
    // The main /petugas route is now managed by FilamentPanelProvider
    // Add redirect for /dashboard to prevent 404
    Route::get('/dashboard', function() {
        return redirect('/petugas');
    })->name('dashboard');
    
    // Enhanced Dashboard
    Route::get('/enhanced-dashboard', function() {
        return view('petugas.enhanced-dashboard');
    })->name('enhanced-dashboard');
    
    // Enhanced UI Routes with Controllers
    Route::prefix('enhanced')->name('enhanced.')->group(function () {
        // Patient Management
        Route::prefix('pasien')->name('pasien.')->controller(\App\Http\Controllers\Petugas\Enhanced\PasienController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/elegant-dark', function() {
                return view('petugas.enhanced.pasien.elegant-dark-index');
            })->name('elegant-dark');
            Route::get('/data', 'data')->name('data');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/search/autocomplete', 'autocomplete')->name('search.autocomplete');
            Route::get('/{id}', 'show')->name('show');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::get('/{id}/timeline', 'timeline')->name('timeline');
            Route::post('/bulk-delete', 'bulkDelete')->name('bulk-delete');
            Route::post('/export', 'export')->name('export');
        });
        
        // Patient Count
        Route::prefix('jumlah-pasien')->name('jumlah-pasien.')->controller(\App\Http\Controllers\Petugas\Enhanced\JumlahPasienController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/analytics', 'analytics')->name('analytics');
            Route::get('/date-stats', 'getDateStats')->name('date-stats');
            Route::get('/calendar-data', 'getCalendarData')->name('calendar-data');
            Route::post('/export', 'export')->name('export');
        });
        
        // Medical Actions (Tindakan)
        Route::prefix('tindakan')->name('tindakan.')->controller(\App\Http\Controllers\Petugas\Enhanced\TindakanController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::get('/{id}/print', 'print')->name('print');
            Route::post('/bulk-delete', 'bulkDelete')->name('bulk-delete');
        });
        
        // Financial Management - Pendapatan
        Route::prefix('pendapatan')->name('pendapatan.')->controller(\App\Http\Controllers\Petugas\Enhanced\PendapatanController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/data', 'data')->name('data');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/analytics', 'analytics')->name('analytics');
            Route::get('/{id}', 'show')->name('show');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::post('/bulk-create-from-tindakan', 'bulkCreateFromTindakan')->name('bulk-create-from-tindakan');
            Route::post('/export', 'export')->name('export');
        });
        
        // Financial Management - Pengeluaran
        Route::prefix('pengeluaran')->name('pengeluaran.')->controller(\App\Http\Controllers\Petugas\Enhanced\PengeluaranController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/data', 'data')->name('data');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/analytics', 'analytics')->name('analytics');
            Route::get('/categories', 'categories')->name('categories');
            Route::get('/{id}', 'show')->name('show');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::post('/bulk-approval', 'bulkApproval')->name('bulk-approval');
            Route::post('/export', 'export')->name('export');
        });
    });
    
    // Worker App
    Route::get('/worker-app', fn() => view('petugas-worker'))
        ->name('worker.app');
    
    // Reports - TEMPORARILY DISABLED: Missing PetugasReportController
    // Route::prefix('laporan')->name('laporan.')->group(function () {
    //     Route::get('/', 'PetugasReportController@index')->name('index');
    //     Route::get('/harian', 'PetugasReportController@daily')->name('daily');
    //     Route::get('/bulanan', 'PetugasReportController@monthly')->name('monthly');
    //     Route::post('/export', 'PetugasReportController@export')->name('export');
    // });
    
    // API endpoints for enhanced features - TEMPORARILY DISABLED: Missing PetugasApiController
    // Route::prefix('api')->name('api.')->group(function () {
    //     Route::post('/pasien', 'PetugasApiController@storePasien')->name('pasien.store');
    //     Route::put('/pasien/{pasien}', 'PetugasApiController@updatePasien')->name('pasien.update');
    //     Route::post('/tindakan', 'PetugasApiController@storeTindakan')->name('tindakan.store');
    // });
});