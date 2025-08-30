<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use App\Providers\CustomEloquentUserProvider;
use App\Services\SessionManagementService;

class UnifiedAuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.unified-login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        // Rate limiting check
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayTime = 60; // 1 minute

        // Check if request is JSON/AJAX
        $isJsonRequest = $request->expectsJson() || $request->header('Content-Type') === 'application/json';

        Log::info('LOGIN ATTEMPT STARTED - DEBUG', [
            'email_or_username' => $request->input('email_or_username'),
            'ip' => $request->ip(),
            'has_token' => $request->has('_token'),
            'session_id' => $request->session()->getId(),
            'csrf_token' => $request->input('_token') ? substr($request->input('_token'), 0, 10) . '...' : 'null',
            'user_agent' => $request->userAgent(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'all_input' => $request->except(['password', '_token']),
            'is_json_request' => $isJsonRequest,
            'content_type' => $request->header('Content-Type')
        ]);

        try {
            $request->validate([
                'email_or_username' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:6'],
                'remember' => ['nullable', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed during login', [
                'errors' => $e->errors(),
                'email_or_username' => $request->input('email_or_username'),
                'is_json_request' => $isJsonRequest
            ]);
            
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            throw $e;
        }

        $identifier = $request->input('email_or_username');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Find user by email or username from User table first
        $user = \App\Models\User::findForAuth($identifier);
        
        // Store the original identifier type for proper authentication
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        // If not found in User table, try to find in Dokter table
        if (!$user) {
            $dokter = \App\Models\Dokter::where('username', $identifier)
                ->whereNotNull('username')
                ->whereNotNull('password')
                ->whereRaw('LOWER(status_akun) = ?', ['aktif'])
                ->first();
                
            Log::info('Dokter search result', [
                'identifier' => $identifier,
                'found' => $dokter ? true : false,
                'dokter_id' => $dokter ? $dokter->id : null,
                'dokter_name' => $dokter ? $dokter->nama_lengkap : null,
            ]);
                
            if ($dokter) {
                // Check password for dokter
                $passwordValid = Hash::check($password, $dokter->password);
                Log::info('Dokter password check', [
                    'valid' => $passwordValid,
                    'dokter_id' => $dokter->id,
                ]);
                
                if ($passwordValid) {
                    // Create or get associated User for the dokter
                    if ($dokter->user_id && $dokter->user) {
                        $user = $dokter->user;
                        Log::info('Debug: Dokter login - using linked User account', [
                            'dokter_id' => $dokter->id,
                            'user_id' => $user->id,
                            'username' => $dokter->username
                        ]);
                    } else {
                        // Create User object for dokter login
                        $role = \App\Models\Role::where('name', 'dokter')->first();
                        
                        if ($role) {
                            // Create a real User record in database for dokter
                            $userEmail = $dokter->nik . '@dokter.local';
                            
                            // Check if user already exists
                            $existingUser = \App\Models\User::where('email', $userEmail)->first();
                            
                            if (!$existingUser) {
                                // Create real user record in database
                                $user = \App\Models\User::create([
                                    'name' => $dokter->nama_lengkap,
                                    'username' => $dokter->username,
                                    'email' => $userEmail,
                                    'role_id' => $role->id,
                                    'is_active' => $dokter->aktif,
                                    'password' => $dokter->password,
                                ]);
                                
                                // Update dokter with user_id
                                $dokter->update(['user_id' => $user->id]);
                            } else {
                                $user = $existingUser;
                                // Update existing user data
                                $user->update([
                                    'name' => $dokter->nama_lengkap,
                                    'username' => $dokter->username,
                                    'role_id' => $role->id,
                                    'is_active' => $dokter->aktif,
                                ]);
                            }
                            
                            Log::info('Debug: Dokter login - created virtual User', [
                                'dokter_id' => $dokter->id,
                                'virtual_user_id' => $user->id,
                                'role' => $role->name,
                                'username' => $dokter->username
                            ]);
                        }
                    }
                }
            }
        }
        
        // If not found in User or Dokter table, try to find in Pegawai table
        if (!$user) {
            $pegawai = \App\Models\Pegawai::where('username', $identifier)
                ->whereNotNull('username')
                ->whereNotNull('password')
                ->whereRaw('LOWER(status_akun) = ?', ['aktif'])
                ->first();
                
            if ($pegawai) {
                // Check password for pegawai
                if (Hash::check($password, $pegawai->password)) {
                    // Create or get associated User for the pegawai
                    if ($pegawai->user_id && $pegawai->user) {
                        $user = $pegawai->user;
                        Log::info('Debug: Pegawai login - using linked User account', [
                            'pegawai_id' => $pegawai->id,
                            'user_id' => $user->id,
                            'username' => $pegawai->username
                        ]);
                    } else {
                        // Create temporary User object for pegawai login
                        $roleName = match($pegawai->jenis_pegawai) {
                            'Paramedis' => 'paramedis',
                            'Non-Paramedis' => 'non_paramedis', // Fixed: Use proper non_paramedis role
                            default => 'petugas'
                        };
                        $role = \App\Models\Role::where('name', $roleName)->first();
                        
                        if ($role) {
                            // Create a real User record in database for pegawai
                            $userEmail = $pegawai->nik . '@pegawai.local';
                            
                            // Check if user already exists
                            $existingUser = \App\Models\User::where('email', $userEmail)->first();
                            
                            if (!$existingUser) {
                                // Create real user record in database
                                $user = \App\Models\User::create([
                                    'name' => $pegawai->nama_lengkap,
                                    'username' => $pegawai->username,
                                    'email' => $userEmail,
                                    'role_id' => $role->id,
                                    'is_active' => $pegawai->aktif,
                                    'password' => $pegawai->password,
                                ]);
                                
                                // Update pegawai with user_id
                                $pegawai->update(['user_id' => $user->id]);
                            } else {
                                $user = $existingUser;
                                // Update existing user data
                                $user->update([
                                    'name' => $pegawai->nama_lengkap,
                                    'username' => $pegawai->username,
                                    'role_id' => $role->id,
                                    'is_active' => $pegawai->aktif,
                                ]);
                            }
                            
                            Log::info('Debug: Pegawai login - created virtual User', [
                                'pegawai_id' => $pegawai->id,
                                'virtual_user_id' => $user->id,
                                'role' => $role->name,
                                'username' => $pegawai->username
                            ]);
                        }
                    }
                }
            }
        }
        
        $originalPassword = $request->input('password');
        Log::info('Debug: User lookup', [
            'identifier' => $identifier,
            'user_found' => $user ? 'YES' : 'NO',
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_active' => $user ? $user->is_active : null,
            'user_source' => $user && isset($pegawai) ? 'pegawai' : 'user_table',
            'password_provided' => !empty($password),
            'original_password_length' => strlen($originalPassword),
            'trimmed_password_length' => strlen($password),
            'password_was_trimmed' => $originalPassword !== $password,
            'password_raw' => $password,
            'password_hex' => bin2hex($password),
            'original_password_hex' => bin2hex($originalPassword),
            'password_ord_last' => ord(substr($password, -1)),
        ]);
        
        // Debug: test password manually before Auth::attempt
        if ($user) {
            $manualPasswordCheck = \Illuminate\Support\Facades\Hash::check($password, $user->password);
            $trimmedPasswordCheck = \Illuminate\Support\Facades\Hash::check(trim($password), $user->password);
            Log::info('Debug: Manual password check', [
                'user_id' => $user->id,
                'manual_check_result' => $manualPasswordCheck,
                'trimmed_check_result' => $trimmedPasswordCheck,
                'password_hash_exists' => !empty($user->password),
                'password_hash_preview' => substr($user->password, 0, 20) . '...',
                'password_needs_trim' => $password !== trim($password),
            ]);
        }

        $loginSuccessful = false;
        
        if ($user) {
            // Check if this is a dokter user (password already verified above)
            if (isset($dokter)) {
                // Manual login for dokter - password already verified
                Auth::login($user, $remember);
                $loginSuccessful = true;
                
                Log::info('Debug: Dokter manual login successful', [
                    'dokter_id' => $dokter->id,
                    'virtual_user_id' => $user->id,
                    'username' => $dokter->username
                ]);
            }
            // Check if this is a pegawai user (password already verified above)
            elseif (isset($pegawai)) {
                // Manual login for pegawai - password already verified
                Auth::login($user, $remember);
                $loginSuccessful = true;
                
                Log::info('Debug: Pegawai manual login successful', [
                    'pegawai_id' => $pegawai->id,
                    'virtual_user_id' => $user->id,
                    'username' => $pegawai->username
                ]);
            } else {
                // Regular Auth::attempt for normal users (including admin)
                if ($isEmail) {
                    // If user provided email, authenticate by email only
                    $loginSuccessful = Auth::attempt([
                        'email' => $user->email,
                        'password' => $password
                    ], $remember);
                } else {
                    // If user provided username, authenticate by username
                    $loginSuccessful = Auth::attempt([
                        'username' => $user->username,
                        'password' => $password
                    ], $remember);
                }
            }
        }
        
        if ($loginSuccessful) {
            // Clear failed attempts on successful login
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            
            $request->session()->regenerate();
            
            // CRITICAL: Save session immediately to prevent race conditions
            $request->session()->save();

            $user = Auth::user();
            
            Log::info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'no_role'
            ]);
            
            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                Log::warning('Inactive user attempted login', ['email' => $user->email]);
                
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                        'errors' => ['email_or_username' => 'Akun Anda tidak aktif. Silakan hubungi administrator.']
                    ], 422);
                }
                
                throw ValidationException::withMessages([
                    'email_or_username' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                ]);
            }
            
            // Clear any previous intended URL to prevent cross-role redirects
            $request->session()->forget('url.intended');
            
            // Debug: Log all user roles for redirect analysis
            Log::info('User role redirect analysis', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'all_roles' => $user->getRoleNames()->toArray(),
                'has_admin' => $user->hasRole('admin'),
                'has_petugas' => $user->hasRole('petugas'),
                'has_manajer' => $user->hasRole('manajer'),
                'has_bendahara' => $user->hasRole('bendahara'),
                'has_dokter' => $user->hasRole('dokter'),
                'has_paramedis' => $user->hasRole('paramedis'),
                'has_non_paramedis' => $user->hasRole('non_paramedis'),
            ]);

            // Determine redirect URL based on user role - ORDER MATTERS: most specific roles first
            $redirectUrl = null;
            if ($user->hasRole('admin')) {
                Log::info('Redirecting admin user to /admin');
                $redirectUrl = '/admin';
            } elseif ($user->hasRole('bendahara')) {
                Log::info('Redirecting bendahara user to /bendahara', [
                    'user_id' => $user->id,
                    'username' => $user->username
                ]);
                $redirectUrl = '/bendahara';
            } elseif ($user->hasRole('manajer')) {
                Log::info('Redirecting manajer user to /manajer');
                $redirectUrl = '/manajer';
            } elseif ($user->hasRole('dokter')) {
                Log::info('Redirecting dokter user to /dokter/mobile-app');
                $redirectUrl = '/dokter/mobile-app';
            } elseif ($user->hasRole('paramedis')) {
                Log::info('Redirecting paramedis user to /paramedis');
                $redirectUrl = '/paramedis';
            } elseif ($user->hasRole('non_paramedis')) {
                Log::info('Redirecting non_paramedis user to /nonparamedis/dashboard', [
                    'user_id' => $user->id,
                    'route_exists' => \Route::has('nonparamedis.dashboard'),
                    'route_url' => route('nonparamedis.dashboard')
                ]);
                $redirectUrl = route('nonparamedis.dashboard');
            } elseif ($user->hasRole('petugas')) {
                // Check if this petugas user should be redirected to non-paramedis interface
                // This handles legacy users who were mapped to 'petugas' but are actually non-paramedis
                if ($user->username && str_contains($user->email, '@pegawai.local')) {
                    // This is likely a pegawai-created user that should go to non-paramedis
                    Log::info('Redirecting pegawai petugas user to non-paramedis interface', [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email
                    ]);
                    $redirectUrl = route('nonparamedis.dashboard');
                } else {
                    Log::info('Redirecting petugas user to /petugas');
                    $redirectUrl = '/petugas';
                }
            } else {
                // Default fallback
                Log::info('Redirecting user to default /dashboard');
                $redirectUrl = '/dashboard';
            }
            
            // Handle JSON/AJAX requests
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'url' => $redirectUrl,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role ? $user->role->name : null
                    ]
                ]);
            }
            
            // Handle regular form requests - Enhanced logging for redirect loop debugging
            Log::info('UnifiedAuthController: About to redirect after successful login', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'redirect_url' => $redirectUrl,
                'session_id' => $request->session()->getId(),
                'auth_check' => Auth::check(),
                'auth_id' => Auth::id()
            ]);
            
            // Additional session save to ensure persistence
            $request->session()->save();
            
            return redirect($redirectUrl);
        }

        // Record failed attempt
        \Illuminate\Support\Facades\RateLimiter::hit($key, $decayTime);
        
        // Additional debug info for failed login
        if ($user) {
            Log::warning('Failed login attempt - User found but auth failed', [
                'email_or_username' => $request->input('email_or_username'),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'is_active' => $user->is_active,
                'has_password' => !empty($user->password),
                'password_check' => \Illuminate\Support\Facades\Hash::check($password, $user->password),
                'ip' => $request->ip(),
                'attempts' => \Illuminate\Support\Facades\RateLimiter::attempts($key)
            ]);
        } else {
            Log::warning('Failed login attempt - User not found', [
                'email_or_username' => $request->input('email_or_username'),
                'ip' => $request->ip(),
                'attempts' => \Illuminate\Support\Facades\RateLimiter::attempts($key)
            ]);
        }
        
        // Add delay for failed attempts to prevent brute force
        sleep(rand(1, 3));
        
        // Handle JSON/AJAX requests
        if ($isJsonRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please check your credentials.',
                'errors' => ['email_or_username' => 'Email/username atau password salah.']
            ], 422);
        }
        
        throw ValidationException::withMessages([
            'email_or_username' => 'Email/username atau password salah.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Use unified session management service for logout
        $sessionService = new SessionManagementService();
        
        // Get user role before logout
        $userRole = Auth::user()?->role?->name;
        
        // Perform unified logout
        $sessionService->logout($request, $userRole);

        // Redirect to unified login page for consistency
        return redirect('/login')->with('status', 'Anda telah berhasil logout.');
    }
}