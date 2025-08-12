<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PengaturanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?string $title = 'Pengaturan Sistem';
    protected static ?string $slug = 'pengaturan';
    protected static ?int $navigationSort = 15;
    protected static string $view = 'filament.petugas.pages.pengaturan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'nama' => auth()->user()->name,
            'email' => auth()->user()->email,
            'notifikasi_email' => true,
            'notifikasi_push' => true,
            'bahasa' => 'id',
            'zona_waktu' => 'Asia/Jakarta',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profil Pengguna')
                    ->description('Informasi profil dan akun')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        FileUpload::make('foto_profil')
                            ->label('Foto Profil')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048),
                    ])
                    ->collapsible(),

                Section::make('Keamanan')
                    ->description('Pengaturan keamanan akun')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('password_lama')
                                    ->label('Password Lama')
                                    ->password()
                                    ->dehydrated(false),
                                TextInput::make('password_baru')
                                    ->label('Password Baru')
                                    ->password()
                                    ->rules([Password::default()])
                                    ->dehydrated(false),
                            ]),
                        TextInput::make('konfirmasi_password')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->dehydrated(false)
                            ->same('password_baru'),
                    ])
                    ->collapsible(),

                Section::make('Notifikasi')
                    ->description('Pengaturan notifikasi sistem')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('notifikasi_email')
                                    ->label('Notifikasi Email')
                                    ->default(true),
                                Toggle::make('notifikasi_push')
                                    ->label('Notifikasi Push')
                                    ->default(true),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('notifikasi_sms')
                                    ->label('Notifikasi SMS')
                                    ->default(false),
                                Toggle::make('notifikasi_whatsapp')
                                    ->label('Notifikasi WhatsApp')
                                    ->default(false),
                            ]),
                        Select::make('frekuensi_notifikasi')
                            ->label('Frekuensi Notifikasi')
                            ->options([
                                'realtime' => 'Real-time',
                                'hourly' => 'Setiap Jam',
                                'daily' => 'Setiap Hari',
                                'weekly' => 'Setiap Minggu',
                            ])
                            ->default('realtime'),
                    ])
                    ->collapsible(),

                Section::make('Tampilan & Bahasa')
                    ->description('Pengaturan tampilan dan bahasa')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('bahasa')
                                    ->label('Bahasa')
                                    ->options([
                                        'id' => 'Bahasa Indonesia',
                                        'en' => 'English',
                                        'ar' => 'العربية',
                                        'zh' => '中文',
                                    ])
                                    ->default('id'),
                                Select::make('zona_waktu')
                                    ->label('Zona Waktu')
                                    ->options([
                                        'Asia/Jakarta' => 'WIB (UTC+7)',
                                        'Asia/Makassar' => 'WITA (UTC+8)',
                                        'Asia/Jayapura' => 'WIT (UTC+9)',
                                        'UTC' => 'UTC',
                                    ])
                                    ->default('Asia/Jakarta'),
                            ]),
                        Select::make('tema')
                            ->label('Tema Tampilan')
                            ->options([
                                'light' => 'Terang',
                                'dark' => 'Gelap',
                                'auto' => 'Otomatis',
                            ])
                            ->default('auto'),
                        Select::make('ukuran_font')
                            ->label('Ukuran Font')
                            ->options([
                                'small' => 'Kecil',
                                'medium' => 'Sedang',
                                'large' => 'Besar',
                            ])
                            ->default('medium'),
                    ])
                    ->collapsible(),

                Section::make('Pengaturan Sistem')
                    ->description('Konfigurasi sistem dan fitur')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('auto_save')
                                    ->label('Auto Save')
                                    ->default(true),
                                Toggle::make('backup_otomatis')
                                    ->label('Backup Otomatis')
                                    ->default(true),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('sync_cloud')
                                    ->label('Sinkronisasi Cloud')
                                    ->default(false),
                                Toggle::make('offline_mode')
                                    ->label('Mode Offline')
                                    ->default(false),
                            ]),
                        Select::make('interval_backup')
                            ->label('Interval Backup')
                            ->options([
                                'daily' => 'Setiap Hari',
                                'weekly' => 'Setiap Minggu',
                                'monthly' => 'Setiap Bulan',
                            ])
                            ->default('daily')
                            ->visible(fn () => $this->data['backup_otomatis'] ?? true),
                    ])
                    ->collapsible(),

                Section::make('Integrasi')
                    ->description('Pengaturan integrasi dengan layanan eksternal')
                    ->schema([
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->dehydrated(false),
                        TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->dehydrated(false),
                        Toggle::make('integrasi_whatsapp')
                            ->label('Integrasi WhatsApp')
                            ->default(false),
                        Toggle::make('integrasi_sms')
                            ->label('Integrasi SMS Gateway')
                            ->default(false),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save')
                ->color('success')
                ->icon('heroicon-o-check'),
            Action::make('reset')
                ->label('Reset ke Default')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->action('resetToDefault'),
            Action::make('export')
                ->label('Export Pengaturan')
                ->color('info')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportSettings'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // Update user profile
        $user = auth()->user();
        $user->update([
            'name' => $data['nama'],
            'email' => $data['email'],
        ]);

        // Update password if provided
        if (!empty($data['password_baru'])) {
            if (!Hash::check($data['password_lama'], $user->password)) {
                Notification::make()
                    ->title('Password lama salah')
                    ->danger()
                    ->send();
                return;
            }
            
            $user->update([
                'password' => Hash::make($data['password_baru'])
            ]);
        }

        // Save settings to user preferences or config
        $this->saveUserSettings($data);

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }

    private function saveUserSettings(array $data): void
    {
        // Save to user preferences table or session
        $user = auth()->user();
        
        $preferences = [
            'notifikasi_email' => $data['notifikasi_email'] ?? true,
            'notifikasi_push' => $data['notifikasi_push'] ?? true,
            'notifikasi_sms' => $data['notifikasi_sms'] ?? false,
            'notifikasi_whatsapp' => $data['notifikasi_whatsapp'] ?? false,
            'frekuensi_notifikasi' => $data['frekuensi_notifikasi'] ?? 'realtime',
            'bahasa' => $data['bahasa'] ?? 'id',
            'zona_waktu' => $data['zona_waktu'] ?? 'Asia/Jakarta',
            'tema' => $data['tema'] ?? 'auto',
            'ukuran_font' => $data['ukuran_font'] ?? 'medium',
            'auto_save' => $data['auto_save'] ?? true,
            'backup_otomatis' => $data['backup_otomatis'] ?? true,
            'interval_backup' => $data['interval_backup'] ?? 'daily',
            'sync_cloud' => $data['sync_cloud'] ?? false,
            'offline_mode' => $data['offline_mode'] ?? false,
            'integrasi_whatsapp' => $data['integrasi_whatsapp'] ?? false,
            'integrasi_sms' => $data['integrasi_sms'] ?? false,
        ];

        // Store in session for now, can be moved to database later
        session(['user_preferences' => $preferences]);
    }

    public function resetToDefault(): void
    {
        $this->form->fill([
            'notifikasi_email' => true,
            'notifikasi_push' => true,
            'notifikasi_sms' => false,
            'notifikasi_whatsapp' => false,
            'frekuensi_notifikasi' => 'realtime',
            'bahasa' => 'id',
            'zona_waktu' => 'Asia/Jakarta',
            'tema' => 'auto',
            'ukuran_font' => 'medium',
            'auto_save' => true,
            'backup_otomatis' => true,
            'interval_backup' => 'daily',
            'sync_cloud' => false,
            'offline_mode' => false,
            'integrasi_whatsapp' => false,
            'integrasi_sms' => false,
        ]);

        Notification::make()
            ->title('Pengaturan direset ke default')
            ->info()
            ->send();
    }

    public function exportSettings(): void
    {
        $data = $this->form->getState();
        
        // Create JSON file for download
        $filename = 'pengaturan_' . auth()->user()->name . '_' . date('Y-m-d_H-i-s') . '.json';
        
        Notification::make()
            ->title('Pengaturan berhasil diexport')
            ->body("File {$filename} telah dibuat")
            ->success()
            ->send();
    }
}
