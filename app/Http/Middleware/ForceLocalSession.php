<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForceLocalSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Force session domain to null for localhost development
        if ($request->getHost() === '127.0.0.1' || $request->getHost() === 'localhost') {
            config(['session.domain' => null]);
            config(['app.url' => 'http://127.0.0.1:8000']);
            config(['database.default' => 'sqlite']);
            config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
            
            // FIXED: Ensure session is started before accessing token
            if (!$request->session()->isStarted()) {
                $request->session()->start();
            }
            
            // Generate CSRF token if missing
            if (!$request->session()->has('_token')) {
                $request->session()->regenerateToken();
            }
            
            Log::debug('ForceLocalSession middleware executed', [
                'session_id' => $request->session()->getId(),
                'session_started' => $request->session()->isStarted(),
                'has_token' => $request->session()->has('_token'),
                'token_length' => $request->session()->token() ? strlen($request->session()->token()) : 0,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        }
        
        return $next($request);
    }
}