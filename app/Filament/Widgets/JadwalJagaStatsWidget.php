<?php

namespace App\Filament\Widgets;

use App\Models\JadwalJaga;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class JadwalJagaStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        try {
            $currentMonth = now()->format('Y-m');
            
            $currentMonthCount = cache()->remember('jadwal_current_month_count', 300, function () use ($currentMonth) {
                return JadwalJaga::whereRaw("strftime('%Y-%m', tanggal_jaga) = ?", [$currentMonth])->count();
            });
            
            $archiveCount = cache()->remember('jadwal_archive_count', 300, function () use ($currentMonth) {
                return JadwalJaga::whereRaw("strftime('%Y-%m', tanggal_jaga) < ?", [$currentMonth])->count();
            });
            
            $todayCount = cache()->remember('jadwal_today_count', 60, function () {
                return JadwalJaga::whereDate('tanggal_jaga', today())->count();
            });
            
            return [
                Stat::make('📅 Jadwal Bulan Ini', $currentMonthCount)
                    ->description('Jadwal jaga ' . now()->format('F Y'))
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('success'),
                    
                Stat::make('📚 Arsip Jadwal', $archiveCount)
                    ->description('Total jadwal bulan lalu')
                    ->descriptionIcon('heroicon-m-archive-box')
                    ->color('info'),
                    
                Stat::make('🗓️ Jadwal Hari Ini', $todayCount)
                    ->description('Yang bertugas hari ini')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
            ];
        } catch (\Exception $e) {
            \Log::error('JadwalJagaStatsWidget error: ' . $e->getMessage());
            
            // Return safe fallback stats
            return [
                Stat::make('📅 Jadwal Bulan Ini', 0)
                    ->description('Error loading data')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
                    
                Stat::make('📚 Arsip Jadwal', 0)
                    ->description('Error loading data')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
                    
                Stat::make('🗓️ Jadwal Hari Ini', 0)
                    ->description('Error loading data')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
}