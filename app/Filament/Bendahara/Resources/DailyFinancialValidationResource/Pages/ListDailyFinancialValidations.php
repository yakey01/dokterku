<?php

namespace App\Filament\Bendahara\Resources\DailyFinancialValidationResource\Pages;

use App\Filament\Bendahara\Resources\DailyFinancialValidationResource;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Filament\Actions;
use Filament\Tables;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\ValidationWorkflowService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ListDailyFinancialValidations extends ListRecords
{
    protected static string $resource = DailyFinancialValidationResource::class;

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                // Quick Validation Actions (Works for both model types)
                Tables\Actions\Action::make('quick_approve')
                    ->label('⚡ Quick Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('⚡ Quick Approve')
                    ->modalDescription('Approve this daily transaction without additional comments?')
                    ->modalSubmitActionLabel('Approve')
                    ->action(function ($record) {
                        $this->handleCombinedValidation($record, 'approved');
                    }),

                Tables\Actions\Action::make('quick_reject')
                    ->label('⚡ Quick Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Berikan alasan penolakan...')
                    ])
                    ->action(function ($record, array $data) {
                        $this->handleCombinedValidation($record, 'rejected', $data['rejection_reason']);
                    }),

                Tables\Actions\Action::make('approve_with_comment')
                    ->label('✅ Approve with Comment')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('approval_comment')
                            ->label('Catatan Persetujuan')
                            ->placeholder('Tambahkan catatan persetujuan...')
                    ])
                    ->action(function ($record, array $data) {
                        $this->handleCombinedValidation($record, 'approved', $data['approval_comment'] ?? null);
                    }),

                // View Action
                Tables\Actions\ViewAction::make()
                    ->label('👁️ View Details')
                    ->modalWidth('4xl'),
            ])
            ->label('⚙️ Actions')
            ->icon('heroicon-o-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

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
                ->badge(function () {
                    // Show total count for all statuses in this model
                    return $this->getFilteredBadgeCount(PendapatanHarian::class, 'all');
                })
                ->modifyQueryUsing(function (Builder $query) {
                    // Store the active tab in session
                    session(['daily_financial_validation_tab' => 'pendapatan']);
                    
                    // Ensure we're querying the right model
                    if ($query->getModel() instanceof PengeluaranHarian) {
                        $query = PendapatanHarian::query();
                    }
                    
                    // Check if monthly archive filter is active and apply it
                    $tableFilters = request()->input('tableFilters', []);
                    
                    // Apply archive_month filter if present
                    if (isset($tableFilters['archive_month']['value']) && 
                        !empty($tableFilters['archive_month']['value']) && 
                        $tableFilters['archive_month']['value'] !== 'all') {
                        
                        $monthValue = $tableFilters['archive_month']['value'];
                        if ($monthValue === 'current') {
                            $monthValue = now()->format('Y-m');
                        }
                        
                        [$year, $month] = explode('-', $monthValue);
                        $query->whereYear('tanggal_input', $year)
                              ->whereMonth('tanggal_input', $month);
                    }
                    
                    return $query;
                }),
                
            'pengeluaran' => Tab::make('Pengeluaran Harian')
                ->badge(function () {
                    // Show total count for all statuses in this model
                    return $this->getFilteredBadgeCount(PengeluaranHarian::class, 'all');
                })
                ->modifyQueryUsing(function (Builder $query) {
                    // Store the active tab in session
                    session(['daily_financial_validation_tab' => 'pengeluaran']);
                    
                    // Ensure we're querying the right model
                    if ($query->getModel() instanceof PendapatanHarian) {
                        $query = PengeluaranHarian::query();
                    }
                    
                    // Check if monthly archive filter is active and apply it
                    $tableFilters = request()->input('tableFilters', []);
                    
                    // Apply archive_month filter if present
                    if (isset($tableFilters['archive_month']['value']) && 
                        !empty($tableFilters['archive_month']['value']) && 
                        $tableFilters['archive_month']['value'] !== 'all') {
                        
                        $monthValue = $tableFilters['archive_month']['value'];
                        if ($monthValue === 'current') {
                            $monthValue = now()->format('Y-m');
                        }
                        
                        [$year, $month] = explode('-', $monthValue);
                        $query->whereYear('tanggal_input', $year)
                              ->whereMonth('tanggal_input', $month);
                    }
                    
                    return $query;
                }),
                
            'all_pending' => Tab::make('Semua Pending')
                ->badge(function () {
                    // FIXED: Combined count from both models
                    return $this->getCombinedFilteredBadgeCount('pending');
                })
                ->modifyQueryUsing(function (Builder $query) {
                    // Mark that this tab needs combined data display
                    session(['daily_financial_validation_show_combined' => 'pending']);
                    
                    // Apply status filter but preserve any existing filters (including monthly archive)
                    $query->where('status_validasi', 'pending');
                    
                    // Check if monthly archive filter is active and apply it
                    $tableFilters = request()->input('tableFilters', []);
                    
                    // Apply archive_month filter if present
                    if (isset($tableFilters['archive_month']['value']) && 
                        !empty($tableFilters['archive_month']['value']) && 
                        $tableFilters['archive_month']['value'] !== 'all') {
                        
                        $monthValue = $tableFilters['archive_month']['value'];
                        if ($monthValue === 'current') {
                            $monthValue = now()->format('Y-m');
                        }
                        
                        [$year, $month] = explode('-', $monthValue);
                        $query->whereYear('tanggal_input', $year)
                              ->whereMonth('tanggal_input', $month);
                    }
                    
                    return $query;
                }),
                
            'approved' => Tab::make('Disetujui')
                ->badge(function () {
                    // FIXED: Combined count from both models (PendapatanHarian + PengeluaranHarian)
                    return $this->getCombinedFilteredBadgeCount('approved');
                })
                ->modifyQueryUsing(function (Builder $query) {
                    // Mark that this tab needs combined data display
                    session(['daily_financial_validation_show_combined' => 'approved']);
                    
                    // Apply status filter but preserve any existing filters (including monthly archive)
                    $query->where('status_validasi', 'approved');
                    
                    // Check if monthly archive filter is active and apply it
                    $tableFilters = request()->input('tableFilters', []);
                    
                    // Apply archive_month filter if present
                    if (isset($tableFilters['archive_month']['value']) && 
                        !empty($tableFilters['archive_month']['value']) && 
                        $tableFilters['archive_month']['value'] !== 'all') {
                        
                        $monthValue = $tableFilters['archive_month']['value'];
                        if ($monthValue === 'current') {
                            $monthValue = now()->format('Y-m');
                        }
                        
                        [$year, $month] = explode('-', $monthValue);
                        $query->whereYear('tanggal_input', $year)
                              ->whereMonth('tanggal_input', $month);
                    }
                    
                    return $query;
                }),
                
            'rejected' => Tab::make('Ditolak')
                ->badge(function () {
                    // FIXED: Combined count from both models
                    return $this->getCombinedFilteredBadgeCount('rejected');
                })
                ->modifyQueryUsing(function (Builder $query) {
                    // Mark that this tab needs combined data display
                    session(['daily_financial_validation_show_combined' => 'rejected']);
                    
                    // Apply status filter but preserve any existing filters (including monthly archive)
                    $query->where('status_validasi', 'rejected');
                    
                    // Check if monthly archive filter is active and apply it
                    $tableFilters = request()->input('tableFilters', []);
                    
                    // Apply archive_month filter if present
                    if (isset($tableFilters['archive_month']['value']) && 
                        !empty($tableFilters['archive_month']['value']) && 
                        $tableFilters['archive_month']['value'] !== 'all') {
                        
                        $monthValue = $tableFilters['archive_month']['value'];
                        if ($monthValue === 'current') {
                            $monthValue = now()->format('Y-m');
                        }
                        
                        [$year, $month] = explode('-', $monthValue);
                        $query->whereYear('tanggal_input', $year)
                              ->whereMonth('tanggal_input', $month);
                    }
                    
                    return $query;
                }),
        ];
    }

    /**
     * Calculate badge count respecting current table filters
     * FIXED: Default to current month, improved filter detection and synchronization with table state
     */
    protected function getFilteredBadgeCount(string $modelClass, string $status): int
    {
        $query = $modelClass::query();
        
        // Apply status filter only if not 'all'
        if ($status !== 'all') {
            $query->where('status_validasi', $status);
        }
        
        // DEFAULT: Apply current month filter unless explicitly overridden
        $tableFilters = request()->input('tableFilters', []);
        $monthFilter = $tableFilters['archive_month']['value'] ?? 'current';
        
        // If no explicit month filter or set to current, default to current month
        if ($monthFilter === 'current' || empty($monthFilter) || $monthFilter === now()->format('Y-m')) {
            $query->whereYear('tanggal_input', now()->year)
                  ->whereMonth('tanggal_input', now()->month);
        } elseif ($monthFilter !== 'all') {
            // Apply specific month filter
            [$year, $month] = explode('-', $monthFilter);
            $query->whereYear('tanggal_input', $year)
                  ->whereMonth('tanggal_input', $month);
        }
        
        // CRITICAL FIX: Get filters using the same logic as table filtering
        try {
            // Try to get table filters if available (when called from filter context)
            $tableFilters = $this->getTableFilters();
        } catch (\Exception $e) {
            // Fallback when table filters aren't initialized yet
            $tableFilters = [];
        }
        
        // Also check URL parameters for filter state
        $urlFilters = request()->input('tableFilters', []);
        
        // Merge and prioritize filter sources
        $allFilters = array_merge($urlFilters, $tableFilters);
        
        // Apply high_value filter with robust boolean/string handling
        $highValueFilter = $allFilters['high_value'] ?? [];
        $highValueActive = $highValueFilter['isActive'] ?? false;
        
        // Handle both string 'true'/'false' and boolean true/false
        $isHighValueActive = ($highValueActive === true || $highValueActive === 'true' || $highValueActive === 1);
        
        if ($isHighValueActive) {
            $query->where('nominal', '>', 500000);
        }
        
        // Apply very_high_value filter with robust boolean/string handling
        $veryHighValueFilter = $allFilters['very_high_value'] ?? [];
        $veryHighValueActive = $veryHighValueFilter['isActive'] ?? false;
        
        $isVeryHighValueActive = ($veryHighValueActive === true || $veryHighValueActive === 'true' || $veryHighValueActive === 1);
        
        if ($isVeryHighValueActive) {
            $query->where('nominal', '>', 1000000);
        }
        
        // Apply date range filters from all sources
        $dateFrom = $allFilters['custom_date_range']['from_date'] ?? 
                   $allFilters['created_at']['created_from'] ?? null;
        $dateUntil = $allFilters['custom_date_range']['to_date'] ?? 
                    $allFilters['created_at']['created_until'] ?? null;
        
        if ($dateFrom) {
            $query->whereDate('tanggal_input', '>=', $dateFrom);
        }
        
        if ($dateUntil) {
            $query->whereDate('tanggal_input', '<=', $dateUntil);
        }
        
        // Apply other filters that might be active
        if (isset($allFilters['status_validasi']['value']) && $allFilters['status_validasi']['value'] !== '') {
            // Status filter is handled by tab logic, but ensure consistency
        }
        
        if (isset($allFilters['shift']['value']) && $allFilters['shift']['value'] !== '') {
            $query->where('shift', $allFilters['shift']['value']);
        }
        
        $finalCount = $query->count();
        
        // Enhanced debug logging
        \Log::debug('Badge count calculation', [
            'model' => $modelClass,
            'status' => $status,
            'high_value_active' => $isHighValueActive,
            'very_high_value_active' => $isVeryHighValueActive,
            'merged_filters' => $allFilters,
            'table_filters_available' => !empty($tableFilters),
            'url_filters' => $urlFilters,
            'final_count' => $finalCount,
            'sql' => $query->toSql()
        ]);
        
        return $finalCount;
    }

    /**
     * Calculate combined badge count from both PendapatanHarian and PengeluaranHarian
     * FIXED: For status tabs that should show combined data (Disetujui, Pending, Ditolak)
     */
    protected function getCombinedFilteredBadgeCount(string $status): int
    {
        try {
            // Get count from PendapatanHarian with filters applied
            $pendapatanCount = $this->getFilteredBadgeCount(PendapatanHarian::class, $status);
            
            // Get count from PengeluaranHarian with filters applied
            $pengeluaranCount = $this->getFilteredBadgeCount(PengeluaranHarian::class, $status);
            
            // Combined total
            $combinedTotal = $pendapatanCount + $pengeluaranCount;
            
            \Log::debug('Combined badge calculation completed', [
                'status' => $status,
                'pendapatan_count' => $pendapatanCount,
                'pengeluaran_count' => $pengeluaranCount,
                'combined_total' => $combinedTotal,
                'expected_vs_actual' => [
                    'user_expected' => 6, // User mentioned should be 6
                    'calculated' => $combinedTotal
                ]
            ]);
            
            return $combinedTotal;
            
        } catch (\Exception $e) {
            \Log::error('Combined badge calculation failed', [
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to simple combined count without filters
            $pendapatanCount = PendapatanHarian::where('status_validasi', $status)->count();
            $pengeluaranCount = PengeluaranHarian::where('status_validasi', $status)->count();
            return $pendapatanCount + $pengeluaranCount;
        }
    }

    /**
     * Override table query to handle combined data display for status tabs
     */
    protected function getTableQuery(): Builder
    {
        // Check if we need to show combined data from both models
        $showCombined = session('daily_financial_validation_show_combined', false);
        
        if ($showCombined) {
            // Clear the session flag
            session()->forget('daily_financial_validation_show_combined');
            
            // Create union query to combine both models
            $pendapatanQuery = PendapatanHarian::query()
                ->select([
                    'id',
                    'tanggal_input as tanggal',
                    'shift',
                    'jenis_transaksi',
                    'nominal',
                    'deskripsi',
                    'status_validasi',
                    'input_by',
                    'validator_id',
                    'tgl_validasi',
                    'created_at',
                    'updated_at',
                    DB::raw("'pendapatan' as tipe_transaksi")
                ])
                ->where('status_validasi', 'approved');

            $pengeluaranQuery = PengeluaranHarian::query()
                ->select([
                    'id',
                    'tanggal_input as tanggal', 
                    'shift',
                    'jenis_transaksi',
                    'nominal',
                    'deskripsi',
                    'status_validasi',
                    'input_by',
                    'validator_id',
                    'tgl_validasi',
                    'created_at',
                    'updated_at',
                    DB::raw("'pengeluaran' as tipe_transaksi")
                ])
                ->where('status_validasi', 'approved');

            // Apply table filters to both queries
            $tableFilters = $this->getTableFilters();
            
            // Apply high_value filter
            if (isset($tableFilters['high_value']['isActive']) && $tableFilters['high_value']['isActive']) {
                $pendapatanQuery->where('nominal', '>', 500000);
                $pengeluaranQuery->where('nominal', '>', 500000);
            }
            
            // Apply very_high_value filter
            if (isset($tableFilters['very_high_value']['isActive']) && $tableFilters['very_high_value']['isActive']) {
                $pendapatanQuery->where('nominal', '>', 1000000);
                $pengeluaranQuery->where('nominal', '>', 1000000);
            }

            // Union the queries (this won't work directly with Filament, need different approach)
            // TODO: Implement proper combined data display
            \Log::info('Combined data display requested for approved status');
        }
        
        // Fallback to parent query
        return parent::getTableQuery();
    }

    /**
     * Override table records to provide combined data for status tabs
     */
    public function getTableRecords(): Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        $showCombinedStatus = session('daily_financial_validation_show_combined');
        
        if ($showCombinedStatus && in_array($showCombinedStatus, ['approved', 'rejected', 'pending'])) {
            // Clear the session flag
            session()->forget('daily_financial_validation_show_combined');
            
            // Get records from both models with the specified status AND APPLY FILTERS
            $pendapatanQuery = PendapatanHarian::where('status_validasi', $showCombinedStatus);
            $pengeluaranQuery = PengeluaranHarian::where('status_validasi', $showCombinedStatus);
            
            // CRITICAL FIX: Apply monthly archive filter with current month as default
            $tableFilters = request()->input('tableFilters', []);
            $monthFilter = $tableFilters['archive_month']['value'] ?? 'current';
            
            // Default to current month unless explicitly set to 'all'
            if ($monthFilter === 'current' || empty($monthFilter) || $monthFilter === now()->format('Y-m')) {
                // Apply current month filter by default
                $pendapatanQuery->whereYear('tanggal_input', now()->year)
                              ->whereMonth('tanggal_input', now()->month);
                $pengeluaranQuery->whereYear('tanggal_input', now()->year)
                               ->whereMonth('tanggal_input', now()->month);
            } elseif ($monthFilter !== 'all') {
                // Apply specific month filter
                [$year, $month] = explode('-', $monthFilter);
                $pendapatanQuery->whereYear('tanggal_input', $year)
                              ->whereMonth('tanggal_input', $month);
                $pengeluaranQuery->whereYear('tanggal_input', $year)
                               ->whereMonth('tanggal_input', $month);
            }
            
            // Apply other filters if present
            if (isset($tableFilters['shift']['value']) && !empty($tableFilters['shift']['value'])) {
                $pendapatanQuery->where('shift', $tableFilters['shift']['value']);
                $pengeluaranQuery->where('shift', $tableFilters['shift']['value']);
            }
            
            if (isset($tableFilters['high_value']['isActive']) && $tableFilters['high_value']['isActive']) {
                $pendapatanQuery->where('nominal', '>', 500000);
                $pengeluaranQuery->where('nominal', '>', 500000);
            }
            
            if (isset($tableFilters['very_high_value']['isActive']) && $tableFilters['very_high_value']['isActive']) {
                $pendapatanQuery->where('nominal', '>', 1000000);
                $pengeluaranQuery->where('nominal', '>', 1000000);
            }
            
            // Execute queries with filters applied
            $pendapatanRecords = $pendapatanQuery
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    $record->tipe_transaksi = 'pendapatan';
                    return $record;
                });

            $pengeluaranRecords = $pengeluaranQuery
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    $record->tipe_transaksi = 'pengeluaran';
                    return $record;
                });

            // DEBUG: Check individual collections before merge
            \Log::debug('Before merge debug', [
                'pendapatan_records' => $pendapatanRecords->pluck('id')->toArray(),
                'pengeluaran_records' => $pengeluaranRecords->pluck('id')->toArray(),
                'pendapatan_count' => $pendapatanRecords->count(),
                'pengeluaran_count' => $pengeluaranRecords->count()
            ]);

            // Combine collections using concat for better merging
            $combinedRecords = $pendapatanRecords->concat($pengeluaranRecords)->sortByDesc('created_at')->values();

            \Log::info('Combined records with FILTER APPLIED', [
                'status' => $showCombinedStatus,
                'filters_applied' => $tableFilters,
                'month_filter_active' => isset($tableFilters['archive_month']['value']),
                'month_filter_value' => $tableFilters['archive_month']['value'] ?? 'none',
                'pendapatan_count' => $pendapatanRecords->count(),
                'pengeluaran_count' => $pengeluaranRecords->count(),
                'combined_total' => $combinedRecords->count(),
                'pendapatan_dates' => $pendapatanRecords->pluck('tanggal_input')->toArray(),
                'pengeluaran_dates' => $pengeluaranRecords->pluck('tanggal_input')->toArray()
            ]);

            return $combinedRecords;
        }
        
        // Fallback to parent implementation
        return parent::getTableRecords();
    }

    /**
     * Force badge refresh after data changes (enhanced version)
     */
    public function updatedTableFilters(): void
    {
        // Force component refresh to recalculate badges
        $this->dispatch('$refresh');
        
        // Clear any cached badge calculations
        \Illuminate\Support\Facades\Cache::forget('daily_financial_validation_badges');
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
    
    /**
     * Ensure badges refresh when page loads or filters change
     */
    public function mount(): void
    {
        parent::mount();
        
        // Ensure session is properly initialized for tab context
        if (!session()->has('daily_financial_validation_tab')) {
            session(['daily_financial_validation_tab' => 'pendapatan']);
        }
    }
    
    /**
     * Handle validation for combined model types (PendapatanHarian & PengeluaranHarian)
     * This method works with both model types from the all_pending tab
     */
    protected function handleCombinedValidation($record, string $status, ?string $comment = null): void
    {
        try {
            // Handle both PendapatanHarian and PengeluaranHarian models
            $record->update([
                'status_validasi' => $status,
                'validasi_by' => Auth::id(),
                'validasi_at' => now(),
                'catatan_validasi' => $comment ?? ($status === 'approved' ? 'Quick approved' : 'Quick processed'),
            ]);

            // UNIFIED SYNC TO MAIN TABLES: Handle both model types
            $syncMessage = '';
            if ($status === 'approved') {
                $validationService = app(ValidationWorkflowService::class);
                
                if ($record instanceof PendapatanHarian) {
                    $syncResult = $validationService->syncPendapatanHarianToMainTable($record);
                    $syncMessage = $syncResult 
                        ? ' & synced to main pendapatan table' 
                        : ' (sync to pendapatan table failed - check logs)';
                        
                } elseif ($record instanceof PengeluaranHarian) {
                    $syncResult = $validationService->syncPengeluaranHarianToMainTable($record);
                    $syncMessage = $syncResult 
                        ? ' & synced to main pengeluaran table' 
                        : ' (sync to pengeluaran table failed - check logs)';
                }
            }

            $message = match($status) {
                'approved' => 'Transaksi harian berhasil disetujui' . $syncMessage,
                'rejected' => 'Transaksi harian berhasil ditolak',
                'revision' => 'Permintaan revisi berhasil dikirim',
                default => 'Transaksi harian berhasil diproses'
            };
            
            Notification::make()
                ->title('✅ Success')
                ->body($message)
                ->success()
                ->send();
                
            // Refresh the table to show updated data
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            \Log::error('Combined validation failed', [
                'record_type' => get_class($record),
                'record_id' => $record->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('❌ Error')
                ->body('Validation failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
}