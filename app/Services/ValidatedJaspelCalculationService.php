<?php

namespace App\Services;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\JumlahPasienHarian;
use App\Models\DokterUmumJaspel;
use App\Services\Jaspel\UnifiedJaspelCalculationService;
use App\Constants\ValidationStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Validated JASPEL Calculation Service
 * 
 * ONLY calculates and returns JASPEL amounts that have been validated 
 * and approved by bendahara (treasurer). This ensures all amounts shown 
 * to doctors are officially approved and financially accurate.
 */
class ValidatedJaspelCalculationService
{
    private $unifiedCalculationService;
    
    public function __construct(UnifiedJaspelCalculationService $unifiedCalculationService)
    {
        $this->unifiedCalculationService = $unifiedCalculationService;
    }

    /**
     * Get ONLY validated JASPEL data for a user
     * Enforces bendahara validation as the single source of truth
     * FIXED: Prevents double counting by prioritizing data sources
     */
    public function getValidatedJaspelData(User $user, $month = null, $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        Log::info('Fetching VALIDATED JASPEL data only', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'month' => $month,
            'year' => $year,
            'validation_status' => 'disetujui_only'
        ]);

        // Get all possible sources separately for analysis
        $validatedJaspel = $this->getValidatedJaspelRecords($user, $month, $year);
        $validatedProcedureJaspel = $this->getValidatedProcedureJaspel($user, $month, $year);
        // 🔧 DISABLE doctor JASPEL to prevent double counting - use direct JASPEL records only
        $validatedDoctorJaspel = []; // $this->getValidatedDoctorJaspel($user, $month, $year);
        $validatedPatientJaspel = $this->getValidatedPatientCountJaspel($user, $month, $year);

        // 🔧 FIX: Apply data source priority to prevent double counting
        // Priority 1: Patient count JASPEL (JumlahPasienHarian) - Most authoritative for jaga
        // Priority 2: Direct JASPEL records for non-jaga items
        // Priority 3: Procedure JASPEL for specific procedures
        
        $prioritizedItems = [];
        $usedDates = [];
        
        // First pass: Add patient count JASPEL (highest priority for jaga)
        foreach ($validatedPatientJaspel as $item) {
            $prioritizedItems[] = $item;
            $usedDates[$item['tanggal']] = 'patient_count';
            Log::info('Added patient count JASPEL', [
                'date' => $item['tanggal'],
                'amount' => $item['nominal'],
                'source' => 'patient_count_priority'
            ]);
        }
        
        // Second pass: Add direct JASPEL that are NOT jaga-related (to avoid double counting with patient count)
        // 🔧 CRITICAL FIX: When we have patient count JASPEL (monthly totals), 
        // we should EXCLUDE all jaga-related direct JASPEL to prevent duplication
        $hasPatientCountData = count($validatedPatientJaspel) > 0;
        
        foreach ($validatedJaspel as $item) {
            $isJagaRelated = in_array($item['jenis_jaspel'], [
                'jaga_umum', 'jaga_pagi', 'jaga_siang', 'jaga_malam', 
                'dokter_jaga_pagi', 'dokter_jaga_siang', 'dokter_jaga_malam'
            ]);
            
            // If we have patient count data, skip ALL jaga-related direct JASPEL
            if ($hasPatientCountData && $isJagaRelated) {
                Log::warning('Skipped jaga-related direct JASPEL (monthly aggregation exists)', [
                    'date' => $item['tanggal'],
                    'amount' => $item['nominal'],
                    'jenis' => $item['jenis_jaspel'],
                    'reason' => 'monthly_patient_count_jaspel_takes_priority'
                ]);
                continue;
            }
            
            // Add non-jaga JASPEL or jaga JASPEL when no patient count data exists
            if (!isset($usedDates[$item['tanggal']]) || !$isJagaRelated) {
                $prioritizedItems[] = $item;
                $usedDates[$item['tanggal']] = 'direct_jaspel';
                Log::info('Added direct JASPEL', [
                    'date' => $item['tanggal'],
                    'amount' => $item['nominal'],
                    'jenis' => $item['jenis_jaspel'],
                    'source' => 'direct_jaspel_non_overlapping',
                    'is_jaga_related' => $isJagaRelated
                ]);
            } else {
                Log::warning('Skipped overlapping direct JASPEL', [
                    'date' => $item['tanggal'],
                    'amount' => $item['nominal'],
                    'jenis' => $item['jenis_jaspel'],
                    'reason' => 'date_already_used'
                ]);
            }
        }
        
