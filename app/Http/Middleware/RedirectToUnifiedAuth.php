<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RedirectToUnifiedAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Enhanced debugging for admin login redirect loop
        $isAuthenticated = Auth::check();
        $expectsJson = $request->expectsJson();
        $sessionId = $request->session()->getId();
        $userId = Auth::id();
        
        Log::info('RedirectToUnifiedAuth middleware check', [
            'path' => $request->path(),
            'url' => $request->url(),
            'is_authenticated' => $isAuthenticated,
            'expects_json' => $expectsJson,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'user_agent' => substr($request->userAgent() ?? '', 0, 100)
        ]);
        
        if (!$isAuthenticated && !$expectsJson) {
            // Only redirect if this is not already a login-related page
            // to prevent redirect loops
            $isLoginPage = $request->is('login') || 
                          $request->is('welcome-login') || 
                          $request->is('unified-login') ||
                          str_contains($request->path(), 'login');
            
            if (!$isLoginPage) {
                Log::info('RedirectToUnifiedAuth: Redirecting unauthenticated user to login', [
                    'from_url' => $request->url(),
                    'session_id' => $sessionId
                ]);
                
                // Store intended URL for redirect after login
                session(['url.intended' => $request->url()]);
                
                return redirect()->route('login')->with('info', 'Please login to access the dashboard');
            } else {
                Log::info('RedirectToUnifiedAuth: Skipping redirect for login page', [
                    'path' => $request->path()
                ]);
            }
        }

        return $next($request);
    }
}