<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClearStaleSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TEMPORARILY DISABLED TO FIX CSRF ISSUES
        // Only clear stale sessions for GET requests on login pages
        // if ($this->isLoginPage($request) && $request->isMethod('GET')) {
        //     $this->clearStaleSession($request);
        // }
        
        return $next($request);
    }
    
    /**
     * Check if this is a login page request
     */
    private function isLoginPage(Request $request): bool
    {
        return $request->is('*/login') || 
               $request->routeIs('*.auth.login') ||
               str_contains($request->path(), '/login');
    }
    
    /**
     * Clear stale session data more carefully
     */
    private function clearStaleSession(Request $request): void
    {
        try {
            // Only clear if user is not authenticated and it's a GET request
            // Don't clear on POST requests as they need existing CSRF tokens
            if (!auth()->check() && $request->isMethod('GET')) {
                // Check if session is stale (older than 1 hour without activity)
                $lastActivity = session()->get('_last_activity', 0);
                $currentTime = time();
                $isStale = ($currentTime - $lastActivity) > 3600; // 1 hour
                
                if ($isStale) {
                    // Only regenerate if session is actually stale
                    session()->invalidate();
                    session()->regenerateToken();
                    
                    // Clear any stale CSRF tokens from cookies
                    if ($request->hasCookie('XSRF-TOKEN')) {
                        cookie()->queue(cookie()->forget('XSRF-TOKEN'));
                    }
                }
                
                // Update last activity time
                session()->put('_last_activity', $currentTime);
            }
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::warning('Session cleanup failed: ' . $e->getMessage());
        }
    }
}