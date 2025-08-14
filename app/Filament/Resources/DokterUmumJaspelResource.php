<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokterUmumJaspelResource\Pages;
use App\Models\DokterUmumJaspel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DokterUmumJaspelResource extends Resource
{
    protected static ?string $model = DokterUmumJaspel::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = '💰 FINANSIAL MANAGEMENT';
    protected static ?string $navigationLabel = 'Manajemen JP Dokter Umum';
    protected static ?int $navigationSort = 70;
    protected static ?string $modelLabel = 'JP Dokter Umum';
    protected static ?string $pluralModelLabel = 'JP Dokter Umum';
    protected static ?string $recordTitleAttribute = 'jenis_shift';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🩺 Formula Jasa Pelayanan Dokter Umum')
                    ->description('Atur formula perhitungan jasa pelayanan berdasarkan shift dan jenis pasien')
                    ->schema([
                        Forms\Components\Select::make('jenis_shift')
                            ->label('Jenis Shift')
                            ->options(DokterUmumJaspel::getShiftOptions())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Setiap shift hanya bisa diatur sekali')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('ambang_pasien')
                            ->label('Ambang Pasien (Threshold)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('pasien')
                            ->helperText('Contoh: Setelah pasien ke-10, fee baru dihitung')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('fee_pasien_umum')
                            ->label('Fee per Pasien Umum')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->maxValue(1000000)
                            ->step(1000)
                            ->placeholder('7000')
                            ->helperText('Fee yang diterima per pasien umum')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('fee_pasien_bpjs')
                            ->label('Fee per Pasien BPJS')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->maxValue(1000000)
                            ->step(1000)
                            ->placeholder('5000')
                            ->helperText('Fee yang diterima per pasien BPJS')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('uang_duduk')
                            ->label('Uang Duduk')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->maxValue(1000000)
                            ->step(1000)
                            ->default(0)
                            ->placeholder('50000')
                            ->helperText('Uang duduk dasar untuk shift (tetap tanpa melihat jumlah pasien)')
                            ->columnSpan(1),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Toggle::make('status_aktif')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->onIcon('heroicon-o-check-circle')
                                    ->offIcon('heroicon-o-x-circle')
                                    ->inline(false)
                                    ->reactive()
                                    ->helperText(fn ($get) => $get('status_aktif') 
                                        ? '🟢 AKTIF - Formula akan digunakan dalam perhitungan' 
                                        : '🔴 NONAKTIF - Formula tidak akan digunakan dalam perhitungan'),
                                
                                Forms\Components\View::make('components.status-toggle-indicator')
                                    ->viewData(fn ($get) => ['status_aktif' => $get('status_aktif')])
                                    ->extraAttributes(['class' => 'mt-2'])
                            ])
                            ->extraAttributes(['class' => 'status-toggle-container'])
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('📝 Informasi Tambahan')
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Catatan atau penjelasan tambahan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shift_display')
                    ->getStateUsing(fn ($record) => $record->shift_display)
                    ->label('Jenis Shift')
                    ->badge()
                    ->color(fn ($record) => $record->shift_badge_color)
                    ->sortable(['jenis_shift'])
                    ->searchable(['jenis_shift']),

                Tables\Columns\TextColumn::make('ambang_pasien')
                    ->label('Threshold')
                    ->suffix(' pasien')
                    ->alignCenter()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fee_umum_formatted')
                    ->getStateUsing(fn ($record) => $record->fee_umum_formatted)
                    ->label('Fee Umum')
                    ->alignEnd()
                    ->color('primary')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('fee_bpjs_formatted')
                    ->getStateUsing(fn ($record) => $record->fee_bpjs_formatted)
                    ->label('Fee BPJS')
                    ->alignEnd()
                    ->color('info')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('uang_duduk_formatted')
                    ->getStateUsing(fn ($record) => $record->uang_duduk_formatted)
                    ->label('Uang Duduk')
                    ->alignEnd()
                    ->color('success')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('status_text')
                    ->getStateUsing(fn ($record) => $record->status_text)
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_badge_color)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_shift')
                    ->label('Shift')
                    ->options(DokterUmumJaspel::getShiftOptions())
                    ->placeholder('Semua Shift'),

                Tables\Filters\TernaryFilter::make('status_aktif')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->status_aktif ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn ($record) => $record->status_aktif ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->status_aktif ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->status_aktif ? 'Nonaktifkan' : 'Aktifkan') . ' Formula JP')
                    ->modalDescription(fn ($record) => 'Apakah Anda yakin ingin ' . 
                        ($record->status_aktif ? 'menonaktifkan' : 'mengaktifkan') . 
                        ' formula JP untuk shift ' . $record->jenis_shift . '?')
                    ->action(function ($record) {
                        $record->update([
                            'status_aktif' => !$record->status_aktif,
                            'updated_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status berhasil diubah!')
                            ->body('Formula JP ' . $record->jenis_shift . ' telah ' . 
                                ($record->status_aktif ? 'diaktifkan' : 'dinonaktifkan'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Formula'),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus Formula'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan Terpilih')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status_aktif' => true,
                                    'updated_by' => auth()->id(),
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Formula berhasil diaktifkan!')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan Terpilih')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status_aktif' => false,
                                    'updated_by' => auth()->id(),
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Formula berhasil dinonaktifkan!')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('jenis_shift')
            ->striped()
            ->emptyStateHeading('🩺 Belum Ada Formula JP')
            ->emptyStateDescription('Klik tombol "Tambah Formula" untuk mengatur jasa pelayanan dokter umum.')
            ->emptyStateIcon('heroicon-o-calculator');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDokterUmumJaspels::route('/'),
            'create' => Pages\CreateDokterUmumJaspel::route('/create'),
            'edit' => Pages\EditDokterUmumJaspel::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}