<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PersistentCsrfToken
{
    /**
     * Handle an incoming request.
     * Ensures CSRF tokens persist for the entire session lifetime.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only regenerate token if:
        // 1. No token exists in session
        // 2. User is explicitly logging out
        // 3. Session is being invalidated
        
        $session = $request->session();
        
        // Check if we have an existing token
        if (!$session->has('_token')) {
            // Generate new token only if none exists
            $session->regenerateToken();
            
            Log::info('CSRF token generated for new session', [
                'session_id' => $session->getId(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        // For logout requests, mark token for regeneration
        if ($request->is('logout') || $request->is('*/logout')) {
            $session->put('_token_regenerate', true);
            
            Log::info('CSRF token marked for regeneration on logout', [
                'session_id' => $session->getId(),
                'user' => auth()->user()?->email,
            ]);
        }
        
        $response = $next($request);
        
        // After logout is processed, regenerate the token
        if ($session->has('_token_regenerate') && !auth()->check()) {
            $session->regenerateToken();
            $session->forget('_token_regenerate');
            
            Log::info('CSRF token regenerated after logout', [
                'session_id' => $session->getId(),
            ]);
        }
        
        return $response;
    }
}