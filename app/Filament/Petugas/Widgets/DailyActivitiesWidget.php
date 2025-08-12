<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\User;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyActivitiesWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.daily-activities-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    
    public function getDailyActivities(): array
    {
        try {
            $today = Carbon::today();
            
            // Today's activities
            $todayPatients = Pasien::whereDate('created_at', $today)->orderBy('created_at', 'desc')->limit(10)->get();
            $todayTindakan = Tindakan::whereDate('tanggal_tindakan', $today)->with('pasien')->orderBy('created_at', 'desc')->limit(10)->get();
            $todayPendapatan = Pendapatan::whereDate('tanggal', $today)->orderBy('created_at', 'desc')->limit(10)->get();
            
            // Activity summary
            $activitiesCount = [
                'new_patients' => $todayPatients->count(),
                'medical_actions' => $todayTindakan->count(),
                'revenue_entries' => $todayPendapatan->count(),
                'pending_verification' => Pasien::where('status', 'pending')->count(),
            ];
            
            // Recent activities timeline
            $timeline = collect();
            
            // Add patient registrations to timeline
            foreach ($todayPatients as $patient) {
                $timeline->push([
                    'type' => 'patient',
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'blue',
                    'title' => 'Pasien Baru',
                    'description' => "Registrasi: {$patient->nama}",
                    'time' => $patient->created_at->format('H:i'),
                    'timestamp' => $patient->created_at,
                    'status' => $patient->status,
                ]);
            }
            
            // Add medical actions to timeline
            foreach ($todayTindakan as $tindakan) {
                $timeline->push([
                    'type' => 'tindakan',
                    'icon' => 'heroicon-o-heart',
                    'color' => 'green',
                    'title' => 'Tindakan Medis',
                    'description' => "Pasien: " . ($tindakan->pasien->nama ?? 'Unknown'),
                    'time' => $tindakan->created_at->format('H:i'),
                    'timestamp' => $tindakan->created_at,
                    'status' => $tindakan->status ?? 'completed',
                ]);
            }
            
            // Add revenue entries to timeline
            foreach ($todayPendapatan as $pendapatan) {
                $timeline->push([
                    'type' => 'revenue',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'amber',
                    'title' => 'Pendapatan',
                    'description' => "Rp " . number_format($pendapatan->nominal, 0, ',', '.'),
                    'time' => $pendapatan->created_at->format('H:i'),
                    'timestamp' => $pendapatan->created_at,
                    'status' => $pendapatan->status_validasi ?? 'pending',
                ]);
            }
            
            // Sort timeline by timestamp (newest first) and take top 15
            $timeline = $timeline->sortByDesc('timestamp')->take(15)->values();
            
            return [
                'counts' => $activitiesCount,
                'timeline' => $timeline->toArray(),
                'quick_actions' => $this->getQuickActions(),
                'performance_metrics' => $this->getPerformanceMetrics(),
            ];
            
        } catch (\Exception $e) {
            return [
                'counts' => [
                    'new_patients' => 0,
                    'medical_actions' => 0,
                    'revenue_entries' => 0,
                    'pending_verification' => 0,
                ],
                'timeline' => [],
                'quick_actions' => [],
                'performance_metrics' => [
                    'efficiency_rate' => 0,
                    'completion_rate' => 0,
                    'average_processing_time' => 0,
                ],
            ];
        }
    }
    
    private function getQuickActions(): array
    {
        return [
            [
                'title' => '➕ Input Pasien Baru',
                'description' => 'Registrasi pasien baru',
                'url' => '/petugas/pasien/create',
                'icon' => 'heroicon-o-user-plus',
                'color' => 'primary',
                'count' => null,
            ],
            [
                'title' => '🩺 Catat Tindakan',
                'description' => 'Input tindakan medis',
                'url' => '/petugas/tindakan/create',
                'icon' => 'heroicon-o-heart',
                'color' => 'success',
                'count' => null,
            ],
            [
                'title' => '💰 Input Pendapatan',
                'description' => 'Catat pendapatan harian',
                'url' => '/petugas/pendapatan-harian/create',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
                'count' => null,
            ],
            [
                'title' => '📋 Validasi Data',
                'description' => 'Review data pending',
                'url' => '/petugas/validasi-pendapatan',
                'icon' => 'heroicon-o-clipboard-document-check',
                'color' => 'info',
                'count' => Pasien::where('status', 'pending')->count(),
            ],
        ];
    }
    
    private function getPerformanceMetrics(): array
    {
        try {
            $today = Carbon::today();
            $thisWeek = Carbon::now()->startOfWeek();
            
            // Calculate efficiency metrics
            $todayTindakan = Tindakan::whereDate('tanggal_tindakan', $today)->count();
            $todayPatients = Pasien::whereDate('created_at', $today)->count();
            
            $weeklyTindakan = Tindakan::whereBetween('tanggal_tindakan', [$thisWeek, Carbon::now()])->count();
            $weeklyPatients = Pasien::whereBetween('created_at', [$thisWeek, Carbon::now()])->count();
            
            $completedTindakan = Tindakan::whereDate('tanggal_tindakan', $today)
                ->where('status', 'completed')->count();
                
            $totalTindakanToday = Tindakan::whereDate('tanggal_tindakan', $today)->count();
            
            return [
                'daily_efficiency' => $todayPatients > 0 ? round(($todayTindakan / $todayPatients), 2) : 0,
                'weekly_efficiency' => $weeklyPatients > 0 ? round(($weeklyTindakan / $weeklyPatients), 2) : 0,
                'completion_rate' => $totalTindakanToday > 0 ? round(($completedTindakan / $totalTindakanToday) * 100, 1) : 0,
                'productivity_score' => min(100, ($todayTindakan + $todayPatients) * 5), // Simple scoring
            ];
            
        } catch (\Exception $e) {
            return [
                'daily_efficiency' => 0,
                'weekly_efficiency' => 0,
                'completion_rate' => 0,
                'productivity_score' => 0,
            ];
        }
    }
    
    public function getHourlyActivity(): array
    {
        try {
            $today = Carbon::today();
            $hours = [];
            $activities = [];
            
            // Generate hourly data for today (8 AM to 5 PM - typical clinic hours)
            for ($hour = 8; $hour <= 17; $hour++) {
                $hours[] = sprintf('%02d:00', $hour);
                
                $hourStart = $today->copy()->setHour($hour);
                $hourEnd = $hourStart->copy()->addHour();
                
                $hourlyActivity = Pasien::whereBetween('created_at', [$hourStart, $hourEnd])->count() +
                                Tindakan::whereBetween('created_at', [$hourStart, $hourEnd])->count() +
                                Pendapatan::whereBetween('created_at', [$hourStart, $hourEnd])->count();
                
                $activities[] = $hourlyActivity;
            }
            
            return [
                'hours' => $hours,
                'activities' => $activities,
            ];
            
        } catch (\Exception $e) {
            return [
                'hours' => [],
                'activities' => [],
            ];
        }
    }
}