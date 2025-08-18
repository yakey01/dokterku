<?php

namespace App\Console\Commands;

use App\Models\Jaspel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Cleanup Dummy JASPEL Data Command
 * 
 * Safely removes dummy/test JASPEL data while preserving legitimate records.
 * Provides backup functionality and comprehensive validation.
 */
class CleanupDummyJaspelData extends Command
{
    protected $signature = 'jaspel:cleanup-dummy 
                           {--backup : Create backup before deletion}
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Skip confirmation prompts}
                           {--target= : Specific target (konsultasi_khusus, all, pattern)}';

    protected $description = 'Clean up dummy/test JASPEL data with backup and validation';

    public function handle()
    {
        $this->info('🧹 JASPEL Dummy Data Cleanup Tool');
        $this->info('==================================');
        
        $isDryRun = $this->option('dry-run');
        $createBackup = $this->option('backup');
        $skipConfirmation = $this->option('force');
        $target = $this->option('target') ?? 'konsultasi_khusus';

        try {
            // Step 1: Identify dummy data
            $this->info("\n📊 Step 1: Identifying dummy data...");
            $dummyRecords = $this->identifyDummyData($target);
            
            if ($dummyRecords->isEmpty()) {
                $this->info('✅ No dummy data found to clean up.');
                return 0;
            }

            $this->displayDummyDataSummary($dummyRecords);

            // Step 2: Create backup if requested
            if ($createBackup && !$isDryRun) {
                $this->info("\n💾 Step 2: Creating backup...");
                $backupPath = $this->createBackup($dummyRecords);
                $this->info("✅ Backup created: {$backupPath}");
            }

            // Step 3: Confirmation (unless force flag is used)
            if (!$skipConfirmation && !$isDryRun) {
                $this->info("\n⚠️  Step 3: Confirmation required");
                if (!$this->confirm("Delete {$dummyRecords->count()} dummy JASPEL records?")) {
                    $this->info('❌ Operation cancelled by user.');
                    return 1;
                }
            }

            // Step 4: Execute cleanup
            $this->info("\n🗑️  Step 4: Executing cleanup...");
            
            if ($isDryRun) {
                $this->info("✅ DRY RUN: Would delete {$dummyRecords->count()} records");
                $this->displayDetailedRecords($dummyRecords);
            } else {
                $deletedCount = $this->executeDeletion($dummyRecords);
                $this->info("✅ Successfully deleted {$deletedCount} dummy records");
            }

            // Step 5: Verification
            $this->info("\n🔍 Step 5: Post-cleanup verification...");
            $this->verifyCleanup($target);

            $this->info("\n🎉 Cleanup completed successfully!");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Cleanup failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Identify dummy data based on patterns and heuristics
     */
    private function identifyDummyData(string $target)
    {
        $query = Jaspel::query();

        switch ($target) {
            case 'konsultasi_khusus':
                // Identify konsultasi_khusus dummy records
                $query->where('jenis_jaspel', 'konsultasi_khusus')
                      ->whereNull('tindakan_id') // No linked procedure
                      ->where('created_at', '>=', '2025-08-06 07:00:00')
                      ->where('created_at', '<=', '2025-08-06 08:00:00') // Seeder timeframe
                      ->where('user_id', 13) // Yaya test user
                      ->where('input_by', 13); // Self-input indicator
                break;

            case 'all':
                // Identify all potential dummy data
                $query->where(function($q) {
                    $q->where(function($subq) {
                        // Pattern 1: Konsultasi khusus without procedures
                        $subq->where('jenis_jaspel', 'konsultasi_khusus')
                            ->whereNull('tindakan_id')
                            ->where('created_at', '>=', '2025-08-06 07:00:00')
                            ->where('created_at', '<=', '2025-08-06 08:00:00');
                    })->orWhere(function($subq) {
                        // Pattern 2: Round amounts that look generated
                        $subq->whereRaw('nominal % 1000 = 0')
                            ->where('nominal', '>=', 100000)
                            ->where('nominal', '<=', 500000)
                            ->whereNull('tindakan_id');
                    });
                });
                break;

            case 'pattern':
                // Advanced pattern detection
                $query->where(function($q) {
                    $q->whereRaw('ABS(nominal - ROUND(nominal/1000)*1000) < 100') // Near round thousands
                      ->orWhere(function($subq) {
                          $subq->whereNull('tindakan_id')
                               ->whereNull('shift_id')
                               ->where('user_id', 13);
                      });
                });
                break;
        }

        return $query->with(['tindakan.jenisTindakan', 'user', 'validasiBy'])
                     ->orderBy('created_at')
                     ->get();
    }

    /**
     * Display summary of dummy data found
     */
    private function displayDummyDataSummary($dummyRecords)
    {
        $this->info("\n📋 Dummy Data Summary:");
        $this->info("======================");
        $this->info("Total records found: " . $dummyRecords->count());
        
        // Group by type
        $byType = $dummyRecords->groupBy('jenis_jaspel');
        foreach ($byType as $type => $records) {
            $totalAmount = $records->sum('nominal');
            $this->info("• {$type}: {$records->count()} records (Rp " . number_format($totalAmount) . ")");
        }

        // Group by date
        $byDate = $dummyRecords->groupBy(function($record) {
            return $record->created_at->format('Y-m-d');
        });
        
        $this->info("\nBy creation date:");
        foreach ($byDate as $date => $records) {
            $this->info("• {$date}: {$records->count()} records");
        }

        // Show first few records as examples
        $this->info("\nExample records:");
        foreach ($dummyRecords->take(5) as $record) {
            $this->info("• ID {$record->id}: {$record->jenis_jaspel} - Rp " . number_format($record->nominal) . 
                       " (Created: {$record->created_at})");
        }
    }

    /**
     * Create backup of records before deletion
     */
    private function createBackup($dummyRecords): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupData = [
            'backup_info' => [
                'created_at' => Carbon::now()->toISOString(),
                'record_count' => $dummyRecords->count(),
                'total_amount' => $dummyRecords->sum('nominal'),
                'cleanup_reason' => 'Dummy data cleanup',
                'command_executed_by' => 'jaspel:cleanup-dummy'
            ],
            'records' => $dummyRecords->toArray()
        ];

        $backupPath = "backups/jaspel_dummy_cleanup_{$timestamp}.json";
        Storage::disk('local')->put($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));
        
        return storage_path("app/{$backupPath}");
    }

    /**
     * Display detailed record information
     */
    private function displayDetailedRecords($dummyRecords)
    {
        $this->info("\n📄 Detailed Records to be deleted:");
        $this->info("==================================");

        $headers = ['ID', 'Type', 'Amount', 'Date', 'Tindakan ID', 'Status', 'Created'];
        $rows = [];

        foreach ($dummyRecords as $record) {
            $rows[] = [
                $record->id,
                $record->jenis_jaspel,
                'Rp ' . number_format($record->nominal),
                $record->tanggal,
                $record->tindakan_id ?? 'NULL',
                $record->status_validasi,
                $record->created_at->format('Y-m-d H:i')
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Execute the actual deletion
     */
    private function executeDeletion($dummyRecords): int
    {
        $deletedCount = 0;
        
        DB::transaction(function() use ($dummyRecords, &$deletedCount) {
            foreach ($dummyRecords as $record) {
                // Additional safety check before deletion
                if ($this->isSafeToDelete($record)) {
                    $record->delete();
                    $deletedCount++;
                    $this->line("✓ Deleted JASPEL ID {$record->id} ({$record->jenis_jaspel})");
                } else {
                    $this->warn("⚠ Skipped JASPEL ID {$record->id} - safety check failed");
                }
            }
        });

        return $deletedCount;
    }

    /**
     * Safety check before deleting a record
     */
    private function isSafeToDelete($record): bool
    {
        // Safety rules:
        // 1. Must not have critical financial dependencies
        // 2. Must not be referenced by other systems
        // 3. Must match dummy data patterns
        
        // Rule 1: Check for financial dependencies
        if ($record->status_validasi === 'disetujui' && $record->nominal > 1000000) {
            return false; // Large approved amounts need manual review
        }

        // Rule 2: Check for system references
        // (Add checks for related tables if needed)

        // Rule 3: Must match dummy patterns
        $isDummyPattern = (
            $record->jenis_jaspel === 'konsultasi_khusus' &&
            is_null($record->tindakan_id) &&
            $record->user_id == 13 &&
            $record->created_at >= '2025-08-06 07:00:00' &&
            $record->created_at <= '2025-08-06 08:00:00'
        );

        return $isDummyPattern;
    }

    /**
     * Verify cleanup was successful
     */
    private function verifyCleanup(string $target)
    {
        $remainingDummy = $this->identifyDummyData($target);
        
        if ($remainingDummy->isEmpty()) {
            $this->info("✅ Verification passed: No dummy data remaining for target '{$target}'");
        } else {
            $this->warn("⚠ Verification warning: {$remainingDummy->count()} potential dummy records still exist");
            
            // Show remaining records
            foreach ($remainingDummy->take(3) as $record) {
                $this->warn("  • ID {$record->id}: {$record->jenis_jaspel} - Rp " . number_format($record->nominal));
            }
        }

        // Overall system health check
        $totalJaspel = Jaspel::count();
        $totalValidated = Jaspel::where('status_validasi', 'disetujui')->count();
        
        $this->info("\n📊 System Status:");
        $this->info("• Total JASPEL records: {$totalJaspel}");
        $this->info("• Validated records: {$totalValidated}");
        $this->info("• Validation rate: " . round(($totalValidated / max($totalJaspel, 1)) * 100, 2) . "%");
    }
}