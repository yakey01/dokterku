<?php

namespace App\Filament\Concerns;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Provides monthly archive functionality for Filament resources
 * 
 * Usage:
 * 1. Add trait to your Resource class
 * 2. Override getArchiveDateColumn() method to return your date column name:
 *    public static function getArchiveDateColumn(): string {
 *        return 'your_date_column';
 *    }
 * 3. Add $this->getMonthlyArchiveFilters() to your filters array
 * 4. Optionally override getArchiveQuery() for custom filtering
 *
 * Examples:
 * - TindakanResource: returns 'tanggal_tindakan'
 * - JumlahPasienHarianResource: returns 'tanggal'
 */
trait HasMonthlyArchive 
{
    
    /**
     * Get the date column for archiving
     * Override this method in your resource class for better control
     */
    protected static function getArchiveDateColumnName(): string
    {
        // Check if the child class has overridden the method
        if (method_exists(static::class, 'getArchiveDateColumn') && 
            get_called_class() !== __CLASS__) {
            return static::getArchiveDateColumn();
        }
        
        // Default fallback if no method is overridden
        return 'created_at';
    }
    
    /**
     * Get monthly archive filters for tables
     */
    public static function getMonthlyArchiveFilters(): array
    {
        return [
            // Quick Month Selector - defaults to current month
            SelectFilter::make('archive_month')
                ->label('📅 Bulan/Tahun')
                ->options(static::getMonthYearOptions())
                ->default(now()->format('Y-m'))
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['value']) || $data['value'] === 'all') {
                        return $query;
                    }
                    
                    if ($data['value'] === 'current') {
                        $data['value'] = now()->format('Y-m');
                    }
                    
                    [$year, $month] = explode('-', $data['value']);
                    $dateColumn = static::getArchiveDateColumnName();
                    
                    return $query->whereYear($dateColumn, $year)
                                 ->whereMonth($dateColumn, $month);
                })
                ->searchable()
                ->placeholder('📅 Semua Data'),
                
            // Archive Status Filter
            SelectFilter::make('archive_status')
                ->label('📂 Status Arsip')
                ->options([
                    'current' => '📅 Bulan Berjalan',
                    'archived' => '🗂️ Arsip (Bulan Lalu)',
                    'all' => '📊 Semua Data'
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['value']) || $data['value'] === 'all') {
                        return $query;
                    }
                    
                    $dateColumn = static::getArchiveDateColumnName();
                    
                    return match($data['value']) {
                        'current' => $query->whereYear($dateColumn, now()->year)
                                          ->whereMonth($dateColumn, now()->month),
                        'archived' => $query->where(function($query) use ($dateColumn) {
                            $query->where($dateColumn, '<', now()->startOfMonth())
                                  ->orWhere(function($subQuery) use ($dateColumn) {
                                      $subQuery->whereYear($dateColumn, '<', now()->year);
                                  });
                        }),
                        default => $query
                    };
                }),
                
            // Custom Date Range Filter for detailed search
            Filter::make('custom_date_range')
                ->form([
                    DatePicker::make('dari_tanggal')
                        ->label('📅 Dari Tanggal')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('sampai_tanggal')
                        ->label('📅 Sampai Tanggal')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $dateColumn = static::getArchiveDateColumnName();
                    
                    return $query
                        ->when(
                            !empty($data['dari_tanggal']),
                            fn (Builder $query) => $query->whereDate($dateColumn, '>=', $data['dari_tanggal'])
                        )
                        ->when(
                            !empty($data['sampai_tanggal']),
                            fn (Builder $query) => $query->whereDate($dateColumn, '<=', $data['sampai_tanggal'])
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    
                    if (!empty($data['dari_tanggal'])) {
                        $indicators['dari_tanggal'] = '📅 Dari: ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                    }
                    
                    if (!empty($data['sampai_tanggal'])) {
                        $indicators['sampai_tanggal'] = '📅 Sampai: ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                    }
                    
                    return $indicators;
                }),
        ];
    }
    
    /**
     * Generate month-year options for the last 24 months + next month
     */
    protected static function getMonthYearOptions(): array 
    {
        $options = [
            'current' => '📅 Bulan Ini (' . now()->isoFormat('MMMM Y') . ')',
            'all' => '📊 Semua Data (History Lengkap)'
        ];
        
        // Add current month as default
        $currentMonth = now();
        $options[$currentMonth->format('Y-m')] = '📅 ' . $currentMonth->isoFormat('MMMM Y');
        
        // Add previous 23 months (24 total including current)
        for ($i = 1; $i <= 23; $i++) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $label = '🗂️ ' . $month->isoFormat('MMMM Y');
            
            // Mark older months as archived
            if ($i > 1) {
                $label = '🗂️ ' . $month->isoFormat('MMMM Y') . ' (Arsip)';
            }
            
            $options[$key] = $label;
        }
        
        return $options;
    }
    
    /**
     * Get default archive query (current month)
     * Override this method in your resource for custom default behavior
     */
    public static function getArchiveQuery(): Builder
    {
        $dateColumn = static::getArchiveDateColumnName();
        
        return static::getModel()::query()
            ->whereYear($dateColumn, now()->year)
            ->whereMonth($dateColumn, now()->month);
    }
    
    /**
     * Get archive statistics for navigation badges
     */
    public static function getArchiveStats(): array 
    {
        $dateColumn = static::getArchiveDateColumnName();
        $model = static::getModel();
        
        $currentMonth = $model::whereYear($dateColumn, now()->year)
                             ->whereMonth($dateColumn, now()->month)
                             ->count();
                             
        $archivedCount = $model::where($dateColumn, '<', now()->startOfMonth())->count();
        
        $totalCount = $model::count();
        
        return [
            'current_month' => $currentMonth,
            'archived' => $archivedCount,
            'total' => $totalCount,
            'archive_percentage' => $totalCount > 0 ? round(($archivedCount / $totalCount) * 100, 1) : 0
        ];
    }
    
    /**
     * Get navigation badge showing current month count
     */
    public static function getNavigationBadge(): ?string
    {
        $stats = static::getArchiveStats();
        return $stats['current_month'] > 0 ? (string) $stats['current_month'] : null;
    }
    
    /**
     * Get archive-aware eloquent query with current month as default
     */
    public static function getEloquentQuery(): Builder
    {
        // Get the parent query
        $query = parent::getEloquentQuery();
        
        // Apply default current month filter if no specific filter is active
        // This is handled by the filter system, so we just return the base query
        return $query;
    }
    
    /**
     * Get months with data for navigation
     */
    public static function getAvailableMonths(): array
    {
        $dateColumn = static::getArchiveDateColumnName();
        $model = static::getModel();
        
        return $model::selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as month_year, COUNT(*) as count")
                    ->groupByRaw("DATE_FORMAT({$dateColumn}, '%Y-%m')")
                    ->orderByRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') DESC")
                    ->limit(24) // Last 24 months
                    ->pluck('count', 'month_year')
                    ->toArray();
    }
    
    /**
     * Check if current resource has archived data
     */
    public static function hasArchivedData(): bool
    {
        $stats = static::getArchiveStats();
        return $stats['archived'] > 0;
    }
    
    /**
     * Get archive summary for dashboard
     */
    public static function getArchiveSummary(): string
    {
        $stats = static::getArchiveStats();
        
        return "📅 Bulan Ini: {$stats['current_month']} | " .
               "🗂️ Arsip: {$stats['archived']} | " . 
               "📊 Total: {$stats['total']}";
    }
}