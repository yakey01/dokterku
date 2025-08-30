<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokterResource\Pages;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class DokterResource extends Resource
{
    protected static ?string $model = Dokter::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Manajemen Dokter';
    protected static ?int $navigationSort = 12;
    protected static ?string $modelLabel = 'Dokter';
    protected static ?string $pluralModelLabel = 'Dokter';
    protected static ?string $recordTitleAttribute = 'nama_lengkap';

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
                Forms\Components\Section::make('👨‍⚕️ Informasi Dokter')
                    ->description('Data pribadi dan identitas dokter')
                    ->schema([
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Dr. Ahmad Yusuf Sp.PD')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK Pegawai')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generate jika kosong')
                            ->helperText('Format: DOK2025XXXX')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(true)
                            ->maxDate(now()->subYears(22)) // Min umur 22 tahun untuk dokter
                            ->minDate(now()->subYears(80)) // Max umur 80 tahun
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->columnSpan(1),

                        Forms\Components\Select::make('jabatan')
                            ->label('Jabatan / Spesialisasi')
                            ->options([
                                'dokter_umum' => 'Dokter Umum',
                                'dokter_gigi' => 'Dokter Gigi',
                                'dokter_spesialis' => 'Dokter Spesialis',
                            ])
                            ->required()
                            ->default('dokter_umum')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('📋 Izin Praktik')
                    ->description('Nomor SIP dan kontak')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_sip')
                            ->label('Nomor SIP')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('50/SIP/XXXX/2024')
                            ->helperText('Nomor Surat Izin Praktik wajib untuk keabsahan')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->placeholder('dokter@klinik.com')
                            ->helperText('Email diperlukan untuk akses sistem dan notifikasi')
                            ->rules(['nullable', 'email', 'max:255'])
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('aktif')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Dokter aktif dapat login dan melakukan tindakan')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('🔐 Manajemen Akun Login')
                    ->description('Pengaturan akun login dokter (khusus admin)')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username Login')
                                    ->unique(table: 'dokters', column: 'username', ignoreRecord: true)
                                    ->nullable()
                                    ->placeholder('Auto-generate jika kosong')
                                    ->helperText('Username untuk login (huruf, angka, spasi, titik, koma diizinkan)')
                                    ->rules(['nullable', 'regex:/^[a-zA-Z0-9\s.,-]+$/'])
                                    ->minLength(3)
                                    ->maxLength(50)
                                    ->suffixIcon('heroicon-m-user')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state) {
                                        \Log::info('DokterResource: Username field updated', [
                                            'new_value' => $state,
                                            'length' => strlen($state ?? ''),
                                            'validation_result' => preg_match('/^[a-zA-Z0-9\s.,-]+$/', $state ?? '') ? 'VALID' : 'INVALID'
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
                                    ->helperText('Status akun login dokter')
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

                Forms\Components\Section::make('📝 Informasi Tambahan')
                    ->description('Catatan dan foto dokter')
                    ->schema([
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto Dokter')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->directory('dokter-photos')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Catatan tambahan tentang dokter (opsional)...')
                            ->nullable()
                            ->rows(4)
                            ->columnSpan(2),
                    ])
                    ->columns(3),
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
                Tables\Columns\Layout\View::make('filament.components.dokter-card-simple')
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
                        Tables\Columns\TextColumn::make('nomor_sip')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jabatan')
                    ->label('Jabatan')
                    ->options([
                        'dokter_umum' => 'Dokter Umum',
                        'dokter_gigi' => 'Dokter Gigi',
                        'dokter_spesialis' => 'Dokter Spesialis',
                    ])
                    ->placeholder('Semua Jabatan'),

                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

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
                    ->trueLabel('Punya Akun')
                    ->falseLabel('Belum Punya Akun')
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
                    )
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Basic Actions
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-m-eye'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit Data')
                        ->icon('heroicon-m-pencil-square'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-m-trash'),
                    
                    // User Account Actions (Admin only)
                    Tables\Actions\Action::make('create_user_account')
                        ->label('Buat Akun User')
                        ->icon('heroicon-m-identification')
                        ->color('info')
                        ->visible(fn ($record) => !$record->user_id && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            try {
                                $dokterRole = \App\Models\Role::where('name', 'dokter')->first();
                                
                                if (!$dokterRole) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body('Role "dokter" tidak ditemukan dalam sistem.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $baseUsername = strtolower(str_replace([' ', '.', ','], '', $record->nama_lengkap));
                                $baseUsername = \Illuminate\Support\Str::ascii($baseUsername);
                                $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
                                $baseUsername = substr($baseUsername, 0, 20);
                                
                                $username = $baseUsername;
                                $counter = 1;
                                
                                while (\App\Models\User::where('username', $username)->exists()) {
                                    $username = $baseUsername . $counter;
                                    $counter++;
                                }

                                $password = \Illuminate\Support\Str::random(8);

                                $user = \App\Models\User::create([
                                    'role_id' => $dokterRole->id,
                                    'name' => $record->nama_lengkap,
                                    'email' => $record->email,
                                    'username' => $username,
                                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                                    'nip' => $record->nik,
                                    'tanggal_bergabung' => now()->toDateString(),
                                    'is_active' => $record->aktif,
                                ]);

                                $record->update(['user_id' => $user->id]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Akun User Berhasil Dibuat')
                                    ->body("Username: {$username}<br>Password: {$password}<br>Role: dokter")
                                    ->success()
                                    ->persistent()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Membuat Akun User')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun User Dokter')
                        ->modalDescription('Akun User akan dibuat dan dihubungkan dengan record dokter ini.')
                        ->modalSubmitActionLabel('Buat Akun User'),

                    Tables\Actions\Action::make('link_existing_user')
                        ->label('Link User Existing')
                        ->icon('heroicon-m-link')
                        ->color('warning')
                        ->visible(fn ($record) => !$record->user_id && auth()->user()?->hasRole('admin'))
                        ->form([
                            Forms\Components\Select::make('user_id')
                                ->label('Pilih User')
                                ->options(function () {
                                    return \App\Models\User::whereHas('role', function ($query) {
                                        $query->where('name', 'dokter');
                                    })
                                    ->whereDoesntHave('dokter')
                                    ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->helperText('Hanya menampilkan User dengan role "dokter" yang belum terhubung ke dokter lain'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                $user = \App\Models\User::find($data['user_id']);
                                if (!$user) {
                                    throw new \Exception('User tidak ditemukan');
                                }

                                $record->update(['user_id' => $user->id]);

                                \Filament\Notifications\Notification::make()
                                    ->title('User Berhasil Dihubungkan')
                                    ->body("User {$user->name} ({$user->username}) berhasil dihubungkan dengan dokter {$record->nama_lengkap}")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Menghubungkan User')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalHeading('Hubungkan User Existing')
                        ->modalDescription('Pilih User account yang sudah ada untuk dihubungkan dengan record dokter ini.')
                        ->modalSubmitActionLabel('Hubungkan User'),

                    Tables\Actions\Action::make('view_user_account')
                        ->label('Lihat User')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->visible(fn ($record) => $record->user_id && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            $user = $record->user;
                            if ($user) {
                                $userRole = optional($user->role)->display_name ?: optional($user->role)->name ?: 'Tidak ada role';
                                $userStatus = $user->is_active ? 'Aktif' : 'Nonaktif';
                                $joinDate = optional($user->tanggal_bergabung)->format('d/m/Y') ?: 'Tidak diketahui';
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Informasi Akun User')
                                    ->body("
                                        <strong>Nama:</strong> {$user->name}<br>
                                        <strong>Username:</strong> {$user->username}<br>
                                        <strong>Email:</strong> {$user->email}<br>
                                        <strong>Role:</strong> {$userRole}<br>
                                        <strong>Status:</strong> {$userStatus}<br>
                                        <strong>Bergabung:</strong> {$joinDate}
                                    ")
                                    ->info()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('unlink_user_account')
                        ->label('Putus Link User')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->visible(fn ($record) => $record->user_id && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            try {
                                $userName = $record->user?->name;
                                
                                $record->update(['user_id' => null]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Link User Berhasil Diputus')
                                    ->body("Dokter {$record->nama_lengkap} tidak lagi terhubung dengan User {$userName}. User account tetap ada di sistem.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Memutus Link User')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Putus Link User Account')
                        ->modalDescription('Link antara record dokter dan User account akan diputus. User account tidak akan dihapus dan tetap ada di sistem.')
                        ->modalSubmitActionLabel('Putus Link'),

                    Tables\Actions\Action::make('create_account')
                        ->label('Buat Akun Login')
                        ->icon('heroicon-m-user-plus')
                        ->color('success')
                        ->visible(fn ($record) => !$record->username && auth()->user()?->hasRole('admin'))
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
                        ->modalHeading('Buat Akun Login')
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

                    Tables\Actions\Action::make('toggle_account')
                        ->label(fn ($record) => $record->status_akun === 'Aktif' ? 'Suspend' : 'Aktifkan')
                        ->icon(fn ($record) => $record->status_akun === 'Aktif' ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                        ->color(fn ($record) => $record->status_akun === 'Aktif' ? 'danger' : 'success')
                        ->visible(fn ($record) => $record->has_login_account && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            $result = $record->toggleAccountStatus();
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Status Akun Berhasil Diubah')
                                    ->body($result['message'])
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Mengubah Status')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->status_akun === 'Aktif' ? 'Suspend Akun' : 'Aktifkan Akun')
                        ->modalDescription(fn ($record) => $record->status_akun === 'Aktif' 
                            ? 'Akun login dokter akan di-suspend dan tidak dapat digunakan untuk login.' 
                            : 'Akun login dokter akan diaktifkan kembali.'
                        )
                        ->modalSubmitActionLabel(fn ($record) => $record->status_akun === 'Aktif' ? 'Suspend' : 'Aktifkan'),
                ])
                ->label('Kelola')
                ->icon('heroicon-m-cog-6-tooth')
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
            ->searchable()
            ->striped()
            ->emptyStateHeading('👨‍⚕️ Belum Ada Dokter')
            ->emptyStateDescription('Klik tombol "Tambah Dokter" untuk menambahkan dokter pertama.')
            ->emptyStateIcon('heroicon-o-user-plus');
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
            'index' => Pages\ListDokters::route('/'),
            'create' => Pages\CreateDokter::route('/create'),
            'edit' => Pages\EditDokter::route('/{record}/edit'),
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
}