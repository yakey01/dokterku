<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UnifiedAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Supports both session-based and token-based authentication
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try session authentication first (for web interface)
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        // Try API token authentication (for mobile/API)
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Check for Bearer token in headers
        $token = $request->bearerToken();
        if ($token) {
            try {
                // Manually validate token using Sanctum
                $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($personalAccessToken && $personalAccessToken->tokenable) {
                    // Set the authenticated user for the request
                    Auth::guard('sanctum')->setUser($personalAccessToken->tokenable);
                    return $next($request);
                }
            } catch (\Exception $e) {
                \Log::warning('Token validation failed in UnifiedAuthMiddleware', [
                    'error' => $e->getMessage(),
                    'token_prefix' => substr($token, 0, 10) . '...'
                ]);
            }
        }

        // If this is an API request, return JSON error
        if ($request->expectsJson() || $request->is('api/*') || $request->is('*/web-api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Please login first.',
                'error' => 'authentication_required',
                'auth_options' => [
                    'web_login' => url('/login'),
                    'api_login' => url('/api/v2/auth/login'),
                    'dokter_panel' => url('/dokter/login')
                ]
            ], 401);
        }

        // For web requests, redirect to login
        return redirect()->guest(route('login'));
    }
}