<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::get('/test-manajer-auth', function () {
    $user = User::where('email', 'manajer@dokterku.com')->first();
    
    if (!$user) {
        return response()->json([
            'error' => 'User manajer@dokterku.com not found',
            'suggestion' => 'Run: php artisan db:seed --class=SimpleManajerSeeder'
        ]);
    }
    
    $passwordCheck = Hash::check('password', $user->password);
    $hasRole = $user->hasRole('manajer');
    
    return response()->json([
        'user_exists' => true,
        'email' => $user->email,
        'name' => $user->name,
        'is_active' => $user->is_active,
        'password_valid' => $passwordCheck,
        'has_manajer_role' => $hasRole,
        'roles' => $user->roles->pluck('name'),
        'login_url' => url('/login'),
        'dashboard_url' => url('/manajer'),
        'enhanced_dashboard_url' => url('/manajer/enhanced-manajer-dashboard'),
    ]);
});

// Removed test-manajer-login - using unified auth only

Route::get('/test-session', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_driver' => config('session.driver'),
        'session_lifetime' => config('session.lifetime'),
        'app_key' => config('app.key') ? 'SET' : 'NOT SET',
        'app_env' => config('app.env'),
        'session_working' => session()->isStarted(),
    ]);
});

Route::get('/fix-session', function () {
    // Clear all sessions and start fresh
    session()->flush();
    session()->regenerate(true);
    
    return response()->json([
        'message' => 'Session cleared and regenerated',
        'new_session_id' => session()->getId(),
        'new_csrf_token' => csrf_token(),
    ]);
});

Route::get('/test-bendahara-auth', function () {
    try {
        $results = [];
        
        // Test 1: Basic User query
        $results['user_query'] = 'Testing User 22 query...';
        $user = User::find(22);
        
        if (!$user) {
            $results['user_query'] = 'FAILED - User 22 not found';
            return response()->json($results, 500);
        }
        
        $results['user_query'] = 'SUCCESS';
        $results['user_name'] = $user->name;
        $results['user_email'] = $user->email;
        $results['user_active'] = $user->is_active;
        
        // Test 2: Role relationships
        $results['role_test'] = 'Testing role relationships...';
        
        try {
            $customRole = $user->customRole;
            $results['custom_role'] = $customRole ? $customRole->name : 'NULL';
        } catch (Exception $e) {
            $results['custom_role'] = 'EXCEPTION: ' . $e->getMessage();
        }
        
        try {
            $spatieRoles = $user->roles;
            $results['spatie_roles'] = $spatieRoles->pluck('name')->toArray();
        } catch (Exception $e) {
            $results['spatie_roles'] = 'EXCEPTION: ' . $e->getMessage();
        }
        
        try {
            $hasRole = $user->hasRole('bendahara');
            $results['has_bendahara_role'] = $hasRole;
        } catch (Exception $e) {
            $results['has_bendahara_role'] = 'EXCEPTION: ' . $e->getMessage();
        }
        
        // Test 3: Authentication provider
        $results['auth_provider_test'] = 'Testing auth provider...';
        
        try {
            $authProvider = Auth::getProvider();
            $retrievedUser = $authProvider->retrieveById(22);
            $results['auth_provider_retrieve'] = $retrievedUser ? 'SUCCESS' : 'NULL';
        } catch (Exception $e) {
            $results['auth_provider_retrieve'] = 'EXCEPTION: ' . $e->getMessage();
            $results['exception_trace'] = explode("\n", $e->getTraceAsString());
        }
        
        // Test 4: Manual login
        $results['manual_login_test'] = 'Testing manual login...';
        
        try {
            Auth::login($user);
            $results['manual_login'] = 'SUCCESS';
            $results['auth_check'] = Auth::check();
            $results['auth_id'] = Auth::id();
        } catch (Exception $e) {
            $results['manual_login'] = 'EXCEPTION: ' . $e->getMessage();
        }
        
        // Test 5: Middleware simulation
        if (Auth::check()) {
            $results['middleware_test'] = 'Testing middleware conditions...';
            $authUser = Auth::user();
            
            try {
                $results['middleware_authenticated'] = true;
                $results['middleware_has_role'] = $authUser->hasRole('bendahara');
                $results['middleware_is_active'] = $authUser->is_active;
                
                $shouldHaveAccess = $authUser->hasRole('bendahara') && $authUser->is_active;
                $results['should_have_access'] = $shouldHaveAccess;
                
            } catch (Exception $e) {
                $results['middleware_test'] = 'EXCEPTION: ' . $e->getMessage();
            }
        } else {
            $results['middleware_test'] = 'SKIPPED - Not authenticated';
        }
        
        return response()->json($results);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'CRITICAL EXCEPTION',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});

Route::get('/test-bendahara-login', function () {
    try {
        $user = User::find(22);
        
        if (!$user) {
            return response()->json(['error' => 'User 22 not found'], 404);
        }
        
        // Login the user
        Auth::login($user);
        
        return response()->json([
            'message' => 'Logged in as Bendahara user',
            'user' => $user->name,
            'auth_check' => Auth::check(),
            'redirect_url' => '/bendahara'
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});