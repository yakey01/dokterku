<?php

namespace App\Jobs;

use App\Models\JumlahPasienHarian;
use App\Models\DokterUmumJaspel;
use App\Models\Jaspel;
use App\Models\Shift;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HitungJaspelPasienJob implements ShouldQueue
{
    use Queueable;

    public int $pasienHarianId;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $pasienHarianId)
    {
        $this->pasienHarianId = $pasienHarianId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $pasienHarian = JumlahPasienHarian::find($this->pasienHarianId);
            
            if (!$pasienHarian) {
                Log::warning("JumlahPasienHarian dengan ID {$this->pasienHarianId} tidak ditemukan");
                return;
            }

            if (!$pasienHarian->isApproved()) {
                Log::info("JumlahPasienHarian ID {$this->pasienHarianId} belum disetujui, skip hitung jaspel");
                return;
            }

            // Check if jaspel already exists for this date and doctor
            $existingJaspel = Jaspel::where('user_id', $pasienHarian->dokter->user_id)
                ->whereDate('tanggal', $pasienHarian->tanggal)
                ->where('jenis_jaspel', 'pasien_harian')
                ->exists();

            if ($existingJaspel) {
                Log::info("Jaspel pasien harian untuk dokter ID {$pasienHarian->dokter->user_id} tanggal {$pasienHarian->tanggal} sudah ada");
                return;
            }

            // Get shift information based on current time or set default
            $shift = Shift::where('nama', 'Pagi')->first(); // Default shift
            
            // Calculate jaspel based on patient count and formula using corrected logic
            $jaspelFormula = $this->getJaspelFormula($pasienHarian->poli);
            
            $nominalJaspel = $this->calculateJaspel($pasienHarian, $jaspelFormula);

            if ($nominalJaspel > 0) {
                $totalPasien = $pasienHarian->jumlah_pasien_umum + $pasienHarian->jumlah_pasien_bpjs;
                
                // Create jaspel record
                Jaspel::create([
                    'user_id' => $pasienHarian->dokter->user_id,
                    'tanggal' => $pasienHarian->tanggal,
                    'shift_id' => $shift->id ?? 1,
                    'jenis_jaspel' => 'pasien_harian',
                    'nominal' => $nominalJaspel,
                    'keterangan' => "Jaspel {$pasienHarian->poli} - Total: {$totalPasien} pasien (Umum: {$pasienHarian->jumlah_pasien_umum}, BPJS: {$pasienHarian->jumlah_pasien_bpjs}) | Threshold: {$jaspelFormula->ambang_pasien} | Formula: {$jaspelFormula->jenis_shift}",
                    'status_validasi' => 'approved', // Auto approve jaspel dari validasi bendahara
                    'input_by' => $pasienHarian->validasi_by,
                    'validasi_by' => $pasienHarian->validasi_by,
                    'validasi_at' => now(),
                ]);

                Log::info("Berhasil menghitung jaspel pasien harian untuk dokter ID {$pasienHarian->dokter->user_id}", [
                    'tanggal' => $pasienHarian->tanggal,
                    'total_pasien' => $totalPasien,
                    'nominal_jaspel' => $nominalJaspel
                ]);
            }

        } catch (Exception $e) {
            Log::error("Gagal menghitung jaspel pasien harian untuk ID {$this->pasienHarianId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get jaspel formula based on poli and current time (shift)
     */
    private function getJaspelFormula(string $poli, int $shiftId = null): ?DokterUmumJaspel
    {
        // Determine shift based on current time
        $currentHour = now()->hour;
        $jenisShift = match(true) {
            $currentHour >= 7 && $currentHour < 14 => 'Pagi',
            $currentHour >= 14 && $currentHour < 21 => 'Sore', 
            default => 'Pagi' // Default fallback
        };

        // Get active Jaspel formula for current shift
        $jaspeFormula = DokterUmumJaspel::where('jenis_shift', $jenisShift)
            ->where('status_aktif', true)
            ->first();
        
        // Fallback to any active formula if shift-specific not found
        if (!$jaspeFormula) {
            $jaspeFormula = DokterUmumJaspel::where('status_aktif', true)->first();
        }

        return $jaspeFormula;
    }

    /**
     * Calculate jaspel based on patient count and formula using corrected threshold logic
     */
    private function calculateJaspel(JumlahPasienHarian $pasienHarian, ?DokterUmumJaspel $formula): float
    {
        if (!$formula) {
            Log::warning("Formula jaspel tidak ditemukan untuk pasien harian ID: {$pasienHarian->id}");
            return 0;
        }

        $totalPasien = $pasienHarian->jumlah_pasien_umum + $pasienHarian->jumlah_pasien_bpjs;
        $pasienUmum = $pasienHarian->jumlah_pasien_umum;
        $pasienBpjs = $pasienHarian->jumlah_pasien_bpjs;

        // Check if total patient count meets threshold
        if ($totalPasien <= $formula->ambang_pasien) {
            Log::info("Total pasien ({$totalPasien}) belum mencapai ambang minimum ({$formula->ambang_pasien})");
            return 0;
        }

        // Use the corrected calculation method: check total for threshold, but calculate all individual patients
        $feeUmum = $formula->calculateFeeByTotal($totalPasien, $pasienUmum, 'umum');
        $feeBpjs = $formula->calculateFeeByTotal($totalPasien, $pasienBpjs, 'bpjs');
        $totalFee = $feeUmum + $feeBpjs;

        Log::info("Jaspel calculation details", [
            'total_pasien' => $totalPasien,
            'pasien_umum' => $pasienUmum,
            'pasien_bpjs' => $pasienBpjs,
            'ambang_pasien' => $formula->ambang_pasien,
            'fee_umum' => $feeUmum,
            'fee_bpjs' => $feeBpjs,
            'total_fee' => $totalFee
        ]);

        return $totalFee;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("HitungJaspelPasienJob gagal untuk pasien harian ID {$this->pasienHarianId}: " . $exception->getMessage());
    }
}