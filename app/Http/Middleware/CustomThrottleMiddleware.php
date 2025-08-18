<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Custom Throttle Middleware
 * 
 * Provides intelligent rate limiting with different limits for different endpoints
 * and user roles. Optimized for real-time dashboard applications.
 */
class CustomThrottleMiddleware extends ThrottleRequests
{
    /**
     * Resolve request signature for intelligent rate limiting
     */
    protected function resolveRequestSignature($request)
    {
        $user = Auth::user();
        $userRole = $user?->roles?->first()?->name ?? 'guest';
        $endpoint = $request->path();
        
        // Different signatures for different contexts
        if (str_contains($endpoint, 'dashboard')) {
            // Dashboard endpoints get user-specific rate limiting
            return $userRole . ':dashboard:' . ($user?->id ?? 'guest') . ':' . $request->ip();
        }
        
        if (str_contains($endpoint, 'jaspel')) {
            // JASPEL endpoints get more lenient limits for real-time
            return $userRole . ':jaspel:' . ($user?->id ?? 'guest') . ':' . $request->ip();
        }
        
        // Default signature
        return parent::resolveRequestSignature($request);
    }

    /**
     * Dynamic rate limit calculation based on endpoint and user role
     */
    public function handle($request, $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        $endpoint = $request->path();
        $user = Auth::user();
        $userRole = $user?->roles?->first()?->name ?? 'guest';

        // 🚀 INTELLIGENT RATE LIMITING: Different limits for different use cases
        
        // Dashboard endpoints - Higher limits for real-time features
        if (str_contains($endpoint, 'dashboard')) {
            $maxAttempts = 180; // 3x normal limit for dashboards
            $decayMinutes = 1;
            
            // Even higher for authenticated medical staff
            if (in_array($userRole, ['dokter', 'paramedis', 'petugas'])) {
                $maxAttempts = 300; // 5x normal limit for medical staff
            }
            
            Log::debug('Dashboard rate limit applied', [
                'endpoint' => $endpoint,
                'user_role' => $userRole,
                'max_attempts' => $maxAttempts,
                'user_id' => $user?->id
            ]);
        }
        
        // JASPEL endpoints - Very high limits for gaming UI real-time updates
        elseif (str_contains($endpoint, 'jaspel')) {
            $maxAttempts = 240; // 4x normal limit for JASPEL
            $decayMinutes = 1;
            
            Log::debug('JASPEL rate limit applied', [
                'endpoint' => $endpoint,
                'user_role' => $userRole,
                'max_attempts' => $maxAttempts,
                'user_id' => $user?->id
            ]);
        }
        
        // Validation endpoints - High limits for bendahara workflows
        elseif (str_contains($endpoint, 'validation') || str_contains($endpoint, 'validasi')) {
            $maxAttempts = 120; // 2x normal limit for validation
            $decayMinutes = 1;
        }
        
        // Real-time endpoints - Maximum limits
        elseif (str_contains($endpoint, 'realtime') || str_contains($endpoint, 'live')) {
            $maxAttempts = 600; // 10x normal limit for real-time
            $decayMinutes = 1;
        }
        
        // 🎯 ROLE-BASED ADJUSTMENTS
        
        // Admin and management get higher limits
        if (in_array($userRole, ['admin', 'manajer'])) {
            $maxAttempts *= 2; // Double limits for admin roles
        }
        
        // Authenticated users get better limits than guests
        if ($user) {
            $maxAttempts = max($maxAttempts, 120); // Minimum 120 for authenticated users
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }

    /**
     * Create a 'too many attempts' response with helpful information
     */
    protected function buildException($request, $key, $maxAttempts, $responseCallback = null)
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);
        
        Log::warning('Rate limit exceeded', [
            'endpoint' => $request->path(),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'max_attempts' => $maxAttempts,
            'retry_after' => $retryAfter,
            'user_agent' => $request->header('User-Agent')
        ]);

        $response = response()->json([
            'message' => 'Too many requests. Please try again later.',
            'error' => 'RATE_LIMIT_EXCEEDED',
            'retry_after_seconds' => $retryAfter,
            'max_attempts' => $maxAttempts,
            'endpoint' => $request->path(),
            'suggestion' => 'Consider reducing auto-refresh frequency or using real-time WebSocket connections',
            'current_time' => now()->toISOString()
        ], 429);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }
}