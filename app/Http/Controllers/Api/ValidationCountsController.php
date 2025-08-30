<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JumlahPasienHarian;
use App\Constants\ValidationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * API Controller for real-time validation counts
 * Provides live updates for validation status tabs
 */
class ValidationCountsController extends Controller
{
    /**
     * Get current validation status counts
     */
    public function getCounts(): JsonResponse
    {
        // Ensure user is authenticated and has bendahara role
        if (!Auth::check() || !Auth::user()->hasRole('bendahara')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Cache for 1 minute for real-time feel while avoiding database load
        $counts = Cache::remember('real_time_validation_counts', 60, function () {
            $baseQuery = JumlahPasienHarian::query()
                ->with(['inputBy', 'validasiBy', 'dokter']);
            
            return [
                'total' => $baseQuery->count(),
                'pending' => $baseQuery->where('status_validasi', ValidationStatus::PENDING)->count(),
                'approved' => $baseQuery->where('status_validasi', ValidationStatus::APPROVED)->count(),
                'rejected' => $baseQuery->where('status_validasi', ValidationStatus::REJECTED)->count(),
                'revision' => $baseQuery->where('status_validasi', ValidationStatus::REVISION)->count(),
                'cancelled' => $baseQuery->where('status_validasi', ValidationStatus::CANCELLED)->count(),
                'validated' => $baseQuery->whereIn('status_validasi', [
                    ValidationStatus::APPROVED,
                    ValidationStatus::REJECTED,
                    ValidationStatus::REVISION,
                    ValidationStatus::CANCELLED
                ])->count(),
            ];
        });

        // Add metadata for client-side optimization
        $counts['last_updated'] = now()->toISOString();
        $counts['cache_key'] = 'real_time_validation_counts';
        
        // Add summary statistics
        $counts['summary'] = [
            'completion_rate' => $counts['total'] > 0 ? round(($counts['validated'] / $counts['total']) * 100, 1) : 0,
            'pending_urgency' => $this->calculatePendingUrgency($counts['pending']),
            'status_breakdown' => [
                'approved_percentage' => $counts['validated'] > 0 ? round(($counts['approved'] / $counts['validated']) * 100, 1) : 0,
                'rejected_percentage' => $counts['validated'] > 0 ? round(($counts['rejected'] / $counts['validated']) * 100, 1) : 0,
            ]
        ];

        return response()->json($counts);
    }

    /**
     * Get detailed validation statistics
     */
    public function getDetailedStats(): JsonResponse
    {
        if (!Auth::check() || !Auth::user()->hasRole('bendahara')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = Cache::remember('detailed_validation_stats', 300, function () {
            $today = now()->toDateString();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            return [
                'today' => [
                    'submitted' => JumlahPasienHarian::whereDate('created_at', $today)->count(),
                    'validated' => JumlahPasienHarian::whereDate('validasi_at', $today)->count(),
                    'pending' => JumlahPasienHarian::whereDate('created_at', $today)
                        ->where('status_validasi', ValidationStatus::PENDING)->count(),
                ],
                'week' => [
                    'submitted' => JumlahPasienHarian::where('created_at', '>=', $thisWeek)->count(),
                    'validated' => JumlahPasienHarian::where('validasi_at', '>=', $thisWeek)->count(),
                    'avg_validation_time' => $this->calculateAverageValidationTime($thisWeek),
                ],
                'month' => [
                    'submitted' => JumlahPasienHarian::where('created_at', '>=', $thisMonth)->count(),
                    'validated' => JumlahPasienHarian::where('validasi_at', '>=', $thisMonth)->count(),
                    'approval_rate' => $this->calculateApprovalRate($thisMonth),
                ],
                'queue_analysis' => [
                    'oldest_pending' => JumlahPasienHarian::where('status_validasi', ValidationStatus::PENDING)
                        ->orderBy('created_at', 'asc')
                        ->first()?->created_at?->diffForHumans(),
                    'average_patient_count' => JumlahPasienHarian::where('status_validasi', ValidationStatus::PENDING)
                        ->selectRaw('AVG(jumlah_pasien_umum + jumlah_pasien_bpjs) as avg')
                        ->value('avg'),
                    'high_value_count' => JumlahPasienHarian::where('status_validasi', ValidationStatus::PENDING)
                        ->where('jaspel_rupiah', '>', 500000)
                        ->count(),
                ]
            ];
        });

        return response()->json($stats);
    }

    /**
     * Clear validation counts cache (for admin use)
     */
    public function clearCache(): JsonResponse
    {
        if (!Auth::check() || !Auth::user()->hasRole('bendahara')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Cache::forget('real_time_validation_counts');
        Cache::forget('detailed_validation_stats');
        Cache::forget('validation_status_counts_bendahara');

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Calculate pending urgency level
     */
    private function calculatePendingUrgency(int $pendingCount): string
    {
        if ($pendingCount === 0) return 'none';
        if ($pendingCount <= 5) return 'low';
        if ($pendingCount <= 15) return 'medium';
        if ($pendingCount <= 30) return 'high';
        return 'critical';
    }

    /**
     * Calculate average validation time in hours
     */
    private function calculateAverageValidationTime($since): ?float
    {
        $validated = JumlahPasienHarian::where('validasi_at', '>=', $since)
            ->whereNotNull('validasi_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, validasi_at)) as avg_hours')
            ->value('avg_hours');

        return $validated ? round($validated, 1) : null;
    }

    /**
     * Calculate approval rate percentage
     */
    private function calculateApprovalRate($since): float
    {
        $total = JumlahPasienHarian::where('validasi_at', '>=', $since)
            ->whereNotNull('validasi_at')
            ->count();

        if ($total === 0) return 0;

        $approved = JumlahPasienHarian::where('validasi_at', '>=', $since)
            ->where('status_validasi', ValidationStatus::APPROVED)
            ->count();

        return round(($approved / $total) * 100, 1);
    }
}