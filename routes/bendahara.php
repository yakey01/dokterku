<?php

use App\Http\Controllers\Bendahara\TreasurerDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bendahara (Treasurer) Routes
|--------------------------------------------------------------------------
|
| World-class route organization for treasurer functionality with
| financial management features and proper access control.
|
*/

Route::middleware(['auth', 'role:bendahara'])->prefix('bendahara')->name('bendahara.')->group(function () {
    
    // Dashboard
    Route::get('/', [TreasurerDashboardController::class, 'index'])
        ->name('dashboard');
    
    // Financial Management
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        // Income Management
        Route::prefix('pendapatan')->name('pendapatan.')->group(function () {
            Route::get('/', 'PendapatanController@index')->name('index');
            Route::get('/create', 'PendapatanController@create')->name('create');
            Route::post('/', 'PendapatanController@store')->name('store');
            Route::get('/{pendapatan}/edit', 'PendapatanController@edit')->name('edit');
            Route::put('/{pendapatan}', 'PendapatanController@update')->name('update');
        });
        
        // Expense Management
        Route::prefix('pengeluaran')->name('pengeluaran.')->group(function () {
            Route::get('/', 'PengeluaranController@index')->name('index');
            Route::get('/create', 'PengeluaranController@create')->name('create');
            Route::post('/', 'PengeluaranController@store')->name('store');
            Route::get('/{pengeluaran}/edit', 'PengeluaranController@edit')->name('edit');
            Route::put('/{pengeluaran}', 'PengeluaranController@update')->name('update');
        });
        
        // Cash Flow
        Route::get('/arus-kas', 'CashFlowController@index')->name('arus-kas');
    });
    
    // Jaspel Management
    Route::prefix('jaspel')->name('jaspel.')->group(function () {
        Route::get('/', 'JaspelManagementController@index')->name('index');
        Route::get('/pending', 'JaspelManagementController@pending')->name('pending');
        Route::post('/approve/{jaspel}', 'JaspelManagementController@approve')->name('approve');
        Route::post('/reject/{jaspel}', 'JaspelManagementController@reject')->name('reject');
        Route::post('/bulk-approve', 'JaspelManagementController@bulkApprove')->name('bulk-approve');
    });
    
    // Financial Reports
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', 'FinancialReportController@index')->name('index');
        Route::get('/bulanan', 'FinancialReportController@monthly')->name('monthly');
        Route::get('/tahunan', 'FinancialReportController@yearly')->name('yearly');
        Route::get('/jaspel', 'FinancialReportController@jaspel')->name('jaspel');
        Route::post('/export', 'FinancialReportController@export')->name('export');
    });
    
    // Budget Management
    Route::prefix('anggaran')->name('anggaran.')->group(function () {
        Route::get('/', 'BudgetController@index')->name('index');
        Route::get('/create', 'BudgetController@create')->name('create');
        Route::post('/', 'BudgetController@store')->name('store');
        Route::get('/{budget}/edit', 'BudgetController@edit')->name('edit');
        Route::put('/{budget}', 'BudgetController@update')->name('update');
    });
});