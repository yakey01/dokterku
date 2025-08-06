<?php

namespace App\Services\Medical\Procedures;

use App\Models\JenisTindakan;
use App\Services\Medical\Procedures\Generators\ProcedureCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProcedureValidationService
{
    protected ProcedureCodeGenerator $codeGenerator;

    public function __construct(ProcedureCodeGenerator $codeGenerator)
    {
        $this->codeGenerator = $codeGenerator;
    }

    /**
     * Validate individual procedure data
     */
    public function validateProcedure(array $procedure): array
    {
        $errors = [];
        $warnings = [];

        // Required fields validation
        $requiredFields = ['nama', 'tarif', 'kategori', 'kode'];
        foreach ($requiredFields as $field) {
            if (!isset($procedure[$field]) || empty($procedure[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        // Early return if critical fields missing
        if (!empty($errors)) {
            return [
                'isValid' => false,
                'errors' => $errors,
                'warnings' => $warnings
            ];
        }

        // Validate individual fields
        $fieldValidation = $this->validateFields($procedure);
        $errors = array_merge($errors, $fieldValidation['errors']);
        $warnings = array_merge($warnings, $fieldValidation['warnings']);

        // Business logic validation
        $businessValidation = $this->validateBusinessLogic($procedure);
        $errors = array_merge($errors, $businessValidation['errors']);
        $warnings = array_merge($warnings, $businessValidation['warnings']);

        // Data consistency validation
        $consistencyValidation = $this->validateDataConsistency($procedure);
        $errors = array_merge($errors, $consistencyValidation['errors']);
        $warnings = array_merge($warnings, $consistencyValidation['warnings']);

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'validationDetails' => [
                'fieldValidation' => $fieldValidation,
                'businessValidation' => $businessValidation,
                'consistencyValidation' => $consistencyValidation
            ]
        ];
    }

    /**
     * Validate field formats and constraints
     */
    protected function validateFields(array $procedure): array
    {
        $errors = [];
        $warnings = [];

        // Validate procedure name
        if (isset($procedure['nama'])) {
            if (strlen($procedure['nama']) < 5) {
                $errors[] = "Procedure name too short (minimum 5 characters)";
            }
            if (strlen($procedure['nama']) > 255) {
                $errors[] = "Procedure name too long (maximum 255 characters)";
            }
            if (!preg_match('/^[a-zA-Z0-9\s\(\)\-\/\<\>]+$/u', $procedure['nama'])) {
                $warnings[] = "Procedure name contains unusual characters";
            }
        }

        // Validate tariff
        if (isset($procedure['tarif'])) {
            if (!is_numeric($procedure['tarif'])) {
                $errors[] = "Tariff must be numeric";
            } elseif ($procedure['tarif'] <= 0) {
                $errors[] = "Tariff must be greater than 0";
            } elseif ($procedure['tarif'] > 10000000) {
                $warnings[] = "Tariff seems unusually high (>10,000,000)";
            } elseif ($procedure['tarif'] < 1000) {
                $warnings[] = "Tariff seems unusually low (<1,000)";
            }
        }

        // Validate category
        $validCategories = ['konsultasi', 'pemeriksaan', 'tindakan', 'obat', 'lainnya'];
        if (isset($procedure['kategori']) && !in_array($procedure['kategori'], $validCategories)) {
            $errors[] = "Invalid category. Valid categories: " . implode(', ', $validCategories);
        }

        // Validate procedure code
        if (isset($procedure['kode'])) {
            if (!$this->codeGenerator->validateCodeFormat($procedure['kode'])) {
                $errors[] = "Invalid code format. Expected format: 3 letters + 3 numbers (e.g., INJ001)";
            }
        }

        // Validate jaspel percentages
        if (isset($procedure['persentase_jaspel'])) {
            if (!is_numeric($procedure['persentase_jaspel'])) {
                $errors[] = "Jaspel percentage must be numeric";
            } elseif ($procedure['persentase_jaspel'] < 0 || $procedure['persentase_jaspel'] > 100) {
                $errors[] = "Jaspel percentage must be between 0 and 100";
            }
        }

        // Validate fee amounts
        $feeFields = ['jasa_dokter', 'jasa_paramedis', 'jasa_non_paramedis'];
        foreach ($feeFields as $field) {
            if (isset($procedure[$field])) {
                if (!is_numeric($procedure[$field])) {
                    $errors[] = "'{$field}' must be numeric";
                } elseif ($procedure[$field] < 0) {
                    $errors[] = "'{$field}' cannot be negative";
                }
            }
        }

        // Validate description
        if (isset($procedure['deskripsi']) && !empty($procedure['deskripsi'])) {
            if (strlen($procedure['deskripsi']) < 10) {
                $warnings[] = "Description seems too short for proper documentation";
            }
            if (strlen($procedure['deskripsi']) > 1000) {
                $warnings[] = "Description might be too long";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate business logic and rules
     */
    protected function validateBusinessLogic(array $procedure): array
    {
        $errors = [];
        $warnings = [];

        // Check fee consistency with tariff
        if (isset($procedure['tarif'], $procedure['jasa_dokter'], $procedure['jasa_paramedis'], $procedure['jasa_non_paramedis'])) {
            $totalFees = $procedure['jasa_dokter'] + $procedure['jasa_paramedis'] + $procedure['jasa_non_paramedis'];
            $tariff = $procedure['tarif'];
            
            if ($totalFees > $tariff) {
                $errors[] = "Total fees (Rp " . number_format($totalFees) . ") cannot exceed tariff (Rp " . number_format($tariff) . ")";
            }
            
            $feePercentage = ($totalFees / $tariff) * 100;
            if ($feePercentage > 80) {
                $warnings[] = "Total fees represent {$feePercentage}% of tariff, which is quite high";
            }
        }

        // Validate jaspel percentage consistency
        if (isset($procedure['tarif'], $procedure['persentase_jaspel'])) {
            $expectedJaspelAmount = ($procedure['tarif'] * $procedure['persentase_jaspel']) / 100;
            
            if (isset($procedure['jasa_dokter'], $procedure['jasa_paramedis'], $procedure['jasa_non_paramedis'])) {
                $actualJaspelAmount = $procedure['jasa_dokter'] + $procedure['jasa_paramedis'] + $procedure['jasa_non_paramedis'];
                $variance = abs($expectedJaspelAmount - $actualJaspelAmount);
                
                if ($variance > 1) { // Allow 1 rupiah variance for rounding
                    $warnings[] = "Jaspel amount mismatch. Expected: Rp " . number_format($expectedJaspelAmount) . 
                                ", Actual: Rp " . number_format($actualJaspelAmount);
                }
            }
        }

        // Doctor fee logic validation
        if (isset($procedure['jasa_dokter']) && $procedure['jasa_dokter'] > 0) {
            // If doctor fee exists, it should be significant for complex procedures
            if (isset($procedure['complexity']) && $procedure['complexity'] === 'complex' && 
                isset($procedure['tarif']) && ($procedure['jasa_dokter'] / $procedure['tarif']) < 0.3) {
                $warnings[] = "Complex procedures typically require higher doctor fees";
            }
        }

        // Category-specific validations
        if (isset($procedure['kategori'])) {
            switch ($procedure['kategori']) {
                case 'konsultasi':
                    if (isset($procedure['jasa_dokter']) && $procedure['jasa_dokter'] == 0) {
                        $warnings[] = "Consultation procedures typically require doctor involvement";
                    }
                    break;
                    
                case 'pemeriksaan':
                    if (isset($procedure['tarif']) && $procedure['tarif'] > 100000) {
                        $warnings[] = "Examination procedures are typically lower cost";
                    }
                    break;
                    
                case 'tindakan':
                    if (isset($procedure['jasa_paramedis']) && $procedure['jasa_paramedis'] == 0) {
                        $warnings[] = "Medical procedures typically involve paramedic staff";
                    }
                    break;
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate data consistency and uniqueness
     */
    protected function validateDataConsistency(array $procedure): array
    {
        $errors = [];
        $warnings = [];

        // Check code uniqueness (excluding current record if updating)
        if (isset($procedure['kode'])) {
            $query = JenisTindakan::where('kode', $procedure['kode']);
            if (isset($procedure['id'])) {
                $query->where('id', '!=', $procedure['id']);
            }
            
            if ($query->exists()) {
                $errors[] = "Procedure code '{$procedure['kode']}' already exists";
            }
        }

        // Check name similarity (potential duplicates)
        if (isset($procedure['nama'])) {
            $similarProcedures = JenisTindakan::where('nama', 'LIKE', '%' . $procedure['nama'] . '%')
                ->orWhere('nama', 'SOUNDS LIKE', $procedure['nama']);
                
            if (isset($procedure['id'])) {
                $similarProcedures->where('id', '!=', $procedure['id']);
            }
            
            $similar = $similarProcedures->pluck('nama')->toArray();
            if (!empty($similar)) {
                $warnings[] = "Similar procedure names exist: " . implode(', ', array_slice($similar, 0, 3));
            }
        }

        // Validate tariff consistency with similar procedures
        if (isset($procedure['kategori'], $procedure['tarif'])) {
            $avgTariffForCategory = JenisTindakan::where('kategori', $procedure['kategori'])
                ->avg('tarif');
                
            if ($avgTariffForCategory && abs($procedure['tarif'] - $avgTariffForCategory) > ($avgTariffForCategory * 2)) {
                $warnings[] = "Tariff significantly different from category average (Rp " . 
                            number_format($avgTariffForCategory) . ")";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate data integrity across all procedures
     */
    public function validateDataIntegrity(): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Check for duplicate codes
            $duplicateCodes = DB::table('jenis_tindakan')
                ->select('kode')
                ->groupBy('kode')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('kode');

            if ($duplicateCodes->isNotEmpty()) {
                $errors[] = "Duplicate procedure codes found: " . $duplicateCodes->implode(', ');
            }

            // Check for procedures with zero tariffs
            $zeroTariffCount = JenisTindakan::where('tarif', '<=', 0)->count();
            if ($zeroTariffCount > 0) {
                $errors[] = "{$zeroTariffCount} procedures have zero or negative tariffs";
            }

            // Check for invalid jaspel percentages
            $invalidJaspelCount = JenisTindakan::where('persentase_jaspel', '<', 0)
                ->orWhere('persentase_jaspel', '>', 100)
                ->count();
            if ($invalidJaspelCount > 0) {
                $errors[] = "{$invalidJaspelCount} procedures have invalid jaspel percentages";
            }

            // Check for fee consistency
            $inconsistentFees = JenisTindakan::whereRaw('
                (jasa_dokter + jasa_paramedis + jasa_non_paramedis) > tarif
            ')->count();
            if ($inconsistentFees > 0) {
                $errors[] = "{$inconsistentFees} procedures have fees exceeding tariff";
            }

            // Check for inactive procedures (warning)
            $inactiveCount = JenisTindakan::where('is_active', false)->count();
            if ($inactiveCount > 0) {
                $warnings[] = "{$inactiveCount} procedures are marked as inactive";
            }

            // Validate total counts
            $totalProcedures = JenisTindakan::count();
            if ($totalProcedures === 0) {
                $errors[] = "No procedures found in database";
            }

            // Check category distribution
            $categories = JenisTindakan::select('kategori')
                ->groupBy('kategori')
                ->pluck('kategori');
            
            if ($categories->count() < 3) {
                $warnings[] = "Limited procedure categories available";
            }

        } catch (\Exception $e) {
            $errors[] = "Database integrity check failed: " . $e->getMessage();
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => [
                'total_procedures' => JenisTindakan::count(),
                'active_procedures' => JenisTindakan::where('is_active', true)->count(),
                'categories_count' => JenisTindakan::distinct('kategori')->count(),
                'average_tariff' => JenisTindakan::avg('tarif'),
                'validation_timestamp' => now()
            ]
        ];
    }

    /**
     * Generate validation report for procedures
     */
    public function generateValidationReport(array $procedures): array
    {
        $report = [
            'total_procedures' => count($procedures),
            'validation_results' => [],
            'summary' => [
                'valid_procedures' => 0,
                'invalid_procedures' => 0,
                'procedures_with_warnings' => 0,
                'total_errors' => 0,
                'total_warnings' => 0
            ],
            'common_errors' => [],
            'common_warnings' => []
        ];

        $allErrors = [];
        $allWarnings = [];

        foreach ($procedures as $index => $procedure) {
            $validation = $this->validateProcedure($procedure);
            
            $report['validation_results'][] = [
                'procedure' => $procedure['nama'] ?? "Procedure {$index}",
                'code' => $procedure['kode'] ?? 'N/A',
                'is_valid' => $validation['isValid'],
                'error_count' => count($validation['errors']),
                'warning_count' => count($validation['warnings']),
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings']
            ];

            // Update summary
            if ($validation['isValid']) {
                $report['summary']['valid_procedures']++;
            } else {
                $report['summary']['invalid_procedures']++;
            }

            if (!empty($validation['warnings'])) {
                $report['summary']['procedures_with_warnings']++;
            }

            // Collect all errors and warnings
            $allErrors = array_merge($allErrors, $validation['errors']);
            $allWarnings = array_merge($allWarnings, $validation['warnings']);
        }

        $report['summary']['total_errors'] = count($allErrors);
        $report['summary']['total_warnings'] = count($allWarnings);
        
        // Find common issues
        $report['common_errors'] = array_slice(
            array_count_values($allErrors),
            0,
            5,
            true
        );
        
        $report['common_warnings'] = array_slice(
            array_count_values($allWarnings),
            0,
            5,
            true
        );

        return $report;
    }
}