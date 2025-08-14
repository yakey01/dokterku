<?php

namespace App\Services\Jaspel;

use App\Models\JumlahPasienHarian;
use App\Models\DokterUmumJaspel;
use App\Models\JadwalJaga;
use App\Models\Dokter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Unified Jaspel Calculation Service
 * 
 * Resolves calculation discrepancies by providing a single, consistent
 * calculation engine for all jaspel-related operations.
 */
class UnifiedJaspelCalculationService
{
    /**
     * Calculate jaspel for a specific patient count record
     */
    public function calculateForPasienRecord(JumlahPasienHarian $record): array
    {
        // Get the active formula with proper precedence
        $formula = $this->getFormulaForRecord($record);
        
        if (!$formula) {
            return [
                'error' => 'No active jaspel formula found',
                'fee_umum' => 0,
                'fee_bpjs' => 0,
                'uang_duduk' => 0,
                'total' => 0,
                'calculation_method' => 'error',
                'formula_used' => null,
            ];
        }

        $result = $this->performCalculation(
            $record->jumlah_pasien_umum,
            $record->jumlah_pasien_bpjs,
            $formula
        );

        // Add metadata for audit trail
        $result['calculation_method'] = 'unified_service';
        $result['formula_used'] = $formula->toArray();
        $result['jadwal_jaga_context'] = $record->jadwalJaga ? $record->jadwalJaga->toArray() : null;
        $result['calculated_at'] = now()->toISOString();

        return $result;
    }

    /**
     * Calculate jaspel for specific parameters with jadwal jaga context
     */
    public function calculateForJadwalJaga(
        int $pasienUmum,
        int $pasienBpjs,
        JadwalJaga $jadwalJaga
    ): array {
        // Get formula based on jadwal jaga shift
        $formula = $this->getFormulaForJadwalJaga($jadwalJaga);
        
        if (!$formula) {
            return [
                'error' => 'No jaspel formula found for shift: ' . $jadwalJaga->shiftTemplate->nama_shift,
                'fee_umum' => 0,
                'fee_bpjs' => 0,
                'uang_duduk' => 0,
                'total' => 0,
                'calculation_method' => 'error',
                'formula_used' => null,
            ];
        }

        $result = $this->performCalculation($pasienUmum, $pasienBpjs, $formula);

        // Add metadata
        $result['calculation_method'] = 'unified_service_with_jadwal';
        $result['formula_used'] = $formula->toArray();
        $result['jadwal_jaga_context'] = $jadwalJaga->toArray();
        $result['calculated_at'] = now()->toISOString();

        return $result;
    }

    /**
     * Calculate estimated jaspel for given parameters and shift
     */
    public function calculateEstimated(
        int $pasienUmum,
        int $pasienBpjs,
        string $shift
    ): array {
        // Get formula for the shift
        $formula = DokterUmumJaspel::where('jenis_shift', $shift)
            ->where('status_aktif', true)
            ->first();

        if (!$formula) {
            // Try to get any active formula as fallback
            $formula = DokterUmumJaspel::where('status_aktif', true)->first();
        }

        if (!$formula) {
            return [
                'error' => 'No active jaspel formula found',
                'fee_umum' => 0,
                'fee_bpjs' => 0,
                'uang_duduk' => 0,
                'total' => 0,
                'calculation_method' => 'error',
                'formula_used' => null,
            ];
        }

        $result = $this->performCalculation($pasienUmum, $pasienBpjs, $formula);

        // Add metadata
        $result['calculation_method'] = 'unified_service_estimated';
        $result['formula_used'] = $formula->toArray();
        $result['shift_requested'] = $shift;
        $result['calculated_at'] = now()->toISOString();

        return $result;
    }

    /**
     * Core calculation logic - single source of truth
     */
    private function performCalculation(
        int $pasienUmum,
        int $pasienBpjs,
        DokterUmumJaspel $formula
    ): array {
        $totalPasien = $pasienUmum + $pasienBpjs;
        $uangDuduk = $formula->uang_duduk;

        // Check if total patients meet the threshold
        if ($totalPasien <= $formula->ambang_pasien) {
            return [
                'fee_umum' => 0,
                'fee_bpjs' => 0,
                'uang_duduk' => $uangDuduk,
                'total' => $uangDuduk,
                'pasien_umum_dihitung' => 0,
                'pasien_bpjs_dihitung' => 0,
                'threshold_met' => false,
                'threshold_value' => $formula->ambang_pasien,
                'total_pasien' => $totalPasien,
            ];
        }

        // Calculate patients counted after threshold
        $totalPasienDihitung = $totalPasien - $formula->ambang_pasien;

        // Calculate proportional distribution
        $proporsiUmum = $totalPasien > 0 ? $pasienUmum / $totalPasien : 0;
        $proporsiBpjs = $totalPasien > 0 ? $pasienBpjs / $totalPasien : 0;

        $pasienUmumDihitung = round($totalPasienDihitung * $proporsiUmum);
        $pasienBpjsDihitung = round($totalPasienDihitung * $proporsiBpjs);

        // Calculate fees
        $feeUmum = $pasienUmumDihitung * $formula->fee_pasien_umum;
        $feeBpjs = $pasienBpjsDihitung * $formula->fee_pasien_bpjs;
        $totalFee = $uangDuduk + $feeUmum + $feeBpjs;

        return [
            'fee_umum' => $feeUmum,
            'fee_bpjs' => $feeBpjs,
            'uang_duduk' => $uangDuduk,
            'total' => $totalFee,
            'pasien_umum_dihitung' => $pasienUmumDihitung,
            'pasien_bpjs_dihitung' => $pasienBpjsDihitung,
            'threshold_met' => true,
            'threshold_value' => $formula->ambang_pasien,
            'total_pasien' => $totalPasien,
            'total_pasien_dihitung' => $totalPasienDihitung,
            'proporsi_umum' => $proporsiUmum,
            'proporsi_bpjs' => $proporsiBpjs,
        ];
    }

