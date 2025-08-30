<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveTypeResource\Pages;
use App\Filament\Resources\LeaveTypeResource\RelationManagers;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationGroup = 'Leave Management';
    
    protected static ?string $navigationLabel = 'Jenis Cuti';
    
    protected static ?string $modelLabel = 'Jenis Cuti';
    
    protected static ?string $pluralModelLabel = 'Jenis Cuti';

    protected static ?int $navigationSort = 52;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jenis Cuti')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Jenis Cuti')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Cuti Tahunan, Sakit, Ibadah')
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\TextInput::make('alokasi_hari')
                            ->label('Alokasi Hari per Tahun')
                            ->numeric()
                            ->placeholder('Kosongkan jika tidak terbatas')
                            ->helperText('Jumlah hari yang dapat diambil pegawai per tahun. Kosongkan untuk tidak terbatas.')
                            ->minValue(0)
                            ->maxValue(365),
                            
                        Forms\Components\Toggle::make('active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Jenis cuti yang tidak aktif tidak akan muncul dalam pilihan pegawai'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Keterangan tambahan tentang jenis cuti ini...')
                            ->rows(3)
                            ->maxLength(500),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Jenis Cuti')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-calendar-days'),
                    
                Tables\Columns\TextColumn::make('alokasi_hari')
                    ->label('Alokasi/Tahun')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' hari' : 'Tidak Terbatas')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'success'),
                    
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->beforeStateUpdated(function ($record, $state) {
                        // Add confirmation for deactivating
                        if (!$state && $record->permohonanCutis()->where('status', 'Menunggu')->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Peringatan')
                                ->body('Masih ada permohonan cuti menunggu dengan jenis ini')
                                ->send();
                        }
                    }),
                    
                Tables\Columns\TextColumn::make('permohonan_cutis_count')
                    ->label('Total Digunakan')
                    ->counts('permohonanCutis')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->wrap()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                    
                Tables\Filters\Filter::make('has_allocation')
                    ->label('Memiliki Alokasi')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('alokasi_hari'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_usage')
                    ->label('Lihat Penggunaan')
                    ->icon('heroicon-m-chart-bar')
                    ->color('info')
                    ->modalHeading('Statistik Penggunaan Jenis Cuti')
                    ->modalContent(fn (LeaveType $record) => view('filament.modals.leave-type-usage', ['record' => $record]))
                    ->modalWidth('2xl'),
                    
                Tables\Actions\EditAction::make()
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $hasActiveRequests = $records->filter(function ($record) {
                                return $record->permohonanCutis()->where('status', 'Menunggu')->exists();
                            });
                            
                            if ($hasActiveRequests->count() > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Tidak Dapat Menghapus')
                                    ->body('Beberapa jenis cuti masih memiliki permohonan yang menunggu')
                                    ->send();
                                    
                                throw new \Exception('Cannot delete leave types with pending requests');
                            }
                        }),
                ]),
            ])
            ->defaultSort('nama');
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
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}