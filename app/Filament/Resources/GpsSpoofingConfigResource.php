<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GpsSpoofingConfigResource\Pages;
use App\Filament\Resources\GpsSpoofingConfigResource\RelationManagers;
use App\Models\GpsSpoofingConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class GpsSpoofingConfigResource extends Resource
{
    protected static ?string $model = GpsSpoofingConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'GPS Security Settings';
    
    protected static ?string $modelLabel = 'GPS Security Setting';
    
    protected static ?string $pluralModelLabel = 'GPS Security Settings';
    
    protected static ?string $navigationGroup = 'Attendance';
    
    protected static ?int $navigationSort = 42;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Configuration')
                    ->tabs([
                        // Basic Settings Tab
                        Forms\Components\Tabs\Tab::make('🔧 Pengaturan Dasar')
                            ->schema([
                                Forms\Components\Section::make('Informasi Konfigurasi')
                                    ->schema([
                                        Forms\Components\TextInput::make('config_name')
                                            ->label('Nama Konfigurasi')
                                            ->required()
                                            ->maxLength(255)
                                            ->default('default')
                                            ->placeholder('e.g., Production Config'),
                                            
                                        Forms\Components\Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->rows(3)
                                            ->placeholder('Deskripsi singkat tentang konfigurasi ini'),
                                            
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Status Aktif')
                                            ->default(true)
                                            ->helperText('Hanya satu konfigurasi yang dapat aktif pada satu waktu'),
                                    ])
                                    ->columns(2),
                                    
                                Forms\Components\Section::make('Threshold Perjalanan')
                                    ->description('Pengaturan untuk mendeteksi perjalanan yang tidak mungkin')
                                    ->schema([
                                        Forms\Components\TextInput::make('max_travel_speed_kmh')
                                            ->label('Kecepatan Maksimal (km/h)')
                                            ->numeric()
                                            ->default(120)
                                            ->suffix('km/h')
                                            ->helperText('Kecepatan maksimal yang masih dianggap wajar'),
                                            
                                        Forms\Components\TextInput::make('min_time_diff_seconds')
                                            ->label('Selisih Waktu Minimum (detik)')
                                            ->numeric()
                                            ->default(300)
                                            ->suffix('detik')
                                            ->helperText('Waktu minimum antara dua lokasi check-in'),
                                            
                                        Forms\Components\TextInput::make('max_distance_km')
                                            ->label('Jarak Maksimal (km)')
                                            ->numeric()
                                            ->default(50)
                                            ->suffix('km')
                                            ->helperText('Jarak maksimal yang dapat ditempuh dalam periode waktu'),
                                    ])
                                    ->columns(3),
                                    
                                Forms\Components\Section::make('Threshold Akurasi GPS')
                                    ->description('Pengaturan akurasi GPS yang diterima')
                                    ->schema([
                                        Forms\Components\TextInput::make('min_gps_accuracy_meters')
                                            ->label('Akurasi Minimum (meter)')
                                            ->numeric()
                                            ->default(50)
                                            ->suffix('m')
                                            ->helperText('Akurasi GPS minimum yang diterima'),
                                            
                                        Forms\Components\TextInput::make('max_gps_accuracy_meters')
                                            ->label('Akurasi Maksimum (meter)')
                                            ->numeric()
                                            ->default(1000)
                                            ->suffix('m')
                                            ->helperText('Akurasi GPS maksimum yang masih diterima'),
                                    ])
                                    ->columns(2),
                            ]),
                            
                        // Risk Scoring Tab
                        Forms\Components\Tabs\Tab::make('⚖️ Skoring Risiko')
                            ->schema([
                                Forms\Components\Section::make('Bobot Deteksi (0-100)')
                                    ->description('Setel bobot untuk setiap metode deteksi')
                                    ->schema([
                                        Forms\Components\TextInput::make('mock_location_weight')
                                            ->label('Mock Location')
                                            ->numeric()
                                            ->default(40)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('fake_gps_app_weight')
                                            ->label('Fake GPS App')
                                            ->numeric()
                                            ->default(35)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('developer_mode_weight')
                                            ->label('Developer Mode')
                                            ->numeric()
                                            ->default(15)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('impossible_travel_weight')
                                            ->label('Impossible Travel')
                                            ->numeric()
                                            ->default(50)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('coordinate_anomaly_weight')
                                            ->label('Coordinate Anomaly')
                                            ->numeric()
                                            ->default(25)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('device_integrity_weight')
                                            ->label('Device Integrity')
                                            ->numeric()
                                            ->default(30)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                    ])
                                    ->columns(3),
                                    
                                Forms\Components\Section::make('Threshold Tingkat Risiko (0-100)')
                                    ->description('Tentukan ambang batas untuk setiap tingkat risiko')
                                    ->schema([
                                        Forms\Components\TextInput::make('low_risk_threshold')
                                            ->label('🟢 Risiko Rendah')
                                            ->numeric()
                                            ->default(20)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('medium_risk_threshold')
                                            ->label('🟡 Risiko Sedang')
                                            ->numeric()
                                            ->default(40)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('high_risk_threshold')
                                            ->label('🔴 Risiko Tinggi')
                                            ->numeric()
                                            ->default(70)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                            
                                        Forms\Components\TextInput::make('critical_risk_threshold')
                                            ->label('🚨 Risiko Kritis')
                                            ->numeric()
                                            ->default(85)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/100'),
                                    ])
                                    ->columns(4),
                            ]),
                            
                        // Auto Actions Tab
                        Forms\Components\Tabs\Tab::make('🤖 Tindakan Otomatis')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Tindakan Otomatis')
                                    ->description('Tentukan tindakan yang diambil secara otomatis berdasarkan tingkat risiko')
                                    ->schema([
                                        Forms\Components\Toggle::make('auto_block_critical')
                                            ->label('🚨 Auto Block - Risiko Kritis')
                                            ->default(true)
                                            ->helperText('Blokir otomatis untuk risiko kritis'),
                                            
                                        Forms\Components\Toggle::make('auto_block_high_risk')
                                            ->label('🔴 Auto Block - Risiko Tinggi')
                                            ->default(false)
                                            ->helperText('Blokir otomatis untuk risiko tinggi'),
                                            
                                        Forms\Components\Toggle::make('auto_flag_medium_risk')
                                            ->label('🟡 Auto Flag - Risiko Sedang')
                                            ->default(true)
                                            ->helperText('Tandai otomatis untuk risiko sedang'),
                                            
                                        Forms\Components\Toggle::make('auto_warning_low_risk')
                                            ->label('🟢 Auto Warning - Risiko Rendah')
                                            ->default(false)
                                            ->helperText('Peringatan otomatis untuk risiko rendah'),
                                    ])
                                    ->columns(2),
                                    
                                Forms\Components\Section::make('Pengaturan Lanjutan')
                                    ->schema([
                                        Forms\Components\TextInput::make('max_failed_attempts_per_hour')
                                            ->label('Maksimal Upaya Gagal per Jam')
                                            ->numeric()
                                            ->default(5)
                                            ->helperText('Maksimal upaya spoofing per jam sebelum diblokir'),
                                            
                                        Forms\Components\TextInput::make('temporary_block_duration_minutes')
                                            ->label('Durasi Blokir Sementara (menit)')
                                            ->numeric()
                                            ->default(30)
                                            ->suffix('menit')
                                            ->helperText('Durasi blokir sementara'),
                                            
                                        Forms\Components\Toggle::make('require_admin_review_for_unblock')
                                            ->label('Butuh Review Admin untuk Unblock')
                                            ->default(true)
                                            ->helperText('Memerlukan persetujuan admin untuk membuka blokir'),
                                    ])
                                    ->columns(3),
                            ]),
                            
                        // Detection Methods Tab
                        Forms\Components\Tabs\Tab::make('🔍 Metode Deteksi')
                            ->schema([
                                Forms\Components\Section::make('Toggle Metode Deteksi')
                                    ->description('Aktifkan atau nonaktifkan metode deteksi tertentu')
                                    ->schema([
                                        Forms\Components\Toggle::make('enable_mock_location_detection')
                                            ->label('📱 Mock Location Detection')
                                            ->default(true)
                                            ->helperText('Deteksi penggunaan mock location'),
                                            
                                        Forms\Components\Toggle::make('enable_fake_gps_detection')
                                            ->label('🗺️ Fake GPS App Detection')
                                            ->default(true)
                                            ->helperText('Deteksi aplikasi GPS palsu'),
                                            
                                        Forms\Components\Toggle::make('enable_developer_mode_detection')
                                            ->label('⚙️ Developer Mode Detection')
                                            ->default(true)
                                            ->helperText('Deteksi mode developer'),
                                            
                                        Forms\Components\Toggle::make('enable_impossible_travel_detection')
                                            ->label('🚀 Impossible Travel Detection')
                                            ->default(true)
                                            ->helperText('Deteksi perjalanan yang tidak mungkin'),
                                            
                                        Forms\Components\Toggle::make('enable_coordinate_anomaly_detection')
                                            ->label('📊 Coordinate Anomaly Detection')
                                            ->default(true)
                                            ->helperText('Deteksi anomali koordinat'),
                                            
                                        Forms\Components\Toggle::make('enable_device_integrity_check')
                                            ->label('🛡️ Device Integrity Check')
                                            ->default(true)
                                            ->helperText('Pemeriksaan integritas perangkat'),
                                    ])
                                    ->columns(2),
                            ]),
                            
                        // Monitoring Tab
                        Forms\Components\Tabs\Tab::make('📊 Monitoring')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Monitoring')
                                    ->schema([
                                        Forms\Components\TextInput::make('data_retention_days')
                                            ->label('Retensi Data (hari)')
                                            ->numeric()
                                            ->default(90)
                                            ->suffix('hari')
                                            ->helperText('Berapa lama data disimpan'),
                                            
                                        Forms\Components\TextInput::make('polling_interval_seconds')
                                            ->label('Interval Polling (detik)')
                                            ->numeric()
                                            ->default(15)
                                            ->suffix('detik')
                                            ->helperText('Seberapa sering sistem memeriksa'),
                                            
                                        Forms\Components\Toggle::make('enable_real_time_alerts')
                                            ->label('Alert Real-time')
                                            ->default(true)
                                            ->helperText('Aktifkan notifikasi real-time'),
                                    ])
                                    ->columns(3),
                                    
                                Forms\Components\Section::make('Notifikasi Email')
                                    ->schema([
                                        Forms\Components\Toggle::make('enable_email_notifications')
                                            ->label('Notifikasi Email')
                                            ->default(false)
                                            ->reactive()
                                            ->helperText('Aktifkan notifikasi via email'),
                                            
                                        Forms\Components\TextInput::make('notification_email')
                                            ->label('Email Notifikasi')
                                            ->email()
                                            ->visible(fn ($get) => $get('enable_email_notifications'))
                                            ->placeholder('admin@example.com')
                                            ->helperText('Email yang akan menerima notifikasi'),
                                    ])
                                    ->columns(2),
                            ]),
                            
                        // Device Management Tab
                        Forms\Components\Tabs\Tab::make('📱 Device Management')
                            ->schema([
                                Forms\Components\Section::make('Device Registration Settings')
                                    ->description('Configure how devices are automatically registered when employees check-in')
                                    ->schema([
                                        Forms\Components\Toggle::make('auto_register_devices')
                                            ->label('🔄 Auto-Register Devices')
                                            ->default(true)
                                            ->reactive()
                                            ->helperText('Automatically register devices when employees check-in or check-out'),
                                            
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('max_devices_per_user')
                                                    ->label('📏 Max Devices per User')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->maxValue(10)
                                                    ->suffix('devices')
                                                    ->helperText('Maximum number of devices allowed per user'),
                                                    
                                                Forms\Components\Select::make('device_limit_policy')
                                                    ->label('🛡️ Device Limit Policy')
                                                    ->options([
                                                        'strict' => '🔒 Strict - Enforce hard limit',
                                                        'warn' => '⚠️ Warn - Allow but notify admin',
                                                        'flexible' => '🔄 Flexible - Allow multiple devices',
                                                    ])
                                                    ->default('strict')
                                                    ->helperText('How to handle device limit violations'),
                                            ])
                                            ->visible(fn ($get) => $get('auto_register_devices')),
                                    ])
                                    ->columns(1),
                                    
                                Forms\Components\Section::make('Device Approval Settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('require_admin_approval_for_new_devices')
                                            ->label('👥 Require Admin Approval')
                                            ->default(false)
                                            ->reactive()
                                            ->helperText('New devices require admin approval before being activated'),
                                            
                                        Forms\Components\Toggle::make('auto_revoke_excess_devices')
                                            ->label('🚫 Auto-Revoke Excess Devices')
                                            ->default(true)
                                            ->helperText('Automatically revoke oldest device when limit is exceeded'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($get) => $get('auto_register_devices')),
                                    
                                Forms\Components\Section::make('Device Cleanup Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('device_auto_cleanup_days')
                                            ->label('🧹 Auto-Cleanup Period')
                                            ->numeric()
                                            ->default(30)
                                            ->minValue(1)
                                            ->maxValue(365)
                                            ->suffix('days')
                                            ->helperText('Automatically remove inactive devices after this many days'),
                                    ])
                                    ->visible(fn ($get) => $get('auto_register_devices')),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('config_name')
                    ->label('Nama Konfigurasi')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->description;
                    }),
                    
                Tables\Columns\TextColumn::make('detection_summary')
                    ->label('Metode Aktif')
                    ->getStateUsing(function ($record) {
                        $summary = $record->getDetectionSummary();
                        return $summary['total_methods'] . '/6 metode';
                    })
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('risk_thresholds')
                    ->label('Threshold Risiko')
                    ->getStateUsing(function ($record) {
                        return "L:{$record->low_risk_threshold} | M:{$record->medium_risk_threshold} | H:{$record->high_risk_threshold} | C:{$record->critical_risk_threshold}";
                    })
                    ->tooltip('Low | Medium | High | Critical'),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->placeholder('Semua Status'),
            ])
            ->actions([
                Action::make('activate')
                    ->label('✅ Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->action(function ($record) {
                        // Deactivate all other configs
                        GpsSpoofingConfig::where('is_active', true)->update(['is_active' => false]);
                        
                        // Activate this config
                        $record->update([
                            'is_active' => true,
                            'updated_by' => Auth::id(),
                        ]);
                        
                        Notification::make()
                            ->title('✅ Konfigurasi diaktifkan')
                            ->body('Konfigurasi "' . $record->config_name . '" sekarang aktif.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan Konfigurasi')
                    ->modalDescription('Apakah Anda yakin ingin mengaktifkan konfigurasi ini? Konfigurasi lain akan dinonaktifkan.'),
                    
                Action::make('view_summary')
                    ->label('📊 Ringkasan')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->action(function ($record) {
                        $summary = $record->getDetectionSummary();
                        
                        Notification::make()
                            ->title('📊 Ringkasan Konfigurasi')
                            ->body('Metode aktif: ' . implode(', ', $summary['enabled_methods']) . '. Skor maksimal: ' . $summary['max_possible_score'])
                            ->info()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                    
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn ($record) => !$record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($records) => $records && $records->every(fn ($record) => !$record->is_active)),
                ]),
            ])
            ->emptyStateHeading('🛡️ Belum Ada Konfigurasi GPS Security')
            ->emptyStateDescription('Buat konfigurasi pertama untuk mulai mengamankan sistem GPS.')
            ->emptyStateIcon('heroicon-o-shield-check');
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
            'index' => Pages\ListGpsSpoofingConfigs::route('/'),
            'create' => Pages\CreateGpsSpoofingConfig::route('/create'),
            'edit' => Pages\EditGpsSpoofingConfig::route('/{record}/edit'),
        ];
    }
}
