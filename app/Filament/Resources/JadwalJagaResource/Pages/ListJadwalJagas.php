<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJadwalJagas extends ListRecords
{
    protected static string $resource = JadwalJagaResource::class;
    
    protected $listeners = [
        'createMissingUsers' => 'handleCreateMissingUsers',
        'showMissingUsersDetail' => 'handleShowMissingUsersDetail'
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_schedule')
                ->label('🎯 Atur Jadwal Shift')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->tooltip('Pilih hingga 5 pegawai sekaligus untuk satu shift')
                ->modal()
                ->modalWidth('7xl')
                ->form([
                    \Filament\Forms\Components\Section::make('Pilih Tanggal & Shift')
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('tanggal_jaga')
                                ->label('Tanggal Jaga')
                                ->required()
                                ->type('date')
                                ->helperText('⚠️ Tidak dapat memilih tanggal kemarin atau masa lalu. Pilih tanggal hari ini atau masa depan. Validasi shift akan dicek saat submit.')
                                ->reactive()
                                ->extraInputAttributes([
                                    'min' => \Carbon\Carbon::today('Asia/Jakarta')->format('Y-m-d'),
                                    'max' => \Carbon\Carbon::today('Asia/Jakarta')->addYear()->format('Y-m-d')
                                ])
                                ->validationAttribute('Tanggal Jaga'),
                            \Filament\Forms\Components\Select::make('shift_template_id')
                                ->label('Template Shift')
                                ->options(function () {
                                    return \App\Models\ShiftTemplate::all()
                                        ->mapWithKeys(fn ($shift) => [
                                            $shift->id => $shift->shift_display
                                        ]);
                                })
                                ->required()
                                ->searchable()
                                ->reactive(),
                            \Filament\Forms\Components\Select::make('jenis_tugas')
                                ->label('Jenis Tugas')
                                ->options([
                                    'pendaftaran' => 'Pendaftaran',
                                    'pelayanan' => 'Pelayanan',
                                    'dokter_jaga' => 'Dokter Jaga'
                                ])
                                ->required()
                                ->reactive()
                                ->helperText('Pilih jenis tugas untuk menentukan pegawai yang tersedia'),
                        ])->columns(3),
                        
                    \Filament\Forms\Components\Section::make('Pegawai yang Sudah Terjadwal')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('existing_schedule')
                                ->label('')
                                ->content(function (callable $get) {
                                    $tanggal = $get('tanggal_jaga');
                                    $shiftId = $get('shift_template_id');
                                    
                                    if (!$tanggal || !$shiftId) {
                                        return '⏳ Pilih tanggal dan shift untuk melihat jadwal yang sudah ada.';
                                    }
                                    
                                    $existingSchedules = \App\Models\JadwalJaga::where('tanggal_jaga', $tanggal)
                                        ->where('shift_template_id', $shiftId)
                                        ->with(['pegawai', 'shiftTemplate'])
                                        ->get();
                                        
                                    if ($existingSchedules->isEmpty()) {
                                        return '✅ Belum ada pegawai yang terjadwal untuk shift ini.';
                                    }
                                    
                                    $html = '<div class="space-y-2">';
                                    $html .= '<div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pegawai yang sudah terjadwal (' . $existingSchedules->count() . ' orang):</div>';
                                    
                                    foreach ($existingSchedules as $schedule) {
                                        $html .= '<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border">';
                                        $html .= '<div class="flex items-center space-x-3">';
                                        $html .= '<div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">' . substr($schedule->pegawai->name, 0, 2) . '</div>';
                                        $html .= '<div class="font-medium text-gray-900 dark:text-gray-100">' . $schedule->pegawai->name . '</div>';
                                        $html .= '</div>';
                                        $html .= '<span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">' . $schedule->status_jaga . '</span>';
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div>';
                                    return new \Illuminate\Support\HtmlString($html);
                                })
                        ])
                        ->visible(fn (callable $get) => $get('tanggal_jaga') && $get('shift_template_id')),
                        
                    \Filament\Forms\Components\Section::make('Pilih Pegawai Baru (Maksimal 5)')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('pegawai_ids')
                                ->label('📋 Pilih Pegawai dari Manajemen Admin')
                                ->descriptions(function (callable $get) {
                                    $tanggal = $get('tanggal_jaga');
                                    $shiftId = $get('shift_template_id');
                                    $jenisTugas = $get('jenis_tugas');
                                    
                                    if (!$tanggal || !$shiftId || !$jenisTugas) {
                                        return [];
                                    }
                                    
                                    // Get comprehensive staff data based on task type
                                    if ($jenisTugas === 'dokter_jaga') {
                                        // Get from Dokter management
                                        $availableStaff = \App\Models\Dokter::where('aktif', true)->get();
                                    } else {
                                        // Get from Pegawai management for pendaftaran and pelayanan
                                        $availableStaff = \App\Models\Pegawai::where('aktif', true)
                                            ->whereIn('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])
                                            ->get();
                                    }
                                    
                                    // Exclude staff already scheduled for this SAME shift
                                    $excludeStaffIds = \App\Models\JadwalJaga::where('tanggal_jaga', $tanggal)
                                        ->where('shift_template_id', $shiftId)
                                        ->pluck('pegawai_id');
                                    
                                    $descriptions = [];
                                    foreach ($availableStaff as $staff) {
                                        // Use staff record ID directly
                                        $staffId = $jenisTugas === 'dokter_jaga' ? 'dokter_' . $staff->id : 'pegawai_' . $staff->id;
                                        
                                        // Show appropriate description based on task type
                                        if ($jenisTugas === 'dokter_jaga') {
                                            $descriptions[$staffId] = "🩺 " . $staff->jabatan_display . " • SIP: " . $staff->nomor_sip;
                                        } elseif ($jenisTugas === 'pendaftaran') {
                                            $descriptions[$staffId] = "📝 " . $staff->jabatan . " • " . $staff->jenis_pegawai . " • NIK: " . $staff->nik;
                                        } else { // pelayanan
                                            $descriptions[$staffId] = "🏥 " . $staff->jabatan . " • " . $staff->jenis_pegawai . " • NIK: " . $staff->nik;
                                        }
                                    }
                                    
                                    return $descriptions;
                                })
                                ->options(function (callable $get) {
                                    $tanggal = $get('tanggal_jaga');
                                    $shiftId = $get('shift_template_id');
                                    $jenisTugas = $get('jenis_tugas');
                                    
                                    if (!$tanggal || !$shiftId || !$jenisTugas) {
                                        return [];
                                    }
                                    
                                    // Get comprehensive staff data based on task type
                                    if ($jenisTugas === 'dokter_jaga') {
                                        // Get from Dokter management
                                        $availableStaff = \App\Models\Dokter::where('aktif', true)->get();
                                    } else {
                                        // Get from Pegawai management for pendaftaran and pelayanan
                                        $availableStaff = \App\Models\Pegawai::where('aktif', true)
                                            ->whereIn('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])
                                            ->get();
                                    }
                                    
                                    // Exclude staff already scheduled for this SAME shift
                                    $excludeStaffIds = \App\Models\JadwalJaga::where('tanggal_jaga', $tanggal)
                                        ->where('shift_template_id', $shiftId)
                                        ->pluck('pegawai_id');
                                    
                                    $options = [];
                                    foreach ($availableStaff as $staff) {
                                        // Use staff record ID directly  
                                        $staffId = $jenisTugas === 'dokter_jaga' ? 'dokter_' . $staff->id : 'pegawai_' . $staff->id;
                                        
                                        // Show all active staff for the selected task type
                                        $options[$staffId] = $staff->nama_lengkap;
                                    }
                                    
                                    return $options;
                                })
                                ->columns(2)
                                ->gridDirection('row')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Limit to 5 selections
                                    if (is_array($state) && count($state) > 5) {
                                        $limited = array_slice($state, 0, 5);
                                        $set('pegawai_ids', $limited);
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->warning()
                                            ->title('Maksimal 5 pegawai')
                                            ->body('Anda hanya bisa memilih maksimal 5 pegawai per shift.')
                                            ->send();
                                    }
                                })
                                ->helperText(function (callable $get) {
                                    $selected = $get('pegawai_ids') ?? [];
                                    $count = is_array($selected) ? count($selected) : 0;
                                    $remaining = 5 - $count;
                                    $jenisTugas = $get('jenis_tugas');
                                    
                                    $source = match($jenisTugas) {
                                        'dokter_jaga' => 'Manajemen Dokter',
                                        'pendaftaran' => 'Manajemen Pegawai (Pendaftaran)',
                                        'pelayanan' => 'Manajemen Pegawai (Pelayanan)',
                                        default => 'Manajemen Pegawai'
                                    };
                                    
                                    if ($count === 0) {
                                        return "💡 Data diambil dari {$source}. Pilih pegawai yang akan bertugas (maksimal 5 orang)";
                                    }
                                    
                                    if ($remaining > 0) {
                                        return "✅ {$count} pegawai dipilih dari {$source}. Tersisa {$remaining} slot.";
                                    }
                                    
                                    return "🔥 Shift penuh! 5 pegawai sudah dipilih dari {$source}.";
                                }),
                        ])
                        ->visible(fn (callable $get) => $get('tanggal_jaga') && $get('shift_template_id') && $get('jenis_tugas')),
                ])
                ->action(function (array $data): void {
                    // Custom validation for modal action
                    $validator = \Validator::make($data, [
                        'tanggal_jaga' => ['required', 'date'],
                        'shift_template_id' => ['required', 'exists:shift_templates,id'],
                        'jenis_tugas' => ['required', 'string'],
                        'pegawai_ids' => ['required', 'array', 'min:1']
                    ]);
                    
                    if ($validator->fails()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Validation Error')
                            ->body($validator->errors()->first())
                            ->send();
                        return;
                    }
                    
                    // Additional shift-specific validation
                    $selectedDate = \Carbon\Carbon::parse($data['tanggal_jaga']);
                    $today = \Carbon\Carbon::today('Asia/Jakarta');
                    $yesterday = $today->copy()->subDay();
                    $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
                    
                    // Block kemarin/masa lalu
                    if ($selectedDate->isBefore($today)) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Tanggal Tidak Valid')
                            ->body('Tidak dapat memilih tanggal kemarin atau masa lalu. Silakan pilih tanggal hari ini atau masa depan.')
                            ->send();
                        return;
                    }
                    
                    // Check shift timing for today
                    if ($selectedDate->isSameDay($today) && isset($data['shift_template_id'])) {
                        $shiftTemplate = \App\Models\ShiftTemplate::find($data['shift_template_id']);
                        if ($shiftTemplate) {
                            $shiftStartTime = \Carbon\Carbon::parse($shiftTemplate->jam_masuk);
                            $todayShiftStart = $today->copy()->setHour($shiftStartTime->hour)->setMinute($shiftStartTime->minute)->setSecond(0);
                            
                            if ($currentTime->greaterThan($todayShiftStart)) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Shift Sudah Dimulai')
                                    ->body("Tidak dapat memilih hari ini karena shift {$shiftTemplate->nama_shift} sudah dimulai pada jam {$shiftTemplate->jam_masuk_format}. Sekarang sudah jam {$currentTime->format('H:i')}. Silakan pilih tanggal mulai besok.")
                                    ->send();
                                return;
                            }
                        }
                    }
                    if (empty($data['pegawai_ids'])) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Gagal!')
                            ->body('Pilih minimal 1 pegawai untuk dijadwalkan.')
                            ->send();
                        return;
                    }

                    $created = 0;
                    $skipped = 0;
                    $duplicateCount = 0;
                    $missingUsers = [];
                    
                    // First pass: Validate and collect missing user accounts
                    foreach ($data['pegawai_ids'] as $staffId) {
                        $staffInfo = $this->validateAndProcessStaff($staffId, $data['jenis_tugas']);
                        
                        if (!$staffInfo) {
                            $skipped++;
                            continue;
                        }
                        
                        if (!$staffInfo['user']) {
                            $missingUsers[] = $staffInfo;
                            continue;
                        }
                    }
                    
                    // If there are missing users, show enhanced notification with action options
                    if (!empty($missingUsers)) {
                        $this->handleMissingUserAccounts($missingUsers);
                        return; // Stop execution and let admin handle missing accounts
                    }
                    
                    // Second pass: Create schedules (all staff have user accounts)
                    foreach ($data['pegawai_ids'] as $staffId) {
                        $staffInfo = $this->validateAndProcessStaff($staffId, $data['jenis_tugas']);
                        
                        if (!$staffInfo || !$staffInfo['user']) {
                            $skipped++;
                            continue;
                        }

                        // Check if already exists to prevent duplicates
                        $existingJadwal = \App\Models\JadwalJaga::whereDate('tanggal_jaga', $data['tanggal_jaga'])
                            ->where('shift_template_id', $data['shift_template_id'])
                            ->where('pegawai_id', $staffInfo['user']->id)
                            ->first();

                        if (!$existingJadwal) {
                            try {
                                \App\Models\JadwalJaga::create([
                                    'tanggal_jaga' => $data['tanggal_jaga'],
                                    'shift_template_id' => $data['shift_template_id'],
                                    'pegawai_id' => $staffInfo['user']->id,
                                    'unit_kerja' => $staffInfo['unit_kerja'],
                                    'unit_instalasi' => $staffInfo['unit_kerja'], // Keep for backward compatibility
                                    'peran' => $staffInfo['peran'],
                                    'status_jaga' => 'Aktif',
                                    'keterangan' => 'Bulk schedule - ' . $staffInfo['name'] . ' (' . $data['jenis_tugas'] . ') - ' . now()->format('d/m/Y H:i'),
                                ]);
                                $created++;
                            } catch (\Illuminate\Database\QueryException $e) {
                                if ($e->getCode() == 23000) { // Integrity constraint violation
                                    $duplicateCount++;
                                    \Log::warning('Duplicate jadwal jaga detected', [
                                        'pegawai' => $staffInfo['name'],
                                        'tanggal' => $data['tanggal_jaga'],
                                        'shift_template_id' => $data['shift_template_id']
                                    ]);
                                } else {
                                    throw $e; // Re-throw if it's not a duplicate error
                                }
                            }
                        } else {
                            $duplicateCount++;
                        }
                    }

                    if ($created > 0) {
                        $message = $created . ' pegawai berhasil dijadwalkan.';
                        if ($skipped > 0) {
                            $message .= ' ' . $skipped . ' pegawai dilewati (belum memiliki akun User).';
                        }
                        if ($duplicateCount > 0) {
                            $message .= ' ' . $duplicateCount . ' pegawai dilewati (jadwal sudah ada).';
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Berhasil!')
                            ->body($message)
                            ->send();
                    } else {
                        $message = 'Tidak ada pegawai yang bisa dijadwalkan.';
                        if ($skipped > 0) {
                            $message .= ' ' . $skipped . ' pegawai dilewati karena belum memiliki akun User.';
                        }
                        if ($duplicateCount > 0) {
                            $message .= ' ' . $duplicateCount . ' pegawai dilewati (jadwal sudah ada).';
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Tidak ada perubahan')
                            ->body($message)
                            ->send();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => $this->getModel()::count()),

            'dokter' => Tab::make('Dokter')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('peran', 'Dokter'))
                ->badge(fn () => $this->getModel()::where('peran', 'Dokter')->count())
                ->badgeColor('success'),

            'paramedis' => Tab::make('Paramedis')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('peran', 'Paramedis'))
                ->badge(fn () => $this->getModel()::where('peran', 'Paramedis')->count())
                ->badgeColor('info'),

            'non_paramedis' => Tab::make('Non Paramedis')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('peran', 'NonParamedis'))
                ->badge(fn () => $this->getModel()::where('peran', 'NonParamedis')->count())
                ->badgeColor('warning'),

            'upcoming' => Tab::make('Mendatang')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereDate('tanggal_jaga', '>=', \Carbon\Carbon::today('Asia/Jakarta'));
                })
                ->badge(fn () => $this->getModel()::whereDate('tanggal_jaga', '>=', \Carbon\Carbon::today('Asia/Jakarta'))->count())
                ->badgeColor('primary'),

            'past' => Tab::make('Riwayat (Terlewat)')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereDate('tanggal_jaga', '<', \Carbon\Carbon::today('Asia/Jakarta'));
                })
                ->badge(fn () => $this->getModel()::whereDate('tanggal_jaga', '<', \Carbon\Carbon::today('Asia/Jakarta'))->count())
                ->badgeColor('gray'),
        ];
    }
    
    /**
     * Validate and process staff information for scheduling
     */
    private function validateAndProcessStaff(string $staffId, string $jenistugas): ?array
    {
        if (str_starts_with($staffId, 'dokter_')) {
            $actualId = str_replace('dokter_', '', $staffId);
            $dokter = \App\Models\Dokter::find($actualId);
            
            if (!$dokter) {
                return null;
            }
            
            return [
                'type' => 'dokter',
                'id' => $actualId,
                'model' => $dokter,
                'user' => $dokter->user,
                'name' => $dokter->nama_lengkap,
                'peran' => 'Dokter',
                'unit_kerja' => 'Dokter Jaga',
                'email_base' => strtolower(str_replace(' ', '.', $dokter->nama_lengkap)),
                'nip_nik' => $dokter->user_id ?? null
            ];
            
        } elseif (str_starts_with($staffId, 'pegawai_')) {
            $actualId = str_replace('pegawai_', '', $staffId);
            $pegawai = \App\Models\Pegawai::find($actualId);
            
            if (!$pegawai) {
                return null;
            }
            
            $unitKerja = match($jenistugas) {
                'pendaftaran' => 'Pendaftaran',
                'pelayanan' => 'Pelayanan',
                default => 'Pelayanan'
            };
            
            return [
                'type' => 'pegawai',
                'id' => $actualId,
                'model' => $pegawai,
                'user' => $pegawai->user,
                'name' => $pegawai->nama_lengkap,
                'peran' => $pegawai->jenis_pegawai === 'Paramedis' ? 'Paramedis' : 'NonParamedis',
                'unit_kerja' => $unitKerja,
                'email_base' => strtolower(str_replace(' ', '.', $pegawai->nama_lengkap)),
                'nip_nik' => $pegawai->nik
            ];
        }
        
        return null;
    }
    
    /**
     * Handle missing user accounts with intelligent options
     */
    private function handleMissingUserAccounts(array $missingUsers): void
    {
        $count = count($missingUsers);
        $names = collect($missingUsers)->pluck('name')->take(3)->implode(', ');
        
        if ($count > 3) {
            $names .= ' dan ' . ($count - 3) . ' lainnya';
        }
        
        // Store missing users data in session for action handling
        session()->put('missing_users_data', $missingUsers);
        
        // Enhanced notification with action buttons using dispatch events
        \Filament\Notifications\Notification::make()
            ->warning()
            ->title('❌ Akun User Belum Tersedia')
            ->body("**{$count} pegawai** belum memiliki akun User: **{$names}**\n\n🔧 **Solusi Tersedia:**\n📝 Buat akun otomatis\n✏️ Buat akun manual")
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('create_auto')
                    ->label('🚀 Buat Akun Otomatis')
                    ->color('success')
                    ->dispatch('createMissingUsers')
                    ->close(),
                \Filament\Notifications\Actions\Action::make('create_auto_url')
                    ->label('🔗 Buat Akun (URL)')
                    ->color('success')
                    ->url(route('filament.jadwal-jaga.create-missing-users'))
                    ->close(),
                \Filament\Notifications\Actions\Action::make('create_manual')
                    ->label('✏️ Buat Manual')
                    ->color('primary')
                    ->url(route('filament.admin.resources.users.create'))
                    ->openUrlInNewTab(),
                \Filament\Notifications\Actions\Action::make('view_details')
                    ->label('📋 Lihat Detail')
                    ->color('gray')
                    ->dispatch('showMissingUsersDetail')
                    ->close()
            ])
            ->send();
    }
    
    /**
     * Create missing user accounts automatically
     */
    public function createMissingUserAccounts(array $missingUsers): void
    {
        // Log the start of account creation process
        \Log::info('Starting automatic user account creation', [
            'count' => count($missingUsers),
            'users' => collect($missingUsers)->pluck('name')->toArray()
        ]);
        
        $created = 0;
        $failed = [];
        
        foreach ($missingUsers as $staff) {
            try {
                \Log::info('Processing user account creation', [
                    'staff_name' => $staff['name'],
                    'staff_type' => $staff['type'],
                    'staff_role' => $staff['peran'] ?? 'N/A'
                ]);
                
                // Generate secure default credentials
                $email = $this->generateUniqueEmail($staff['email_base']);
                $password = 'Password123!'; // Should be changed on first login
                
                // Determine role based on staff type
                $roleName = match($staff['type']) {
                    'dokter' => 'dokter',
                    'pegawai' => $staff['peran'] === 'Paramedis' ? 'paramedis' : 'petugas'
                };
                
                $role = \App\Models\Role::where('name', $roleName)->first();
                
                if (!$role) {
                    \Log::warning('Role not found for user creation', [
                        'staff_name' => $staff['name'],
                        'required_role' => $roleName,
                        'available_roles' => \App\Models\Role::pluck('name')->toArray()
                    ]);
                    $failed[] = $staff['name'] . ' (Role tidak ditemukan)';
                    continue;
                }
                
                // Check if user already exists with this NIP (including soft deleted)
                $existingUser = \App\Models\User::withTrashed()->where('nip', $staff['nip_nik'])->first();
                
                if ($existingUser) {
                    // If user is soft deleted, restore it
                    if ($existingUser->trashed()) {
                        $existingUser->restore();
                        \Log::info('Restored soft deleted user', ['user_id' => $existingUser->id, 'nip' => $staff['nip_nik']]);
                    }
                    
                    // Update existing user data if needed
                    $existingUser->update([
                        'name' => $staff['name'],
                        'email' => $email,
                    ]);
                    
                    $user = $existingUser;
                    \Log::info('Using existing user account', ['user_id' => $user->id, 'nip' => $staff['nip_nik']]);
                } else {
                    // Create new User account
                    $user = \App\Models\User::create([
                        'name' => $staff['name'],
                        'email' => $email,
                        'password' => \Hash::make($password),
                        'role_id' => $role->id,
                        'nip' => $staff['nip_nik'],
                        'email_verified_at' => now()
                    ]);
                    
                    \Log::info('Created new user account', ['user_id' => $user->id, 'nip' => $staff['nip_nik']]);
                }
                
                // Link to staff record
                if ($staff['type'] === 'dokter') {
                    $staff['model']->update(['user_id' => $user->id]);
                } elseif ($staff['type'] === 'pegawai') {
                    $staff['model']->update(['user_id' => $user->id]);
                }
                
                \Log::info('User account created successfully', [
                    'staff_name' => $staff['name'],
                    'user_id' => $user->id,
                    'email' => $email,
                    'role' => $roleName
                ]);
                
                $created++;
                
            } catch (\Exception $e) {
                \Log::error('Failed to create user account', [
                    'staff_name' => $staff['name'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed[] = $staff['name'] . ' (' . $e->getMessage() . ')';
            }
        }
        
        // Success notification
        if ($created > 0) {
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('✅ Akun User Berhasil Dibuat')
                ->body("**{$created} akun** berhasil dibuat dengan password default: **Password123!**\n\n⚠️ **Penting:** Minta pegawai mengganti password saat login pertama.")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('retry_schedule')
                        ->label('🔄 Coba Jadwal Ulang')
                        ->color('primary')
                        ->action(function () {
                            $this->refreshData();
                        })
                ])
                ->send();
        }
        
        // Failure notification
        if (!empty($failed)) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('❌ Beberapa Akun Gagal Dibuat')
                ->body('Gagal: ' . implode(', ', $failed))
                ->send();
        }
    }
    
    /**
     * Show detailed information about missing users
     */
    private function showMissingUsersDetail(array $missingUsers): void
    {
        $details = collect($missingUsers)->map(function ($staff) {
            return "• **{$staff['name']}** ({$staff['peran']}) - {$staff['unit_kerja']}";
        })->implode("\n");
        
        \Filament\Notifications\Notification::make()
            ->info()
            ->title('📋 Detail Pegawai Tanpa Akun User')
            ->body("**Total: " . count($missingUsers) . " pegawai**\n\n{$details}\n\n💡 **Rekomendasi:**\n1. Gunakan 'Buat Akun Otomatis' untuk kemudahan\n2. Atau buat manual di menu User Management")
            ->persistent()
            ->send();
    }
    
    /**
     * Generate unique email for staff
     */
    private function generateUniqueEmail(string $baseEmail): string
    {
        $email = $baseEmail . '@dokterku.local';
        $counter = 1;
        
        while (\App\Models\User::where('email', $email)->exists()) {
            $email = $baseEmail . $counter . '@dokterku.local';
            $counter++;
        }
        
        return $email;
    }
    
    /**
     * Refresh the current page data
     */
    private function refreshData(): void
    {
        $this->redirect(request()->header('Referer'));
    }
    
    /**
     * Handle create missing users event from notification action
     */
    public function handleCreateMissingUsers(): void
    {
        \Log::info('Handling create missing users event via dispatch');
        
        $missingUsers = session()->get('missing_users_data', []);
        
        if (empty($missingUsers)) {
            \Log::warning('No missing users data found in session');
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('❌ Data Tidak Tersedia')
                ->body('Data pegawai yang hilang tidak ditemukan. Silakan coba lagi.')
                ->send();
            return;
        }
        
        \Log::info('Found missing users data in session', [
            'count' => count($missingUsers)
        ]);
        
        $this->createMissingUserAccounts($missingUsers);
        
        // Clear session data after processing
        session()->forget('missing_users_data');
        
        \Log::info('Missing users data cleared from session');
    }
    
    /**
     * Handle show missing users detail event from notification action
     */
    public function handleShowMissingUsersDetail(): void
    {
        $missingUsers = session()->get('missing_users_data', []);
        
        if (empty($missingUsers)) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('❌ Data Tidak Tersedia')
                ->body('Data pegawai yang hilang tidak ditemukan. Silakan coba lagi.')
                ->send();
            return;
        }
        
        $this->showMissingUsersDetail($missingUsers);
    }
}