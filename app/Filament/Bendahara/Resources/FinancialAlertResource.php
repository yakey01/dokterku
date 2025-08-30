<?php

namespace App\Filament\Bendahara\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class FinancialAlertResource extends Resource
{
    protected static ?string $model = null; // This is a utility resource for financial alerts

    protected static ?string $navigationIcon = null;

    // DISABLED: Prevent showing in bendahara panel due to missing model
    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = 'Financial Alerts';

    protected static ?string $modelLabel = 'Financial Alert';

    protected static ?string $pluralModelLabel = 'Financial Alerts';

    // DISABLED: Prevent navigation registration due to missing model
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alert_type')
                    ->label('Alert Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'budget_exceeded' => 'danger',
                        'unusual_expense' => 'warning',
                        'duplicate_transaction' => 'info',
                        'pending_approval' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'acknowledged' => 'info',
                        'resolved' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('acknowledge')
                        ->label('✅ Acknowledge')
                        ->color('success')
                        ->action(function () {
                            Notification::make()
                                ->title('✅ Alert Acknowledged')
                                ->body('Alert has been acknowledged')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('resolve')
                        ->label('🔧 Resolve')
                        ->color('success')
                        ->action(function () {
                            Notification::make()
                                ->title('✅ Alert Resolved')
                                ->body('Alert has been resolved')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->headerActions([
                Action::make('alert_settings')
                    ->label('⚙️ Alert Settings')
                    ->color('gray')
                    ->action(function () {
                        Notification::make()
                            ->title('⚙️ Alert Settings')
                            ->body('Configure financial alert thresholds and rules')
                            ->info()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => FinancialAlertResource\Pages\ListFinancialAlerts::route('/'),
        ];
    }
}