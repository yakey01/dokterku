<?php

namespace App\Services\Medical\Procedures\Calculators;

class FeeCalculatorService
{
    /**
     * Fee calculation matrices based on procedure characteristics
     */
    protected array $complexityMultipliers = [
        'simple' => 0.6,      // Simple procedures - lower skill requirement
        'standard' => 1.0,    // Standard procedures - normal complexity
        'complex' => 1.4      // Complex procedures - higher skill requirement
    ];

    protected array $categoryJaspelPercentages = [
        'konsultasi' => 30.00,     // Doctor-centric services
        'pemeriksaan' => 70.00,    // Can be performed by paramedics
        'tindakan' => 70.00,       // Most procedures by paramedics
        'obat' => 60.00,           // Medication management
        'lainnya' => 40.00         // Administrative services
    ];

    protected array $doctorRequiredAdjustment = [
        'jasa_dokter_percentage' => 70.00,      // 70% of jaspel goes to doctor
        'jasa_paramedis_percentage' => 20.00,   // 20% to paramedics
        'jasa_non_paramedis_percentage' => 10.00, // 10% to support staff
        'persentase_jaspel' => 30.00            // Lower total jaspel (30%)
    ];

    protected array $paramedicOnlyAdjustment = [
        'jasa_dokter_percentage' => 0.00,       // No doctor fee
        'jasa_paramedis_percentage' => 80.00,   // 80% to paramedics
        'jasa_non_paramedis_percentage' => 20.00, // 20% to support staff
        'persentase_jaspel' => 70.00            // Higher total jaspel (70%)
    ];

    /**
     * Calculate comprehensive fee structure for a medical procedure
     */
    public function calculateFees(
        float $baseTariff,
        string $complexity = 'standard',
        bool $requiresDoctor = false,
        string $category = 'tindakan'
    ): array {
        // Validate inputs
        $this->validateInputs($baseTariff, $complexity, $category);
        
        // Apply complexity adjustment
        $adjustedTariff = $baseTariff * $this->getComplexityMultiplier($complexity);
        
        // Determine fee structure based on doctor requirement
        $feeStructure = $requiresDoctor 
            ? $this->doctorRequiredAdjustment 
            : $this->paramedicOnlyAdjustment;
        
        // Determine jaspel percentage based on doctor requirement and category
        if ($requiresDoctor) {
            // For doctor-required procedures, use the doctor structure percentage
            $jaspelPercentage = $feeStructure['persentase_jaspel'];
        } else {
            // For paramedic procedures, use category-specific percentage
            $jaspelPercentage = $this->categoryJaspelPercentages[$category] ?? $feeStructure['persentase_jaspel'];
        }
        
        // Calculate total jaspel amount
        $totalJaspel = ($adjustedTariff * $jaspelPercentage) / 100;
        
        // Distribute jaspel according to structure
        $jasaDokter = ($totalJaspel * $feeStructure['jasa_dokter_percentage']) / 100;
        $jasaParamedis = ($totalJaspel * $feeStructure['jasa_paramedis_percentage']) / 100;
        $jasaNonParamedis = ($totalJaspel * $feeStructure['jasa_non_paramedis_percentage']) / 100;
        
        // Apply rounding for currency precision
        return [
            'jasa_dokter' => round($jasaDokter, 2),
            'jasa_paramedis' => round($jasaParamedis, 2),
            'jasa_non_paramedis' => round($jasaNonParamedis, 2),
            'persentase_jaspel' => $jaspelPercentage,
            'total_jaspel' => round($totalJaspel, 2),
            'calculation_metadata' => [
                'base_tariff' => $baseTariff,
                'adjusted_tariff' => $adjustedTariff,
                'complexity' => $complexity,
                'complexity_multiplier' => $this->getComplexityMultiplier($complexity),
                'requires_doctor' => $requiresDoctor,
                'category' => $category,
                'fee_structure' => $requiresDoctor ? 'doctor_required' : 'paramedic_only'
            ]
        ];
    }

