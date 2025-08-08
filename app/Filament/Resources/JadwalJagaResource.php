<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalJagaResource\Pages;
use App\Filament\Resources\JadwalJagaResource\RelationManagers;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Actions;

class JadwalJagaResource extends Resource
{
    protected static ?string $model = JadwalJaga::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = '📅 KALENDAR DAN JADWAL';
    
    protected static ?string $navigationLabel = 'Jadwal Jaga';
    
    protected static ?string $modelLabel = 'Jadwal Jaga';
    
    protected static ?string $pluralModelLabel = 'Jadwal Jaga';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        // Debug log to confirm this method is being called
        \Log::info('JadwalJagaResource form method called');
        
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jadwal')
                    ->schema([
                        Forms\Components\Select::make('shift_template_id')
                            ->label('Template Shift')
                            ->relationship('shiftTemplate', 'nama_shift')
                            ->getOptionLabelFromRecordUsing(fn (ShiftTemplate $record): string => $record->shift_display)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Reset pegawai and date selection when shift changes
                                $set('pegawai_id', null);
                                $set('tanggal_jaga', null);
                            })
                            ->helperText('Pilih shift terlebih dahulu untuk menentukan batasan tanggal yang tersedia'),
                        Forms\Components\TextInput::make('tanggal_jaga')
                            ->label('Tanggal Jaga')
                            ->required()
                            ->type('date')
                            ->helperText('Pilih tanggal jadwal jaga (format: YYYY-MM-DD)')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset pegawai selection when date changes
                                $set('pegawai_id', null);
                            })
                            ->helperText(function (callable $get) {
                                $shiftTemplateId = $get('shift_template_id');
                                $selectedDate = $get('tanggal_jaga');
                                
                                if (!$shiftTemplateId) {
                                    return 'Pilih template shift terlebih dahulu untuk melihat validasi tanggal.';
                                }
                                
                                $shiftTemplate = \App\Models\ShiftTemplate::find($shiftTemplateId);
                                if (!$shiftTemplate) {
                                    return '';
                                }
                                
                                $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
                                $today = \Carbon\Carbon::today('Asia/Jakarta');
                                $shiftStartTime = \Carbon\Carbon::parse($shiftTemplate->jam_masuk);
                                $todayShiftStart = $today->copy()->setHour($shiftStartTime->hour)->setMinute($shiftStartTime->minute)->setSecond(0);
                                
                                if ($selectedDate) {
                                    $selected = \Carbon\Carbon::parse($selectedDate);
                                    if ($selected->isSameDay($today)) {
                                        if ($currentTime->greaterThan($todayShiftStart)) {
                                            return "❌ Tanggal hari ini tidak valid - Shift {$shiftTemplate->nama_shift} sudah dimulai jam {$shiftTemplate->jam_masuk_format}. Sekarang jam {$currentTime->format('H:i')}.";
                                        } else {
                                            $timeLeft = round($currentTime->diffInMinutes($todayShiftStart));
                                            return "✅ Tanggal hari ini masih valid - Shift dimulai jam {$shiftTemplate->jam_masuk_format} ({$timeLeft} menit lagi).";
                                        }
                                    } else {
                                        return "✅ Tanggal {$selected->format('d/m/Y')} valid - Tidak ada batasan waktu untuk jadwal masa depan.";
                                    }
                                }
                                
                                if ($currentTime->greaterThan($todayShiftStart)) {
                                    return "⚠️ Shift {$shiftTemplate->nama_shift} hari ini sudah dimulai (jam {$shiftTemplate->jam_masuk_format}). Pilih tanggal mulai besok.";
                                }
                                
                                $timeLeft = round($currentTime->diffInMinutes($todayShiftStart));
                                return "✅ Shift {$shiftTemplate->nama_shift} dimulai jam {$shiftTemplate->jam_masuk_format}. Masih bisa pilih hari ini ({$timeLeft} menit lagi).";
                            }),
                            
                        Forms\Components\TimePicker::make('jam_jaga_custom')
                            ->label('Jam Mulai Jaga (Opsional)')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $selectedDate = $get('tanggal_jaga');
                                        $shiftTemplateId = $get('shift_template_id');
                                        
                                        if (!$value || !$selectedDate || !$shiftTemplateId) {
                                            return; // Skip validation if fields are empty
                                        }
                                        
                                        $shiftTemplate = \App\Models\ShiftTemplate::find($shiftTemplateId);
                                        if (!$shiftTemplate) {
                                            return;
                                        }
                                        
                                        $selectedDate = \Carbon\Carbon::parse($selectedDate);
                                        $today = \Carbon\Carbon::today('Asia/Jakarta');
                                        $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
                                        
                                        // Validasi hanya untuk hari ini
                                        if ($selectedDate->isSameDay($today)) {
                                            $customTime = \Carbon\Carbon::parse($value);
                                            $selectedDateTime = $today->copy()->setHour($customTime->hour)->setMinute($customTime->minute)->setSecond(0);
                                            
                                            // Jika jam custom dipilih dan hari ini
                                            if ($currentTime->greaterThan($selectedDateTime)) {
                                                $fail("Tidak dapat memilih jam {$customTime->format('H:i')} untuk hari ini karena waktu tersebut sudah lewat. Sekarang jam {$currentTime->format('H:i')}.");
                                            }
                                            
                                            // Validasi jam custom tidak boleh setelah jam mulai shift template
                                            $shiftStartTime = \Carbon\Carbon::parse($shiftTemplate->jam_masuk);
                                            $todayShiftStart = $today->copy()->setHour($shiftStartTime->hour)->setMinute($shiftStartTime->minute)->setSecond(0);
                                            
                                            if ($selectedDateTime->greaterThan($todayShiftStart)) {
                                                $fail("Jam mulai jaga custom ({$customTime->format('H:i')}) tidak boleh setelah jam mulai shift template ({$shiftTemplate->jam_masuk_format}).");
                                            }
                                        }
                                    };
                                }
                            ])
                            ->helperText(function (callable $get) {
                                $shiftTemplateId = $get('shift_template_id');
                                $selectedDate = $get('tanggal_jaga');
                                
                                if (!$shiftTemplateId) {
                                    return 'Pilih shift template terlebih dahulu.';
                                }
                                
                                $shiftTemplate = \App\Models\ShiftTemplate::find($shiftTemplateId);
                                if (!$shiftTemplate) {
                                    return '';
                                }
                                
                                $today = \Carbon\Carbon::today('Asia/Jakarta');
                                $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
                                
                                if ($selectedDate && \Carbon\Carbon::parse($selectedDate)->isSameDay($today)) {
                                    return "⚠️ Untuk hari ini: Jam harus sebelum {$shiftTemplate->jam_masuk_format} (jam mulai shift) dan setelah jam {$currentTime->format('H:i')} (sekarang). Kosongkan untuk menggunakan jam default dari template.";
                                }
                                
                                return "💡 Opsional: Kosongkan untuk menggunakan jam default dari shift template ({$shiftTemplate->jam_masuk_format} - {$shiftTemplate->jam_pulang_format}).";
                            }),
                        Forms\Components\Select::make('unit_kerja')
                            ->label('Unit Kerja')
                            ->options([
                                'Pendaftaran' => 'Pendaftaran',
                                'Pelayanan' => 'Pelayanan',
                                'Dokter Jaga' => 'Dokter Jaga'
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset pegawai selection when unit changes
                                $set('pegawai_id', null);
                            }),
                    ])->columns(3),
                    
                // Existing Schedule Preview Section
                Forms\Components\Section::make('Pegawai yang Sudah Terjadwal')
                    ->schema([
                        Forms\Components\Placeholder::make('existing_schedule')
                            ->label('')
                            ->content(function (callable $get) {
                                $tanggal = $get('tanggal_jaga');
                                $shiftId = $get('shift_template_id');
                                
                                if (!$tanggal || !$shiftId) {
                                    return 'Pilih tanggal dan shift untuk melihat jadwal yang sudah ada.';
                                }
                                
                                $existingSchedules = JadwalJaga::where('tanggal_jaga', $tanggal)
                                    ->where('shift_template_id', $shiftId)
                                    ->with(['pegawai', 'shiftTemplate'])
                                    ->get();
                                    
                                if ($existingSchedules->isEmpty()) {
                                    return '✅ Belum ada pegawai yang terjadwal untuk shift ini.';
                                }
                                
                                $html = '<div class="space-y-2">';
                                $html .= '<div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pegawai yang sudah terjadwal (' . $existingSchedules->count() . ' orang):</div>';
                                
                                foreach ($existingSchedules as $schedule) {
                                    $statusColor = match($schedule->status_jaga) {
                                        'Aktif' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'Cuti' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'Izin' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'OnCall' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    };
                                    
                                    $peranColor = match($schedule->peran) {
                                        'Dokter' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                        'Paramedis' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'NonParamedis' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    };
                                    
                                    $html .= '<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border">';
                                    $html .= '<div class="flex items-center space-x-3">';
                                    $html .= '<div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-sm font-medium">' . substr($schedule->pegawai->name, 0, 2) . '</div>';
                                    $html .= '<div>';
                                    $html .= '<div class="font-medium text-gray-900 dark:text-gray-100">' . $schedule->pegawai->name . '</div>';
                                    
                                    // Get enhanced staff info
                                    $staffInfo = '';
                                    if ($schedule->unit_kerja === 'Dokter Jaga') {
                                        $dokter = \App\Models\Dokter::where('user_id', $schedule->pegawai_id)->first();
                                        if ($dokter) {
                                            $staffInfo = $dokter->jabatan_display . ' • SIP: ' . $dokter->nomor_sip;
                                        }
                                    } else {
                                        $pegawai = \App\Models\Pegawai::whereHas('user', function($q) use ($schedule) {
                                            $q->where('id', $schedule->pegawai_id);
                                        })->first();
                                        if ($pegawai) {
                                            $staffInfo = $pegawai->jabatan . ' • ' . $pegawai->jenis_pegawai . ' • NIK: ' . $pegawai->nik;
                                        } else {
                                            $staffInfo = $schedule->unit_kerja;
                                        }
                                    }
                                    
                                    $html .= '<div class="text-sm text-gray-500 dark:text-gray-400">' . $staffInfo . '</div>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                    $html .= '<div class="flex space-x-2">';
                                    $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . $peranColor . '">' . $schedule->peran . '</span>';
                                    $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . $statusColor . '">' . $schedule->status_jaga . '</span>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                    ])
                    ->visible(fn (callable $get) => $get('tanggal_jaga') && $get('shift_template_id')),
                    
                Forms\Components\Section::make('Tambah Pegawai Baru')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('👨‍💼 Pilih Pegawai dari Manajemen Admin')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->options(function (callable $get) {
                                $tanggal = $get('tanggal_jaga');
                                $shiftId = $get('shift_template_id');
                                $unitKerja = $get('unit_kerja');
                                
                                if (!$tanggal || !$shiftId || !$unitKerja) {
                                    return [];
                                }
                                
                                // Get comprehensive staff data based on unit type
                                if ($unitKerja === 'Dokter Jaga') {
                                    // Get from Dokter management
                                    $availableStaff = \App\Models\Dokter::where('aktif', true)
                                        ->with(['user'])
                                        ->get();
                                } else {
                                    // Get from Pegawai management  
                                    $availableStaff = \App\Models\Pegawai::where('aktif', true)
                                        ->whereIn('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])
                                        ->with(['user'])
                                        ->get();
                                }
                                
                                // Exclude staff already scheduled for this SAME shift
                                $excludeUserIds = JadwalJaga::whereDate('tanggal_jaga', $tanggal)
                                    ->where('shift_template_id', $shiftId)
                                    ->pluck('pegawai_id');
                                
                                $options = [];
                                foreach ($availableStaff as $staff) {
                                    $userId = $unitKerja === 'Dokter Jaga' ? $staff->user_id : $staff->user?->id;
                                    
                                    if (!$userId || $excludeUserIds->contains($userId)) {
                                        continue;
                                    }
                                    
                                    // Check other shifts on same day
                                    $otherShifts = JadwalJaga::where('pegawai_id', $userId)
                                        ->whereDate('tanggal_jaga', $tanggal)
                                        ->with('shiftTemplate')
                                        ->get();
                                    
                                    $label = $staff->nama_lengkap;
                                    
                                    if ($unitKerja === 'Dokter Jaga') {
                                        $label .= " - " . $staff->jabatan_display . " (SIP: " . $staff->nomor_sip . ")";
                                    } else {
                                        $label .= " - " . $staff->jabatan . " (" . $staff->jenis_pegawai . ", NIK: " . $staff->nik . ")";
                                    }
                                    
                                    if ($otherShifts->isNotEmpty()) {
                                        $shifts = $otherShifts->pluck('shiftTemplate.nama_shift')->join(', ');
                                        $label .= " [Sudah: " . $shifts . "]";
                                    }
                                    
                                    $options[$userId] = $label;
                                }
                                
                                return $options;
                            })
                            ->placeholder('Pilih pegawai yang akan ditambahkan...')
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $tanggal = $get('tanggal_jaga');
                                        $shiftId = $get('shift_template_id');
                                        
                                        if ($value && $tanggal && $shiftId) {
                                            $exists = \App\Models\JadwalJaga::whereDate('tanggal_jaga', $tanggal)
                                                ->where('shift_template_id', $shiftId)
                                                ->where('pegawai_id', $value)
                                                ->exists();
                                                
                                            if ($exists) {
                                                $pegawai = \App\Models\User::find($value);
                                                $shiftTemplate = \App\Models\ShiftTemplate::find($shiftId);
                                                $fail("⚠️ Pegawai {$pegawai->name} sudah memiliki jadwal jaga untuk shift {$shiftTemplate->nama_shift} pada tanggal " . \Carbon\Carbon::parse($tanggal)->format('d/m/Y') . ".");
                                            }
                                        }
                                    };
                                }
                            ])
                            ->helperText(function (callable $get) {
                                $tanggal = $get('tanggal_jaga');
                                $shiftId = $get('shift_template_id');
                                $unitKerja = $get('unit_kerja');
                                
                                if (!$tanggal || !$shiftId || !$unitKerja) {
                                    return 'Pilih tanggal, shift, dan unit kerja terlebih dahulu.';
                                }
                                
                                $source = match($unitKerja) {
                                    'Dokter Jaga' => 'Manajemen Dokter',
                                    default => 'Manajemen Pegawai'
                                };
                                
                                $existingCount = JadwalJaga::where('tanggal_jaga', $tanggal)
                                    ->where('shift_template_id', $shiftId)
                                    ->count();
                                    
                                $remaining = 5 - $existingCount;
                                
                                if ($remaining <= 0) {
                                    return "⚠️ Shift ini sudah penuh (5 pegawai). Tidak bisa menambah lagi.";
                                }
                                
                                return "💡 Data diambil dari {$source}. Tersisa {$remaining} slot untuk shift ini.";
                            }),
                        Forms\Components\Select::make('peran')
                            ->label('Peran')
                            ->options([
                                'Paramedis' => 'Paramedis',
                                'NonParamedis' => 'Non Paramedis', 
                                'Dokter' => 'Dokter'
                            ])
                            ->required()
                            ->native(false)
                            ->default('Paramedis'),
                        Forms\Components\Select::make('status_jaga')
                            ->label('Status Jaga')
                            ->options([
                                'Aktif' => 'Aktif',
                                'Cuti' => 'Cuti',
                                'Izin' => 'Izin',
                                'OnCall' => 'On Call'
                            ])
                            ->default('Aktif')
                            ->required()
                            ->native(false),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Keterangan')
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan Tambahan')
                            ->placeholder('Catatan khusus untuk jadwal ini...')
                            ->rows(3),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_jaga')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Pegawai')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('shiftTemplate.nama_shift')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_shift')
                    ->label('Jam')
                    ->getStateUsing(function (JadwalJaga $record): string {
                        if ($record->jam_jaga_custom) {
                            return $record->jam_shift . ' (Custom)';
                        }
                        
                        return $record->jam_shift;
                    })
                    ->badge()
                    ->color(function (JadwalJaga $record): string {
                        return $record->jam_jaga_custom ? 'success' : 'info';
                    })
                    ->sortable(false)
                    ->searchable(false),
                Tables\Columns\TextColumn::make('peran')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dokter' => 'success',
                        'Paramedis' => 'info',
                        'NonParamedis' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_jaga')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Cuti' => 'warning',
                        'Izin' => 'danger',
                        'OnCall' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dokter Jaga' => 'success',
                        'Pelayanan' => 'info',
                        'Pendaftaran' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListJadwalJagas::route('/'),
            'create' => Pages\CreateJadwalJaga::route('/create'),
            'edit' => Pages\EditJadwalJaga::route('/{record}/edit'),
        ];
    }
}
