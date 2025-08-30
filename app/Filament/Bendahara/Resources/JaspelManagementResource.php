<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Jaspel;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class JaspelManagementResource extends Resource
{
    protected static ?string $model = Jaspel::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Kelola Jaspel';
    
    // DISABLED: Moved from Validasi Transaksi to prevent showing in bendahara panel
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'jaspel-management';

    // DISABLED: Prevent navigation registration for bendahara panel
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Jaspel Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('User')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                
                                Forms\Components\Select::make('jenis_jaspel')
                                    ->label('Jenis Jaspel')
                                    ->options([
                                        'paramedis' => 'Paramedis',
                                        'dokter_umum' => 'Dokter Umum',
                                        'dokter_spesialis' => 'Dokter Spesialis',
                                        'shift' => 'Shift',
                                        'manual' => 'Manual Entry',
                                    ])
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('nominal')
                                    ->label('Nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                    
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now()),
                            ]),
                            
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Keterangan Jaspel...')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Validation Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_validasi')
                                    ->label('Status Validasi')
                                    ->options([
                                        'pending' => 'Menunggu Validasi',
                                        'disetujui' => 'Disetujui',
                                        'ditolak' => 'Ditolak',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false),
                                    
                                Forms\Components\TextInput::make('validasiBy.name')
                                    ->label('Divalidasi Oleh')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['disetujui', 'ditolak'])),
                            ]),
                            
                        Forms\Components\DateTimePicker::make('validasi_at')
                            ->label('Tanggal Validasi')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['disetujui', 'ditolak'])),
                            
                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->placeholder('Tambahkan catatan validasi...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('jenis_jaspel')
                    ->label('Jenis')
                    ->badge()
                    ->colors([
                        'primary' => 'paramedis',
                        'success' => 'dokter_umum',
                        'warning' => 'dokter_spesialis',
                        'info' => 'shift',
                        'gray' => 'manual',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paramedis' => 'Paramedis',
                        'dokter_umum' => 'Dokter Umum',
                        'dokter_spesialis' => 'Dokter Spesialis',
                        'shift' => 'Shift',
                        'manual' => 'Manual',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('tindakan_id')
                    ->label('Source')
                    ->badge()
                    ->getStateUsing(function (Jaspel $record): string {
                        return $record->tindakan_id ? 'Tindakan' : 'Manual';
                    })
                    ->colors([
                        'success' => fn ($state) => $state === 'Tindakan',
                        'warning' => fn ($state) => $state === 'Manual',
                    ]),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'disetujui',
                        'heroicon-o-x-circle' => 'ditolak',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Validator')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validasi_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui', 
                        'ditolak' => 'Ditolak',
                    ])
                    ->placeholder('All Status'),

                Tables\Filters\SelectFilter::make('jenis_jaspel')
                    ->label('Jenis Jaspel')
                    ->options([
                        'paramedis' => 'Paramedis',
                        'dokter_umum' => 'Dokter Umum',
                        'dokter_spesialis' => 'Dokter Spesialis',
                        'shift' => 'Shift',
                        'manual' => 'Manual Entry',
                    ])
                    ->placeholder('All Types'),

                Tables\Filters\Filter::make('manual_entries')
                    ->label('Manual Entries Only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('tindakan_id'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    // Quick Validation Actions
                    Action::make('quick_approve')
                        ->label('⚡ Quick Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Jaspel $record): bool => $record->status_validasi === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('⚡ Quick Approve Jaspel')
                        ->modalDescription('Approve this Jaspel entry?')
                        ->modalSubmitActionLabel('Approve')
                        ->action(function (Jaspel $record) {
                            static::quickValidateJaspel($record, 'disetujui');
                        }),

                    Action::make('quick_reject')
                        ->label('⚡ Quick Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Jaspel $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Please provide reason for rejection...')
                        ])
                        ->action(function (Jaspel $record, array $data) {
                            static::quickValidateJaspel($record, 'ditolak', $data['rejection_reason']);
                        }),

                    Tables\Actions\ViewAction::make()
                        ->label('👁️ View Details'),

                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (Jaspel $record): bool => Auth::user()->hasRole(['admin', 'bendahara'])),
                ])
                ->label('⚙️ Actions')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('✅ Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('✅ Bulk Approve Jaspel')
                        ->modalDescription('Are you sure you want to approve all selected Jaspel entries?')
                        ->action(function (Collection $records) {
                            static::bulkValidateJaspel($records->where('status_validasi', 'pending'), 'disetujui');
                        }),

                    BulkAction::make('bulk_reject')
                        ->label('❌ Bulk Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('bulk_rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Provide reason for bulk rejection...')
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::bulkValidateJaspel(
                                $records->where('status_validasi', 'pending'),
                                'ditolak',
                                $data['bulk_rejection_reason']
                            );
                        }),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'validasiBy', 'tindakan']);
    }

    // Helper Methods for Actions
    protected static function quickValidateJaspel(Jaspel $record, string $status, ?string $comment = null): void
    {
        try {
            // Map status to database constraint values
            $mappedStatus = match($status) {
                'approved', 'disetujui' => 'disetujui',
                'rejected', 'ditolak' => 'ditolak',
                'pending' => 'pending',
                default => $status
            };
            
            $record->update([
                'status_validasi' => $mappedStatus,
                'validasi_by' => Auth::id(),
                'validasi_at' => now(),
                'catatan_validasi' => $comment ?? ($mappedStatus === 'disetujui' ? 'Quick approved' : 'Quick rejected'),
            ]);

            $message = $mappedStatus === 'disetujui' 
                ? 'Jaspel berhasil disetujui'
                : 'Jaspel berhasil ditolak';
            
            Notification::make()
                ->title('✅ Success')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body('Validation failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function bulkValidateJaspel(Collection $records, string $status, ?string $comment = null): void
    {
        try {
            $count = $records->count();
            
            // Map status to database constraint values
            $mappedStatus = match($status) {
                'approved', 'disetujui' => 'disetujui',
                'rejected', 'ditolak' => 'ditolak',
                'pending' => 'pending',
                default => $status
            };
            
            foreach ($records as $record) {
                $record->update([
                    'status_validasi' => $mappedStatus,
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => $comment ?? "Bulk {$mappedStatus} by " . Auth::user()->name,
                ]);
            }

            $message = $mappedStatus === 'disetujui' 
                ? "Successfully approved {$count} Jaspel entries"
                : "Successfully rejected {$count} Jaspel entries";
            
            Notification::make()
                ->title('✅ Bulk Operation Complete')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Bulk Operation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status_validasi', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['admin', 'bendahara']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\JaspelManagementResource\Pages\ListJaspelManagement::route('/'),
            'create' => \App\Filament\Bendahara\Resources\JaspelManagementResource\Pages\CreateJaspelManagement::route('/create'),
            'view' => \App\Filament\Bendahara\Resources\JaspelManagementResource\Pages\ViewJaspelManagement::route('/{record}'),
            'edit' => \App\Filament\Bendahara\Resources\JaspelManagementResource\Pages\EditJaspelManagement::route('/{record}/edit'),
        ];
    }
}