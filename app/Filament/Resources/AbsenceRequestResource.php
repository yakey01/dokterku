<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsenceRequestResource\Pages;
use App\Models\AbsenceRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class AbsenceRequestResource extends Resource
{
    protected static ?string $model = AbsenceRequest::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Leave Management';
    
    protected static ?string $navigationLabel = 'Permintaan Izin';
    
    protected static ?string $modelLabel = 'Permintaan Izin';
    
    protected static ?string $pluralModelLabel = 'Permintaan Izin';

    protected static ?int $navigationSort = 55;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('absence_date')
                            ->label('Absence Date')
                            ->required()
                            ->minDate(Carbon::today()),

                        Forms\Components\Select::make('absence_type')
                            ->label('Absence Type')
                            ->options([
                                'sick' => 'Sakit',
                                'personal' => 'Keperluan Pribadi',
                                'vacation' => 'Cuti',
                                'emergency' => 'Darurat',
                                'medical' => 'Medical',
                                'family' => 'Keluarga',
                                'other' => 'Lainnya',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Forms\Components\Section::make('Evidence & Documentation')
                    ->schema([
                        Forms\Components\FileUpload::make('evidence_file')
                            ->label('Evidence File')
                            ->directory('absence-evidence')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->placeholder('Upload medical certificate, documents, etc.'),

                        Forms\Components\Toggle::make('requires_medical_cert')
                            ->label('Requires Medical Certificate')
                            ->default(false),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_half_day')
                                    ->label('Half Day')
                                    ->default(false)
                                    ->reactive(),

                                Forms\Components\TextInput::make('deduction_amount')
                                    ->label('Deduction Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0.00'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('half_day_start')
                                    ->label('Half Day Start')
                                    ->visible(fn ($get) => $get('is_half_day')),

                                Forms\Components\TimePicker::make('half_day_end')
                                    ->label('Half Day End')
                                    ->visible(fn ($get) => $get('is_half_day')),
                            ]),

                        Forms\Components\Repeater::make('replacement_staff')
                            ->label('Replacement Staff')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Staff Member')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('role')
                                    ->label('Replacement Role'),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['role'] ?? null),
                    ]),

                Forms\Components\Section::make('Review & Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->placeholder('Add review notes or comments'),

                        Forms\Components\Select::make('reviewed_by')
                            ->label('Reviewed By')
                            ->relationship('reviewedBy', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('absence_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_absence_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Sakit' => 'danger',
                        'Cuti' => 'success',
                        'Darurat' => 'warning',
                        'Medical' => 'info',
                        default => 'gray'
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\IconColumn::make('is_half_day')
                    ->label('Half Day')
                    ->boolean(),

                Tables\Columns\IconColumn::make('requires_medical_cert')
                    ->label('Medical Cert')
                    ->boolean(),

                Tables\Columns\TextColumn::make('evidence_file')
                    ->label('Evidence')
                    ->formatStateUsing(fn ($state) => $state ? '✓' : '✗')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('deduction_amount')
                    ->label('Deduction')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->placeholder('Not reviewed')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('absence_type')
                    ->label('Type')
                    ->options([
                        'sick' => 'Sakit',
                        'personal' => 'Keperluan Pribadi',
                        'vacation' => 'Cuti',
                        'emergency' => 'Darurat',
                        'medical' => 'Medical',
                        'family' => 'Keluarga',
                        'other' => 'Lainnya',
                    ]),

                Filter::make('pending_requests')
                    ->label('Pending Only')
                    ->query(fn (Builder $query): Builder => $query->pending()),

                Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('absence_date', Carbon::now()->month)
                              ->whereYear('absence_date', Carbon::now()->year)
                    ),

                Filter::make('requires_review')
                    ->label('Requires Review')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', 'pending')
                              ->where('requires_medical_cert', true)
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Approval Notes')
                            ->placeholder('Add approval notes (optional)'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->approve(auth()->id(), $data['admin_notes'] ?? null);
                        Notification::make()
                            ->title('Absence request approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide reason for rejection'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject(auth()->id(), $data['admin_notes']);
                        Notification::make()
                            ->title('Absence request rejected')
                            ->success()
                            ->send();
                    }),

                Action::make('view_evidence')
                    ->label('View Evidence')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->evidence_file)
                    ->url(fn ($record) => $record->evidence_file_url)
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->poll('15s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsenceRequests::route('/'),
            'create' => Pages\CreateAbsenceRequest::route('/create'),
            'view' => Pages\ViewAbsenceRequest::route('/{record}'),
            'edit' => Pages\EditAbsenceRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
