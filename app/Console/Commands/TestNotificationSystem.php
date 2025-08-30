<?php

namespace App\Console\Commands;

use App\Enums\TelegramNotificationType;
use App\Facades\NotificationDispatcher;
use App\Services\NotificationDispatcherService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * TestNotificationSystem Command
 * 
 * Comprehensive testing command for the enhanced notification system.
 */
class TestNotificationSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:test
                            {--type= : Specific notification type to test}
                            {--role= : Specific role to test}
                            {--user= : Specific user ID to test}
                            {--all : Test all notification types}
                            {--cross-role : Test cross-role notifications}
                            {--emergency : Test emergency notifications}
                            {--stats : Show notification statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Test the enhanced Telegram notification system';

    protected TelegramService $telegramService;
    protected NotificationDispatcherService $dispatcher;

    /**
     * Create a new command instance.
     */
    public function __construct(TelegramService $telegramService, NotificationDispatcherService $dispatcher)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Testing Enhanced Notification System');
        $this->newLine();

        // Show stats if requested
        if ($this->option('stats')) {
            $this->showStatistics();
            return 0;
        }

        // Test specific notification type
        if ($type = $this->option('type')) {
            return $this->testSpecificType($type);
        }

        // Test specific role
        if ($role = $this->option('role')) {
            return $this->testSpecificRole($role);
        }

        // Test specific user
        if ($userId = $this->option('user')) {
            return $this->testSpecificUser((int) $userId);
        }

        // Test emergency notifications
        if ($this->option('emergency')) {
            return $this->testEmergencyNotifications();
        }

        // Test cross-role notifications
        if ($this->option('cross-role')) {
            return $this->testCrossRoleNotifications();
        }

        // Test all notification types
        if ($this->option('all')) {
            return $this->testAllNotificationTypes();
        }

        // Show interactive menu
        return $this->showInteractiveMenu();
    }

    /**
     * Show notification statistics
     */
    protected function showStatistics(): void
    {
        $this->info('📊 Notification System Statistics');
        $this->newLine();

        // Get system stats
        $stats = $this->telegramService->getNotificationStats();
        $dispatchStats = $this->dispatcher->getDispatchStats();

        $this->table(['Metric', 'Value'], [
            ['Active Telegram Settings', $stats['active_settings']],
            ['Total Telegram Settings', $stats['total_settings']],
            ['Bot Configured', $stats['bot_configured'] ? '✅ Yes' : '❌ No'],
            ['Total Notification Types', $dispatchStats['total_rules']],
        ]);

        // Role distribution
        if (!empty($stats['role_distribution'])) {
            $this->newLine();
            $this->info('📋 Role Distribution');
            $roleData = [];
            foreach ($stats['role_distribution'] as $role => $count) {
                $roleData[] = [ucfirst($role), $count];
            }
            $this->table(['Role', 'Active Settings'], $roleData);
        }

        // Available notification types
        $this->newLine();
        $this->info('📢 Available Notification Types');
        $typeData = [];
        foreach (TelegramNotificationType::cases() as $type) {
            $typeData[] = [$type->value, $type->label()];
        }
        $this->table(['Type', 'Label'], $typeData);
    }

    /**
     * Test specific notification type
     */
    protected function testSpecificType(string $type): int
    {
        $this->info("🧪 Testing notification type: {$type}");

        $enum = TelegramNotificationType::tryFrom($type);
        if (!$enum) {
            $this->error("Invalid notification type: {$type}");
            return 1;
        }

        $data = $this->generateTestData($type);
        
        $this->info('📝 Test data:');
        foreach ($data as $key => $value) {
            $this->line("  {$key}: {$value}");
        }

        if ($this->confirm('Send this test notification?')) {
            $results = $this->dispatcher->dispatch($type, $data, ['priority' => 'normal']);
            $this->displayResults($results);
        }

        return 0;
    }

    /**
     * Test specific role
     */
    protected function testSpecificRole(string $role): int
    {
        $this->info("👥 Testing notifications for role: {$role}");

        $availableTypes = TelegramNotificationType::getForRole($role);
        
        if (empty($availableTypes)) {
            $this->warn("No notification types configured for role: {$role}");
            return 1;
        }

        $this->info('Available notification types for this role:');
        foreach ($availableTypes as $type) {
            $this->line("  • {$type->value} - {$type->label()}");
        }

        $selectedType = $this->choice(
            'Select notification type to test',
            array_map(fn($t) => $t->value, $availableTypes)
        );

        return $this->testSpecificType($selectedType);
    }

    /**
     * Test specific user
     */
    protected function testSpecificUser(int $userId): int
    {
        $this->info("👤 Testing notifications for user ID: {$userId}");

        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error("User not found: {$userId}");
            return 1;
        }

        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Role: " . ($user->role->display_name ?? 'Unknown'));

        $validation = $this->telegramService->validateNotificationRouting($userId, 'pasien');
        
        if (!$validation['valid']) {
            $this->warn("Notification routing validation failed: {$validation['reason']}");
            if (isset($validation['valid_types'])) {
                $this->info('Valid types for this user: ' . implode(', ', $validation['valid_types']));
            }
        } else {
            $this->info("✅ User has valid notification setup");
            $this->info("Chat ID: {$validation['chat_id']}");
        }

        return 0;
    }

    /**
     * Test emergency notifications
     */
    protected function testEmergencyNotifications(): int
    {
        $this->info('🚨 Testing Emergency Notifications');

        $data = [
            'level' => 'HIGH',
            'location' => 'Test Location',
            'description' => 'This is a test emergency alert',
            'reporter' => 'System Test',
        ];

        if ($this->confirm('Send test emergency alert?')) {
            $results = $this->telegramService->sendEmergencyNotification(
                'Test emergency alert',
                $data
            );
            $this->displayResults($results);
        }

        return 0;
    }

    /**
     * Test cross-role notifications
     */
    protected function testCrossRoleNotifications(): int
    {
        $this->info('🔄 Testing Cross-Role Notifications');

        $scenarios = [
            'Validation Approval' => [
                'type' => TelegramNotificationType::VALIDASI_DISETUJUI->value,
                'roles' => ['dokter', 'manajer'],
                'data' => [
                    'type' => 'Tindakan Medis',
                    'amount' => 150000,
                    'description' => 'Konsultasi umum',
                    'dokter_name' => 'Dr. Test',
                    'patient_name' => 'Test Patient',
                    'validator_name' => 'Test Bendahara',
                ]
            ],
            'New Medical Procedure' => [
                'type' => TelegramNotificationType::TINDAKAN_BARU->value,
                'roles' => ['bendahara', 'manajer'],
                'data' => [
                    'patient_name' => 'Test Patient',
                    'procedure' => 'Test Procedure',
                    'dokter_name' => 'Dr. Test',
                    'tarif' => 200000,
                    'tanggal_tindakan' => now()->format('d/m/Y H:i'),
                ]
            ]
        ];

        $scenario = $this->choice('Select test scenario', array_keys($scenarios));
        $selectedScenario = $scenarios[$scenario];

        $this->info("Testing scenario: {$scenario}");
        $this->info("Target roles: " . implode(', ', $selectedScenario['roles']));

        if ($this->confirm('Execute cross-role notification test?')) {
            $results = $this->telegramService->sendCrossRoleNotification(
                $selectedScenario['roles'],
                $selectedScenario['type'],
                $selectedScenario['data']
            );
            $this->displayResults($results);
        }

        return 0;
    }

    /**
     * Test all notification types
     */
    protected function testAllNotificationTypes(): int
    {
        $this->info('🧪 Testing All Notification Types');
        $this->warn('This will send test notifications for ALL types!');

        if (!$this->confirm('Are you sure you want to proceed?')) {
            return 0;
        }

        $results = [];
        $bar = $this->output->createProgressBar(count(TelegramNotificationType::cases()));

        foreach (TelegramNotificationType::cases() as $type) {
            $bar->setMessage("Testing {$type->value}");
            
            $data = $this->generateTestData($type->value);
            $result = $this->dispatcher->dispatch($type->value, $data, ['priority' => 'low']);
            $results[$type->value] = $result;
            
            $bar->advance();
            sleep(1); // Avoid rate limiting
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('📊 Test Results Summary:');
        foreach ($results as $type => $result) {
            $success = !empty(array_filter($result));
            $status = $success ? '✅' : '❌';
            $this->line("{$status} {$type}");
        }

        return 0;
    }

    /**
     * Show interactive menu
     */
    protected function showInteractiveMenu(): int
    {
        $this->info('🎛️  Interactive Notification Testing Menu');
        $this->newLine();

        $options = [
            'Test specific notification type',
            'Test notifications for specific role',
            'Test user notification setup',
            'Test emergency notifications',
            'Test cross-role notifications',
            'Show system statistics',
            'Exit'
        ];

        $choice = $this->choice('What would you like to test?', $options);

        switch ($choice) {
            case $options[0]:
                $type = $this->choice(
                    'Select notification type',
                    array_map(fn($t) => $t->value, TelegramNotificationType::cases())
                );
                return $this->testSpecificType($type);

            case $options[1]:
                $role = $this->choice(
                    'Select role',
                    ['admin', 'manajer', 'bendahara', 'petugas', 'dokter', 'paramedis', 'non_paramedis']
                );
                return $this->testSpecificRole($role);

            case $options[2]:
                $userId = $this->ask('Enter user ID');
                return $this->testSpecificUser((int) $userId);

            case $options[3]:
                return $this->testEmergencyNotifications();

            case $options[4]:
                return $this->testCrossRoleNotifications();

            case $options[5]:
                $this->showStatistics();
                return 0;

            case $options[6]:
                $this->info('👋 Goodbye!');
                return 0;
        }

        return 0;
    }

    /**
     * Generate test data for notification type
     */
    protected function generateTestData(string $type): array
    {
        $baseData = [
            'test_mode' => true,
            'timestamp' => now()->toISOString(),
        ];

        return match ($type) {
            'pendapatan', 'pengeluaran' => array_merge($baseData, [
                'amount' => 150000,
                'description' => 'Test transaction',
                'date' => now()->format('d/m/Y'),
                'shift' => 'Pagi',
                'petugas' => 'Test User',
            ]),
            'pasien' => array_merge($baseData, [
                'patient_name' => 'Test Patient',
                'patient_id' => 999,
                'dokter_name' => 'Dr. Test',
            ]),
            'tindakan_baru' => array_merge($baseData, [
                'patient_name' => 'Test Patient',
                'procedure' => 'Test Procedure',
                'dokter_name' => 'Dr. Test',
                'tarif' => 200000,
                'tanggal_tindakan' => now()->format('d/m/Y H:i'),
            ]),
            'validasi_disetujui' => array_merge($baseData, [
                'type' => 'Test Validation',
                'amount' => 150000,
                'description' => 'Test validation approval',
                'validator_name' => 'Test Validator',
            ]),
            'jaspel_dokter_ready' => array_merge($baseData, [
                'dokter_name' => 'Dr. Test',
                'total_jaspel' => 500000,
                'total_procedures' => 5,
                'period' => now()->format('F Y'),
            ]),
            'emergency_alert' => array_merge($baseData, [
                'level' => 'HIGH',
                'location' => 'Test Location',
                'description' => 'Test emergency alert',
                'reporter' => 'Test System',
            ]),
            default => array_merge($baseData, [
                'description' => "Test notification for {$type}",
                'test_data' => 'This is test data',
            ]),
        };
    }

    /**
     * Display notification results
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📤 Notification Results:');
        
        if (empty($results)) {
            $this->warn('No notifications were sent');
            return;
        }

        foreach ($results as $target => $success) {
            $status = $success ? '✅ Success' : '❌ Failed';
            $this->line("  {$target}: {$status}");
        }
    }
}