    /**
     * Get formula for a JumlahPasienHarian record with proper precedence
     */
    private function getFormulaForRecord(JumlahPasienHarian $record): ?DokterUmumJaspel
    {
        // 1. Explicit formula relationship (highest priority)
        if ($record->dokterUmumJaspel) {
            Log::info('Using explicit formula relationship', [
                'record_id' => $record->id,
                'formula_id' => $record->dokterUmumJaspel->id
            ]);
            return $record->dokterUmumJaspel;
        }

        // 2. Jadwal jaga context (second priority)
        if ($record->jadwalJaga && $record->jadwalJaga->shiftTemplate) {
            $formula = $this->getFormulaForJadwalJaga($record->jadwalJaga);
            if ($formula) {
                Log::info('Using jadwal jaga context formula', [
                    'record_id' => $record->id,
                    'jadwal_jaga_id' => $record->jadwalJaga->id,
                    'shift_template' => $record->jadwalJaga->shiftTemplate->nama_shift,
                    'formula_id' => $formula->id
                ]);
                return $formula;
            }
        }

        // 3. Stored shift field (third priority)
        if ($record->shift) {
            $formula = DokterUmumJaspel::where('jenis_shift', $record->shift)
                ->where('status_aktif', true)
                ->first();
            if ($formula) {
                Log::info('Using stored shift formula', [
                    'record_id' => $record->id,
                    'shift' => $record->shift,
                    'formula_id' => $formula->id
                ]);
                return $formula;
            }
        }

        // 4. Fallback to any active formula (lowest priority)
        $formula = DokterUmumJaspel::where('status_aktif', true)->first();
        if ($formula) {
            Log::warning('Using fallback formula', [
                'record_id' => $record->id,
                'formula_id' => $formula->id
            ]);
        }

        return $formula;
    }

    /**
     * Get formula for a specific jadwal jaga
     */
    private function getFormulaForJadwalJaga(JadwalJaga $jadwalJaga): ?DokterUmumJaspel
    {
        if (!$jadwalJaga->shiftTemplate) {
            return null;
        }

        return DokterUmumJaspel::where('jenis_shift', $jadwalJaga->shiftTemplate->nama_shift)
            ->where('status_aktif', true)
            ->first();
    }

    /**
     * Validate calculation consistency across different methods
     */
    public function validateCalculationConsistency(JumlahPasienHarian $record): array
    {
        $unifiedResult = $this->calculateForPasienRecord($record);
        $modelResult = $record->calculateJaspel();

        $isConsistent = (
            $unifiedResult['total'] == $modelResult['total'] &&
            $unifiedResult['fee_umum'] == $modelResult['fee_umum'] &&
            $unifiedResult['fee_bpjs'] == $modelResult['fee_bpjs'] &&
            $unifiedResult['uang_duduk'] == $modelResult['uang_duduk']
        );

        return [
            'is_consistent' => $isConsistent,
            'unified_result' => $unifiedResult,
            'model_result' => $modelResult,
            'differences' => $isConsistent ? [] : [
                'total_diff' => $unifiedResult['total'] - $modelResult['total'],
                'fee_umum_diff' => $unifiedResult['fee_umum'] - $modelResult['fee_umum'],
                'fee_bpjs_diff' => $unifiedResult['fee_bpjs'] - $modelResult['fee_bpjs'],
                'uang_duduk_diff' => $unifiedResult['uang_duduk'] - $modelResult['uang_duduk'],
            ]
        ];
    }

    /**
     * Get calculation breakdown for transparency
     */
    public function getCalculationBreakdown(JumlahPasienHarian $record): array
    {
        $calculation = $this->calculateForPasienRecord($record);
        $formula = $this->getFormulaForRecord($record);

        if (!$formula) {
            return ['error' => 'No formula available'];
        }

        return [
            'input' => [
                'pasien_umum' => $record->jumlah_pasien_umum,
                'pasien_bpjs' => $record->jumlah_pasien_bpjs,
                'total_pasien' => $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs,
            ],
            'formula' => [
                'jenis_shift' => $formula->jenis_shift,
                'ambang_pasien' => $formula->ambang_pasien,
                'fee_pasien_umum' => $formula->fee_pasien_umum,
                'fee_pasien_bpjs' => $formula->fee_pasien_bpjs,
                'uang_duduk' => $formula->uang_duduk,
            ],
            'calculation_steps' => [
                'threshold_check' => $calculation['threshold_met'],
                'patients_counted' => $calculation['total_pasien_dihitung'] ?? 0,
                'distribution' => [
                    'umum_proportion' => $calculation['proporsi_umum'] ?? 0,
                    'bpjs_proportion' => $calculation['proporsi_bpjs'] ?? 0,
                    'umum_counted' => $calculation['pasien_umum_dihitung'] ?? 0,
                    'bpjs_counted' => $calculation['pasien_bpjs_dihitung'] ?? 0,
                ],
            ],
            'result' => [
                'fee_umum' => $calculation['fee_umum'],
                'fee_bpjs' => $calculation['fee_bpjs'],
                'uang_duduk' => $calculation['uang_duduk'],
                'total' => $calculation['total'],
            ],
            'context' => [
                'calculation_method' => $calculation['calculation_method'],
                'jadwal_jaga_linked' => !is_null($record->jadwal_jaga_id),
                'explicit_formula' => !is_null($record->dokter_umum_jaspel_id),
            ]
        ];
    }
}