<?php

namespace App\Services;

use App\Models\Tindakan;
use App\Models\JumlahPasienHarian;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Procedure-Based Jaspel Calculation Service
 * 
 * Calculates jaspel amounts directly from actual procedures (tindakan + pasien harian)
 * Eliminates discrepancy by using real procedure data instead of manual jaspel table
 */
class ProcedureJaspelCalculationService
{
    /**
     * Calculate jaspel for user based on actual procedures
     */
    public function calculateJaspelFromProcedures(int $userId, array $filters = []): array
    {
        Log::info('ProcedureJaspelCalculation: Calculating from procedures', [
            'user_id' => $userId,
            'filters' => $filters
        ]);

        $user = User::with('role')->find($userId);
        
        if (!$user) {
            return [
                'user_id' => $userId,
                'total_jaspel' => 0,
                'tindakan_jaspel' => 0,
                'pasien_jaspel' => 0,
                'total_tindakan' => 0,
                'error' => 'User not found'
            ];
        }

        // REMOVED: Special case - use real petugas workflow calculation

        // 1. Calculate jaspel from tindakan procedures
        $tindakanJaspel = $this->calculateTindakanJaspel($userId, $filters);
        
        // 2. Calculate jaspel from jumlah pasien harian
        $pasienJaspel = $this->calculatePasienHarianJaspel($userId, $filters);
        
        $totalJaspel = $tindakanJaspel['total'] + $pasienJaspel['total'];
        $totalProcedures = $tindakanJaspel['count'] + $pasienJaspel['count'];

        $result = [
            'user_id' => $userId,
            'user_name' => $user->name,
            'user_role' => $user->role->name ?? 'unknown',
            'total_jaspel' => $totalJaspel,
            'tindakan_jaspel' => $tindakanJaspel['total'],
            'pasien_jaspel' => $pasienJaspel['total'],
            'total_procedures' => $totalProcedures,
            'tindakan_count' => $tindakanJaspel['count'],
            'pasien_days' => $pasienJaspel['count'],
            'calculation_method' => 'procedure_based',
            'last_procedure' => max($tindakanJaspel['last_date'] ?? '', $pasienJaspel['last_date'] ?? ''),
            'breakdown' => [
                'tindakan_procedures' => $tindakanJaspel['details'],
                'pasien_harian_days' => $pasienJaspel['details']
            ]
        ];

        Log::info('ProcedureJaspelCalculation: Calculation completed', [
            'user_id' => $userId,
            'total_jaspel' => $totalJaspel,
            'tindakan_jaspel' => $tindakanJaspel['total'],
            'pasien_jaspel' => $pasienJaspel['total']
        ]);

        return $result;
    }

    /**
     * Calculate jaspel from tindakan procedures
     */
    protected function calculateTindakanJaspel(int $userId, array $filters = []): array
    {
        // FIXED: Map user_id to dokter_id correctly
        $user = User::find($userId);
        if (!$user) {
            return [
                'total' => 0,
                'count' => 0,
                'last_date' => null,
                'details' => []
            ];
        }

        // Find corresponding dokter record
        $dokter = \App\Models\Dokter::where('nama_lengkap', 'like', '%' . $user->name . '%')
            ->orWhere('email', $user->email)
            ->first();

        if (!$dokter) {
            Log::info('ProcedureJaspelCalculation: No matching dokter found for user', [
                'user_id' => $userId,
                'user_name' => $user->name,
                'user_email' => $user->email
            ]);
            return [
                'total' => 0,
                'count' => 0,
                'last_date' => null,
                'details' => []
            ];
        }

        Log::info('ProcedureJaspelCalculation: Found dokter mapping', [
            'user_id' => $userId,
            'dokter_id' => $dokter->id,
            'dokter_name' => $dokter->nama_lengkap
        ]);

        $query = Tindakan::with(['jenisTindakan', 'pasien'])
            ->where('dokter_id', $dokter->id)
            ->where('status_validasi', 'disetujui'); // Only validated tindakans

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->where('tanggal_tindakan', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('tanggal_tindakan', '<=', $filters['date_to']);
        }

        $tindakanRecords = $query->get();

        $totalJaspel = 0;
        $details = [];

        foreach ($tindakanRecords as $tindakan) {
            // Use actual jasa_dokter if available, otherwise calculate as 30% of tarif
            $jaspelAmount = $tindakan->jasa_dokter > 0 ? $tindakan->jasa_dokter : ($tindakan->tarif * 0.30);
            $totalJaspel += $jaspelAmount;
            
            $details[] = [
                'tanggal' => $tindakan->tanggal_tindakan,
                'jenis_tindakan' => $tindakan->jenisTindakan->nama ?? 'Unknown',
                'tarif' => $tindakan->tarif,
                'jaspel' => $jaspelAmount,
                'pasien' => $tindakan->pasien->nama ?? 'Unknown',
                'status' => $tindakan->status,
                'status_validasi' => $tindakan->status_validasi,
                'tindakan_id' => $tindakan->id
            ];
        }

        Log::info('ProcedureJaspelCalculation: Tindakan calculation completed', [
            'user_id' => $userId,
            'dokter_id' => $dokter->id ?? null,
            'tindakan_count' => $tindakanRecords->count(),
            'total_jaspel' => $totalJaspel
        ]);

        return [
            'total' => $totalJaspel,
            'count' => $tindakanRecords->count(),
            'last_date' => $tindakanRecords->max('tanggal_tindakan'),
            'details' => $details
        ];
    }

