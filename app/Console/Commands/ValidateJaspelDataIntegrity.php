<?php

namespace App\Console\Commands;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\JenisTindakan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * JASPEL Data Integrity Validator
 * 
 * Comprehensive validation of JASPEL system data integrity.
 * Detects inconsistencies, orphaned records, and business rule violations.
 */
class ValidateJaspelDataIntegrity extends Command
{
    protected $signature = 'jaspel:validate-integrity 
                           {--report : Generate detailed integrity report}
                           {--fix : Automatically fix detected issues where safe}
                           {--export= : Export results to file (json, csv, html)}
                           {--focus= : Focus on specific area (amounts, relationships, business-rules)}';

    protected $description = 'Validate JASPEL system data integrity and detect issues';

    private $issues = [];
    private $fixedCount = 0;
    private $warningCount = 0;
    private $errorCount = 0;

    public function handle()
    {
        $this->info('🔍 JASPEL Data Integrity Validator');
        $this->info('=================================');
        
        $generateReport = $this->option('report');
        $autoFix = $this->option('fix');
        $exportFormat = $this->option('export');
        $focus = $this->option('focus') ?? 'all';

        try {
            // Initialize validation
            $this->issues = [];
            $this->fixedCount = 0;
            $this->warningCount = 0;
            $this->errorCount = 0;

            $this->info("\n🚀 Starting integrity validation...");
            $startTime = microtime(true);

            // Run validation tests based on focus
            if ($focus === 'all' || $focus === 'relationships') {
                $this->validateRelationshipIntegrity($autoFix);
            }

            if ($focus === 'all' || $focus === 'amounts') {
                $this->validateAmountConsistency($autoFix);
            }

            if ($focus === 'all' || $focus === 'business-rules') {
                $this->validateBusinessRules($autoFix);
            }

            if ($focus === 'all') {
                $this->validateDataQuality($autoFix);
                $this->detectAnomalies();
                $this->validateUserPermissions();
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Generate summary
            $this->displaySummary($executionTime);

            // Generate report if requested
            if ($generateReport) {
                $this->generateIntegrityReport($exportFormat);
            }

            // Return appropriate exit code
            return $this->errorCount > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("❌ Validation failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Validate relationship integrity between tables
     */
    private function validateRelationshipIntegrity(bool $autoFix)
    {
        $this->info("\n🔗 Validating relationship integrity...");

        // Check 1: Orphaned JASPEL records (invalid user_id)
        $orphanedUsers = Jaspel::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('users')
                  ->whereRaw('users.id = jaspel.user_id');
        })->get();

        if ($orphanedUsers->count() > 0) {
            $this->addIssue('ERROR', 'Orphaned JASPEL records', 
                "Found {$orphanedUsers->count()} JASPEL records with invalid user_id", 
                $orphanedUsers->pluck('id')->toArray());
        }

        // Check 2: Invalid tindakan_id references
        $orphanedTindakan = Jaspel::whereNotNull('tindakan_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('tindakan')
                      ->whereRaw('tindakan.id = jaspel.tindakan_id');
            })->get();

        if ($orphanedTindakan->count() > 0) {
            $this->addIssue('ERROR', 'Invalid tindakan references', 
                "Found {$orphanedTindakan->count()} JASPEL records with invalid tindakan_id", 
                $orphanedTindakan->pluck('id')->toArray());

            if ($autoFix) {
                $this->fixOrphanedTindakanReferences($orphanedTindakan);
            }
        }

        // Check 3: Missing validasi_by references
        $invalidValidators = Jaspel::whereNotNull('validasi_by')
            ->where('validasi_by', '>', 0)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('users')
                      ->whereRaw('users.id = jaspel.validasi_by');
            })->get();

