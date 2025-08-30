<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class LivewireDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // SPECIAL: Log any GET requests to Livewire endpoints to debug the routing error
        if ($request->isMethod('GET') && $request->is('livewire/*')) {
            Log::channel('stack')->error('🚨 GET REQUEST TO LIVEWIRE ENDPOINT DETECTED', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('Referer'),
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'route_name' => $request->route()?->getName(),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                'timestamp' => now()->toISOString()
            ]);
        }
        // Only monitor Livewire update requests
        if ($request->is("livewire/update*") || $request->is("livewire/message/*")) {
            
            $startTime = microtime(true);
            $requestId = uniqid("lw_", true);
            
            // Log the incoming request
            Log::channel("stack")->info("🔵 LIVEWIRE REQUEST START", [
                "request_id" => $requestId,
                "url" => $request->fullUrl(),
                "method" => $request->method(),
                "user_agent" => $request->userAgent(),
                "ip" => $request->ip(),
                "user_id" => auth()->id(),
                "session_id" => session()->getId(),
                "payload_preview" => substr($request->getContent(), 0, 200),
                "headers" => [
                    "X-Livewire" => $request->header("X-Livewire"),
                    "X-CSRF-TOKEN" => $request->header("X-CSRF-TOKEN"),
                    "Content-Type" => $request->header("Content-Type"),
                ],
                "timestamp" => now()->toISOString()
            ]);
            
            // Try to decode Livewire payload to identify component
            try {
                $payload = json_decode($request->getContent(), true);
                if (isset($payload["fingerprint"]["name"])) {
                    Log::channel("stack")->info("🎯 LIVEWIRE COMPONENT IDENTIFIED", [
                        "request_id" => $requestId,
                        "component_name" => $payload["fingerprint"]["name"],
                        "component_id" => $payload["fingerprint"]["id"] ?? "unknown",
                        "updates" => $payload["updates"] ?? [],
                        "calls" => array_keys($payload["calls"] ?? [])
                    ]);
                }
            } catch (Exception $e) {
                Log::channel("stack")->warning("⚠️ Could not decode Livewire payload", [
                    "request_id" => $requestId,
                    "error" => $e->getMessage()
                ]);
            }
        }

        try {
            $response = $next($request);
            
            if ($request->is("livewire/update*") || $request->is("livewire/message/*")) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::channel("stack")->info("🟢 LIVEWIRE REQUEST SUCCESS", [
                    "request_id" => $requestId,
                    "status" => $response->getStatusCode(),
                    "duration_ms" => $duration,
                    "response_size" => strlen($response->getContent()),
                    "memory_peak" => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB"
                ]);
            }
            
            return $response;
            
        } catch (Exception $e) {
            if ($request->is("livewire/update*") || $request->is("livewire/message/*")) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                Log::channel("stack")->error("🔴 LIVEWIRE REQUEST ERROR", [
                    "request_id" => $requestId,
                    "error_class" => get_class($e),
                    "error_message" => $e->getMessage(),
                    "error_file" => $e->getFile(),
                    "error_line" => $e->getLine(),
                    "duration_ms" => $duration,
                    "memory_peak" => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB",
                    "stack_trace" => array_slice(explode("\n", $e->getTraceAsString()), 0, 10)
                ]);
                
                // Log additional context for debugging
                Log::channel("stack")->error("🔴 LIVEWIRE ERROR CONTEXT", [
                    "request_id" => $requestId,
                    "request_content" => substr($request->getContent(), 0, 1000),
                    "all_headers" => $request->headers->all(),
                    "route_info" => [
                        "name" => $request->route()?->getName(),
                        "action" => $request->route()?->getActionName(),
                        "middleware" => $request->route()?->middleware() ?? []
                    ]
                ]);
            }
            
            throw $e;
        }
    }
}