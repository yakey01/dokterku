<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For login pages, aggressively regenerate session to clear stale tokens
        if ($request->is('*/login') || $request->routeIs('*.auth.login')) {
            session()->invalidate();
            session()->regenerateToken();
            session()->migrate(true);
        }
        
        // Ensure session is started and CSRF token exists
        if (!session()->isStarted()) {
            session()->start();
        }
        
        if (!session()->token()) {
            session()->regenerateToken();
        }
        
        $response = $next($request);

        // Add CSRF token to all responses for Filament panels and dokter routes
        if ($request->is('admin*') || $request->is('paramedis*') || $request->is('bendahara*') || $request->is('manajer*') || $request->is('petugas*') || $request->is('dokter*') || $request->is('dokter-gigi*')) {
            $token = csrf_token();
            
            // Add CSRF token to response headers for AJAX requests
            $response->headers->set('X-CSRF-TOKEN', $token);
            
            // If it's an HTML response, add meta tag
            if ($response->headers->get('Content-Type') && 
                str_contains($response->headers->get('Content-Type'), 'text/html')) {
                
                $content = $response->getContent();
                if ($content && str_contains($content, '<head>')) {
                    $metaTag = '<meta name="csrf-token" content="' . $token . '">';
                    $content = str_replace('<head>', '<head>' . $metaTag, $content);
                    $response->setContent($content);
                }
            }
        }

        return $response;
    }
}