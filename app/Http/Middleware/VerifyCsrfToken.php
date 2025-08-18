<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'livewire/update',
        'livewire/upload-file',
        'livewire/message/*',
        'api/v2/dashboards/dokter/*',
        'api/v2/dashboards/dokter/checkin',
        'api/v2/dashboards/dokter/checkout',
        // Only exclude test routes, keep login protected
        'test-login',
        'test-csrf-post',
        // Temporarily exclude login to fix CSRF issues
        'login',        
        'unified-login',
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, \Closure $next)
    {
        // Log CSRF token information for debugging
        if ($request->is('login') || $request->is('unified-login')) {
            Log::info('Login route accessed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'has_session_token' => $request->session()->has('_token'),
                'session_token' => $request->session()->token() ? substr($request->session()->token(), 0, 10) . '...' : 'null',
                'request_token' => $request->input('_token') ? substr($request->input('_token'), 0, 10) . '...' : 'null',
                'header_token' => $request->header('X-CSRF-TOKEN') ? substr($request->header('X-CSRF-TOKEN'), 0, 10) . '...' : 'null',
                'all_headers' => $request->headers->all(),
            ]);
        }

        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            Log::warning('CSRF Token Mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'token_from_request' => $request->input('_token') ?: $request->header('X-CSRF-TOKEN'),
                'token_from_session' => $request->session()->token(),
                'referer' => $request->headers->get('referer'),
            ]);

            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch.',
                    'error' => 'csrf_token_mismatch',
                    'redirect' => route('login')
                ], 419);
            }

            // For regular requests, redirect with error message
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->withErrors([
                    'csrf' => 'Your session has expired. Please refresh the page and try again.',
                ]);
        }
    }
}