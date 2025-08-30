<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Dashboard Action Controller
 * 
 * Handles dashboard-related actions like quick actions, shortcuts, etc.
 * Temporary stub to prevent route registration errors.
 */
class DashboardActionController extends Controller
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
            'message' => 'Dashboard actions endpoint - under construction'
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Action execution endpoint - under construction'
        ]);
    }
}