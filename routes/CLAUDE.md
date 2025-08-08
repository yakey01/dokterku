# Routes Guidelines

## Overview
All application routes are defined here. Follow Laravel routing best practices.

## File Structure
- `web.php` - Web routes with session state
- `api.php` - Stateless API routes
- `channels.php` - WebSocket broadcast channels
- `console.php` - Artisan console commands

## Route Patterns

### Web Routes
```php
// Named routes for easy reference
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(['auth', 'verified']);

// Resource routes for CRUD operations
Route::resource('patients', PatientController::class);

// Grouped routes with shared middleware
Route::middleware(['auth', 'role:petugas'])->group(function () {
    Route::prefix('petugas')->name('petugas.')->group(function () {
        // Petugas-specific routes
    });
});
```

### API Routes
```php
// Versioned API routes
Route::prefix('v2')->name('api.v2.')->group(function () {
    // Public endpoints
    Route::get('/locations/work-locations', [LocationController::class, 'workLocations']);
    
    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/attendance/checkin', [AttendanceController::class, 'checkIn']);
    });
});
```

## Naming Conventions
- Use kebab-case for URLs: `/user-profile`
- Use dot notation for route names: `user.profile.edit`
- Prefix API routes with version: `/api/v2/`
- Group related routes with common prefixes

## Middleware Usage
```php
// Single middleware
->middleware('auth')

// Multiple middleware
->middleware(['auth', 'verified', 'role:admin'])

// Custom middleware
->middleware(RedirectToUnifiedAuth::class)
```

## Route Model Binding
```php
// Automatic model binding
Route::get('/users/{user}', function (User $user) {
    return $user;
});

// Custom binding logic
Route::bind('user', function ($value) {
    return User::where('slug', $value)->firstOrFail();
});
```

## Security Best Practices
- Always use CSRF protection for state-changing operations
- Apply authentication middleware to protected routes
- Use route model binding to prevent ID manipulation
- Implement rate limiting for API endpoints
- Validate route parameters

## Performance Tips
- Cache routes in production: `php artisan route:cache`
- Group routes to minimize middleware checks
- Use route model binding for automatic 404s
- Avoid closure routes in production (not cacheable)

## Common Route Groups

### Admin Routes
```php
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    // Admin routes
});
```

### API Authentication
```php
Route::middleware('auth:sanctum')->group(function () {
    // Authenticated API routes
});
```

### Public Pages
```php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
```

## Testing Routes
```bash
# List all routes
php artisan route:list

# Filter routes
php artisan route:list --name=admin
php artisan route:list --method=POST
php artisan route:list --path=api

# Clear route cache
php artisan route:clear
```