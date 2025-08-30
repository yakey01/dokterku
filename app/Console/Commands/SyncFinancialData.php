<?php

namespace App\Console\Commands;

use App\Services\FinancialSyncService;
use Illuminate\Console\Command;

class SyncFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:sync 
                            {--status : Show sync status only}
                            {--emergency : Force emergency sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync approved daily financial records to master tables for manager dashboard';

    /**
     * Execute the console command.
     */
    public function handle(FinancialSyncService $syncService): int
    {
        $this->info('🏥 Healthcare Financial Data Sync Tool');
        $this->info('====================================');
        $this->newLine();

        // Show status only
        if ($this->option('status')) {
            return $this->showSyncStatus($syncService);
        }

        // Emergency sync
        if ($this->option('emergency')) {
            return $this->performEmergencySync($syncService);
        }

        // Regular sync
        return $this->performRegularSync($syncService);
    }

    /**
     * Show current sync status
     */
    private function showSyncStatus(FinancialSyncService $syncService): int
    {
        $status = $syncService->getSyncStatus();
        
        if (!$status['success']) {
            $this->error('❌ Failed to get sync status: ' . $status['message']);
            return 1;
        }
        
        $data = $status['data'];
        
        $this->info('📊 Current Sync Status:');
        $this->newLine();
        
        // Revenue status
        $this->line('💰 <fg=green>REVENUE SYNC:</fg=green>');
        $this->line("   Approved Daily: {$data['revenue']['approved_daily']}");
        $this->line("   Synced Master: {$data['revenue']['synced_master']}");
        $this->line("   Pending Sync: {$data['revenue']['pending_sync']}");
        $this->line("   Sync Rate: <fg=yellow>{$data['revenue']['sync_percentage']}%</fg=yellow>");
        $this->newLine();
        
        // Expense status
        $this->line('💸 <fg=red>EXPENSE SYNC:</fg=red>');
        $this->line("   Approved Daily: {$data['expenses']['approved_daily']}");
        $this->line("   Synced Master: {$data['expenses']['synced_master']}");
        $this->line("   Pending Sync: {$data['expenses']['pending_sync']}");
        $this->line("   Sync Rate: <fg=yellow>{$data['expenses']['sync_percentage']}%</fg=yellow>");
        $this->newLine();
        
        // Overall health
        $healthColor = match($data['sync_health']) {
            'excellent' => 'green',
            'good' => 'cyan',
            'fair' => 'yellow',
            'poor' => 'red'
        };
        
        $this->line("🏥 <fg={$healthColor}>Overall Sync Health: " . strtoupper($data['sync_health']) . "</fg={$healthColor}>");
        
        return 0;
    }

    /**
     * Perform emergency sync
     */
    private function performEmergencySync(FinancialSyncService $syncService): int
    {
        $this->warn('🚨 EMERGENCY SYNC MODE');
        $this->warn('This will force sync all approved records immediately');
        $this->newLine();
        
        if (!$this->confirm('Are you sure you want to proceed?')) {
            $this->info('Emergency sync cancelled');
            return 0;
        }
        
        $this->info('🔄 Starting emergency sync...');
        
        $result = $syncService->emergencySync();
        
        if ($result['success']) {
            $this->info('✅ Emergency sync completed successfully');
            $this->displaySyncResults($result['data']);
        } else {
            $this->error('❌ Emergency sync failed: ' . $result['message']);
            return 1;
        }
        
        return 0;
    }

    /**
     * Perform regular sync
     */
    private function performRegularSync(FinancialSyncService $syncService): int
    {
        $this->info('🔄 Starting financial data synchronization...');
        $this->newLine();
        
        // Show current status first
        $this->showSyncStatus($syncService);
        $this->newLine();
        
        if (!$this->confirm('Proceed with synchronization?')) {
            $this->info('Sync cancelled');
            return 0;
        }
        
        $result = $syncService->syncAllApprovedRecords();
        
        if ($result['success']) {
            $this->info('✅ Sync completed successfully');
            $this->displaySyncResults($result['data']);
            
            // Clear manager dashboard cache
            $this->info('🗑️ Clearing manager dashboard cache...');
            cache()->forget('manajer_today_stats_' . now()->format('Y-m-d'));
            $this->info('✅ Cache cleared');
            
        } else {
            $this->error('❌ Sync failed: ' . $result['message']);
            return 1;
        }
        
        return 0;
    }

    /**
     * Display sync results in formatted table
     */
    private function displaySyncResults(array $data): void
    {
        $this->newLine();
        $this->info('📋 Sync Results:');
        
        $this->table(
            ['Type', 'Synced', 'Skipped', 'Errors', 'Total'],
            [
                [
                    'Revenue',
                    $data['revenue']['synced'],
                    $data['revenue']['skipped'],
                    $data['revenue']['errors'],
                    $data['revenue']['total_processed']
                ],
                [
                    'Expenses', 
                    $data['expenses']['synced'],
                    $data['expenses']['skipped'],
                    $data['expenses']['errors'],
                    $data['expenses']['total_processed']
                ]
            ]
        );
        
        $totalSynced = $data['revenue']['synced'] + $data['expenses']['synced'];
        $totalErrors = $data['revenue']['errors'] + $data['expenses']['errors'];
        
        if ($totalSynced > 0) {
            $this->info("✅ Successfully synced {$totalSynced} financial records");
        }
        
        if ($totalErrors > 0) {
            $this->warn("⚠️ {$totalErrors} records failed to sync - check logs for details");
        }
        
        $this->newLine();
        $this->info('💡 Manager dashboard should now show updated financial data');
        $this->info('🔄 Test at: /manajer/dashboard');
    }
}