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
                    ->offColor('danger'),

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
                    ->color(fn ($record) => $record->deleted_at ? 'danger' : 'success'),
            ])
            ->filters([
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

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->label('🗑️ Safe Delete')
                    ->modalHeading('Safe Work Location Deletion')
                    ->modalDescription('This will safely delete the work location with proper dependency handling.')
                    ->form([
                        Forms\Components\Alert::make('deletion_warning')
                            ->warning()
                            ->title('Deletion Impact Assessment')
                            ->body(function ($record) {
                                $service = app(WorkLocationDeletionService::class);
                                $preview = $service->getDeletePreview($record);
                                
                                $message = "Location: {$record->name}";
                                
                                if (!empty($preview['dependencies']['blocking_dependencies'])) {
                                    $message .= "\n\n⛔ BLOCKING ISSUES:\n" . implode("\n", $preview['dependencies']['blocking_dependencies']);
                                }
                                
                                if (!empty($preview['dependencies']['warnings'])) {
                                    $message .= "\n\n⚠️  WARNINGS:\n" . implode("\n", $preview['dependencies']['warnings']);
                                }
                                
                                if ($preview['dependencies']['can_delete']) {
                                    $message .= "\n\n✅ This location can be safely deleted.";
                                } else {
                                    $message .= "\n\n❌ This location cannot be deleted due to blocking dependencies.";
                                }
                                
                                return $message;
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
                            Forms\Components\Alert::make('bulk_warning')
                                ->warning()
                                ->title('Bulk Deletion Warning')
                                ->body('You are about to delete multiple work locations. Each will be processed safely with dependency checking.'),
                                
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