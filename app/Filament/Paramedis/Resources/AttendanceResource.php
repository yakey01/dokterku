<?php

namespace App\Filament\Paramedis\Resources;

use App\Filament\Paramedis\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Services\GeolocationService;
use Filament\Forms\Components\ViewField;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = '✏️ Input Presensi';

    protected static ?string $modelLabel = 'Input Presensi';

    protected static ?string $pluralModelLabel = 'Input Presensi';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = '📅 PRESENSI & LAPORAN';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Kehadiran')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(today())
                                    ->maxDate(today()),

                                Forms\Components\Select::make('user_id')
                                    ->label('Pegawai')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->default(fn() => auth()->id())
                                    ->disabled(fn() => !auth()->user()->hasRole('super_admin'))
                                    ->dehydrated(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('time_in')
                                    ->label('Waktu Masuk')
                                    ->seconds(false)
                                    ->required(),

                                Forms\Components\TimePicker::make('time_out')
                                    ->label('Waktu Keluar')
                                    ->seconds(false)
                                    ->nullable(),
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'present' => 'Hadir',
                                'late' => 'Terlambat',
                                'absent' => 'Tidak Hadir',
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                            ])
                            ->required()
                            ->default('present'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->nullable(),
                    ]),

                Section::make('Lokasi Check-in')
                    ->schema([
                        ViewField::make('location_map')
                            ->view('filament.forms.components.simple-leaflet-map')
                            ->label('📍 Pilih Lokasi Check-in')
                            ->columnSpanFull()
                            ->dehydrated(false),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step('any')
                                    ->rules(['between:-90,90'])
                                    ->required(),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step('any')
                                    ->rules(['between:-180,180'])
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('accuracy')
                                    ->label('Akurasi GPS (meter)')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('meter'),

                                Forms\Components\TextInput::make('location_name_in')
                                    ->label('Nama Lokasi Check-in')
                                    ->nullable(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Lokasi Check-out')
                    ->schema([
                        Map::make('checkout_location')
                            ->label('Pilih Lokasi Check-out')
                            ->columnSpanFull()
                            ->defaultLocation(latitude: -7.89946200, longitude: 111.96239900)
                            ->afterStateUpdated(function (Map $component, $state) {
                                $component->getContainer()->getComponent('checkout_latitude')
                                    ->state($state['lat'] ?? null);
                                $component->getContainer()->getComponent('checkout_longitude')
                                    ->state($state['lng'] ?? null);
                            })
                            ->afterStateHydrated(function (Map $component, $state) {
                                $latitude = $component->getContainer()->getComponent('checkout_latitude')->getState();
                                $longitude = $component->getContainer()->getComponent('checkout_longitude')->getState();
                                
                                if ($latitude && $longitude) {
                                    $component->state([
                                        'lat' => (float) $latitude,
                                        'lng' => (float) $longitude,
                                    ]);
                                }
                            })
                            ->liveLocation()
                            ->showMarker()
                            ->markerColor("#ef4444ff")
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable()
                            ->tilesUrl("https://tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png")
                            ->zoom(15)
                            ->detectRetina()
                            ->showMyLocationButton()
                            ->extraTileControl([])
                            ->extraControl([
                                'zoomDelta' => 1,
                                'zoomSnap' => 2,
                            ])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (is_array($state) && isset($state['lat'], $state['lng'])) {
                                    $set('checkout_latitude', $state['lat']);
                                    $set('checkout_longitude', $state['lng']);
                                }
                            })
                            ->afterStateHydrated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($get('checkout_latitude') && $get('checkout_longitude')) {
                                    $set('checkout_location', [
                                        'lat' => (float) $get('checkout_latitude'),
                                        'lng' => (float) $get('checkout_longitude'),
                                    ]);
                                }
                            })
                            ->reactive(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('checkout_latitude')
                                    ->label('Checkout Latitude')
                                    ->numeric()
                                    ->step('any')
                                    ->rules(['between:-90,90'])
                                    ->nullable(),

                                Forms\Components\TextInput::make('checkout_longitude')
                                    ->label('Checkout Longitude')
                                    ->numeric()
                                    ->step('any')
                                    ->rules(['between:-180,180'])
                                    ->nullable(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('checkout_accuracy')
                                    ->label('Checkout Akurasi GPS')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('meter'),

                                Forms\Components\TextInput::make('location_name_out')
                                    ->label('Nama Lokasi Check-out')
                                    ->nullable(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Validasi & Info Tambahan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('location_validated')
                                    ->label('Lokasi Tervalidasi')
                                    ->default(false),

                                Forms\Components\FileUpload::make('photo_in')
                                    ->label('Foto Check-in')
                                    ->image()
                                    ->directory('attendance/checkin')
                                    ->nullable(),

                                Forms\Components\FileUpload::make('photo_out')
                                    ->label('Foto Check-out')
                                    ->image()
                                    ->directory('attendance/checkout')
                                    ->nullable(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_in')
                    ->label('Masuk')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_out')
                    ->label('Keluar')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('work_duration')
                    ->label('Durasi Kerja')
                    ->formatStateUsing(fn ($record) => $record->formatted_work_duration ?? '-')
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'present',
                        'warning' => 'late',
                        'danger' => 'absent',
                        'info' => 'sick',
                        'secondary' => 'permission',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('location_coordinates')
                    ->label('Koordinat Check-in')
                    ->formatStateUsing(function ($record) {
                        if ($record->latitude && $record->longitude) {
                            return number_format($record->latitude, 6) . ', ' . number_format($record->longitude, 6);
                        }
                        return '-';
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('location_validated')
                    ->label('Validasi')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->where('date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('location_validated')
                    ->label('Status Validasi Lokasi')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Kehadiran')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Pegawai'),
                                Infolists\Components\TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('time_in')
                                    ->label('Waktu Masuk')
                                    ->time('H:i'),
                                Infolists\Components\TextEntry::make('time_out')
                                    ->label('Waktu Keluar')
                                    ->time('H:i')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('work_duration')
                                    ->label('Durasi Kerja')
                                    ->formatStateUsing(fn ($record) => $record->formatted_work_duration ?? '-'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'present' => 'success',
                                        'late' => 'warning',
                                        'absent' => 'danger',
                                        'sick' => 'info',
                                        'permission' => 'secondary',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'present' => 'Hadir',
                                        'late' => 'Terlambat',
                                        'absent' => 'Tidak Hadir',
                                        'sick' => 'Sakit',
                                        'permission' => 'Izin',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Informasi Lokasi')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('location_name_in')
                                    ->label('Lokasi Check-in')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('location_name_out')
                                    ->label('Lokasi Check-out')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('latitude')
                                    ->label('Latitude Check-in')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('longitude')
                                    ->label('Longitude Check-in')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('checkout_latitude')
                                    ->label('Latitude Check-out')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('checkout_longitude')
                                    ->label('Longitude Check-out')
                                    ->placeholder('-'),
                            ]),

                        Infolists\Components\TextEntry::make('location_validated')
                            ->label('Status Validasi Lokasi')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Tervalidasi' : 'Belum Divalidasi')
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                    ]),

                Infolists\Components\Section::make('Foto & Catatan')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ImageEntry::make('photo_in')
                                    ->label('Foto Check-in')
                                    ->height(200)
                                    ->placeholder('-'),
                                Infolists\Components\ImageEntry::make('photo_out')
                                    ->label('Foto Check-out')
                                    ->height(200)
                                    ->placeholder('-'),
                            ]),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                !auth()->user()->hasRole('super_admin'),
                fn (Builder $query) => $query->where('user_id', auth()->id())
            );
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Show today's attendance count
        return static::getModel()::where('user_id', auth()->id())
            ->where('date', today())
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Red if user hasn't checked in today, green if they have
        $hasCheckedIn = static::getModel()::where('user_id', auth()->id())
            ->where('date', today())
            ->exists();
        
        return $hasCheckedIn ? 'success' : 'danger';
    }
}