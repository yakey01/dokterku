# Route Migration Guide - World Class Implementation

## Overview

This guide provides a systematic approach to migrate from the current monolithic `web.php` file (2,182 lines) to a world-class modular route organization.

## Benefits of the New Structure

1. **Modular Organization**: Routes are organized by domain (auth, admin, paramedis, etc.)
2. **Enhanced Security**: Proper middleware grouping and rate limiting
3. **Better Performance**: Route caching optimization
4. **Maintainability**: Easy to find and modify routes
5. **Scalability**: Easy to add new features without cluttering
6. **API Versioning**: Proper API versioning support

## File Structure

```
routes/
├── web.php              # Main web routes (simplified)
├── auth.php            # Authentication routes
├── admin.php           # Admin panel routes
├── paramedis.php       # Paramedis routes
├── dokter.php          # Dokter routes
├── petugas.php         # Staff routes
├── bendahara.php       # Treasurer routes
├── api.php             # API routes with versioning
├── debug.php           # Debug routes (dev only)
├── test.php            # Test routes (existing)
└── test-models.php     # Test model routes (existing)
```

## Migration Steps

### Step 1: Backup Current Routes
```bash
cp routes/web.php routes/web.php.backup.$(date +%Y%m%d_%H%M%S)
```

### Step 2: Create New Route Files
All new route files have been created with world-class organization:
- `routes/auth.php` - Authentication routes with proper middleware
- `routes/admin.php` - Admin routes with activity logging
- `routes/paramedis.php` - Paramedis routes with clean organization
- `routes/dokter.php` - Dokter routes optimized for mobile
- `routes/petugas.php` - Staff routes with enhanced features
- `routes/bendahara.php` - Treasurer routes with financial features
- `routes/api-improved.php` - RESTful API with versioning

### Step 3: Update RouteServiceProvider

Replace the current RouteServiceProvider with the world-class version:

```php
// In config/app.php, update the provider
App\Providers\RouteServiceProviderWorldClass::class,
```

### Step 4: Implement Middleware

Create these middleware classes:

```bash
php artisan make:middleware SecurityHeaders
php artisan make:middleware LogActivity
php artisan make:middleware CheckRole
php artisan make:middleware CheckPermission
```

### Step 5: Route Naming Convention

All routes now follow this naming convention:
- `{domain}.{resource}.{action}`
- Examples:
  - `auth.login`
  - `admin.users.create`
  - `paramedis.jaspel.index`
  - `api.v2.attendance.checkin`

### Step 6: Update Controllers

Controllers should be organized in subdirectories:
```
app/Http/Controllers/
├── Admin/
├── Api/
│   ├── V1/
│   └── V2/
├── Auth/
├── Bendahara/
├── Dokter/
├── Paramedis/
└── Petugas/
```

### Step 7: Test Routes

Run these commands to verify routes:
```bash
# List all routes
php artisan route:list

# List specific domain routes
php artisan route:list --path=admin
php artisan route:list --path=api/v2

# Cache routes for production
php artisan route:cache
```

### Step 8: Update Frontend References

Update all frontend route references to use the new route names:

```javascript
// Old
url: '/paramedis/api/v2/jaspel/mobile-data'

// New
url: route('api.v2.jaspel.mobile-data')
```

### Step 9: Implement Route Model Binding

The new structure includes automatic route model binding:
```php
// Automatically resolves User by ID or slug
Route::get('/users/{user}', ...);
```

### Step 10: Enable Route Caching

For production performance:
```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

## Security Enhancements

1. **Rate Limiting**:
   - API: 60 requests/minute per user
   - Auth: 5 requests/minute per IP
   - Global: 1000 requests/minute per IP

2. **Middleware Groups**:
   - Security headers on all web routes
   - Activity logging for admin actions
   - Role-based access control

3. **CSRF Protection**:
   - Automatically applied to all web routes
   - API routes use Sanctum tokens

## Performance Optimizations

1. **Route Caching**: Significantly improves route registration performance
2. **Middleware Grouping**: Reduces middleware execution overhead
3. **Prefix Grouping**: Optimizes route matching algorithm
4. **Pattern Constraints**: Faster route parameter validation

## Rollback Plan

If issues arise, rollback to the original routes:
```bash
# Restore backup
cp routes/web.php.backup.[timestamp] routes/web.php

# Clear route cache
php artisan route:clear

# Restart application
php artisan optimize:clear
```

## Monitoring

After migration, monitor:
1. Route performance in APM tools
2. 404 error rates
3. Authentication success rates
4. API response times

## Next Steps

1. Implement the missing middleware classes
2. Create controller subdirectories
3. Update all route() helper calls in views
4. Test each route group thoroughly
5. Deploy in staging environment first
6. Monitor for 24-48 hours before production deployment

## Support

For questions or issues during migration:
1. Check Laravel documentation on routing
2. Review the example implementations in each route file
3. Test thoroughly in development environment