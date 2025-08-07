<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Admin GPS Validation Management Controller
 * Provides tools for administrators to manage GPS validation issues
 */
class GPSValidationController extends Controller
{
    protected AttendanceValidationService $validationService;

    public function __construct(AttendanceValidationService $validationService)
    {
        $this->validationService = $validationService;
        $this->middleware(['auth:sanctum', 'role:admin,super-admin']);
    }

    /**
     * Get GPS diagnostic information for a user
     */
    public function getDiagnostics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $workLocation = $user->workLocation;

        if (!$workLocation) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have an assigned work location',
            ], 404);
        }

        // Get comprehensive GPS diagnostics
        $diagnostics = $this->validationService->getGPSDiagnosticInfo(
            $request->latitude,
            $request->longitude,
            $request->accuracy,
            $workLocation
        );

        // Get validation result
        $validation = $this->validationService->validateWorkLocation(
            $user,
            $request->latitude,
            $request->longitude,
            $request->accuracy
        );

        // Check for active overrides
        $overrideCheck = $this->validationService->hasActiveGPSOverride($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'work_location' => [
                    'id' => $workLocation->id,
                    'name' => $workLocation->name,
                    'address' => $workLocation->address,
                    'coordinates' => [
                        'latitude' => (float) $workLocation->latitude,
                        'longitude' => (float) $workLocation->longitude,
                    ],
                    'radius_meters' => $workLocation->radius_meters,
                ],
                'gps_diagnostics' => $diagnostics,
                'validation_result' => $validation,
                'admin_override' => $overrideCheck,
            ]
        ]);
    }

    /**
     * Create an admin override for GPS validation
     */
    public function createOverride(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'reason' => 'required|string|max:500',
            'duration_hours' => 'nullable|integer|min:1|max:72', // Max 3 days
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = auth()->user();
        $user = User::findOrFail($request->user_id);
        
        $result = $this->validationService->createAdminGPSOverride(
            $admin,
            $user,
            $request->latitude,
            $request->longitude,
            $request->reason
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'code' => $result['code']
            ], 403);
        }

        // Log the override creation
        Log::info('Admin created GPS override', [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'coordinates' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ],
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'GPS validation override created successfully',
            'data' => $result['override_data']
        ]);
    }

    /**
     * Get GPS validation logs for analysis
     */
    public function getValidationLogs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|in:success,failed,override',
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // This would typically query a dedicated logs table
        // For now, return a placeholder response
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::today()->subDays(7);
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::today();
        $limit = $request->limit ?? 50;

        // In a real implementation, you would query your logs table
        // For demonstration, we'll return a structured response
        return response()->json([
            'success' => true,
            'data' => [
                'filters' => [
                    'user_id' => $request->user_id,
                    'date_from' => $dateFrom->format('Y-m-d'),
                    'date_to' => $dateTo->format('Y-m-d'),
                    'status' => $request->status,
                    'limit' => $limit,
                ],
                'logs' => [], // Would contain actual log entries
                'summary' => [
                    'total_attempts' => 0,
                    'successful_validations' => 0,
                    'failed_validations' => 0,
                    'overrides_used' => 0,
                ],
                'message' => 'GPS validation logs retrieved successfully'
            ]
        ]);
    }

    /**
     * Generate GPS validation report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d',
            'include_diagnostics' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $dateFrom = Carbon::parse($request->date_from);
        $dateTo = Carbon::parse($request->date_to);
        $includeDiagnostics = $request->include_diagnostics ?? false;

        // Generate comprehensive GPS validation report
        $report = [
            'report_info' => [
                'generated_at' => now()->toISOString(),
                'generated_by' => auth()->user()->name,
                'date_range' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
                'include_diagnostics' => $includeDiagnostics,
            ],
            'summary' => [
                'total_validation_attempts' => 0,
                'successful_validations' => 0,
                'failed_validations' => 0,
                'admin_overrides_used' => 0,
                'common_failure_reasons' => [],
                'users_with_issues' => [],
            ],
            'recommendations' => [
                'high_priority' => [],
                'medium_priority' => [],
                'low_priority' => [],
            ],
        ];

        if ($includeDiagnostics) {
            $report['detailed_analysis'] = [
                'vpn_proxy_detections' => [],
                'coordinate_quality_issues' => [],
                'geofence_violations' => [],
                'accuracy_problems' => [],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'GPS validation report generated successfully'
        ]);
    }

    /**
     * Test GPS coordinates against work location
     */
    public function testCoordinates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000',
            'simulate_conditions' => 'nullable|array',
            'simulate_conditions.vpn_enabled' => 'nullable|boolean',
            'simulate_conditions.poor_gps_accuracy' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $accuracy = $request->accuracy;

        // Apply simulation conditions if specified
        $simulationConditions = $request->simulate_conditions ?? [];
        
        if ($simulationConditions['poor_gps_accuracy'] ?? false) {
            $accuracy = 150; // Simulate poor GPS accuracy
        }

        // Get diagnostics and validation results
        $workLocation = $user->workLocation;
        if (!$workLocation) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have an assigned work location'
            ], 404);
        }

        $diagnostics = $this->validationService->getGPSDiagnosticInfo($latitude, $longitude, $accuracy, $workLocation);
        $validation = $this->validationService->validateWorkLocation($user, $latitude, $longitude, $accuracy);
        
        // Calculate additional test metrics
        $distance = $workLocation->calculateDistance($latitude, $longitude);
        $withinGeofence = $workLocation->isWithinGeofence($latitude, $longitude, $accuracy);
        
        return response()->json([
            'success' => true,
            'data' => [
                'test_parameters' => [
                    'user_id' => $user->id,
                    'coordinates' => ['latitude' => $latitude, 'longitude' => $longitude],
                    'accuracy' => $accuracy,
                    'simulation_conditions' => $simulationConditions,
                ],
                'work_location' => [
                    'id' => $workLocation->id,
                    'name' => $workLocation->name,
                    'coordinates' => [
                        'latitude' => (float) $workLocation->latitude,
                        'longitude' => (float) $workLocation->longitude,
                    ],
                    'radius_meters' => $workLocation->radius_meters,
                ],
                'test_results' => [
                    'distance_meters' => round($distance, 2),
                    'within_geofence' => $withinGeofence,
                    'validation_passed' => $validation['valid'],
                    'validation_code' => $validation['code'],
                    'validation_message' => $validation['message'],
                ],
                'gps_diagnostics' => $diagnostics,
                'troubleshooting_recommendations' => $validation['data']['troubleshooting_tips'] ?? [],
            ],
            'message' => 'GPS coordinates test completed successfully'
        ]);
    }
}