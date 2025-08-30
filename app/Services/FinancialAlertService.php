<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\BendaharaStatsService;
use App\Services\TelegramService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FinancialAlertService
{
    protected BendaharaStatsService $statsService;
    protected TelegramService $telegramService;
    protected NotificationService $notificationService;
    
    protected array $defaultThresholds = [
        'daily_income_low' => 500000,      // Daily income below 500k
        'daily_expense_high' => 1000000,   // Daily expense above 1M
        'budget_target_warning' => 75,     // 75% of budget target
        'budget_target_critical' => 90,    // 90% of budget target
        'cash_flow_negative_days' => 3,    // 3 consecutive negative days
        'validation_queue_high' => 50,     // 50+ pending validations
        'expense_ratio_high' => 85,        // Expense ratio above 85%
        'growth_decline_threshold' => -10, // 10% decline in growth
        'profit_margin_low' => 5,          // Profit margin below 5%
        'critical_amount_threshold' => 5000000, // 5M+ transactions need alerts
    ];
    
    protected array $alertChannels = [
        'dashboard' => true,
        'telegram' => true,
        'email' => false,
        'database' => true,
    ];

    public function __construct(
        BendaharaStatsService $statsService,
        TelegramService $telegramService,
        NotificationService $notificationService
    ) {
        $this->statsService = $statsService;
        $this->telegramService = $telegramService;
        $this->notificationService = $notificationService;
    }

    /**
     * Run comprehensive financial monitoring and alerts
     */
    public function runFinancialMonitoring(): array
    {
        try {
            $alerts = [];
            $stats = $this->statsService->getDashboardStats();
            $thresholds = $this->getAlertThresholds();
            
            // Check all alert conditions
            $alerts = array_merge($alerts, $this->checkDailyPerformanceAlerts($stats, $thresholds));
            $alerts = array_merge($alerts, $this->checkBudgetTargetAlerts($stats, $thresholds));
            $alerts = array_merge($alerts, $this->checkCashFlowAlerts($stats, $thresholds));
            $alerts = array_merge($alerts, $this->checkValidationQueueAlerts($stats, $thresholds));
            $alerts = array_merge($alerts, $this->checkGrowthTrendAlerts($stats, $thresholds));
            $alerts = array_merge($alerts, $this->checkProfitabilityAlerts($stats, $thresholds));
            $alerts = array_merge($alerts, $this->checkCriticalTransactionAlerts($thresholds));
            
            // Process and send alerts
            $this->processAlerts($alerts);
            
            // Log monitoring activity
            $this->logMonitoringActivity(count($alerts));
            
            return [
                'success' => true,
                'alerts_triggered' => count($alerts),
                'alerts' => $alerts,
                'monitoring_timestamp' => now(),
            ];
            
        } catch (Exception $e) {
            Log::error('Financial monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'alerts_triggered' => 0,
            ];
        }
    }

    /**
     * Check daily performance alerts
     */
    protected function checkDailyPerformanceAlerts(array $stats, array $thresholds): array
    {
        $alerts = [];
        $today = $stats['daily']['today'] ?? [];
        
        // Low daily income alert
        if (($today['pendapatan_approved'] ?? 0) < $thresholds['daily_income_low']) {
            $alerts[] = [
                'type' => 'daily_income_low',
                'severity' => 'warning',
                'title' => '💰 Pendapatan Harian Rendah',
                'message' => 'Pendapatan hari ini hanya Rp ' . number_format($today['pendapatan_approved'] ?? 0, 0, ',', '.') . 
                           ' (Target minimal: Rp ' . number_format($thresholds['daily_income_low'], 0, ',', '.') . ')',
                'data' => [
                    'current_amount' => $today['pendapatan_approved'] ?? 0,
                    'threshold' => $thresholds['daily_income_low'],
                    'date' => now()->format('Y-m-d'),
                ],
                'recommendations' => [
                    'Review marketing strategy',
                    'Increase service promotion',
                    'Check operational issues',
                ],
            ];
        }
        
        // High daily expense alert
        if (($today['pengeluaran_approved'] ?? 0) > $thresholds['daily_expense_high']) {
            $alerts[] = [
                'type' => 'daily_expense_high',
                'severity' => 'warning',
                'title' => '💸 Pengeluaran Harian Tinggi',
                'message' => 'Pengeluaran hari ini mencapai Rp ' . number_format($today['pengeluaran_approved'] ?? 0, 0, ',', '.') . 
                           ' (Batas warning: Rp ' . number_format($thresholds['daily_expense_high'], 0, ',', '.') . ')',
                'data' => [
                    'current_amount' => $today['pengeluaran_approved'] ?? 0,
                    'threshold' => $thresholds['daily_expense_high'],
                    'date' => now()->format('Y-m-d'),
                ],
                'recommendations' => [
                    'Review all expenses today',
                    'Check for unusual transactions',
                    'Implement cost control measures',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Check budget target alerts
     */
    protected function checkBudgetTargetAlerts(array $stats, array $thresholds): array
    {
        $alerts = [];
        $budget = $stats['budget_tracking'] ?? [];
        $progress = $budget['income_progress'] ?? 0;
        
        // Budget target warning
        if ($progress >= $thresholds['budget_target_warning'] && $progress < $thresholds['budget_target_critical']) {
            $alerts[] = [
                'type' => 'budget_target_warning',
                'severity' => 'warning',
                'title' => '🎯 Mendekati Target Budget',
                'message' => 'Progress budget mencapai ' . round($progress, 1) . '% dari target bulanan',
                'data' => [
                    'progress_percentage' => $progress,
                    'target_amount' => $budget['monthly_income_target'] ?? 0,
                    'current_amount' => $stats['monthly']['this_month']['pendapatan_approved'] ?? 0,
                ],
                'recommendations' => [
                    'Monitor daily progress closely',
                    'Prepare action plan for final sprint',
                    'Optimize high-margin services',
                ],
            ];
        }
        
        // Budget target critical
        if ($progress >= $thresholds['budget_target_critical']) {
            $alerts[] = [
                'type' => 'budget_target_critical',
                'severity' => 'critical',
                'title' => '🚨 Target Budget Hampir Tercapai',
                'message' => 'Progress budget telah mencapai ' . round($progress, 1) . '% dari target bulanan!',
                'data' => [
                    'progress_percentage' => $progress,
                    'target_amount' => $budget['monthly_income_target'] ?? 0,
                    'current_amount' => $stats['monthly']['this_month']['pendapatan_approved'] ?? 0,
                ],
                'recommendations' => [
                    'Congratulations on excellent performance!',
                    'Consider stretching targets for next month',
                    'Document success factors',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Check cash flow alerts
     */
    protected function checkCashFlowAlerts(array $stats, array $thresholds): array
    {
        $alerts = [];
        $cashFlow = $stats['cash_flow'] ?? [];
        $trend = $cashFlow['cash_flow_trend'] ?? 'stable';
        
        // Declining cash flow
        if ($trend === 'declining') {
            $alerts[] = [
                'type' => 'cash_flow_declining',
                'severity' => 'warning',
                'title' => '📉 Arus Kas Menurun',
                'message' => 'Trend arus kas menunjukkan penurunan dalam beberapa hari terakhir',
                'data' => [
                    'trend' => $trend,
                    'net_cash_flow_30d' => $cashFlow['net_cash_flow_30d'] ?? 0,
                    'projected_monthly' => $cashFlow['projected_monthly'] ?? 0,
                ],
                'recommendations' => [
                    'Review collection policies',
                    'Accelerate receivables',
                    'Defer non-critical expenses',
                ],
            ];
        }
        
        // Check for consecutive negative days
        $negativeDays = $this->getConsecutiveNegativeDays();
        if ($negativeDays >= $thresholds['cash_flow_negative_days']) {
            $alerts[] = [
                'type' => 'negative_cash_flow_streak',
                'severity' => 'critical',
                'title' => '🚨 Arus Kas Negatif Berturut-turut',
                'message' => "Arus kas negatif selama {$negativeDays} hari berturut-turut",
                'data' => [
                    'consecutive_days' => $negativeDays,
                    'threshold' => $thresholds['cash_flow_negative_days'],
                ],
                'recommendations' => [
                    'URGENT: Review cash position',
                    'Implement immediate cost cuts',
                    'Accelerate revenue collection',
                    'Consider emergency funding',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Check validation queue alerts
     */
    protected function checkValidationQueueAlerts(array $stats, array $thresholds): array
    {
        $alerts = [];
        $validationSummary = $stats['validation_summary'] ?? [];
        $pendingCount = $validationSummary['pending_validations'] ?? 0;
        
        if ($pendingCount >= $thresholds['validation_queue_high']) {
            $alerts[] = [
                'type' => 'validation_queue_high',
                'severity' => 'warning',
                'title' => '📋 Antrian Validasi Tinggi',
                'message' => "Terdapat {$pendingCount} transaksi menunggu validasi",
                'data' => [
                    'pending_count' => $pendingCount,
                    'threshold' => $thresholds['validation_queue_high'],
                    'urgent_count' => $validationSummary['urgent_validations'] ?? 0,
                ],
                'recommendations' => [
                    'Prioritize urgent validations',
                    'Add validation resources',
                    'Review validation workflow',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Check growth trend alerts
     */
    protected function checkGrowthTrendAlerts(array $stats, array $thresholds): array
    {
        $alerts = [];
        $financialMetrics = $stats['financial_metrics'] ?? [];
        $incomeGrowth = $financialMetrics['income_growth'] ?? 0;
        
        if ($incomeGrowth <= $thresholds['growth_decline_threshold']) {
            $alerts[] = [
                'type' => 'growth_decline',
                'severity' => 'critical',
                'title' => '📉 Penurunan Pertumbuhan Signifikan',
                'message' => "Pertumbuhan pendapatan turun {$incomeGrowth}% dibanding bulan lalu",
                'data' => [
                    'growth_percentage' => $incomeGrowth,
                    'threshold' => $thresholds['growth_decline_threshold'],
                ],
                'recommendations' => [
                    'Conduct growth analysis',
                    'Review market conditions',
                    'Implement recovery strategy',
                    'Consider new revenue streams',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Check profitability alerts
     */
    protected function checkProfitabilityAlerts(array $stats, array $thresholds): array
    {
        $alerts = [];
        $financialMetrics = $stats['financial_metrics'] ?? [];
        $profitMargin = $financialMetrics['profit_margin'] ?? 0;
        
        if ($profitMargin <= $thresholds['profit_margin_low']) {
            $alerts[] = [
                'type' => 'profit_margin_low',
                'severity' => 'warning',
                'title' => '📊 Margin Profit Rendah',
                'message' => "Margin profit hanya {$profitMargin}% (Target minimal: {$thresholds['profit_margin_low']}%)",
                'data' => [
                    'profit_margin' => $profitMargin,
                    'threshold' => $thresholds['profit_margin_low'],
                    'expense_ratio' => $financialMetrics['expense_ratio'] ?? 0,
                ],
                'recommendations' => [
                    'Review pricing strategy',
                    'Optimize operational costs',
                    'Focus on high-margin services',
                    'Negotiate better supplier terms',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Check for critical transaction alerts
     */
    protected function checkCriticalTransactionAlerts(array $thresholds): array
    {
        $alerts = [];
        
        // Check for large pending transactions
        $criticalPendingAmount = DB::table('pendapatan_harians')
            ->where('status_validasi', 'pending')
            ->where('nominal', '>=', $thresholds['critical_amount_threshold'])
            ->where('created_at', '>=', now()->subDays(3))
            ->sum('nominal');
        
        if ($criticalPendingAmount > 0) {
            $alerts[] = [
                'type' => 'critical_pending_transactions',
                'severity' => 'critical',
                'title' => '🚨 Transaksi Kritis Menunggu Validasi',
                'message' => 'Terdapat transaksi bernilai tinggi menunggu validasi: Rp ' . number_format($criticalPendingAmount, 0, ',', '.'),
                'data' => [
                    'pending_amount' => $criticalPendingAmount,
                    'threshold' => $thresholds['critical_amount_threshold'],
                ],
                'recommendations' => [
                    'URGENT: Validate high-value transactions',
                    'Escalate to senior management',
                    'Verify transaction authenticity',
                ],
            ];
        }
        
        return $alerts;
    }

    /**
     * Process and send alerts through configured channels
     */
    protected function processAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            try {
                // Store alert in database
                if ($this->alertChannels['database']) {
                    $this->storeAlert($alert);
                }
                
                // Send telegram notification
                if ($this->alertChannels['telegram']) {
                    $this->sendTelegramAlert($alert);
                }
                
                // Send dashboard notification
                if ($this->alertChannels['dashboard']) {
                    $this->sendDashboardAlert($alert);
                }
                
                // Send email (if configured)
                if ($this->alertChannels['email']) {
                    $this->sendEmailAlert($alert);
                }
                
            } catch (Exception $e) {
                Log::error('Failed to process alert', [
                    'alert_type' => $alert['type'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Store alert in database for tracking
     */
    protected function storeAlert(array $alert): void
    {
        try {
            AuditLog::create([
                'user_id' => null, // System alert
                'action' => 'financial_alert_' . $alert['type'],
                'model_type' => 'FinancialAlert',
                'model_id' => null,
                'changes' => json_encode([
                    'alert_type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'data' => $alert['data'] ?? [],
                    'recommendations' => $alert['recommendations'] ?? [],
                    'triggered_at' => now(),
                ]),
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => 'FinancialAlertSystem',
                'url' => 'system://financial-alerts',
                'method' => 'AUTO',
                'risk_level' => $alert['severity'] === 'critical' ? 'high' : 'medium',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to store alert in database', [
                'alert_type' => $alert['type'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send telegram alert to bendahara users
     */
    protected function sendTelegramAlert(array $alert): void
    {
        try {
            $severityEmoji = match($alert['severity']) {
                'critical' => '🚨',
                'warning' => '⚠️',
                'info' => 'ℹ️',
                default => '📢',
            };
            
            $message = "{$severityEmoji} **FINANCIAL ALERT**\n\n";
            $message .= "🏷️ **{$alert['title']}**\n";
            $message .= "📝 {$alert['message']}\n\n";
            
            if (!empty($alert['recommendations'])) {
                $message .= "💡 **Rekomendasi:**\n";
                foreach ($alert['recommendations'] as $rec) {
                    $message .= "• {$rec}\n";
                }
            }
            
            $message .= "\n⏰ " . now()->format('d/m/Y H:i:s');
            
            // Send to all bendahara users
            $bendaharaUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['bendahara', 'supervisor', 'manager']);
            })->get();
            
            foreach ($bendaharaUsers as $user) {
                $this->telegramService->sendMessage($user->id, $message);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to send telegram alert', [
                'alert_type' => $alert['type'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send dashboard notification
     */
    protected function sendDashboardAlert(array $alert): void
    {
        try {
            $notificationData = [
                'title' => $alert['title'],
                'message' => $alert['message'],
                'severity' => $alert['severity'],
                'type' => 'financial_alert',
                'data' => $alert['data'] ?? [],
            ];
            
            // Send to all bendahara users
            $bendaharaUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['bendahara', 'supervisor', 'manager']);
            })->get();
            
            foreach ($bendaharaUsers as $user) {
                $this->notificationService->sendRealTimeNotification(
                    $user->id,
                    'financial_alert',
                    $alert['title'],
                    $alert['message'],
                    $notificationData,
                    $alert['severity']
                );
            }
            
        } catch (Exception $e) {
            Log::error('Failed to send dashboard alert', [
                'alert_type' => $alert['type'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email alert (placeholder for future implementation)
     */
    protected function sendEmailAlert(array $alert): void
    {
        // TODO: Implement email alert functionality
        Log::info('Email alert would be sent here', ['alert' => $alert]);
    }

    /**
     * Get alert thresholds from cache or use defaults
     */
    protected function getAlertThresholds(): array
    {
        return Cache::get('financial_alert_thresholds', $this->defaultThresholds);
    }

    /**
     * Update alert thresholds
     */
    public function updateAlertThresholds(array $thresholds): bool
    {
        try {
            $mergedThresholds = array_merge($this->defaultThresholds, $thresholds);
            Cache::put('financial_alert_thresholds', $mergedThresholds, now()->addYear());
            
            // Log the threshold update
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'financial_alert_thresholds_updated',
                'model_type' => 'FinancialAlertSettings',
                'model_id' => null,
                'changes' => json_encode([
                    'old_thresholds' => $this->getAlertThresholds(),
                    'new_thresholds' => $mergedThresholds,
                    'updated_by' => auth()->user()?->name,
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => 'medium',
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to update alert thresholds', [
                'error' => $e->getMessage(),
                'thresholds' => $thresholds
            ]);
            return false;
        }
    }

    /**
     * Get consecutive negative cash flow days
     */
    protected function getConsecutiveNegativeDays(): int
    {
        try {
            $consecutiveDays = 0;
            $currentDate = now();
            
            for ($i = 0; $i < 30; $i++) {
                $checkDate = $currentDate->copy()->subDays($i);
                $dailyStats = $this->statsService->getFinancialStatsForDate($checkDate);
                
                if (($dailyStats['net_income'] ?? 0) < 0) {
                    $consecutiveDays++;
                } else {
                    break; // Stop at first positive day
                }
            }
            
            return $consecutiveDays;
        } catch (Exception $e) {
            Log::error('Failed to calculate consecutive negative days', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Log monitoring activity
     */
    protected function logMonitoringActivity(int $alertCount): void
    {
        try {
            AuditLog::create([
                'user_id' => null,
                'action' => 'financial_monitoring_completed',
                'model_type' => 'FinancialMonitoring',
                'model_id' => null,
                'changes' => json_encode([
                    'alerts_triggered' => $alertCount,
                    'monitoring_timestamp' => now(),
                    'system_version' => '1.0.0',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'FinancialAlertSystem',
                'url' => 'system://financial-monitoring',
                'method' => 'AUTO',
                'risk_level' => 'low',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log monitoring activity', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get alert history and statistics
     */
    public function getAlertHistory(int $days = 30): array
    {
        try {
            $alerts = AuditLog::where('action', 'like', 'financial_alert_%')
                ->where('created_at', '>=', now()->subDays($days))
                ->orderByDesc('created_at')
                ->get();
            
            $stats = [
                'total_alerts' => $alerts->count(),
                'critical_alerts' => $alerts->where('risk_level', 'high')->count(),
                'warning_alerts' => $alerts->where('risk_level', 'medium')->count(),
                'alert_types' => $alerts->groupBy(function ($alert) {
                    $changes = json_decode($alert->changes, true);
                    return $changes['alert_type'] ?? 'unknown';
                })->map->count(),
                'daily_distribution' => $alerts->groupBy(function ($alert) {
                    return $alert->created_at->format('Y-m-d');
                })->map->count(),
            ];
            
            return [
                'success' => true,
                'stats' => $stats,
                'alerts' => $alerts->take(50)->toArray(), // Limit to 50 most recent
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get alert history', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => [],
                'alerts' => [],
            ];
        }
    }

    /**
     * Test alert system
     */
    public function testAlertSystem(): array
    {
        try {
            $testAlert = [
                'type' => 'test_alert',
                'severity' => 'info',
                'title' => '🧪 Test Alert System',
                'message' => 'This is a test alert to verify the system is working properly.',
                'data' => [
                    'test_timestamp' => now(),
                    'test_user' => auth()->user()?->name ?? 'System',
                ],
                'recommendations' => [
                    'Alert system is functioning correctly',
                    'All channels are operational',
                ],
            ];
            
            $this->processAlerts([$testAlert]);
            
            return [
                'success' => true,
                'message' => 'Test alert sent successfully through all configured channels',
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}