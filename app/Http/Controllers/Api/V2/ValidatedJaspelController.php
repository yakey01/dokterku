<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\ValidatedJaspelCalculationService;
use App\Services\EnhancedJaspelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Validated JASPEL Controller
 * 
 * ONLY provides JASPEL data that has been validated and approved by bendahara.
 * This ensures gaming UI shows only financially accurate amounts.
 */
class ValidatedJaspelController extends Controller
{
    private ValidatedJaspelCalculationService $validatedJaspelService;
    private EnhancedJaspelService $enhancedJaspelService;

    public function __construct(
        ValidatedJaspelCalculationService $validatedJaspelService,
        EnhancedJaspelService $enhancedJaspelService
    ) {
        $this->validatedJaspelService = $validatedJaspelService;
        $this->enhancedJaspelService = $enhancedJaspelService;
    }

    /**
     * Get validated JASPEL data for gaming UI
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGamingData(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            Log::info('🎮 Fetching VALIDATED gaming data ONLY', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'month' => $month,
                'year' => $year,
                'endpoint' => '/api/v2/jaspel/validated/gaming-data'
            ]);

            // Get ONLY validated JASPEL data
            $validatedData = $this->validatedJaspelService->getValidatedJaspelData($user, $month, $year);
            
            // Get validation status for transparency
            $validationStatus = $this->validatedJaspelService->getValidationStatus($user, $month, $year);
            
            // Get pending validation summary
            $pendingSummary = $this->validatedJaspelService->getPendingValidationSummary($user, $month, $year);

            // Transform data for gaming UI format
            $jaspelItems = $validatedData['jaspel_items'];
            
            // Separate jaga and tindakan for gaming UI tabs
            $jagaQuests = array_filter($jaspelItems, function($item) {
                return in_array($item['jenis_jaspel'], ['jaga_umum', 'jaga_pagi', 'jaga_siang', 'jaga_malam']);
            });

            $achievementTindakan = array_filter($jaspelItems, function($item) {
                return in_array($item['jenis_jaspel'], ['paramedis', 'dokter_umum', 'dokter_spesialis']);
            });

            // Calculate gaming statistics from validated data only
            $gamingStats = [
                'total_gold' => $validatedData['summary']['total'],
                'completed_quests' => count($jagaQuests),
                'achievements_unlocked' => count($achievementTindakan),
                'validation_rate' => $validationStatus['validation_rate'],
                'financial_accuracy' => $validationStatus['financial_accuracy']
            ];

            // Validation guarantee for frontend
            $validationGuarantee = [
                'all_amounts_validated' => $validationStatus['validation_rate'] == 100,
                'financial_accuracy' => 'guaranteed',
                'bendahara_approved' => true,
                'gaming_ui_safe' => true,
                'total_validated_amount' => $validatedData['summary']['total'],
                'validation_timestamp' => now()->toISOString()
            ];

            Log::info('✅ Validated gaming data prepared', [
                'user_id' => $user->id,
                'total_items' => count($jaspelItems),
                'jaga_quests' => count($jagaQuests),
                'achievements' => count($achievementTindakan),
                'total_amount' => $validatedData['summary']['total'],
                'validation_guaranteed' => $validationGuarantee['all_amounts_validated']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Validated JASPEL gaming data retrieved successfully',
                'data' => [
                    'gaming_stats' => $gamingStats,
                    'jaga_quests' => array_values($jagaQuests),
                    'achievement_tindakan' => array_values($achievementTindakan),
                    'summary' => $validatedData['summary'],
                    'validation_guarantee' => $validationGuarantee,
                    'validation_status' => $validationStatus,
                    'pending_summary' => $pendingSummary,
                    'counts' => $validatedData['counts']
                ],
                'meta' => [
                    'period' => "{$year}-{$month}",
                    'user_id' => $user->id,
                    'data_source' => 'validated_only',
                    'financial_accuracy' => 'guaranteed',
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to get validated gaming data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve validated JASPEL data',
                'error' => 'VALIDATION_SERVICE_ERROR',
                'details' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get validated JASPEL data (general endpoint)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $validatedData = $this->validatedJaspelService->getValidatedJaspelData($user, $month, $year);

            return response()->json([
                'success' => true,
                'message' => 'Validated JASPEL data retrieved successfully',
                'data' => $validatedData,
                'meta' => [
                    'period' => "{$year}-{$month}",
                    'user_id' => $user->id,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get validated data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve validated JASPEL data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get validation report for transparency
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getValidationReport(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $validationStatus = $this->validatedJaspelService->getValidationStatus($user, $month, $year);
            $pendingSummary = $this->validatedJaspelService->getPendingValidationSummary($user, $month, $year);

            return response()->json([
                'success' => true,
                'message' => 'Validation report retrieved successfully',
                'data' => [
                    'validation_status' => $validationStatus,
                    'pending_summary' => $pendingSummary,
                    'recommendations' => [
                        'gaming_ui_safe' => $validationStatus['gaming_ui_safe'],
                        'action_required' => $pendingSummary['pending_count'] > 0 ? 'Contact bendahara for validation' : 'All validated',
                        'financial_accuracy' => $validationStatus['financial_accuracy']
                    ]
                ],
                'meta' => [
                    'period' => "{$year}-{$month}",
                    'user_id' => $user->id,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get validation report', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve validation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}