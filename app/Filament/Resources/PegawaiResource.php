<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PegawaiResource\Pages;
use App\Models\Pegawai;
use App\Models\EmployeeCard;
use App\Models\User;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Manajemen Pegawai';

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Pegawai';

    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 10;

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('manajer');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('manajer');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pegawai')
                    ->schema([
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK Pegawai')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan NIK pegawai')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Pegawai')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan email pegawai')
                            ->helperText('Email digunakan untuk semua role pegawai ini')
                            ->suffixIcon('heroicon-m-envelope')
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->maxDate(now()->subYears(17))
                            ->minDate(now()->subYears(80)) 
                            ->helperText('Usia minimal 17 tahun')
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informasi Pekerjaan')
                    ->schema([
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->required()
                            ->placeholder('e.g. Perawat, Kasir, IT Support')
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_pegawai')
                            ->label('Jenis Pegawai')
                            ->options([
                                'Paramedis' => 'Paramedis',
                                'Non-Paramedis' => 'Non-Paramedis',
                            ])
                            ->required()
                            ->default('Non-Paramedis')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('aktif')
                            ->label('Status Aktif')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('🔐 Manajemen Akun Login')
                    ->description('Pengaturan akun login pegawai (khusus admin)')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username Login')
                                    ->nullable()
                                    ->placeholder('Auto-generate jika kosong')
                                    ->helperText('Username untuk login (huruf, angka, spasi, titik, koma diizinkan). Username dari pegawai yang dihapus dapat digunakan kembali.')
                                    ->rules(['nullable', 'regex:/^[a-zA-Z0-9\s.,-]+$/'])
                                    ->minLength(3)
                                    ->maxLength(50)
                                    ->suffixIcon('heroicon-m-user')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, ?string $operation, $record) {
                                        if (empty($state)) return;
                                        
                                        // Check username availability using our new method
                                        $availability = \App\Models\Pegawai::checkUsernameAvailability(
                                            $state, 
                                            $operation === 'edit' ? $record?->id : null
                                        );
                                        
                                        if (!$availability['available']) {
                                            $set('username', ''); // Clear invalid username
                                            \Filament\Notifications\Notification::make()
                                                ->title('Username Tidak Tersedia')
                                                ->body($availability['message'])
                                                ->danger()
                                                ->send();
                                        } elseif ($availability['reused_from_deleted']) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Username Dapat Digunakan')
                                                ->body($availability['message'])
                                                ->warning()
                                                ->send();
                                        }
                                        
                                        \Log::info('PegawaiResource: Username availability check', [
                                            'username' => $state,
                                            'available' => $availability['available'],
                                            'reused_from_deleted' => $availability['reused_from_deleted'] ?? false
                                        ]);
                                    })
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->placeholder(fn (string $operation): string => 
                                        $operation === 'create' ? 'Auto-generate jika kosong' : 'Kosongkan jika tidak ingin mengubah password')
                                    ->helperText('Minimal 6 karakter')
                                    ->minLength(6)
                                    ->maxLength(50)
                                    ->suffixIcon('heroicon-m-key')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->placeholder('Ketik ulang password baru')
                                    ->helperText('Harus sama dengan password baru')
                                    ->same('password')
                                    ->requiredWith('password')
                                    ->suffixIcon('heroicon-m-check-circle')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status_akun')
                                    ->label('Status Akun Login')
                                    ->options([
                                        'Aktif' => '✅ Aktif - Dapat Login',
                                        'Suspend' => '❌ Suspend - Tidak Dapat Login',
                                    ])
                                    ->default('Aktif')
                                    ->helperText('Status akun login pegawai')
                                    ->suffixIcon('heroicon-m-shield-check')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('password_info')
                            ->label('ℹ️ Informasi Password')
                            ->content(function () {
                                return new \Illuminate\Support\HtmlString('
                                    <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <ul class="space-y-1">
                                            <li>• Password minimal 6 karakter, maksimal 50 karakter</li>
                                            <li>• Gunakan kombinasi huruf, angka, dan simbol untuk keamanan</li>
                                            <li>• Konfirmasi password harus sama dengan password baru</li>
                                            <li>• Klik ikon mata untuk melihat/menyembunyikan password</li>
                                            <li>• Jika kosong, sistem akan auto-generate password</li>
                                        </ul>
                                    </div>
                                ');
                            })
                            ->columnSpan('full'),
                    ])
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
                '2xl' => 3,
            ])
            ->paginated([12, 24, 48, 96, 'all'])
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('📋 Tampilan Tabel')
            )
            ->columns([
                Tables\Columns\Layout\View::make('filament.components.pegawai-card-simple')
                    ->components([
                        // Hidden columns for search functionality
                        Tables\Columns\TextColumn::make('nama_lengkap')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Tables\Columns\TextColumn::make('nik')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Tables\Columns\TextColumn::make('jabatan')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Tables\Columns\TextColumn::make('jenis_pegawai')
                            ->toggleable(isToggledHiddenByDefault: true),
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pegawai')
                    ->label('Jenis Pegawai')
                    ->options([
                        'Paramedis' => 'Paramedis',
                        'Non-Paramedis' => 'Non-Paramedis',
                    ])
                    ->placeholder('Semua Jenis'),

                Tables\Filters\SelectFilter::make('aktif')
                    ->label('Status')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Tidak Aktif',
                    ])
                    ->placeholder('Semua Status'),

                Tables\Filters\SelectFilter::make('status_akun')
                    ->label('Status Login')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Suspend' => 'Suspend',
                    ])
                    ->placeholder('Semua Status Login')
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Tables\Filters\TernaryFilter::make('has_login_account')
                    ->label('Akun Login')
                    ->placeholder('Semua')
                    ->trueLabel('Punya Akun Login')
                    ->falseLabel('Belum Punya Akun Login')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('username')->whereNotNull('password'),
                        false: fn ($query) => $query->whereNull('username')->orWhereNull('password'),
                    )
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Tables\Filters\TernaryFilter::make('has_user_account')
                    ->label('Akun User')
                    ->placeholder('Semua')
                    ->trueLabel('Punya Akun User')
                    ->falseLabel('Belum Punya Akun User')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('user_id'),
                        false: fn ($query) => $query->whereNull('user_id'),
                    ),

                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('jabatan')
                    ->form([
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->placeholder('Cari berdasarkan jabatan...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['jabatan'],
                                fn (Builder $query, $jabatan): Builder => $query->where('jabatan', 'like', "%{$jabatan}%"),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Basic Actions
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-m-eye')
                        ->modalHeading(fn ($record) => 'Detail Pegawai: '.$record->nama_lengkap)
                        ->modalContent(fn ($record) => view('filament.pages.pegawai-detail', ['record' => $record])),
                    Tables\Actions\EditAction::make()
                        ->label('Edit Data')
                        ->icon('heroicon-m-pencil-square'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-m-trash'),
                    
                    // Account Actions (Admin only)
                    Tables\Actions\Action::make('create_account')
                        ->label('Buat Akun Login')
                        ->icon('heroicon-m-user-plus')
                        ->color('success')
                        ->visible(fn ($record) => !$record->has_login_account && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            $result = $record->createLoginAccount();
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Akun Login Berhasil Dibuat')
                                    ->body("Username: {$result['username']}<br>Password: {$result['password']}")
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Membuat Akun')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun Login Pegawai')
                        ->modalDescription('Akun login akan dibuat secara otomatis dengan username dan password yang di-generate sistem.')
                        ->modalSubmitActionLabel('Buat Akun'),

                    Tables\Actions\Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-m-key')
                        ->color('warning')
                        ->visible(fn ($record) => $record->has_login_account && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            $result = $record->resetPassword();
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Password Berhasil Direset')
                                    ->body("Password baru: {$result['password']}")
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Reset Password')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Reset Password')
                        ->modalDescription('Password akan direset dan password baru akan di-generate secara otomatis.')
                        ->modalSubmitActionLabel('Reset Password'),

                    // User Management Actions
                    Action::make('create_user_account')
                        ->label('Buat Akun User')
                        ->icon('heroicon-m-identification')
                        ->color('info')
                        ->visible(fn ($record) => true) // Always visible now - can create multiple accounts
                        ->form([
                            Forms\Components\Select::make('jenis_pegawai_for_role')
                                ->label('Menentukan role pengguna sebagai:')
                                ->options(function ($record) {
                                    // Get existing roles for this pegawai
                                    $existingRoles = $record->users()->with('role')->get()->pluck('role.name')->toArray();
                                    
                                    // All available roles
                                    $allRoles = [
                                        'Paramedis' => 'Paramedis (role: paramedis)',
                                        'Petugas' => 'Petugas (role: petugas)',
                                        'Bendahara' => 'Bendahara (role: bendahara)',
                                    ];
                                    
                                    // Filter out existing roles
                                    $availableRoles = [];
                                    foreach ($allRoles as $key => $label) {
                                        $roleName = strtolower($key);
                                        if (!in_array($roleName, $existingRoles)) {
                                            $availableRoles[$key] = $label;
                                        }
                                    }
                                    
                                    return $availableRoles;
                                })
                                ->default(fn ($record) => $record->jenis_pegawai === 'Paramedis' ? 'Paramedis' : 'Petugas')
                                ->required()
                                ->live()
                                ->helperText(function ($record) {
                                    $existingRoles = $record->users()->with('role')->get()->pluck('role.display_name')->toArray();
                                    if (count($existingRoles) > 0) {
                                        return 'Role yang sudah ada: ' . implode(', ', $existingRoles) . '. Pilih role baru yang belum ada.';
                                    }
                                    return 'Admin bisa membuat satu pegawai memiliki multiple role sebagai Bendahara, Paramedis atau Petugas';
                                }),
                                
                            Forms\Components\TextInput::make('custom_username')
                                ->label('Username Login (Opsional)')
                                ->placeholder('Biarkan kosong untuk auto-generate berdasarkan nama')
                                ->maxLength(50)
                                ->minLength(3)
                                ->regex('/^[a-zA-Z0-9._-]+$/')
                                ->helperText('Username harus unik per role. Format: huruf, angka, titik, underscore, dash')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state, $record) {
                                    if (empty($state)) return;
                                    
                                    // Get selected role
                                    $selectedRole = $get('jenis_pegawai_for_role');
                                    if (!$selectedRole) return;
                                    
                                    $roleName = match($selectedRole) {
                                        'Paramedis' => 'paramedis',
                                        'Petugas' => 'petugas',
                                        'Bendahara' => 'bendahara',
                                        default => 'petugas'
                                    };
                                    
                                    $role = \App\Models\Role::where('name', $roleName)->first();
                                    if (!$role) return;
                                    
                                    // Check if username exists for this role or other critical roles
                                    $existingUser = \App\Models\User::where('username', $state)
                                        ->when($record?->user_id, function ($query) use ($record) {
                                            return $query->where('id', '!=', $record->user_id);
                                        })
                                        ->first();
                                        
                                    if ($existingUser) {
                                        $set('custom_username', '');
                                        \Filament\Notifications\Notification::make()
                                            ->title('Username Sudah Digunakan')
                                            ->body("Username '{$state}' sudah digunakan oleh {$existingUser->role->display_name}. Username harus unik untuk setiap role.")
                                            ->danger()
                                            ->send();
                                    }
                                }),
                                
                            Forms\Components\Placeholder::make('pegawai_email_info')
                                ->label('Email Pegawai')
                                ->content(function ($record) {
                                    if ($record && $record->email) {
                                        return "Email yang akan digunakan: {$record->email}";
                                    }
                                    return 'Email belum diatur pada data pegawai. Silakan set email di bagian Informasi Pegawai terlebih dahulu.';
                                })
                                ->helperText('Email diambil dari data pegawai dan digunakan untuk semua role'),
                                
                            Forms\Components\TextInput::make('custom_password')
                                ->label('Password Kustom (Opsional)')
                                ->password()
                                ->revealable()
                                ->placeholder('Biarkan kosong untuk auto-generate')
                                ->minLength(6)
                                ->maxLength(50)
                                ->helperText('Minimal 6 karakter. Biarkan kosong untuk password otomatis 8 karakter'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                // Map selected role to actual role name
                                $roleName = match($data['jenis_pegawai_for_role']) {
                                    'Paramedis' => 'paramedis',
                                    'Petugas' => 'petugas',
                                    'Bendahara' => 'bendahara',
                                    default => 'petugas'
                                };
                                
                                $role = Role::where('name', $roleName)->first();
                                
                                if (!$role) {
                                    Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body("Role '{$roleName}' tidak ditemukan dalam sistem.")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Handle username - use custom or auto-generate
                                if (!empty($data['custom_username'])) {
                                    $username = $data['custom_username'];
                                    
                                    // Validate uniqueness with role-specific check
                                    $existingUser = User::where('username', $username)->first();
                                    if ($existingUser) {
                                        Notification::make()
                                            ->title('Gagal Membuat Akun User')
                                            ->body("Username '{$username}' sudah digunakan oleh {$existingUser->role->display_name}. Silakan pilih username lain.")
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                } else {
                                    // Auto-generate username with role prefix for uniqueness
                                    $rolePrefix = match($roleName) {
                                        'paramedis' => 'pm',
                                        'petugas' => 'pt',
                                        'bendahara' => 'bd',
                                        default => 'usr'
                                    };
                                    
                                    $baseUsername = strtolower(str_replace([' ', '.', ','], '', $record->nama_lengkap));
                                    $baseUsername = Str::ascii($baseUsername);
                                    $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
                                    $baseUsername = substr($baseUsername, 0, 15);
                                    
                                    $username = $rolePrefix . '_' . $baseUsername;
                                    $counter = 1;
                                    
                                    while (User::where('username', $username)->exists()) {
                                        $username = $rolePrefix . '_' . $baseUsername . $counter;
                                        $counter++;
                                    }
                                }

                                // Handle password - use custom or auto-generate
                                $password = !empty($data['custom_password']) ? $data['custom_password'] : Str::random(8);

                                // Use the pegawai's email (required field)
                                $email = $record->email;
                                
                                // Check if email is set
                                if (empty($email)) {
                                    Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body('Email pegawai belum diset. Silakan set email di bagian Informasi Pegawai terlebih dahulu.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                // Double check email uniqueness - exclude existing users for this pegawai
                                $existingEmailUser = User::where('email', $email)
                                    ->where('pegawai_id', '!=', $record->id)
                                    ->first();
                                if ($existingEmailUser) {
                                    Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body("Email '{$email}' sudah digunakan oleh pegawai lain ({$existingEmailUser->name}). Setiap pegawai harus memiliki email unik.")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $user = User::create([
                                    'role_id' => $role->id,
                                    'pegawai_id' => $record->id, // New: link to pegawai
                                    'name' => $record->nama_lengkap,
                                    'username' => $username,
                                    'email' => $email,
                                    'password' => Hash::make($password),
                                    'nip' => $record->nik,
                                    'tanggal_bergabung' => now()->toDateString(),
                                    'is_active' => $record->aktif,
                                ]);

                                // Legacy: update user_id if this is the first user account for pegawai
                                if (!$record->user_id) {
                                    $record->update(['user_id' => $user->id]);
                                }

                                Notification::make()
                                    ->title('Akun User Berhasil Dibuat')
                                    ->body("Username: {$username}<br>Email: {$email}<br>Password: {$password}<br>Role: {$role->display_name}")
                                    ->success()
                                    ->persistent()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Membuat Akun User')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun User Pegawai')
                        ->modalDescription('Akun User akan dibuat dan dihubungkan dengan record pegawai ini.')
                        ->modalSubmitActionLabel('Buat Akun User'),

                    // Advanced User Management
                    Action::make('manage_user_account')
                        ->label('Kelola Semua Role')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->color('warning')
                        ->visible(fn ($record) => $record->users()->count() > 0)
                        ->form([
                            Forms\Components\Section::make('Semua User Accounts')
                                ->description('Kelola semua role yang dimiliki pegawai ini')
                                ->schema([
                                    Forms\Components\Placeholder::make('all_accounts_info')
                                        ->label('Role yang dimiliki')
                                        ->content(function ($record) {
                                            $users = $record->users()->with('role')->get();
                                            if ($users->isEmpty()) return 'Tidak ada user account ditemukan';
                                            
                                            $accountsInfo = $users->map(function ($user) {
                                                $status = $user->is_active ? 'Aktif' : 'Nonaktif';
                                                return "• {$user->role->display_name} - {$user->username} ({$user->email}) - Status: {$status}";
                                            })->join('<br>');
                                            
                                            return new \Illuminate\Support\HtmlString($accountsInfo);
                                        }),
                                ]),
                                
                            Forms\Components\Section::make('Edit Semua Role Sekaligus')
                                ->schema([
                                    Forms\Components\Repeater::make('user_accounts')
                                        ->label('User Accounts')
                                        ->schema([
                                            Forms\Components\Hidden::make('user_id'),
                                            
                                            Forms\Components\TextInput::make('role_display_name')
                                                ->label('Role')
                                                ->disabled()
                                                ->dehydrated(false),
                                                
                                            Forms\Components\TextInput::make('username')
                                                ->label('Username')
                                                ->required()
                                                ->maxLength(50)
                                                ->minLength(3)
                                                ->regex('/^[a-zA-Z0-9._-]+$/')
                                                ->helperText('Username harus unik'),
                                                
                                            Forms\Components\Placeholder::make('email_display')
                                                ->label('Email')
                                                ->content(function ($record) {
                                                    return $record->email ?? 'Email belum diset di data pegawai';
                                                })
                                                ->helperText('Email diambil dari data pegawai (tidak bisa diubah per role)'),
                                                
                                            Forms\Components\TextInput::make('new_password')
                                                ->label('Password Baru (Opsional)')
                                                ->password()
                                                ->revealable()
                                                ->placeholder('Kosongkan jika tidak ingin mengubah')
                                                ->minLength(6)
                                                ->maxLength(50),
                                                
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Status Aktif')
                                                ->default(true),
                                        ])
                                        ->columns(2)
                                        ->defaultItems(0)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false),
                                ])
                                ->collapsible(),
                        ])
                        ->fillForm(function ($record) {
                            // Pre-populate the form with existing user account data
                            $users = $record->users()->with('role')->get();
                            
                            $userAccounts = $users->map(function ($user) {
                                return [
                                    'user_id' => $user->id,
                                    'role_display_name' => $user->role->display_name,
                                    'username' => $user->username,
                                    'new_password' => '', // Always empty for security
                                    'is_active' => $user->is_active,
                                ];
                            })->toArray();
                            
                            return [
                                'user_accounts' => $userAccounts,
                            ];
                        })
                        ->action(function ($record, array $data) {
                            try {
                                $users = $record->users()->with('role')->get();
                                if ($users->isEmpty()) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Tidak ada user account ditemukan')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $allChanges = [];
                                $newPasswords = [];
                                
                                // Process each user account
                                foreach ($data['user_accounts'] as $userAccountData) {
                                    $userId = $userAccountData['user_id'];
                                    $user = $users->firstWhere('id', $userId);
                                    
                                    if (!$user) continue;
                                    
                                    $updates = [];
                                    $changesForThisUser = [];
                                    
                                    // Update username if changed
                                    if (!empty($userAccountData['username']) && $userAccountData['username'] !== $user->username) {
                                        // Check uniqueness
                                        $existingUser = \App\Models\User::where('username', $userAccountData['username'])
                                            ->where('id', '!=', $user->id)
                                            ->first();
                                            
                                        if ($existingUser) {
                                            Notification::make()
                                                ->title('Username Sudah Digunakan')
                                                ->body("Username '{$userAccountData['username']}' sudah digunakan oleh {$existingUser->role->display_name}")
                                                ->danger()
                                                ->send();
                                            continue;
                                        }
                                        
                                        $updates['username'] = $userAccountData['username'];
                                        $changesForThisUser[] = "Username: {$user->username} → {$userAccountData['username']}";
                                    }
                                    
                                    // Email is automatically synced from pegawai record, no manual update needed
                                    
                                    // Update password if provided
                                    if (!empty($userAccountData['new_password'])) {
                                        $updates['password'] = \Illuminate\Support\Facades\Hash::make($userAccountData['new_password']);
                                        $changesForThisUser[] = "Password diubah";
                                        $newPasswords[$user->role->display_name] = $userAccountData['new_password'];
                                    }
                                    
                                    // Update status if changed
                                    if (isset($userAccountData['is_active']) && $userAccountData['is_active'] != $user->is_active) {
                                        $updates['is_active'] = $userAccountData['is_active'];
                                        $changesForThisUser[] = $userAccountData['is_active'] ? "Status diaktifkan" : "Status dinonaktifkan";
                                    }
                                    
                                    // Apply updates for this user
                                    if (!empty($updates)) {
                                        $user->update($updates);
                                        $allChanges[$user->role->display_name] = $changesForThisUser;
                                    }
                                }
                                
                                // Send notification
                                if (!empty($allChanges)) {
                                    $notificationBody = "Perubahan berhasil untuk:\n";
                                    foreach ($allChanges as $role => $changes) {
                                        $notificationBody .= "\n{$role}:\n• " . implode("\n• ", $changes) . "\n";
                                    }
                                    
                                    if (!empty($newPasswords)) {
                                        $notificationBody .= "\nPassword baru:\n";
                                        foreach ($newPasswords as $role => $password) {
                                            $notificationBody .= "• {$role}: {$password}\n";
                                        }
                                    }
                                    
                                    Notification::make()
                                        ->title('Semua Role Berhasil Diperbarui')
                                        ->body($notificationBody)
                                        ->success()
                                        ->persistent()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Tidak Ada Perubahan')
                                        ->body('Tidak ada data yang diubah untuk semua role')
                                        ->warning()
                                        ->send();
                                }
                                
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Memperbarui User Accounts')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalHeading('Kelola Akun User Advanced')
                        ->modalDescription('Ubah role, username, password, dan status user')
                        ->modalSubmitActionLabel('Simpan Perubahan')
                        ->modalWidth('4xl'),

                    // Card Actions
                    Action::make('create_card')
                        ->label('Buat Kartu ID')
                        ->icon('heroicon-o-identification')
                        ->color('primary')
                        ->visible(fn ($record) => !EmployeeCard::where('pegawai_id', $record->id)->exists())
                        ->action(function ($record) {
                            $existingCard = EmployeeCard::where('pegawai_id', $record->id)->first();
                            
                            if ($existingCard) {
                                Notification::make()
                                    ->title('Kartu sudah ada!')
                                    ->body('Pegawai ini sudah memiliki kartu.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            $user = $record->user;
                            
                            $card = EmployeeCard::create([
                                'pegawai_id' => $record->id,
                                'user_id' => $user?->id,
                                'card_number' => EmployeeCard::generateCardNumber(),
                                'card_type' => 'standard',
                                'design_template' => 'default',
                                'employee_name' => $record->nama_lengkap,
                                'employee_id' => $record->nik,
                                'position' => $record->jabatan,
                                'department' => $record->jenis_pegawai,
                                'role_name' => $user?->role?->display_name,
                                'join_date' => $user?->tanggal_bergabung,
                                'photo_path' => $record->foto,
                                'issued_date' => now()->toDateString(),
                                'is_active' => true,
                                'created_by' => Auth::id(),
                            ]);
                            
                            $service = app(\App\Services\CardGenerationService::class);
                            $result = $service->generateCard($card);
                            
                            if ($result['success']) {
                                $card->update([
                                    'pdf_path' => $result['pdf_path'],
                                    'generated_at' => now(),
                                ]);
                                
                                Notification::make()
                                    ->title('Kartu berhasil dibuat!')
                                    ->body('Kartu ID untuk ' . $record->nama_lengkap . ' telah dibuat.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Gagal membuat kartu')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Action::make('view_card')
                        ->label('Lihat Kartu')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => '/admin/employee-cards/' . EmployeeCard::where('pegawai_id', $record->id)->first()?->id)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => EmployeeCard::where('pegawai_id', $record->id)->exists()),
                ])
                ->label('Kelola')
                ->icon('heroicon-m-cog-6-tooth')
                ->size('sm')
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\PegawaiStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawais::route('/'),
            'create' => Pages\CreatePegawai::route('/create'),
            'edit' => Pages\EditPegawai::route('/{record}/edit'),
        ];
    }
}
