<?php

namespace App\Filament\Petugas\Pages;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\DB;

class PetugasDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static string $view = 'filament.petugas.pages.elegant-glassmorphic-dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Petugas\Widgets\PetugasHeaderWidget::class,
        ];
    }
    
    protected static ?string $title = 'Dashboard Petugas';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = null;
    
    protected static ?string $slug = 'dashboard';
    
    // Ensure navigation visibility - single dashboard only
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    public static function canView(): bool
    {
        return true;
    }
    
    public function getOperationalSummary(): array
    {
        $currentMonth = now();
        $lastMonth = now()->subMonth();
        
        // Current month data
        $currentPendapatan = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentPengeluaran = PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentPasien = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $currentTindakan = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();
        
        // Last month data for comparison
        $lastPendapatan = PendapatanHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal');
            
        $lastPengeluaran = PengeluaranHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal');
            
        $lastPasien = JumlahPasienHarian::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $lastTindakan = Tindakan::whereMonth('tanggal_tindakan', $lastMonth->month)
            ->whereYear('tanggal_tindakan', $lastMonth->year)
            ->count();
        
        return [
            'current' => [
                'pendapatan' => $currentPendapatan,
                'pengeluaran' => $currentPengeluaran,
                'pasien' => $currentPasien,
                'tindakan' => $currentTindakan,
                'net_income' => $currentPendapatan - $currentPengeluaran,
            ],
            'last_month' => [
                'pendapatan' => $lastPendapatan,
                'pengeluaran' => $lastPengeluaran,
                'pasien' => $lastPasien,
                'tindakan' => $lastTindakan,
                'net_income' => $lastPendapatan - $lastPengeluaran,
            ],
            'changes' => [
                'pendapatan' => $this->calculatePercentageChange($currentPendapatan, $lastPendapatan),
                'pengeluaran' => $this->calculatePercentageChange($currentPengeluaran, $lastPengeluaran),
                'pasien' => $this->calculatePercentageChange($currentPasien, $lastPasien),
                'tindakan' => $this->calculatePercentageChange($currentTindakan, $lastTindakan),
                'net_income' => $this->calculatePercentageChange(
                    $currentPendapatan - $currentPengeluaran,
                    $lastPendapatan - $lastPengeluaran
                ),
            ],
        ];
    }
    
    public function getDataEntryStats(): array
    {
        $today = now()->toDateString();
        
        // Today's completed entries
        $completed = [
            'pendapatan' => PendapatanHarian::whereDate('tanggal_input', $today)->count(),
            'pengeluaran' => PengeluaranHarian::whereDate('tanggal_input', $today)->count(),
            'pasien' => JumlahPasienHarian::whereDate('tanggal', $today)->count(),
            'tindakan' => Tindakan::whereDate('tanggal_tindakan', $today)->count(),
        ];
        
        // Expected targets (can be made configurable)
        $targets = [
            'pendapatan' => 5,
            'pengeluaran' => 3,
            'pasien' => 10,
            'tindakan' => 20,
        ];
        
        return [
            'completed' => $completed,
            'targets' => $targets,
            'total_entries' => array_sum($completed),
        ];
    }
    
    public function getRecentActivities(): array
    {
        $activities = collect();
        
        // Recent Pendapatan entries
        $recentPendapatan = PendapatanHarian::with('user')
            ->latest('tanggal_input')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pendapatan',
                    'description' => 'Input Pendapatan Harian',
                    'amount' => $item->nominal,
                    'date' => $item->tanggal_input,
                    'created_by' => $item->user->name ?? 'Unknown',
                ];
            });
            
        // Recent Pengeluaran entries
        $recentPengeluaran = PengeluaranHarian::with('user')
            ->latest('tanggal_input')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'pengeluaran',
                    'description' => 'Input Pengeluaran Harian',
                    'amount' => $item->nominal,
                    'date' => $item->tanggal_input,
                    'created_by' => $item->user->name ?? 'Unknown',
                ];
            });
        
        // Recent Tindakan entries
        $recentTindakan = Tindakan::with(['inputBy', 'jenisTindakan'])
            ->latest('tanggal_tindakan')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'tindakan',
                    'description' => $item->jenisTindakan->nama ?? 'Tindakan',
                    'amount' => 0,
                    'date' => $item->tanggal_tindakan,
                    'created_by' => $item->inputBy->name ?? 'Unknown',
                ];
            });
        
        return $activities->merge($recentPendapatan)
            ->merge($recentPengeluaran)
            ->merge($recentTindakan)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }
    
    public function getMonthlyTrends(): array
    {
        $months = [];
        $pendapatan = [];
        $pengeluaran = [];
        $pasien = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthlyPendapatan = PendapatanHarian::whereMonth('tanggal_input', $date->month)
                ->whereYear('tanggal_input', $date->year)
                ->sum('nominal');
                
            $monthlyPengeluaran = PengeluaranHarian::whereMonth('tanggal_input', $date->month)
                ->whereYear('tanggal_input', $date->year)
                ->sum('nominal');
                
            $monthlyPasien = JumlahPasienHarian::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
            $pendapatan[] = $monthlyPendapatan;
            $pengeluaran[] = $monthlyPengeluaran;
            $pasien[] = $monthlyPasien;
        }
        
        return [
            'months' => $months,
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaran,
            'pasien' => $pasien,
        ];
    }
    
    public function getTopPerformers(): array
    {
        // Top staff by data entry
        $topStaff = User::whereHas('roles', function($q) {
                $q->where('name', 'petugas');
            })
            ->withCount([
                'pendapatanHarian as pendapatan_count',
                'pengeluaranHarian as pengeluaran_count',
                'tindakanInput as tindakan_count'
            ])
            ->get()
            ->map(function ($user) {
                $totalEntries = $user->pendapatan_count + $user->pengeluaran_count + $user->tindakan_count;
                return [
                    'name' => $user->name,
                    'total' => $totalEntries,
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->toArray();
            
        // Top procedures
        $topProcedures = Tindakan::select('jenis_tindakan_id', DB::raw('COUNT(*) as total_count'))
            ->with('jenisTindakan')
            ->groupBy('jenis_tindakan_id')
            ->orderBy('total_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->jenisTindakan->nama ?? 'Unknown',
                    'total' => $item->total_count,
                ];
            })
            ->toArray();
        
        return [
            'staff' => $topStaff,
            'procedures' => $topProcedures,
        ];
    }
    
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
}