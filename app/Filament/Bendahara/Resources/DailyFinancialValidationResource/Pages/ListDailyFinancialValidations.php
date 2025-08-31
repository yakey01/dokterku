<?php

namespace App\Filament\Bendahara\Resources\DailyFinancialValidationResource\Pages;

use App\Filament\Bendahara\Resources\DailyFinancialValidationResource;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ValidationWorkflowService;
use Filament\Notifications\Notification;

class ListDailyFinancialValidations extends ListRecords
{
    protected static string $resource = DailyFinancialValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_sync')
                ->label('🔄 Sync All to Main Tables')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('🔄 Sync All Approved Data to Main Tables')
                ->modalDescription('This will sync all approved PendapatanHarian AND PengeluaranHarian records to their respective main tables for dashboard display.')
                ->modalSubmitActionLabel('Sync All Now')
                ->action(function () {
                    $validationService = app(ValidationWorkflowService::class);
                    
                    // Sync both pendapatan and pengeluaran
                    $pendapatanResults = $validationService->bulkSyncPendapatanHarian();
                    $pengeluaranResults = $validationService->bulkSyncPengeluaranHarian();
                    
                    $totalSynced = $pendapatanResults['successful_syncs'] + $pengeluaranResults['successful_syncs'];
                    $totalFound = $pendapatanResults['total_found'] + $pengeluaranResults['total_found'];
                    
                    if ($totalSynced > 0) {
                        $message = "Synced {$totalSynced} records to main tables:\n";
                        $message .= "• Pendapatan: {$pendapatanResults['successful_syncs']} records\n";
                        $message .= "• Pengeluaran: {$pengeluaranResults['successful_syncs']} records\n";
                        $message .= "Dashboard will now show updated totals.";
                        
                        Notification::make()
                            ->title('✅ Complete Sync Successful')
                            ->body($message)
                            ->success()
                            ->persistent()
                            ->send();
                    } elseif ($totalFound === 0) {
                        Notification::make()
                            ->title('ℹ️ No Records to Sync')
                            ->body('All approved daily transactions have already been synced to main tables.')
                            ->info()
                            ->send();
                    } else {
                        $message = "Found {$totalFound} records:\n";
                        $message .= "• Pendapatan: {$pendapatanResults['successful_syncs']}/{$pendapatanResults['total_found']} synced\n";
                        $message .= "• Pengeluaran: {$pengeluaranResults['successful_syncs']}/{$pengeluaranResults['total_found']} synced";
                        
                        Notification::make()
                            ->title('⚠️ Partial Sync Results')
                            ->body($message)
                            ->warning()
                            ->send();
                    }
                    
                    // Clear dashboard cache so updated values show immediately
                    \Illuminate\Support\Facades\Cache::forget('bendahara_financial_summary');
                    \Illuminate\Support\Facades\Cache::forget('bendahara_validation_stats');
                }),
                
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->redirect(static::getResource()::getUrl('index'))),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pendapatan' => Tab::make('Pendapatan Harian')
                ->badge(PendapatanHarian::where('status_validasi', 'pending')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    // Store the active tab in session
                    session(['daily_financial_validation_tab' => 'pendapatan']);
                    
                    // Ensure we're querying the right model
                    if ($query->getModel() instanceof PengeluaranHarian) {
                        // If the query is for PengeluaranHarian but we want PendapatanHarian,
                        // we need to return a new query for the correct model
                        return PendapatanHarian::query();
                    }
                    
                    return $query;
                }),
                
            'pengeluaran' => Tab::make('Pengeluaran Harian')
                ->badge(PengeluaranHarian::where('status_validasi', 'pending')->count())
                ->modifyQueryUsing(function (Builder $query) {
                    // Store the active tab in session
                    session(['daily_financial_validation_tab' => 'pengeluaran']);
                    
                    // Ensure we're querying the right model
                    if ($query->getModel() instanceof PendapatanHarian) {
                        // If the query is for PendapatanHarian but we want PengeluaranHarian,
                        // we need to return a new query for the correct model
                        return PengeluaranHarian::query();
                    }
                    
                    return $query;
                }),
                
            'all_pending' => Tab::make('Semua Pending')
                ->badge(function () {
                    $activeTab = session('daily_financial_validation_tab', 'pendapatan');
                    if ($activeTab === 'pengeluaran') {
                        return PengeluaranHarian::where('status_validasi', 'pending')->count();
                    }
                    return PendapatanHarian::where('status_validasi', 'pending')->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),
                
            'approved' => Tab::make('Disetujui')
                ->badge(function () {
                    $activeTab = session('daily_financial_validation_tab', 'pendapatan');
                    if ($activeTab === 'pengeluaran') {
                        return PengeluaranHarian::where('status_validasi', 'approved')->count();
                    }
                    return PendapatanHarian::where('status_validasi', 'approved')->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'approved')),
                
            'rejected' => Tab::make('Ditolak')
                ->badge(function () {
                    $activeTab = session('daily_financial_validation_tab', 'pendapatan');
                    if ($activeTab === 'pengeluaran') {
                        return PengeluaranHarian::where('status_validasi', 'rejected')->count();
                    }
                    return PendapatanHarian::where('status_validasi', 'rejected')->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'rejected')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add widgets here for summary statistics if needed
        ];
    }

    public function getTitle(): string
    {
        return 'Validasi Transaksi Harian';
    }

    public function getHeading(): string
    {
        return 'Pusat Validasi Transaksi Harian';
    }

    public function getSubheading(): ?string
    {
        $pendingCount = PendapatanHarian::where('status_validasi', 'pending')->count() +
                       PengeluaranHarian::where('status_validasi', 'pending')->count();
        
        if ($pendingCount > 0) {
            return "Terdapat {$pendingCount} transaksi yang menunggu validasi";
        }
        
        return 'Semua transaksi telah divalidasi';
    }
}