<?php

namespace App\Filament\Manajer\Resources;

use App\Filament\Manajer\Resources\HighValueApprovalResource\Pages;
use App\Models\ManagerApproval;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HighValueApprovalResource extends Resource
{
    protected static ?string $model = ManagerApproval::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'High-Value Approvals';
    
    protected static ?string $navigationGroup = '✅ Approval Workflows';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📋 Approval Request Details')
                    ->description('High-value approval requiring manager authorization')
                    ->schema([
                        Forms\Components\Select::make('approval_type')
                            ->label('Approval Type')
                            ->options(ManagerApproval::getApprovalTypeOptions())
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->label('Request Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Brief description of approval needed'),

                        Forms\Components\Textarea::make('description')
                            ->label('Detailed Description')
                            ->required()
                            ->rows(3)
                            ->placeholder('Comprehensive description of the approval request...'),

                        Forms\Components\Select::make('priority')
                            ->label('Priority Level')
                            ->options(ManagerApproval::getPriorityOptions())
                            ->required()
                            ->default('medium')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('💰 Financial Information')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (if applicable)')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('Rp')
                            ->helperText('For financial approvals only'),

                        Forms\Components\DateTimePicker::make('required_by')
                            ->label('Required By')
                            ->helperText('When decision is needed')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('📄 Justification & Support')
                    ->schema([
                        Forms\Components\Textarea::make('justification')
                            ->label('Business Justification')
                            ->required()
                            ->rows(4)
                            ->placeholder('Explain why this approval is necessary for business operations...'),

                        Forms\Components\KeyValue::make('supporting_data')
                            ->label('Supporting Information')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Supporting Data')
                            ->reorderable(),
                    ]),

                Forms\Components\Section::make('🔄 Workflow Information')
                    ->schema([
                        Forms\Components\Select::make('requested_by')
                            ->label('Requested By')
                            ->options(User::all()->pluck('name', 'id'))
                            ->default(Auth::id())
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('requester_role')
                            ->label('Requester Role')
                            ->default(fn () => Auth::user()?->roles?->first()?->name ?? 'unknown')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ManagerApproval::getStatusOptions())
                            ->default('pending')
                            ->disabled()
                            ->helperText('Status will be updated by manager'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('📋 Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50)
                    ->tooltip(fn (ManagerApproval $record): string => $record->description),

                Tables\Columns\TextColumn::make('approval_type')
                    ->label('🏷️ Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'financial' => 'success',
                        'policy_override' => 'warning',
                        'staff_action' => 'info',
                        'emergency' => 'danger',
                        'budget_adjustment' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => 
                        ManagerApproval::getApprovalTypeOptions()[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('💰 Amount')
                    ->state(fn (ManagerApproval $record): string => $record->formatted_amount)
                    ->color('warning')
                    ->weight('bold')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('⚡ Priority')
                    ->badge()
                    ->color(fn (ManagerApproval $record): string => $record->priority_color)
                    ->formatStateUsing(fn (string $state): string => 
                        ManagerApproval::getPriorityOptions()[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('📍 Status')
                    ->badge()
                    ->color(fn (ManagerApproval $record): string => $record->status_color)
                    ->formatStateUsing(fn (string $state): string => 
                        ManagerApproval::getStatusOptions()[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('👤 Requested By')
                    ->searchable()
                    ->description(fn (ManagerApproval $record): string => 
                        '📋 Role: ' . ucfirst($record->requester_role)
                    ),

                Tables\Columns\TextColumn::make('days_until_due')
                    ->label('⏰ Deadline')
                    ->state(fn (ManagerApproval $record): string => 
                        $record->is_overdue 
                            ? '⚠️ OVERDUE'
                            : ($record->days_until_due === 999 ? 'No deadline' : $record->days_until_due . ' days')
                    )
                    ->color(fn (ManagerApproval $record): string => 
                        $record->is_overdue ? 'danger' : ($record->days_until_due <= 2 ? 'warning' : 'success')
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Submitted')
                    ->dateTime('M j, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_type')
                    ->options(ManagerApproval::getApprovalTypeOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(ManagerApproval::getStatusOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->options(ManagerApproval::getPriorityOptions()),

                Tables\Filters\Filter::make('pending_only')
                    ->label('Pending Approvals')
                    ->query(fn (Builder $query): Builder => $query->pending()),

                Tables\Filters\Filter::make('high_value')
                    ->label('High Value (>500K)')
                    ->query(fn (Builder $query): Builder => $query->highValue()),

                Tables\Filters\Filter::make('urgent')
                    ->label('Urgent Priority')
                    ->query(fn (Builder $query): Builder => $query->urgent()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️')
                    ->tooltip('View Details'),

                Tables\Actions\Action::make('approve')
                    ->label('✅')
                    ->tooltip('Approve Request')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->placeholder('Optional notes for approval...')
                            ->rows(3),
                    ])
                    ->action(function (ManagerApproval $record, array $data) {
                        $record->approve(Auth::user(), $data['approval_notes'] ?? null);
                        
                        Notification::make()
                            ->title('✅ Request Approved')
                            ->body('Approval request has been approved successfully.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (ManagerApproval $record): bool => $record->status === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('❌')
                    ->tooltip('Reject Request')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Explain why this request is being rejected...')
                            ->rows(3),
                    ])
                    ->action(function (ManagerApproval $record, array $data) {
                        $record->reject(Auth::user(), $data['rejection_reason']);
                        
                        Notification::make()
                            ->title('❌ Request Rejected')
                            ->body('Approval request has been rejected.')
                            ->warning()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (ManagerApproval $record): bool => $record->status === 'pending'),

                Tables\Actions\Action::make('escalate')
                    ->label('⬆️')
                    ->tooltip('Escalate to Higher Authority')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->action(function (ManagerApproval $record) {
                        $record->escalate();
                        
                        Notification::make()
                            ->title('⬆️ Request Escalated')
                            ->body('Request has been escalated to higher authority.')
                            ->info()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (ManagerApproval $record): bool => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('bulk_notes')
                                ->label('Approval Notes')
                                ->placeholder('Optional notes for all approvals...')
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve(Auth::user(), $data['bulk_notes'] ?? null);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('✅ Bulk Approval Complete')
                                ->body("{$count} requests have been approved.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['requestedBy', 'approvedBy']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = ManagerApproval::pending()->count();
        $urgentCount = ManagerApproval::urgent()->pending()->count();
        
        return $urgentCount > 0 ? (string) $urgentCount : ($pendingCount > 0 ? (string) $pendingCount : null);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $urgentCount = ManagerApproval::urgent()->pending()->count();
        return $urgentCount > 0 ? 'danger' : 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHighValueApprovals::route('/'),
            'create' => Pages\CreateHighValueApproval::route('/create'),
            'edit' => Pages\EditHighValueApproval::route('/{record}/edit'),
        ];
    }
}