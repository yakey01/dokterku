<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCardResource\Pages;
use App\Models\EmployeeCard;
use App\Models\Pegawai;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeCardResource extends Resource
{
    protected static ?string $model = EmployeeCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Kartu Pegawai';
    protected static ?int $navigationSort = 11;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $modelLabel = 'Kartu Pegawai';
    protected static ?string $pluralModelLabel = 'Kartu Pegawai';
    protected static ?string $recordTitleAttribute = 'employee_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🆔 Informasi Kartu')
                    ->description('Informasi dasar kartu pegawai')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('👤 Pegawai')
                            ->relationship('pegawai', 'nama_lengkap')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $pegawai = Pegawai::with('user.role')->find($state);
                                    if ($pegawai) {
                                        $set('employee_name', $pegawai->nama_lengkap);
                                        $set('employee_id', $pegawai->nik);
                                        $set('position', $pegawai->jabatan);
                                        $set('department', $pegawai->jenis_pegawai);
                                        $set('photo_path', $pegawai->foto);
                                        
                                        if ($pegawai->user) {
                                            $set('user_id', $pegawai->user->id);
                                            $set('join_date', $pegawai->user->tanggal_bergabung);
                                            $set('role_name', $pegawai->user->role?->display_name);
                                        }
                                    }
                                }
                            }),
                        
                        Forms\Components\TextInput::make('card_number')
                            ->label('🔢 Nomor Kartu')
                            ->default(fn () => EmployeeCard::generateCardNumber())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\Select::make('card_type')
                            ->label('📋 Jenis Kartu')
                            ->options(EmployeeCard::getCardTypes())
                            ->default('standard')
                            ->required(),
                        
                        Forms\Components\Select::make('design_template')
                            ->label('🎨 Template Desain')
                            ->options(EmployeeCard::getCardTemplates())
                            ->default('default')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('👤 Data Pegawai')
                    ->description('Informasi pegawai yang akan ditampilkan di kartu')
                    ->schema([
                        Forms\Components\TextInput::make('employee_name')
                            ->label('📝 Nama Lengkap')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\TextInput::make('employee_id')
                            ->label('🆔 NIK/NIP')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\TextInput::make('position')
                            ->label('💼 Jabatan')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\TextInput::make('department')
                            ->label('🏢 Departemen')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\TextInput::make('role_name')
                            ->label('👔 Role')
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\DatePicker::make('join_date')
                            ->label('📅 Tanggal Bergabung')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('📋 Validitas Kartu')
                    ->description('Pengaturan masa berlaku kartu')
                    ->schema([
                        Forms\Components\DatePicker::make('issued_date')
                            ->label('📅 Tanggal Terbit')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('⏰ Berlaku Hingga')
                            ->helperText('Kosongkan jika tidak ada masa berlaku'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('✅ Status Aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('📁 File & Metadata')
                    ->description('File kartu dan informasi tambahan')
                    ->schema([
                        Forms\Components\Placeholder::make('pdf_info')
                            ->label('📄 File PDF')
                            ->content(fn ($record) => $record?->pdf_path ? 
                                'File tersimpan: ' . basename($record->pdf_path) : 
                                'Belum ada file PDF')
                            ->visible(fn ($record) => $record !== null),
                        
                        Forms\Components\Placeholder::make('print_info')
                            ->label('🖨️ Informasi Cetak')
                            ->content(fn ($record) => $record ? 
                                "Dicetak: {$record->print_count} kali" . 
                                ($record->printed_at ? " | Terakhir: {$record->printed_at->format('d M Y H:i')}" : '') :
                                'Belum pernah dicetak')
                            ->visible(fn ($record) => $record !== null),
                        
                        Forms\Components\Textarea::make('card_data')
                            ->label('📝 Data Tambahan (JSON)')
                            ->helperText('Data tambahan dalam format JSON')
                            ->columnSpanFull()
                            ->rows(3),
                    ])
                    ->columns(2)
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('pegawai.foto')
                    ->label('📸 Foto')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(fn ($record) => $record->photo_url),
                
                Tables\Columns\TextColumn::make('employee_name')
                    ->label('👤 Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('card_number')
                    ->label('🔢 Nomor Kartu')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\BadgeColumn::make('card_type')
                    ->label('📋 Jenis')
                    ->getStateUsing(fn ($record) => $record->formatted_type)
                    ->color(fn ($record) => $record->type_badge_color),
                
                Tables\Columns\TextColumn::make('position')
                    ->label('💼 Jabatan')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('department')
                    ->label('🏢 Departemen')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('✅ Status')
                    ->getStateUsing(fn ($record) => $record->status_text)
                    ->color(fn ($record) => $record->status_badge_color),
                
                Tables\Columns\TextColumn::make('issued_date')
                    ->label('📅 Tanggal Terbit')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('⏰ Berlaku Hingga')
                    ->getStateUsing(fn ($record) => $record->expiry_status)
                    ->color(function ($record) {
                        if ($record->isExpired()) return 'danger';
                        if ($record->days_until_expiry && $record->days_until_expiry <= 30) return 'warning';
                        return 'success';
                    })
                    ->badge(),
                
                Tables\Columns\TextColumn::make('print_count')
                    ->label('🖨️ Cetak')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('👤 Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('card_type')
                    ->label('Jenis Kartu')
                    ->options(EmployeeCard::getCardTypes()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->expired())
                    ->toggle(),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Akan Expire (30 hari)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('valid_until')
                              ->whereBetween('valid_until', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    )
                    ->toggle(),
            ])
            ->actions([
                Action::make('generate_card')
                    ->label('🎨 Generate')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->action(function ($record) {
                        $service = app(\App\Services\CardGenerationService::class);
                        $result = $service->generateCard($record);
                        
                        if ($result['success']) {
                            $record->update([
                                'pdf_path' => $result['pdf_path'],
                                'generated_at' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('🎨 Kartu berhasil digenerate!')
                                ->body('File PDF telah dibuat dan tersimpan.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('❌ Gagal generate kartu')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => !$record->pdf_path),
                
                Action::make('download_card')
                    ->label('📥 Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => route('employee-card.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->pdf_path && Storage::exists($record->pdf_path)),
                
                Action::make('print_card')
                    ->label('🖨️ Print')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function ($record) {
                        $record->markAsPrinted();
                        
                        Notification::make()
                            ->title('🖨️ Kartu ditandai sebagai dicetak')
                            ->body("Total cetak: {$record->print_count} kali")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->pdf_path),
                
                Tables\Actions\EditAction::make()
                    ->label('✏️ Edit'),
                
                Tables\Actions\DeleteAction::make()
                    ->label('🗑️ Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_generate')
                        ->label('🎨 Generate Kartu')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('primary')
                        ->action(function ($records) {
                            $service = app(\App\Services\CardGenerationService::class);
                            $successCount = 0;
                            
                            foreach ($records as $record) {
                                $result = $service->generateCard($record);
                                if ($result['success']) {
                                    $record->update([
                                        'pdf_path' => $result['pdf_path'],
                                        'generated_at' => now(),
                                    ]);
                                    $successCount++;
                                }
                            }
                            
                            Notification::make()
                                ->title("🎨 {$successCount} kartu berhasil digenerate!")
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('🆔 Belum Ada Kartu Pegawai')
            ->emptyStateDescription('Klik tombol "Buat Kartu Baru" untuk membuat kartu pegawai pertama.')
            ->emptyStateIcon('heroicon-o-identification');
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
            'index' => Pages\ListEmployeeCards::route('/'),
            'create' => Pages\CreateEmployeeCard::route('/create'),
            'edit' => Pages\EditEmployeeCard::route('/{record}/edit'),
        ];
    }
}