    /**
     * Calculate fees for multiple procedures
     */
    public function calculateBatchFees(array $procedures): array
    {
        $results = [];
        
        foreach ($procedures as $index => $procedure) {
            try {
                $fees = $this->calculateFees(
                    $procedure['tarif'],
                    $procedure['complexity'] ?? 'standard',
                    $procedure['requires_doctor'] ?? false,
                    $procedure['kategori'] ?? 'tindakan'
                );
                
                $results[$index] = [
                    'success' => true,
                    'fees' => $fees,
                    'procedure' => $procedure
                ];
            } catch (\Exception $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'procedure' => $procedure
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get complexity multiplier with validation
     */
    protected function getComplexityMultiplier(string $complexity): float
    {
        return $this->complexityMultipliers[$complexity] ?? 1.0;
    }

    /**
     * Validate calculation inputs
     */
    protected function validateInputs(float $baseTariff, string $complexity, string $category): void
    {
        if ($baseTariff <= 0) {
            throw new \InvalidArgumentException('Base tariff must be greater than 0');
        }
        
        if (!array_key_exists($complexity, $this->complexityMultipliers)) {
            throw new \InvalidArgumentException("Invalid complexity level: {$complexity}");
        }
        
        $validCategories = array_keys($this->categoryJaspelPercentages);
        if (!in_array($category, $validCategories)) {
            throw new \InvalidArgumentException("Invalid category: {$category}");
        }
    }

    /**
     * Get fee calculation report for analysis
     */
    public function getFeeCalculationReport(array $procedures): array
    {
        $results = $this->calculateBatchFees($procedures);
        
        $totalProcedures = count($results);
        $successfulCalculations = count(array_filter($results, fn($r) => $r['success']));
        $failedCalculations = $totalProcedures - $successfulCalculations;
        
        // Analyze successful calculations
        $successfulFees = array_filter($results, fn($r) => $r['success']);
        
        $totalJaspelAmount = array_sum(array_column(array_column($successfulFees, 'fees'), 'total_jaspel'));
        $averageJaspelPercentage = count($successfulFees) > 0 
            ? array_sum(array_column(array_column($successfulFees, 'fees'), 'persentase_jaspel')) / count($successfulFees)
            : 0;
        
        // Doctor vs Paramedic distribution
        $doctorProcedures = count(array_filter($successfulFees, fn($r) => $r['fees']['jasa_dokter'] > 0));
        $paramedicOnlyProcedures = count($successfulFees) - $doctorProcedures;
        
        // Complexity distribution
        $complexityDistribution = [];
        foreach ($successfulFees as $result) {
            $complexity = $result['fees']['calculation_metadata']['complexity'];
            $complexityDistribution[$complexity] = ($complexityDistribution[$complexity] ?? 0) + 1;
        }
        
        return [
            'summary' => [
                'total_procedures' => $totalProcedures,
                'successful_calculations' => $successfulCalculations,
                'failed_calculations' => $failedCalculations,
                'success_rate' => round(($successfulCalculations / $totalProcedures) * 100, 2)
            ],
            'financial_analysis' => [
                'total_jaspel_amount' => round($totalJaspelAmount, 2),
                'average_jaspel_percentage' => round($averageJaspelPercentage, 2),
                'doctor_procedures' => $doctorProcedures,
                'paramedic_only_procedures' => $paramedicOnlyProcedures
            ],
            'complexity_distribution' => $complexityDistribution,
            'errors' => array_column(array_filter($results, fn($r) => !$r['success']), 'error')
        ];
    }

    /**
     * Validate fee calculation logic
     */
    public function validateCalculationLogic(): array
    {
        $testCases = [
            ['tarif' => 30000, 'complexity' => 'standard', 'requires_doctor' => false, 'kategori' => 'tindakan'],
            ['tarif' => 75000, 'complexity' => 'complex', 'requires_doctor' => true, 'kategori' => 'tindakan'],
            ['tarif' => 25000, 'complexity' => 'simple', 'requires_doctor' => false, 'kategori' => 'pemeriksaan'],
            ['tarif' => 100000, 'complexity' => 'standard', 'requires_doctor' => false, 'kategori' => 'tindakan']
        ];
        
        $validationResults = [];
        
        foreach ($testCases as $index => $testCase) {
            try {
                $fees = $this->calculateFees(
                    $testCase['tarif'],
                    $testCase['complexity'],
                    $testCase['requires_doctor'],
                    $testCase['kategori']
                );
                
                // Validate calculation integrity
                $totalCalculated = $fees['jasa_dokter'] + $fees['jasa_paramedis'] + $fees['jasa_non_paramedis'];
                $expectedTotal = $fees['total_jaspel'];
                $variance = abs($totalCalculated - $expectedTotal);
                
                $validationResults[] = [
                    'test_case' => $testCase,
                    'fees' => $fees,
                    'validation' => [
                        'sum_check_passed' => $variance < 0.01, // Allow 1 cent variance for rounding
                        'calculated_total' => $totalCalculated,
                        'expected_total' => $expectedTotal,
                        'variance' => $variance
                    ]
                ];
                
            } catch (\Exception $e) {
                $validationResults[] = [
                    'test_case' => $testCase,
                    'error' => $e->getMessage(),
                    'validation' => [
                        'sum_check_passed' => false
                    ]
                ];
            }
        }
        
        $allPassed = array_reduce($validationResults, function ($carry, $result) {
            return $carry && $result['validation']['sum_check_passed'];
        }, true);
        
        return [
            'all_tests_passed' => $allPassed,
            'test_results' => $validationResults
        ];
    }
}