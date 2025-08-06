<?php

namespace Database\Seeders\Master;

use App\Services\Medical\Procedures\MedicalProcedureSeederService;
use App\Services\Medical\Procedures\ProcedureValidationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnhancedJenisTindakanSeeder extends Seeder
{
    use WithoutModelEvents;

    protected MedicalProcedureSeederService $seederService;
    protected ProcedureValidationService $validationService;

    public function __construct(
        MedicalProcedureSeederService $seederService,
        ProcedureValidationService $validationService
    ) {
        $this->seederService = $seederService;
        $this->validationService = $validationService;
    }

    /**
     * Run the enhanced medical procedures seeder.
     * 
     * This seeder provides comprehensive medical procedure data with:
     * - Intelligent fee calculation based on procedure complexity
     * - Proper jaspel percentage allocation
     * - Transaction safety with rollback capabilities
     * - Validation and error handling
     * - Production-ready implementation
     */
    public function run(): void
    {
        try {
            $this->command->info('🏥 Starting Enhanced Medical Procedures Seeder...');
            
            DB::beginTransaction();
            
            // Validate system requirements
            $this->validateSystemRequirements();
            
            // Get comprehensive procedure data
            $procedures = $this->seederService->getEnhancedProcedureData();
            
            $this->command->info("📋 Processing {$procedures->count()} medical procedures...");
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($procedures as $procedure) {
                try {
                    // Validate individual procedure
                    $validationResult = $this->validationService->validateProcedure($procedure);
                    
                    if (!$validationResult['isValid']) {
                        throw new \InvalidArgumentException(
                            "Validation failed for {$procedure['nama']}: " . 
                            implode(', ', $validationResult['errors'])
                        );
                    }
                    
                    // Seed the procedure
                    $result = $this->seederService->seedProcedure($procedure);
                    
                    if ($result['success']) {
                        $successCount++;
                        $this->command->line("  ✅ {$procedure['nama']} ({$procedure['kode']})");
                    } else {
                        throw new \RuntimeException($result['error'] ?? 'Unknown seeding error');
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->command->error("  ❌ {$procedure['nama']}: {$e->getMessage()}");
                    Log::error("Procedure seeding error: {$e->getMessage()}", [
                        'procedure' => $procedure,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Validate final state
            $this->validateSeederResults($successCount, $errorCount);
            
            DB::commit();
            
            $this->command->info("🎉 Enhanced Medical Procedures Seeder completed successfully!");
            $this->command->info("   ✅ Success: {$successCount} procedures");
            if ($errorCount > 0) {
                $this->command->warn("   ⚠️  Errors: {$errorCount} procedures (see logs)");
            }
            
            // Generate summary report
            $this->generateSeederReport($successCount, $errorCount);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Enhanced Medical Procedures Seeder failed: {$e->getMessage()}");
            Log::error("Enhanced seeder failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate system requirements before seeding
     */
    protected function validateSystemRequirements(): void
    {
        // Check database connection
        if (!DB::connection()->getPdo()) {
            throw new \RuntimeException('Database connection failed');
        }

        // Verify table exists
        if (!DB::getSchemaBuilder()->hasTable('jenis_tindakan')) {
            throw new \RuntimeException('jenis_tindakan table does not exist');
        }

        // Validate required columns
        $requiredColumns = [
            'kode', 'nama', 'deskripsi', 'tarif', 'jasa_dokter', 
            'jasa_paramedis', 'jasa_non_paramedis', 'persentase_jaspel', 
            'kategori', 'is_active'
        ];

        foreach ($requiredColumns as $column) {
            if (!DB::getSchemaBuilder()->hasColumn('jenis_tindakan', $column)) {
                throw new \RuntimeException("Required column '{$column}' not found in jenis_tindakan table");
            }
        }

        $this->command->info('✅ System requirements validated');
    }

    /**
     * Validate seeder results
     */
    protected function validateSeederResults(int $successCount, int $errorCount): void
    {
        $totalExpected = $this->seederService->getExpectedProcedureCount();
        
        if ($successCount < $totalExpected * 0.8) { // Allow 20% failure tolerance
            throw new \RuntimeException(
                "Seeder validation failed: Only {$successCount}/{$totalExpected} procedures seeded successfully"
            );
        }

        // Verify data integrity
        $integrityCheck = $this->validationService->validateDataIntegrity();
        if (!$integrityCheck['isValid']) {
            throw new \RuntimeException(
                "Data integrity validation failed: " . implode(', ', $integrityCheck['errors'])
            );
        }
    }

    /**
     * Generate comprehensive seeder report
     */
    protected function generateSeederReport(int $successCount, int $errorCount): void
    {
        $report = $this->seederService->generateSeederReport($successCount, $errorCount);
        
        $this->command->info("📊 Seeder Report:");
        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Total Procedures', $report['totalProcedures']],
                ['Successfully Seeded', $report['successCount']],
                ['Errors', $report['errorCount']],
                ['Success Rate', $report['successRate'] . '%'],
                ['Categories Seeded', implode(', ', $report['categoriesSeeded'])],
                ['Total Value (Tarif)', 'Rp ' . number_format($report['totalValue'])],
                ['Average Jaspel %', $report['averageJaspel'] . '%'],
            ]
        );

        if (!empty($report['recommendations'])) {
            $this->command->warn("⚡ Recommendations:");
            foreach ($report['recommendations'] as $recommendation) {
                $this->command->line("  • {$recommendation}");
            }
        }
    }
}