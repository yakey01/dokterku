# CSRF Token Mismatch Fix Documentation

## Problem Summary
The application was experiencing 419 (CSRF token mismatch) errors when attempting to login, preventing users from accessing the system.

## Root Causes Identified

### 1. Session Cookie Domain Mismatch
- The session configuration was using production domain `.dokterkuklinik.com` instead of localhost
- This prevented session cookies from being set correctly on `127.0.0.1:8000`

### 2. Environment Configuration Issues
- Application was running in production mode (`APP_ENV=production`)
- Database connection was attempting to use MySQL instead of SQLite
- Session driver configuration inconsistencies

### 3. Login Field Name Mismatch
- Frontend was sending `email` field while backend expected `email_or_username`

## Solution Implementation

### Step 1: Create ForceLocalSession Middleware
Created `/app/Http/Middleware/ForceLocalSession.php` to force correct configuration for localhost:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceLocalSession
{
    public function handle(Request $request, Closure $next)
    {
        // Force session domain to null for localhost development
        if ($request->getHost() === '127.0.0.1' || $request->getHost() === 'localhost') {
            config(['session.domain' => null]);
            config(['app.url' => 'http://127.0.0.1:8000']);
            config(['database.default' => 'sqlite']);
            config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        }
        
        return $next($request);
    }
}
```

### Step 2: Register Middleware in Laravel 11
Updated `/bootstrap/app.php` to prepend the middleware:

```php
->withMiddleware(function (Middleware $middleware) {
    // Force local session configuration for development
    $middleware->prepend(\App\Http\Middleware\ForceLocalSession::class);
    
    // ... rest of middleware configuration
})
```

### Step 3: Fix Login Request Field Name
Updated `/resources/js/components/dokter/App.tsx`:

```javascript
// Before:
body: JSON.stringify({ email, password }),

// After:
body: JSON.stringify({ email_or_username: email, password }),
```

### Step 4: Enhance Fetch Request Headers
Added proper headers and credentials to maintain session:

```javascript
const response = await fetch('/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json', // Added
    },
    body: JSON.stringify({ email_or_username: email, password }),
    credentials: 'same-origin', // Added
});
```

### Step 5: Remove CSRF Exceptions (Optional Enhancement)
Updated `/app/Http/Middleware/VerifyCsrfToken.php` to enforce CSRF protection:

```php
protected $except = [
    'livewire/update',
    'livewire/upload-file',
    'livewire/message/*',
    // Removed login and unified-login to enforce CSRF protection
];
```

### Step 6: Fix Environment Configuration
Updated `.env` file:
```
APP_ENV=local  # Changed from production
DB_CONNECTION=sqlite
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=  # Empty for localhost
```

### Step 7: Reset Admin Password
Since database connection was fixed, reset the admin password:
```bash
php artisan tinker --execute="\$user = App\Models\User::where('email', 'admin@dokterku.com')->first(); \$user->password = Hash::make('password123'); \$user->save();"
```

## Additional Files Created

### 1. CSRF Helper Utility
Created `/resources/js/utils/csrf-helper.ts` for consistent CSRF token handling:

```typescript
export class CSRFHelper {
    private static instance: CSRFHelper;
    private token: string | null = null;

    static getInstance(): CSRFHelper {
        if (!CSRFHelper.instance) {
            CSRFHelper.instance = new CSRFHelper();
        }
        return CSRFHelper.instance;
    }

    getToken(): string {
        if (!this.token) {
            this.refreshToken();
        }
        return this.token || '';
    }

    private refreshToken(): void {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        this.token = metaTag?.getAttribute('content') || null;
    }

    getHeaders(additionalHeaders: Record<string, string> = {}): Record<string, string> {
        return {
            'X-CSRF-TOKEN': this.getToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...additionalHeaders
        };
    }
}
```

### 2. Global CSRF Setup
Created `/resources/js/setup-csrf.ts` to initialize CSRF protection globally:

```typescript
import { csrfHelper } from './utils/csrf-helper';

function setupCSRF() {
    // Setup axios defaults if available
    csrfHelper.setupAxiosDefaults();
    
    // Intercept all fetch requests to add CSRF token
    csrfHelper.interceptFetch();
    
    // Add CSRF token to all forms on submit
    document.addEventListener('submit', (e) => {
        const form = e.target as HTMLFormElement;
        if (form.method.toUpperCase() !== 'GET') {
            csrfHelper.addTokenToForm(form);
        }
    });
}
```

## Testing Tools Created

### 1. Test Login Page
Created `/public/test-login-csrf-v2.html` for testing CSRF token flow

### 2. Debug Session Info
Created `/public/debug-csrf-session.php` to inspect session configuration

### 3. Test Routes
Created `/routes/test-csrf.php` with test endpoints for CSRF validation

## Build and Deployment

After making all changes, rebuild the application:
```bash
npm run build
php artisan config:clear
php artisan cache:clear
```

## Verification Steps

1. Access the application at `http://127.0.0.1:8000/dokter/mobile-app`
2. Check browser developer tools for:
   - No 419 errors in network tab
   - Session cookie being set on correct domain
   - CSRF token included in request headers
3. Login with:
   - Email: admin@dokterku.com
   - Password: password123

## Key Takeaways

1. **Session Domain Configuration**: Must be `null` for localhost, not a specific domain
2. **Environment Awareness**: Development environment needs different settings than production
3. **Field Name Consistency**: Frontend and backend must use the same field names
4. **Request Headers**: Include `Accept: application/json` and `credentials: 'same-origin'`
5. **Middleware Solution**: Use middleware to force correct configuration regardless of environment settings

## Prevention for Future

1. Always test authentication flows on localhost before deployment
2. Maintain separate `.env.local` and `.env.production` files
3. Use middleware to handle environment-specific configurations
4. Document expected request/response formats for authentication endpoints
5. Include CSRF token handling in all AJAX requests by default