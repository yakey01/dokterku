<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Dashboard Export Controller
 * 
 * Handles dashboard data exports (PDF, Excel, CSV, etc.).
 * Temporary stub to prevent route registration errors.
 */
class DashboardExportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Dashboard export endpoint - under construction'
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Export generation endpoint - under construction'
        ]);
    }
}