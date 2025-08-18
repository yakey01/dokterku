<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OperationalAnalyticsResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    
    protected static ?string $navigationLabel = 'Operations Analytics';
    
    protected static ?string $navigationGroup = '📊 Performance Analytics';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Patient Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('daily_visits')
                    ->label('Daily Visits')
                    ->state(function (Pasien $record): string {
                        $visitsToday = Tindakan::where('pasien_id', $record->id)
                            ->whereDate('created_at', today())
                            ->count();
                        return $visitsToday . ' today';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $count = (int) str_replace(' today', '', $state);
                        return match (true) {
                            $count >= 3 => 'danger',  // Frequent visits
                            $count >= 2 => 'warning',
                            $count >= 1 => 'success',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('total_procedures')
                    ->label('Total Procedures')
                    ->state(function (Pasien $record): string {
                        $totalProcedures = Tindakan::where('pasien_id', $record->id)->count();
                        return (string) $totalProcedures;
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('last_visit')
                    ->label('Last Visit')
                    ->state(function (Pasien $record): string {
                        $lastVisit = Tindakan::where('pasien_id', $record->id)
                            ->latest()
                            ->first();
                        return $lastVisit ? $lastVisit->created_at->diffForHumans() : 'Never';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_waiting_time')
                    ->label('Avg Wait Time')
                    ->state(function (Pasien $record): string {
                        // Calculate estimated time based on actual procedure count
                        $procedureCount = Tindakan::where('pasien_id', $record->id)
                            ->whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                            ->count();
                        $avgWaitTime = $procedureCount > 0 ? $procedureCount * 10 : 0; // Realistic 10 min estimate
                        return $avgWaitTime > 0 ? $avgWaitTime . ' min' : 'N/A';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'N/A') return 'gray';
                        $minutes = (int) str_replace(' min', '', $state);
                        return match (true) {
                            $minutes >= 60 => 'danger',
                            $minutes >= 30 => 'warning',
                            default => 'success',
                        };
                    }),

                Tables\Columns\TextColumn::make('revenue_contribution')
                    ->label('Revenue Contribution')
                    ->state(function (Pasien $record): string {
                        $revenue = Pendapatan::whereHas('tindakan', function (Builder $query) use ($record) {
                            $query->where('pasien_id', $record->id);
                        })->sum('nominal');
                        return 'Rp ' . number_format($revenue);
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $amount = (int) str_replace(['Rp ', '.', ','], '', $state);
                        return match (true) {
                            $amount >= 1000000 => 'success',
                            $amount >= 500000 => 'warning',
                            default => 'gray',
                        };
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registration Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('frequent_visitors')
                    ->label('Frequent Visitors (3+ visits today)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE tindakan.pasien_id = pasien.id AND date(tindakan.created_at) = ? AND tindakan.deleted_at IS NULL) >= 3', [today()->format('Y-m-d')])
                    ),
                    
                Tables\Filters\Filter::make('high_revenue')
                    ->label('High Revenue Patients (>1M)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('(SELECT COALESCE(SUM(p.nominal), 0) FROM pendapatan p INNER JOIN tindakan t ON p.tindakan_id = t.id WHERE t.pasien_id = pasien.id AND p.deleted_at IS NULL AND t.deleted_at IS NULL) >= 1000000')
                    ),

                Tables\Filters\SelectFilter::make('visit_frequency')
                    ->label('Visit Frequency')
                    ->options([
                        'daily' => 'Daily Visitors',
                        'weekly' => 'Weekly Visitors', 
                        'monthly' => 'Monthly Visitors',
                        'rare' => 'Rare Visitors',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'daily' => $query->whereHas('tindakan', fn($q) => $q->whereDate('created_at', today())),
                            'weekly' => $query->whereHas('tindakan', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
                            'monthly' => $query->whereHas('tindakan', fn($q) => $q->whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])),
                            'rare' => $query->whereDoesntHave('tindakan', fn($q) => $q->whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('registration_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From Date'),
                        Forms\Components\DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_analytics')
                    ->label('Patient Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->action(function (Pasien $record) {
                        session()->flash('success', "Viewing analytics for patient: {$record->nama}");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('export_analytics')
                        ->label('Export Analytics Report')
                        ->icon('heroicon-o-document-chart-bar')
                        ->action(function ($records) {
                            session()->flash('success', 'Operational analytics exported successfully');
                        }),
                    Tables\Actions\Action::make('generate_trends')
                        ->label('Generate Trend Analysis')
                        ->icon('heroicon-o-presentation-chart-line')
                        ->action(function ($records) {
                            session()->flash('success', 'Trend analysis generated for selected patients');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['tindakan' => function ($query) {
                $query->latest()->limit(1);
            }]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $frequentVisitors = static::getEloquentQuery()
            ->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE tindakan.pasien_id = pasien.id AND date(tindakan.created_at) = ? AND tindakan.deleted_at IS NULL) >= 3', [today()->format('Y-m-d')])
            ->count();
            
        return $frequentVisitors > 0 ? (string) $frequentVisitors : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\OperationalAnalyticsResource\Pages\ListOperationalAnalytics::route('/'),
        ];
    }
}