    /**
     * Calculate jaspel from jumlah pasien harian
     */
    protected function calculatePasienHarianJaspel(int $userId, array $filters = []): array
    {
        // FIXED: Find dokter_id from user_id first, then filter by petugas input
        $user = User::find($userId);
        $dokter = \App\Models\Dokter::where('nama_lengkap', 'like', '%' . $user->name . '%')->first();
        
        if (!$dokter) {
            return [
                'total' => 0,
                'count' => 0,
                'last_date' => null,
                'details' => []
            ];
        }
        
        $query = JumlahPasienHarian::where('dokter_id', $dokter->id)
            ->whereHas('inputBy.role', function ($q) {
                $q->where('name', 'petugas');
            });

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->where('tanggal', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('tanggal', '<=', $filters['date_to']);
        }

        $pasienRecords = $query->get();

        $totalJaspel = 0;
        $details = [];

        foreach ($pasienRecords as $pasien) {
            // Calculate total patients from umum + bpjs columns
            $totalPasien = ($pasien->jumlah_pasien_umum ?? 0) + ($pasien->jumlah_pasien_bpjs ?? 0);
            
            // Use jaspel_rupiah if available, otherwise calculate from patient count
            $jaspelAmount = $pasien->jaspel_rupiah > 0 
                ? $pasien->jaspel_rupiah 
                : ($totalPasien * 2500); // Default Rp 2.500 per patient
            
            $totalJaspel += $jaspelAmount;
            
            $details[] = [
                'tanggal' => $pasien->tanggal,
                'jumlah_pasien' => $totalPasien,
                'pasien_umum' => $pasien->jumlah_pasien_umum ?? 0,
                'pasien_bpjs' => $pasien->jumlah_pasien_bpjs ?? 0,
                'jaspel_rupiah' => $jaspelAmount
            ];
        }

        return [
            'total' => $totalJaspel,
            'count' => $pasienRecords->count(),
            'last_date' => $pasienRecords->max('tanggal'),
            'details' => $details
        ];
    }

    /**
     * Get all users with procedure-based jaspel calculation
     */
    public function getAllUsersWithProcedureJaspel(string $role = 'semua', array $filters = []): array
    {
        // Get all active users with the specified role
        $usersQuery = User::with('role')->where('is_active', true);
        
        if ($role !== 'semua') {
            if ($role === 'dokter') {
                $usersQuery->whereHas('role', function ($q) {
                    $q->whereIn('name', ['dokter', 'dokter_gigi']);
                });
            } else {
                $usersQuery->whereHas('role', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }
        }

        $users = $usersQuery->get();
        $results = [];

        foreach ($users as $user) {
            // Calculate jaspel from procedures for each user
            $calculation = $this->calculateJaspelFromProcedures($user->id, $filters);
            
            // Only include users who have procedures (jaspel > 0)
            if ($calculation['total_jaspel'] > 0) {
                $results[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_name' => $user->role->name ?? 'unknown',
                    'total_jaspel' => $calculation['total_jaspel'],
                    'total_procedures' => $calculation['total_procedures'],
                    'tindakan_jaspel' => $calculation['tindakan_jaspel'],
                    'pasien_jaspel' => $calculation['pasien_jaspel'],
                    'last_procedure' => $calculation['last_procedure'],
                    'calculation_method' => 'procedure_based',
                    'data_source' => 'tindakan_and_pasien_harian'
                ];
            }
        }

        // Sort by total jaspel descending
        usort($results, function ($a, $b) {
            return $b['total_jaspel'] <=> $a['total_jaspel'];
        });

        return $results;
    }

    /**
     * Verify calculation accuracy against manual jaspel table
     */
    public function verifyCalculationAccuracy(int $userId): array
    {
        $procedureCalculation = $this->calculateJaspelFromProcedures($userId);
        
        $manualJaspelSum = DB::table('jaspel')
            ->where('user_id', $userId)
            ->where('status_validasi', 'disetujui')
            ->sum('total_jaspel');

        return [
            'user_id' => $userId,
            'procedure_based' => $procedureCalculation['total_jaspel'],
            'manual_jaspel_table' => $manualJaspelSum,
            'discrepancy' => $manualJaspelSum - $procedureCalculation['total_jaspel'],
            'accuracy_percentage' => $manualJaspelSum > 0 
                ? round(($procedureCalculation['total_jaspel'] / $manualJaspelSum) * 100, 2)
                : 100,
            'recommendation' => $procedureCalculation['total_jaspel'] > 0 
                ? 'Use procedure-based calculation' 
                : 'No procedures found - verify data input'
        ];
    }

    /**
     * Generate summary for all users
     */
    public function generateSystemWideSummary(array $filters = []): array
    {
        $allUsers = $this->getAllUsersWithProcedureJaspel('semua', $filters);
        
        $summary = [
            'total_users' => count($allUsers),
            'total_jaspel_procedure_based' => array_sum(array_column($allUsers, 'total_jaspel')),
            'total_procedures' => array_sum(array_column($allUsers, 'total_procedures')),
            'calculation_method' => 'procedure_based_accurate',
            'data_integrity' => 'verified_from_actual_procedures',
            'users' => $allUsers
        ];

        return $summary;
    }
}