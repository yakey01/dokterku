<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\AssignmentHistory;
use App\Services\SmartWorkLocationAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WorkLocationAssignmentController extends Controller
{
    protected SmartWorkLocationAssignmentService $assignmentService;

    public function __construct(SmartWorkLocationAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get comprehensive assignment dashboard data
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            // Get analytics
            $analytics = $this->assignmentService->getAssignmentAnalytics();
            
            // Get recent assignments
            $recentAssignments = AssignmentHistory::with(['user', 'workLocation', 'assignedBy'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'user' => [
                            'id' => $assignment->user->id,
                            'name' => $assignment->user->name,
                            'role' => $assignment->user->role?->name
                        ],
                        'location' => [
                            'id' => $assignment->workLocation->id,
                            'name' => $assignment->workLocation->name,
                            'type' => $assignment->workLocation->location_type
                        ],
                        'assigned_by' => $assignment->assignedBy?->name ?? 'System',
                        'method' => $assignment->method_label,
                        'score' => $assignment->assignment_score,
                        'confidence' => $assignment->confidence_badge,
                        'reasons' => $assignment->formatted_reasons,
                        'assigned_at' => $assignment->created_at->diffForHumans()
                    ];
                });

            // Get users needing assignment
            $usersNeedingAssignment = User::whereNull('work_location_id')
                ->with(['role', 'pegawai'])
                ->limit(20)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role?->name ?? 'No Role',
                        'unit_kerja' => $user->pegawai?->unit_kerja ?? 'Not Set',
                        'email' => $user->email,
                        'status' => 'needs_assignment'
                    ];
                });

            // Get location utilization
            $locationUtilization = WorkLocation::where('is_active', true)
                ->withCount('users')
                ->get()
                ->map(function ($location) {
                    $utilization = $location->getCapacityUtilization();
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => $location->location_type,
                        'unit_kerja' => $location->unit_kerja,
                        'current_users' => $utilization['current_users'],
                        'utilization_percentage' => $utilization['utilization_percentage'],
                        'status' => $utilization['status'],
                        'is_active' => $location->is_active
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'analytics' => $analytics,
                    'recent_assignments' => $recentAssignments,
                    'users_needing_assignment' => $usersNeedingAssignment,
                    'location_utilization' => $locationUtilization,
                    'summary' => [
                        'total_locations' => WorkLocation::where('is_active', true)->count(),
                        'pending_assignments' => $usersNeedingAssignment->count(),
                        'recent_activity' => $recentAssignments->count()
                    ]
                ],
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'version' => '1.0'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard data error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🧠 Get smart assignment recommendations for a user
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $recommendations = $this->assignmentService->getAssignmentRecommendations($user);

        return response()->json([
            'success' => true,
            'message' => 'Recommendations generated successfully',
            'data' => $recommendations
        ]);
    }

    /**
     * 🎯 Execute smart assignment for a single user
     */
    public function smartAssignment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $result = $this->assignmentService->intelligentAssignment($user);

        return response()->json($result);
    }

    /**
     * 📋 Execute bulk smart assignment
     */
    public function bulkSmartAssignment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'assignment_type' => 'in:all_unassigned,selected_users',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->assignment_type === 'all_unassigned') {
                $users = User::whereNull('work_location_id')->get();
            } else {
                $users = User::whereIn('id', $request->user_ids ?? [])->get();
            }

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found for assignment'
                ], 400);
            }

            $result = $this->assignmentService->bulkIntelligentAssignment($users);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk assignment completed: {$result['successful']} successful, {$result['failed']} failed",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bulk assignment error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 👤 Manual assignment
     */
    public function manualAssignment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'work_location_id' => 'required|exists:work_locations,id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);
            $workLocation = WorkLocation::findOrFail($request->work_location_id);
            
            $previousLocationId = $user->work_location_id;
            
            // Update user
            $user->work_location_id = $workLocation->id;
            $user->save();

            // Create assignment history
            AssignmentHistory::create([
                'user_id' => $user->id,
                'work_location_id' => $workLocation->id,
                'previous_work_location_id' => $previousLocationId,
                'assigned_by' => auth()->id(),
                'assignment_method' => 'manual',
                'assignment_reasons' => ['Manual assignment by admin'],
                'assignment_score' => null,
                'metadata' => [
                    'assigned_by_name' => auth()->user()->name,
                    'timestamp' => now()->toISOString()
                ],
                'notes' => $request->notes
            ]);

            // Clear caches
            $this->clearUserCaches($user);

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned {$user->name} to {$workLocation->name}",
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ],
                    'location' => [
                        'id' => $workLocation->id,
                        'name' => $workLocation->name
                    ],
                    'assigned_by' => auth()->user()->name,
                    'assigned_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Manual assignment error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Manual assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Get assignment history
     */
    public function getAssignmentHistory(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 50);
        $search = $request->get('search');
        $method = $request->get('method');
        $location = $request->get('location');

        $query = AssignmentHistory::with(['user', 'workLocation', 'assignedBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($method) {
            $query->where('assignment_method', $method);
        }

        if ($location) {
            $query->where('work_location_id', $location);
        }

        $assignments = $query->paginate($perPage);

        $formattedAssignments = $assignments->getCollection()->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'user' => [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'email' => $assignment->user->email,
                    'role' => $assignment->user->role?->name
                ],
                'location' => [
                    'id' => $assignment->workLocation->id,
                    'name' => $assignment->workLocation->name,
                    'type' => $assignment->workLocation->location_type
                ],
                'previous_location' => $assignment->previousWorkLocation ? [
                    'id' => $assignment->previousWorkLocation->id,
                    'name' => $assignment->previousWorkLocation->name
                ] : null,
                'assigned_by' => $assignment->assignedBy?->name ?? 'System',
                'method' => $assignment->method_label,
                'score' => $assignment->assignment_score,
                'confidence' => $assignment->confidence_badge,
                'reasons' => $assignment->formatted_reasons,
                'notes' => $assignment->notes,
                'assigned_at' => $assignment->created_at->toISOString(),
                'assigned_at_human' => $assignment->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Assignment history retrieved successfully',
            'data' => [
                'assignments' => $formattedAssignments,
                'pagination' => [
                    'current_page' => $assignments->currentPage(),
                    'last_page' => $assignments->lastPage(),
                    'per_page' => $assignments->perPage(),
                    'total' => $assignments->total()
                ]
            ]
        ]);
    }

    /**
     * 🗑️ Remove assignment (set location to null)
     */
    public function removeAssignment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);
            $previousLocation = $user->workLocation;
            
            if (!$previousLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a work location assigned'
                ], 400);
            }

            // Remove assignment
            $user->work_location_id = null;
            $user->save();

            // Create history record
            AssignmentHistory::create([
                'user_id' => $user->id,
                'work_location_id' => $previousLocation->id,
                'previous_work_location_id' => $previousLocation->id,
                'assigned_by' => auth()->id(),
                'assignment_method' => 'manual',
                'assignment_reasons' => ['Assignment removed by admin'],
                'metadata' => [
                    'action' => 'removed',
                    'reason' => $request->reason,
                    'removed_by' => auth()->user()->name,
                    'timestamp' => now()->toISOString()
                ],
                'notes' => $request->reason
            ]);

            // Clear caches
            $this->clearUserCaches($user);

            return response()->json([
                'success' => true,
                'message' => "Successfully removed work location assignment for {$user->name}",
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ],
                    'removed_location' => [
                        'id' => $previousLocation->id,
                        'name' => $previousLocation->name
                    ],
                    'removed_by' => auth()->user()->name,
                    'removed_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Remove assignment error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear user caches
     */
    private function clearUserCaches(User $user): void
    {
        $cacheKeys = [
            "user_work_location_{$user->id}",
            "paramedis_dashboard_stats_{$user->id}",
            "dokter_dashboard_stats_{$user->id}",
            "attendance_status_{$user->id}",
        ];
        
        foreach ($cacheKeys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }
    }
}