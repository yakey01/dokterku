<?php

namespace App\Services;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\Tindakan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use App\Services\CacheService;
use App\Services\LoggingService;
use App\Services\BendaharaStatsService;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class FinancialReportService
{
    protected CacheService $cacheService;
    protected LoggingService $loggingService;
    protected BendaharaStatsService $statsService;
    protected int $cacheMinutes = 60; // Cache for 1 hour
    protected int $longCacheMinutes = 1440; // Cache for 24 hours
    
    public function __construct(
        CacheService $cacheService, 
        LoggingService $loggingService,
        BendaharaStatsService $statsService
    ) {
        $this->cacheService = $cacheService;
        $this->loggingService = $loggingService;
        $this->statsService = $statsService;
    }
    
    /**
     * Generate comprehensive financial report
     */
    public function generateFinancialReport(array $params = []): array
    {
        try {
            $startTime = microtime(true);
            
            // Extract parameters
            $dateFrom = Carbon::parse($params['date_from'] ?? Carbon::now()->startOfMonth());
            $dateTo = Carbon::parse($params['date_to'] ?? Carbon::now()->endOfMonth());
            $reportType = $params['type'] ?? 'comprehensive';
            $format = $params['format'] ?? 'array';
            $includeCharts = $params['include_charts'] ?? true;
            
            $cacheKey = "financial_report_{$dateFrom->format('Y-m-d')}_{$dateTo->format('Y-m-d')}_{$reportType}";
            
            $report = $this->cacheService->cacheReport($cacheKey, function () use ($dateFrom, $dateTo, $reportType, $includeCharts) {
                return [
                    'metadata' => $this->getReportMetadata($dateFrom, $dateTo, $reportType),
                    'executive_summary' => $this->getExecutiveSummary($dateFrom, $dateTo),
                    'income_analysis' => $this->getIncomeAnalysis($dateFrom, $dateTo),
                    'expense_analysis' => $this->getExpenseAnalysis($dateFrom, $dateTo),
                    'cash_flow_statement' => $this->getCashFlowStatement($dateFrom, $dateTo),
                    'profitability_analysis' => $this->getProfitabilityAnalysis($dateFrom, $dateTo),
                    'variance_analysis' => $this->getVarianceAnalysis($dateFrom, $dateTo),
                    'trend_analysis' => $this->getDetailedTrendAnalysis($dateFrom, $dateTo),
                    'category_breakdown' => $this->getCategoryBreakdown($dateFrom, $dateTo),
                    'validation_status' => $this->getValidationStatusReport($dateFrom, $dateTo),
                    'risk_assessment' => $this->getRiskAssessment($dateFrom, $dateTo),
                    'recommendations' => $this->getRecommendations($dateFrom, $dateTo),
                    'charts_data' => $includeCharts ? $this->getChartsData($dateFrom, $dateTo) : [],
                ];
            }, $this->cacheMinutes * 60);
            
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'financial_report_generation',
                $duration,
                [
                    'date_from' => $dateFrom->format('Y-m-d'),
                    'date_to' => $dateTo->format('Y-m-d'),
                    'type' => $reportType,
                    'format' => $format,
                ],
                $duration > 5 ? 'warning' : 'info'
            );
            
            // Convert to requested format
            return $this->formatReport($report, $format, $params);
            
        } catch (Exception $e) {
            $this->loggingService->logError(
                'Failed to generate financial report',
                $e,
                [
                    'params' => $params,
                    'user_id' => Auth::id(),
                ],
                'error'
            );
            
            throw $e;
        }
    }
    
    /**
     * Get report metadata
     */
    protected function getReportMetadata(Carbon $dateFrom, Carbon $dateTo, string $reportType): array
    {
        return [
            'report_title' => 'Financial Report',
            'report_type' => $reportType,
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
                'days' => $dateFrom->diffInDays($dateTo) + 1,
                'description' => $this->getPeriodDescription($dateFrom, $dateTo),
            ],
            'generated_at' => now()->toISOString(),
            'generated_by' => Auth::user()->name ?? 'System',
            'currency' => 'IDR',
            'version' => '1.0',
        ];
    }
    
    /**
     * Get executive summary
     */
    protected function getExecutiveSummary(Carbon $dateFrom, Carbon $dateTo): array
    {
        try {
            // Get key financial metrics
            $totalIncome = $this->getTotalIncome($dateFrom, $dateTo);
            $totalExpense = $this->getTotalExpense($dateFrom, $dateTo);
            $netIncome = $totalIncome - $totalExpense;
            $profitMargin = $totalIncome > 0 ? ($netIncome / $totalIncome) * 100 : 0;
            
            // Get comparison with previous period
            $periodDays = $dateFrom->diffInDays($dateTo) + 1;
            $previousDateFrom = $dateFrom->copy()->subDays($periodDays);
            $previousDateTo = $dateFrom->copy()->subDay();
            
            $previousIncome = $this->getTotalIncome($previousDateFrom, $previousDateTo);
            $previousExpense = $this->getTotalExpense($previousDateFrom, $previousDateTo);
            $previousNet = $previousIncome - $previousExpense;
            
            // Calculate growth rates
            $incomeGrowth = $this->calculateGrowthRate($totalIncome, $previousIncome);
            $expenseGrowth = $this->calculateGrowthRate($totalExpense, $previousExpense);
            $netGrowth = $this->calculateGrowthRate($netIncome, $previousNet);
            
            // Get transaction counts
            $transactionCounts = $this->getTransactionCounts($dateFrom, $dateTo);
            
            return [
                'financial_highlights' => [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'net_income' => $netIncome,
                    'profit_margin' => round($profitMargin, 2),
                    'income_growth' => $incomeGrowth,
                    'expense_growth' => $expenseGrowth,
                    'net_growth' => $netGrowth,
                ],
                'operational_highlights' => [
                    'total_transactions' => $transactionCounts['total'],
                    'income_transactions' => $transactionCounts['income'],
                    'expense_transactions' => $transactionCounts['expense'],
                    'average_transaction_value' => $transactionCounts['total'] > 0 ? 
                        round(($totalIncome + $totalExpense) / $transactionCounts['total'], 2) : 0,
                ],
                'key_insights' => $this->generateKeyInsights($dateFrom, $dateTo, [
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'net' => $netIncome,
                    'transactions' => $transactionCounts,
                ]),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get executive summary', [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'financial_highlights' => [],
                'operational_highlights' => [],
                'key_insights' => [],
                'error' => 'Unable to generate executive summary',
            ];
        }
    }
    
    /**
     * Get income analysis
     */
    protected function getIncomeAnalysis(Carbon $dateFrom, Carbon $dateTo): array
    {
        try {
            // Get income by source
            $pendapatanByCategory = DB::table('pendapatan_harians')
                ->join('pendapatan', 'pendapatan_harians.pendapatan_id', '=', 'pendapatan.id')
                ->select('pendapatan.kategori', 'pendapatan.nama_pendapatan')
                ->selectRaw('SUM(pendapatan_harians.nominal) as total')
                ->selectRaw('COUNT(pendapatan_harians.id) as count')
                ->selectRaw('AVG(pendapatan_harians.nominal) as average')
                ->whereBetween('pendapatan_harians.tanggal_input', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
                ->where('pendapatan_harians.status_validasi', 'disetujui')
                ->groupBy('pendapatan.kategori', 'pendapatan.nama_pendapatan')
                ->orderByDesc('total')
                ->get();
            
            $tindakanByCategory = DB::table('tindakan')
                ->join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
                ->select('jenis_tindakan.kategori', 'jenis_tindakan.nama')
                ->selectRaw('SUM(tindakan.tarif) as total')
                ->selectRaw('COUNT(tindakan.id) as count')
                ->selectRaw('AVG(tindakan.tarif) as average')
                ->whereBetween(DB::raw('DATE(tindakan.tanggal_tindakan)'), [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
                ->where('tindakan.status_validasi', 'disetujui')
                ->groupBy('jenis_tindakan.kategori', 'jenis_tindakan.nama')
                ->orderByDesc('total')
                ->get();
            
            // Daily income trend
            $dailyIncome = $this->getDailyIncomeTrend($dateFrom, $dateTo);
            
            // Top income sources
            $topIncomeCategories = $pendapatanByCategory->take(5);
            $topTindakanCategories = $tindakanByCategory->take(5);
            
            return [
                'total_income' => $this->getTotalIncome($dateFrom, $dateTo),
                'income_sources' => [
                    'pendapatan' => [
                        'total' => $pendapatanByCategory->sum('total'),
                        'count' => $pendapatanByCategory->sum('count'),
                        'average' => $pendapatanByCategory->avg('average'),
                        'by_category' => $pendapatanByCategory->toArray(),
                        'top_categories' => $topIncomeCategories->toArray(),
                    ],
                    'tindakan' => [
                        'total' => $tindakanByCategory->sum('total'),
                        'count' => $tindakanByCategory->sum('count'),
                        'average' => $tindakanByCategory->avg('average'),
                        'by_category' => $tindakanByCategory->toArray(),
                        'top_categories' => $topTindakanCategories->toArray(),
                    ],
                ],
                'daily_trend' => $dailyIncome,
                'growth_analysis' => $this->getIncomeGrowthAnalysis($dateFrom, $dateTo),
                'seasonality' => $this->getIncomeSeasonality($dateFrom, $dateTo),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get income analysis', [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_income' => 0,
                'income_sources' => [],
                'daily_trend' => [],
                'growth_analysis' => [],
                'seasonality' => [],
                'error' => 'Unable to generate income analysis',
            ];
        }
    }
    
    /**
     * Get expense analysis
     */
    protected function getExpenseAnalysis(Carbon $dateFrom, Carbon $dateTo): array
    {
        try {
            // Get expenses by category
            $expensesByCategory = DB::table('pengeluaran_harian')
                ->join('pengeluaran', 'pengeluaran_harian.pengeluaran_id', '=', 'pengeluaran.id')
                ->select('pengeluaran.kategori', 'pengeluaran.nama_pengeluaran')
                ->selectRaw('SUM(pengeluaran_harian.nominal) as total')
                ->selectRaw('COUNT(pengeluaran_harian.id) as count')
                ->selectRaw('AVG(pengeluaran_harian.nominal) as average')
                ->whereBetween('pengeluaran_harian.tanggal_input', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
                ->where('pengeluaran_harian.status_validasi', 'disetujui')
                ->groupBy('pengeluaran.kategori', 'pengeluaran.nama_pengeluaran')
                ->orderByDesc('total')
                ->get();
            
            // Daily expense trend
            $dailyExpense = $this->getDailyExpenseTrend($dateFrom, $dateTo);
            
            // Top expense categories
            $topExpenseCategories = $expensesByCategory->take(5);
            
            // Expense efficiency metrics
            $totalExpense = $this->getTotalExpense($dateFrom, $dateTo);
            $totalIncome = $this->getTotalIncome($dateFrom, $dateTo);
            $expenseRatio = $totalIncome > 0 ? ($totalExpense / $totalIncome) * 100 : 0;
            
            return [
                'total_expense' => $totalExpense,
                'expense_ratio' => round($expenseRatio, 2),
                'expense_breakdown' => [
                    'by_category' => $expensesByCategory->toArray(),
                    'top_categories' => $topExpenseCategories->toArray(),
                    'largest_expense' => $expensesByCategory->first(),
                ],
                'daily_trend' => $dailyExpense,
                'efficiency_metrics' => [
                    'expense_per_transaction' => $expensesByCategory->sum('count') > 0 ? 
                        round($totalExpense / $expensesByCategory->sum('count'), 2) : 0,
                    'cost_control_index' => $this->calculateCostControlIndex($dateFrom, $dateTo),
                ],
                'variance_analysis' => $this->getExpenseVarianceAnalysis($dateFrom, $dateTo),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get expense analysis', [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_expense' => 0,
                'expense_ratio' => 0,
                'expense_breakdown' => [],
                'daily_trend' => [],
                'efficiency_metrics' => [],
                'variance_analysis' => [],
                'error' => 'Unable to generate expense analysis',
            ];
        }
    }
    
    /**
     * Get cash flow statement
     */
    protected function getCashFlowStatement(Carbon $dateFrom, Carbon $dateTo): array
    {
        try {
            $dailyCashFlow = collect();
            $current = $dateFrom->copy();
            $runningBalance = 0;
            
            while ($current->lte($dateTo)) {
                $dailyIncome = $this->getDailyIncome($current);
                $dailyExpense = $this->getDailyExpense($current);
                $netCashFlow = $dailyIncome - $dailyExpense;
                $runningBalance += $netCashFlow;
                
                $dailyCashFlow->push([
                    'date' => $current->format('Y-m-d'),
                    'income' => $dailyIncome,
                    'expense' => $dailyExpense,
                    'net_cash_flow' => $netCashFlow,
                    'running_balance' => $runningBalance,
                ]);
                
                $current->addDay();
            }
            
            // Calculate cash flow metrics
            $totalInflow = $dailyCashFlow->sum('income');
            $totalOutflow = $dailyCashFlow->sum('expense');
            $netCashFlow = $totalInflow - $totalOutflow;
            $averageDailyCashFlow = $dailyCashFlow->avg('net_cash_flow');
            
            // Cash flow patterns
            $positiveDays = $dailyCashFlow->where('net_cash_flow', '>', 0)->count();
            $negativeDays = $dailyCashFlow->where('net_cash_flow', '<', 0)->count();
            $neutralDays = $dailyCashFlow->where('net_cash_flow', '=', 0)->count();
            
            return [
                'summary' => [
                    'total_inflow' => $totalInflow,
                    'total_outflow' => $totalOutflow,
                    'net_cash_flow' => $netCashFlow,
                    'ending_balance' => $runningBalance,
                    'average_daily_cash_flow' => round($averageDailyCashFlow, 2),
                ],
                'daily_cash_flow' => $dailyCashFlow->toArray(),
                'cash_flow_patterns' => [
                    'positive_days' => $positiveDays,
                    'negative_days' => $negativeDays,
                    'neutral_days' => $neutralDays,
                    'volatility' => $this->calculateCashFlowVolatility($dailyCashFlow),
                ],
                'cash_flow_ratios' => [
                    'operating_cash_ratio' => $totalOutflow > 0 ? round($totalInflow / $totalOutflow, 2) : 0,
                    'cash_conversion_cycle' => $this->calculateCashConversionCycle($dateFrom, $dateTo),
                ],
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get cash flow statement', [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'summary' => [],
                'daily_cash_flow' => [],
                'cash_flow_patterns' => [],
                'cash_flow_ratios' => [],
                'error' => 'Unable to generate cash flow statement',
            ];
        }
    }
    
    /**
     * Get profitability analysis
     */
    protected function getProfitabilityAnalysis(Carbon $dateFrom, Carbon $dateTo): array
    {
        try {
            $totalIncome = $this->getTotalIncome($dateFrom, $dateTo);
            $totalExpense = $this->getTotalExpense($dateFrom, $dateTo);
            $grossProfit = $totalIncome - $totalExpense;
            
            // Calculate various profitability metrics
            $grossProfitMargin = $totalIncome > 0 ? ($grossProfit / $totalIncome) * 100 : 0;
            $expenseRatio = $totalIncome > 0 ? ($totalExpense / $totalIncome) * 100 : 0;
            
            // ROI calculations
            $averageAssets = $this->calculateAverageAssets($dateFrom, $dateTo);
            $roi = $averageAssets > 0 ? ($grossProfit / $averageAssets) * 100 : 0;
            
            // Profitability by category
            $profitabilityByCategory = $this->getProfitabilityByCategory($dateFrom, $dateTo);
            
            // Break-even analysis
            $breakEvenAnalysis = $this->getBreakEvenAnalysis($dateFrom, $dateTo);
            
            return [
                'key_metrics' => [
                    'gross_profit' => $grossProfit,
                    'gross_profit_margin' => round($grossProfitMargin, 2),
                    'expense_ratio' => round($expenseRatio, 2),
                    'return_on_investment' => round($roi, 2),
                ],
                'profitability_trends' => $this->getProfitabilityTrends($dateFrom, $dateTo),
                'category_profitability' => $profitabilityByCategory,
                'break_even_analysis' => $breakEvenAnalysis,
                'benchmark_comparison' => $this->getBenchmarkComparison($grossProfitMargin, $expenseRatio),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get profitability analysis', [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'key_metrics' => [],
                'profitability_trends' => [],
                'category_profitability' => [],
                'break_even_analysis' => [],
                'benchmark_comparison' => [],
                'error' => 'Unable to generate profitability analysis',
            ];
        }
    }
    
    /**
     * Export report to PDF
     */
    public function exportToPdf(array $reportData, array $options = []): string
    {
        try {
            $fileName = $options['filename'] ?? 'financial_report_' . now()->format('Y_m_d_H_i_s') . '.pdf';
            $template = $options['template'] ?? 'reports.financial.pdf';
            
            // Prepare data for PDF
            $pdfData = [
                'report' => $reportData,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'generated_by' => Auth::user()->name ?? 'System',
                'logo_path' => public_path('images/logo.png'),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView($template, $pdfData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => true,
                ]);
            
            // Save to storage
            $filePath = "reports/financial/{$fileName}";
            Storage::disk('public')->put($filePath, $pdf->output());
            
            // Log export
            $this->loggingService->logActivity(
                'financial_report_pdf_export',
                null,
                [
                    'filename' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => Storage::disk('public')->size($filePath),
                ],
                'Financial report exported to PDF: ' . $fileName
            );
            
            return Storage::disk('public')->url($filePath);
            
        } catch (Exception $e) {
            $this->loggingService->logError(
                'Failed to export financial report to PDF',
                $e,
                [
                    'options' => $options,
                    'user_id' => Auth::id(),
                ],
                'error'
            );
            
            throw $e;
        }
    }
    
    /**
     * Export report to Excel
     */
    public function exportToExcel(array $reportData, array $options = []): string
    {
        try {
            $fileName = $options['filename'] ?? 'financial_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filePath = "reports/financial/{$fileName}";
            
            // Create Excel export class
            $export = new class($reportData) implements \Maatwebsite\Excel\Concerns\FromCollection, 
                                                    \Maatwebsite\Excel\Concerns\WithHeadings,
                                                    \Maatwebsite\Excel\Concerns\WithMapping,
                                                    \Maatwebsite\Excel\Concerns\WithMultipleSheets {
                private $reportData;
                
                public function __construct($reportData) {
                    $this->reportData = $reportData;
                }
                
                public function sheets(): array {
                    return [
                        'Summary' => new class($this->reportData['executive_summary']) implements \Maatwebsite\Excel\Concerns\FromArray {
                            private $data;
                            public function __construct($data) { $this->data = $data; }
                            public function array(): array { return $this->data; }
                        },
                        'Income Analysis' => new class($this->reportData['income_analysis']) implements \Maatwebsite\Excel\Concerns\FromArray {
                            private $data;
                            public function __construct($data) { $this->data = $data; }
                            public function array(): array { return $this->data; }
                        },
                        'Expense Analysis' => new class($this->reportData['expense_analysis']) implements \Maatwebsite\Excel\Concerns\FromArray {
                            private $data;
                            public function __construct($data) { $this->data = $data; }
                            public function array(): array { return $this->data; }
                        },
                        'Cash Flow' => new class($this->reportData['cash_flow_statement']) implements \Maatwebsite\Excel\Concerns\FromArray {
                            private $data;
                            public function __construct($data) { $this->data = $data; }
                            public function array(): array { return $this->data; }
                        },
                    ];
                }
                
                public function collection() { return collect(); }
                public function headings(): array { return []; }
                public function map($row): array { return []; }
            };
            
            // Export to Excel
            Excel::store($export, $filePath, 'public');
            
            // Log export
            $this->loggingService->logActivity(
                'financial_report_excel_export',
                null,
                [
                    'filename' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => Storage::disk('public')->size($filePath),
                ],
                'Financial report exported to Excel: ' . $fileName
            );
            
            return Storage::disk('public')->url($filePath);
            
        } catch (Exception $e) {
            $this->loggingService->logError(
                'Failed to export financial report to Excel',
                $e,
                [
                    'options' => $options,
                    'user_id' => Auth::id(),
                ],
                'error'
            );
            
            throw $e;
        }
    }
    
    /**
     * Generate real-time dashboard report
     */
    public function generateDashboardReport(): array
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $thisYear = Carbon::now()->startOfYear();
            
            return [
                'real_time_metrics' => [
                    'today' => $this->getDashboardMetrics($today, $today),
                    'this_month' => $this->getDashboardMetrics($thisMonth, Carbon::now()),
                    'this_year' => $this->getDashboardMetrics($thisYear, Carbon::now()),
                ],
                'live_data' => [
                    'latest_transactions' => $this->getLatestTransactions(10),
                    'pending_validations' => $this->getPendingValidationsCount(),
                    'alerts' => $this->getFinancialAlerts(),
                ],
                'quick_insights' => $this->getQuickInsights(),
                'last_updated' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            $this->loggingService->logError(
                'Failed to generate dashboard report',
                $e,
                ['user_id' => Auth::id()],
                'error'
            );
            
            return [
                'real_time_metrics' => [],
                'live_data' => [],
                'quick_insights' => [],
                'error' => 'Unable to generate dashboard report',
                'last_updated' => now()->toISOString(),
            ];
        }
    }
    
    // === HELPER METHODS ===
    
    protected function getTotalIncome(Carbon $dateFrom, Carbon $dateTo): float
    {
        $pendapatan = PendapatanHarian::whereBetween('tanggal_input', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('status_validasi', 'disetujui')
            ->sum('nominal') ?? 0;
        
        $tindakan = Tindakan::whereBetween(DB::raw('DATE(tanggal_tindakan)'), [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('status_validasi', 'disetujui')
            ->sum('tarif') ?? 0;
        
        return $pendapatan + $tindakan;
    }
    
    protected function getTotalExpense(Carbon $dateFrom, Carbon $dateTo): float
    {
        return PengeluaranHarian::whereBetween('tanggal_input', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('status_validasi', 'disetujui')
            ->sum('nominal') ?? 0;
    }
    
    protected function getDailyIncome(Carbon $date): float
    {
        $pendapatan = PendapatanHarian::whereDate('tanggal_input', $date)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal') ?? 0;
        
        $tindakan = Tindakan::whereDate('tanggal_tindakan', $date)
            ->where('status_validasi', 'disetujui')
            ->sum('tarif') ?? 0;
        
        return $pendapatan + $tindakan;
    }
    
    protected function getDailyExpense(Carbon $date): float
    {
        return PengeluaranHarian::whereDate('tanggal_input', $date)
            ->where('status_validasi', 'disetujui')
            ->sum('nominal') ?? 0;
    }
    
    protected function calculateGrowthRate(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
    protected function getTransactionCounts(Carbon $dateFrom, Carbon $dateTo): array
    {
        $pendapatanCount = PendapatanHarian::whereBetween('tanggal_input', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('status_validasi', 'disetujui')
            ->count();
        
        $pengeluaranCount = PengeluaranHarian::whereBetween('tanggal_input', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('status_validasi', 'disetujui')
            ->count();
        
        $tindakanCount = Tindakan::whereBetween(DB::raw('DATE(tanggal_tindakan)'), [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('status_validasi', 'disetujui')
            ->count();
        
        return [
            'income' => $pendapatanCount + $tindakanCount,
            'expense' => $pengeluaranCount,
            'total' => $pendapatanCount + $pengeluaranCount + $tindakanCount,
        ];
    }
    
    protected function formatReport(array $report, string $format, array $params): array
    {
        switch ($format) {
            case 'pdf':
                $pdfUrl = $this->exportToPdf($report, $params);
                return [
                    'success' => true,
                    'format' => 'pdf',
                    'download_url' => $pdfUrl,
                    'report_data' => $report,
                ];
                
            case 'excel':
                $excelUrl = $this->exportToExcel($report, $params);
                return [
                    'success' => true,
                    'format' => 'excel',
                    'download_url' => $excelUrl,
                    'report_data' => $report,
                ];
                
            case 'json':
                return [
                    'success' => true,
                    'format' => 'json',
                    'report_data' => json_encode($report, JSON_PRETTY_PRINT),
                ];
                
            default: // array
                return [
                    'success' => true,
                    'format' => 'array',
                    'report_data' => $report,
                ];
        }
    }
    
    protected function getPeriodDescription(Carbon $dateFrom, Carbon $dateTo): string
    {
        $days = $dateFrom->diffInDays($dateTo) + 1;
        
        if ($days == 1) {
            return "Daily report for " . $dateFrom->format('d M Y');
        } elseif ($days <= 7) {
            return "Weekly report ({$days} days)";
        } elseif ($days <= 31) {
            return "Monthly report ({$days} days)";
        } elseif ($days <= 365) {
            return "Yearly report ({$days} days)";
        } else {
            return "Custom period ({$days} days)";
        }
    }
    
    // Additional helper methods would be implemented here for:
    // - generateKeyInsights
    // - getDailyIncomeTrend
    // - getDailyExpenseTrend
    // - getIncomeGrowthAnalysis
    // - getIncomeSeasonality
    // - getExpenseVarianceAnalysis
    // - calculateCostControlIndex
    // - calculateCashFlowVolatility
    // - calculateCashConversionCycle
    // - getProfitabilityByCategory
    // - getBreakEvenAnalysis
    // - getProfitabilityTrends
    // - getBenchmarkComparison
    // - calculateAverageAssets
    // - getVarianceAnalysis
    // - getDetailedTrendAnalysis
    // - getCategoryBreakdown
    // - getValidationStatusReport
    // - getRiskAssessment
    // - getRecommendations
    // - getChartsData
    // - getDashboardMetrics
    // - getLatestTransactions
    // - getPendingValidationsCount
    // - getFinancialAlerts
    // - getQuickInsights
    
    /**
     * Clear report cache
     */
    public function clearReportCache(): void
    {
        try {
            $this->cacheService->flushTag('report');
            $this->cacheService->flushTag('dashboard');
            
            $this->loggingService->logActivity(
                'financial_report_cache_cleared',
                null,
                [],
                'Financial report cache cleared'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to clear financial report cache', [
                'error' => $e->getMessage()
            ]);
        }
    }
}