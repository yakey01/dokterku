<?php

namespace App\Filament\Resources\AttendanceRecapResource\Pages;

use App\Filament\Resources\AttendanceRecapResource;
use App\Models\AttendanceRecap;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Filament\Resources\Components\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListAttendanceRecaps extends ListRecords
{
    protected static string $resource = AttendanceRecapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    return redirect(request()->header('Referer'));
                })
                ->tooltip('Refresh attendance data'),

            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // Export logic will be implemented later
                    \Filament\Notifications\Notification::make()
                        ->title('Export Excel')
                        ->body('Fitur export Excel akan segera tersedia')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action(function () {
                    // Export logic will be implemented later
                    \Filament\Notifications\Notification::make()
                        ->title('Export PDF')
                        ->body('Fitur export PDF akan segera tersedia')
                        ->info()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widget temporarily disabled due to loading issues
            // \App\Filament\Widgets\AttendanceOverviewWidget::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Rekapitulasi Absensi';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $month = request('tableFilters.month.value', now()->month);
        $year = request('tableFilters.year.value', now()->year);
        $staffType = request('tableFilters.staff_type.value');
        
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        try {
            $data = AttendanceRecap::getRecapData($month, $year, $staffType);
            $totalStaff = $data->count();
            $averageAttendance = $data->avg('attendance_percentage');
            $excellentStaff = $data->where('status', 'excellent')->count();
            
            $categoryText = $staffType ? " kategori $staffType" : " semua kategori";
            $statsText = $totalStaff > 0 
                ? " | $totalStaff staff | Rata-rata: " . number_format($averageAttendance, 1) . "% | Excellent: $excellentStaff staff"
                : " | Belum ada data";
            
            return 'Data kehadiran untuk bulan ' . $monthNames[$month] . ' ' . $year . $categoryText . $statsText;
        } catch (\Exception $e) {
            return 'Data kehadiran untuk bulan ' . $monthNames[$month] . ' ' . $year;
        }
    }

    protected function paginateTableQuery(Builder $query): LengthAwarePaginator
    {
        // Get filter values from request or use defaults
        $month = request('tableFilters.month.value', now()->month);
        $year = request('tableFilters.year.value', now()->year);
        $staffType = request('tableFilters.staff_type.value');
        $statusFilter = request('tableFilters.status.value');
        
        // Store context in session for action resolution
        session([
            'attendance_recap_month' => $month,
            'attendance_recap_year' => $year,
            'attendance_recap_staff_type' => $staffType,
            'attendance_recap_status' => $statusFilter
        ]);
        
        // Get data from our custom method
        $data = AttendanceRecap::getRecapData($month, $year, $staffType);
        
        // Apply status filter if specified
        if ($statusFilter) {
            $data = $data->where('status', $statusFilter);
        }
        
        // Convert to models using the new createVirtualModel method
        $models = $data->map(function ($item) {
            return AttendanceRecap::createVirtualModel($item);
        });

        // Handle sorting
        $sortColumn = request('tableSortColumn', 'rank');
        $sortDirection = request('tableSortDirection', 'asc');
        
        if ($sortColumn === 'rank') {
            $models = $sortDirection === 'asc' 
                ? $models->sortBy('rank') 
                : $models->sortByDesc('rank');
        } elseif ($sortColumn === 'attendance_percentage') {
            $models = $sortDirection === 'asc' 
                ? $models->sortBy('attendance_percentage')
                : $models->sortByDesc('attendance_percentage');
        } elseif ($sortColumn === 'staff_name') {
            $models = $sortDirection === 'asc' 
                ? $models->sortBy('staff_name')
                : $models->sortByDesc('staff_name');
        }

        // Convert back to indexed collection
        $models = $models->values();

        // Paginate the collection
        $page = request('page', 1);
        $perPage = request('tableRecordsPerPage', 10);
        $offset = ($page - 1) * $perPage;
        $items = $models->slice($offset, $perPage);

        return new LengthAwarePaginator(
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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => $this->getTableRecordsCount()),
                
            'dokter' => Tab::make('Dokter')
                ->modifyQueryUsing(function () {
                    // This will be handled in our custom pagination
                    request()->merge(['staff_type' => 'Dokter']);
                })
                ->badge(fn () => $this->getStaffTypeCount('Dokter')),
                
            'paramedis' => Tab::make('Paramedis')
                ->modifyQueryUsing(function () {
                    request()->merge(['staff_type' => 'Paramedis']);
                })
                ->badge(fn () => $this->getStaffTypeCount('Paramedis')),
                
            'non_paramedis' => Tab::make('Non-Paramedis')
                ->modifyQueryUsing(function () {
                    request()->merge(['staff_type' => 'Non-Paramedis']);
                })
                ->badge(fn () => $this->getStaffTypeCount('Non-Paramedis')),
        ];
    }

    protected function getTableRecordsCount(): int
    {
        $month = request('month', now()->month);
        $year = request('year', now()->year);
        
        return AttendanceRecap::getRecapData($month, $year)->count();
    }

    protected function getStaffTypeCount(string $staffType): int
    {
        $month = request('month', now()->month);
        $year = request('year', now()->year);
        
        return AttendanceRecap::getRecapData($month, $year, $staffType)->count();
    }
}