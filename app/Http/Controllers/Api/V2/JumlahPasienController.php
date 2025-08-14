<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JumlahPasienController extends Controller
{
    /**
     * Get Jumlah Pasien data for Jaspel integration
     * This endpoint provides patient count data for doctors' Jaga shifts
     */
    public function getJumlahPasienForJaspel(Request $request)
    {
        try {
            // Support both web session and API token authentication
            $user = Auth::guard('web')->user() ?? Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Verify user has proper role
            if (!$user->hasRole(['dokter', 'admin', 'bendahara', 'petugas'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }
            
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);
            $dokterId = $request->get('dokter_id');
            
            // If dokter role, automatically filter by their dokter_id
            if ($user->hasRole('dokter')) {
                $dokter = Dokter::where('user_id', $user->id)->first();
                if ($dokter) {
                    $dokterId = $dokter->id;
                }
            }
            
            // Build query for JumlahPasienHarian
            $query = JumlahPasienHarian::with(['dokter', 'validasiBy'])
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year);
            
            // IMPORTANT: Only show Bendahara-validated (approved) entries for Jaspel
            // This ensures only verified patient counts are used for payment calculations
            $query->where('status_validasi', 'approved');
            
            // Filter by dokter if specified
            if ($dokterId) {
                $query->where('dokter_id', $dokterId);
            }
            
            // Get the data
            $jumlahPasienData = $query->orderBy('tanggal', 'desc')->get();
            
            // Transform data for Jaspel Jaga integration
            $transformedData = $jumlahPasienData->map(function($item) {
                return [
                    'id' => 'jp_' . $item->id,
                    'tanggal' => $item->tanggal->format('Y-m-d'),
                    'dokter_id' => $item->dokter_id,
                    'dokter_nama' => $item->dokter ? $item->dokter->nama_lengkap : 'Unknown',
                    'poli' => $item->poli,
                    'jumlah_pasien_umum' => $item->jumlah_pasien_umum,
                    'jumlah_pasien_bpjs' => $item->jumlah_pasien_bpjs,
                    'total_pasien' => $item->total_pasien,
                    'status_validasi' => $item->status_validasi,
                    'validasi_by' => $item->validasiBy ? $item->validasiBy->name : null,
                    'validasi_at' => $item->validasi_at ? $item->validasi_at->format('Y-m-d H:i:s') : null,
                    'catatan' => $item->catatan_validasi,
                    
                    // Additional fields for Jaspel Jaga integration
                    'shift' => $this->determineShiftFromPoli($item->poli, $item->tanggal),
                    'jam' => $this->getShiftTimeFromPoli($item->poli),
                    'lokasi' => $this->getLocationFromPoli($item->poli),
                    'jenis_jaga' => 'jaga_' . $item->poli,
                    
                    // Calculate estimated Jaspel based on patient count
                    'estimated_jaspel' => $this->calculateEstimatedJaspel($item->total_pasien, $item->poli),
                    'tarif_base' => $this->getBaseTarif($item->poli),
                    'bonus' => $this->calculateBonus($item->total_pasien),
                ];
            });
            
            // Calculate summary statistics
            $summary = [
                'total_hari_jaga' => $transformedData->count(),
                'total_pasien_bulan_ini' => $transformedData->sum('total_pasien'),
                'rata_rata_pasien' => $transformedData->avg('total_pasien'),
                'total_estimated_jaspel' => $transformedData->sum('estimated_jaspel'),
                'status_breakdown' => [
                    'approved' => $transformedData->where('status_validasi', 'approved')->count(),
                    'pending' => $transformedData->where('status_validasi', 'pending')->count(),
                    'rejected' => $transformedData->where('status_validasi', 'rejected')->count(),
                ],
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Jumlah Pasien data retrieved successfully',
                'data' => [
                    'jumlah_pasien_items' => $transformedData,
                    'summary' => $summary,
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'dokter_id' => $dokterId,
                    'timestamp' => now()->toISOString(),
                    'source' => 'bendahara_validasi_jumlah_pasien',
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jumlah Pasien data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Helper: Determine shift from poli type and date
     */
    private function determineShiftFromPoli($poli, $tanggal)
    {
        // Logic to determine shift based on poli and date
        // This is a simplified version - adjust based on actual business logic
        $dayOfWeek = Carbon::parse($tanggal)->dayOfWeek;
        
        if ($poli === 'umum') {
            return 'Pagi'; // Poli umum usually morning shift
        } elseif ($poli === 'gigi') {
            return $dayOfWeek < 4 ? 'Siang' : 'Pagi'; // Varies by day
        }
        
        return 'Pagi'; // Default
    }
    
    /**
     * Helper: Get shift time from poli
     */
    private function getShiftTimeFromPoli($poli)
    {
        return match($poli) {
            'umum' => '07:00 - 14:00',
            'gigi' => '10:00 - 17:00',
            default => '08:00 - 15:00',
        };
    }
    
    /**
     * Helper: Get location from poli
     */
    private function getLocationFromPoli($poli)
    {
        return match($poli) {
            'umum' => 'Poli Umum - Lantai 1',
            'gigi' => 'Poli Gigi - Lantai 2',
            default => 'Klinik Dokterku',
        };
    }
    
    /**
     * Helper: Calculate estimated Jaspel based on patient count
     */
    private function calculateEstimatedJaspel($totalPasien, $poli)
    {
        // Base tarif per patient varies by poli
        $tarifPerPasien = match($poli) {
            'umum' => 15000,
            'gigi' => 25000,
            default => 15000,
        };
        
        // Progressive bonus for high patient count
        $bonus = 0;
        if ($totalPasien > 50) {
            $bonus = ($totalPasien - 50) * 5000;
        }
        
        return ($totalPasien * $tarifPerPasien) + $bonus;
    }
    
    /**
     * Helper: Get base tarif for poli
     */
    private function getBaseTarif($poli)
    {
        return match($poli) {
            'umum' => 200000,
            'gigi' => 300000,
            default => 200000,
        };
    }
    
    /**
     * Helper: Calculate bonus based on patient count
     */
    private function calculateBonus($totalPasien)
    {
        if ($totalPasien > 100) {
            return 150000;
        } elseif ($totalPasien > 75) {
            return 100000;
        } elseif ($totalPasien > 50) {
            return 50000;
        }
        
        return 0;
    }
}