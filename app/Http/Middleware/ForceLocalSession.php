<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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
            
            // Ensure session is started and CSRF token is available
            if (!session()->isStarted()) {
                session()->start();
            }
            
            // Regenerate CSRF token if not present
            if (!session()->token()) {
                session()->regenerateToken();
            }
        }
        
        return $next($request);
    }
}