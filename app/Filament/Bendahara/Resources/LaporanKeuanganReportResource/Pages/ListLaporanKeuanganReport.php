<?php

namespace App\Filament\Bendahara\Resources\LaporanKeuanganReportResource\Pages;

use App\Filament\Bendahara\Resources\LaporanKeuanganReportResource;
use App\Filament\Bendahara\Resources\LaporanKeuanganReportResource\Actions\ExportJaspelAction;
use App\Services\JaspelReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class ListLaporanKeuanganReport extends ListRecords
{
    protected static string $resource = LaporanKeuanganReportResource::class;

    protected static ?string $title = 'Laporan Jaspel Tervalidasi';
    
    protected ?string $activeRole = null;
    protected bool $isMonthlyView = false;

    public function getTitle(): string | Htmlable
    {
        return 'Laporan Jaspel Tervalidasi';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Clear any cached data
                    \Illuminate\Support\Facades\Cache::forget('db_subagent_jaspel_role_agg_petugas_dokter_');
                    \Illuminate\Support\Facades\Cache::forget('db_subagent_jaspel_role_agg_petugas_semua_');
                    \Illuminate\Support\Facades\Cache::forget('db_subagent_jaspel_role_agg_petugas_petugas_');
                    
                    $this->resetTableSearch();
                    $this->resetTable();
                    $this->dispatch('$refresh');
                    
                    // Show success notification
                    \Filament\Notifications\Notification::make()
                        ->title('Data Refreshed')
                        ->body('Cache cleared and data refreshed successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->action(fn () => $this->exportToExcel())
                ->requiresConfirmation()
                ->modalHeading('Export ke Excel')
                ->modalDescription('Export semua data laporan jaspel ke format Excel?'),

            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-m-document-text')
                ->color('danger')
                ->action(fn () => $this->exportToPdf())
                ->requiresConfirmation()
                ->modalHeading('Export ke PDF')
                ->modalDescription('Export laporan jaspel ke format PDF?'),
        ];
    }

    public function getTabs(): array
    {
        // Role-based tabs removed as requested - no individual role breakdown
        return [];
    }

    protected function modifyQueryForRole(Builder $query, string $role): Builder
    {
        // Store the active role for use in getTableRecords
        $this->activeRole = $role;
        $this->isMonthlyView = false;
        return $query;
    }

    protected function modifyQueryForMonthlyView(Builder $query): Builder
    {
        // Set monthly view mode
        $this->activeRole = 'semua';
        $this->isMonthlyView = true;
        return $query;
    }

    public function getTableRecords(): Paginator
    {
        $jaspelService = app(JaspelReportService::class);
        
        // Simplified data retrieval - no role-based breakdown
        $filters = [];
        $tableFilters = $this->getTableFilters();
        
        if (!empty($tableFilters['date_range'])) {
            $filters['date_from'] = $tableFilters['date_range']['date_from'] ?? null;
            $filters['date_to'] = $tableFilters['date_range']['date_to'] ?? null;
        }

        // Get search term
        $filters['search'] = $this->getTableSearch();
        
        // Add cache-busting parameter if 'clear' or 'refresh' parameter is present
        if (request()->has('clear') || request()->has('refresh') || request()->has('v')) {
            $filters['cache_bust'] = time();
        }
        
        // Get all validated jaspel data without role filtering
        $data = $jaspelService->getValidatedJaspelByRole('semua', $filters);
        
        // Convert collection items to User-like objects for compatibility
        $data = $data->map(function ($item) {
            // Create a User object with additional attributes
            $user = new \App\Models\User([
                'id' => $item->id ?? $item->user_id,
                'name' => $item->name,
                'email' => $item->email ?? 'N/A',
            ]);
            
            // Explicitly set the ID and mark as existing
            $user->id = $item->id ?? $item->user_id;
            $user->exists = true;
            
            // Add custom attributes - simplified without role details
            $user->setAttribute('total_tindakan', $item->total_tindakan);
            $user->setAttribute('total_jaspel', $item->total_jaspel);
            $user->setAttribute('last_validation', $item->last_validation);
            $user->setAttribute('period', $item->period ?? 'Current');
            
            return $user;
        });
        
        // Handle sorting
        $sortColumn = $this->getTableSortColumn();
        $sortDirection = $this->getTableSortDirection();
        
        if ($sortColumn) {
            if ($sortDirection === 'desc') {
                $data = $data->sortByDesc($sortColumn);
            } else {
                $data = $data->sortBy($sortColumn);
            }
        }
        
        // Paginate the results
        $perPage = $this->getTableRecordsPerPage();
        $page = request()->get('page', 1);
        
        $paginated = new LengthAwarePaginator(
            $data->forPage($page, $perPage),
            $data->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
        
        return $paginated;
    }

    protected function getTableColumns(): array
    {
        $resource = static::getResource();
        $columns = $resource::table(\Filament\Tables\Table::make())->getColumns();
        
        // Modify column visibility based on view mode
        foreach ($columns as $column) {
            if ($column->getName() === 'last_validation') {
                $column->visible(!$this->isMonthlyView);
            }
            if ($column->getName() === 'period') {
                $column->visible($this->isMonthlyView);
            }
        }
        
        return $columns;
    }

    public function getTableRecordKey($record): string
    {
        // Ensure we always return a string ID
        return (string) ($record->getKey() ?? $record->id ?? uniqid());
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // JaspelSummaryStatsWidget will be created later if needed
        ];
    }

    public function getDefaultActiveTab(): string 
    {
        return 'semua';
    }

    protected function exportToExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $exportAction = app(ExportJaspelAction::class);
        
        // Get current filters
        $activeRole = $this->activeRole ?? $this->getDefaultActiveTab();
        $filters = $this->prepareFiltersForExport();
        
        try {
            // Generate Excel file
            $filepath = $exportAction->exportToExcel($activeRole, $filters);
            $filename = 'laporan_jaspel_' . $activeRole . '_' . now()->format('Ymd_His') . '.csv';
            
            // Download and cleanup
            return $exportAction->downloadAndCleanup($filepath, $filename);
            
        } catch (\Exception $e) {
            $this->notify('danger', 'Export Gagal', 'Error: ' . $e->getMessage());
            return back();
        }
    }

    protected function exportToPdf(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $exportAction = app(ExportJaspelAction::class);
        
        // Get current filters
        $activeRole = $this->activeRole ?? $this->getDefaultActiveTab();
        $filters = $this->prepareFiltersForExport();
        
        try {
            // Generate PDF file
            $filepath = $exportAction->exportToPdf($activeRole, $filters);
            $filename = 'laporan_jaspel_' . $activeRole . '_' . now()->format('Ymd_His') . '.html';
            
            // Download and cleanup
            return $exportAction->downloadAndCleanup($filepath, $filename);
            
        } catch (\Exception $e) {
            $this->notify('danger', 'Export Gagal', 'Error: ' . $e->getMessage());
            return back();
        }
    }

    protected function prepareFiltersForExport(): array
    {
        $tableFilters = $this->getTableFilters();
        
        $filters = [];
        
        if (!empty($tableFilters['date_range'])) {
            $filters['date_from'] = $tableFilters['date_range']['date_from'] ?? null;
            $filters['date_to'] = $tableFilters['date_range']['date_to'] ?? null;
        }

        $filters['search'] = $this->getTableSearch();
        
        return $filters;
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function paginateTableQuery(Builder $query): \Illuminate\Contracts\Pagination\Paginator
    {
        return $query->paginate($this->getTableRecordsPerPage() ?: 25);
    }

    public function getSubheading(): string
    {
        return 'Laporan total jaspel yang telah divalidasi.';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Belum ada data jaspel tervalidasi untuk role dan periode yang dipilih. Coba ubah filter atau pilih tab role lain.';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Tidak Ada Data Jaspel';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-document-chart-bar';
    }
}