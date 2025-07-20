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
        $response = $next($request);

        // Add CSRF token to all responses for Filament panels
        if ($request->is('admin*') || $request->is('paramedis*') || $request->is('bendahara*') || $request->is('manajer*') || $request->is('petugas*')) {
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