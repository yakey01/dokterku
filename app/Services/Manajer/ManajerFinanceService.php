<?php

namespace App\Services\Manajer;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\BudgetPlan;
use App\Models\JumlahPasienHarian;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManajerFinanceService
{
    private const CACHE_TTL = 600; // 10 minutes
    private const LONG_CACHE_TTL = 1800; // 30 minutes
    
    /**
     * Get advanced financial analytics
     */
    public function getAdvancedAnalytics(int $month = null, int $year = null): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            
            $cacheKey = "manajer_finance_advanced_{$year}_{$month}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($month, $year) {
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                
                // Current month validated data
                $monthlyRevenue = Pendapatan::whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                    
                $monthlyExpenses = Pengeluaran::whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                
                $netProfit = $monthlyRevenue - $monthlyExpenses;
                $profitMargin = $monthlyRevenue > 0 ? ($netProfit / $monthlyRevenue) * 100 : 0;
                
                // Quarter comparison
                $quarterData = $this->getQuarterComparison($month, $year);
                
                // Year-to-date analysis
                $ytdData = $this->getYearToDateAnalysis($year);
                
                // Financial ratios
                $ratios = $this->calculateFinancialRatios($month, $year);
                
                // Cash flow analysis
                $cashFlow = $this->analyzeCashFlow($month, $year);
                
                // Revenue per patient analysis
                $revenuePerPatient = $this->calculateRevenuePerPatient($month, $year);
                
                // Category performance analysis
                $categoryPerformance = $this->analyzeCategoryPerformance($month, $year);
                
                return [
                    'success' => true,
                    'message' => 'Advanced financial analytics retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => $startDate->format('F Y'),
                            'days_in_month' => $startDate->daysInMonth
                        ],
                        'current_month' => [
                            'revenue' => (float) $monthlyRevenue,
                            'expenses' => (float) $monthlyExpenses,
                            'net_profit' => (float) $netProfit,
                            'profit_margin' => round($profitMargin, 2),
                            'formatted' => [
                                'revenue' => 'Rp ' . number_format($monthlyRevenue, 0, ',', '.'),
                                'expenses' => 'Rp ' . number_format($monthlyExpenses, 0, ',', '.'),
                                'net_profit' => 'Rp ' . number_format($netProfit, 0, ',', '.'),
                                'profit_margin' => round($profitMargin, 2) . '%'
                            ]
                        ],
                        'quarter_comparison' => $quarterData,
                        'year_to_date' => $ytdData,
                        'financial_ratios' => $ratios,
                        'cash_flow_analysis' => $cashFlow,
                        'revenue_per_patient' => $revenuePerPatient,
                        'category_performance' => $categoryPerformance
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting advanced financial analytics', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve advanced financial analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get budget vs actual analysis
     */
    public function getBudgetAnalysis(int $month = null, int $year = null): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            
            $cacheKey = "manajer_finance_budget_{$year}_{$month}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($month, $year) {
                // Get budget plan for the period (if exists)
                $budgetPlan = BudgetPlan::where('year', $year)
                    ->where('month', $month)
                    ->first();
                
                // Actual figures
                $actualRevenue = Pendapatan::whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                    
                $actualExpenses = Pengeluaran::whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                
                // Budget vs actual comparison
                $budgetRevenue = $budgetPlan ? $budgetPlan->planned_revenue : 0;
                $budgetExpenses = $budgetPlan ? $budgetPlan->planned_expenses : 0;
                
                $revenueVariance = $actualRevenue - $budgetRevenue;
                $expenseVariance = $actualExpenses - $budgetExpenses;
                
                $revenueVariancePercent = $budgetRevenue > 0 ? 
                    ($revenueVariance / $budgetRevenue) * 100 : 0;
                $expenseVariancePercent = $budgetExpenses > 0 ? 
                    ($expenseVariance / $budgetExpenses) * 100 : 0;
                
                // Category-wise budget analysis
                $categoryAnalysis = $this->getBudgetCategoryAnalysis($month, $year, $budgetPlan);
                
                // Forecast for remaining months
                $forecast = $this->generateFinancialForecast($year);
                
                return [
                    'success' => true,
                    'message' => 'Budget analysis retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'has_budget_plan' => $budgetPlan !== null,
                        'budget_vs_actual' => [
                            'revenue' => [
                                'budget' => (float) $budgetRevenue,
                                'actual' => (float) $actualRevenue,
                                'variance' => (float) $revenueVariance,
                                'variance_percent' => round($revenueVariancePercent, 2),
                                'status' => $revenueVariance >= 0 ? 'above_budget' : 'below_budget',
                                'formatted' => [
                                    'budget' => 'Rp ' . number_format($budgetRevenue, 0, ',', '.'),
                                    'actual' => 'Rp ' . number_format($actualRevenue, 0, ',', '.'),
                                    'variance' => 'Rp ' . number_format($revenueVariance, 0, ',', '.')
                                ]
                            ],
                            'expenses' => [
                                'budget' => (float) $budgetExpenses,
                                'actual' => (float) $actualExpenses,
                                'variance' => (float) $expenseVariance,
                                'variance_percent' => round($expenseVariancePercent, 2),
                                'status' => $expenseVariance <= 0 ? 'under_budget' : 'over_budget',
                                'formatted' => [
                                    'budget' => 'Rp ' . number_format($budgetExpenses, 0, ',', '.'),
                                    'actual' => 'Rp ' . number_format($actualExpenses, 0, ',', '.'),
                                    'variance' => 'Rp ' . number_format($expenseVariance, 0, ',', '.')
                                ]
                            ],
                            'net_profit' => [
                                'budget' => (float) ($budgetRevenue - $budgetExpenses),
                                'actual' => (float) ($actualRevenue - $actualExpenses),
                                'variance' => (float) (($actualRevenue - $actualExpenses) - ($budgetRevenue - $budgetExpenses))
                            ]
                        ],
                        'category_analysis' => $categoryAnalysis,
                        'forecast' => $forecast,
                        'performance_indicators' => [
                            'revenue_achievement' => $budgetRevenue > 0 ? 
                                round(($actualRevenue / $budgetRevenue) * 100, 1) : 0,
                            'expense_control' => $budgetExpenses > 0 ? 
                                round(($actualExpenses / $budgetExpenses) * 100, 1) : 0,
                            'overall_performance' => $this->calculateOverallBudgetPerformance(
                                $actualRevenue, $budgetRevenue, $actualExpenses, $budgetExpenses
                            )
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting budget analysis', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve budget analysis',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get cost breakdown and category analysis
     */
    public function getCostBreakdown(int $month = null, int $year = null): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            
            $cacheKey = "manajer_finance_cost_breakdown_{$year}_{$month}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($month, $year) {
                // Detailed expense breakdown by category
                $expensesByCategory = Pengeluaran::select('kategori', DB::raw('SUM(nominal) as total'), DB::raw('COUNT(*) as count'))
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->groupBy('kategori')
                    ->orderBy('total', 'desc')
                    ->get();
                
                $totalExpenses = $expensesByCategory->sum('total');
                
                $categoryBreakdown = $expensesByCategory->map(function ($item) use ($totalExpenses) {
                    $category = $item->kategori ?: 'Lainnya';
                    $total = (float) $item->total;
                    $count = (int) $item->count;
                    
                    return [
                        'category' => $category,
                        'total' => $total,
                        'count' => $count,
                        'percentage' => $totalExpenses > 0 ? round(($total / $totalExpenses) * 100, 1) : 0,
                        'avg_per_transaction' => $count > 0 ? round($total / $count, 0) : 0,
                        'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.'),
                        'cost_classification' => $this->classifyExpenseCategory($category, $total, $totalExpenses)
                    ];
                });
                
                // Revenue breakdown by category
                $revenueByCategory = Pendapatan::select('kategori', DB::raw('SUM(nominal) as total'), DB::raw('COUNT(*) as count'))
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->groupBy('kategori')
                    ->orderBy('total', 'desc')
                    ->get();
                
                $totalRevenue = $revenueByCategory->sum('total');
                
                $revenueBreakdown = $revenueByCategory->map(function ($item) use ($totalRevenue) {
                    $category = $item->kategori ?: 'Lainnya';
                    $total = (float) $item->total;
                    $count = (int) $item->count;
                    
                    return [
                        'category' => $category,
                        'total' => $total,
                        'count' => $count,
                        'percentage' => $totalRevenue > 0 ? round(($total / $totalRevenue) * 100, 1) : 0,
                        'avg_per_transaction' => $count > 0 ? round($total / $count, 0) : 0,
                        'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.')
                    ];
                });
                
                // Cost efficiency metrics
                $costEfficiency = $this->analyzeCostEfficiency($month, $year);
                
                // Category trends (compare with previous month)
                $categoryTrends = $this->analyzeCategoryTrends($month, $year);
                
                return [
                    'success' => true,
                    'message' => 'Cost breakdown analysis retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'expense_breakdown' => [
                            'total_expenses' => (float) $totalExpenses,
                            'formatted_total' => 'Rp ' . number_format($totalExpenses, 0, ',', '.'),
                            'categories' => $categoryBreakdown,
                            'top_expense_category' => $categoryBreakdown->first(),
                            'category_count' => $categoryBreakdown->count()
                        ],
                        'revenue_breakdown' => [
                            'total_revenue' => (float) $totalRevenue,
                            'formatted_total' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                            'categories' => $revenueBreakdown,
                            'top_revenue_category' => $revenueBreakdown->first(),
                            'category_count' => $revenueBreakdown->count()
                        ],
                        'cost_efficiency' => $costEfficiency,
                        'category_trends' => $categoryTrends,
                        'financial_health' => [
                            'net_profit' => (float) ($totalRevenue - $totalExpenses),
                            'profit_margin' => $totalRevenue > 0 ? round((($totalRevenue - $totalExpenses) / $totalRevenue) * 100, 2) : 0,
                            'cost_ratio' => $totalRevenue > 0 ? round(($totalExpenses / $totalRevenue) * 100, 2) : 0,
                            'break_even_status' => $totalRevenue >= $totalExpenses ? 'profitable' : 'loss'
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting cost breakdown', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve cost breakdown',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get financial forecasting and trends
     */
    public function getFinancialForecast(int $year = null, int $months = 6): array
    {
        try {
            $year = $year ?? now()->year;
            $months = min($months, 12); // Max 12 months
            
            $cacheKey = "manajer_finance_forecast_{$year}_{$months}";
            
            return Cache::remember($cacheKey, self::LONG_CACHE_TTL, function () use ($year, $months) {
                // Historical data for trend analysis
                $historicalData = $this->getHistoricalFinancialData($year, $months);
                
                // Generate forecasts based on trends
                $forecasts = $this->generateForecast($historicalData, $months);
                
                // Scenario analysis
                $scenarios = $this->generateScenarios($historicalData);
                
                // Key performance indicators trends
                $kpiTrends = $this->analyzeKPITrends($historicalData);
                
                return [
                    'success' => true,
                    'message' => 'Financial forecast retrieved successfully',
                    'data' => [
                        'year' => $year,
                        'forecast_period' => $months,
                        'historical_data' => $historicalData,
                        'forecasts' => $forecasts,
                        'scenarios' => $scenarios,
                        'kpi_trends' => $kpiTrends,
                        'recommendations' => $this->generateFinancialRecommendations($historicalData, $forecasts)
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting financial forecast', [
                'error' => $e->getMessage(),
                'year' => $year,
                'months' => $months
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve financial forecast',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    // Helper Methods
    
    private function getQuarterComparison(int $month, int $year): array
    {
        $quarter = ceil($month / 3);
        $quarterStart = ($quarter - 1) * 3 + 1;
        $quarterEnd = $quarter * 3;
        
        $currentQuarter = Pendapatan::whereYear('tanggal', $year)
            ->whereMonth('tanggal', '>=', $quarterStart)
            ->whereMonth('tanggal', '<=', $quarterEnd)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $previousQuarter = $quarter > 1 ? 
            Pendapatan::whereYear('tanggal', $year)
                ->whereMonth('tanggal', '>=', $quarterStart - 3)
                ->whereMonth('tanggal', '<=', $quarterEnd - 3)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal') : 0;
        
        $growth = $previousQuarter > 0 ? (($currentQuarter - $previousQuarter) / $previousQuarter) * 100 : 0;
        
        return [
            'quarter' => $quarter,
            'current_quarter' => (float) $currentQuarter,
            'previous_quarter' => (float) $previousQuarter,
            'growth_percent' => round($growth, 2)
        ];
    }
    
    private function getYearToDateAnalysis(int $year): array
    {
        $ytdRevenue = Pendapatan::whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $ytdExpenses = Pengeluaran::whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
        
        $previousYtd = Pendapatan::whereYear('tanggal', $year - 1)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $growth = $previousYtd > 0 ? (($ytdRevenue - $previousYtd) / $previousYtd) * 100 : 0;
        
        return [
            'revenue' => (float) $ytdRevenue,
            'expenses' => (float) $ytdExpenses,
            'net_profit' => (float) ($ytdRevenue - $ytdExpenses),
            'previous_year_revenue' => (float) $previousYtd,
            'growth_percent' => round($growth, 2)
        ];
    }
    
    private function calculateFinancialRatios(int $month, int $year): array
    {
        $revenue = Pendapatan::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $expenses = Pengeluaran::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
        
        return [
            'profit_margin' => $revenue > 0 ? round((($revenue - $expenses) / $revenue) * 100, 2) : 0,
            'expense_ratio' => $revenue > 0 ? round(($expenses / $revenue) * 100, 2) : 0,
            'return_on_revenue' => $revenue > 0 ? round((($revenue - $expenses) / $revenue), 4) : 0
        ];
    }
    
    private function analyzeCashFlow(int $month, int $year): array
    {
        // Simplified cash flow analysis
        $revenue = Pendapatan::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
            
        $expenses = Pengeluaran::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
        
        $netCashFlow = $revenue - $expenses;
        
        return [
            'cash_inflow' => (float) $revenue,
            'cash_outflow' => (float) $expenses,
            'net_cash_flow' => (float) $netCashFlow,
            'cash_flow_status' => $netCashFlow >= 0 ? 'positive' : 'negative'
        ];
    }
    
    private function calculateRevenuePerPatient(int $month, int $year): array
    {
        $revenue = Pendapatan::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
        
        $totalPatients = JumlahPasienHarian::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'approved')
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
        
        $revenuePerPatient = $totalPatients > 0 ? $revenue / $totalPatients : 0;
        
        return [
            'total_revenue' => (float) $revenue,
            'total_patients' => (int) $totalPatients,
            'revenue_per_patient' => round($revenuePerPatient, 0),
            'formatted_revenue_per_patient' => 'Rp ' . number_format($revenuePerPatient, 0, ',', '.')
        ];
    }
    
    private function analyzeCategoryPerformance(int $month, int $year): array
    {
        // This is a placeholder - implement based on your specific category analysis needs
        return [
            'top_performing_categories' => [],
            'underperforming_categories' => [],
            'category_growth_rates' => []
        ];
    }
    
    private function getBudgetCategoryAnalysis(int $month, int $year, $budgetPlan): array
    {
        // Implement category-wise budget analysis
        return [];
    }
    
    private function generateFinancialForecast(int $year): array
    {
        // Implement forecasting logic
        return [];
    }
    
    private function calculateOverallBudgetPerformance(float $actualRevenue, float $budgetRevenue, float $actualExpenses, float $budgetExpenses): string
    {
        if ($budgetRevenue == 0 && $budgetExpenses == 0) {
            return 'no_budget';
        }
        
        $revenueAchievement = $budgetRevenue > 0 ? ($actualRevenue / $budgetRevenue) : 0;
        $expenseControl = $budgetExpenses > 0 ? ($actualExpenses / $budgetExpenses) : 1;
        
        if ($revenueAchievement >= 1 && $expenseControl <= 1) {
            return 'excellent';
        } elseif ($revenueAchievement >= 0.9 && $expenseControl <= 1.1) {
            return 'good';
        } elseif ($revenueAchievement >= 0.8 && $expenseControl <= 1.2) {
            return 'average';
        } else {
            return 'poor';
        }
    }
    
    private function classifyExpenseCategory(string $category, float $amount, float $totalExpenses): string
    {
        $percentage = $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0;
        
        if ($percentage >= 30) return 'major';
        if ($percentage >= 15) return 'significant';
        if ($percentage >= 5) return 'moderate';
        return 'minor';
    }
    
    private function analyzeCostEfficiency(int $month, int $year): array
    {
        // Implement cost efficiency analysis
        return [
            'cost_per_patient' => 0,
            'operational_efficiency' => 0,
            'cost_trends' => []
        ];
    }
    
    private function analyzeCategoryTrends(int $month, int $year): array
    {
        // Implement category trend analysis
        return [];
    }
    
    private function getHistoricalFinancialData(int $year, int $months): array
    {
        // Implement historical data gathering
        return [];
    }
    
    private function generateForecast(array $historicalData, int $months): array
    {
        // Implement forecast generation
        return [];
    }
    
    private function generateScenarios(array $historicalData): array
    {
        // Implement scenario generation
        return [];
    }
    
    private function analyzeKPITrends(array $historicalData): array
    {
        // Implement KPI trend analysis
        return [];
    }
    
    private function generateFinancialRecommendations(array $historicalData, array $forecasts): array
    {
        // Implement recommendation generation
        return [];
    }
}