<?php

namespace App\Filament\Resources\JagaAttendanceRecapResource\Pages;

use App\Filament\Resources\JagaAttendanceRecapResource;
use App\Models\AttendanceJagaRecap;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;

class ListJagaAttendanceRecaps extends ListRecords
{
    protected static string $resource = JagaAttendanceRecapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export Semua')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // Export all data functionality
                    return $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('print_report')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function () {
                    // Print report functionality
                    return $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    public function getTabs(): array
    {
        $currentMonth = request()->get('tableFilters.month.value', now()->month);
        $currentYear = request()->get('tableFilters.year.value', now()->year);

        try {
            // Get statistics for badge counts
            $allData = AttendanceJagaRecap::getJagaRecapData(null, $currentMonth, $currentYear);
            $dokterData = $allData->where('profession', 'Dokter');
            $paramedisData = $allData->where('profession', 'Paramedis');
            $nonParamedisData = $allData->where('profession', 'NonParamedis');

            return [
                'all' => Tab::make('Semua Staff')
                    ->icon('heroicon-o-users')
                    ->badge($allData->count())
                    ->badgeColor($this->getBadgeColor($allData))
                    ->modifyQueryUsing(fn (Builder $query) => $query),

                'dokter' => Tab::make('👨‍⚕️ Dokter')
                    ->icon('heroicon-o-heart')
                    ->badge($dokterData->count())
                    ->badgeColor($this->getBadgeColor($dokterData))
                    ->modifyQueryUsing(function () {
                        request()->merge(['profession_filter' => 'Dokter']);
                    }),

                'paramedis' => Tab::make('👩‍⚕️ Paramedis')
                    ->icon('heroicon-o-shield-check')
                    ->badge($paramedisData->count())
                    ->badgeColor($this->getBadgeColor($paramedisData))
                    ->modifyQueryUsing(function () {
                        request()->merge(['profession_filter' => 'Paramedis']);
                    }),

                'non_paramedis' => Tab::make('👤 Non-Paramedis')
                    ->icon('heroicon-o-building-office')
                    ->badge($nonParamedisData->count())
                    ->badgeColor($this->getBadgeColor($nonParamedisData))
                    ->modifyQueryUsing(function () {
                        request()->merge(['profession_filter' => 'NonParamedis']);
                    }),
            ];

        } catch (\Exception $e) {
            // Fallback tabs if data fetching fails
            return [
                'all' => Tab::make('Semua Staff')
                    ->icon('heroicon-o-users')
                    ->badge(0)
                    ->badgeColor('gray'),

                'dokter' => Tab::make('👨‍⚕️ Dokter')
                    ->icon('heroicon-o-heart')
                    ->badge(0)
                    ->badgeColor('gray'),

                'paramedis' => Tab::make('👩‍⚕️ Paramedis')
                    ->icon('heroicon-o-shield-check')
                    ->badge(0)
                    ->badgeColor('gray'),

                'non_paramedis' => Tab::make('👤 Non-Paramedis')
                    ->icon('heroicon-o-building-office')
                    ->badge(0)
                    ->badgeColor('gray'),
            ];
        }
    }

    protected function paginateTableQuery(Builder $query): \Illuminate\Pagination\LengthAwarePaginator
    {
        // Get filter values from request or use defaults
        $month = request('tableFilters.month.value', now()->month);
        $year = request('tableFilters.year.value', now()->year);
        $profession = request()->get('profession_filter') ?? request('tableFilters.profession.value');
        $statusFilter = request('tableFilters.status.value');

        // Get data from our custom method
        $data = AttendanceJagaRecap::getJagaRecapData($profession, $month, $year);

        // Apply status filter if specified
        if ($statusFilter) {
            $data = $data->where('status', $statusFilter);
        }

        // Convert to models with proper Filament action support
        $models = $data->map(function ($item) {
            $model = new AttendanceJagaRecap;
            
            // Fill with data
            $model->fill($item->toArray());
            
            // Ensure proper model state for Filament actions
            $model->exists = true;
            $model->id = $item->user_id; // Primary key for actions
            $model->setRawAttributes($item->toArray(), true); // Sync original attributes
            $model->syncOriginal(); // Mark as clean/existing model
            
            // Set the key name explicitly for Filament
            $model->incrementing = false;
            $model->keyType = 'int';
            
            return $model;
        });

        // Handle sorting
        $sortColumn = request('tableSortColumn', 'attendance_percentage');
        $sortDirection = request('tableSortDirection', 'desc');

        if ($sortColumn === 'attendance_percentage') {
            $models = $sortDirection === 'asc'
                ? $models->sortBy('attendance_percentage')
                : $models->sortByDesc('attendance_percentage');
        } elseif ($sortColumn === 'staff_name') {
            $models = $sortDirection === 'asc'
                ? $models->sortBy('staff_name')
                : $models->sortByDesc('staff_name');
        } elseif ($sortColumn === 'total_working_hours') {
            $models = $sortDirection === 'asc'
                ? $models->sortBy('total_working_hours')
                : $models->sortByDesc('total_working_hours');
        }

        // Convert back to indexed collection
        $models = $models->values();

        // Paginate the collection
        $page = request('page', 1);
        $perPage = request('tableRecordsPerPage', 25);
        $offset = ($page - 1) * $perPage;
        $items = $models->slice($offset, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $models->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Get badge color based on data quality
     */
    private function getBadgeColor($data): string
    {
        if ($data->isEmpty()) {
            return 'gray';
        }

        $excellentCount = $data->where('status', 'excellent')->count();
        $total = $data->count();
        $excellentPercentage = ($excellentCount / $total) * 100;

        return match (true) {
            $excellentPercentage >= 70 => 'success',
            $excellentPercentage >= 50 => 'info',
            $excellentPercentage >= 30 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Add custom content to the page header
     */
    protected function getHeaderWidgets(): array
    {
        return [
            // We can add summary widgets here later
        ];
    }

    /**
     * Custom page header content
     */
    public function getHeader(): ?View
    {
        return view('filament.pages.jaga-attendance-header', [
            'currentMonth' => request()->get('tableFilters.month.value', now()->month),
            'currentYear' => request()->get('tableFilters.year.value', now()->year),
        ]);
    }
}
