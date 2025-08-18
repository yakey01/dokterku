<?php

namespace App\Http\Controllers\Api\V2\Jaspel;

use App\Http\Controllers\Controller;
use App\Services\ValidatedJaspelCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Validated JASPEL Controller
 * 
 * ONLY returns bendahara-validated JASPEL amounts for gaming UI and dashboards.
 * Ensures financial accuracy and prevents display of unvalidated amounts.
 */
class ValidatedJaspelController extends Controller
{
    private $validatedJaspelService;

    public function __construct(ValidatedJaspelCalculationService $validatedJaspelService)
    {
        $this->validatedJaspelService = $validatedJaspelService;
    }

    /**
     * Get validated JASPEL data for gaming UI
     * Only returns amounts approved by bendahara
     */
    public function getValidatedJaspelData(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'error_code' => 'AUTH_REQUIRED'
                ], 401);
            }

            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);

            Log::info('Fetching validated JASPEL data for gaming UI', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'month' => $month,
                'year' => $year,
                'endpoint' => 'validated-jaspel-data'
            ]);

            // Get ONLY validated JASPEL data
            $validatedData = $this->validatedJaspelService->getValidatedJaspelData($user, $month, $year);

            // Get validation status for transparency
            $validationStatus = $this->validatedJaspelService->getValidationStatus($user, $month, $year);

            // Get pending summary for user awareness
            $pendingSummary = $this->validatedJaspelService->getPendingValidationSummary($user, $month, $year);

            return response()->json([
                'success' => true,
                'message' => 'Validated JASPEL data retrieved successfully',
                'data' => [
                    'jaspel_items' => $validatedData['jaspel_items'],
                    'summary' => $validatedData['summary'],
                    'validation_info' => [
                        'status' => $validationStatus,
                        'pending_summary' => $pendingSummary,
                        'financial_guarantee' => 'bendahara_validated_only',
                        'gaming_ui_safe' => true,
                        'data_source' => 'validated_only'
                    ],
                    'counts' => $validatedData['counts'],
                    'metadata' => [
                        'validation_timestamp' => $validatedData['validation_timestamp'],
                        'validation_source' => $validatedData['validation_source'],
                        'period' => "{$year}-{$month}",
                        'user_id' => $user->id
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching validated JASPEL data', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch validated JASPEL data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get gaming UI data with validation guarantee
     * Specifically designed for gaming interface
     */
    public function getGamingData(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);

            // Get validated data
            $validatedData = $this->validatedJaspelService->getValidatedJaspelData($user, $month, $year);
            $validationStatus = $this->validatedJaspelService->getValidationStatus($user, $month, $year);

            // Separate jaga and tindakan for gaming UI
            $jagaData = collect($validatedData['jaspel_items'])->filter(function($item) {
                $jenis = strtolower($item['jenis_jaspel'] ?? '');
                return str_contains($jenis, 'jaga') || str_contains($jenis, 'shift');
            })->values()->toArray();

            $tindakanData = collect($validatedData['jaspel_items'])->filter(function($item) {
                $jenis = strtolower($item['jenis_jaspel'] ?? '');
                return !str_contains($jenis, 'jaga') && !str_contains($jenis, 'shift');
            })->values()->toArray();

            // Gaming UI specific calculations
            $totalGoldEarned = $validatedData['summary']['total'];
            $completedQuests = count($jagaData);
            $achievementsUnlocked = count($tindakanData);

            return response()->json([
                'success' => true,
                'message' => 'Gaming data retrieved with validation guarantee',
                'data' => [
                    'gaming_stats' => [
                        'total_gold_earned' => $totalGoldEarned,
                        'completed_quests' => $completedQuests,
                        'achievements_unlocked' => $achievementsUnlocked,
                        'validation_rate' => $validationStatus['validation_rate'],
                        'financial_accuracy' => '100%' // Always 100% since only validated data
                    ],
                    'jaga_quests' => $jagaData,
                    'achievement_tindakan' => $tindakanData,
                    'summary' => $validatedData['summary'],
                    'validation_guarantee' => [
                        'all_amounts_validated' => true,
                        'bendahara_approved' => true,
                        'financial_accuracy' => 'guaranteed',
                        'safe_for_gaming_ui' => true
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching gaming data', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch gaming data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get validation transparency report
     * Shows validation status and pending items
     */
    public function getValidationReport(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);

            $validationStatus = $this->validatedJaspelService->getValidationStatus($user, $month, $year);
            $pendingSummary = $this->validatedJaspelService->getPendingValidationSummary($user, $month, $year);

            return response()->json([
                'success' => true,
                'message' => 'Validation report generated',
                'data' => [
                    'validation_status' => $validationStatus,
                    'pending_summary' => $pendingSummary,
                    'recommendations' => $this->getValidationRecommendations($validationStatus, $pendingSummary)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating validation report', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate validation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate validation recommendations
     */
    private function getValidationRecommendations(array $validationStatus, array $pendingSummary): array
    {
        $recommendations = [];

        if ($validationStatus['validation_rate'] < 100) {
            $recommendations[] = [
                'type' => 'pending_validation',
                'message' => "Ada {$pendingSummary['pending_count']} item JASPEL yang belum divalidasi bendahara",
                'action' => 'Hubungi bendahara untuk validasi',
                'priority' => 'medium'
            ];
        }

        if ($validationStatus['validation_rate'] < 80) {
            $recommendations[] = [
                'type' => 'low_validation_rate',
                'message' => 'Tingkat validasi rendah, periksa kelengkapan dokumen',
                'action' => 'Review dan lengkapi data yang diperlukan',
                'priority' => 'high'
            ];
        }

        if ($validationStatus['validation_rate'] == 100) {
            $recommendations[] = [
                'type' => 'excellent_validation',
                'message' => 'Semua JASPEL sudah tervalidasi - data akurat 100%',
                'action' => 'Lanjutkan praktik dokumentasi yang baik',
                'priority' => 'info'
            ];
        }

        return $recommendations;
    }
}