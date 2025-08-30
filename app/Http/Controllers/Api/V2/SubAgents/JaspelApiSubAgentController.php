<?php

namespace App\Http\Controllers\Api\V2\SubAgents;

use App\Http\Controllers\Controller;
use App\Services\SubAgents\ApiSubAgentService;
use App\Services\SubAgents\PetugasBendaharaFlowSubAgentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Jaspel API Sub-Agent Controller
 * 
 * RESTful API endpoints for jaspel reporting with sub-agent architecture
 */
class JaspelApiSubAgentController extends Controller
{
    protected ApiSubAgentService $apiSubAgent;
    protected PetugasBendaharaFlowSubAgentService $flowSubAgent;

    public function __construct(
        ApiSubAgentService $apiSubAgent,
        PetugasBendaharaFlowSubAgentService $flowSubAgent
    ) {
        $this->apiSubAgent = $apiSubAgent;
        $this->flowSubAgent = $flowSubAgent;
        
        // Apply authentication middleware
        $this->middleware('auth:sanctum');
        
        // Apply role-based middleware for specific endpoints
        $this->middleware('role:bendahara|admin|manajer')->only(['reports', 'summary']);
        $this->middleware('role:bendahara')->only(['export']);
    }

    /**
     * GET /api/v2/bendahara/jaspel-reports/{role}
     * Get jaspel reports data by role with filtering
     */
    public function reports(Request $request, string $role = 'semua'): JsonResponse
    {
        // Validate access
        $accessValidation = $this->apiSubAgent->validateApiAccess('jaspel_reports', auth()->id());
        if (!$accessValidation['valid']) {
            return response()->json([
                'success' => false,
                'error' => $accessValidation['error']
            ], $accessValidation['code']);
        }

        // Validate input parameters
        $validator = Validator::make(array_merge($request->all(), ['role' => $role]), [
            'role' => 'in:semua,dokter,paramedis,non_paramedis,petugas',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        // Prepare filters
        $filters = array_filter([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'search' => $request->search
        ]);

        // Delegate to ApiSubAgent
        return $this->apiSubAgent->handleJaspelReportsApi($role, $filters, auth()->id());
    }

    /**
     * GET /api/v2/bendahara/jaspel-summary/{userId}
     * Get detailed jaspel summary for specific user
     */
    public function summary(Request $request, int $userId): JsonResponse
    {
        // Validate access
        $accessValidation = $this->apiSubAgent->validateApiAccess('jaspel_summary', auth()->id());
        if (!$accessValidation['valid']) {
            return response()->json([
                'success' => false,
                'error' => $accessValidation['error']
            ], $accessValidation['code']);
        }

        // Validate input parameters
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        // Prepare filters
        $filters = array_filter([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to
        ]);

        // Delegate to ApiSubAgent
        return $this->apiSubAgent->handleJaspelUserDetailApi($userId, $filters);
    }

    /**
     * POST /api/v2/bendahara/jaspel-export
     * Export jaspel data in various formats
     */
    public function export(Request $request): JsonResponse
    {
        // Validate access (stricter for exports)
        $accessValidation = $this->apiSubAgent->validateApiAccess('jaspel_export', auth()->id(), ['bendahara']);
        if (!$accessValidation['valid']) {
            return response()->json([
                'success' => false,
                'error' => $accessValidation['error']
            ], $accessValidation['code']);
        }

        // Validate input parameters
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:excel,pdf,csv',
            'role' => 'nullable|in:semua,dokter,paramedis,non_paramedis,petugas',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        // Prepare filters
        $filters = array_filter([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'search' => $request->search
        ]);

        $role = $request->role ?? 'semua';
        $format = $request->format;

        // Delegate to ApiSubAgent
        return $this->apiSubAgent->handleJaspelExportApi($format, $role, $filters);
    }

    /**
     * GET /api/v2/bendahara/jaspel-health
     * Get API health status and performance metrics
     */
    public function health(Request $request): JsonResponse
    {
        // Public endpoint for health checks
        $period = $request->get('period', 'today');
        
        if (!in_array($period, ['today', 'week', 'month'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid period. Use: today, week, or month'
            ], 400);
        }

        $healthData = $this->apiSubAgent->getApiHealthStatus();
        $metricsData = $this->apiSubAgent->getApiPerformanceMetrics($period);

        return response()->json([
            'success' => true,
            'health' => $healthData,
            'metrics' => $metricsData,
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * GET /api/v2/bendahara/jaspel-roles
     * Get available roles for filtering
     */
    public function roles(): JsonResponse
    {
        // Simple endpoint that doesn't require heavy processing
        $roles = [
            'semua' => 'Semua Role',
            'dokter' => 'Dokter (Umum + Gigi)', 
            'paramedis' => 'Paramedis',
            'non_paramedis' => 'Non-Paramedis',
            'petugas' => 'Petugas'
        ];

        return response()->json([
            'success' => true,
            'data' => $roles,
            'metadata' => [
                'count' => count($roles),
                'generated_at' => Carbon::now()->toISOString()
            ]
        ]);
    }

    /**
     * POST /api/v2/bendahara/jaspel-cache/clear
     * Clear jaspel-related caches (bendahara only)
     */
    public function clearCache(Request $request): JsonResponse
    {
        // Validate access (bendahara only)
        $accessValidation = $this->apiSubAgent->validateApiAccess('jaspel_export', auth()->id(), ['bendahara']);
        if (!$accessValidation['valid']) {
            return response()->json([
                'success' => false,
                'error' => $accessValidation['error']
            ], $accessValidation['code']);
        }

        try {
            // Clear API cache
            $this->apiSubAgent->clearApiCache();
            
            // Clear database sub-agent cache
            app(\App\Services\SubAgents\DatabaseSubAgentService::class)->clearRelatedCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache berhasil dibersihkan',
                'timestamp' => Carbon::now()->toISOString()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal membersihkan cache',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * GET /api/v2/bendahara/petugas-flow/analyze
     * Analyze petugas to bendahara data flow
     */
    public function analyzeFlow(): JsonResponse
    {
        try {
            $flowAnalysis = $this->flowSubAgent->analyzeDataFlow();
            
            return response()->json([
                'success' => true,
                'data' => $flowAnalysis,
                'timestamp' => Carbon::now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('JaspelApiSubAgent: Flow analysis failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal menganalisis data flow petugas → bendahara'
            ], 500);
        }
    }

    /**
     * POST /api/v2/bendahara/petugas-flow/create-test-data
     * Create test petugas input for workflow testing
     */
    public function createTestData(Request $request): JsonResponse
    {
        // Validate access (bendahara only for test data creation)
        $accessValidation = $this->apiSubAgent->validateApiAccess('jaspel_export', auth()->id(), ['bendahara']);
        if (!$accessValidation['valid']) {
            return response()->json([
                'success' => false,
                'error' => $accessValidation['error']
            ], $accessValidation['code']);
        }

        $validator = Validator::make($request->all(), [
            'petugas_user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->flowSubAgent->createTestPetugasInput($request->petugas_user_id);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Test data berhasil dibuat untuk workflow petugas → bendahara'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('JaspelApiSubAgent: Test data creation failed', [
                'error' => $e->getMessage(),
                'petugas_user_id' => $request->petugas_user_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal membuat test data workflow'
            ], 500);
        }
    }

    /**
     * GET /api/v2/bendahara/petugas-flow/activities
     * Track petugas input activities
     */
    public function trackActivities(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $dateRange = [];
            if ($request->start_date) {
                $dateRange['start'] = Carbon::parse($request->start_date);
            }
            if ($request->end_date) {
                $dateRange['end'] = Carbon::parse($request->end_date);
            }

            $activities = $this->flowSubAgent->trackPetugasInputActivities($dateRange);
            
            return response()->json([
                'success' => true,
                'data' => $activities,
                'timestamp' => Carbon::now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('JaspelApiSubAgent: Activity tracking failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal melacak aktivitas petugas'
            ], 500);
        }
    }

    /**
     * GET /api/v2/bendahara/petugas-flow/metrics
     * Get workflow performance metrics
     */
    public function workflowMetrics(): JsonResponse
    {
        try {
            $metrics = $this->flowSubAgent->getWorkflowPerformanceMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => Carbon::now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('JaspelApiSubAgent: Workflow metrics failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal mengambil metrics workflow'
            ], 500);
        }
    }
}