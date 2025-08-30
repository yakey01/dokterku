<?php

namespace App\Filament\Resources;

use App\Enums\TelegramNotificationType;
use App\Filament\Resources\TelegramSettingResource\Pages;
use App\Models\TelegramSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramSettingResource extends Resource
{
    protected static ?string $model = TelegramSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Telegram Bot';

    protected static ?string $modelLabel = 'Pengaturan Telegram';

    protected static ?string $pluralModelLabel = 'Pengaturan Telegram';

    protected static ?string $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 72;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🎯 Konfigurasi Role & Pengguna')
                    ->description('Pilih role dan konfigurasi pengguna untuk menerima notifikasi Telegram')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('role')
                                    ->label('👥 Pilih Role')
                                    ->options([
                                        'admin' => '🔧 Administrator',
                                        'manajer' => '👔 Manajer Klinik',
                                        'bendahara' => '💼 Bendahara',
                                        'petugas' => '🏥 Petugas Frontdesk',
                                        'dokter' => '👨‍⚕️ Dokter',
                                        'paramedis' => '👩‍⚕️ Paramedis',
                                        'non_paramedis' => '👥 Non Paramedis',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->searchable()
                                    ->disabled(function ($record) {
                                        return $record !== null;
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('notification_types', []);
                                        $set('user_id', null);
                                        $set('user_name', null);
                                        if (in_array($state, ['dokter', 'paramedis', 'non_paramedis'])) {
                                            $set('role_type', 'general');
                                        } else {
                                            $set('role_type', null);
                                        }
                                    })
                                    ->helperText(function ($record) {
                                        if ($record) {
                                            return '🔒 Role tidak dapat diubah setelah dibuat untuk menjaga konsistensi data.';
                                        }
                                        return '📋 Pilih role yang akan menerima notifikasi Telegram. Role dengan dukungan pengguna spesifik: Dokter, Paramedis, Non-Paramedis.';
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('role_preview')
                                    ->label('📊 Preview Role')
                                    ->content(function (callable $get) {
                                        $role = $get('role');
                                        if (!$role) {
                                            return '👈 Pilih role untuk melihat preview';
                                        }
                                        
                                        $roleDetails = [
                                            'admin' => ['icon' => '🔧', 'name' => 'Administrator', 'desc' => 'Mengelola sistem & pengguna'],
                                            'manajer' => ['icon' => '👔', 'name' => 'Manajer Klinik', 'desc' => 'Mengawasi operasional klinik'],
                                            'bendahara' => ['icon' => '💼', 'name' => 'Bendahara', 'desc' => 'Mengelola keuangan & validasi'],
                                            'petugas' => ['icon' => '🏥', 'name' => 'Petugas Frontdesk', 'desc' => 'Melayani pasien & registrasi'],
                                            'dokter' => ['icon' => '👨‍⚕️', 'name' => 'Dokter', 'desc' => 'Menangani pasien & diagnosis'],
                                            'paramedis' => ['icon' => '👩‍⚕️', 'name' => 'Paramedis', 'desc' => 'Mendukung pelayanan medis'],
                                            'non_paramedis' => ['icon' => '👥', 'name' => 'Non Paramedis', 'desc' => 'Staff pendukung administratif'],
                                        ];
                                        
                                        $detail = $roleDetails[$role] ?? ['icon' => '❓', 'name' => 'Unknown', 'desc' => ''];
                                        
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">'
                                            . '<div class="flex items-center gap-2 mb-1">'
                                            . '<span class="text-lg">' . $detail['icon'] . '</span>'
                                            . '<span class="font-semibold text-gray-900 dark:text-gray-100">' . $detail['name'] . '</span>'
                                            . '</div>'
                                            . '<p class="text-sm text-gray-600 dark:text-gray-300">' . $detail['desc'] . '</p>'
                                            . '</div>'
                                        );
                                    })
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('role_type')
                                    ->label('📋 Tipe Konfigurasi')
                                    ->options([
                                        'general' => '🌐 Umum (Semua Pengguna)',
                                        'specific_user' => '👤 Pengguna Spesifik',
                                    ])
                                    ->default('general')
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->visible(function (callable $get) {
                                        $role = $get('role');
                                        return in_array($role, ['dokter', 'paramedis', 'non_paramedis']);
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state === 'general') {
                                            $set('user_id', null);
                                            $set('user_name', null);
                                        }
                                    })
                                    ->rules([
                                        function (callable $get) {
                                            return function ($attribute, $value, $fail) use ($get) {
                                                if ($value === 'general') {
                                                    $role = $get('role');
                                                    $existing = TelegramSetting::where('role', $role)
                                                        ->where('role_type', 'general')
                                                        ->exists();

                                                    if ($existing) {
                                                        $fail("Sudah ada konfigurasi umum untuk role {$role}. Gunakan Edit untuk mengubah yang sudah ada.");
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->helperText('Pilih cakupan konfigurasi: semua pengguna atau individu tertentu')
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('role_type_preview')
                                    ->label('ℹ️ Info Tipe')
                                    ->content(function (callable $get) {
                                        $roleType = $get('role_type');
                                        $role = $get('role');
                                        
                                        if (!$role || !in_array($role, ['dokter', 'paramedis', 'non_paramedis'])) {
                                            return '';
                                        }
                                        
                                        if ($roleType === 'general') {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded border-l-4 border-blue-400 dark:border-blue-500">'
                                                . '<p class="text-sm font-medium text-blue-800 dark:text-blue-200">🌐 Konfigurasi Umum</p>'
                                                . '<p class="text-xs text-blue-600 dark:text-blue-300">Notifikasi akan dikirim ke semua ' . ucfirst($role) . '</p>'
                                                . '</div>'
                                            );
                                        } elseif ($roleType === 'specific_user') {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="p-2 bg-green-50 dark:bg-green-900/20 rounded border-l-4 border-green-400 dark:border-green-500">'
                                                . '<p class="text-sm font-medium text-green-800 dark:text-green-200">👤 Pengguna Spesifik</p>'
                                                . '<p class="text-xs text-green-600 dark:text-green-300">Notifikasi hanya untuk pengguna yang dipilih</p>'
                                                . '</div>'
                                            );
                                        }
                                        
                                        return '';
                                    })
                                    ->visible(function (callable $get) {
                                        $role = $get('role');
                                        return in_array($role, ['dokter', 'paramedis', 'non_paramedis']);
                                    })
                                    ->columnSpan(1),
                            ])
                            ->visible(function (callable $get) {
                                $role = $get('role');
                                return in_array($role, ['dokter', 'paramedis', 'non_paramedis']);
                            }),

                        Forms\Components\Select::make('user_id')
                            ->label('👤 Pilih Pengguna')
                            ->options(function (callable $get) {
                                $role = $get('role');
                                if (! $role || ! in_array($role, ['dokter', 'paramedis', 'non_paramedis'])) {
                                    return [];
                                }

                                return TelegramSetting::getAvailableUsersForRole($role);
                            })
                            ->searchable()
                            ->preload()
                            ->required(function (callable $get) {
                                return $get('role_type') === 'specific_user';
                            })
                            ->visible(function (callable $get) {
                                $role = $get('role');
                                $roleType = $get('role_type');

                                return in_array($role, ['dokter', 'paramedis', 'non_paramedis']) && $roleType === 'specific_user';
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $role = $get('role');
                                    $user = \App\Models\User::find($state);
                                    if ($user) {
                                        $set('user_name', $user->name);
                                    }
                                }
                            })
                            ->validationMessages([
                                'required' => 'Pilih pengguna untuk konfigurasi spesifik.',
                            ])
                            ->rules([
                                function (callable $get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        if ($value && $get('role_type') === 'specific_user') {
                                            $role = $get('role');
                                            $existing = TelegramSetting::where('user_id', $value)
                                                ->where('role', $role)
                                                ->where('role_type', 'specific_user')
                                                ->exists();

                                            if ($existing) {
                                                $user = \App\Models\User::find($value);
                                                $userName = $user ? $user->name : 'User';
                                                $fail("Pengguna {$userName} telah terkonfigurasi untuk role {$role}.");
                                            }
                                        }
                                    };
                                },
                            ])
                            ->helperText(function (callable $get) {
                                $role = $get('role');
                                $totalUsers = count(TelegramSetting::getAvailableUsersForRole($role ?: 'dokter'));
                                $configuredUsers = \App\Models\TelegramSetting::where('role', $role)
                                    ->where('role_type', 'specific_user')
                                    ->count();
                                    
                                return "Pilih pengguna spesifik. Format yang sudah dikonfigurasi akan ditandai dengan ✅ Terkonfigurasi. Tersedia {$totalUsers} pengguna, {$configuredUsers} sudah dikonfigurasi.";
                            })
                            ->placeholder('Cari dan pilih pengguna...'),

                        Forms\Components\Hidden::make('user_name'),

                        Forms\Components\Placeholder::make('user_preview')
                            ->label('👤 Preview Pengguna Terpilih')
                            ->content(function (callable $get) {
                                $userId = $get('user_id');
                                $role = $get('role');
                                
                                if (!$userId || !$role) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600 text-center">'
                                        . '<p class="text-sm text-gray-500 dark:text-gray-400">👆 Pilih pengguna untuk melihat preview</p>'
                                        . '</div>'
                                    );
                                }
                                
                                $user = \App\Models\User::find($userId);
                                
                                if (!$user) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-600">'
                                        . '<p class="text-sm text-red-700 dark:text-red-300">❌ Pengguna tidak ditemukan</p>'
                                        . '</div>'
                                    );
                                }
                                
                                // Check existing configurations for this user
                                $existingConfigs = \App\Models\TelegramSetting::where('user_id', $userId)
                                    ->where('role_type', 'specific_user')
                                    ->get();
                                
                                $displayName = $user->name;
                                if ($role === 'dokter' && !str_starts_with(strtolower($displayName), 'dr')) {
                                    $displayName = 'Dr. ' . $displayName;
                                }
                                
                                $previewHtml = '<div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-600">'
                                . '<div class="flex items-center gap-2 mb-2">'
                                . '<span class="text-lg">👤</span>'
                                . '<span class="font-semibold text-blue-900 dark:text-blue-100">' . $displayName . '</span>'
                                . '</div>'
                                . '<div class="space-y-1 text-sm">'
                                . '<p class="text-blue-800 dark:text-blue-200"><span class="font-medium">Email:</span> ' . $user->email . '</p>';
                                
                                if ($user->username) {
                                    $previewHtml .= '<p class="text-blue-800 dark:text-blue-200"><span class="font-medium">Username:</span> ' . $user->username . '</p>';
                                }
                                
                                if ($existingConfigs->isNotEmpty()) {
                                    $configRoles = $existingConfigs->pluck('role')->unique()->toArray();
                                    $previewHtml .= '<p class="text-amber-700 dark:text-amber-300"><span class="font-medium">⚠️ Terkonfigurasi untuk:</span> ' . implode(', ', $configRoles) . '</p>';
                                }
                                
                                $previewHtml .= '</div></div>';
                                
                                return new \Illuminate\Support\HtmlString($previewHtml);
                            })
                            ->visible(function (callable $get) {
                                $role = $get('role');
                                $roleType = $get('role_type');
                                
                                return in_array($role, ['dokter', 'paramedis', 'non_paramedis']) && $roleType === 'specific_user';
                            })
                            ->reactive(['user_id', 'role']),

                    ])
                    ->columns(1),

                Forms\Components\Section::make('📲 Konfigurasi Telegram')
                    ->description('Masukkan Chat ID Telegram untuk menerima notifikasi')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('chat_id')
                                    ->label('📲 Chat ID Telegram')
                                    ->required()
                                    ->numeric()
                                    ->placeholder('Contoh: 123456789 atau -1001234567890')
                                    ->helperText('Chat ID grup/user Telegram (angka positif untuk user, negatif untuk grup)')
                                    ->rule('regex:/^-?[0-9]{1,15}$/')
                                    ->unique(table: TelegramSetting::class, ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Chat ID sudah digunakan untuk role lain.',
                                        'regex' => 'Chat ID harus berupa angka (1-15 digit), bisa dimulai dengan tanda minus untuk grup.',
                                        'numeric' => 'Chat ID harus berupa angka.',
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('chat_id_info')
                                    ->label('ℹ️ Cara Mendapatkan Chat ID')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        '<div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-600">'
                                        . '<div class="space-y-2 text-sm">'
                                        . '<p class="font-medium text-yellow-800 dark:text-yellow-200">📱 Untuk User Pribadi:</p>'
                                        . '<p class="text-yellow-700 dark:text-yellow-300">• Chat @userinfobot di Telegram</p>'
                                        . '<p class="text-yellow-700 dark:text-yellow-300">• Kirim pesan apa saja untuk mendapat Chat ID</p>'
                                        . '<p class="font-medium text-yellow-800 dark:text-yellow-200 mt-3">👥 Untuk Grup:</p>'
                                        . '<p class="text-yellow-700 dark:text-yellow-300">• Tambahkan @userinfobot ke grup</p>'
                                        . '<p class="text-yellow-700 dark:text-yellow-300">• Chat ID grup biasanya dimulai dengan -100</p>'
                                        . '</div>'
                                        . '</div>'
                                    ))
                                    ->columnSpan(1),
                            ]),

                    ])
                    ->columns(1),

                Forms\Components\Section::make('📢 Konfigurasi Notifikasi')
                    ->description('Pilih jenis notifikasi yang akan diterima oleh role ini')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Placeholder::make('notification_preview')
                            ->label('📊 Preview Notifikasi')
                            ->content(function (callable $get) {
                                $role = $get('role');
                                $selectedTypes = $get('notification_types') ?: [];
                                
                                if (!$role) {
                                    return '👆 Pilih role terlebih dahulu untuk melihat notifikasi yang tersedia';
                                }
                                
                                $availableTypes = TelegramNotificationType::getForRole($role);
                                
                                if (empty($availableTypes)) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-600">'
                                        . '<p class="text-amber-800 dark:text-amber-200 font-medium">⚠️ Belum Ada Notifikasi</p>'
                                        . '<p class="text-sm text-amber-700 dark:text-amber-300">Role ' . ucfirst($role) . ' belum memiliki jenis notifikasi yang didefinisikan. Silakan hubungi administrator untuk menambahkan konfigurasi notifikasi.</p>'
                                        . '</div>'
                                    );
                                }
                                
                                $previewHtml = '<div class="space-y-3">'
                                . '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
                                
                                // Categorize notifications
                                $categories = [
                                    'financial' => ['pendapatan', 'pengeluaran', 'validasi_disetujui', 'jaspel_selesai'],
                                    'medical' => ['pasien'],
                                    'system' => ['user_baru', 'backup_gagal'],
                                    'reports' => ['rekap_harian', 'rekap_mingguan']
                                ];
                                
                                $categoryNames = [
                                    'financial' => '💰 Keuangan',
                                    'medical' => '⚕️ Medis',
                                    'system' => '⚙️ Sistem',
                                    'reports' => '📊 Laporan'
                                ];
                                
                                foreach ($categories as $category => $typeValues) {
                                    $categoryTypes = array_filter($availableTypes, fn($type) => in_array($type->value, $typeValues));
                                    
                                    if (!empty($categoryTypes)) {
                                        $previewHtml .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">'
                                        . '<h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">' . $categoryNames[$category] . '</h4>'
                                        . '<div class="space-y-1">';
                                        
                                        foreach ($categoryTypes as $type) {
                                            $isSelected = in_array($type->value, $selectedTypes);
                                            $badgeClass = $isSelected ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 border-green-200 dark:border-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600';
                                            $icon = $isSelected ? '✅' : '⚪';
                                            
                                            $previewHtml .= '<div class="flex items-center gap-2">'
                                            . '<span>' . $icon . '</span>'
                                            . '<span class="px-2 py-1 text-xs border rounded ' . $badgeClass . '">' . $type->label() . '</span>'
                                            . '</div>';
                                        }
                                        
                                        $previewHtml .= '</div></div>';
                                    }
                                }
                                
                                $previewHtml .= '</div>';
                                
                                // Add summary
                                $totalAvailable = count($availableTypes);
                                $totalSelected = count($selectedTypes);
                                
                                $previewHtml .= '<div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-600">'
                                . '<p class="text-sm text-blue-800 dark:text-blue-200">'
                                . '<span class="font-medium">Status:</span> '
                                . $totalSelected . ' dari ' . $totalAvailable . ' notifikasi dipilih'
                                . '</p></div></div>';
                                
                                return new \Illuminate\Support\HtmlString($previewHtml);
                            })
                            ->reactive(['role', 'notification_types']),

                        Forms\Components\CheckboxList::make('notification_types')
                            ->label('📢 Pilih Jenis Notifikasi')
                            ->options(function (callable $get) {
                                $role = $get('role');
                                if (! $role) {
                                    return TelegramNotificationType::getAllOptions();
                                }

                                return TelegramSetting::getRoleNotifications($role);
                            })
                            ->columns(1)
                            ->reactive()
                            ->helperText(function (callable $get) {
                                $role = $get('role');
                                if (!$role) {
                                    return 'Pilih role terlebih dahulu';
                                }
                                
                                $availableTypes = TelegramNotificationType::getForRole($role);
                                $count = count($availableTypes);
                                
                                if ($count === 0) {
                                    return '⚠️ Role ini belum memiliki notifikasi yang didefinisikan';
                                }
                                
                                return "📋 Tersedia {$count} jenis notifikasi untuk role {$role}. Pilih sesuai kebutuhan.";
                            })
                            ->descriptions(function (callable $get) {
                                $role = $get('role');
                                if (! $role) {
                                    return [];
                                }

                                $descriptions = [];
                                foreach (TelegramNotificationType::getForRole($role) as $type) {
                                    $descriptions[$type->value] = $type->description();
                                }

                                return $descriptions;
                            })
                            ->visible(function (callable $get) {
                                $role = $get('role');
                                if (!$role) return false;
                                
                                $availableTypes = TelegramNotificationType::getForRole($role);
                                return !empty($availableTypes);
                            }),

                    ])
                    ->columns(1),

                Forms\Components\Section::make('⚙️ Pengaturan Aktivasi')
                    ->description('Kontrol status aktif dan pengaturan lanjutan')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('🔌 Status Aktif')
                                    ->default(true)
                                    ->helperText('Aktifkan/nonaktifkan semua notifikasi untuk konfigurasi ini')
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('activation_status')
                                    ->label('📊 Status Konfigurasi')
                                    ->content(function (callable $get) {
                                        $isActive = $get('is_active');
                                        $notificationTypes = $get('notification_types') ?: [];
                                        $role = $get('role');
                                        $chatId = $get('chat_id');
                                        
                                        if ($isActive) {
                                            $statusHtml = '<div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-600">'
                                            . '<div class="flex items-center gap-2 mb-2">'
                                            . '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>'
                                            . '<span class="font-medium text-green-800 dark:text-green-200">Konfigurasi Aktif</span>'
                                            . '</div>';
                                            
                                            if ($role && $chatId && !empty($notificationTypes)) {
                                                $statusHtml .= '<p class="text-sm text-green-700 dark:text-green-300">✅ Siap menerima ' . count($notificationTypes) . ' jenis notifikasi</p>';
                                            } else {
                                                $statusHtml .= '<p class="text-sm text-amber-700 dark:text-amber-300">⚠️ Lengkapi semua field untuk mengaktifkan notifikasi</p>';
                                            }
                                            
                                            $statusHtml .= '</div>';
                                        } else {
                                            $statusHtml = '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">'
                                            . '<div class="flex items-center gap-2 mb-2">'
                                            . '<span class="w-2 h-2 bg-gray-400 rounded-full"></span>'
                                            . '<span class="font-medium text-gray-600 dark:text-gray-300">Konfigurasi Nonaktif</span>'
                                            . '</div>'
                                            . '<p class="text-sm text-gray-600 dark:text-gray-300">🔇 Tidak akan menerima notifikasi</p>'
                                            . '</div>';
                                        }
                                        
                                        return new \Illuminate\Support\HtmlString($statusHtml);
                                    })
                                    ->reactive(['is_active', 'notification_types', 'role', 'chat_id'])
                                    ->columnSpan(1),
                            ]),
                            
                        Forms\Components\Placeholder::make('final_summary')
                            ->label('📋 Ringkasan Konfigurasi')
                            ->content(function (callable $get) {
                                $role = $get('role');
                                $roleType = $get('role_type');
                                $userId = $get('user_id');
                                $chatId = $get('chat_id');
                                $notificationTypes = $get('notification_types') ?: [];
                                $isActive = $get('is_active');
                                
                                if (!$role) {
                                    return '👆 Lengkapi form untuk melihat ringkasan';
                                }
                                
                                $summaryHtml = '<div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-600">'
                                . '<h4 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">📝 Ringkasan Konfigurasi:</h4>'
                                . '<div class="space-y-2 text-sm text-slate-800 dark:text-slate-200">';
                                
                                // Role info
                                $roleDetails = [
                                    'admin' => '🔧 Administrator',
                                    'manajer' => '👔 Manajer Klinik',
                                    'bendahara' => '💼 Bendahara',
                                    'petugas' => '🏥 Petugas Frontdesk',
                                    'dokter' => '👨‍⚕️ Dokter',
                                    'paramedis' => '👩‍⚕️ Paramedis',
                                    'non_paramedis' => '👥 Non Paramedis',
                                ];
                                
                                $summaryHtml .= '<div><span class="font-medium">Role:</span> ' . ($roleDetails[$role] ?? ucfirst($role)) . '</div>';
                                
                                // User type
                                if (in_array($role, ['dokter', 'paramedis', 'non_paramedis'])) {
                                    if ($roleType === 'specific_user' && $userId) {
                                        $user = \App\Models\User::find($userId);
                                        $summaryHtml .= '<div><span class="font-medium">Target:</span> 👤 ' . ($user ? $user->name : 'Pengguna Spesifik') . '</div>';
                                    } else {
                                        $summaryHtml .= '<div><span class="font-medium">Target:</span> 🌐 Semua ' . ucfirst($role) . '</div>';
                                    }
                                }
                                
                                // Chat ID
                                if ($chatId) {
                                    $chatType = str_starts_with($chatId, '-') ? 'Grup' : 'User';
                                    $summaryHtml .= '<div><span class="font-medium">Chat ID:</span> 📲 ' . $chatId . ' (' . $chatType . ')</div>';
                                }
                                
                                // Notifications
                                $summaryHtml .= '<div><span class="font-medium">Notifikasi:</span> 📢 ' . count($notificationTypes) . ' jenis</div>';
                                
                                // Status
                                $statusText = $isActive ? '🟢 Aktif' : '🔴 Nonaktif';
                                $summaryHtml .= '<div><span class="font-medium">Status:</span> ' . $statusText . '</div>';
                                
                                $summaryHtml .= '</div></div>';
                                
                                return new \Illuminate\Support\HtmlString($summaryHtml);
                            })
                            ->reactive(['role', 'role_type', 'user_id', 'chat_id', 'notification_types', 'is_active']),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role')
                    ->label('👥 Role & Pengguna')
                    ->formatStateUsing(function ($record) {
                        $roleIcons = [
                            'admin' => '🔧',
                            'manajer' => '👔',
                            'bendahara' => '💼',
                            'petugas' => '🏥',
                            'dokter' => '👨‍⚕️',
                            'paramedis' => '👩‍⚕️',
                            'non_paramedis' => '👥',
                        ];
                        
                        $icon = $roleIcons[$record->role] ?? '❓';
                        $displayName = $record->getDisplayName();
                        
                        // Add user type indicator
                        if ($record->role_type === 'specific_user') {
                            $displayName .= ' 👤';
                        } elseif ($record->role_type === 'general') {
                            $displayName .= ' 🌐';
                        }
                        
                        return $icon . ' ' . $displayName;
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manajer' => 'warning', 
                        'bendahara' => 'success',
                        'petugas' => 'info',
                        'dokter' => 'primary',
                        'paramedis' => 'gray',
                        'non_paramedis' => 'slate',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        if ($record->role_type === 'specific_user' && $record->user_name) {
                            return '👤 Khusus: ' . $record->user_name;
                        } elseif ($record->role_type === 'general') {
                            return '🌐 Semua ' . ucfirst($record->role);
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('chat_id')
                    ->label('📲 Chat ID & Tipe')
                    ->formatStateUsing(function ($record) {
                        if (!$record->chat_id) {
                            return '❌ Belum diatur';
                        }
                        
                        $chatType = str_starts_with($record->chat_id, '-') ? '👥 Grup' : '👤 User';
                        $maskedChatId = '***' . substr($record->chat_id, -4);
                        
                        return $chatType . ': ' . $maskedChatId;
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->chat_id) return 'danger';
                        return str_starts_with($record->chat_id, '-') ? 'info' : 'success';
                    })
                    ->tooltip(function ($record) {
                        if (!$record->chat_id) {
                            return 'Chat ID belum dikonfigurasi';
                        }
                        
                        $chatType = str_starts_with($record->chat_id, '-') ? 'Grup Telegram' : 'User Telegram';
                        return $chatType . ': ' . $record->chat_id;
                    })
                    ->copyable()
                    ->copyableState(fn ($record) => $record->chat_id)
                    ->searchable(),

                Tables\Columns\TextColumn::make('formatted_notification_types')
                    ->label('📢 Notifikasi Aktif')
                    ->state(function ($record) {
                        $notificationTypes = $record->notification_types ?? [];
                        $totalAvailable = count(TelegramNotificationType::getForRole($record->role));
                        $totalSelected = count($notificationTypes);
                        
                        if ($totalSelected === 0) {
                            return '❌ Tidak ada';
                        }
                        
                        // Calculate completion percentage
                        $percentage = $totalAvailable > 0 ? round(($totalSelected / $totalAvailable) * 100) : 0;
                        
                        // Get formatted types for tooltip
                        $formatted = $record->getFormattedNotificationTypes();
                        $preview = array_slice($formatted, 0, 2);
                        $moreText = count($formatted) > 2 ? ' +' . (count($formatted) - 2) . ' lainnya' : '';
                        
                        return "📢 {$totalSelected}/{$totalAvailable} ({$percentage}%)" . ($preview ? '\n' . implode(', ', $preview) . $moreText : '');
                    })
                    ->badge()
                    ->color(function ($record) {
                        $notificationTypes = $record->notification_types ?? [];
                        $totalAvailable = count(TelegramNotificationType::getForRole($record->role));
                        $totalSelected = count($notificationTypes);
                        
                        if ($totalSelected === 0) return 'danger';
                        
                        $percentage = $totalAvailable > 0 ? ($totalSelected / $totalAvailable) * 100 : 0;
                        
                        if ($percentage >= 80) return 'success';
                        if ($percentage >= 50) return 'warning';
                        return 'danger';
                    })
                    ->tooltip(function ($record) {
                        $formatted = $record->getFormattedNotificationTypes();
                        $availableTypes = TelegramNotificationType::getForRole($record->role);
                        
                        if (empty($formatted)) {
                            return 'Tidak ada notifikasi yang dipilih. Total tersedia: ' . count($availableTypes);
                        }
                        
                        $tooltip = "Notifikasi aktif untuk {$record->role}:\n";
                        $tooltip .= implode("\n", array_map(fn($type) => '• ' . $type, $formatted));
                        $tooltip .= "\n\nTotal: " . count($formatted) . ' dari ' . count($availableTypes) . ' tersedia';
                        
                        return $tooltip;
                    })
                    ->wrap()
                    ->lineClamp(2),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('🔌 Status & Kesehatan')
                    ->icon(function ($record) {
                        if (!$record->is_active) {
                            return 'heroicon-o-x-circle';
                        }
                        
                        // Check configuration health
                        $hasValidConfig = $record->chat_id && !empty($record->notification_types);
                        
                        if ($hasValidConfig) {
                            return 'heroicon-o-check-circle';
                        } else {
                            return 'heroicon-o-exclamation-triangle';
                        }
                    })
                    ->color(function ($record) {
                        if (!$record->is_active) {
                            return 'danger';
                        }
                        
                        $hasValidConfig = $record->chat_id && !empty($record->notification_types);
                        
                        if ($hasValidConfig) {
                            return 'success';
                        } else {
                            return 'warning';
                        }
                    })
                    ->tooltip(function ($record) {
                        if (!$record->is_active) {
                            return '🔴 Nonaktif - Tidak akan menerima notifikasi';
                        }
                        
                        $issues = [];
                        
                        if (!$record->chat_id) {
                            $issues[] = 'Chat ID belum diatur';
                        }
                        
                        if (empty($record->notification_types)) {
                            $issues[] = 'Belum ada notifikasi yang dipilih';
                        }
                        
                        if (empty($issues)) {
                            $notifCount = count($record->notification_types ?? []);
                            return "🟢 Aktif & Sehat - {$notifCount} notifikasi siap";
                        }
                        
                        return "⚠️ Aktif tapi ada masalah: " . implode(', ', $issues);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('📅 Terakhir Diperbarui')
                    ->state(function ($record) {
                        $lastUpdate = $record->updated_at;
                        $diffInDays = $lastUpdate->diffInDays(now());
                        
                        if ($diffInDays === 0) {
                            return '🔴 Hari ini';
                        } elseif ($diffInDays === 1) {
                            return '🟡 Kemarin';
                        } elseif ($diffInDays <= 7) {
                            return '🟠 ' . $diffInDays . ' hari lalu';
                        } elseif ($diffInDays <= 30) {
                            return '🔵 ' . ceil($diffInDays / 7) . ' minggu lalu';
                        } else {
                            return '⚪ ' . ceil($diffInDays / 30) . ' bulan lalu';
                        }
                    })
                    ->badge()
                    ->color(function ($record) {
                        $diffInDays = $record->updated_at->diffInDays(now());
                        
                        if ($diffInDays <= 1) return 'success';
                        if ($diffInDays <= 7) return 'warning';
                        if ($diffInDays <= 30) return 'info';
                        return 'gray';
                    })
                    ->tooltip(function ($record) {
                        return 'Terakhir diperbarui: ' . $record->updated_at->format('d M Y H:i:s') . ' (' . $record->updated_at->diffForHumans() . ')';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('config_completeness')
                    ->label('📋 Kelengkapan')
                    ->state(function ($record) {
                        $score = 0;
                        $maxScore = 4;
                        
                        // Check each configuration aspect
                        if ($record->role) $score++;
                        if ($record->chat_id) $score++;
                        if (!empty($record->notification_types)) $score++;
                        if ($record->is_active) $score++;
                        
                        $percentage = round(($score / $maxScore) * 100);
                        
                        $emoji = match(true) {
                            $percentage >= 100 => '🟢',
                            $percentage >= 75 => '🟡', 
                            $percentage >= 50 => '🟠',
                            $percentage >= 25 => '🔴',
                            default => '⚪'
                        };
                        
                        return $emoji . ' ' . $percentage . '%';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $score = 0;
                        if ($record->role) $score++;
                        if ($record->chat_id) $score++;
                        if (!empty($record->notification_types)) $score++;
                        if ($record->is_active) $score++;
                        
                        $percentage = ($score / 4) * 100;
                        
                        if ($percentage >= 100) return 'success';
                        if ($percentage >= 75) return 'warning';
                        if ($percentage >= 50) return 'info';
                        return 'danger';
                    })
                    ->tooltip(function ($record) {
                        $checks = [
                            'Role dikonfigurasi' => (bool) $record->role,
                            'Chat ID diatur' => (bool) $record->chat_id,
                            'Notifikasi dipilih' => !empty($record->notification_types),
                            'Status aktif' => $record->is_active,
                        ];
                        
                        $tooltip = "Status konfigurasi:\n";
                        foreach ($checks as $item => $status) {
                            $icon = $status ? '✅' : '❌';
                            $tooltip .= "{$icon} {$item}\n";
                        }
                        
                        return $tooltip;
                    })
                    ->sortable(query: function (\Illuminate\Database\Eloquent\Builder $query, string $direction): \Illuminate\Database\Eloquent\Builder {
                        return $query->orderByRaw(
                            '(CASE WHEN role IS NOT NULL THEN 1 ELSE 0 END + '
                            . 'CASE WHEN chat_id IS NOT NULL THEN 1 ELSE 0 END + '
                            . 'CASE WHEN JSON_LENGTH(notification_types) > 0 THEN 1 ELSE 0 END + '
                            . 'CASE WHEN is_active = 1 THEN 1 ELSE 0 END) ' . $direction
                        );
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('👥 Filter Role')
                    ->options([
                        'admin' => '🔧 Administrator',
                        'manajer' => '👔 Manajer',
                        'bendahara' => '💼 Bendahara',
                        'petugas' => '🏥 Petugas',
                        'dokter' => '👨‍⚕️ Dokter',
                        'paramedis' => '👩‍⚕️ Paramedis',
                        'non_paramedis' => '👥 Non Paramedis',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('role_type')
                    ->label('🎯 Tipe Konfigurasi')
                    ->options([
                        'general' => '🌐 Umum',
                        'specific_user' => '👤 Pengguna Spesifik',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('🔌 Status Aktif')
                    ->trueLabel('🟢 Aktif')
                    ->falseLabel('🔴 Nonaktif')
                    ->placeholder('🔗 Semua Status'),
                    
                Tables\Filters\Filter::make('has_notifications')
                    ->label('📢 Ada Notifikasi')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNotNull('notification_types')->whereJsonLength('notification_types', '>', 0))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('missing_chat_id')
                    ->label('❌ Tanpa Chat ID')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNull('chat_id')->orWhere('chat_id', ''))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('incomplete_config')
                    ->label('⚠️ Konfigurasi Tidak Lengkap')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query) {
                        return $query->where(function ($q) {
                            $q->whereNull('chat_id')
                              ->orWhere('chat_id', '')
                              ->orWhereNull('notification_types')
                              ->orWhereJsonLength('notification_types', 0);
                        });
                    })
                    ->toggle(),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->color('warning'),
                        
                    Tables\Actions\Action::make('test_notification')
                        ->label('🧪 Test')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading(fn (TelegramSetting $record) => 'Test Notifikasi ke ' . $record->getDisplayName())
                        ->modalDescription(fn (TelegramSetting $record) => 'Kirim notifikasi test ke Chat ID: ' . ($record->chat_id ?? 'Belum diatur'))
                        ->action(function (TelegramSetting $record) {
                            try {
                                if (! $record->chat_id) {
                                    throw new \Exception('Chat ID tidak tersedia untuk role '.$record->role);
                                }

                                if (! $record->is_active) {
                                    throw new \Exception('Konfigurasi sedang nonaktif');
                                }

                                $telegramService = app(\App\Services\TelegramService::class);
                                $message = "🧪 *Test Notification*\n\n".
                                          "Role: *{$record->role}*\n".
                                          "Chat ID: `{$record->chat_id}`\n".
                                          "Waktu: " . now()->format('d M Y H:i:s') . "\n\n".
                                          "✅ Telegram bot Dokterku berfungsi dengan baik!\n\n".
                                          "_Pesan ini dikirim melalui fitur test notifikasi._";

                                $result = $telegramService->sendMessage($record->chat_id, $message);

                                if ($result) {
                                    Notification::make()
                                        ->title('✅ Test Berhasil!')
                                        ->body('Notifikasi test berhasil dikirim ke ' . $record->getDisplayName())
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Gagal mengirim pesan ke Telegram');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Test Gagal!')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (TelegramSetting $record) => $record->chat_id && $record->is_active),
                        
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (TelegramSetting $record) => $record->is_active ? '🔴 Nonaktifkan' : '🟢 Aktifkan')
                        ->icon(fn (TelegramSetting $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (TelegramSetting $record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function (TelegramSetting $record) {
                            $newStatus = !$record->is_active;
                            $record->update(['is_active' => $newStatus]);
                            
                            $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
                            
                            Notification::make()
                                ->title('Status Berubah!')
                                ->body($record->getDisplayName() . ' berhasil ' . $statusText)
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->requiresConfirmation(),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('🟢 Aktifkan Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $updated = $records->where('is_active', false)->count();
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('✅ Berhasil Diaktifkan!')
                                ->body("Mengaktifkan {$updated} dari {$records->count()} konfigurasi")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('🔴 Nonaktifkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $updated = $records->where('is_active', true)->count();
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('✅ Berhasil Dinonaktifkan!')
                                ->body("Menonaktifkan {$updated} dari {$records->count()} konfigurasi")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('🗑️ Hapus Terpilih'),
                ]),
            ])
            ->emptyStateHeading('🤖 Belum Ada Konfigurasi Telegram')
            ->emptyStateDescription('Mulai dengan menambahkan konfigurasi role pertama untuk menerima notifikasi Telegram')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTelegramSettings::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getModel()::where('is_active', true)->count();
        $totalCount = static::getModel()::count();
        
        return $activeCount > 0 ? "{$activeCount}/{$totalCount}" : $totalCount;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $activeCount = static::getModel()::where('is_active', true)->count();
        $totalCount = static::getModel()::count();
        
        if ($totalCount === 0) return 'gray';
        if ($activeCount === $totalCount) return 'success';
        if ($activeCount > 0) return 'warning';
        return 'danger';
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        $activeCount = static::getModel()::where('is_active', true)->count();
        $totalCount = static::getModel()::count();
        $incompleteCount = static::getModel()::where(function ($query) {
            $query->whereNull('chat_id')
                  ->orWhere('chat_id', '')
                  ->orWhereNull('notification_types')
                  ->orWhereJsonLength('notification_types', 0);
        })->count();
        
        return "Aktif: {$activeCount} | Total: {$totalCount} | Tidak Lengkap: {$incompleteCount}";
    }
}
