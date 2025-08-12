<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class PetugasSimpleDashboardWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.petugas-simple-dashboard-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
        ];
    }
    
    protected function getStats(): array
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            
            return [
                'pasien_today' => Pasien::whereDate('created_at', $today)->count() ?: 0,
                'pasien_month' => Pasien::whereBetween('created_at', [$thisMonth, Carbon::now()])->count() ?: 0,
                'tindakan_today' => Tindakan::whereDate('tanggal_tindakan', $today)->count() ?: 0,
                'tindakan_month' => Tindakan::whereBetween('tanggal_tindakan', [$thisMonth, Carbon::now()])->count() ?: 0,
                'pendapatan_today' => Pendapatan::whereDate('tanggal', $today)
                    ->where('status_validasi', 'disetujui')->sum('nominal') ?: 0,
                'pendapatan_month' => Pendapatan::whereBetween('tanggal', [$thisMonth, Carbon::now()])
                    ->where('status_validasi', 'disetujui')->sum('nominal') ?: 0,
            ];
        } catch (\Exception $e) {
            return [
                'pasien_today' => 0,
                'pasien_month' => 0,
                'tindakan_today' => 0,
                'tindakan_month' => 0,
                'pendapatan_today' => 0,
                'pendapatan_month' => 0,
            ];
        }
    }
}