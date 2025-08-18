<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ValidatedJaspelCalculationService;
use Carbon\Carbon;

class JaspelComparisonController extends Controller
{
    protected $validatedJaspelService;

    public function __construct(ValidatedJaspelCalculationService $validatedJaspelService)
    {
        $this->validatedJaspelService = $validatedJaspelService;
    }

    /**
     * Get Jaspel comparison data between current month and previous month
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMonthlyComparison(Request $request): JsonResponse
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

            // Get current month and year from request or use current date
            $currentMonth = $request->get('month', now()->month);
            $currentYear = $request->get('year', now()->year);
            
            // Calculate previous month
            $currentDate = Carbon::create($currentYear, $currentMonth, 1);
            $previousDate = $currentDate->copy()->subMonth();
            
            $previousMonth = $previousDate->month;
            $previousYear = $previousDate->year;

            Log::info('🔄 Fetching Jaspel comparison data', [
                'user_id' => $user->id,
                'current_month' => $currentMonth,
                'current_year' => $currentYear,
                'previous_month' => $previousMonth,
                'previous_year' => $previousYear,
            ]);

            // Get current month data
            $currentData = $this->validatedJaspelService->getValidatedJaspelData($user, $currentMonth, $currentYear);
            
            // Get previous month data
            $previousData = $this->validatedJaspelService->getValidatedJaspelData($user, $previousMonth, $previousYear);

            // Process comparison data
            $currentSummary = $currentData['summary'];
            $previousSummary = $previousData['summary'];
            
            // Calculate changes
            $amountChange = $currentSummary['total'] - $previousSummary['total'];
            $percentageChange = $previousSummary['total'] > 0 
                ? (($amountChange / $previousSummary['total']) * 100) 
                : ($currentSummary['total'] > 0 ? 100 : 0);

            // Determine trend
            $trend = 'stable';
            $status = 'maintained';
            
            if ($percentageChange > 5) {
                $trend = 'up';
                $status = 'improved';
            } elseif ($percentageChange < -5) {
                $trend = 'down';
                $status = 'declined';
            }

            // Get month names in Indonesian
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            $comparisonData = [
                'current_month' => [
                    'total' => $currentSummary['total'],
                    'approved' => $currentSummary['approved'],
                    'pending' => $currentSummary['pending'],
                    'rejected' => $currentSummary['rejected'],
                    'count' => $currentSummary['count']['total'],
                    'month_name' => $monthNames[$currentMonth] . ' ' . $currentYear,
                    'month' => $currentMonth,
                    'year' => $currentYear,
                ],
                'previous_month' => [
                    'total' => $previousSummary['total'],
                    'approved' => $previousSummary['approved'],
                    'pending' => $previousSummary['pending'],
                    'rejected' => $previousSummary['rejected'],
                    'count' => $previousSummary['count']['total'],
                    'month_name' => $monthNames[$previousMonth] . ' ' . $previousYear,
                    'month' => $previousMonth,
                    'year' => $previousYear,
                ],
                'comparison' => [
                    'percentage_change' => round($percentageChange, 1),
                    'amount_change' => $amountChange,
                    'trend' => $trend,
                    'status' => $status,
                    'has_previous_data' => $previousSummary['total'] > 0 || $previousSummary['count']['total'] > 0,
                ],
                'insights' => [
                    'message' => $this->generateInsightMessage($trend, $percentageChange, $amountChange),
                    'recommendation' => $this->generateRecommendation($trend, $currentSummary, $previousSummary),
                ],
                'meta' => [
                    'calculation_method' => 'validated_data_only',
                    'data_source' => 'ValidatedJaspelService',
                    'comparison_period' => $monthNames[$previousMonth] . ' vs ' . $monthNames[$currentMonth],
                    'generated_at' => now()->toISOString(),
                ]
            ];

            Log::info('✅ Jaspel comparison data generated successfully', [
                'user_id' => $user->id,
                'trend' => $trend,
                'percentage_change' => $percentageChange,
                'amount_change' => $amountChange,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jaspel comparison data retrieved successfully',
                'data' => $comparisonData,
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'version' => '2.0',
                    'endpoint' => '/api/v2/jaspel/comparison/monthly'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch Jaspel comparison data', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jaspel comparison data',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Generate insight message based on trend analysis
     */
    private function generateInsightMessage(string $trend, float $percentageChange, int $amountChange): string
    {
        $absPercentage = abs($percentageChange);
        $formattedAmount = number_format($amountChange, 0, ',', '.');

        switch ($trend) {
            case 'up':
                if ($absPercentage > 50) {
                    return "Luar biasa! Jaspel Anda meningkat drastis sebesar {$absPercentage}% (+Rp {$formattedAmount}). Performa exceptional!";
                } elseif ($absPercentage > 20) {
                    return "Excellent! Jaspel Anda meningkat signifikan sebesar {$absPercentage}% (+Rp {$formattedAmount}). Keep it up!";
                } else {
                    return "Good progress! Jaspel Anda meningkat {$absPercentage}% (+Rp {$formattedAmount}) dari bulan lalu.";
                }
                
            case 'down':
                if ($absPercentage > 50) {
                    return "Perhatian! Jaspel turun drastis {$absPercentage}% (-Rp {$formattedAmount}). Perlu evaluasi dan perbaikan.";
                } elseif ($absPercentage > 20) {
                    return "Jaspel turun {$absPercentage}% (-Rp {$formattedAmount}). Focus pada peningkatan konsistensi.";
                } else {
                    return "Jaspel turun sedikit {$absPercentage}% (-Rp {$formattedAmount}). Variasi normal dalam performa.";
                }
                
            default:
                return "Performa stabil dengan perubahan minimal ({$absPercentage}%). Konsistensi yang baik!";
        }
    }

