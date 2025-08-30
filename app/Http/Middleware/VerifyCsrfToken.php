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
        // Only exclude test routes
        'test-login',
        'test-csrf-post',
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