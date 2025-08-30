<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\AuditLog;
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

class AuditTrailResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Audit & Kontrol';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'Audit Trail';

    protected static ?string $pluralModelLabel = 'Audit Trail';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),

                Tables\Columns\TextColumn::make('model_id')
                    ->label('Record ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'login' => 'Login',
                        'logout' => 'Logout',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->headerActions([
                Action::make('audit_summary')
                    ->label('📊 Audit Summary')
                    ->color('info')
                    ->action(function () {
                        $today = now()->toDateString();
                        $summary = [
                            'total_today' => AuditLog::whereDate('created_at', $today)->count(),
                            'unique_users' => AuditLog::whereDate('created_at', $today)->distinct('user_id')->count(),
                            'most_common_action' => AuditLog::whereDate('created_at', $today)
                                ->groupBy('action')
                                ->selectRaw('action, count(*) as count')
                                ->orderBy('count', 'desc')
                                ->first(),
                        ];

                        $message = "📊 **AUDIT SUMMARY - TODAY**\n\n";
                        $message .= "📝 Total Activities: {$summary['total_today']}\n";
                        $message .= "👥 Unique Users: {$summary['unique_users']}\n";
                        if ($summary['most_common_action']) {
                            $message .= "🔥 Most Common: {$summary['most_common_action']->action} ({$summary['most_common_action']->count}x)";
                        }

                        Notification::make()
                            ->title('📊 Audit Summary')
                            ->body($message)
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

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => AuditTrailResource\Pages\ListAuditTrail::route('/'),
        ];
    }
}