    /**
     * Generate recommendation based on comparison analysis
     */
    private function generateRecommendation(string $trend, array $currentSummary, array $previousSummary): string
    {
        switch ($trend) {
            case 'up':
                return "Pertahankan momentum positif ini! Analisis aktivitas yang memberikan peningkatan terbesar dan replicate success patterns.";
                
            case 'down':
                $currentApprovalRate = $currentSummary['total'] > 0 ? ($currentSummary['approved'] / $currentSummary['total']) * 100 : 0;
                $previousApprovalRate = $previousSummary['total'] > 0 ? ($previousSummary['approved'] / $previousSummary['total']) * 100 : 0;
                
                if ($currentApprovalRate < $previousApprovalRate) {
                    return "Focus pada peningkatan kualitas dokumentasi untuk approval rate yang lebih baik. Review rejected items untuk learning.";
                } else {
                    return "Tingkatkan volume aktivitas sambil mempertahankan kualitas. Consider diversifikasi jenis tindakan medis.";
                }
                
            default:
                return "Maintain current performance level. Consider gradual improvements dalam volume atau efficiency untuk growth berkelanjutan.";
        }
    }

    /**
     * Get quarterly comparison data (3 months trend)
     */
    public function getQuarterlyTrend(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $currentMonth = $request->get('month', now()->month);
            $currentYear = $request->get('year', now()->year);
            
            $trendsData = [];
            
            // Get data for current month and 2 previous months
            for ($i = 0; $i < 3; $i++) {
                $date = Carbon::create($currentYear, $currentMonth, 1)->subMonths($i);
                $month = $date->month;
                $year = $date->year;
                
                $monthData = $this->validatedJaspelService->getValidatedJaspelData($user, $month, $year);
                
                $trendsData[] = [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => $date->format('M Y'),
                    'total' => $monthData['summary']['total'],
                    'count' => $monthData['summary']['count']['total'],
                    'approved' => $monthData['summary']['approved'],
                ];
            }
            
            // Reverse to get chronological order
            $trendsData = array_reverse($trendsData);

            return response()->json([
                'success' => true,
                'message' => 'Quarterly trend data retrieved successfully',
                'data' => [
                    'trends' => $trendsData,
                    'period' => '3 months',
                    'generated_at' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch quarterly trend data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quarterly trend data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}