<?php

namespace App\Services;

use App\Enums\TelegramNotificationType;
use App\Models\SystemConfig;
use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    protected ?TelegramTemplateService $templateService = null;

    public function __construct()
    {
        // Initialize template service if available
        if (class_exists(TelegramTemplateService::class)) {
            $this->templateService = app(TelegramTemplateService::class);
        }
    }
    public function sendMessage(string $chatId, string $message, array $options = []): bool
    {
        Log::info('TelegramService::sendMessage called', [
            'chat_id' => $chatId,
            'message_length' => strlen($message),
            'has_options' => ! empty($options),
        ]);

        // Get token from database or config
        $token = $this->getBotToken();
        Log::info('Bot token retrieved', [
            'has_token' => ! empty($token),
            'is_demo' => $this->isDemoToken($token),
            'token_preview' => $token ? substr($token, 0, 10).'...' : 'null',
        ]);

        if (! $token || $this->isDemoToken($token)) {
            // Demo mode - log but don't send in production
            if (app()->environment(['local', 'development'])) {
                Log::info("Demo Telegram Message to {$chatId}: {$message}");
                return true;
            } else {
                Log::warning("Telegram token not configured properly in production");
                return false;
            }
        }

        try {
            // Set token dynamically
            config(['telegram.bots.dokterku.token' => $token]);

            Log::info("Sending message to chat_id: {$chatId}");

            $response = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                ...$options,
            ]);

            $success = $response->getMessageId() !== null;
            Log::info('Send result: '.($success ? 'SUCCESS' : 'FAILED'), [
                'message_id' => $response->getMessageId(),
                'chat_id' => $chatId,
            ]);

            return $success;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram send message error: '.$e->getMessage(), [
                'chat_id' => $chatId,
                'error_code' => $e->getCode(),
            ]);

            return false;
        }
    }

    public function getBotInfo(): array
    {
        // Get token from database or config
        $token = $this->getBotToken();
        if (! $token || $this->isDemoToken($token)) {
            return [
                'id' => 1234567890,
                'username' => 'dokterku_demo_bot',
                'first_name' => 'Dokterku Demo Bot',
                'is_bot' => true,
                'can_join_groups' => true,
                'can_read_all_group_messages' => false,
                'supports_inline_queries' => false,
                'demo_mode' => true,
            ];
        }

        try {
            // Set token dynamically
            config(['telegram.bots.dokterku.token' => $token]);

            $response = Telegram::getMe();

            return [
                'id' => $response->getId(),
                'username' => $response->getUsername(),
                'first_name' => $response->getFirstName(),
                'is_bot' => $response->isBot(),
                'can_join_groups' => true,
                'can_read_all_group_messages' => false,
                'demo_mode' => false,
            ];
        } catch (TelegramSDKException $e) {
            throw new \Exception('Failed to get bot info: '.$e->getMessage());
        }
    }

    public function sendNotificationToRole(string $role, string $notificationType, string $message, ?int $userId = null): bool
    {
        Log::info('TelegramService::sendNotificationToRole called', [
            'role' => $role,
            'user_id' => $userId,
            'notification_type' => $notificationType,
            'message_preview' => substr($message, 0, 100).'...',
        ]);

        // If user_id is provided, try to find user-specific setting first
        if ($userId) {
            $userSetting = TelegramSetting::where('role', $role)
                ->where('user_id', $userId)
                ->where('role_type', 'specific_user')
                ->where('is_active', true)
                ->first();

            if ($userSetting && $userSetting->chat_id && $userSetting->hasNotificationType($notificationType)) {
                Log::info("Sending notification to specific user {$userId} in role {$role} with chat_id: {$userSetting->chat_id}");
                $result = $this->sendMessage($userSetting->chat_id, $message);
                Log::info('User-specific notification result: '.($result ? 'SUCCESS' : 'FAILED'));

                return $result;
            }
        }

        // Fallback to general role setting
        $setting = TelegramSetting::where('role', $role)
            ->where(function ($query) {
                $query->where('role_type', 'general')
                    ->orWhereNull('role_type');
            })
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            Log::warning("No telegram setting found for role: {$role}");

            return false;
        }

        if (! $setting->chat_id) {
            Log::warning("No chat_id configured for role: {$role}");

            return false;
        }

        if (! $setting->hasNotificationType($notificationType)) {
            Log::warning("Notification type {$notificationType} not enabled for role: {$role}", [
                'enabled_types' => $setting->notification_types ?? [],
            ]);

            return false;
        }

        Log::info("Sending notification to role {$role} with chat_id: {$setting->chat_id}");
        $result = $this->sendMessage($setting->chat_id, $message);
        Log::info('Notification result: '.($result ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    public function sendNotificationToMultipleRoles(array $roles, string $notificationType, string $message): array
    {
        $results = [];

        foreach ($roles as $role) {
            $results[$role] = $this->sendNotificationToRole($role, $notificationType, $message);
        }

        return $results;
    }

    public function formatNotificationMessage(string $type, array $data = [], ?string $targetRole = null): string
    {
        // Use template service if available
        if ($this->templateService) {
            try {
                return $this->templateService->generateMessage($type, $data, $targetRole);
            } catch (\Exception $e) {
                Log::warning('Template service failed, falling back to legacy formatting', [
                    'error' => $e->getMessage(),
                    'type' => $type,
                ]);
            }
        }

        // Legacy formatting as fallback
        return $this->formatMessageLegacy($type, $data);
    }

    /**
     * Legacy message formatting method (kept as fallback)
     */
    protected function formatMessageLegacy(string $type, array $data = []): string
    {
        $emoji = $this->getNotificationEmoji($type);
        $title = $this->getNotificationTitle($type);

        $message = "{$emoji} <b>{$title}</b>\n\n";

        $enum = TelegramNotificationType::tryFrom($type);
        if ($enum) {
            switch ($enum) {
                case TelegramNotificationType::PENDAPATAN:
                    $message .= '💰 Pendapatan: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::PENGELUARAN:
                    $message .= '📉 Pengeluaran: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::PASIEN:
                    $message .= '👤 Nama Pasien: '.($data['patient_name'] ?? '-')."\n";
                    $message .= '🩺 Tindakan: '.($data['procedure'] ?? '-')."\n";
                    if (isset($data['dokter_name'])) {
                        $message .= '👨‍⚕️ Dokter: '.$data['dokter_name']."\n";
                    }
                    break;

                case TelegramNotificationType::VALIDASI_DISETUJUI:
                    if (isset($data['type'])) {
                        $message .= '📋 Jenis: '.$data['type']."\n";
                    }
                    $message .= '💰 Nilai: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    if (isset($data['date'])) {
                        $message .= '📅 Tanggal: '.$data['date']."\n";
                    }
                    if (isset($data['shift'])) {
                        $message .= '⏰ Shift: '.$data['shift']."\n";
                    }
                    if (isset($data['petugas'])) {
                        $message .= '👤 Input oleh: '.$data['petugas']."\n";
                    }
                    if (isset($data['validator_name'])) {
                        $message .= '✅ Divalidasi oleh: '.$data['validator_name']."\n";
                    }
                    break;

                case TelegramNotificationType::JASPEL_SELESAI:
                    $message .= '👨‍⚕️ Dokter: '.($data['doctor_name'] ?? '-')."\n";
                    $message .= '💰 Jaspel: Rp '.number_format($data['jaspel_amount'] ?? 0, 0, ',', '.')."\n";
                    break;

                case TelegramNotificationType::BACKUP_GAGAL:
                    $message .= '❌ Error: '.($data['error'] ?? 'Unknown error')."\n";
                    $message .= '🕐 Waktu: '.($data['time'] ?? now()->format('H:i:s'))."\n";
                    break;

                case TelegramNotificationType::USER_BARU:
                    $message .= '👤 Nama: '.($data['user_name'] ?? '-')."\n";
                    $message .= '🏷️ Role: '.($data['role'] ?? '-')."\n";
                    break;

                case TelegramNotificationType::REKAP_HARIAN:
                case TelegramNotificationType::REKAP_MINGGUAN:
                    $message .= '📊 Total Pendapatan: Rp '.number_format($data['total_income'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📉 Total Pengeluaran: Rp '.number_format($data['total_expense'] ?? 0, 0, ',', '.')."\n";
                    $message .= '💰 Saldo: Rp '.number_format(($data['total_income'] ?? 0) - ($data['total_expense'] ?? 0), 0, ',', '.')."\n";
                    break;

                // Enhanced notification types
                case TelegramNotificationType::PRESENSI_DOKTER:
                case TelegramNotificationType::PRESENSI_PARAMEDIS:
                    $message .= '👤 Nama: '.($data['staff_name'] ?? '-')."\n";
                    $message .= '⏰ Jenis: '.($data['type'] ?? 'Check-in')."\n";
                    $message .= '🕐 Waktu: '.($data['time'] ?? now()->format('H:i:s'))."\n";
                    if (isset($data['shift'])) {
                        $message .= '⌚ Shift: '.$data['shift']."\n";
                    }
                    break;

                case TelegramNotificationType::TINDAKAN_BARU:
                    $message .= '👤 Pasien: '.($data['patient_name'] ?? '-')."\n";
                    $message .= '🩺 Tindakan: '.($data['procedure'] ?? '-')."\n";
                    $message .= '👨‍⚕️ Dokter: '.($data['dokter_name'] ?? '-')."\n";
                    $message .= '💰 Tarif: Rp '.number_format($data['tarif'] ?? 0, 0, ',', '.')."\n";
                    if (isset($data['paramedis_name'])) {
                        $message .= '👩‍⚕️ Paramedis: '.$data['paramedis_name']."\n";
                    }
                    break;

                case TelegramNotificationType::VALIDASI_PENDING:
                    $message .= '📋 Jenis: '.($data['type'] ?? '-')."\n";
                    $message .= '💰 Nilai: Rp '.number_format($data['amount'] ?? 0, 0, ',', '.')."\n";
                    $message .= '👤 Input oleh: '.($data['input_by'] ?? '-')."\n";
                    $message .= '📅 Tanggal: '.($data['date'] ?? now()->format('d/m/Y'))."\n";
                    $message .= '⚠️ Status: Menunggu validasi bendahara';
                    break;

                case TelegramNotificationType::JASPEL_DOKTER_READY:
                    $message .= '👨‍⚕️ Dokter: '.($data['dokter_name'] ?? '-')."\n";
                    $message .= '💰 Total Jaspel: Rp '.number_format($data['total_jaspel'] ?? 0, 0, ',', '.')."\n";
                    $message .= '📊 Jumlah Tindakan: '.($data['total_procedures'] ?? 0).' tindakan'."\n";
                    $message .= '📅 Periode: '.($data['period'] ?? '-')."\n";
                    $message .= '💳 Status: Siap dicairkan';
                    break;

                case TelegramNotificationType::LAPORAN_SHIFT:
                    $message .= '⌚ Shift: '.($data['shift'] ?? '-')."\n";
                    $message .= '📅 Tanggal: '.($data['date'] ?? now()->format('d/m/Y'))."\n";
                    $message .= '👤 Penanggung Jawab: '.($data['pic'] ?? '-')."\n";
                    if (isset($data['patient_count'])) {
                        $message .= '👥 Jumlah Pasien: '.$data['patient_count'].' pasien'."\n";
                    }
                    if (isset($data['procedure_count'])) {
                        $message .= '🩺 Jumlah Tindakan: '.$data['procedure_count'].' tindakan'."\n";
                    }
                    break;

                case TelegramNotificationType::EMERGENCY_ALERT:
                    $message .= '🚨 Level: '.($data['level'] ?? 'HIGH')."\n";
                    $message .= '📍 Lokasi: '.($data['location'] ?? 'Rumah Sakit')."\n";
                    $message .= '📝 Deskripsi: '.($data['description'] ?? 'Situasi emergency')."\n";
                    $message .= '👤 Dilaporkan oleh: '.($data['reporter'] ?? '-')."\n";
                    $message .= '⚡ Tindakan segera diperlukan!';
                    break;

                case TelegramNotificationType::SISTEM_MAINTENANCE:
                    $message .= '🔧 Jenis: '.($data['type'] ?? 'Maintenance terjadwal')."\n";
                    $message .= '⏰ Waktu: '.($data['start_time'] ?? 'Segera')."\n";
                    if (isset($data['end_time'])) {
                        $message .= '⏱️ Estimasi selesai: '.$data['end_time']."\n";
                    }
                    if (isset($data['affected_services'])) {
                        $message .= '🎯 Layanan terdampak: '.$data['affected_services']."\n";
                    }
                    $message .= '📝 Deskripsi: '.($data['description'] ?? 'Maintenance sistem');
                    break;

                case TelegramNotificationType::APPROVAL_REQUEST:
                    $message .= '📝 Jenis Permohonan: '.($data['request_type'] ?? '-')."\n";
                    $message .= '👤 Pemohon: '.($data['requester'] ?? '-')."\n";
                    $message .= '📅 Tanggal: '.($data['date'] ?? now()->format('d/m/Y'))."\n";
                    if (isset($data['amount'])) {
                        $message .= '💰 Nilai: Rp '.number_format($data['amount'], 0, ',', '.')."\n";
                    }
                    $message .= '📝 Deskripsi: '.($data['description'] ?? '-')."\n";
                    $message .= '⏳ Menunggu persetujuan atasan';
                    break;

                case TelegramNotificationType::JADWAL_JAGA_UPDATE:
                    $message .= '👤 Staff: '.($data['staff_name'] ?? '-')."\n";
                    $message .= '📅 Tanggal: '.($data['date'] ?? '-')."\n";
                    $message .= '⌚ Shift: '.($data['shift'] ?? '-')."\n";
                    $message .= '🔄 Jenis Update: '.($data['update_type'] ?? 'Perubahan jadwal')."\n";
                    if (isset($data['old_shift'])) {
                        $message .= '⏰ Shift Lama: '.$data['old_shift']."\n";
                    }
                    if (isset($data['reason'])) {
                        $message .= '📝 Alasan: '.$data['reason']."\n";
                    }
                    break;

                case TelegramNotificationType::CUTI_REQUEST:
                    $message .= '👤 Pemohon: '.($data['staff_name'] ?? '-')."\n";
                    $message .= '🏷️ Jenis Cuti: '.($data['leave_type'] ?? '-')."\n";
                    $message .= '📅 Mulai: '.($data['start_date'] ?? '-')."\n";
                    $message .= '📅 Selesai: '.($data['end_date'] ?? '-')."\n";
                    $message .= '📊 Durasi: '.($data['duration'] ?? '-').' hari'."\n";
                    $message .= '📝 Alasan: '.($data['reason'] ?? '-')."\n";
                    $message .= '⏳ Status: '.($data['status'] ?? 'Menunggu persetujuan');
                    break;

                case TelegramNotificationType::SHIFT_ASSIGNMENT:
                    $message .= '👤 Staff: '.($data['staff_name'] ?? '-')."\n";
                    $message .= '🏷️ Role: '.($data['role'] ?? '-')."\n";
                    $message .= '📅 Tanggal: '.($data['date'] ?? '-')."\n";
                    $message .= '⌚ Shift: '.($data['shift'] ?? '-')."\n";
                    $message .= '🕐 Jam Kerja: '.($data['work_hours'] ?? '-')."\n";
                    if (isset($data['location'])) {
                        $message .= '📍 Lokasi: '.$data['location']."\n";
                    }
                    $message .= '📝 Status: Penugasan baru';
                    break;
            }
        }

        $message .= "\n📅 ".now()->format('d/m/Y H:i:s')."\n";
        $message .= '🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>';

        return $message;
    }

    private function getNotificationEmoji(string $type): string
    {
        if ($enum = TelegramNotificationType::tryFrom($type)) {
            return match ($enum) {
                TelegramNotificationType::PENDAPATAN => '💰',
                TelegramNotificationType::PENGELUARAN => '📉',
                TelegramNotificationType::PASIEN => '👤',
                TelegramNotificationType::USER_BARU => '👋',
                TelegramNotificationType::REKAP_HARIAN => '📊',
                TelegramNotificationType::REKAP_MINGGUAN => '📈',
                TelegramNotificationType::VALIDASI_DISETUJUI => '✅',
                TelegramNotificationType::JASPEL_SELESAI => '💼',
                TelegramNotificationType::BACKUP_GAGAL => '🚨',
                // Enhanced notification types
                TelegramNotificationType::PRESENSI_DOKTER => '🩺',
                TelegramNotificationType::PRESENSI_PARAMEDIS => '👩‍⚕️',
                TelegramNotificationType::TINDAKAN_BARU => '🏥',
                TelegramNotificationType::VALIDASI_PENDING => '⏳',
                TelegramNotificationType::JASPEL_DOKTER_READY => '💵',
                TelegramNotificationType::LAPORAN_SHIFT => '📋',
                TelegramNotificationType::EMERGENCY_ALERT => '🚨',
                TelegramNotificationType::SISTEM_MAINTENANCE => '🔧',
                TelegramNotificationType::APPROVAL_REQUEST => '📝',
                TelegramNotificationType::JADWAL_JAGA_UPDATE => '📅',
                TelegramNotificationType::CUTI_REQUEST => '🏖️',
                TelegramNotificationType::SHIFT_ASSIGNMENT => '⏰',
            };
        }

        return '📢';
    }

    private function getNotificationTitle(string $type): string
    {
        if ($enum = TelegramNotificationType::tryFrom($type)) {
            return match ($enum) {
                TelegramNotificationType::PENDAPATAN => 'Pendapatan Berhasil Diinput',
                TelegramNotificationType::PENGELUARAN => 'Pengeluaran Berhasil Diinput',
                TelegramNotificationType::PASIEN => 'Pasien Berhasil Diinput',
                TelegramNotificationType::USER_BARU => 'User Baru Ditambahkan',
                TelegramNotificationType::REKAP_HARIAN => 'Rekap Harian',
                TelegramNotificationType::REKAP_MINGGUAN => 'Rekap Mingguan',
                TelegramNotificationType::VALIDASI_DISETUJUI => 'Validasi Disetujui',
                TelegramNotificationType::JASPEL_SELESAI => 'Jaspel Selesai',
                TelegramNotificationType::BACKUP_GAGAL => 'Backup Gagal',
                // Enhanced notification types
                TelegramNotificationType::PRESENSI_DOKTER => 'Presensi Dokter',
                TelegramNotificationType::PRESENSI_PARAMEDIS => 'Presensi Paramedis',
                TelegramNotificationType::TINDAKAN_BARU => 'Tindakan Medis Baru',
                TelegramNotificationType::VALIDASI_PENDING => 'Menunggu Validasi',
                TelegramNotificationType::JASPEL_DOKTER_READY => 'Jaspel Siap Dicairkan',
                TelegramNotificationType::LAPORAN_SHIFT => 'Laporan Pergantian Shift',
                TelegramNotificationType::EMERGENCY_ALERT => 'Alert Emergency',
                TelegramNotificationType::SISTEM_MAINTENANCE => 'Maintenance Sistem',
                TelegramNotificationType::APPROVAL_REQUEST => 'Permohonan Persetujuan',
                TelegramNotificationType::JADWAL_JAGA_UPDATE => 'Update Jadwal Jaga',
                TelegramNotificationType::CUTI_REQUEST => 'Permohonan Cuti',
                TelegramNotificationType::SHIFT_ASSIGNMENT => 'Penugasan Shift Baru',
            };
        }

        return 'Notifikasi Sistem';
    }

    public function isConfigured(): bool
    {
        $token = SystemConfig::get('telegram_bot_token');

        return ! empty($token);
    }

    public function getActiveSettings(): array
    {
        return TelegramSetting::where('is_active', true)
            ->whereNotNull('chat_id')
            ->get()
            ->keyBy('role')
            ->toArray();
    }

    /**
     * Get bot token from database or fallback to config
     */
    protected function getBotToken(): ?string
    {
        // Try to get from database first
        $dbToken = SystemConfig::where('key', 'TELEGRAM_BOT_TOKEN')->value('value');

        if ($dbToken) {
            return $dbToken;
        }

        // Fallback to config/env
        return config('telegram.bots.dokterku.token');
    }

    /**
     * Check if token is demo/placeholder token
     */
    protected function isDemoToken(?string $token): bool
    {
        if (! $token) {
            return true;
        }

        $demoTokens = [
            'YOUR-BOT-TOKEN',
            'your_bot_token_from_botfather',
            '1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijk',
        ];

        // Check if token contains demo patterns
        foreach ($demoTokens as $demoToken) {
            if ($token === $demoToken || str_contains($token, 'ABCD')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send cross-role notification (e.g., when bendahara validates, notify dokter)
     */
    public function sendCrossRoleNotification(array $targetRoles, string $notificationType, array $data, ?int $excludeUserId = null): array
    {
        Log::info('TelegramService::sendCrossRoleNotification called', [
            'target_roles' => $targetRoles,
            'notification_type' => $notificationType,
            'exclude_user_id' => $excludeUserId,
        ]);

        $results = [];
        $message = $this->formatNotificationMessage($notificationType, $data);

        foreach ($targetRoles as $role) {
            // Skip if this is the same user who triggered the action
            if ($excludeUserId && isset($data['user_id']) && $data['user_id'] == $excludeUserId) {
                continue;
            }

            $results[$role] = $this->sendNotificationToRole($role, $notificationType, $message);
        }

        return $results;
    }

    /**
     * Send notification to specific user by ID
     */
    public function sendNotificationToUser(int $userId, string $notificationType, array $data): bool
    {
        Log::info('TelegramService::sendNotificationToUser called', [
            'user_id' => $userId,
            'notification_type' => $notificationType,
        ]);

        // Find user's telegram setting
        $userSetting = TelegramSetting::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$userSetting || !$userSetting->chat_id) {
            Log::warning("No telegram setting found for user ID: {$userId}");
            return false;
        }

        if (!$userSetting->hasNotificationType($notificationType)) {
            Log::warning("Notification type {$notificationType} not enabled for user {$userId}");
            return false;
        }

        // Get user's role for template customization
        $user = \App\Models\User::find($userId);
        $userRole = $user ? ($user->role->name ?? null) : null;
        
        $message = $this->formatNotificationMessage($notificationType, $data, $userRole);
        return $this->sendMessage($userSetting->chat_id, $message);
    }

    /**
     * Send emergency notification to all active roles
     */
    public function sendEmergencyNotification(string $message, array $data = []): array
    {
        Log::info('TelegramService::sendEmergencyNotification called');

        $emergencyRoles = ['admin', 'manajer', 'dokter', 'paramedis'];
        $formattedMessage = $this->formatNotificationMessage(
            TelegramNotificationType::EMERGENCY_ALERT->value, 
            array_merge($data, ['description' => $message])
        );

        $results = [];
        foreach ($emergencyRoles as $role) {
            $results[$role] = $this->sendNotificationToRole(
                $role, 
                TelegramNotificationType::EMERGENCY_ALERT->value, 
                $formattedMessage
            );
        }

        return $results;
    }

    /**
     * Send notification with retry mechanism
     */
    public function sendNotificationWithRetry(string $chatId, string $message, int $maxRetries = 3): bool
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            if ($this->sendMessage($chatId, $message)) {
                return true;
            }
            
            if ($attempt < $maxRetries) {
                Log::warning("Telegram notification attempt {$attempt} failed, retrying...");
                sleep(2 ** $attempt); // Exponential backoff
            }
        }
        
        Log::error("Telegram notification failed after {$maxRetries} attempts");
        return false;
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        $activeSettings = TelegramSetting::where('is_active', true)->count();
        $totalSettings = TelegramSetting::count();
        
        $roleStats = TelegramSetting::where('is_active', true)
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return [
            'active_settings' => $activeSettings,
            'total_settings' => $totalSettings,
            'role_distribution' => $roleStats,
            'bot_configured' => $this->isConfigured(),
        ];
    }

    /**
     * Validate notification routing for specific user and type
     */
    public function validateNotificationRouting(int $userId, string $notificationType): array
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            return [
                'valid' => false,
                'reason' => 'User not found',
            ];
        }

        $userRole = $user->role->name ?? null;
        if (!$userRole) {
            return [
                'valid' => false,
                'reason' => 'User role not found',
            ];
        }

        // Check if notification type is valid for user role
        $roleNotifications = TelegramNotificationType::getForRole($userRole);
        $notificationTypes = array_map(fn($enum) => $enum->value, $roleNotifications);
        
        if (!in_array($notificationType, $notificationTypes)) {
            return [
                'valid' => false,
                'reason' => "Notification type '{$notificationType}' not valid for role '{$userRole}'",
                'valid_types' => $notificationTypes,
            ];
        }

        // Check telegram setting
        $setting = TelegramSetting::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            return [
                'valid' => false,
                'reason' => 'No active telegram setting found for user',
            ];
        }

        if (!$setting->hasNotificationType($notificationType)) {
            return [
                'valid' => false,
                'reason' => 'Notification type not enabled in user settings',
                'enabled_types' => $setting->notification_types ?? [],
            ];
        }

        return [
            'valid' => true,
            'user_role' => $userRole,
            'chat_id' => $setting->chat_id,
            'enabled_types' => $setting->notification_types ?? [],
        ];
    }
}
