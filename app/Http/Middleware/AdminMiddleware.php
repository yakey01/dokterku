<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enhanced Admin Middleware
 * 
 * Improved admin access control with comprehensive security checks,
 * rate limiting, session validation, and audit logging.
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request with enhanced security
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Basic authentication check
        if (!Auth::check()) {
            $this->logSecurityEvent('unauthorized_access_attempt', $request);
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // 2. Rate limiting check
        if ($this->isRateLimited($request, $user)) {
            $this->logSecurityEvent('rate_limit_exceeded', $request, $user);
            return $this->handleRateLimitExceeded($request);
        }

        // 3. Admin role validation
        if (!$this->hasAdminAccess($user)) {
            $this->logSecurityEvent('insufficient_privileges', $request, $user);
            return $this->redirectToUserPanel($user, $request);
        }

        // 4. Session security validation - TEMPORARILY DISABLED
        // if (!$this->validateSession($request, $user)) {
        //     $this->logSecurityEvent('invalid_session', $request, $user);
        //     Auth::logout();
        //     return $this->redirectToLogin($request, 'Session expired for security reasons.');
        // }

        // 5. Account status validation
        if (!$this->isAccountActive($user)) {
            $this->logSecurityEvent('inactive_account_access', $request, $user);
            Auth::logout();
            return $this->redirectToLogin($request, 'Account is inactive.');
        }

        // 6. IP whitelist validation (if configured)
        if (!$this->isIpAllowed($request)) {
            $this->logSecurityEvent('ip_not_whitelisted', $request, $user);
            return $this->handleForbidden($request);
        }

        // 7. Update last activity and security metrics
        $this->updateUserActivity($user, $request);

        // 8. Log successful access
        $this->logSecurityEvent('admin_access_granted', $request, $user);

        return $next($request);
    }

    /**
     * Check if user has admin access
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function hasAdminAccess($user): bool
    {
        // Check if user has admin role or specific admin permissions
        return $user->hasRole(['admin', 'super_admin']) || 
               $user->hasPermissionTo('access_admin_panel');
    }

    /**
     * Check if request is rate limited
     *
     * @param Request $request
     * @param \App\Models\User $user
     * @return bool
     */
    private function isRateLimited(Request $request, $user): bool
    {
        $key = 'admin_access:' . $user->id . ':' . $request->ip();
        
        // More lenient rate limiting for admins (60 requests per minute)
        return RateLimiter::tooManyAttempts($key, 60);
    }

    /**
     * Handle rate limit exceeded
     *
     * @param Request $request
     * @return Response
     */
    private function handleRateLimitExceeded(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn('admin_access:' . auth()->id() . ':' . $request->ip())
            ], 429);
        }

        return redirect()
            ->back()
            ->with('error', 'Too many requests. Please try again later.');
    }

    /**
     * Validate session security
     *
     * @param Request $request
     * @param \App\Models\User $user
     * @return bool
     */
    private function validateSession(Request $request, $user): bool
    {
        // Check if session fingerprint matches
        $currentFingerprint = $this->generateSessionFingerprint($request);
        $storedFingerprint = session('session_fingerprint');

        if ($storedFingerprint && $currentFingerprint !== $storedFingerprint) {
            return false;
        }

        // Store fingerprint if not exists
        if (!$storedFingerprint) {
            session(['session_fingerprint' => $currentFingerprint]);
        }

        // Check session timeout for admin users (shorter timeout)
        $lastActivity = session('last_activity');
        $adminSessionTimeout = config('session.admin_lifetime', 1800); // 30 minutes default

        if ($lastActivity && (time() - $lastActivity) > $adminSessionTimeout) {
            return false;
        }

        // Update last activity
        session(['last_activity' => time()]);

        return true;
    }

    /**
     * Generate session fingerprint for security
     *
     * @param Request $request
     * @return string
     */
    private function generateSessionFingerprint(Request $request): string
    {
        return hash('sha256', 
            $request->ip() . 
            $request->userAgent() . 
            config('app.key')
        );
    }

    /**
     * Check if account is active
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function isAccountActive($user): bool
    {
        return $user->email_verified_at !== null && 
               !$user->trashed() &&
               !$this->isAccountLocked($user);
    }

    /**
     * Check if account is locked due to security reasons
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function isAccountLocked($user): bool
    {
        $lockKey = 'account_locked:' . $user->id;
        return Cache::has($lockKey);
    }

    /**
     * Check if IP is allowed (if whitelist is configured)
     *
     * @param Request $request
     * @return bool
     */
    private function isIpAllowed(Request $request): bool
    {
        $allowedIps = config('admin.allowed_ips', []);
        
        if (empty($allowedIps)) {
            return true; // No whitelist configured
        }

        $clientIp = $request->ip();
        
        foreach ($allowedIps as $allowedIp) {
            if ($this->ipMatches($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches pattern (supports CIDR)
     *
     * @param string $ip
     * @param string $pattern
     * @return bool
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        if ($ip === $pattern) {
            return true;
        }

        // Support CIDR notation
        if (strpos($pattern, '/') !== false) {
            return $this->ipInRange($ip, $pattern);
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    /**
     * Update user activity metrics
     *
     * @param \App\Models\User $user
     * @param Request $request
     * @return void
     */
    private function updateUserActivity($user, Request $request): void
    {
        // Update last activity timestamp
        $user->update([
            'last_activity_at' => now(),
            'last_admin_access_at' => now(),
            'last_admin_ip' => $request->ip()
        ]);

        // Increment rate limiter
        $key = 'admin_access:' . $user->id . ':' . $request->ip();
        RateLimiter::hit($key, 60); // 1 minute decay
    }

    /**
     * Redirect to appropriate user panel based on role
     *
     * @param \App\Models\User $user
     * @param Request $request
     * @return Response
     */
    private function redirectToUserPanel($user, Request $request): Response
    {
        $message = 'Anda tidak memiliki akses ke panel admin.';
        
        // Determine redirect based on user role
        $redirectUrl = '/dashboard'; // Default fallback
        
        if ($user->hasRole('petugas')) {
            $redirectUrl = '/petugas';
            $message = 'Anda telah diarahkan ke panel petugas.';
        } elseif ($user->hasRole('dokter')) {
            $redirectUrl = '/dokter';
            $message = 'Anda telah diarahkan ke panel dokter.';
        } elseif ($user->hasRole('paramedis')) {
            $redirectUrl = '/paramedis';
            $message = 'Anda telah diarahkan ke panel paramedis.';
        } elseif ($user->hasRole('bendahara')) {
            $redirectUrl = '/bendahara';
            $message = 'Anda telah diarahkan ke panel bendahara.';
        } elseif ($user->hasRole('manajer')) {
            $redirectUrl = '/manajer';
            $message = 'Anda telah diarahkan ke panel manajer.';
        } elseif ($user->hasRole('non_paramedis')) {
            $redirectUrl = route('nonparamedis.dashboard');
            $message = 'Anda telah diarahkan ke panel non-paramedis.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => $message,
                'redirect_url' => $redirectUrl
            ], 403);
        }

        return redirect($redirectUrl)->with('info', $message);
    }

    /**
     * Redirect to login with message
     *
     * @param Request $request
     * @param string|null $message
     * @return Response
     */
    private function redirectToLogin(Request $request, ?string $message = null): Response
    {
        $message = $message ?? 'Please log in to access the admin panel.';

        if ($request->expectsJson()) {
            return response()->json([
                'error' => $message,
                'redirect_url' => route('login')
            ], 401);
        }

        return redirect()
            ->route('login')
            ->with('error', $message);
    }

    /**
     * Handle forbidden access
     *
     * @param Request $request
     * @return Response
     */
    private function handleForbidden(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access forbidden from this IP address.'
            ], 403);
        }

        return response()->view('errors.403', [
            'message' => 'Access forbidden from this IP address.'
        ], 403);
    }

    /**
     * Log security events for audit trail
     *
     * @param string $event
     * @param Request $request
     * @param \App\Models\User|null $user
     * @return void
     */
    private function logSecurityEvent(string $event, Request $request, $user = null): void
    {
        $logData = [
            'event' => $event,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ];

        if ($user) {
            $logData['user_id'] = $user->id;
            $logData['user_email'] = $user->email;
            $logData['user_roles'] = $user->roles->pluck('name')->toArray();
        }

        // Log to Laravel log
        Log::channel('security')->info('Admin middleware security event', $logData);

        // Store in cache for real-time monitoring
        $cacheKey = 'security_events:' . date('Y-m-d-H');
        $events = Cache::get($cacheKey, []);
        $events[] = $logData;
        Cache::put($cacheKey, $events, 3600); // Keep for 1 hour
    }

    /**
     * Get security statistics for monitoring
     *
     * @return array
     */
    public static function getSecurityStats(): array
    {
        $hour = date('Y-m-d-H');
        $events = Cache::get('security_events:' . $hour, []);

        return [
            'total_events' => count($events),
            'unauthorized_attempts' => count(array_filter($events, fn($e) => $e['event'] === 'unauthorized_access_attempt')),
            'rate_limit_hits' => count(array_filter($events, fn($e) => $e['event'] === 'rate_limit_exceeded')),
            'privilege_violations' => count(array_filter($events, fn($e) => $e['event'] === 'insufficient_privileges')),
            'session_violations' => count(array_filter($events, fn($e) => $e['event'] === 'invalid_session')),
            'successful_accesses' => count(array_filter($events, fn($e) => $e['event'] === 'admin_access_granted')),
            'hour' => $hour
        ];
    }
}