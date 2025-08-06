<?php

namespace App\Services\Medical\Procedures;

use App\Models\JenisTindakan;
use App\Services\Medical\Procedures\Data\MedicalProcedureDataProvider;
use App\Services\Medical\Procedures\Calculators\FeeCalculatorService;
use App\Services\Medical\Procedures\Generators\ProcedureCodeGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MedicalProcedureSeederService
{
    protected MedicalProcedureDataProvider $dataProvider;
    protected FeeCalculatorService $feeCalculator;
    protected ProcedureCodeGenerator $codeGenerator;

    public function __construct(
        MedicalProcedureDataProvider $dataProvider,
        FeeCalculatorService $feeCalculator,
        ProcedureCodeGenerator $codeGenerator
    ) {
        $this->dataProvider = $dataProvider;
        $this->feeCalculator = $feeCalculator;
        $this->codeGenerator = $codeGenerator;
    }

    /**
     * Get enhanced procedure data with intelligent processing
     */
    public function getEnhancedProcedureData(): Collection
    {
        $rawProcedures = $this->dataProvider->getRawProcedureData();
        
        return $rawProcedures->map(function ($procedure) {
            return $this->enhanceProcedureData($procedure);
        });
    }

    /**
     * Enhance individual procedure data with calculated fields
     */
    protected function enhanceProcedureData(array $procedure): array
    {
        // Generate appropriate code if not provided
        if (empty($procedure['kode'])) {
            $procedure['kode'] = $this->codeGenerator->generateCode(
                $procedure['nama'],
                $procedure['kategori'] ?? 'tindakan'
            );
        }

        // Calculate fees based on complexity and requirements
        $feeStructure = $this->feeCalculator->calculateFees(
            $procedure['tarif'],
            $procedure['complexity'] ?? 'standard',
            $procedure['requires_doctor'] ?? false,
            $procedure['kategori'] ?? 'tindakan'
        );

        // Apply calculated fees
        $procedure['jasa_dokter'] = $feeStructure['jasa_dokter'];
        $procedure['jasa_paramedis'] = $feeStructure['jasa_paramedis'];
        $procedure['jasa_non_paramedis'] = $feeStructure['jasa_non_paramedis'];
        $procedure['persentase_jaspel'] = $feeStructure['persentase_jaspel'];

        // Ensure required fields
        $procedure['is_active'] = $procedure['is_active'] ?? true;
        $procedure['kategori'] = $procedure['kategori'] ?? 'tindakan';

        // Generate comprehensive description if minimal
        if (empty($procedure['deskripsi']) || strlen($procedure['deskripsi']) < 20) {
            $procedure['deskripsi'] = $this->generateDetailedDescription($procedure);
        }

        return $procedure;
    }

    /**
     * Seed individual procedure with comprehensive error handling
     */
    public function seedProcedure(array $procedure): array
    {
        try {
            // Check if procedure already exists
            $existing = JenisTindakan::where('kode', $procedure['kode'])->first();
            
            if ($existing) {
                // Update existing procedure with new data
                $updated = $existing->update($procedure);
                
                return [
                    'success' => true,
                    'action' => 'updated',
                    'id' => $existing->id,
                    'data' => $procedure
                ];
            } else {
                // Create new procedure
                $created = JenisTindakan::create($procedure);
                
                return [
                    'success' => true,
                    'action' => 'created',
                    'id' => $created->id,
                    'data' => $procedure
                ];
            }
        } catch (\Exception $e) {
            Log::error("Failed to seed procedure: {$e->getMessage()}", [
                'procedure' => $procedure,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => $procedure
            ];
        }
    }

    /**
     * Get expected number of procedures for validation
     */
    public function getExpectedProcedureCount(): int
    {
        return $this->dataProvider->getRawProcedureData()->count();
    }

    /**
     * Generate comprehensive seeder report
     */
    public function generateSeederReport(int $successCount, int $errorCount): array
    {
        $totalProcedures = $successCount + $errorCount;
        $successRate = $totalProcedures > 0 ? round(($successCount / $totalProcedures) * 100, 2) : 0;

        // Get seeded data statistics
        $seededProcedures = JenisTindakan::all();
        $categoriesSeeded = $seededProcedures->pluck('kategori')->unique()->toArray();
        $totalValue = $seededProcedures->sum('tarif');
        $averageJaspel = $seededProcedures->avg('persentase_jaspel');

        $recommendations = [];

        // Generate recommendations based on results
        if ($successRate < 95) {
            $recommendations[] = "Success rate below 95% - review error logs for improvement";
        }

        if (count($categoriesSeeded) < 4) {
            $recommendations[] = "Consider adding more procedure categories for comprehensive coverage";
        }

        if ($averageJaspel > 60) {
            $recommendations[] = "High average jaspel percentage - review fee structure";
        }

        return [
            'totalProcedures' => $totalProcedures,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'successRate' => $successRate,
            'categoriesSeeded' => $categoriesSeeded,
            'totalValue' => $totalValue,
            'averageJaspel' => round($averageJaspel, 2),
            'recommendations' => $recommendations
        ];
    }

    /**
     * Generate detailed description for procedures
     */
    protected function generateDetailedDescription(array $procedure): string
    {
        $baseName = $procedure['nama'];
        $kategori = $procedure['kategori'] ?? 'tindakan';
        
        $descriptions = [
            'injeksi' => 'Prosedur pemberian obat atau cairan melalui jarum suntik steril dengan teknik aseptik',
            'infus' => 'Tindakan medis untuk memberikan cairan, elektrolit, atau obat langsung ke dalam pembuluh darah',
            'kateter' => 'Prosedur pemasangan atau pelepasan alat medis untuk drainase atau akses ke organ tubuh',
            'jahit' => 'Tindakan penjahitan luka dengan teknik steril untuk mempercepat penyembuhan',
            'pemeriksaan' => 'Prosedur diagnostik untuk mengevaluasi kondisi kesehatan pasien',
            'nebulizer' => 'Terapi inhalasi untuk mengubah obat cair menjadi uap halus yang mudah dihirup',
            'oksigenasi' => 'Pemberian terapi oksigen untuk membantu pernapasan dan oksigenasi jaringan',
            'ekstraksi' => 'Tindakan pengangkatan atau pencabutan benda asing atau jaringan bermasalah',
            'perawatan luka' => 'Tindakan pembersihan, perawatan, dan pembalutan luka dengan teknik steril',
            'insisi' => 'Tindakan pembedahan minor dengan sayatan kecil untuk akses atau drainase'
        ];

        $lowerName = strtolower($baseName);
        
        foreach ($descriptions as $keyword => $description) {
            if (str_contains($lowerName, $keyword)) {
                return $description . ". Dilakukan sesuai standar prosedur medis yang berlaku.";
            }
        }
        
        // Fallback description based on category
        $categoryDescriptions = [
            'tindakan' => 'Prosedur medis yang dilakukan sesuai standar operasional dan keselamatan pasien.',
            'pemeriksaan' => 'Pemeriksaan medis yang dilakukan untuk evaluasi kondisi kesehatan pasien.',
            'konsultasi' => 'Layanan konsultasi medis dengan dokter spesialis atau umum.',
            'lainnya' => 'Layanan medis pendukung yang diperlukan untuk perawatan pasien.'
        ];
        
        return $categoryDescriptions[$kategori] ?? 'Layanan medis yang dilakukan sesuai standar prosedur yang berlaku.';
    }
}