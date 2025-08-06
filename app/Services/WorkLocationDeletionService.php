<?php

namespace App\Services;

use App\Models\WorkLocation;
use App\Models\User;
use App\Models\AssignmentHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Enterprise-grade WorkLocation Deletion Service
 * 
 * Handles safe deletion of work locations with proper cascade handling,
 * transaction safety, and comprehensive dependency management.
 */
class WorkLocationDeletionService
{
    /**
     * Safe deletion of work location with comprehensive dependency handling
     */
    public function safeDelete(WorkLocation $workLocation, array $options = []): array
    {
        $options = array_merge([
            'force_delete' => false,
            'reassign_users' => true,
            'preserve_history' => true,
            'notify_users' => true,
            'reason' => 'Administrative deletion',
            'assigned_by' => auth()->id(),
        ], $options);

        return DB::transaction(function () use ($workLocation, $options) {
            try {
                // Step 1: Validate deletion possibility
                $dependencyCheck = $this->checkDependencies($workLocation);
                if (!$dependencyCheck['can_delete']) {
                    return [
                        'success' => false,
                        'message' => 'Cannot delete work location due to dependencies',
                        'dependencies' => $dependencyCheck,
                        'error_code' => 'DEPENDENCY_VIOLATION'
                    ];
                }

                // Step 2: Handle user reassignments if requested
                $userReassignmentResult = [];
                if ($options['reassign_users'] && $dependencyCheck['assigned_users_count'] > 0) {
                    $userReassignmentResult = $this->reassignUsers($workLocation, $options);
                    if (!$userReassignmentResult['success']) {
                        throw new Exception('Failed to reassign users: ' . $userReassignmentResult['message']);
                    }
                }

                // Step 3: Archive related data before deletion
                $archiveResult = $this->archiveRelatedData($workLocation, $options);

                // Step 4: Perform the deletion
                $deletionResult = $this->performDeletion($workLocation, $options);

                // Step 5: Log the deletion
                $this->logDeletion($workLocation, $options, [
                    'dependencies_checked' => $dependencyCheck,
                    'users_reassigned' => $userReassignmentResult,
                    'data_archived' => $archiveResult,
                ]);

                // Step 6: Clear related caches
                $this->clearRelatedCaches($workLocation);

                return [
                    'success' => true,
                    'message' => "Work location '{$workLocation->name}' deleted successfully",
                    'data' => [
                        'deleted_location' => [
                            'id' => $workLocation->id,
                            'name' => $workLocation->name,
                            'deletion_type' => $options['force_delete'] ? 'hard_delete' : 'soft_delete'
                        ],
                        'users_reassigned' => $userReassignmentResult['count'] ?? 0,
                        'data_archived' => $archiveResult['archived_records'] ?? 0,
                        'dependencies_resolved' => $dependencyCheck
                    ],
                    'meta' => [
                        'deleted_at' => now()->toISOString(),
                        'deleted_by' => auth()->user()->name ?? 'System',
                        'reason' => $options['reason']
                    ]
                ];

            } catch (Exception $e) {
                Log::error('Work location deletion failed', [
                    'work_location_id' => $workLocation->id,
                    'work_location_name' => $workLocation->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'options' => $options
                ]);

                throw $e;
            }
        });
    }

