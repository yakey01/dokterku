<?php

namespace App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource;
use App\Constants\ValidationStatus;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListValidasiJumlahPasien extends ListRecords
{
    protected static string $resource = ValidasiJumlahPasienResource::class;

    protected static ?string $title = '👥 Validasi Jumlah Pasien';

    /**
     * Define tabs for filtering validation status
     * Replaces dropdown filter with intuitive tab interface
     */
    public function getTabs(): array
    {
        // Generate fresh counts every time to ensure accuracy
        // Cache was causing issues with authentication context
        $baseQuery = static::getResource()::getEloquentQuery();
        
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status_validasi', ['pending', 'menunggu'])->count(),
            'approved' => (clone $baseQuery)->whereIn('status_validasi', ['approved', 'disetujui'])->count(),
            'rejected' => (clone $baseQuery)->whereIn('status_validasi', ['rejected', 'ditolak'])->count(),
            'revision' => (clone $baseQuery)->where('status_validasi', ValidationStatus::REVISION)->count(),
            'cancelled' => (clone $baseQuery)->where('status_validasi', ValidationStatus::CANCELLED)->count(),
            'validated' => (clone $baseQuery)->whereIn('status_validasi', [
                'approved', 'disetujui',      // Approved statuses
                'rejected', 'ditolak',       // Rejected statuses  
                ValidationStatus::REVISION,  // Revision status
                ValidationStatus::CANCELLED  // Cancelled status
            ])->count(),
        ];

        return [
            'semua' => Tab::make('Semua')
                ->label('📋 Semua')
                ->badge($counts['total'])
                ->badgeColor('gray')
                ->icon('heroicon-o-document-text')
                ->extraAttributes([
                    'class' => 'validation-tab-all',
                    'title' => 'Tampilkan semua data validasi jumlah pasien'
                ]),

            'belum_validasi' => Tab::make('Belum Validasi')
                ->label('⏳ Belum Validasi')
                ->badge($counts['pending'])
                ->badgeColor($counts['pending'] > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('status_validasi', ['pending', 'menunggu'])
                        ->orderBy('created_at', 'asc') // Show oldest pending first
                )
                ->extraAttributes([
                    'class' => 'validation-tab-pending',
                    'title' => $counts['pending'] > 0 
                        ? 'Data yang menunggu validasi bendahara' 
                        : '✅ Semua data sudah divalidasi! Lihat tab "Semua" atau "Sudah Validasi"'
                ]),

            'sudah_validasi' => Tab::make('Sudah Validasi')
                ->label('✅ Sudah Validasi')
                ->badge($counts['validated'])
                ->badgeColor($counts['validated'] > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('status_validasi', [
                        'approved', 'disetujui',      // Approved statuses
                        'rejected', 'ditolak',       // Rejected statuses
                        ValidationStatus::REVISION,  // Revision status
                        ValidationStatus::CANCELLED  // Cancelled status
                    ])
                    ->orderBy('validasi_at', 'desc') // Show recently validated first
                )
                ->extraAttributes([
                    'class' => 'validation-tab-validated',
                    'title' => 'Data yang sudah divalidasi (disetujui, ditolak, revisi, atau dibatalkan)'
                ]),
        ];
    }

    /**
     * Get the default active tab
     * Smart default: show pending if any exist, otherwise show all
     */
    public function getDefaultActiveTab(): string | int | null
    {
        // Get pending count to determine smart default
        $pendingCount = static::getResource()::getModel()::where('status_validasi', ValidationStatus::PENDING)->count();
        
        // If there are pending records, show pending tab
        // Otherwise, show all records so user sees data
        return $pendingCount > 0 ? 'belum_validasi' : 'semua';
    }

    /**
     * Clear cache when records are modified to ensure real-time updates
     */
    protected function afterCreate(): void
    {
        $this->clearValidationStatusCache();
    }

    protected function afterSave(): void
    {
        $this->clearValidationStatusCache();
    }

    protected function afterDelete(): void
    {
        $this->clearValidationStatusCache();
    }

    /**
     * Clear cached validation status counts
     */
    private function clearValidationStatusCache(): void
    {
        Cache::forget('validation_status_counts_bendahara');
        // Also clear manager dashboard cache for real-time updates
        Cache::forget('manajer_today_stats_' . now()->format('Y-m-d'));
        Cache::forget('manajer.today_stats');
    }

    /**
     * Customize the page header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            // Keep existing header actions from the resource
            // This allows the auto-validation and summary actions to remain accessible
        ];
    }

    /**
     * Add custom page subtitle with validation summary
     */
    public function getSubheading(): ?string
    {
        $counts = Cache::get('validation_status_counts_bendahara', [
            'total' => 0, 
            'pending' => 0, 
            'validated' => 0,
            'approved' => 0,
            'rejected' => 0,
            'revision' => 0
        ]);
        
        $subtitle = "Total: {$counts['total']} data";
        
        if ($counts['pending'] > 0) {
            $subtitle .= " | ⏳ Menunggu: {$counts['pending']}";
        } else if ($counts['total'] > 0) {
            $subtitle .= " | ✅ Semua sudah divalidasi";
        }
        
        if ($counts['validated'] > 0) {
            // Add breakdown if there are multiple status types
            $breakdown = [];
            if ($counts['approved'] > 0) $breakdown[] = "Disetujui: {$counts['approved']}";
            if ($counts['rejected'] > 0) $breakdown[] = "Ditolak: {$counts['rejected']}";
            if ($counts['revision'] > 0) $breakdown[] = "Revisi: {$counts['revision']}";
            
            if (count($breakdown) > 0) {
                $subtitle .= " (" . implode(', ', $breakdown) . ")";
            }
        }
        
        // Clean interface following petugas pattern - remove verbose subheading
        return null;
    }

    /**
     * Handle real-time refresh when validation status changes
     */
    public function refreshValidationCounts(): void
    {
        $this->clearValidationStatusCache();
        $this->dispatch('refreshTabCounts');
    }

    /**
     * Add footer actions for quick access
     */
    protected function getFooterActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh_counts')
                ->label('🔄 Refresh Counts')
                ->color('gray')
                ->size('sm')
                ->action(fn () => $this->refreshValidationCounts())
                ->tooltip('Refresh tab counts manually'),
        ];
    }

    /**
     * Add custom view data for validation tabs enhancement
     */
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'validationTabsEnabled' => true,
            'realTimeUpdates' => true,
            'apiEndpoint' => route('bendahara.api.validation.counts'),
        ]);
    }
}