        if ($invalidValidators->count() > 0) {
            $this->addIssue('WARNING', 'Invalid validator references', 
                "Found {$invalidValidators->count()} JASPEL records with invalid validasi_by", 
                $invalidValidators->pluck('id')->toArray());
        }
    }

    /**
     * Validate amount consistency and calculations
     */
    private function validateAmountConsistency(bool $autoFix)
    {
        $this->info("\n💰 Validating amount consistency...");

        // Check 1: Zero or negative amounts
        $invalidAmounts = Jaspel::where(function($query) {
            $query->where('nominal', '<=', 0)
                  ->orWhere('total_jaspel', '<=', 0);
        })->get();

        if ($invalidAmounts->count() > 0) {
            $this->addIssue('WARNING', 'Invalid amounts', 
                "Found {$invalidAmounts->count()} JASPEL records with zero or negative amounts", 
                $invalidAmounts->pluck('id')->toArray());
        }

        // Check 2: Inconsistent nominal vs total_jaspel
        $inconsistentTotals = Jaspel::whereRaw('ABS(nominal - total_jaspel) > 1')->get();

        if ($inconsistentTotals->count() > 0) {
            $this->addIssue('WARNING', 'Inconsistent totals', 
                "Found {$inconsistentTotals->count()} JASPEL records where nominal ≠ total_jaspel", 
                $inconsistentTotals->pluck('id')->toArray());

            if ($autoFix) {
                $this->fixInconsistentTotals($inconsistentTotals);
            }
        }

        // Check 3: Suspiciously high amounts
        $suspiciousAmounts = Jaspel::where('nominal', '>', 10000000)->get(); // > 10 million

        if ($suspiciousAmounts->count() > 0) {
            $this->addIssue('WARNING', 'Suspiciously high amounts', 
                "Found {$suspiciousAmounts->count()} JASPEL records with amounts > Rp 10,000,000", 
                $suspiciousAmounts->pluck('id')->toArray());
        }

        // Check 4: Round number patterns (potential dummy data)
        $roundNumbers = Jaspel::whereRaw('nominal % 10000 = 0')
            ->where('nominal', '>=', 100000)
            ->whereNull('tindakan_id')
            ->get();

        if ($roundNumbers->count() > 10) { // Only flag if many round numbers
            $this->addIssue('INFO', 'Round number pattern detected', 
                "Found {$roundNumbers->count()} JASPEL records with round amounts (potential test data)", 
                $roundNumbers->pluck('id')->toArray());
        }
    }

    /**
     * Validate business rules compliance
     */
    private function validateBusinessRules(bool $autoFix)
    {
        $this->info("\n📋 Validating business rules...");

        // Rule 1: Status validation consistency
        $invalidStatus = Jaspel::whereNotIn('status_validasi', ['pending', 'disetujui', 'ditolak'])->get();

        if ($invalidStatus->count() > 0) {
            $this->addIssue('ERROR', 'Invalid status values', 
                "Found {$invalidStatus->count()} JASPEL records with invalid status_validasi", 
                $invalidStatus->pluck('id')->toArray());
        }

        // Rule 2: Approved records must have validation date
        $approvedWithoutDate = Jaspel::where('status_validasi', 'disetujui')
            ->whereNull('validasi_at')
            ->get();

        if ($approvedWithoutDate->count() > 0) {
            $this->addIssue('WARNING', 'Approved without validation date', 
                "Found {$approvedWithoutDate->count()} approved JASPEL records without validasi_at", 
                $approvedWithoutDate->pluck('id')->toArray());

            if ($autoFix) {
                $this->fixMissingValidationDates($approvedWithoutDate);
            }
        }

        // Rule 3: Future dated entries
        $futureDated = Jaspel::where('tanggal', '>', Carbon::now()->addDays(1))->get();

        if ($futureDated->count() > 0) {
            $this->addIssue('WARNING', 'Future dated entries', 
                "Found {$futureDated->count()} JASPEL records dated in the future", 
                $futureDated->pluck('id')->toArray());
        }

        // Rule 4: Very old pending entries
        $oldPending = Jaspel::where('status_validasi', 'pending')
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->get();

        if ($oldPending->count() > 0) {
            $this->addIssue('INFO', 'Old pending entries', 
                "Found {$oldPending->count()} JASPEL records pending validation for >30 days", 
                $oldPending->pluck('id')->toArray());
        }
    }

    /**
     * Validate data quality
     */
    private function validateDataQuality(bool $autoFix)
    {
        $this->info("\n📊 Validating data quality...");

        // Check 1: Missing required fields
        $missingJenisJaspel = Jaspel::whereNull('jenis_jaspel')->orWhere('jenis_jaspel', '')->get();

        if ($missingJenisJaspel->count() > 0) {
            $this->addIssue('ERROR', 'Missing jenis_jaspel', 
                "Found {$missingJenisJaspel->count()} JASPEL records without jenis_jaspel", 
                $missingJenisJaspel->pluck('id')->toArray());
        }

        // Check 2: Duplicate entries (same user, date, amount, type)
        $duplicates = DB::table('jaspel')
            ->select('user_id', 'tanggal', 'jenis_jaspel', 'nominal', DB::raw('count(*) as count'))
            ->groupBy('user_id', 'tanggal', 'jenis_jaspel', 'nominal')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->addIssue('WARNING', 'Potential duplicates', 
                "Found {$duplicates->count()} sets of potential duplicate JASPEL records");
        }

        // Check 3: Unrealistic date ranges
        $tooOld = Jaspel::where('tanggal', '<', '2020-01-01')->get();
        $tooNew = Jaspel::where('tanggal', '>', Carbon::now()->addYear())->get();

        if ($tooOld->count() > 0 || $tooNew->count() > 0) {
            $this->addIssue('WARNING', 'Unrealistic dates', 
                "Found " . ($tooOld->count() + $tooNew->count()) . " JASPEL records with unrealistic dates");
        }
    }

    /**
     * Detect anomalies and patterns
     */
    private function detectAnomalies()
    {
        $this->info("\n🔍 Detecting anomalies...");

        // Anomaly 1: Users with excessive daily JASPEL amounts
        $excessiveDaily = DB::table('jaspel')
            ->select('user_id', 'tanggal', DB::raw('SUM(nominal) as daily_total'))
            ->groupBy('user_id', 'tanggal')
            ->having('daily_total', '>', 5000000) // > 5 million per day
            ->get();

        if ($excessiveDaily->count() > 0) {
            $this->addIssue('INFO', 'Excessive daily amounts', 
                "Found {$excessiveDaily->count()} user-days with JASPEL > Rp 5,000,000");
        }

        // Anomaly 2: Unusual time patterns
        $lateNightEntries = Jaspel::whereTime('created_at', '>', '22:00:00')
            ->orWhereTime('created_at', '<', '06:00:00')
            ->get();

        if ($lateNightEntries->count() > 50) { // Many late night entries might be unusual
            $this->addIssue('INFO', 'Late night entry pattern', 
                "Found {$lateNightEntries->count()} JASPEL records created during late night hours");
        }

        // Anomaly 3: Single-user domination
        $userStats = DB::table('jaspel')
            ->select('user_id', DB::raw('count(*) as count'), DB::raw('sum(nominal) as total'))
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->first();

        $totalRecords = Jaspel::count();
        if ($userStats && $totalRecords > 0 && ($userStats->count / $totalRecords) > 0.8) {
            $this->addIssue('INFO', 'Single user dominance', 
                "User {$userStats->user_id} represents " . 
                round(($userStats->count / $totalRecords) * 100, 1) . "% of all JASPEL records");
        }
    }

    /**
     * Validate user permissions and access patterns
     */
    private function validateUserPermissions()
    {
        $this->info("\n👤 Validating user permissions...");

        // Check 1: Self-validation (users validating their own JASPEL)
        $selfValidated = Jaspel::whereRaw('user_id = validasi_by')
            ->where('status_validasi', 'disetujui')
            ->get();

        if ($selfValidated->count() > 0) {
            $this->addIssue('WARNING', 'Self-validated entries', 
                "Found {$selfValidated->count()} JASPEL records where user validated their own entry", 
                $selfValidated->pluck('id')->toArray());
        }

        // Check 2: Non-existent validators
        $unknownValidators = Jaspel::whereNotNull('validasi_by')
            ->where('validasi_by', '>', 0)
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('users')
                      ->whereRaw('users.id = jaspel.validasi_by');
            })->get();

        if ($unknownValidators->count() > 0) {
            $this->addIssue('ERROR', 'Unknown validators', 
                "Found {$unknownValidators->count()} JASPEL records validated by non-existent users", 
                $unknownValidators->pluck('id')->toArray());
        }
    }

    /**
     * Auto-fix methods
     */
    private function fixOrphanedTindakanReferences($orphanedTindakan)
    {
        $this->info("🔧 Fixing orphaned tindakan references...");
        
        foreach ($orphanedTindakan as $jaspel) {
            $jaspel->tindakan_id = null;
            $jaspel->save();
            $this->fixedCount++;
        }
        
        $this->info("✅ Fixed {$orphanedTindakan->count()} orphaned tindakan references");
    }

    private function fixInconsistentTotals($inconsistentTotals)
    {
        $this->info("🔧 Fixing inconsistent totals...");
        
        foreach ($inconsistentTotals as $jaspel) {
            $jaspel->total_jaspel = $jaspel->nominal;
            $jaspel->save();
            $this->fixedCount++;
        }
        
        $this->info("✅ Fixed {$inconsistentTotals->count()} inconsistent totals");
    }

    private function fixMissingValidationDates($approvedWithoutDate)
    {
        $this->info("🔧 Fixing missing validation dates...");
        
        foreach ($approvedWithoutDate as $jaspel) {
            // Set validation date to created_at or a reasonable default
            $jaspel->validasi_at = $jaspel->updated_at ?? $jaspel->created_at ?? now();
            $jaspel->save();
            $this->fixedCount++;
        }
        
        $this->info("✅ Fixed {$approvedWithoutDate->count()} missing validation dates");
    }

    /**
     * Helper methods
     */
    private function addIssue(string $level, string $title, string $description, array $affectedIds = [])
    {
        $this->issues[] = [
            'level' => $level,
            'title' => $title,
            'description' => $description,
            'affected_ids' => $affectedIds,
            'count' => count($affectedIds),
            'detected_at' => Carbon::now()->toISOString()
        ];

        // Update counters
        switch ($level) {
            case 'ERROR':
                $this->errorCount++;
                $this->error("❌ {$title}: {$description}");
                break;
            case 'WARNING':
                $this->warningCount++;
                $this->warn("⚠️ {$title}: {$description}");
                break;
            case 'INFO':
                $this->line("ℹ️ {$title}: {$description}");
                break;
        }
    }

    private function displaySummary($executionTime)
    {
        $this->info("\n📋 VALIDATION SUMMARY");
        $this->info("====================");
        $this->info("Execution time: {$executionTime}ms");
        $this->info("Total issues found: " . count($this->issues));
        $this->info("• Errors: {$this->errorCount}");
        $this->info("• Warnings: {$this->warningCount}");
        $this->info("• Info: " . (count($this->issues) - $this->errorCount - $this->warningCount));
        
        if ($this->fixedCount > 0) {
            $this->info("Issues auto-fixed: {$this->fixedCount}");
        }

        // Overall health score
        $totalRecords = Jaspel::count();
        $issueRecords = collect($this->issues)->sum('count');
        $healthScore = $totalRecords > 0 ? max(0, 100 - (($issueRecords / $totalRecords) * 100)) : 100;
        
        $this->info("System health score: " . round($healthScore, 1) . "%");
        
        if ($healthScore >= 95) {
            $this->info("🎉 Excellent data integrity!");
        } elseif ($healthScore >= 85) {
            $this->info("✅ Good data integrity");
        } elseif ($healthScore >= 70) {
            $this->warn("⚠️ Fair data integrity - attention needed");
        } else {
            $this->error("❌ Poor data integrity - immediate action required");
        }
    }

    private function generateIntegrityReport($exportFormat)
    {
        $this->info("\n📄 Generating integrity report...");
        
        $report = [
            'generated_at' => Carbon::now()->toISOString(),
            'summary' => [
                'total_issues' => count($this->issues),
                'errors' => $this->errorCount,
                'warnings' => $this->warningCount,
                'info' => count($this->issues) - $this->errorCount - $this->warningCount,
                'auto_fixed' => $this->fixedCount
            ],
            'issues' => $this->issues,
            'system_stats' => [
                'total_jaspel_records' => Jaspel::count(),
                'validated_records' => Jaspel::where('status_validasi', 'disetujui')->count(),
                'pending_records' => Jaspel::where('status_validasi', 'pending')->count(),
                'rejected_records' => Jaspel::where('status_validasi', 'ditolak')->count(),
            ]
        ];

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        
        if ($exportFormat === 'json' || !$exportFormat) {
            $filename = "jaspel_integrity_report_{$timestamp}.json";
            Storage::disk('local')->put("reports/{$filename}", json_encode($report, JSON_PRETTY_PRINT));
            $this->info("✅ Report saved: storage/app/reports/{$filename}");
        }

        // Could add CSV, HTML formats here if needed
    }
}