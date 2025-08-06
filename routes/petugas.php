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
    
    // Dashboard
    Route::get('/', [StaffDashboardController::class, 'index'])
        ->name('dashboard');
    
    // Enhanced UI Routes
    Route::prefix('enhanced')->name('enhanced.')->group(function () {
        // Patient Management
        Route::prefix('pasien')->name('pasien.')->group(function () {
            Route::get('/', fn() => view('petugas.enhanced.pasien.index'))->name('index');
            Route::get('/create', fn() => view('petugas.enhanced.pasien.create'))->name('create');
            Route::get('/{pasien}', fn($pasien) => view('petugas.enhanced.pasien.show', compact('pasien')))->name('show');
            Route::get('/{pasien}/edit', fn($pasien) => view('petugas.enhanced.pasien.edit', compact('pasien')))->name('edit');
        });
        
        // Patient Count
        Route::get('/jumlah-pasien', fn() => view('petugas.enhanced.jumlah-pasien.index'))
            ->name('jumlah-pasien');
        
        // Medical Actions (Tindakan)
        Route::prefix('tindakan')->name('tindakan.')->group(function () {
            Route::get('/create', fn() => view('petugas.enhanced.tindakan.create'))->name('create');
            Route::get('/{tindakan}', fn($tindakan) => view('petugas.enhanced.tindakan.show', compact('tindakan')))->name('show');
        });
        
        // Financial Management
        Route::prefix('keuangan')->name('keuangan.')->group(function () {
            Route::get('/pendapatan', fn() => view('petugas.enhanced.pendapatan.index'))->name('pendapatan');
            Route::get('/pengeluaran', fn() => view('petugas.enhanced.pengeluaran.index'))->name('pengeluaran');
        });
    });
    
    // Worker App
    Route::get('/worker-app', fn() => view('petugas-worker'))
        ->name('worker.app');
    
    // Reports
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', 'PetugasReportController@index')->name('index');
        Route::get('/harian', 'PetugasReportController@daily')->name('daily');
        Route::get('/bulanan', 'PetugasReportController@monthly')->name('monthly');
        Route::post('/export', 'PetugasReportController@export')->name('export');
    });
    
    // API endpoints for enhanced features
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/pasien', 'PetugasApiController@storePasien')->name('pasien.store');
        Route::put('/pasien/{pasien}', 'PetugasApiController@updatePasien')->name('pasien.update');
        Route::post('/tindakan', 'PetugasApiController@storeTindakan')->name('tindakan.store');
    });
});