    /**
     * Check all dependencies before deletion
     */
    public function checkDependencies(WorkLocation $workLocation): array
    {
        // Check assigned users
        $assignedUsers = User::where('work_location_id', $workLocation->id)->get();
        
        // Check assignment histories (should be preserved)
        $assignmentHistories = AssignmentHistory::where('work_location_id', $workLocation->id)
            ->orWhere('previous_work_location_id', $workLocation->id)
            ->count();

        // Check attendances (if exists)
        $attendancesCount = 0;
        if (class_exists(\App\Models\Attendance::class)) {
            $attendancesCount = \App\Models\Attendance::where('work_location_id', $workLocation->id)->count();
        }

        // Check location validations
        $locationValidationsCount = 0;
        if (DB::getSchemaBuilder()->hasTable('location_validations')) {
            $locationValidationsCount = DB::table('location_validations')
                ->where('work_location_id', $workLocation->id)
                ->count();
        }

        // Determine if deletion is safe
        $blockingDependencies = [];
        $warnings = [];

        if ($assignedUsers->count() > 0) {
            $warnings[] = "Has {$assignedUsers->count()} assigned users";
        }

        if ($attendancesCount > 0) {
            $blockingDependencies[] = "Has {$attendancesCount} attendance records";
        }

        if ($locationValidationsCount > 0) {
            $blockingDependencies[] = "Has {$locationValidationsCount} location validation records";
        }

        $canDelete = empty($blockingDependencies);

        return [
            'can_delete' => $canDelete,
            'blocking_dependencies' => $blockingDependencies,
            'warnings' => $warnings,
            'assigned_users_count' => $assignedUsers->count(),
            'assignment_histories_count' => $assignmentHistories,
            'attendances_count' => $attendancesCount,
            'location_validations_count' => $locationValidationsCount,
            'assigned_users' => $assignedUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name ?? 'No Role'
                ];
            })->toArray()
        ];
    }

    /**
     * Reassign users to alternative work locations
     */
    protected function reassignUsers(WorkLocation $workLocation, array $options): array
    {
        $assignedUsers = User::where('work_location_id', $workLocation->id)->get();
        
        if ($assignedUsers->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No users to reassign',
                'count' => 0,
                'reassignments' => []
            ];
        }

        // Find alternative work location (same unit_kerja if possible)
        $alternativeLocation = WorkLocation::where('id', '!=', $workLocation->id)
            ->where('is_active', true)
            ->when($workLocation->unit_kerja, function ($query) use ($workLocation) {
                $query->where('unit_kerja', $workLocation->unit_kerja);
            })
            ->first();

        if (!$alternativeLocation) {
            // If no same unit_kerja location, find any active location
            $alternativeLocation = WorkLocation::where('id', '!=', $workLocation->id)
                ->where('is_active', true)
                ->first();
        }

        $reassignments = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($assignedUsers as $user) {
            try {
                $previousLocationId = $user->work_location_id;
                
                // Remove assignment (set to null or reassign)
                $user->work_location_id = $alternativeLocation?->id;
                $user->save();

                // Create assignment history
                AssignmentHistory::create([
                    'user_id' => $user->id,
                    'work_location_id' => $alternativeLocation?->id,
                    'previous_work_location_id' => $previousLocationId,
                    'assigned_by' => $options['assigned_by'],
                    'assignment_method' => 'automatic_reassignment',
                    'assignment_reasons' => [
                        'Previous work location deleted',
                        $alternativeLocation ? "Automatically reassigned to {$alternativeLocation->name}" : 'Assignment removed - no alternative location available'
                    ],
                    'metadata' => [
                        'deleted_location_name' => $workLocation->name,
                        'deletion_reason' => $options['reason'],
                        'automatic_reassignment' => true,
                        'timestamp' => now()->toISOString()
                    ],
                    'notes' => "Automatically reassigned due to work location deletion"
                ]);

                $reassignments[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'from_location' => $workLocation->name,
                    'to_location' => $alternativeLocation?->name ?? 'Unassigned',
                    'status' => 'success'
                ];

                $successCount++;

            } catch (Exception $e) {
                $reassignments[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                
                $failedCount++;
                
                Log::error('User reassignment failed', [
                    'user_id' => $user->id,
                    'work_location_id' => $workLocation->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => $failedCount === 0,
            'message' => $failedCount === 0 
                ? "All {$successCount} users reassigned successfully" 
                : "{$successCount} users reassigned successfully, {$failedCount} failed",
            'count' => $successCount,
            'failed_count' => $failedCount,
            'alternative_location' => $alternativeLocation ? [
                'id' => $alternativeLocation->id,
                'name' => $alternativeLocation->name
            ] : null,
            'reassignments' => $reassignments
        ];
    }

    /**
     * Archive related data before deletion
     */
    protected function archiveRelatedData(WorkLocation $workLocation, array $options): array
    {
        if (!$options['preserve_history']) {
            return ['archived_records' => 0, 'message' => 'History preservation disabled'];
        }

        $archivedCount = 0;

        try {
            // Archive approach: mark records with deletion metadata rather than moving to archive table
            // Update assignment histories with deletion context
            $updated = AssignmentHistory::where('work_location_id', $workLocation->id)
                ->orWhere('previous_work_location_id', $workLocation->id)
                ->update([
                    'metadata' => DB::raw("JSON_SET(
                        COALESCE(metadata, '{}'), 
                        '$.location_deleted_at', '" . now()->toISOString() . "',
                        '$.location_deleted_by', '" . (auth()->user()->name ?? 'System') . "',
                        '$.deletion_reason', '" . addslashes($options['reason']) . "'
                    )")
                ]);

            $archivedCount += $updated;

            return [
                'archived_records' => $archivedCount,
                'message' => "Archived {$archivedCount} related records"
            ];

        } catch (Exception $e) {
            Log::warning('Data archiving partially failed', [
                'work_location_id' => $workLocation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'archived_records' => $archivedCount,
                'message' => "Partial archive completed: {$archivedCount} records",
                'warning' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform the actual deletion
     */
    protected function performDeletion(WorkLocation $workLocation, array $options): array
    {
        if ($options['force_delete']) {
            // Hard delete
            $deleted = $workLocation->forceDelete();
            $deletionType = 'hard_delete';
        } else {
            // Soft delete
            $deleted = $workLocation->delete();
            $deletionType = 'soft_delete';
        }

        return [
            'success' => $deleted,
            'deletion_type' => $deletionType,
            'deleted_at' => now()->toISOString()
        ];
    }

    /**
     * Log deletion activity
     */
    protected function logDeletion(WorkLocation $workLocation, array $options, array $context): void
    {
        Log::info('Work location deleted successfully', [
            'work_location_id' => $workLocation->id,
            'work_location_name' => $workLocation->name,
            'location_type' => $workLocation->location_type,
            'unit_kerja' => $workLocation->unit_kerja,
            'deletion_type' => $options['force_delete'] ? 'hard_delete' : 'soft_delete',
            'reason' => $options['reason'],
            'deleted_by' => auth()->user()->name ?? 'System',
            'deleted_at' => now()->toISOString(),
            'context' => $context
        ]);

        // Create audit trail if audit log exists
        if (class_exists(\App\Models\AuditLog::class)) {
            try {
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'delete',
                    'model_type' => WorkLocation::class,
                    'model_id' => $workLocation->id,
                    'old_values' => $workLocation->toArray(),
                    'new_values' => null,
                    'metadata' => [
                        'deletion_type' => $options['force_delete'] ? 'hard_delete' : 'soft_delete',
                        'reason' => $options['reason'],
                        'users_affected' => $context['users_reassigned']['count'] ?? 0,
                    ]
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to create audit log for work location deletion', [
                    'error' => $e->getMessage(),
                    'work_location_id' => $workLocation->id
                ]);
            }
        }
    }

    /**
     * Clear related caches
     */
    protected function clearRelatedCaches(WorkLocation $workLocation): void
    {
        $cacheKeys = [
            "work_location_{$workLocation->id}",
            "work_locations_active",
            "work_locations_by_unit_kerja",
            "location_capacity_utilization",
            "assignment_analytics",
        ];

        foreach ($cacheKeys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }

        // Clear user-specific caches for affected users
        $affectedUsers = User::where('work_location_id', $workLocation->id)->get();
        foreach ($affectedUsers as $user) {
            $userCacheKeys = [
                "user_work_location_{$user->id}",
                "paramedis_dashboard_stats_{$user->id}",
                "dokter_dashboard_stats_{$user->id}",
                "attendance_status_{$user->id}",
            ];
            
            foreach ($userCacheKeys as $key) {
                \Illuminate\Support\Facades\Cache::forget($key);
            }
        }
    }

    /**
     * Get deletion preview without actually deleting
     */
    public function getDeletePreview(WorkLocation $workLocation): array
    {
        $dependencies = $this->checkDependencies($workLocation);
        
        return [
            'work_location' => [
                'id' => $workLocation->id,
                'name' => $workLocation->name,
                'location_type' => $workLocation->location_type,
                'unit_kerja' => $workLocation->unit_kerja,
                'is_active' => $workLocation->is_active,
            ],
            'dependencies' => $dependencies,
            'recommendations' => $this->getDeletionRecommendations($dependencies),
            'alternative_locations' => $this->getAlternativeLocations($workLocation),
            'estimated_impact' => $this->estimateImpact($dependencies)
        ];
    }

    /**
     * Get deletion recommendations based on dependencies
     */
    protected function getDeletionRecommendations(array $dependencies): array
    {
        $recommendations = [];

        if ($dependencies['assigned_users_count'] > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => "Reassign {$dependencies['assigned_users_count']} users before deletion",
                'action' => 'reassign_users'
            ];
        }

        if ($dependencies['attendances_count'] > 0) {
            $recommendations[] = [
                'type' => 'error',
                'message' => "Cannot delete - has {$dependencies['attendances_count']} attendance records",
                'action' => 'preserve_data'
            ];
        }

        if (!$dependencies['can_delete']) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'Deletion not possible due to blocking dependencies',
                'action' => 'resolve_dependencies_first'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Safe to delete - no blocking dependencies found',
                'action' => 'proceed_with_deletion'
            ];
        }

        return $recommendations;
    }

    /**
     * Get alternative locations for user reassignment
     */
    protected function getAlternativeLocations(WorkLocation $workLocation): array
    {
        return WorkLocation::where('id', '!=', $workLocation->id)
            ->where('is_active', true)
            ->select('id', 'name', 'location_type', 'unit_kerja', 'address')
            ->withCount('users')
            ->get()
            ->map(function ($location) use ($workLocation) {
                $capacity = $location->getCapacityUtilization();
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_type' => $location->location_type,
                    'unit_kerja' => $location->unit_kerja,
                    'address' => $location->address,
                    'current_users' => $location->users_count,
                    'capacity_status' => $capacity['status'],
                    'utilization_percentage' => $capacity['utilization_percentage'],
                    'same_unit_kerja' => $location->unit_kerja === $workLocation->unit_kerja,
                    'recommendation_score' => $this->calculateRecommendationScore($location, $workLocation)
                ];
            })
            ->sortByDesc('recommendation_score')
            ->take(5)
            ->values()
            ->toArray();
    }

    /**
     * Calculate recommendation score for alternative locations
     */
    protected function calculateRecommendationScore($alternativeLocation, $originalLocation): int
    {
        $score = 0;

        // Same unit kerja gets highest priority
        if ($alternativeLocation->unit_kerja === $originalLocation->unit_kerja) {
            $score += 50;
        }

        // Lower utilization is better
        $capacity = $alternativeLocation->getCapacityUtilization();
        if ($capacity['utilization_percentage'] < 50) {
            $score += 30;
        } elseif ($capacity['utilization_percentage'] < 75) {
            $score += 20;
        } elseif ($capacity['utilization_percentage'] < 90) {
            $score += 10;
        }

        // Same location type gets bonus
        if ($alternativeLocation->location_type === $originalLocation->location_type) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Estimate impact of deletion
     */
    protected function estimateImpact(array $dependencies): array
    {
        $impact = [
            'severity' => 'low',
            'users_affected' => $dependencies['assigned_users_count'],
            'data_preserved' => $dependencies['assignment_histories_count'],
            'blocking_issues' => count($dependencies['blocking_dependencies']),
        ];

        if ($dependencies['assigned_users_count'] > 10) {
            $impact['severity'] = 'high';
        } elseif ($dependencies['assigned_users_count'] > 5) {
            $impact['severity'] = 'medium';
        }

        if (!$dependencies['can_delete']) {
            $impact['severity'] = 'critical';
        }

        return $impact;
    }
}