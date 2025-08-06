<?php

namespace App\Filament\Resources\DokterPresensiResource\Widgets;

use App\Models\DokterPresensi;
use App\Models\Dokter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DokterAttendanceOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Today's statistics
        $todayTotal = DokterPresensi::whereDate('tanggal', $today)->count();
        $todayActive = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang')
            ->count();
        $todayCompleted = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNotNull('jam_pulang')
            ->count();

        // Weekly statistics
        $weeklyTotal = DokterPresensi::whereBetween('tanggal', [$thisWeek, $today])->count();
        $weeklyAverage = $weeklyTotal > 0 ? round($weeklyTotal / 7, 1) : 0;

        // Monthly statistics
        $monthlyTotal = DokterPresensi::whereBetween('tanggal', [$thisMonth, $today])->count();
        $monthlyAverage = $monthlyTotal > 0 ? round($monthlyTotal / $today->day, 1) : 0;

        // Compliance rate (completed vs total for this month)
        $monthlyCompleted = DokterPresensi::whereBetween('tanggal', [$thisMonth, $today])
            ->whereNotNull('jam_masuk')
            ->whereNotNull('jam_pulang')
            ->count();
        $complianceRate = $monthlyTotal > 0 ? round(($monthlyCompleted / $monthlyTotal) * 100, 1) : 0;

        // Total registered doctors
        $totalDoctors = Dokter::count();

        // Average work hours today
        $todayWorkHours = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNotNull('jam_pulang')
            ->get()
            ->map(function ($record) {
                if (!$record->jam_masuk || !$record->jam_pulang) return 0;
                
                $checkIn = Carbon::createFromFormat('H:i:s', $record->jam_masuk);
                $checkOut = Carbon::createFromFormat('H:i:s', $record->jam_pulang);
                return $checkOut->diffInMinutes($checkIn);
            })
            ->average();
        
        $avgHoursToday = $todayWorkHours ? round($todayWorkHours / 60, 1) : 0;

        return [
            Stat::make('Doctors Working Today', $todayActive)
                ->description("{$todayCompleted} completed, {$todayTotal} total")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($todayActive > 0 ? 'success' : 'warning')
                ->chart(array_fill(0, 7, $todayActive)), // Simple chart

            Stat::make('Weekly Average', $weeklyAverage)
                ->description("{$weeklyTotal} total this week")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart($this->getWeeklyChart()),

            Stat::make('Monthly Compliance', $complianceRate . '%')
                ->description("{$monthlyCompleted} of {$monthlyTotal} completed")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($complianceRate >= 80 ? 'success' : ($complianceRate >= 60 ? 'warning' : 'danger'))
                ->chart(array_fill(0, 7, $complianceRate)),

            Stat::make('Average Work Hours Today', $avgHoursToday . 'h')
                ->description('Based on completed shifts')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgHoursToday >= 8 ? 'success' : 'warning')
                ->chart(array_fill(0, 7, $avgHoursToday * 10)), // Scale for chart

            Stat::make('Total Registered Doctors', $totalDoctors)
                ->description('Active doctors in system')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('gray')
                ->chart(array_fill(0, 7, $totalDoctors)),

            Stat::make('Today\'s Performance', $this->getTodayPerformance())
                ->description($this->getTodayPerformanceDescription())
                ->descriptionIcon('heroicon-m-trophy')
                ->color($this->getTodayPerformanceColor())
                ->chart($this->getPerformanceChart()),
        ];
    }

    private function getWeeklyChart(): array
    {
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = DokterPresensi::whereDate('tanggal', $date)->count();
            $chart[] = $count;
        }
        return $chart;
    }

    private function getTodayPerformance(): string
    {
        $today = Carbon::today();
        $onTime = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereTime('jam_masuk', '<=', '08:00:00') // Assuming 8 AM is on time
            ->count();
        
        $total = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->count();
            
        if ($total === 0) return '0%';
        
        $percentage = round(($onTime / $total) * 100, 1);
        return $percentage . '%';
    }

    private function getTodayPerformanceDescription(): string
    {
        $today = Carbon::today();
        $onTime = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereTime('jam_masuk', '<=', '08:00:00')
            ->count();
        
        $total = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->count();
            
        return "{$onTime} of {$total} on time";
    }

    private function getTodayPerformanceColor(): string
    {
        $today = Carbon::today();
        $onTime = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereTime('jam_masuk', '<=', '08:00:00')
            ->count();
        
        $total = DokterPresensi::whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->count();
            
        if ($total === 0) return 'gray';
        
        $percentage = ($onTime / $total) * 100;
        
        if ($percentage >= 90) return 'success';
        if ($percentage >= 70) return 'warning';
        return 'danger';
    }

    private function getPerformanceChart(): array
    {
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $onTime = DokterPresensi::whereDate('tanggal', $date)
                ->whereNotNull('jam_masuk')
                ->whereTime('jam_masuk', '<=', '08:00:00')
                ->count();
            
            $total = DokterPresensi::whereDate('tanggal', $date)
                ->whereNotNull('jam_masuk')
                ->count();
                
            $percentage = $total > 0 ? ($onTime / $total) * 100 : 0;
            $chart[] = round($percentage, 1);
        }
        return $chart;
    }

    protected function getColumns(): int
    {
        return 3; // Display 3 columns on desktop
    }
}