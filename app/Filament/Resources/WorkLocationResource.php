<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkLocationResource\Pages;
use App\Filament\Resources\WorkLocationResource\RelationManagers;
use App\Models\WorkLocation;
use App\Services\WorkLocationDeletionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Toggle;
use Filament\Support\Exceptions\Halt;

class WorkLocationResource extends Resource
{
    protected static ?string $model = WorkLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationLabel = 'Validasi Lokasi (Geofencing)';
    
    protected static ?string $modelLabel = 'Lokasi Kerja';
    
    protected static ?string $pluralModelLabel = 'Lokasi Kerja';
    
    protected static ?string $navigationGroup = '📍 PRESENSI';
    
    protected static ?int $navigationSort = 41;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🏢 Informasi Lokasi')
                    ->description('Konfigurasi dasar lokasi kerja')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Lokasi')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Kantor Pusat Jakarta'),

                                Forms\Components\Select::make('location_type')
                                    ->label('Jenis Lokasi')
                                    ->required()
                                    ->options([
                                        'main_office' => '🏢 Kantor Pusat',
                                        'branch_office' => '🏪 Kantor Cabang',
                                        'project_site' => '🚧 Lokasi Proyek',
                                        'mobile_location' => '📱 Lokasi Mobile',
                                        'client_office' => '🤝 Kantor Klien',
                                    ])
                                    ->default('main_office')
                                    ->native(false),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('unit_kerja')
                                    ->label('Unit Kerja')
                                    ->placeholder('Contoh: IGD, Poli Umum, dll')
                                    ->helperText('Unit kerja yang menggunakan lokasi ini')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Contact Person')
                                    ->placeholder('Nama penanggung jawab lokasi')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi detail lokasi kerja...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->required()
                            ->placeholder('Masukkan alamat lengkap lokasi...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('📍 Koordinat GPS & Geofencing')
                    ->description('Pilih lokasi pada peta OSM dengan GPS detection')
                    ->schema([
                        ViewField::make('osm_map')
                            ->view('filament.forms.components.leaflet-osm-map')
                            ->label('📍 Pilih Lokasi pada Peta OSM')
                            ->columnSpanFull()
                            ->dehydrated(false), // Don't save this field to database

                        // Tombol Get Location yang prominent
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('getLocation')
                                ->label('🌍 Get My Location')
                                ->icon('heroicon-o-map-pin')
                                ->color('success')
                                ->size('lg')
                                ->extraAttributes([
                                    'class' => 'w-full',
                                    'id' => 'get-location-btn',
                                    'onclick' => 'autoDetectLocation()'
                                ])
                                ->action(function () {
                                    // This will be handled by JavaScript
                                })
                                ->tooltip('Deteksi lokasi GPS Anda secara otomatis')
                        ])
                        ->fullWidth()
                        ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('📍 Latitude (Lintang)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('Contoh: -6.2088200 (Jakarta)')
                                    ->helperText('Koordinat lintang - sinkron dengan peta otomatis')
                                    ->reactive()
                                    ->live(onBlur: true)
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'between:-90,90'
                                    ])
                                    ->suffixIcon('heroicon-o-globe-alt')
                                    ->id('latitude')
                                    ->extraAttributes(['data-coordinate-field' => 'latitude'])
                                    ->afterStateUpdated(function (callable $get, callable $set, $state): void {
                                        $lat = $get('latitude');
                                        $lng = $get('longitude');
                                        
                                        // Validate latitude range
                                        if ($lat && ($lat < -90 || $lat > 90)) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Latitude Tidak Valid')
                                                ->body('Latitude harus berada dalam rentang -90 hingga 90 derajat.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        // Update map if both coordinates are valid
                                        if ($lat && $lng && is_numeric($lat) && is_numeric($lng)) {
                                            // Map will automatically update via Alpine.js form sync
                                            // The leaflet-osm-map component listens to input changes
                                        }
                                    }),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('🌐 Longitude (Bujur)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('Contoh: 106.8238800 (Jakarta)')
                                    ->helperText('Koordinat bujur - sinkron dengan peta otomatis')
                                    ->reactive()
                                    ->live(onBlur: true)
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'between:-180,180'
                                    ])
                                    ->suffixIcon('heroicon-o-globe-alt')
                                    ->id('longitude')
                                    ->extraAttributes(['data-coordinate-field' => 'longitude'])
                                    ->afterStateUpdated(function (callable $get, callable $set, $state): void {
                                        $lat = $get('latitude');
                                        $lng = $get('longitude');
                                        
                                        // Validate longitude range
                                        if ($lng && ($lng < -180 || $lng > 180)) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Longitude Tidak Valid')
                                                ->body('Longitude harus berada dalam rentang -180 hingga 180 derajat.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        // Update map if both coordinates are valid
                                        if ($lat && $lng && is_numeric($lat) && is_numeric($lng)) {
                                            // Map will automatically update via Alpine.js form sync
                                            // The leaflet-osm-map component listens to input changes
                                        }
                                    })
                                    ->suffixActions([
                                        Forms\Components\Actions\Action::make('openMaps')
                                            ->label('Google Maps')
                                            ->icon('heroicon-o-map')
                                            ->color('success')
                                            ->size('sm')
                                            ->url(fn ($get) => $get('latitude') && $get('longitude') 
                                                ? "https://maps.google.com/maps?q={$get('latitude')},{$get('longitude')}" 
                                                : 'https://maps.google.com')
                                            ->openUrlInNewTab()
                                            ->tooltip('Lihat di Google Maps'),
                                        Forms\Components\Actions\Action::make('copyCoords')
                                            ->label('Copy')
                                            ->icon('heroicon-o-clipboard')
                                            ->color('gray')
                                            ->size('sm')
                                            ->action(function ($get) {
                                                $lat = $get('latitude');
                                                $lng = $get('longitude');
                                                if ($lat && $lng) {
                                                    $coords = "{$lat},{$lng}";
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Koordinat Disalin!')
                                                        ->body("Koordinat: {$coords}")
                                                        ->success()
                                                        ->send();
                                                }
                                            })
                                            ->tooltip('Salin koordinat'),
                                    ]),

                                Forms\Components\TextInput::make('radius_meters')
                                    ->label('Radius Geofence (meter)')
                                    ->required()
                                    ->numeric()
                                    ->default(100)
                                    ->minValue(10)
                                    ->maxValue(1000)
                                    ->suffix('meter')
                                    ->helperText('Area valid untuk absensi (10-1000m)'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('gps_accuracy_required')
                                    ->label('Akurasi GPS Minimum')
                                    ->required()
                                    ->numeric()
                                    ->default(20)
                                    ->minValue(5)
                                    ->maxValue(100)
                                    ->suffix('meter')
                                    ->helperText('Akurasi GPS minimum yang diperlukan'),

                                Forms\Components\Toggle::make('strict_geofence')
                                    ->label('Geofence Ketat')
                                    ->default(true)
                                    ->helperText('Apakah geofence harus ketat atau fleksibel'),
                            ]),

                        Forms\Components\Placeholder::make('location_tips')
                            ->label('💡 Tips Penggunaan Peta:')
                            ->content(new \Illuminate\Support\HtmlString('<div class="location-tips-content">
                                • 🌍 <strong>Auto-Detection:</strong> Lokasi akan terdeteksi otomatis saat halaman dimuat<br>
                                • 🌐 Klik tombol "Get My Location" untuk deteksi ulang GPS<br>
                                • 🖱️ Klik pada peta untuk memindahkan marker ke lokasi yang diinginkan<br>
                                • ↕️ Drag marker pada peta untuk mengubah posisi secara manual<br>
                                • 🔍 Zoom in/out dengan scroll mouse atau kontrol peta<br>
                                • ✏️ Field latitude dan longitude dapat diedit manual jika diperlukan<br>
                                • 🔄 Koordinat akan sinkron otomatis antara peta dan form fields
                            </div>'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('⚙️ Shift & Jam Kerja')
                    ->description('Konfigurasi shift yang diizinkan di lokasi kerja ini')
                    ->schema([
                        Forms\Components\CheckboxList::make('allowed_shifts')
                            ->label('Shift yang Diizinkan')
                            ->options([
                                'Pagi' => '🌅 Shift Pagi (08:00-14:00)',
                                'Siang' => '☀️ Shift Siang (14:00-20:00)',
                                'Malam' => '🌙 Shift Malam (20:00-08:00)',
                            ])
                            ->descriptions([
                                'Pagi' => 'Shift pagi untuk operasional normal',
                                'Siang' => 'Shift siang untuk layanan sore',
                                'Malam' => 'Shift malam untuk keamanan/emergency',
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes(['class' => 'mb-6'])
                    ->collapsible()
                    ->collapsed(true),

                Forms\Components\Section::make('⏱️ Pengaturan Toleransi Waktu')
                    ->description('Konfigurasi toleransi waktu check-in dan check-out untuk fleksibilitas presensi')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('late_tolerance_minutes')
                                    ->label('⏰ Toleransi Keterlambatan Check-in')
                                    ->numeric()
                                    ->default(15)
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->suffix('menit')
                                    ->helperText('Berapa menit setelah waktu shift dimulai, pegawai masih bisa check-in tanpa dianggap terlambat')
                                    ->live()
                                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                        // Update helper text based on value
                                        $description = match(true) {
                                            $state == 0 => '⚡ Tidak ada toleransi - harus tepat waktu',
                                            $state <= 5 => '🟢 Toleransi ketat - disiplin tinggi',
                                            $state <= 15 => '🟡 Toleransi normal - standar perusahaan',
                                            $state <= 30 => '🟠 Toleransi longgar - fleksibel',
                                            default => '🔴 Toleransi sangat longgar - perlu review'
                                        };
                                    }),

                                Forms\Components\TextInput::make('early_departure_tolerance_minutes')
                                    ->label('🏃 Toleransi Check-out Lebih Awal')
                                    ->numeric()
                                    ->default(15)
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->suffix('menit')
                                    ->helperText('Berapa menit sebelum waktu shift berakhir, pegawai sudah bisa check-out'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('checkin_before_shift_minutes')
                                    ->label('📅 Check-in Sebelum Shift')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(5)
                                    ->maxValue(120)
                                    ->suffix('menit')
                                    ->helperText('Berapa menit sebelum shift dimulai, pegawai sudah bisa check-in'),

                                Forms\Components\TextInput::make('checkout_after_shift_minutes')
                                    ->label('⏳ Batas Check-out Setelah Shift')
                                    ->numeric()
                                    ->default(60)
                                    ->minValue(15)
                                    ->maxValue(180)
                                    ->suffix('menit')
                                    ->helperText('Berapa menit setelah shift berakhir, sistem masih menerima check-out'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('break_time_minutes')
                                    ->label('☕ Durasi Istirahat Standar')
                                    ->numeric()
                                    ->default(60)
                                    ->minValue(15)
                                    ->maxValue(120)
                                    ->suffix('menit')
                                    ->helperText('Durasi istirahat standar untuk perhitungan jam kerja efektif'),

                                Forms\Components\TextInput::make('overtime_threshold_minutes')
                                    ->label('💼 Batas Jam Kerja Normal')
                                    ->numeric()
                                    ->default(480)
                                    ->minValue(420)
                                    ->maxValue(600)
                                    ->suffix('menit')
                                    ->helperText('Batas jam kerja normal sebelum dianggap lembur (8 jam = 480 menit)'),
                            ]),

                        // Tolerance Preview/Calculator
                        Forms\Components\Placeholder::make('tolerance_preview')
                            ->label('📊 Preview Pengaturan Toleransi')
                            ->content(function (callable $get) {
                                $late = $get('late_tolerance_minutes') ?? 15;
                                $early = $get('early_departure_tolerance_minutes') ?? 15;
                                $before = $get('checkin_before_shift_minutes') ?? 30;
                                $after = $get('checkout_after_shift_minutes') ?? 60;
                                
                                return new \Illuminate\Support\HtmlString("
                                    <div class='tolerance-preview space-y-3 p-4 bg-gray-50 rounded-lg'>
                                        <h4 class='font-semibold text-gray-800 mb-3'>💡 Contoh untuk Shift Pagi (08:00-16:00):</h4>
                                        <div class='grid grid-cols-2 gap-4 text-sm'>
                                            <div class='bg-blue-50 p-3 rounded border-l-4 border-blue-400'>
                                                <strong class='text-blue-700'>📥 Check-in:</strong><br>
                                                • Bisa check-in dari: <code>07:" . sprintf('%02d', 60 - $before) . "</code><br>
                                                • Dianggap tepat waktu sampai: <code>08:" . sprintf('%02d', $late) . "</code><br>
                                                • Setelah itu: terlambat
                                            </div>
                                            <div class='bg-green-50 p-3 rounded border-l-4 border-green-400'>
                                                <strong class='text-green-700'>📤 Check-out:</strong><br>
                                                • Bisa check-out mulai: <code>15:" . sprintf('%02d', 60 - $early) . "</code><br>
                                                • Shift berakhir: <code>16:00</code><br>
                                                • Batas akhir check-out: <code>17:" . sprintf('%02d', $after) . "</code>
                                            </div>
                                        </div>
                                        <div class='mt-3 p-2 bg-yellow-50 rounded text-xs text-yellow-800'>
                                            <strong>⚠️ Catatan:</strong> Pengaturan ini berlaku untuk semua shift di lokasi ini. Pastikan sesuai dengan kebijakan perusahaan.
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('tolerance_tips')
                            ->label('💡 Tips Pengaturan Toleransi:')
                            ->content(new \Illuminate\Support\HtmlString('<div class="tolerance-tips-content text-sm space-y-2">
                                <div class="flex items-start space-x-2">
                                    <span class="text-green-600">✅</span>
                                    <span><strong>Toleransi Keterlambatan:</strong> 15 menit adalah standar umum perusahaan</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="text-blue-600">📋</span>
                                    <span><strong>Check-in Awal:</strong> 30 menit sebelum shift memungkinkan persiapan</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="text-orange-600">⏰</span>
                                    <span><strong>Check-out Awal:</strong> 15 menit untuk finishing pekerjaan</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="text-purple-600">🔄</span>
                                    <span><strong>Batas Check-out:</strong> 60 menit untuk handling situasi darurat</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="text-red-600">⚠️</span>
                                    <span><strong>Penting:</strong> Toleransi terlalu longgar dapat mengurangi disiplin kerja</span>
                                </div>
                            </div>'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('📍 Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('location_type')
                    ->label('Jenis Lokasi')
                    ->badge()
                    ->colors([
                        'primary' => 'main_office',
                        'success' => 'branch_office',
                        'warning' => 'project_site',
                        'info' => 'mobile_location',
                        'secondary' => 'client_office',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'main_office' => '🏢 Kantor Pusat',
                        'branch_office' => '🏪 Kantor Cabang',
                        'project_site' => '🚧 Lokasi Proyek',
                        'mobile_location' => '📱 Lokasi Mobile',
                        'client_office' => '🤝 Kantor Klien',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address)
                    ->searchable(),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('📍 Koordinat')
                    ->formatStateUsing(fn ($record) => 
                        number_format($record->latitude, 6) . ', ' . number_format($record->longitude, 6)
                    )
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_radius')
                    ->label('🎯 Radius')
                    ->color('warning')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('tolerance_info')
                    ->label('⏱️ Toleransi')
                    ->formatStateUsing(function ($record) {
                        $late = $record->late_tolerance_minutes ?? 15;
                        $early = $record->early_departure_tolerance_minutes ?? 15;
                        return "📥 {$late}m | 📤 {$early}m";
                    })
                    ->tooltip(function ($record) {
                        $late = $record->late_tolerance_minutes ?? 15;
                        $early = $record->early_departure_tolerance_minutes ?? 15;
                        $before = $record->checkin_before_shift_minutes ?? 30;
                        $after = $record->checkout_after_shift_minutes ?? 60;
                        return "Check-in: {$late} menit setelah shift\nCheck-out: {$early} menit sebelum shift\nCheck-in awal: {$before} menit\nBatas akhir: {$after} menit";
                    })
                    ->color('info')
                    ->weight('medium')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('require_photo')
                    ->label('📸 Foto')
                    ->boolean()
                    ->trueIcon('heroicon-o-camera')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('strict_geofence')
                    ->label('🛡️ Ketat')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('✅ Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(fn ($record) => $record->trashed())
                    ->tooltip(fn ($record) => $record->trashed() ? 'Cannot toggle status of deleted location' : 'Click to toggle active status')
                    ->updateStateUsing(function ($record, $state) {
                        // Prevent updates to soft-deleted records
                        if ($record->trashed()) {
                            \Filament\Notifications\Notification::make()
                                ->title('⚠️ Cannot Update Deleted Location')
                                ->body('This location has been deleted. Please restore it first to change the status.')
                                ->warning()
                                ->duration(5000)
                                ->send();
                                
                            return $record->is_active; // Return original state
                        }
                        
                        // Update the record normally for non-deleted records
                        $record->update(['is_active' => $state]);
                        
                        // Send success notification
                        \Filament\Notifications\Notification::make()
                            ->title($state ? '✅ Location Activated' : '❌ Location Deactivated')
                            ->body('Status updated successfully.')
                            ->color($state ? 'success' : 'warning')
                            ->duration(3000)
                            ->send();
                            
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('👤 Penanggung Jawab')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('🗑️ Dihapus')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Aktif')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->color(fn ($record) => $record->deleted_at ? 'danger' : 'success')
                    ->badge(fn ($record) => $record->deleted_at ? 'Deleted' : 'Active')
                    ->tooltip(fn ($record) => $record->deleted_at 
                        ? 'Location deleted on ' . $record->deleted_at->format('M j, Y g:i A')
                        : 'Location is active and available'),

                Tables\Columns\TextColumn::make('record_status')
                    ->label('📊 Status')
                    ->formatStateUsing(function ($record) {
                        if ($record->trashed()) {
                            return '🗑️ Deleted';
                        }
                        
                        return $record->is_active ? '✅ Active' : '❌ Inactive';
                    })
                    ->color(function ($record) {
                        if ($record->trashed()) {
                            return 'danger';
                        }
                        
                        return $record->is_active ? 'success' : 'warning';
                    })
                    ->badge()
                    ->sortable(false)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('trashed')
                    ->label('Record Status')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->onlyTrashed())
                    ->indicator('Deleted Records Only'),

                Tables\Filters\TernaryFilter::make('record_state')
                    ->label('Record State')
                    ->placeholder('All Records')
                    ->trueLabel('Active Records Only')
                    ->falseLabel('Deleted Records Only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('deleted_at'),
                        false: fn (Builder $query) => $query->onlyTrashed(),
                        blank: fn (Builder $query) => $query->withTrashed(),
                    ),

                Tables\Filters\SelectFilter::make('location_type')
                    ->label('Jenis Lokasi')
                    ->options([
                        'main_office' => '🏢 Kantor Pusat',
                        'branch_office' => '🏪 Kantor Cabang',
                        'project_site' => '🚧 Lokasi Proyek',
                        'mobile_location' => '📱 Lokasi Mobile',
                        'client_office' => '🤝 Kantor Klien',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\TernaryFilter::make('require_photo')
                    ->label('Wajib Foto')
                    ->boolean()
                    ->trueLabel('Wajib Foto')
                    ->falseLabel('Tidak Wajib'),

                Tables\Filters\TernaryFilter::make('strict_geofence')
                    ->label('Geofence Ketat')
                    ->boolean()
                    ->trueLabel('Ketat')
                    ->falseLabel('Fleksibel'),

                Tables\Filters\Filter::make('radius_range')
                    ->label('Rentang Radius')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('radius_min')
                                    ->label('Radius Minimum (m)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('radius_max')
                                    ->label('Radius Maximum (m)')
                                    ->numeric(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['radius_min'], fn ($q, $min) => $q->where('radius_meters', '>=', $min))
                            ->when($data['radius_max'], fn ($q, $max) => $q->where('radius_meters', '<=', $max));
                    }),
            ])
            ->actions([
                Action::make('view_map')
                    ->label('🗺️ Lihat Peta')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn ($record) => $record->google_maps_url)
                    ->openUrlInNewTab(),

                Action::make('test_geofence')
                    ->label('🎯 Test Geofence')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('test_latitude')
                                    ->label('Test Latitude')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001),
                                Forms\Components\TextInput::make('test_longitude')
                                    ->label('Test Longitude')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001),
                            ]),
                        Forms\Components\TextInput::make('test_accuracy')
                            ->label('GPS Accuracy (meter)')
                            ->numeric()
                            ->default(10),
                    ])
                    ->action(function ($record, $data) {
                        $isValid = $record->isWithinGeofence(
                            $data['test_latitude'],
                            $data['test_longitude'],
                            $data['test_accuracy'] ?? null
                        );
                        
                        $distance = $record->calculateDistance(
                            $data['test_latitude'],
                            $data['test_longitude']
                        );

                        Notification::make()
                            ->title($isValid ? '✅ Lokasi Valid!' : '❌ Lokasi Tidak Valid!')
                            ->body("Jarak: " . number_format($distance) . "m dari radius {$record->radius_meters}m")
                            ->color($isValid ? 'success' : 'danger')
                            ->duration(5000)
                            ->send();
                    }),

                Action::make('copy_coordinates')
                    ->label('📋 Copy Koordinat')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->action(function ($record) {
                        $coordinates = "{$record->latitude},{$record->longitude}";
                        
                        Notification::make()
                            ->title('📋 Koordinat Disalin!')
                            ->body("Koordinat: {$coordinates}")
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),

                Action::make('location_status')
                    ->label(fn ($record) => $record->trashed() ? '🗑️ Deleted Location' : (
                        $record->is_active ? '✅ Active Location' : '❌ Inactive Location'
                    ))
                    ->icon(fn ($record) => $record->trashed() ? 'heroicon-o-trash' : (
                        $record->is_active ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'
                    ))
                    ->color(fn ($record) => $record->trashed() ? 'danger' : (
                        $record->is_active ? 'success' : 'warning'
                    ))
                    ->modalContent(function ($record) {
                        $status = $record->trashed() ? 'deleted' : ($record->is_active ? 'active' : 'inactive');
                        $icon = match($status) {
                            'deleted' => '🗑️',
                            'active' => '✅',
                            'inactive' => '❌',
                        };
                        $color = match($status) {
                            'deleted' => 'text-red-700 bg-red-100',
                            'active' => 'text-green-700 bg-green-100',
                            'inactive' => 'text-yellow-700 bg-yellow-100',
                        };
                        
                        $statusText = match($status) {
                            'deleted' => 'This location has been soft-deleted. It is no longer available for assignments but data is preserved for historical purposes.',
                            'active' => 'This location is active and available for employee assignments and attendance tracking.',
                            'inactive' => 'This location is inactive. It cannot be used for new assignments but existing data is preserved.',
                        };
                        
                        $actions = match($status) {
                            'deleted' => 'You can restore this location using the restore action, or permanently delete it using force delete.',
                            'active' => 'You can deactivate this location or delete it if no longer needed.',
                            'inactive' => 'You can activate this location to make it available for assignments again.',
                        };
                        
                        return view('filament.components.location-status-info', [
                            'record' => $record,
                            'status' => $status,
                            'icon' => $icon,
                            'color' => $color,
                            'statusText' => $statusText,
                            'actions' => $actions
                        ]);
                    })
                    ->modalHeading(fn ($record) => 'Location Status: ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('deletion_preview')
                    ->label('🔍 Preview Deletion')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Deletion Impact Preview')
                    ->modalDescription('Review the impact of deleting this work location')
                    ->modalContent(function ($record) {
                        $service = app(WorkLocationDeletionService::class);
                        $preview = $service->getDeletePreview($record);
                        
                        return view('filament.work-location.deletion-preview', compact('preview'));
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\ViewAction::make()
                    ->color(fn ($record) => $record->trashed() ? 'gray' : 'info'),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->trashed())
                    ->tooltip(fn ($record) => $record->trashed() ? 'Cannot edit deleted location' : 'Edit location'),
                
                Tables\Actions\DeleteAction::make()
                    ->label('🗑️ Safe Delete')
                    ->modalHeading('Safe Work Location Deletion')
                    ->modalDescription('This will safely delete the work location with proper dependency handling.')
                    ->form([
                        Forms\Components\Placeholder::make('deletion_warning')
                            ->label('🚨 Deletion Impact Assessment')
                            ->content(function ($record) {
                                $service = app(WorkLocationDeletionService::class);
                                $preview = $service->getDeletePreview($record);
                                $dependencies = $preview['dependencies'];
                                $recommendations = $preview['recommendations'];
                                $impact = $preview['estimated_impact'];
                                
                                // Build comprehensive warning content with HTML styling
                                $html = '<div class="deletion-impact-assessment space-y-4 p-4 rounded-lg border">';
                                
                                // Location header
                                $html .= '<div class="location-header border-b border-gray-200 pb-3">';
                                $html .= '<h3 class="text-lg font-semibold text-gray-800 flex items-center">';
                                $html .= '<span class="mr-2">📍</span>' . htmlspecialchars($record->name);
                                $html .= '</h3>';
                                $html .= '<div class="text-sm text-gray-600 mt-1">';
                                $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 mr-2">';
                                $html .= match($record->location_type) {
                                    'main_office' => '🏢 Kantor Pusat',
                                    'branch_office' => '🏪 Kantor Cabang', 
                                    'project_site' => '🚧 Lokasi Proyek',
                                    'mobile_location' => '📱 Lokasi Mobile',
                                    'client_office' => '🤝 Kantor Klien',
                                    default => $record->location_type
                                };
                                $html .= '</span>';
                                if ($record->unit_kerja) {
                                    $html .= '<span class="text-gray-500">Unit: ' . htmlspecialchars($record->unit_kerja) . '</span>';
                                }
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                // Impact severity indicator
                                $severityColors = [
                                    'low' => 'bg-green-100 text-green-800 border-green-200',
                                    'medium' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'high' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'critical' => 'bg-red-100 text-red-800 border-red-200'
                                ];
                                $severityIcons = [
                                    'low' => '✅',
                                    'medium' => '⚠️',
                                    'high' => '🔶',
                                    'critical' => '🚨'
                                ];
                                
                                $severity = $impact['severity'];
                                $severityClass = $severityColors[$severity] ?? $severityColors['medium'];
                                $severityIcon = $severityIcons[$severity] ?? '⚠️';
                                
                                $html .= '<div class="impact-severity mb-4">';
                                $html .= '<div class="inline-flex items-center px-3 py-2 rounded-lg border ' . $severityClass . '">';
                                $html .= '<span class="mr-2">' . $severityIcon . '</span>';
                                $html .= '<span class="font-medium">Impact Severity: ' . ucfirst($severity) . '</span>';
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                // Blocking Dependencies (Critical Issues)
                                if (!empty($dependencies['blocking_dependencies'])) {
                                    $html .= '<div class="blocking-dependencies bg-red-50 border border-red-200 rounded-lg p-4 mb-4">';
                                    $html .= '<div class="flex items-center mb-3">';
                                    $html .= '<span class="text-red-600 mr-2">⛔</span>';
                                    $html .= '<h4 class="font-semibold text-red-800">Blocking Issues (Deletion Not Allowed)</h4>';
                                    $html .= '</div>';
                                    $html .= '<ul class="space-y-1 text-sm text-red-700">';
                                    foreach ($dependencies['blocking_dependencies'] as $dependency) {
                                        $html .= '<li class="flex items-start">';
                                        $html .= '<span class="text-red-500 mr-2 mt-0.5">•</span>';
                                        $html .= htmlspecialchars($dependency);
                                        $html .= '</li>';
                                    }
                                    $html .= '</ul>';
                                    $html .= '</div>';
                                }
                                
                                // Warnings (Non-blocking Issues)
                                if (!empty($dependencies['warnings'])) {
                                    $html .= '<div class="warnings bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
                                    $html .= '<div class="flex items-center mb-3">';
                                    $html .= '<span class="text-yellow-600 mr-2">⚠️</span>';
                                    $html .= '<h4 class="font-semibold text-yellow-800">Warnings (Will Be Handled)</h4>';
                                    $html .= '</div>';
                                    $html .= '<ul class="space-y-1 text-sm text-yellow-700">';
                                    foreach ($dependencies['warnings'] as $warning) {
                                        $html .= '<li class="flex items-start">';
                                        $html .= '<span class="text-yellow-500 mr-2 mt-0.5">•</span>';
                                        $html .= htmlspecialchars($warning);
                                        $html .= '</li>';
                                    }
                                    $html .= '</ul>';
                                    $html .= '</div>';
                                }
                                
                                // Recommendations
                                if (!empty($recommendations)) {
                                    $html .= '<div class="recommendations bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">';
                                    $html .= '<div class="flex items-center mb-3">';
                                    $html .= '<span class="text-blue-600 mr-2">💡</span>';
                                    $html .= '<h4 class="font-semibold text-blue-800">Recommendations</h4>';
                                    $html .= '</div>';
                                    foreach ($recommendations as $rec) {
                                        $recIcon = match($rec['type']) {
                                            'error' => '<span class="text-red-500">❌</span>',
                                            'warning' => '<span class="text-yellow-500">⚠️</span>',
                                            'success' => '<span class="text-green-500">✅</span>',
                                            default => '<span class="text-blue-500">ℹ️</span>'
                                        };
                                        $html .= '<div class="flex items-start mb-2 text-sm">';
                                        $html .= '<span class="mr-2 mt-0.5">' . $recIcon . '</span>';
                                        $html .= '<span class="text-gray-700">' . htmlspecialchars($rec['message']) . '</span>';
                                        $html .= '</div>';
                                    }
                                    $html .= '</div>';
                                }
                                
                                // Affected Users Details
                                if ($dependencies['assigned_users_count'] > 0) {
                                    $html .= '<div class="affected-users bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">';
                                    $html .= '<div class="flex items-center mb-3">';
                                    $html .= '<span class="text-gray-600 mr-2">👥</span>';
                                    $html .= '<h4 class="font-semibold text-gray-800">Affected Users (' . $dependencies['assigned_users_count'] . ')</h4>';
                                    $html .= '</div>';
                                    
                                    if (!empty($dependencies['assigned_users'])) {
                                        $html .= '<div class="grid grid-cols-1 gap-2 max-h-32 overflow-y-auto">';
                                        foreach (array_slice($dependencies['assigned_users'], 0, 5) as $user) {
                                            $html .= '<div class="flex items-center justify-between p-2 bg-white rounded border text-sm">';
                                            $html .= '<div>';
                                            $html .= '<span class="font-medium text-gray-800">' . htmlspecialchars($user['name']) . '</span>';
                                            $html .= '<span class="text-gray-500 ml-2">(' . htmlspecialchars($user['role']) . ')</span>';
                                            $html .= '</div>';
                                            $html .= '<span class="text-xs text-gray-500">' . htmlspecialchars($user['email']) . '</span>';
                                            $html .= '</div>';
                                        }
                                        if ($dependencies['assigned_users_count'] > 5) {
                                            $html .= '<div class="text-center text-sm text-gray-500 py-2">';
                                            $html .= '+ ' . ($dependencies['assigned_users_count'] - 5) . ' more users...';
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                    $html .= '</div>';
                                }
                                
                                // Final Status
                                if ($dependencies['can_delete']) {
                                    $html .= '<div class="final-status bg-green-50 border border-green-200 rounded-lg p-4">';
                                    $html .= '<div class="flex items-center">';
                                    $html .= '<span class="text-green-600 mr-2">✅</span>';
                                    $html .= '<span class="font-semibold text-green-800">This location can be safely deleted</span>';
                                    $html .= '</div>';
                                    $html .= '<div class="text-sm text-green-700 mt-2">';
                                    $html .= 'All users will be automatically reassigned to alternative locations.';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                } else {
                                    $html .= '<div class="final-status bg-red-50 border border-red-200 rounded-lg p-4">';
                                    $html .= '<div class="flex items-center">';
                                    $html .= '<span class="text-red-600 mr-2">❌</span>';
                                    $html .= '<span class="font-semibold text-red-800">This location cannot be deleted</span>';
                                    $html .= '</div>';
                                    $html .= '<div class="text-sm text-red-700 mt-2">';
                                    $html .= 'Please resolve the blocking dependencies before attempting deletion.';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                            
                        Forms\Components\Toggle::make('reassign_users')
                            ->label('Automatically reassign users to alternative locations')
                            ->default(true)
                            ->helperText('Users will be reassigned to the best matching alternative location'),
                            
                        Forms\Components\Toggle::make('preserve_history')
                            ->label('Preserve assignment history')
                            ->default(true)
                            ->helperText('Keep historical records with deletion context'),
                            
                        Forms\Components\Textarea::make('deletion_reason')
                            ->label('Deletion Reason')
                            ->required()
                            ->placeholder('Please provide a reason for deleting this work location...')
                            ->maxLength(500),
                    ])
                    ->action(function ($record, $data) {
                        try {
                            $service = app(WorkLocationDeletionService::class);
                            
                            // Check if deletion is possible
                            $preview = $service->getDeletePreview($record);
                            if (!$preview['dependencies']['can_delete']) {
                                Notification::make()
                                    ->title('❌ Deletion Failed')
                                    ->body('Cannot delete location due to blocking dependencies: ' . implode(', ', $preview['dependencies']['blocking_dependencies']))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                    
                                throw new Halt();
                            }
                            
                            $result = $service->safeDelete($record, [
                                'reassign_users' => $data['reassign_users'] ?? true,
                                'preserve_history' => $data['preserve_history'] ?? true,
                                'reason' => $data['deletion_reason'],
                                'assigned_by' => auth()->id(),
                            ]);
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->title('✅ ' . $result['message'])
                                    ->body("Users reassigned: {$result['data']['users_reassigned']}")
                                    ->success()
                                    ->duration(8000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('❌ Deletion Failed')
                                    ->body($result['message'])
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                    
                                throw new Halt();
                            }
                            
                        } catch (\Exception $e) {
                            if (!($e instanceof Halt)) {
                                \Log::error('Work location deletion error in Filament', [
                                    'record_id' => $record->id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Deletion Error')
                                    ->body('An unexpected error occurred: ' . $e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                            
                            throw $e;
                        }
                    })
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Delete Location')
                    ->visible(fn ($record) => !$record->trashed()),

                Tables\Actions\RestoreAction::make()
                    ->label('🔄 Restore')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Location Restored')
                            ->body('Work location has been restored and reactivated.')
                    ),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('📛 Force Delete')
                    ->modalHeading('Permanent Deletion Warning')
                    ->modalDescription('This will permanently delete the work location and all its data. This action cannot be undone!')
                    ->modalSubmitActionLabel('Permanently Delete')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Location Permanently Deleted')
                            ->body('Work location has been permanently removed from the system.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('safe_bulk_delete')
                        ->label('🗑️ Safe Bulk Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->form([
                            Forms\Components\Placeholder::make('bulk_warning')
                                ->label('⚠️ Bulk Deletion Warning')
                                ->content(new \Illuminate\Support\HtmlString('<div class="bulk-deletion-warning bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div class="flex items-center mb-3">
                                        <span class="text-yellow-600 mr-2 text-lg">⚠️</span>
                                        <h4 class="font-semibold text-yellow-800">Bulk Deletion Warning</h4>
                                    </div>
                                    <div class="text-sm text-yellow-700 space-y-2">
                                        <p>You are about to delete multiple work locations. Each will be processed safely with dependency checking.</p>
                                        <div class="mt-3 p-3 bg-yellow-100 rounded border">
                                            <div class="flex items-start space-x-2">
                                                <span class="text-yellow-600 mt-0.5">💡</span>
                                                <div class="text-xs text-yellow-800">
                                                    <div class="font-medium mb-1">Safety Features:</div>
                                                    <ul class="list-disc list-inside space-y-1">
                                                        <li>Dependency checking for each location</li>
                                                        <li>Automatic user reassignment</li>
                                                        <li>Historical data preservation</li>
                                                        <li>Detailed operation logging</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>')),
                                
                            Forms\Components\Toggle::make('reassign_users')
                                ->label('Automatically reassign users')
                                ->default(true),
                                
                            Forms\Components\Toggle::make('preserve_history')
                                ->label('Preserve assignment history')
                                ->default(true),
                                
                            Forms\Components\Textarea::make('deletion_reason')
                                ->label('Bulk Deletion Reason')
                                ->required()
                                ->placeholder('Reason for bulk deletion...')
                        ])
                        ->action(function ($records, $data) {
                            $service = app(WorkLocationDeletionService::class);
                            $results = [
                                'successful' => 0,
                                'failed' => 0,
                                'skipped' => 0,
                                'details' => []
                            ];
                            
                            foreach ($records as $record) {
                                try {
                                    $result = $service->safeDelete($record, [
                                        'reassign_users' => $data['reassign_users'] ?? true,
                                        'preserve_history' => $data['preserve_history'] ?? true,
                                        'reason' => $data['deletion_reason'],
                                        'assigned_by' => auth()->id(),
                                    ]);
                                    
                                    if ($result['success']) {
                                        $results['successful']++;
                                        $results['details'][] = "✅ {$record->name}: Deleted successfully";
                                    } else {
                                        $results['failed']++;
                                        $results['details'][] = "❌ {$record->name}: {$result['message']}";
                                    }
                                    
                                } catch (\Exception $e) {
                                    $results['failed']++;
                                    $results['details'][] = "❌ {$record->name}: {$e->getMessage()}";
                                }
                            }
                            
                            $message = "Bulk deletion completed: {$results['successful']} successful, {$results['failed']} failed";
                            
                            Notification::make()
                                ->title($results['failed'] === 0 ? '✅ Bulk Deletion Successful' : '⚠️ Bulk Deletion Completed with Issues')
                                ->body($message)
                                ->color($results['failed'] === 0 ? 'success' : 'warning')
                                ->duration(10000)
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalSubmitActionLabel('Delete Selected'),
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('✅ Aktifkan Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title("✅ {$count} lokasi diaktifkan!")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('❌ Nonaktifkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title("❌ {$count} lokasi dinonaktifkan!")
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label('🔄 Restore Selected')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Locations Restored')
                                ->body('Selected work locations have been restored.')
                        ),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('📛 Force Delete Selected')
                        ->modalHeading('Permanent Bulk Deletion Warning')
                        ->modalDescription('This will permanently delete all selected work locations and their data. This action cannot be undone!')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Locations Permanently Deleted')
                                ->body('Selected work locations have been permanently removed.')
                        ),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->poll('60s')
            ->recordClasses(fn ($record) => $record->trashed() 
                ? 'bg-red-50 border-l-4 border-red-500 opacity-75' 
                : ($record->is_active ? '' : 'bg-yellow-50 border-l-4 border-yellow-500')
            )
            ->recordUrl(null) // Disable row click to prevent confusion with deleted records
            ->emptyStateHeading('📍 Belum Ada Lokasi Kerja')
            ->emptyStateDescription('Tambahkan lokasi kerja pertama untuk mengaktifkan validasi geofencing.')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('➕ Tambah Lokasi Pertama')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
            ]);
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
            'index' => Pages\ListWorkLocations::route('/'),
            'create' => Pages\CreateWorkLocation::route('/create'),
            'view' => Pages\ViewWorkLocation::route('/{record}'),
            'edit' => Pages\EditWorkLocation::route('/{record}/edit'),
        ];
    }
}