<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

/**
 * Middleware to handle OpenAPI documentation generation and serving
 */
class OpenApiDocumentationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only process OpenAPI documentation requests
        if (!$this->isDocumentationRequest($request)) {
            return $next($request);
        }

        // Security check - only allow in development or with proper authorization
        if (!$this->isAuthorized($request)) {
            return response()->json([
                'error' => 'Documentation access denied',
                'message' => 'API documentation is not available in this environment'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if this is a documentation request
     */
    private function isDocumentationRequest(Request $request): bool
    {
        $path = $request->path();
        
        return str_contains($path, 'api-docs') || 
               str_contains($path, 'swagger') || 
               str_contains($path, 'openapi');
    }

    /**
     * Check if user is authorized to access documentation
     */
    private function isAuthorized(Request $request): bool
    {
        // Allow in development
        if (App::environment('local', 'development')) {
            return true;
        }

        // Check for proper authorization token or admin role
        if ($request->bearerToken()) {
            $user = $request->user();
            return $user && in_array($user->role, ['admin', 'developer']);
        }

        // Check for special documentation access key
        return $request->header('X-Docs-Key') === config('app.docs_key');
    }
}