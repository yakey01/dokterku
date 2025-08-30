<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BendaharaMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            Log::warning('BendaharaMiddleware: Unauthorized access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            
            return redirect('/bendahara/login');
        }

        // Check if user has bendahara role
        if (!$user->hasRole('bendahara')) {
            Log::warning('BendaharaMiddleware: Access denied - insufficient permissions', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'Akses ditolak. Anda tidak memiliki izin sebagai bendahara.');
        }

        // Check if user account is active
        if (!$user->is_active) {
            Log::warning('BendaharaMiddleware: Access denied - inactive account', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
            ]);

            Auth::logout();
            return redirect('/login')
                ->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        // Additional security checks for sensitive operations
        if ($this->isSensitiveOperation($request)) {
            // Log sensitive operations
            Log::info('BendaharaMiddleware: Sensitive operation accessed', [
                'user_id' => $user->id,
                'operation' => $request->route()?->getName(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            // Check session age for sensitive operations
            $sessionAge = time() - $request->session()->get('login_time', 0);
            $maxSessionAge = config('session.sensitive_operation_timeout', 3600); // 1 hour

            if ($sessionAge > $maxSessionAge) {
                Log::warning('BendaharaMiddleware: Session expired for sensitive operation', [
                    'user_id' => $user->id,
                    'session_age' => $sessionAge,
                    'max_age' => $maxSessionAge,
                ]);

                $request->session()->flush();
                return redirect('/login')
                    ->with('error', 'Sesi Anda telah berakhir untuk operasi sensitif. Silakan login kembali.');
            }

            // Rate limiting for bulk operations
            if ($this->isBulkOperation($request)) {
                $rateLimitKey = 'bulk_operations:' . $user->id;
                $attempts = cache()->increment($rateLimitKey);
                
                if ($attempts === 1) {
                    cache()->put($rateLimitKey, 1, now()->addMinutes(1));
                }

                $maxAttempts = config('security.bulk_operation_limit', 10);
                if ($attempts > $maxAttempts) {
                    Log::warning('BendaharaMiddleware: Rate limit exceeded for bulk operations', [
                        'user_id' => $user->id,
                        'attempts' => $attempts,
                        'max_attempts' => $maxAttempts,
                    ]);

                    abort(429, 'Terlalu banyak operasi bulk. Coba lagi dalam beberapa menit.');
                }
            }
        }

        // Update last activity timestamp
        $user->update(['last_activity' => now()]);

        // Log successful access for audit trail
        if (config('app.debug') || $this->shouldLogAccess($request)) {
            Log::debug('BendaharaMiddleware: Access granted', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }

    /**
     * Check if the request is for a sensitive operation
     */
    private function isSensitiveOperation(Request $request): bool
    {
        $sensitiveRoutes = [
            'filament.bendahara.resources.validasi-pendapatan.bulk-action',
            'filament.bendahara.resources.validasi-pengeluaran.bulk-action',
            'filament.bendahara.resources.validasi-tindakan.bulk-action',
        ];

        $routeName = $request->route()?->getName();
        
        return in_array($routeName, $sensitiveRoutes) ||
               str_contains($routeName ?? '', 'approve') ||
               str_contains($routeName ?? '', 'reject') ||
               str_contains($routeName ?? '', 'export') ||
               str_contains($routeName ?? '', 'bulk');
    }

    /**
     * Check if the request is for a bulk operation
     */
    private function isBulkOperation(Request $request): bool
    {
        $routeName = $request->route()?->getName() ?? '';
        
        return str_contains($routeName, 'bulk') ||
               $request->has('bulk_action') ||
               (is_array($request->input('records')) && count($request->input('records')) > 1);
    }

    /**
     * Determine if access should be logged
     */
    private function shouldLogAccess(Request $request): bool
    {
        // Log access to critical resources
        $criticalPaths = [
            'bendahara/validasi',
            'bendahara/laporan',
            'bendahara/export',
        ];

        $path = $request->path();
        
        foreach ($criticalPaths as $criticalPath) {
            if (str_contains($path, $criticalPath)) {
                return true;
            }
        }

        return false;
    }
}