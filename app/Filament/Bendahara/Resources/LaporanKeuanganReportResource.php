<?php

namespace App\Filament\Bendahara\Resources;

use App\Services\JaspelReportService;
use App\Services\SubAgents\ValidationSubAgentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class LaporanKeuanganReportResource extends Resource
{
    protected static ?string $model = User::class; // Use User model as base for queries

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Jaspel';

    protected static ?string $modelLabel = 'Laporan Jaspel';

    protected static ?string $pluralModelLabel = 'Laporan Jaspel';

    protected static ?string $slug = 'laporan-jaspel';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        // ENABLE NAVIGATION TO PROVIDE ACCESSIBLE ROUTES
        return true;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('bendahara') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->hasRole('bendahara') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::query()) // Base query that will be overridden by the page
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-user')
                    ->iconColor('primary'),

                // Role column removed as requested - no individual role breakdown

                Tables\Columns\TextColumn::make('total_tindakan')
                    ->label('Jumlah Tindakan')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-m-clipboard-document-list')
                    ->iconColor('gray')
                    ->formatStateUsing(fn (int $state): string => number_format($state) . ' tindakan'),

                Tables\Columns\TextColumn::make('total_jaspel')
                    ->label('Total Jaspel')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success')
                    ->icon('heroicon-m-banknotes')
                    ->iconColor('success')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state ?? 0, 0, ',', '.')),

                Tables\Columns\TextColumn::make('last_validation')
                    ->label('Validasi Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-m-check-circle')
                    ->iconColor('success')
                    ->placeholder('Tidak ada data')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->visible(fn () => true), // Always visible in Resource context

                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->sortable()
                    ->color('purple')
                    ->icon('heroicon-m-calendar')
                    ->iconColor('purple')
                    ->formatStateUsing(fn ($state): string => $state ? \Carbon\Carbon::createFromFormat('Y-m', $state)->format('M Y') : 'N/A')
                    ->visible(fn () => false), // Hidden by default in Resource, shown in Page

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-envelope')
                    ->iconColor('gray'),
            ])
            ->filters([
                // Role filter removed as requested - no role-based filtering

                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->placeholder('Pilih tanggal mulai'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal') 
                            ->placeholder('Pilih tanggal akhir'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date_from'] ?? null,
                            fn ($query) => $query->whereHas('jaspel', 
                                fn ($q) => $q->where('validasi_at', '>=', $data['date_from'])
                            )
                        )->when(
                            $data['date_to'] ?? null,
                            fn ($query) => $query->whereHas('jaspel',
                                fn ($q) => $q->where('validasi_at', '<=', $data['date_to'])
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'Dari: ' . Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators['date_to'] = 'Sampai: ' . Carbon::parse($data['date_to'])->format('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn ($record): string => route('filament.bendahara.resources.laporan-jaspel.view', ['record' => $record]))
                    ->openUrlInNewTab(false),

                Action::make('export_user')
                    ->label('Export User')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(fn ($record) => self::exportUserData($record))
                    ->requiresConfirmation()
                    ->modalHeading('Export Data User')
                    ->modalDescription('Export data jaspel untuk user ini?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected')
                    ->label('Export Terpilih')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(fn (Collection $records) => self::exportSelectedData($records))
                    ->requiresConfirmation()
                    ->modalHeading('Export Data Terpilih')
                    ->modalDescription('Export data jaspel untuk user yang dipilih?'),
            ])
            ->headerActions([
                Action::make('export_all')
                    ->label('Export Semua')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => self::exportAllData())
                    ->requiresConfirmation()
                    ->modalHeading('Export Semua Data')
                    ->modalDescription('Export semua data laporan jaspel?'),

                Action::make('summary_stats')
                    ->label('Ringkasan')
                    ->icon('heroicon-m-chart-bar')
                    ->color('info')
                    ->modalContent(fn (): string => self::getSummaryStatsModal())
                    ->modalHeading('Ringkasan Laporan Jaspel')
                    ->modalIcon('heroicon-o-chart-bar')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->defaultSort('total_jaspel', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s') // Refresh setiap 30 detik
            ->emptyStateHeading('Tidak Ada Data Jaspel')
            ->emptyStateDescription('Belum ada data jaspel tervalidasi untuk periode yang dipilih.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }


    protected static function getSummaryStatsModal(): Htmlable
    {
        $jaspelService = app(JaspelReportService::class);
        $stats = $jaspelService->getRoleSummaryStats();
        
        $html = '<div class="space-y-4">';
        
        foreach ($stats as $stat) {
            $html .= '<div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-800">';
            $html .= '<div class="flex justify-between items-center mb-2">';
            $html .= '<h3 class="font-semibold text-lg">' . $stat['display_name'] . '</h3>';
            $html .= '<span class="text-sm text-gray-500">' . $stat['user_count'] . ' orang</span>';
            $html .= '</div>';
            $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
            $html .= '<div>Total Jaspel: <span class="font-semibold">Rp ' . number_format($stat['total_jaspel'], 0, ',', '.') . '</span></div>';
            $html .= '<div>Total Tindakan: <span class="font-semibold">' . number_format($stat['total_tindakan']) . '</span></div>';
            $html .= '<div>Rata-rata: <span class="font-semibold">Rp ' . number_format($stat['avg_jaspel'], 0, ',', '.') . '</span></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return new HtmlString($html);
    }

    protected static function getDetailModalContent($record): Htmlable
    {
        $jaspelService = app(JaspelReportService::class);
        $validationAgent = app(ValidationSubAgentService::class);
        
        $userDetail = $jaspelService->getJaspelSummaryByUser($record->id, []);
        
        if (empty($userDetail)) {
            return new HtmlString('<div class="p-4 text-center text-gray-500">Tidak ada data detail untuk user ini.</div>');
        }

        $user = $userDetail['user'];
        $summary = $userDetail['summary'];
        
        // Get cermat validation from ValidationSubAgent
        $validation = $validationAgent->performCermatJaspelValidation($record->id);
        $discrepancyAnalysis = $validationAgent->analyzeJaspelCalculationDiscrepancy($record->id);
        
        $html = '<div class="space-y-6 max-h-96 overflow-y-auto">';
        
        // Simplified User Info - Remove detailed role breakdown
        $html .= '<div class="bg-blue-50 p-4 rounded-lg dark:bg-blue-900/20">';
        $html .= '<h3 class="font-semibold text-lg mb-3 text-blue-800 dark:text-blue-200">👤 Informasi User</h3>';
        $html .= '<div class="text-sm">';
        $html .= '<div><strong>Nama:</strong> ' . htmlspecialchars($user->name) . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Jaspel Summary Section  
        $html .= '<div class="bg-green-50 p-4 rounded-lg dark:bg-green-900/20">';
        $html .= '<h3 class="font-semibold text-lg mb-3 text-green-800 dark:text-green-200">💰 Ringkasan Jaspel</h3>';
        $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
        $html .= '<div><strong>Total Tindakan:</strong> <span class="font-semibold">' . number_format($summary->total_tindakan ?? 0) . ' tindakan</span></div>';
        $html .= '<div><strong>Total Jaspel:</strong> <span class="font-semibold text-green-600">Rp ' . number_format($summary->total_jaspel ?? 0, 0, ',', '.') . '</span></div>';
        $html .= '<div><strong>Rata-rata per Tindakan:</strong> <span class="font-semibold">Rp ' . number_format($summary->avg_jaspel ?? 0, 0, ',', '.') . '</span></div>';
        $html .= '<div><strong>Status Validasi:</strong> <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Tervalidasi</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Validation Info Section - REMOVED as requested
        // Individual validation details removed for cleaner view
        if (false) { // Disabled section
            $html .= '<div class="bg-amber-50 p-4 rounded-lg dark:bg-amber-900/20">';
            $html .= '<h3 class="font-semibold text-lg mb-3 text-amber-800 dark:text-amber-200">✅ Informasi Validasi</h3>';
            $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
            
            if ($summary->first_validation) {
                $html .= '<div><strong>Validasi Pertama:</strong> ' . \Carbon\Carbon::parse($summary->first_validation)->format('d M Y H:i') . '</div>';
            }
            
            if ($summary->last_validation) {
                $html .= '<div><strong>Validasi Terakhir:</strong> ' . \Carbon\Carbon::parse($summary->last_validation)->format('d M Y H:i') . '</div>';
            }
            
            $html .= '<div><strong>Total Periode:</strong> ';
            if ($summary->first_validation && $summary->last_validation) {
                $firstDate = \Carbon\Carbon::parse($summary->first_validation);
                $lastDate = \Carbon\Carbon::parse($summary->last_validation);
                $daysDiff = $firstDate->diffInDays($lastDate);
                $html .= $daysDiff . ' hari';
            } else {
                $html .= 'N/A';
            }
            $html .= '</div>';
            
            $html .= '</div>';
            $html .= '</div>';
        }

        // Performance Metrics
        if ($summary->total_tindakan > 0) {
            $html .= '<div class="bg-purple-50 p-4 rounded-lg dark:bg-purple-900/20">';
            $html .= '<h3 class="font-semibold text-lg mb-3 text-purple-800 dark:text-purple-200"><svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>Metrik Kinerja</h3>';
            $html .= '<div class="space-y-2 text-sm">';
            
            // Calculate performance metrics
            $avgPerDay = $summary->first_validation && $summary->last_validation ? 
                ($summary->total_tindakan / max(1, \Carbon\Carbon::parse($summary->first_validation)->diffInDays(\Carbon\Carbon::parse($summary->last_validation)) + 1)) : 0;
            
            $html .= '<div class="flex justify-between"><span>Rata-rata Tindakan per Hari:</span><span class="font-semibold">' . number_format($avgPerDay, 1) . '</span></div>';
            $html .= '<div class="flex justify-between"><span>Rata-rata Jaspel per Hari:</span><span class="font-semibold">Rp ' . number_format($avgPerDay * ($summary->avg_jaspel ?? 0), 0, ',', '.') . '</span></div>';
            
            // Performance indicator
            $performanceLevel = 'Baik';
            $performanceColor = 'text-green-600';
            if ($avgPerDay < 1) {
                $performanceLevel = 'Rendah';
                $performanceColor = 'text-red-600';
            } elseif ($avgPerDay > 3) {
                $performanceLevel = 'Tinggi';
                $performanceColor = 'text-blue-600';
            }
            
            $html .= '<div class="flex justify-between"><span>Level Aktivitas:</span><span class="font-semibold ' . $performanceColor . '">' . $performanceLevel . '</span></div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Data Integrity Validation Section - REMOVED as requested
        // Detailed individual validation analysis removed for cleaner view

        // Transaction History Section - REMOVED as requested
        // Individual detailed transaction breakdown removed for cleaner view

        // Monthly Breakdown Section - REMOVED as requested  
        // Individual monthly breakdown details removed for cleaner view
        
        $html .= '</div>';
        
        return new HtmlString($html);
    }

    protected static function exportUserData($record): void
    {
        // Implementation for exporting individual user data
        // This would typically generate PDF/Excel for the specific user
    }

    protected static function exportSelectedData(Collection $records): void
    {
        // Implementation for exporting selected records
        // This would generate PDF/Excel for selected users
    }

    protected static function exportAllData(): void
    {
        // Implementation for exporting all filtered data
        // This would generate PDF/Excel for all current filter results
    }

    public static function getPages(): array
    {
        return [
            'index' => LaporanKeuanganReportResource\Pages\ListLaporanKeuanganReport::route('/'),
            'view' => LaporanKeuanganReportResource\Pages\ViewJaspelDetail::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Show count of total users with validated jaspel
        $jaspelService = app(JaspelReportService::class);
        $data = $jaspelService->getValidatedJaspelByRole('semua', []);
        return (string) $data->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah user dengan jaspel tervalidasi';
    }
}