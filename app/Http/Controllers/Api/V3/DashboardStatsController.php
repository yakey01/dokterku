<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Dashboard Statistics Controller
 * 
 * Provides statistical data for dashboard components.
 * Temporary stub to prevent route registration errors.
 */
class DashboardStatsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function daily(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Daily stats endpoint - under construction'
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Weekly stats endpoint - under construction'
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Monthly stats endpoint - under construction'
        ]);
    }

    public function period(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Period stats endpoint - under construction'
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Compare stats endpoint - under construction'
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Trends stats endpoint - under construction'
        ]);
    }

    public function team(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Team stats endpoint - under construction'
        ]);
    }
}