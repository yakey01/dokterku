<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\JumlahPasienHarian;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JumlahPasienHistoryStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $myData = JumlahPasienHarian::where('input_by', auth()->id());
        $allData = JumlahPasienHarian::query();

        return [
            Stat::make('📊 Total History', $allData->count())
                ->description('Semua data dalam sistem')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('✅ Data Anda', $myData->count())
                ->description('Data yang Anda input')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('success'),

            Stat::make('📅 Hari Ini', $allData->whereDate('tanggal', today())->count())
                ->description('Data pasien hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('⏳ Menunggu Validasi', $allData->where('status_validasi', 'pending')->count())
                ->description('Data perlu divalidasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}