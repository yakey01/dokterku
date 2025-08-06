<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - World Class Organization
|--------------------------------------------------------------------------
|
| This file follows world-class Laravel routing practices:
| - Modular route organization
| - Proper middleware grouping
| - Consistent naming conventions
| - Performance optimization ready
| - Security-first approach
|
*/

// Include modular route files
require __DIR__.'/auth.php';
require __DIR__.'/paramedis.php';
require __DIR__.'/dokter.php';
require __DIR__.'/admin.php';
require __DIR__.'/petugas.php';
require __DIR__.'/bendahara.php';
require __DIR__.'/api.php';

// Include debug routes only in development
if (!app()->environment('production')) {
    require __DIR__.'/debug.php';
    require __DIR__.'/test.php';
    require __DIR__.'/test-models.php';
}

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web'])->group(function () {
    
    // Home route
    Route::get('/', function () {
        if (auth()->check()) {
            return redirect()->route(auth()->user()->getDefaultRoute());
        }
        return redirect()->route('auth.welcome');
    })->name('home');
    
    // Dashboard links (authenticated users)
    Route::get('/dashboard-links', fn() => view('new-dashboard-links'))
        ->middleware('auth')
        ->name('dashboard.links');
});

/*
|--------------------------------------------------------------------------
| Shared Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Profile management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    
    // Common dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Route Model Binding Customization
|--------------------------------------------------------------------------
*/

Route::bind('user', function ($value) {
    return \App\Models\User::where('id', $value)
        ->orWhere('slug', $value)
        ->firstOrFail();
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});