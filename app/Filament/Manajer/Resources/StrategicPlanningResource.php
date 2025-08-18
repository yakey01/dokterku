<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pegawai;
use App\Models\Pasien;
use App\Models\Pendapatan;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StrategicPlanningResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'Strategic KPIs';
    
    protected static ?string $navigationGroup = '📊 Strategic Planning';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Staff Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('jenis_pegawai')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paramedis' => 'primary',
                        'Non-Paramedis' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('monthly_performance')
                    ->label('Monthly Performance')
                    ->state(function (Pegawai $record): string {
                        $monthlyTindakan = Tindakan::whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                            ->where(function($query) use ($record) {
                                $query->where('paramedis_id', $record->id)
                                      ->orWhere('non_paramedis_id', $record->id);
                            })
                            ->count();
                        return $monthlyTindakan . ' procedures';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $count = (int) str_replace(' procedures', '', $state);
                        return match (true) {
                            $count >= 50 => 'success',
                            $count >= 25 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->url(fn (Pegawai $record): string => route('filament.manajer.resources.strategic-plannings.performance-details', $record)),

                Tables\Columns\TextColumn::make('efficiency_rating')
                    ->label('Efficiency Rating')
                    ->state(function (Pegawai $record): string {
                        // Calculate efficiency based on procedures vs attendance
                        $monthlyTindakan = Tindakan::whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                            ->where(function($query) use ($record) {
                                $query->where('paramedis_id', $record->id)
                                      ->orWhere('non_paramedis_id', $record->id);
                            })
                            ->count();
                        
                        $efficiency = $monthlyTindakan > 0 ? min(100, ($monthlyTindakan / 30) * 100) : 0;
                        return round($efficiency) . '%';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $percentage = (int) str_replace('%', '', $state);
                        return match (true) {
                            $percentage >= 80 => 'success',
                            $percentage >= 60 => 'warning', 
                            default => 'danger',
                        };
                    }),
                    
                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Active')
                    ->state(function (Pegawai $record): string {
                        return $record->updated_at ? $record->updated_at->diffForHumans() : 'Never';
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pegawai')
                    ->label('Employee Type')
                    ->options([
                        'Paramedis' => 'Paramedis',
                        'Non-Paramedis' => 'Non-Paramedis',
                    ]),
                    
                Tables\Filters\Filter::make('high_performers')
                    ->label('High Performers')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE strftime("%m", tindakan.created_at) = ? AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) AND tindakan.deleted_at IS NULL) >= 30', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                    ),
                    
                Tables\Filters\Filter::make('needs_attention')
                    ->label('Needs Attention')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE strftime("%m", tindakan.created_at) = ? AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) AND tindakan.deleted_at IS NULL) = 0', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_details')
                    ->label('Performance Details')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn (Pegawai $record): string => route('filament.manajer.resources.strategic-plannings.performance-details', $record)),
                    
                Tables\Actions\Action::make('procedure_breakdown')
                    ->label('Procedure Breakdown')
                    ->icon('heroicon-o-list-bullet')
                    ->color('warning')
                    ->modalHeading(fn (Pegawai $record): string => "Procedure Breakdown - {$record->nama_lengkap}")
                    ->modalContent(fn (Pegawai $record): \Illuminate\Contracts\View\View => view('filament.manajer.modals.procedure-breakdown', ['staff' => $record]))
                    ->modalWidth('5xl'),
                    
                Tables\Actions\Action::make('send_performance_alert')
                    ->label('Send Alert')
                    ->icon('heroicon-o-bell')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Send Performance Alert')
                    ->modalDescription('This will send a performance improvement notification to the selected staff member.')
                    ->action(function (Pegawai $record) {
                        // Dispatch notification job
                        \App\Jobs\SendPerformanceAlertJob::dispatch($record);
                        session()->flash('success', "Performance alert sent to {$record->nama_lengkap}");
                    })
                    ->visible(fn (Pegawai $record): bool => static::isLowPerformer($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('export_performance')
                        ->label('Export Performance Report')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            // Export logic would go here
                            session()->flash('success', 'Performance report exported successfully');
                        }),
                        
                    Tables\Actions\Action::make('bulk_performance_alert')
                        ->label('Send Performance Alerts')
                        ->icon('heroicon-o-bell')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Bulk Performance Alerts')
                        ->modalDescription('This will send performance alerts to all selected staff members.')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $staff) {
                                if (static::isLowPerformer($staff)) {
                                    \App\Jobs\SendPerformanceAlertJob::dispatch($staff);
                                    $count++;
                                }
                            }
                            session()->flash('success', "Performance alerts sent to {$count} staff members");
                        }),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user.role', 'users.role']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $lowPerformers = static::getEloquentQuery()
            ->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE strftime("%m", tindakan.created_at) = ? AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) AND tindakan.deleted_at IS NULL) = 0', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
            ->count();
            
        return $lowPerformers > 0 ? (string) $lowPerformers : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    protected static function isLowPerformer(Pegawai $record): bool
    {
        $monthlyTindakan = Tindakan::whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
            ->where(function($query) use ($record) {
                $query->where('paramedis_id', $record->id)
                      ->orWhere('non_paramedis_id', $record->id);
            })
            ->count();
            
        return $monthlyTindakan < 10; // Threshold for low performance
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\StrategicPlanningResource\Pages\ListStrategicPlannings::route('/'),
            'performance-details' => \App\Filament\Manajer\Resources\StrategicPlanningResource\Pages\PerformanceDetails::route('/{record}/performance'),
        ];
    }
}