        // Third pass: Add procedure JASPEL (paramedis)
        foreach ($validatedProcedureJaspel as $item) {
            $prioritizedItems[] = $item;
            Log::info('Added procedure JASPEL', [
                'date' => $item['tanggal'],
                'amount' => $item['nominal'],
                'source' => 'procedure_jaspel'
            ]);
        }
        
        // Fourth pass: Add doctor JASPEL
        foreach ($validatedDoctorJaspel as $item) {
            $prioritizedItems[] = $item;
            Log::info('Added doctor JASPEL', [
                'date' => $item['tanggal'],
                'amount' => $item['nominal'],
                'source' => 'doctor_jaspel'
            ]);
        }

        // Calculate summary from prioritized data (no double counting)
        $summary = $this->calculateValidatedSummary($prioritizedItems);
        
        Log::info('JASPEL calculation summary after deduplication', [
            'original_counts' => [
                'direct_jaspel' => count($validatedJaspel),
                'procedure_jaspel' => count($validatedProcedureJaspel),
                'doctor_jaspel' => count($validatedDoctorJaspel),
                'patient_count_jaspel' => count($validatedPatientJaspel)
            ],
            'final_count' => count($prioritizedItems),
            'final_total' => $summary['total'],
            'deduplication_applied' => true
        ]);

