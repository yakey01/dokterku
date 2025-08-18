<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ValidatedJaspelCalculationService;
use App\Services\EnhancedJaspelService;
use App\Models\User;
use App\Models\Jaspel;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;

class TestValidatedJaspelSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jaspel:test-validated-system {user_id?} {--month=} {--year=} {--create-test-data}';

    /**
     * The console command description.
     */
    protected $description = 'Test the validated JASPEL calculation system and ensure only bendahara-approved amounts are shown';

    private ValidatedJaspelCalculationService $validatedJaspelService;
    private EnhancedJaspelService $enhancedJaspelService;

    public function __construct(
        ValidatedJaspelCalculationService $validatedJaspelService,
        EnhancedJaspelService $enhancedJaspelService
    ) {
        parent::__construct();
        $this->validatedJaspelService = $validatedJaspelService;
        $this->enhancedJaspelService = $enhancedJaspelService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Validated JASPEL System');
        $this->info('=====================================');

        $userId = $this->argument('user_id');
        $month = $this->option('month') ?: now()->month;
        $year = $this->option('year') ?: now()->year;

        if (!$userId) {
            // Get a user with JASPEL data
            $user = User::whereHas('jaspel')->first();
            if (!$user) {
                $this->error('No users found with JASPEL data. Use --create-test-data to create sample data.');
                return 1;
            }
            $userId = $user->id;
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        }

        $this->info("Testing for User: {$user->name} (ID: {$user->id})");
        $this->info("Period: {$year}-{$month}");
        $this->newLine();

        if ($this->option('create-test-data')) {
            $this->createTestData($user, $month, $year);
        }

        // Test 1: Validation Status Check
        $this->testValidationStatus($user, $month, $year);

        // Test 2: Validated Data Retrieval
        $this->testValidatedDataRetrieval($user, $month, $year);

        // Test 3: Gaming UI Safety Check
        $this->testGamingUISafety($user, $month, $year);

        // Test 4: Pending Validation Summary
        $this->testPendingValidationSummary($user, $month, $year);

        // Test 5: Financial Accuracy Guarantee
        $this->testFinancialAccuracyGuarantee($user, $month, $year);

        $this->newLine();
        $this->info('✅ Validated JASPEL System Test Completed');

        return 0;
    }

    private function testValidationStatus(User $user, $month, $year)
    {
        $this->info('🔍 Test 1: Validation Status Check');
        $this->line('--------------------------------------');

        $validationStatus = $this->validatedJaspelService->getValidationStatus($user, $month, $year);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Items', $validationStatus['total_items']],
                ['Validated Items', $validationStatus['validated_items']],
                ['Pending Items', $validationStatus['pending_items']],
                ['Rejected Items', $validationStatus['rejected_items']],
                ['Validation Rate', $validationStatus['validation_rate'] . '%'],
                ['Financial Accuracy', $validationStatus['financial_accuracy']],
                ['Bendahara Status', $validationStatus['bendahara_status']],
                ['Gaming UI Safe', $validationStatus['gaming_ui_safe'] ? 'YES' : 'NO']
            ]
        );

        if ($validationStatus['validation_rate'] < 100) {
            $this->warn("⚠️  Validation rate is {$validationStatus['validation_rate']}% - some amounts are not validated");
        } else {
            $this->info('✅ All JASPEL amounts are validated by bendahara');
        }

        $this->newLine();
    }

    private function testValidatedDataRetrieval(User $user, $month, $year)
    {
        $this->info('📊 Test 2: Validated Data Retrieval');
        $this->line('--------------------------------------');

        $validatedData = $this->validatedJaspelService->getValidatedJaspelData($user, $month, $year);

        $this->info("Total validated items: " . count($validatedData['jaspel_items']));
        $this->info("Total validated amount: Rp " . number_format($validatedData['summary']['total']));
        
        $this->table(
            ['Source', 'Count'],
            [
                ['Direct JASPEL', $validatedData['counts']['direct_jaspel']],
                ['Procedure JASPEL', $validatedData['counts']['procedure_jaspel']],
                ['Patient Count JASPEL', $validatedData['counts']['patient_count_jaspel']],
                ['Total', $validatedData['counts']['total_validated']]
            ]
        );

        // Verify all items are validated
        $allValidated = true;
        foreach ($validatedData['jaspel_items'] as $item) {
            if (!isset($item['validation_guaranteed']) || !$item['validation_guaranteed']) {
                $allValidated = false;
                break;
            }
        }

        if ($allValidated) {
            $this->info('✅ All retrieved items have validation guarantee');
        } else {
            $this->error('❌ Some items lack validation guarantee');
        }

        $this->newLine();
    }

    private function testGamingUISafety(User $user, $month, $year)
    {
        $this->info('🎮 Test 3: Gaming UI Safety Check');
        $this->line('--------------------------------------');

        $isGamingUISafe = $this->enhancedJaspelService->isGamingUISafe($user, $month, $year);

        if ($isGamingUISafe) {
            $this->info('✅ Gaming UI is SAFE - only validated amounts will be displayed');
        } else {
            $this->warn('⚠️  Gaming UI is NOT SAFE - unvalidated amounts detected');
        }

        // Test the comprehensive service comparison
        $comprehensiveData = $this->enhancedJaspelService->getComprehensiveJaspelData($user, $month, $year);
        $validatedOnlyData = $this->enhancedJaspelService->getValidatedJaspelDataForGaming($user, $month, $year);

        $this->table(
            ['Data Type', 'Total Items', 'Total Amount'],
            [
                ['Comprehensive (All)', count($comprehensiveData['jaspel_items']), 'Rp ' . number_format($comprehensiveData['summary']['total'])],
                ['Validated Only', count($validatedOnlyData['jaspel_items']), 'Rp ' . number_format($validatedOnlyData['summary']['total'])]
            ]
        );

        $discrepancy = $comprehensiveData['summary']['total'] - $validatedOnlyData['summary']['total'];
        if ($discrepancy > 0) {
            $this->warn("💰 Discrepancy detected: Rp " . number_format($discrepancy) . " in unvalidated amounts");
            $this->warn("This amount represents Rp4,637,238 type issues that would appear in gaming UI without validation");
        } else {
            $this->info('✅ No discrepancy - all amounts are validated');
        }

        $this->newLine();
    }

    private function testPendingValidationSummary(User $user, $month, $year)
    {
        $this->info('⏳ Test 4: Pending Validation Summary');
        $this->line('--------------------------------------');

        $pendingSummary = $this->validatedJaspelService->getPendingValidationSummary($user, $month, $year);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Pending Amount', 'Rp ' . number_format($pendingSummary['pending_amount'])],
                ['Pending Count', $pendingSummary['pending_count']],
                ['Status', $pendingSummary['status']],
                ['Can Show in Gaming', $pendingSummary['can_show_in_gaming'] ? 'YES' : 'NO']
            ]
        );

        $this->line($pendingSummary['message']);

        $this->newLine();
    }

    private function testFinancialAccuracyGuarantee(User $user, $month, $year)
    {
        $this->info('💯 Test 5: Financial Accuracy Guarantee');
        $this->line('--------------------------------------');

        // Query all JASPEL data and check validation status
        $totalJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->count();

        $validatedJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'disetujui')
            ->count();

        $pendingJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'pending')
            ->count();

        $validationRate = $totalJaspel > 0 ? ($validatedJaspel / $totalJaspel) * 100 : 100;

        $this->table(
            ['Financial Metric', 'Value', 'Status'],
            [
                ['Total JASPEL Records', $totalJaspel, $totalJaspel > 0 ? '✅' : '⚠️'],
                ['Validated Records', $validatedJaspel, $validatedJaspel == $totalJaspel ? '✅' : '⚠️'],
                ['Pending Records', $pendingJaspel, $pendingJaspel == 0 ? '✅' : '⚠️'],
                ['Validation Rate', round($validationRate, 2) . '%', $validationRate == 100 ? '✅' : '⚠️'],
                ['Financial Accuracy', $validationRate == 100 ? 'GUARANTEED' : 'AT RISK', $validationRate == 100 ? '✅' : '❌']
            ]
        );

        if ($validationRate == 100) {
            $this->info('🎯 FINANCIAL ACCURACY GUARANTEED: All amounts are bendahara-validated');
            $this->info('🎮 Gaming UI can safely display all amounts');
        } else {
            $this->error('🚨 FINANCIAL ACCURACY AT RISK: Unvalidated amounts detected');
            $this->error('Gaming UI should not display unvalidated amounts');
        }

        $this->newLine();
    }

    private function createTestData(User $user, $month, $year)
    {
        $this->info('🧪 Creating test data...');

        // Create a validated JASPEL record
        Jaspel::create([
            'user_id' => $user->id,
            'jenis_jaspel' => 'paramedis',
            'nominal' => 500000,
            'tanggal' => now()->setMonth($month)->setYear($year)->startOfMonth(),
            'status_validasi' => 'disetujui',
            'validasi_by' => 1,
            'validasi_at' => now(),
            'input_by' => 1,
            'catatan_validasi' => 'Test validated JASPEL'
        ]);

        // Create a pending JASPEL record
        Jaspel::create([
            'user_id' => $user->id,
            'jenis_jaspel' => 'paramedis',
            'nominal' => 300000,
            'tanggal' => now()->setMonth($month)->setYear($year)->startOfMonth()->addDays(1),
            'status_validasi' => 'pending',
            'input_by' => 1,
            'catatan_validasi' => 'Test pending JASPEL'
        ]);

        $this->info('✅ Test data created');
        $this->newLine();
    }
}