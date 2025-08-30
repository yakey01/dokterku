<?php

namespace App\Filament\Bendahara\Pages;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\Tindakan;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;

class BendaharaDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    
    protected static ?string $navigationGroup = 'Dashboard';
    
    protected static string $view = 'filament.bendahara.pages.petugas-style-dashboard';
    
    // Disable default page header - matching petugas configuration
    protected static bool $shouldShowPageHeader = false;
    
    protected function getHeaderActions(): array
    {
        return []; // No header actions to prevent duplicate headers
    }
    
    protected static ?string $title = '💰 Bendahara Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    // Removed duplicate navigationGroup property

    public function mount(): void
    {
        // Initialize world-class treasury dashboard
    }
    


    // Core Financial Metrics - World Class Treasury Analytics
    public function getFinancialSummary(): array
    {
        return Cache::remember('bendahara_financial_summary', now()->addMinutes(5), function () {
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Current month data
            $currentPendapatan = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $currentPengeluaran = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');
                
            $currentJaspel = Jaspel::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');

            // Last month for comparison
            $lastPendapatan = Pendapatan::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $lastPengeluaran = Pengeluaran::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');
                
            $lastJaspel = Jaspel::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');

            // Calculate net values for both current and previous periods
            $currentNet = $currentPendapatan - $currentPengeluaran - $currentJaspel;
            $previousNet = $lastPendapatan - $lastPengeluaran - $lastJaspel;
            
            return [
                'current' => [
                    'pendapatan' => $currentPendapatan,
                    'pengeluaran' => $currentPengeluaran,
                    'jaspel' => $currentJaspel,
                    'net_profit' => $currentNet,  // Keep existing key for new dashboard
                    'net_income' => $currentNet,  // Add for backward compatibility with legacy views
                ],
                'previous' => [
                    'pendapatan' => $lastPendapatan,
                    'pengeluaran' => $lastPengeluaran,
                    'jaspel' => $lastJaspel,
                    'net_profit' => $previousNet,  // Keep existing key for new dashboard
                    'net_income' => $previousNet,  // Add for backward compatibility with legacy views
                ],
                'changes' => [
                    'pendapatan' => $this->calculateGrowth($currentPendapatan, $lastPendapatan),
                    'pengeluaran' => $this->calculateGrowth($currentPengeluaran, $lastPengeluaran),
                    'jaspel' => $this->calculateGrowth($currentJaspel, $lastJaspel),
                    'net_profit' => $this->calculateGrowth($currentNet, $previousNet),  // Keep existing key
                    'net_income' => $this->calculateGrowth($currentNet, $previousNet),  // Add for backward compatibility
                ],
            ];
        });
    }

    // Validation Statistics 
    public function getValidationStats(): array
    {
        return Cache::remember('bendahara_validation_stats', now()->addMinutes(3), function () {
            $pending = [
                'pendapatan' => Pendapatan::where('status_validasi', 'pending')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'pending')->count(), 
                'jaspel' => Jaspel::where('status_validasi', 'pending')->count(),
            ];
            
            $approved = [
                'pendapatan' => Pendapatan::where('status_validasi', 'disetujui')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'disetujui')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'disetujui')->count(),
            ];
            
            $rejected = [
                'pendapatan' => Pendapatan::where('status_validasi', 'ditolak')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'ditolak')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'ditolak')->count(),
            ];

            return [
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'total_pending' => array_sum($pending),
                'total_approved' => array_sum($approved),
                'total_rejected' => array_sum($rejected),
            ];
        });
    }

    // Monthly Trend Analysis for Charts
    public function getMonthlyTrends(): array
    {
        return Cache::remember('bendahara_monthly_trends', now()->addMinutes(10), function () {
            $months = [];
            $pendapatan = [];
            $pengeluaran = [];
            $jaspel = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthlyPendapatan = Pendapatan::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                    
                $monthlyPengeluaran = Pengeluaran::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                    
                $monthlyJaspel = Jaspel::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                
                $pendapatan[] = $monthlyPendapatan;
                $pengeluaran[] = $monthlyPengeluaran;
                $jaspel[] = $monthlyJaspel;
            }
            
            return [
                'months' => $months,
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
                'jaspel' => $jaspel,
            ];
        });
    }

    // Recent Transactions
    public function getRecentTransactions(): array
    {
        return Cache::remember('bendahara_recent_transactions', now()->addMinutes(2), function () {
            $transactions = collect();
            
            // Recent pendapatan
            $recentPendapatan = Pendapatan::with(['inputBy'])
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'pendapatan',
                        'code' => 'REV-' . $item->id,
                        'description' => $item->nama_pendapatan,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi,
                        'date' => $item->updated_at,
                    ];
                });
            
            // Recent pengeluaran
            $recentPengeluaran = Pengeluaran::with(['inputBy'])
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'pengeluaran', 
                        'code' => 'EXP-' . $item->id,
                        'description' => $item->nama_pengeluaran,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi ?? 'disetujui',
                        'date' => $item->updated_at,
                    ];
                });
            
            return $transactions->merge($recentPendapatan)
                ->merge($recentPengeluaran)
                ->sortByDesc('date')
                ->take(10)
                ->values()
                ->toArray();
        });
    }

    private function calculateGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    private function exportReport(): void
    {
        // TODO: Implement export functionality
        $this->notify('success', 'Export functionality will be implemented soon');
    }
    
    // Top Performers
    public function getTopPerformers(): array
    {
        return Cache::remember('bendahara_top_performers', now()->addMinutes(10), function () {
            // Top doctors by jaspel
            $topDoctors = Jaspel::select('dokter_id', DB::raw('SUM(nominal) as total'))
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->groupBy('dokter_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => 'Dokter ' . ($item->dokter_id ?? 'Unknown'),
                        'total' => $item->total,
                    ];
                });
                
            // Mock procedures data
            $topProcedures = collect([
                ['name' => 'Konsultasi Umum', 'total' => 45],
                ['name' => 'Pemeriksaan Laboratorium', 'total' => 32],
                ['name' => 'Konsultasi Spesialis', 'total' => 28],
                ['name' => 'Pemeriksaan Radiologi', 'total' => 15],
                ['name' => 'Tindakan Medis', 'total' => 12],
            ]);
            
            return [
                'doctors' => $topDoctors,
                'procedures' => $topProcedures,
            ];
        });
    }
    
    protected function notify(string $type, string $message): void
    {
        session()->flash('filament.notification', [
            'type' => $type,
            'message' => $message,
        ]);
    }
    
    public function getWidgets(): array
    {
        return [
            // Widget removed to prevent Livewire multiple root elements error
            // Financial metrics now integrated directly in the main dashboard view
        ];
    }
    
    // Add missing methods for petugas-style dashboard template
    public function getValidationMetrics(): array
    {
        // Alias to existing getValidationStats for template compatibility
        return $this->getValidationStats();
    }
    
    public function getRecentActivities(): array
    {
        // Alias to existing getRecentTransactions for template compatibility
        return [
            'recent_activities' => collect($this->getRecentTransactions())->map(function ($transaction) {
                return [
                    'description' => $transaction['description'] ?? 'Transaction',
                    'amount' => $transaction['amount'] ?? 0,
                    'type' => $transaction['type'] ?? 'income',
                    'status' => $transaction['status'] ?? 'approved',
                    'date' => isset($transaction['date']) ? $transaction['date']->format('d/m/Y') : date('d/m/Y'),
                    'time' => isset($transaction['date']) ? $transaction['date']->diffForHumans() : 'Baru saja',
                    'user' => 'System',
                ];
            })->toArray()
        ];
    }
    
    public function getWidgetData(): array
    {
        return [];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        // ENABLE NAVIGATION TO PROVIDE ACCESSIBLE LANDING PAGE
        return true;
    }
}