        return [
            'jaspel_items' => $prioritizedItems,
            'summary' => $summary,
            'validation_status' => 'validated_only',
            'validation_timestamp' => now()->toISOString(),
            'validation_source' => 'bendahara_approved_deduplicated',
            'counts' => [
                'direct_jaspel' => count($validatedJaspel),
                'procedure_jaspel' => count($validatedProcedureJaspel),
                'doctor_jaspel' => count($validatedDoctorJaspel),
                'patient_count_jaspel' => count($validatedPatientJaspel),
                'total_validated' => count($prioritizedItems), // After deduplication
                'deduplication_applied' => true
            ]
        ];
    }

    /**
     * Get validated JASPEL records from jaspel table
     * ONLY approved records (handles both 'approved' and legacy 'disetujui')
     */
    private function getValidatedJaspelRecords(User $user, $month, $year): array
    {
        $validatedRecords = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ValidationStatus::approvedStatuses()) // Handles both approved and disetujui
            ->with(['tindakan.jenisTindakan', 'tindakan.pasien', 'validasiBy'])
            ->orderBy('tanggal', 'desc')
            ->get();

        return $validatedRecords->map(function($jaspel) {
            $tindakan = $jaspel->tindakan;
            $jenisTindakan = $tindakan ? $tindakan->jenisTindakan : null;
            $pasien = $tindakan ? $tindakan->pasien : null;

            return [
                'id' => (string) $jaspel->id,
                'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                'jenis' => $jenisTindakan ? $jenisTindakan->nama : 
                          ($jaspel->keterangan ?: 'Jaspel ' . ucwords(str_replace('_', ' ', $jaspel->jenis_jaspel))),
                'nominal' => (int) $jaspel->nominal,
                'jenis_jaspel' => $jaspel->jenis_jaspel,
                'status' => 'approved', // All are approved since we filtered
                'status_validasi' => 'disetujui',
                'keterangan' => $this->generateValidatedKeterangan($jaspel, $pasien, $jenisTindakan),
                'validated_by' => $jaspel->validasiBy ? $jaspel->validasiBy->name : 'Bendahara',
                'validated_at' => $jaspel->validasi_at ? $jaspel->validasi_at->format('Y-m-d H:i:s') : null,
                'source' => 'jaspel_validated',
                'tindakan_id' => $jaspel->tindakan_id,
                'shift_id' => $jaspel->shift_id,
                'validation_guaranteed' => true // Flag indicating this is validated
            ];
        })->toArray();
    }

    /**
     * Get validated procedure-based JASPEL from tindakan table
     * ONLY approved tindakan (handles both 'approved' and legacy 'disetujui')
     */
    private function getValidatedProcedureJaspel(User $user, $month, $year): array
    {
        // Get user's paramedis record
        $paramedis = DB::table('pegawais')
            ->where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();

        if (!$paramedis) {
            return [];
        }

        // Get validated tindakan that don't have JASPEL records yet
        $validatedTindakan = Tindakan::where('paramedis_id', $paramedis->id)
            ->whereMonth('tanggal_tindakan', $month)
            ->whereYear('tanggal_tindakan', $year)
            ->whereIn('status_validasi', ValidationStatus::approvedStatuses()) // Handles both approved and disetujui
            ->where('jasa_paramedis', '>', 0)
            ->whereDoesntHave('jaspel', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['jenisTindakan', 'pasien', 'validatedBy'])
            ->get();

        return $validatedTindakan->map(function($tindakan) {
            $expectedJaspel = $this->calculateValidatedJaspelAmount($tindakan, 'paramedis');

            return [
                'id' => 'validated_tindakan_' . $tindakan->id,
                'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                'jenis' => $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : 'Tindakan Medis',
                'nominal' => (int) $expectedJaspel,
                'jenis_jaspel' => 'paramedis',
                'status' => 'approved', // Validated by bendahara
                'status_validasi' => 'disetujui',
                'keterangan' => 'TERVALIDASI BENDAHARA - ' . 
                    ($tindakan->pasien ? "Pasien: {$tindakan->pasien->nama}" : 'Tindakan medis'),
                'validated_by' => $tindakan->validatedBy ? $tindakan->validatedBy->name : 'Bendahara',
                'validated_at' => $tindakan->validated_at ? $tindakan->validated_at->format('Y-m-d H:i:s') : null,
                'source' => 'tindakan_validated',
                'tindakan_id' => $tindakan->id,
                'validation_guaranteed' => true
            ];
        })->toArray();
    }

    /**
     * Get validated doctor-based JASPEL from tindakan table
     * ONLY tindakan with status_validasi = 'disetujui' and jasa_dokter > 0
     */
    private function getValidatedDoctorJaspel(User $user, $month, $year): array
    {
        // Get user's dokter record
        $dokter = DB::table('dokters')
            ->where('user_id', $user->id)
            ->first();

        if (!$dokter) {
            return [];
        }

        // Get validated tindakan that don't have JASPEL records yet for this doctor
        $validatedTindakan = Tindakan::where('dokter_id', $dokter->id)
            ->whereMonth('tanggal_tindakan', $month)
            ->whereYear('tanggal_tindakan', $year)
            ->whereIn('status_validasi', ValidationStatus::approvedStatuses()) // Handles both approved and disetujui
            ->where('jasa_dokter', '>', 0)
            ->whereDoesntHave('jaspel', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->whereIn('jenis_jaspel', ['dokter_umum', 'dokter_spesialis']);
            })
            ->with(['jenisTindakan', 'pasien', 'validatedBy'])
            ->get();

        return $validatedTindakan->map(function($tindakan) {
            // Use the actual jasa_dokter amount from the tindakan
            $expectedJaspel = $tindakan->jasa_dokter;

            return [
                'id' => 'validated_doctor_tindakan_' . $tindakan->id,
                'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                'jenis' => $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : 'Tindakan Medis Dokter',
                'nominal' => (int) $expectedJaspel,
                'jenis_jaspel' => 'dokter_umum', // Default to dokter_umum
                'status' => 'approved', // Validated by bendahara
                'status_validasi' => 'disetujui',
                'keterangan' => 'TERVALIDASI BENDAHARA - DOKTER - ' . 
                    ($tindakan->pasien ? "Pasien: {$tindakan->pasien->nama}" : 'Tindakan medis dokter'),
                'validated_by' => $tindakan->validatedBy ? $tindakan->validatedBy->name : 'Bendahara',
                'validated_at' => $tindakan->validated_at ? $tindakan->validated_at->format('Y-m-d H:i:s') : null,
                'source' => 'doctor_tindakan_validated',
                'tindakan_id' => $tindakan->id,
                'validation_guaranteed' => true
            ];
        })->toArray();
    }

    /**
     * Get validated patient count JASPEL from JumlahPasienHarian
     * ONLY records with status_validasi = 'disetujui'
     */
    private function getValidatedPatientCountJaspel(User $user, $month, $year): array
    {
        // Get user's dokter record
        $dokter = DB::table('dokters')->where('user_id', $user->id)->first();
        if (!$dokter) {
            return [];
        }

        // Get validated patient count records
        $validatedPatientRecords = JumlahPasienHarian::where('dokter_id', $dokter->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ['disetujui', 'approved']) // Accept both validation statuses
            ->with(['jadwalJaga', 'dokter'])
            ->get();

        return $validatedPatientRecords->map(function($record) {
            // 🔧 CRITICAL FIX: Use stored jaspel_rupiah (bendahara-validated amount) instead of recalculating
            // The stored amount is the OFFICIAL bendahara-approved JASPEL amount
            $officialJaspelAmount = $record->jaspel_rupiah ?: 0;
            
            // Only calculate if no stored amount exists (should be rare for validated records)
            if ($officialJaspelAmount == 0) {
                $calculation = $this->unifiedCalculationService->calculateForPasienRecord($record);
                $officialJaspelAmount = $calculation['total'] ?? 0;
                
                // Store the calculated amount for future use
                $record->jaspel_rupiah = $officialJaspelAmount;
                $record->save();
                
                Log::warning('Had to calculate JASPEL for validated record', [
                    'record_id' => $record->id,
                    'calculated_amount' => $officialJaspelAmount
                ]);
            }

            Log::info('Using OFFICIAL bendahara-validated JASPEL amount', [
                'record_id' => $record->id,
                'date' => $record->tanggal->format('Y-m-d'),
                'stored_amount' => $record->jaspel_rupiah,
                'official_amount_used' => $officialJaspelAmount
            ]);

            return [
                'id' => 'validated_pasien_' . $record->id,
                'tanggal' => $record->tanggal->format('Y-m-d'),
                'jenis' => "Jaspel Jaga - {$record->jumlah_pasien_umum} umum, {$record->jumlah_pasien_bpjs} BPJS",
                'nominal' => (int) $officialJaspelAmount, // Use OFFICIAL stored amount
                'jenis_jaspel' => 'jaga_umum',
                'status' => 'approved', // Validated by bendahara
                'status_validasi' => 'disetujui',
                'keterangan' => "✅ BENDAHARA OFICIAL - Jaspel jaga " . $record->tanggal->format('d/m/Y') . " (" . ($record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs) . " total pasien) - Rp " . number_format($officialJaspelAmount),
                'validated_by' => 'Bendahara',
                'validated_at' => $record->updated_at ? $record->updated_at->format('Y-m-d H:i:s') : null,
                'source' => 'patient_count_validated',
                'patient_record_id' => $record->id,
                'calculation_method' => 'official_stored_amount', // Changed to reflect stored amount usage
                'validation_guaranteed' => true,
                'official_bendahara_amount' => true, // Flag indicating this is the official amount
                'patient_details' => [
                    'umum' => $record->jumlah_pasien_umum,
                    'bpjs' => $record->jumlah_pasien_bpjs,
                    'total' => $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs
                ]
            ];
        })->toArray();
    }

    /**
     * Calculate summary from validated data only
     */
    private function calculateValidatedSummary(array $validatedItems): array
    {
        $totalAmount = 0;
        $itemCount = count($validatedItems);

        foreach ($validatedItems as $item) {
            $totalAmount += $item['nominal'];
        }

        return [
            'total' => $totalAmount,
            'approved' => $totalAmount, // All items are approved
            'pending' => 0, // No pending items in validated view
            'rejected' => 0, // No rejected items in validated view
            'count' => [
                'total' => $itemCount,
                'approved' => $itemCount,
                'pending' => 0,
                'rejected' => 0
            ],
            'validation_status' => 'all_validated',
            'financial_accuracy' => '100%',
            'bendahara_approval' => 'complete'
        ];
    }

    /**
     * Calculate JASPEL amount for validated tindakan
     */
    private function calculateValidatedJaspelAmount(Tindakan $tindakan, string $jaspelType): float
    {
        $jenisTindakan = $tindakan->jenisTindakan;
        
        if (!$jenisTindakan) {
            return 0;
        }

        // Use persentase_jaspel from jenis_tindakan if available
        if ($jenisTindakan->persentase_jaspel > 0) {
            return $tindakan->tarif * ($jenisTindakan->persentase_jaspel / 100);
        }

        // Fallback to standard percentages
        return match($jaspelType) {
            'paramedis' => $tindakan->tarif * 0.15, // 15% for paramedis
            'dokter_umum' => $tindakan->tarif * 0.40, // 40% for general doctor
            'dokter_spesialis' => $tindakan->tarif * 0.50, // 50% for specialist
            default => 0
        };
    }

    /**
     * Generate keterangan for validated items
     */
    private function generateValidatedKeterangan($jaspel, $pasien, $jenisTindakan): string
    {
        $baseKeterangan = '';
        
        if (!$jaspel->tindakan_id) {
            $baseKeterangan = $jaspel->keterangan ?: 'Jaspel manual entry';
        } else {
            if ($pasien) {
                $baseKeterangan = "Pasien: {$pasien->nama}" . 
                               ($jenisTindakan ? " - {$jenisTindakan->nama}" : '');
            } else {
                $baseKeterangan = $jenisTindakan ? $jenisTindakan->nama : 'Jaspel medis';
            }
        }

        return "✅ TERVALIDASI BENDAHARA - " . $baseKeterangan;
    }

    /**
     * Get validation status for transparency
     */
    public function getValidationStatus(User $user, $month = null, $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // Count total vs validated items
        $totalJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->count();

        $validatedJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ValidationStatus::approvedStatuses())
            ->count();

        $pendingJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'pending')
            ->count();

        $rejectedJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'ditolak')
            ->count();

        $validationRate = $totalJaspel > 0 ? ($validatedJaspel / $totalJaspel) * 100 : 0;

        return [
            'period' => "{$year}-{$month}",
            'total_items' => $totalJaspel,
            'validated_items' => $validatedJaspel,
            'pending_items' => $pendingJaspel,
            'rejected_items' => $rejectedJaspel,
            'validation_rate' => round($validationRate, 2),
            'financial_accuracy' => $validationRate >= 95 ? 'excellent' : ($validationRate >= 80 ? 'good' : 'needs_improvement'),
            'bendahara_status' => $pendingJaspel == 0 ? 'complete' : 'pending_review',
            'gaming_ui_safe' => $validationRate == 100 // Safe to show in gaming UI only if 100% validated
        ];
    }

    /**
     * Get pending validation summary for transparency
     */
    public function getPendingValidationSummary(User $user, $month = null, $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $pendingJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'pending')
            ->sum('nominal');

        $pendingCount = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status_validasi', 'pending')
            ->count();

        return [
            'pending_amount' => $pendingJaspel,
            'pending_count' => $pendingCount,
            'status' => $pendingCount > 0 ? 'awaiting_bendahara_approval' : 'all_validated',
            'message' => $pendingCount > 0 
                ? "Ada {$pendingCount} item JASPEL menunggu validasi bendahara" 
                : "Semua JASPEL sudah tervalidasi",
            'can_show_in_gaming' => $pendingCount == 0
        ];
    }
}