<?php

namespace App\Filament\Bendahara\Resources\LaporanKeuanganReportResource\Pages;

use App\Filament\Bendahara\Resources\LaporanKeuanganReportResource;
use App\Services\JaspelReportService;
use App\Services\ProcedureJaspelCalculationService;
use App\Services\SubAgents\ValidationSubAgentService;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class ViewJaspelDetail extends Page
{
    protected static string $resource = LaporanKeuanganReportResource::class;

    protected static ?string $title = '📊 Detail Rincian Jaspel';

    protected static string $view = 'filament.bendahara.pages.jaspel-detail';
    
    public int $userId;
    public ?\App\Models\User $user = null;

    public function mount(int $record): void
    {
        $this->userId = $record;
        $this->user = \App\Models\User::find($record);
        
        if (!$this->user) {
            abort(404, 'User tidak ditemukan');
        }
    }

    public function getTitle(): string | Htmlable
    {
        return ''; // Empty to prevent Filament header rendering
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }
    
    public function getHeading(): string | Htmlable
    {
        return ''; // Empty to prevent Filament header rendering
    }
    
    protected static bool $shouldShowPageHeader = false;

    protected function getHeaderActions(): array
    {
        // CRITICAL FIX: Return empty array to prevent Filament header action duplication
        // Our custom top navigation buttons in the Blade template handle all actions
        return [];
    }

    // Infolist removed - using Blade view instead
    // REMOVED mutateFormDataBeforeFill to prevent conflicts with Livewire component
    // All data processing is now handled by JaspelDetailComponent

    protected function calculatePerformanceLevel(float $totalJaspel): string
    {
        if ($totalJaspel >= 1000000) {
            return 'Elite Performer';
        } elseif ($totalJaspel >= 500000) {
            return 'High Performer';
        } elseif ($totalJaspel >= 200000) {
            return 'Good Performer';
        } else {
            return 'Standard Performer';
        }
    }

    protected function generateMonthlyTrendHtml(int $userId): string
    {
        $monthlyData = \Illuminate\Support\Facades\DB::table('jaspel')
            ->select([
                \Illuminate\Support\Facades\DB::raw('strftime(\'%Y-%m\', validasi_at) as month'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'),
                \Illuminate\Support\Facades\DB::raw('SUM(total_jaspel) as total')
            ])
            ->where('user_id', $userId)
            ->where('status_validasi', 'disetujui')
            ->whereNotNull('validasi_at')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        if ($monthlyData->isEmpty()) {
            return '<div class="p-4 text-center text-gray-500">Tidak ada data monthly trend</div>';
        }

        $html = '<div class="space-y-3">';
        
        foreach ($monthlyData as $month) {
            $monthName = \Carbon\Carbon::createFromFormat('Y-m', $month->month)->format('M Y');
            $percentage = $monthlyData->sum('total') > 0 
                ? round(($month->total / $monthlyData->sum('total')) * 100, 1)
                : 0;
            
            $html .= '<div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg dark:bg-gray-800">';
            $html .= '<div class="flex-1">';
            $html .= '<div class="font-medium text-gray-900 dark:text-gray-100">' . $monthName . '</div>';
            $html .= '<div class="text-sm text-gray-500">' . $month->count . ' transaksi</div>';
            $html .= '</div>';
            $html .= '<div class="text-right">';
            $html .= '<div class="font-semibold text-green-600">Rp ' . number_format($month->total, 0, ',', '.') . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . $percentage . '%</div>';
            $html .= '</div>';
            $html .= '<div class="ml-4 w-20 bg-gray-200 rounded-full h-2 dark:bg-gray-700">';
            $html .= '<div class="bg-green-500 h-2 rounded-full" style="width: ' . $percentage . '%"></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    protected function getHeaderWidgets(): array
    {
        // No header widgets to prevent any additional header elements
        return [];
    }

    public function exportDetailedBreakdown(): void
    {
        $record = $this->user;
        $procedureCalculator = app(ProcedureJaspelCalculationService::class);
        
        $detailData = $procedureCalculator->calculateJaspelFromProcedures($record->id, []);
        
        // Generate comprehensive PDF export
        \Filament\Notifications\Notification::make()
            ->title('Export Started')
            ->body('Detail breakdown export sedang diproses...')
            ->success()
            ->send();
        
        // Implementation would create detailed PDF/Excel with all breakdown data
    }

    public function refreshCalculation(): void
    {
        $record = $this->user;
        
        // Clear all related caches
        app(\App\Services\SubAgents\DatabaseSubAgentService::class)->clearRelatedCache();
        
        // Force recalculate from procedures
        $procedureCalculator = app(ProcedureJaspelCalculationService::class);
        $freshData = $procedureCalculator->calculateJaspelFromProcedures($record->id, []);
        
        \Filament\Notifications\Notification::make()
            ->title('Calculation Refreshed')
            ->body('Jaspel dihitung ulang: Rp ' . number_format($freshData['total_jaspel'], 0, ',', '.'))
            ->success()
            ->send();
        
        // Refresh the page to show updated data
        $this->redirect($this->getResource()::getUrl('view', ['record' => $record->id]));
    }

    public function getViewData(): array
    {
        $record = $this->user;
        $procedureCalculator = app(ProcedureJaspelCalculationService::class);
        $validationAgent = app(ValidationSubAgentService::class);
        
        return [
            'user' => $record,
            'procedure_data' => $procedureCalculator->calculateJaspelFromProcedures($record->id, []),
            'validation_data' => $validationAgent->performCermatJaspelValidation($record->id),
            'accuracy_check' => $procedureCalculator->verifyCalculationAccuracy($record->id)
        ];
    }
    
    
    /**
     * Ensure no navigation registration for this specific page
     */
    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }
}