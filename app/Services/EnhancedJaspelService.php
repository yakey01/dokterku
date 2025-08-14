<?php

namespace App\Services;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\JenisTindakan;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Services\Jaspel\UnifiedJaspelCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnhancedJaspelService
{
    private $unifiedCalculationService;
    
    public function __construct()
    {
        $this->unifiedCalculationService = app(UnifiedJaspelCalculationService::class);
    }
    /**
     * Get comprehensive Jaspel data for user including orphan records
     */
    public function getComprehensiveJaspelData(User $user, $month = null, $year = null, $status = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // WORLD-CLASS SECURITY: Triple-layer user authentication and validation
        if (!$user || !$user->id) {
            throw new \Exception('SECURITY VIOLATION: Invalid user provided to getComprehensiveJaspelData');
        }
        
        // Additional security: Verify user is still active and exists in database
        $currentUser = User::find($user->id);
        if (!$currentUser || !$currentUser->is_active) {
            throw new \Exception('SECURITY VIOLATION: User not found or inactive');
        }

        // SECURITY: Log data access for audit trail
        \Log::info('Comprehensive Jaspel Data Access', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'month' => $month,
            'year' => $year,
            'status_filter' => $status
        ]);

        // NEW: Use UnifiedJaspelCalculationService for consistent calculations
        return $this->getUnifiedJaspelData($user, $month, $year, $status);
    }

    /**
     * Get unified Jaspel data using UnifiedJaspelCalculationService
     */
    private function getUnifiedJaspelData(User $user, $month, $year, $status = null)
    {
        // Find the associated Dokter record
        $dokter = Dokter::where('user_id', $user->id)->first();
        if (!$dokter) {
            // Fallback to legacy method if no Dokter record
            return $this->getLegacyJaspelData($user, $month, $year, $status);
        }

        // Get JumlahPasienHarian records for unified calculation
        $pasienQuery = JumlahPasienHarian::where('dokter_id', $dokter->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->with(['jadwalJaga', 'dokter']);

        if ($status) {
            $pasienQuery->where('status_validasi', $status);
        }

        $pasienRecords = $pasienQuery->get();

        $jaspelItems = [];
        $totalAmount = 0;
        $paidAmount = 0;
        $pendingAmount = 0;
        $rejectedAmount = 0;

        foreach ($pasienRecords as $record) {
            // Use unified calculation service
            if (!$record->jaspel_rupiah || $record->jaspel_rupiah == 0) {
                try {
                    $calculation = $this->unifiedCalculationService->calculateForPasienRecord($record);
                    $record->jaspel_rupiah = $calculation['total'];
                    $record->save();
                    
                    \Log::info('Updated jaspel_rupiah using unified service', [
                        'record_id' => $record->id,
                        'old_value' => 0,
                        'new_value' => $calculation['total'],
                        'calculation_method' => $calculation['calculation_method'] ?? 'unified'
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to calculate jaspel using unified service', [
                        'record_id' => $record->id,
                        'error' => $e->getMessage()
                    ]);
                    // Use fallback calculation
                    $record->jaspel_rupiah = $this->calculateFallbackJaspel($record);
                }
            }

            // Map to frontend format
            $amount = $record->jaspel_rupiah;
            $status = $this->mapStatusToFrontend($record->status_validasi ?? 'pending');
            
            $jaspelItems[] = [
                'id' => $record->id,
                'tanggal' => $record->tanggal->format('Y-m-d'),
                'jumlah' => $amount,
                'pasien_umum' => $record->jumlah_pasien_umum ?? 0,
                'pasien_bpjs' => $record->jumlah_pasien_bpjs ?? 0,
                'jenis_jaspel' => 'jaga_umum',
                'status' => $status,
                'status_validasi' => $record->status_validasi ?? 'pending',
                'keterangan' => "Jaspel jaga tanggal {$record->tanggal->format('d/m/Y')}",
                'calculation_method' => 'unified_service',
                'nominal' => $amount // ✅ FIXED: Use nominal instead of tarif/bonus
            ];

            // Calculate totals
            $totalAmount += $amount;
            switch ($status) {
                case 'paid':
                    $paidAmount += $amount;
                    break;
                case 'pending':
                    $pendingAmount += $amount;
                    break;
                case 'rejected':
                    $rejectedAmount += $amount;
                    break;
            }
        }

        // Also get legacy Jaspel records to merge
        $legacyData = $this->getLegacyJaspelData($user, $month, $year, $status);
        
        // Merge the data
        $allJaspelItems = array_merge($jaspelItems, $legacyData['jaspel_items']);
        
        // Calculate comprehensive summary
        $summary = [
            'total' => $totalAmount + $legacyData['summary']['total'],
            'paid' => $paidAmount + $legacyData['summary']['paid'],
            'pending' => $pendingAmount + $legacyData['summary']['pending'],
            'rejected' => $rejectedAmount + $legacyData['summary']['rejected'],
            'count' => [
                'total' => count($allJaspelItems),
                'paid' => count(array_filter($allJaspelItems, fn($item) => $item['status'] === 'paid')),
                'pending' => count(array_filter($allJaspelItems, fn($item) => $item['status'] === 'pending')),
                'rejected' => count(array_filter($allJaspelItems, fn($item) => $item['status'] === 'rejected'))
            ]
        ];

        return [
            'jaspel_items' => $allJaspelItems,
            'summary' => $summary,
            'counts' => [
                'unified_records' => count($jaspelItems),
                'legacy_records' => count($legacyData['jaspel_items']),
                'total_records' => count($allJaspelItems)
            ]
        ];
    }

    /**
     * Fallback jaspel calculation for records without unified calculation
     */
    private function calculateFallbackJaspel(JumlahPasienHarian $record): float
    {
        // Simple fallback calculation
        $pasienUmum = $record->jumlah_pasien_umum ?? 0;
        $pasienBpjs = $record->jumlah_pasien_bpjs ?? 0;
        
        // Basic rates
        $rateUmum = 30000; // 30k per pasien umum
        $rateBpjs = 25000; // 25k per pasien BPJS
        $uangDuduk = 50000; // Base sitting fee
        
        return ($pasienUmum * $rateUmum) + ($pasienBpjs * $rateBpjs) + $uangDuduk;
    }

    /**
     * Legacy method for backward compatibility
     */
    private function getLegacyJaspelData(User $user, $month, $year, $status = null)
    {

        // Get all Jaspel records (including orphans) - STRICTLY filtered by user_id
        $query = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->with(['tindakan.jenisTindakan', 'tindakan.pasien', 'validasiBy']);

        if ($status) {
            $query->where('status_validasi', $status);
        }

        $jaspelRecords = $query->orderBy('tanggal', 'desc')->get();
        
        // SECURITY: Additional verification that all returned records belong to the user
        $invalidRecords = $jaspelRecords->filter(function($record) use ($user) {
            return $record->user_id !== $user->id;
        });
        
        if ($invalidRecords->isNotEmpty()) {
            \Log::error('Data Leakage Detected in Jaspel Service', [
                'user_id' => $user->id,
                'invalid_record_ids' => $invalidRecords->pluck('id')->toArray(),
                'invalid_user_ids' => $invalidRecords->pluck('user_id')->toArray()
            ]);
            throw new \Exception('Security violation: Invalid user data detected');
        }

        // Get paramedis info for virtual pending calculation
        $paramedis = Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();

        $virtualPendingItems = [];
        
        if ($paramedis) {
            // SECURITY: Verify paramedis belongs to the user
            if ($paramedis->user_id !== $user->id) {
                \Log::error('Paramedis User Mismatch Detected', [
                    'expected_user_id' => $user->id,
                    'paramedis_user_id' => $paramedis->user_id,
                    'paramedis_id' => $paramedis->id
                ]);
                throw new \Exception('Security violation: Paramedis user mismatch');
            }
            
            // Get Tindakan that are pending validation (belum divalidasi bendahara)
            $pendingTindakan = Tindakan::where('paramedis_id', $paramedis->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year)
                ->where('status_validasi', 'pending')
                ->where('jasa_paramedis', '>', 0)
                ->with(['jenisTindakan', 'pasien'])
                ->get();
                
            // SECURITY: Additional verification that all Tindakan belong to this paramedis
            $invalidTindakan = $pendingTindakan->filter(function($tindakan) use ($paramedis) {
                return $tindakan->paramedis_id !== $paramedis->id;
            });
            
            if ($invalidTindakan->isNotEmpty()) {
                \Log::error('Data Leakage Detected in Tindakan Query', [
                    'paramedis_id' => $paramedis->id,
                    'user_id' => $user->id,
                    'invalid_tindakan_ids' => $invalidTindakan->pluck('id')->toArray(),
                    'invalid_paramedis_ids' => $invalidTindakan->pluck('paramedis_id')->toArray()
                ]);
                throw new \Exception('Security violation: Invalid Tindakan data detected');
            }

            foreach ($pendingTindakan as $tindakan) {
                $expectedJaspel = $this->calculateExpectedJaspel($tindakan, 'paramedis');
                
                $virtualPendingItems[] = [
                    'id' => 'virtual_' . $tindakan->id,
                    'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                    'jenis' => $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : 'Tindakan Medis',
                    'jumlah' => (int) $expectedJaspel, // PENTING: jumlah = expected Jaspel (persentase dari tarif)
                    'jenis_jaspel' => 'paramedis', // ✅ ADDED: Jenis jaspel yang benar
                    'status' => 'pending',
                    'keterangan' => 'Menunggu validasi bendahara - ' . 
                        ($tindakan->pasien ? "Pasien: {$tindakan->pasien->nama}" : 'Tindakan pending'),
                    'validated_by' => null,
                    'validated_at' => null,
                    'source' => 'tindakan_pending',
                    'tindakan_id' => $tindakan->id,
                    'tindakan_status' => $tindakan->status_validasi,
                    'nominal' => (int) $expectedJaspel // ✅ ADDED: Nominal untuk compatibility
                ];
            }
            
            // Get approved Tindakan that should generate Jaspel
            $approvedTindakan = Tindakan::where('paramedis_id', $paramedis->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year)
                ->whereIn('status_validasi', ['approved', 'disetujui'])
                ->whereDoesntHave('jaspel', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('jasa_paramedis', '>', 0)
                ->with(['jenisTindakan', 'pasien', 'validatedBy'])
                ->get();

            foreach ($approvedTindakan as $tindakan) {
                $expectedJaspel = $this->calculateExpectedJaspel($tindakan, 'paramedis');
                
                $virtualPendingItems[] = [
                    'id' => 'approved_' . $tindakan->id,
                    'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                    'jenis' => $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : 'Tindakan Medis',
                    'jumlah' => (int) $expectedJaspel, // PENTING: jumlah = expected Jaspel (persentase dari tarif)
                    'jenis_jaspel' => 'paramedis', // ✅ ADDED: Jenis jaspel yang benar
                    'status' => 'paid', // Approved by bendahara = paid
                    'keterangan' => 'Tervalidasi bendahara - ' . 
                        ($tindakan->pasien ? "Pasien: {$tindakan->pasien->nama}" : 'Menunggu generate Jaspel'),
                    'validated_by' => $tindakan->validatedBy ? $tindakan->validatedBy->name : 'Bendahara',
                    'validated_at' => $tindakan->validated_at ? $tindakan->validated_at->format('Y-m-d H:i:s') : null,
                    'source' => 'tindakan_approved',
                    'tindakan_id' => $tindakan->id,
                    'tindakan_status' => $tindakan->status_validasi,
                    'nominal' => (int) $expectedJaspel // ✅ ADDED: Nominal untuk compatibility
                ];
            }
        }

        // Format real Jaspel records
        $formattedRecords = $jaspelRecords->map(function($jaspel) {
            $tindakan = $jaspel->tindakan;
            $jenisTindakan = $tindakan ? $tindakan->jenisTindakan : null;
            $pasien = $tindakan ? $tindakan->pasien : null;

            return [
                'id' => (string) $jaspel->id,
                'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                'jenis' => $jenisTindakan ? $jenisTindakan->nama : 
                          ($jaspel->keterangan ?: 'Jaspel ' . ucwords(str_replace('_', ' ', $jaspel->jenis_jaspel))),
                'jumlah' => (int) $jaspel->nominal, // PENTING: jumlah = nominal (nilai Jaspel)
                'jenis_jaspel' => $jaspel->jenis_jaspel, // ✅ ADDED: Jenis jaspel dari database
                'status' => $this->mapStatusToFrontend($jaspel->status_validasi),
                'keterangan' => $this->generateKeterangan($jaspel, $pasien, $jenisTindakan),
                'validated_by' => $jaspel->validasiBy ? $jaspel->validasiBy->name : null,
                'validated_at' => $jaspel->validasi_at ? $jaspel->validasi_at->format('Y-m-d H:i:s') : null,
                'source' => $jaspel->tindakan_id ? 'tindakan_linked' : 'manual_entry',
                'tindakan_id' => $jaspel->tindakan_id,
                'shift_id' => $jaspel->shift_id, // ✅ ADDED: Shift ID untuk tab jaga
                'nominal' => (int) $jaspel->nominal // ✅ ADDED: Nominal untuk compatibility
            ];
        })->toArray();

        // Merge real and virtual records
        $allRecords = array_merge($formattedRecords, $virtualPendingItems);

        // Calculate comprehensive summaries
        $summaries = $this->calculateComprehensiveSummary($allRecords, $jaspelRecords);

        return [
            'jaspel_items' => $allRecords,
            'summary' => $summaries,
            'counts' => [
                'real_records' => $jaspelRecords->count(),
                'virtual_records' => count($virtualPendingItems),
                'total_records' => count($allRecords)
            ]
        ];
    }

    /**
     * Calculate expected Jaspel amount for a Tindakan
     */
    private function calculateExpectedJaspel(Tindakan $tindakan, string $jaspelType)
    {
        $jenisTindakan = $tindakan->jenisTindakan;
        
        if (!$jenisTindakan) {
            return 0;
        }

        // WORLD-CLASS: Use persentase_jaspel from jenis_tindakan if available (standardized)
        if ($jenisTindakan->persentase_jaspel > 0) {
            return $tindakan->tarif * ($jenisTindakan->persentase_jaspel / 100);
        }

        // Fallback to standard percentages (world-class calculation method)
        return match($jaspelType) {
            'paramedis' => $tindakan->tarif * 0.15, // 15% for paramedis
            'dokter_umum' => $tindakan->tarif * 0.40, // 40% for general doctor
            'dokter_spesialis' => $tindakan->tarif * 0.50, // 50% for specialist
            default => 0
        };
    }

    /**
     * Map database status to frontend status
     */
    private function mapStatusToFrontend(string $dbStatus): string
    {
        return match($dbStatus) {
            'disetujui' => 'paid',
            'pending' => 'pending', 
            'ditolak' => 'rejected',
            default => 'pending'
        };
    }

    /**
     * Generate appropriate keterangan for Jaspel
     */
    private function generateKeterangan($jaspel, $pasien, $jenisTindakan): string
    {
        // If manual entry (no tindakan_id)
        if (!$jaspel->tindakan_id) {
            return $jaspel->keterangan ?: 'Jaspel manual entry';
        }

        // If linked to tindakan
        if ($pasien) {
            return "Pasien: {$pasien->nama}" . 
                   ($jenisTindakan ? " - {$jenisTindakan->nama}" : '');
        }

        return $jenisTindakan ? $jenisTindakan->nama : 'Jaspel medis';
    }

    /**
     * Calculate comprehensive summary including all sources
     */
    private function calculateComprehensiveSummary(array $allRecords, $realJaspelRecords)
    {
        $paidTotal = 0;
        $pendingTotal = 0;
        $rejectedTotal = 0;
        
        $paidCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;

        foreach ($allRecords as $record) {
            switch ($record['status']) {
                case 'paid':
                    $paidTotal += $record['jumlah'];
                    $paidCount++;
                    break;
                case 'pending':
                    $pendingTotal += $record['jumlah'];
                    $pendingCount++;
                    break;
                case 'rejected':
                    $rejectedTotal += $record['jumlah'];
                    $rejectedCount++;
                    break;
            }
        }

        return [
            'total' => $paidTotal + $pendingTotal + $rejectedTotal,
            'paid' => $paidTotal,
            'pending' => $pendingTotal,
            'rejected' => $rejectedTotal,
            'count' => [
                'total' => $paidCount + $pendingCount + $rejectedCount,
                'paid' => $paidCount,
                'pending' => $pendingCount,
                'rejected' => $rejectedCount
            ],
            'breakdown' => [
                'manual_entries' => $realJaspelRecords->whereNull('tindakan_id')->count(),
                'tindakan_linked' => $realJaspelRecords->whereNotNull('tindakan_id')->count(),
                'virtual_pending' => $pendingCount - $realJaspelRecords->where('status_validasi', 'pending')->count()
            ]
        ];
    }

    /**
     * Standardize Jaspel calculation across all systems
     */
    public function getStandardizedJaspelAmount(Tindakan $tindakan, string $jaspelType): float
    {
        $jenisTindakan = $tindakan->jenisTindakan;
        
        if (!$jenisTindakan) {
            return 0;
        }

        // Primary: Use persentase_jaspel from jenis_tindakan table
        if ($jenisTindakan->persentase_jaspel > 0) {
            return $tindakan->tarif * ($jenisTindakan->persentase_jaspel / 100);
        }

        // Secondary: Use standard percentages based on type
        return match($jaspelType) {
            'paramedis' => $tindakan->tarif * 0.15,
            'dokter_umum' => $tindakan->tarif * 0.40,
            'dokter_spesialis' => $tindakan->tarif * 0.50,
            default => 0
        };
    }

    /**
     * Audit Jaspel data consistency
     */
    public function auditJaspelConsistency(User $user, $month = null, $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $issues = [];

        // Check for orphan Jaspel records
        $orphanJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereNull('tindakan_id')
            ->get();

        if ($orphanJaspel->isNotEmpty()) {
            $issues['orphan_records'] = [
                'count' => $orphanJaspel->count(),
                'total_amount' => $orphanJaspel->sum('nominal'),
                'records' => $orphanJaspel->pluck('id')->toArray()
            ];
        }

        // Check for Tindakan without Jaspel
        $paramedis = Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();

        if ($paramedis) {
            // SECURITY: Double-check paramedis ownership
            if ($paramedis->user_id !== $user->id) {
                \Log::error('Audit Method: Paramedis User Mismatch', [
                    'expected_user_id' => $user->id,
                    'paramedis_user_id' => $paramedis->user_id
                ]);
                return [
                    'user_id' => $user->id,
                    'period' => "{$year}-{$month}",
                    'issues' => ['security_error' => 'Paramedis user mismatch in audit'],
                    'audit_time' => now()->toISOString()
                ];
            }
            
            $tindakanWithoutJaspel = Tindakan::where('paramedis_id', $paramedis->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year)
                ->whereIn('status_validasi', ['disetujui', 'approved'])
                ->whereDoesntHave('jaspel', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();

            if ($tindakanWithoutJaspel->isNotEmpty()) {
                $issues['missing_jaspel'] = [
                    'count' => $tindakanWithoutJaspel->count(),
                    'potential_amount' => $tindakanWithoutJaspel->sum(function($tindakan) {
                        return $this->getStandardizedJaspelAmount($tindakan, 'paramedis');
                    }),
                    'tindakan_ids' => $tindakanWithoutJaspel->pluck('id')->toArray()
                ];
            }
        }

        return [
            'user_id' => $user->id,
            'period' => "{$year}-{$month}",
            'issues' => $issues,
            'audit_time' => now()->toISOString()
        ];
    }
}