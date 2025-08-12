<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FilamentCsrfProtection
{
    /**
     * Handle an incoming request for Filament panels
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip CSRF for GET requests and safe methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Ensure session is started
        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }

        // For Filament panels, ensure CSRF token exists
        if (!$request->session()->has('_token')) {
            $request->session()->regenerateToken();
            Log::info('FilamentCsrfProtection: Generated new CSRF token', [
                'url' => $request->fullUrl(),
                'session_id' => $request->session()->getId()
            ]);
        }

        // Enhanced CSRF validation for Filament
        $sessionToken = $request->session()->token();
        $requestToken = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$requestToken || !hash_equals($sessionToken, $requestToken)) {
            Log::warning('FilamentCsrfProtection: CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_token' => $sessionToken ? substr($sessionToken, 0, 10) . '...' : 'null',
                'request_token' => $requestToken ? substr($requestToken, 0, 10) . '...' : 'null',
                'has_session_token' => $request->session()->has('_token'),
                'session_id' => $request->session()->getId()
            ]);

            // For AJAX/Filament requests, return JSON
            if ($request->expectsJson() || $request->is('*/livewire/*')) {
                return response()->json([
                    'message' => 'CSRF token mismatch. Please refresh the page and try again.',
                    'error' => 'csrf_token_mismatch',
                    'csrf_token' => csrf_token() // Provide new token
                ], 419);
            }

            // For regular requests, redirect with error
            return redirect()->back()
                ->withInput($request->except('_token', 'password'))
                ->withErrors([
                    'csrf' => 'Your session has expired. Please refresh the page and try again.',
                ]);
        }

        return $next($request);
    }
}