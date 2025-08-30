<?php

namespace App\Filament\Resources\TelegramSettingResource\Pages;

use App\Filament\Resources\TelegramSettingResource;
use App\Services\TelegramService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms;
use Illuminate\Support\Facades\Http;

class ManageTelegramSettings extends ManageRecords
{
    protected static string $resource = TelegramSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Main Setup Action - Combines Quick Setup and Bot Config
            Actions\Action::make('setup_telegram')
                ->label('🚀 Setup Telegram')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->modal()
                ->modalHeading('🤖 Setup & Konfigurasi Telegram Bot')
                ->modalDescription('Konfigurasi bot dan setup notifikasi untuk multiple role sekaligus')
                ->form([
                    \Filament\Forms\Components\Tabs::make('setup_tabs')
                        ->tabs([
                            \Filament\Forms\Components\Tabs\Tab::make('bot_config')
                                ->label('🤖 Konfigurasi Bot')
                                ->schema([
                                    \Filament\Forms\Components\TextInput::make('bot_token')
                                        ->label('🔐 Token Bot Telegram')
                                        ->placeholder('1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijk')
                                        ->helperText('Token bot dari @BotFather')
                                        ->password()
                                        ->revealable()
                                        ->required()
                                        ->default(fn() => \App\Models\SystemConfig::where('key', 'TELEGRAM_BOT_TOKEN')->value('value')),
                                        
                                    \Filament\Forms\Components\TextInput::make('admin_chat_id')
                                        ->label('📲 Admin Chat ID')
                                        ->placeholder('123456789')
                                        ->helperText('Chat ID admin utama untuk fallback')
                                        ->required()
                                        ->default(fn() => \App\Models\SystemConfig::where('key', 'TELEGRAM_ADMIN_CHAT_ID')->value('value')),
                                ]),
                                
                            \Filament\Forms\Components\Tabs\Tab::make('role_setup')
                                ->label('👥 Setup Role')
                                ->schema([
                                    \Filament\Forms\Components\CheckboxList::make('roles_to_setup')
                                        ->label('👥 Pilih Role untuk Setup')
                                        ->options([
                                            'admin' => '🔧 Administrator - Sistem & User Management',
                                            'manajer' => '👔 Manajer - Laporan & Monitoring',
                                            'bendahara' => '💼 Bendahara - Keuangan & Validasi',
                                            'petugas' => '🏥 Petugas - Operasional Harian',
                                        ])
                                        ->columns(2)
                                        ->helperText('Pilih role yang akan dikonfigurasi'),
                                        
                                    \Filament\Forms\Components\Textarea::make('chat_ids')
                                        ->label('📲 Chat ID (satu per baris)')
                                        ->placeholder("123456789\n-1001234567890")
                                        ->rows(4)
                                        ->helperText('Masukkan Chat ID sesuai urutan role di atas'),
                                        
                                    \Filament\Forms\Components\Toggle::make('use_recommended_notifications')
                                        ->label('🔔 Gunakan Template Notifikasi Standar')
                                        ->default(true)
                                        ->helperText('Otomatis mengatur notifikasi yang direkomendasikan'),
                                ])
                        ])
                ])
                ->action(function (array $data) {
                    try {
                        // Save bot configuration
                        \App\Models\SystemConfig::updateOrCreate(
                            ['key' => 'TELEGRAM_BOT_TOKEN'],
                            [
                                'value' => $data['bot_token'],
                                'description' => 'Token bot Telegram dari BotFather',
                                'category' => 'telegram'
                            ]
                        );
                        
                        \App\Models\SystemConfig::updateOrCreate(
                            ['key' => 'TELEGRAM_ADMIN_CHAT_ID'],
                            [
                                'value' => $data['admin_chat_id'],
                                'description' => 'Chat ID admin utama untuk fallback notifikasi',
                                'category' => 'telegram'
                            ]
                        );
                        
                        $successMessages = ['✅ Bot berhasil dikonfigurasi'];
                        
                        // Setup roles if provided
                        if (!empty($data['roles_to_setup']) && !empty($data['chat_ids'])) {
                            $roles = $data['roles_to_setup'];
                            $chatIds = array_filter(array_map('trim', explode("\n", $data['chat_ids'])));
                            
                            if (count($roles) === count($chatIds)) {
                                $successCount = 0;
                                foreach ($roles as $index => $role) {
                                    $existing = \App\Models\TelegramSetting::where('role', $role)->first();
                                    if (!$existing) {
                                        $notifications = [];
                                        if ($data['use_recommended_notifications']) {
                                            $notifications = array_map(fn($type) => $type->value, \App\Enums\TelegramNotificationType::getForRole($role));
                                        }
                                        
                                        \App\Models\TelegramSetting::create([
                                            'role' => $role,
                                            'chat_id' => $chatIds[$index],
                                            'notification_types' => $notifications,
                                            'is_active' => true,
                                        ]);
                                        $successCount++;
                                    }
                                }
                                if ($successCount > 0) {
                                    $successMessages[] = "✅ {$successCount} role berhasil dikonfigurasi";
                                }
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('🎉 Setup Berhasil!')
                            ->body(implode(', ', $successMessages))
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Setup Gagal!')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Testing & Operations - Combines test functions and bulk operations
            Actions\Action::make('test_operations')
                ->label('🧪 Test & Operasi')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->modal()
                ->modalHeading('🔧 Test Notifikasi & Operasi Massal')
                ->modalDescription('Test koneksi dan lakukan operasi pada konfigurasi Telegram')
                ->form([
                    \Filament\Forms\Components\Tabs::make('operation_tabs')
                        ->tabs([
                            \Filament\Forms\Components\Tabs\Tab::make('test_section')
                                ->label('🧪 Test Notifikasi')
                                ->schema([
                                    \Filament\Forms\Components\Radio::make('test_type')
                                        ->label('Pilih Jenis Test')
                                        ->options([
                                            'admin_only' => '👤 Test Admin Saja',
                                            'all_active' => '👥 Test Semua Role Aktif',
                                        ])
                                        ->default('admin_only')
                                        ->inline()
                                        ->required(),
                                ]),
                                
                            \Filament\Forms\Components\Tabs\Tab::make('bulk_operations')
                                ->label('🛠️ Operasi Massal')
                                ->schema([
                                    \Filament\Forms\Components\Select::make('bulk_operation')
                                        ->label('🎯 Pilih Operasi')
                                        ->options([
                                            'activate_all' => '🟢 Aktifkan Semua',
                                            'deactivate_all' => '🔴 Nonaktifkan Semua',
                                            'sync_notifications' => '🔄 Sinkronisasi Notifikasi',
                                        ])
                                        ->required(),
                                        
                                    \Filament\Forms\Components\CheckboxList::make('target_roles')
                                        ->label('🎯 Target Role (kosongkan untuk semua)')
                                        ->options([
                                            'admin' => '🔧 Administrator',
                                            'manajer' => '👔 Manajer',
                                            'bendahara' => '💼 Bendahara',
                                            'petugas' => '🏥 Petugas',
                                            'dokter' => '👨‍⚕️ Dokter',
                                            'paramedis' => '👩‍⚕️ Paramedis',
                                            'non_paramedis' => '👥 Non Paramedis',
                                        ])
                                        ->columns(2),
                                ])
                        ])
                ])
                ->action(function (array $data) {
                    try {
                        $telegramService = app(\App\Services\TelegramService::class);
                        
                        // Handle test operations
                        if (isset($data['test_type'])) {
                            if ($data['test_type'] === 'admin_only') {
                                $adminChatId = \App\Models\SystemConfig::where('key', 'TELEGRAM_ADMIN_CHAT_ID')->value('value');
                                
                                if (!$adminChatId) {
                                    throw new \Exception('Admin Chat ID belum dikonfigurasi.');
                                }
                                
                                $message = "🧪 *Test Notifikasi Admin*\n\nChat ID: *{$adminChatId}*\nWaktu: " . now()->format('d M Y H:i:s') . "\n\n✅ Telegram bot berfungsi dengan baik!";
                                
                                if ($telegramService->sendMessage((string)$adminChatId, $message)) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('✅ Test Admin Berhasil!')
                                        ->body('Notifikasi berhasil dikirim ke admin.')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Gagal mengirim ke admin. Periksa Chat ID dan token.');
                                }
                            } else {
                                // Test all active
                                $activeSettings = \App\Models\TelegramSetting::where('is_active', true)->get();
                                $successCount = 0;
                                $failCount = 0;
                                
                                foreach ($activeSettings as $setting) {
                                    try {
                                        if (!$setting->chat_id) continue;
                                        
                                        $message = "🧪 *Test Notifikasi*\n\nRole: *{$setting->role}*\nWaktu: " . now()->format('d M Y H:i:s') . "\n\n✅ Bot Telegram berfungsi dengan baik!";
                                        
                                        if ($telegramService->sendMessage($setting->chat_id, $message)) {
                                            $successCount++;
                                        } else {
                                            $failCount++;
                                        }
                                    } catch (\Exception $e) {
                                        $failCount++;
                                    }
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('✅ Test Selesai!')
                                    ->body("📨 Berhasil: {$successCount}" . ($failCount > 0 ? ", ❌ Gagal: {$failCount}" : ''))
                                    ->success()
                                    ->send();
                            }
                        }
                        
                        // Handle bulk operations
                        if (isset($data['bulk_operation'])) {
                            $operation = $data['bulk_operation'];
                            $targetRoles = $data['target_roles'] ?? [];
                            
                            $query = \App\Models\TelegramSetting::query();
                            if (!empty($targetRoles)) {
                                $query->whereIn('role', $targetRoles);
                            }
                            
                            $settings = $query->get();
                            $successCount = 0;
                            
                            switch ($operation) {
                                case 'activate_all':
                                    $successCount = $settings->where('is_active', false)->count();
                                    \App\Models\TelegramSetting::whereIn('id', $settings->pluck('id'))->update(['is_active' => true]);
                                    break;
                                    
                                case 'deactivate_all':
                                    $successCount = $settings->where('is_active', true)->count();
                                    \App\Models\TelegramSetting::whereIn('id', $settings->pluck('id'))->update(['is_active' => false]);
                                    break;
                                    
                                case 'sync_notifications':
                                    foreach ($settings as $setting) {
                                        $recommendedTypes = array_map(fn($type) => $type->value, \App\Enums\TelegramNotificationType::getForRole($setting->role));
                                        if (!empty($recommendedTypes)) {
                                            $setting->update(['notification_types' => $recommendedTypes]);
                                            $successCount++;
                                        }
                                    }
                                    break;
                            }
                            
                            $operationNames = [
                                'activate_all' => 'aktivasi',
                                'deactivate_all' => 'deaktivasi',
                                'sync_notifications' => 'sinkronisasi notifikasi'
                            ];
                            
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Operasi Berhasil!')
                                ->body("Berhasil melakukan {$operationNames[$operation]} pada {$successCount} konfigurasi")
                                ->success()
                                ->send();
                        }
                        
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Operasi Gagal!')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Bot Info - Keep as lightweight separate action
            Actions\Action::make('bot_info')
                ->label('ℹ️ Info Bot')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modal()
                ->modalHeading('🤖 Informasi Bot Telegram')
                ->modalContent(function () {
                    try {
                        $telegramService = app(TelegramService::class);
                        $botInfo = $telegramService->getBotInfo();
                        
                        if ($botInfo) {
                            $content = view('filament.telegram.bot-info', compact('botInfo'))->render();
                        } else {
                            $content = '<div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-800">❌ Tidak dapat terhubung ke bot</p>
                                <p class="text-sm text-red-600 mt-1">Periksa token bot</p>
                            </div>';
                        }
                        
                        return new \Illuminate\Support\HtmlString($content);
                    } catch (\Exception $e) {
                        return new \Illuminate\Support\HtmlString(
                            '<div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-800">❌ Error: ' . $e->getMessage() . '</p>
                            </div>'
                        );
                    }
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            // Add Configuration - Keep separate as it's a different workflow
            Actions\CreateAction::make()
                ->label('➕ Tambah Konfigurasi')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->modalHeading('✨ Tambah Pengaturan Telegram')
                ->modalDescription(function () {
                    $allRoles = [
                        'admin' => '🔧 Administrator',
                        'manajer' => '👔 Manajer',
                        'bendahara' => '💼 Bendahara',
                        'petugas' => '🏥 Petugas',
                        'dokter' => '👨‍⚕️ Dokter',
                        'paramedis' => '👩‍⚕️ Paramedis',
                        'non_paramedis' => '👥 Non Paramedis'
                    ];
                    
                    $existingRoles = \App\Models\TelegramSetting::pluck('role')->toArray();
                    $missingRoles = array_diff(array_keys($allRoles), $existingRoles);
                    
                    if (!empty($missingRoles)) {
                        $missingNames = array_map(fn($role) => $allRoles[$role], $missingRoles);
                        return '⚠️ Role belum dikonfigurasi: ' . implode(', ', $missingNames);
                    }
                    
                    return '✅ Semua role sudah dikonfigurasi. Tambah konfigurasi khusus jika diperlukan.';
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Re-enable when widget is fixed
            // \App\Filament\Widgets\TelegramStatsWidget::class,
        ];
    }
    
    protected function getTableBulkActions(): array
    {
        return [
            \Filament\Tables\Actions\BulkActionGroup::make([
                \Filament\Tables\Actions\BulkAction::make('bulk_activate')
                    ->label('🟢 Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each->update(['is_active' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil!')
                            ->body('Mengaktifkan ' . $records->count() . ' konfigurasi')
                            ->success()
                            ->send();
                    }),
                    
                \Filament\Tables\Actions\BulkAction::make('bulk_deactivate')
                    ->label('🔴 Nonaktifkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each->update(['is_active' => false]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil!')
                            ->body('Menonaktifkan ' . $records->count() . ' konfigurasi')
                            ->success()
                            ->send();
                    }),
                    
                \Filament\Tables\Actions\BulkAction::make('bulk_test')
                    ->label('🧪 Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $telegramService = app(\App\Services\TelegramService::class);
                        $successCount = 0;
                        $errorCount = 0;
                        
                        foreach ($records->where('is_active', true) as $record) {
                            try {
                                if (!$record->chat_id) continue;
                                
                                $message = "🧪 *Bulk Test - Selected Records*\n\n"
                                . "Role: *{$record->role}*\n"
                                . "Waktu: " . now()->format('d M Y H:i:s') . "\n\n"
                                . "✅ Test notifikasi berhasil!";
                                
                                if ($telegramService->sendMessage($record->chat_id, $message)) {
                                    $successCount++;
                                } else {
                                    $errorCount++;
                                }
                            } catch (\Exception $e) {
                                $errorCount++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Test Selesai!')
                            ->body("Berhasil: {$successCount}, Gagal: {$errorCount}")
                            ->success()
                            ->send();
                    }),
                    
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    public function getTitle(): string
    {
        return '🤖 Pengaturan Telegram Bot';
    }

    public function getSubheading(): ?string
    {
        $totalConfigs = \App\Models\TelegramSetting::count();
        $activeConfigs = \App\Models\TelegramSetting::where('is_active', true)->count();
        $totalNotifications = \App\Models\TelegramSetting::where('is_active', true)
            ->get()
            ->sum(function($setting) {
                return count($setting->notification_types ?? []);
            });
        
        return "Kelola notifikasi Telegram untuk setiap role sistem | 📊 {$activeConfigs}/{$totalConfigs} aktif | 📢 {$totalNotifications} total notifikasi";
    }
    
    protected function getTableFiltersLayout(): ?string
    {
        return 'above-content';
    }
}