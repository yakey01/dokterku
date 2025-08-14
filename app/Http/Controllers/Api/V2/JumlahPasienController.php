<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Models\DokterUmumJaspel;
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
                    
                    // ✅ DYNAMIC: Additional fields for Jaspel Jaga integration from database
                    ...$this->getDynamicShiftData($item),
                    'jenis_jaga' => 'jaga_' . $item->poli,
                    
                    // ✅ UNIFIED: Use Bendahara calculation logic via jaspel_rupiah field
                    'estimated_jaspel' => $item->jaspel_rupiah ?? $this->calculateJaspelWithBendaharaLogic($item),
                    'tarif_base' => $this->getBendaharaBaseTarif($item->poli),
                    'bonus' => 0, // Bonus included in Bendahara calculation
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
     * ✅ DYNAMIC: Get shift data from database based on JumlahPasienHarian item
     */
    private function getDynamicShiftData($jumlahPasienItem)
    {
        // Try to get from jadwalJaga relationship first
        if ($jumlahPasienItem->jadwalJaga && $jumlahPasienItem->jadwalJaga->shiftTemplate) {
            $shiftTemplate = $jumlahPasienItem->jadwalJaga->shiftTemplate;
            return [
                'shift' => $shiftTemplate->nama_shift,
                'jam' => $shiftTemplate->jam_masuk . ' - ' . $shiftTemplate->jam_pulang,
                'lokasi' => $this->getLocationFromPoli($jumlahPasienItem->poli),
            ];
        }
        
        // Fallback: Try to find matching JadwalJaga by date, dokter, and poli logic
        $jadwalJaga = \App\Models\JadwalJaga::with('shiftTemplate')
            ->whereDate('tanggal_jaga', $jumlahPasienItem->tanggal)
            ->whereHas('pegawai', function($query) use ($jumlahPasienItem) {
                // Try to find jadwal for the same doctor
                $query->whereHas('dokter', function($q) use ($jumlahPasienItem) {
                    $q->where('id', $jumlahPasienItem->dokter_id);
                });
            })
            ->first();
            
        if ($jadwalJaga && $jadwalJaga->shiftTemplate) {
            return [
                'shift' => $jadwalJaga->shiftTemplate->nama_shift,
                'jam' => $jadwalJaga->shiftTemplate->jam_masuk . ' - ' . $jadwalJaga->shiftTemplate->jam_pulang,
                'lokasi' => $this->getLocationFromPoli($jumlahPasienItem->poli),
            ];
        }
        
        // Final fallback: Use stored shift field with dynamic ShiftTemplate lookup
        if ($jumlahPasienItem->shift) {
            $shiftTemplate = \App\Models\ShiftTemplate::where('nama_shift', $jumlahPasienItem->shift)->first();
            if ($shiftTemplate) {
                return [
                    'shift' => $shiftTemplate->nama_shift,
                    'jam' => $shiftTemplate->jam_masuk . ' - ' . $shiftTemplate->jam_pulang,
                    'lokasi' => $this->getLocationFromPoli($jumlahPasienItem->poli),
                ];
            }
        }
        
        // Ultimate fallback: Intelligent poli-based shift mapping
        return $this->getIntelligentPoliShiftMapping($jumlahPasienItem->poli);
    }
    
    /**
     * ✅ DYNAMIC: Intelligent poli-to-shift mapping based on database
     */
    private function getIntelligentPoliShiftMapping($poli)
    {
        // Get common shift templates from database
        $shiftTemplates = \App\Models\ShiftTemplate::whereIn('nama_shift', ['Pagi', 'Sore', 'Siang'])
            ->get()
            ->keyBy('nama_shift');
        
        $defaultShift = match($poli) {
            'umum' => $shiftTemplates->get('Pagi') ?? $shiftTemplates->get('Siang'),
            'gigi' => $shiftTemplates->get('Siang') ?? $shiftTemplates->get('Sore'),
            default => $shiftTemplates->get('Pagi') ?? $shiftTemplates->first(),
        };
        
        if ($defaultShift) {
            return [
                'shift' => $defaultShift->nama_shift,
                'jam' => $defaultShift->jam_masuk . ' - ' . $defaultShift->jam_pulang,
                'lokasi' => $this->getLocationFromPoli($poli),
            ];
        }
        
        // Hard fallback if no database data
        return [
            'shift' => 'Pagi',
            'jam' => '08:00 - 15:00',
            'lokasi' => 'Klinik Dokterku',
        ];
    }
    
    /**
     * ✅ DYNAMIC: Get location with potential future database integration
     */
    private function getLocationFromPoli($poli)
    {
        // TODO: Could be moved to database configuration table in future
        return match($poli) {
            'umum' => 'Poli Umum - Lantai 1',
            'gigi' => 'Poli Gigi - Lantai 2',
            default => 'Klinik Dokterku',
        };
    }
    
    /**
     * ✅ UNIFIED: Calculate Jaspel using Bendahara system logic (threshold-based)
     */
    private function calculateJaspelWithBendaharaLogic($jumlahPasienItem)
    {
        // Get active formula from DokterUmumJaspel (same as Bendahara system)
        $formula = $this->getActiveDokterUmumJaspelFormula($jumlahPasienItem->poli);
        
        if (!$formula) {
            // Fallback to old calculation if no formula found
            return $this->calculateEstimatedJaspelFallback($jumlahPasienItem->total_pasien, $jumlahPasienItem->poli);
        }

        $totalPasien = $jumlahPasienItem->total_pasien;
        $jumlahPasienUmum = $jumlahPasienItem->jumlah_pasien_umum;
        $jumlahPasienBpjs = $jumlahPasienItem->jumlah_pasien_bpjs;

        // ✅ BENDAHARA LOGIC: Threshold-based calculation
        if ($totalPasien <= $formula->ambang_pasien) {
            return $formula->uang_duduk; // Hanya dapat uang duduk jika belum mencapai threshold
        }

        // Hitung pasien yang melebihi threshold
        $totalPasienDihitung = $totalPasien - $formula->ambang_pasien;
        
        // Hitung proporsi berdasarkan jenis pasien
        $proporsiUmum = $totalPasien > 0 ? $jumlahPasienUmum / $totalPasien : 0;
        $proporsiBpjs = $totalPasien > 0 ? $jumlahPasienBpjs / $totalPasien : 0;

        $pasienUmumDihitung = round($totalPasienDihitung * $proporsiUmum);
        $pasienBpjsDihitung = round($totalPasienDihitung * $proporsiBpjs);

        $feeUmum = $pasienUmumDihitung * $formula->fee_pasien_umum;
        $feeBpjs = $pasienBpjsDihitung * $formula->fee_pasien_bpjs;

        // Total = uang duduk + fee berdasarkan pasien yang melebihi threshold
        return $formula->uang_duduk + $feeUmum + $feeBpjs;
    }
    
    /**
     * Get active DokterUmumJaspel formula for calculation
     */
    private function getActiveDokterUmumJaspelFormula($poli)
    {
        // Map poli to shift (simplified logic - adjust based on business rules)
        $shift = match($poli) {
            'umum' => 'Pagi',
            'gigi' => 'Sore',
            default => 'Pagi',
        };
        
        return DokterUmumJaspel::where('jenis_shift', $shift)
            ->where('status_aktif', true)
            ->first();
    }
    
    /**
     * Fallback calculation if no formula found
     */
    private function calculateEstimatedJaspelFallback($totalPasien, $poli)
    {
        // Simplified fallback - basic calculation
        $tarifPerPasien = match($poli) {
            'umum' => 7000, // Sesuai dengan tarif Bendahara
            'gigi' => 10000,
            default => 7000,
        };
        
        // Assume threshold 10 and uang duduk 200k (default values)
        if ($totalPasien <= 10) {
            return 200000;
        }
        
        return 200000 + (($totalPasien - 10) * $tarifPerPasien);
    }
    
    /**
     * ✅ UNIFIED: Get base tarif using Bendahara system (uang_duduk)
     */
    private function getBendaharaBaseTarif($poli)
    {
        $formula = $this->getActiveDokterUmumJaspelFormula($poli);
        
        if ($formula) {
            return $formula->uang_duduk;
        }
        
        // Fallback to standard uang duduk
        return match($poli) {
            'umum' => 200000,
            'gigi' => 300000,
            default => 200000,
        };
    }
}