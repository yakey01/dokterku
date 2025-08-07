<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Medical\Procedures\Calculators\FeeCalculatorService;

/**
 * Dedicated Fee Calculator Validation Test
 * 
 * This script specifically tests the fee calculation logic
 */

echo "💰 Fee Calculator Validation Test\n";
echo "=" . str_repeat("=", 40) . "\n\n";

try {
    // Initialize Laravel application
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $feeCalculator = new FeeCalculatorService();
    
    echo "🧪 Running validation tests...\n";
    $validationResult = $feeCalculator->validateCalculationLogic();
    
    if ($validationResult['all_tests_passed']) {
        echo "   ✅ All validation tests passed!\n\n";
    } else {
        echo "   ❌ Some validation tests failed:\n";
        foreach ($validationResult['test_results'] as $index => $result) {
            if (!$result['validation']['sum_check_passed']) {
                echo "   Test Case " . ($index + 1) . ":\n";
                echo "      Tariff: Rp " . number_format($result['test_case']['tarif']) . "\n";
                echo "      Complexity: {$result['test_case']['complexity']}\n";
                echo "      Requires Doctor: " . ($result['test_case']['requires_doctor'] ? 'Yes' : 'No') . "\n";
                echo "      Category: {$result['test_case']['kategori']}\n";
                echo "      Expected Total: Rp " . number_format($result['validation']['expected_total'], 2) . "\n";
                echo "      Calculated Total: Rp " . number_format($result['validation']['calculated_total'], 2) . "\n";
                echo "      Variance: Rp " . number_format($result['validation']['variance'], 2) . "\n";
                echo "\n";
            }
        }
    }
    
    // Test specific examples to demonstrate the fix
    echo "🔍 Testing specific examples:\n\n";
    
    $testCases = [
        [
            'name' => 'Standard Tindakan (Paramedic Only)',
            'tarif' => 30000,
            'complexity' => 'standard',
            'requires_doctor' => false,
            'kategori' => 'tindakan'
        ],
        [
            'name' => 'Complex Tindakan (Doctor Required)',
            'tarif' => 75000,
            'complexity' => 'complex',
            'requires_doctor' => true,
            'kategori' => 'tindakan'
        ],
        [
            'name' => 'Simple Pemeriksaan (Paramedic Only)',
            'tarif' => 25000,
            'complexity' => 'simple',
            'requires_doctor' => false,
            'kategori' => 'pemeriksaan'
        ]
    ];
    
    foreach ($testCases as $index => $testCase) {
        echo "Test Case " . ($index + 1) . ": {$testCase['name']}\n";
        
        $fees = $feeCalculator->calculateFees(
            $testCase['tarif'],
            $testCase['complexity'],
            $testCase['requires_doctor'],
            $testCase['kategori']
        );
        
        echo "   Base Tariff: Rp " . number_format($testCase['tarif']) . "\n";
        echo "   Adjusted Tariff: Rp " . number_format($fees['calculation_metadata']['adjusted_tariff']) . "\n";
        echo "   Jaspel Percentage: {$fees['persentase_jaspel']}%\n";
        echo "   Total Jaspel: Rp " . number_format($fees['total_jaspel']) . "\n";
        echo "   Distribution:\n";
        echo "      - Doctor Fee: Rp " . number_format($fees['jasa_dokter']) . "\n";
        echo "      - Paramedic Fee: Rp " . number_format($fees['jasa_paramedis']) . "\n";
        echo "      - Non-Paramedic Fee: Rp " . number_format($fees['jasa_non_paramedis']) . "\n";
        
        // Validation check
        $totalDistribution = $fees['jasa_dokter'] + $fees['jasa_paramedis'] + $fees['jasa_non_paramedis'];
        $variance = abs($totalDistribution - $fees['total_jaspel']);
        
        if ($variance < 0.01) {
            echo "   ✅ Distribution sum matches total jaspel\n";
        } else {
            echo "   ❌ Distribution sum mismatch (variance: Rp " . number_format($variance, 2) . ")\n";
        }
        
        echo "\n";
    }
    
    echo "🎉 Fee Calculator validation completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}