<?php

namespace App\Rules;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

/**
 * Validation rule for JASPEL entries
 * 
 * Ensures JASPEL entries follow business rules and data integrity constraints.
 * Prevents creation of invalid or dummy-like data.
 */
class ValidJaspelEntry implements Rule
{
    private $field;
    private $context;
    private $failureMessage = '';

    public function __construct(string $field = 'general', array $context = [])
    {
        $this->field = $field;
        $this->context = $context;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        switch ($this->field) {
            case 'nominal':
                return $this->validateNominal($value);
            case 'jenis_jaspel':
                return $this->validateJenisJaspel($value);
            case 'tindakan_id':
                return $this->validateTindakanId($value);
            case 'tanggal':
                return $this->validateTanggal($value);
            case 'duplicate':
                return $this->validateNoDuplicate();
            case 'business_rules':
                return $this->validateBusinessRules($value);
            default:
                return $this->validateGeneral($value);
        }
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return $this->failureMessage ?: 'The :attribute field contains invalid data.';
    }

    /**
     * Validate nominal amount
     */
    private function validateNominal($nominal): bool
    {
        // Rule 1: Must be positive
        if ($nominal <= 0) {
            $this->failureMessage = 'Nominal JASPEL harus lebih besar dari 0.';
            return false;
        }

        // Rule 2: Must not be suspiciously high
        if ($nominal > 10000000) { // > 10 million
            $this->failureMessage = 'Nominal JASPEL terlalu tinggi (maksimal Rp 10,000,000).';
            return false;
        }

        // Rule 3: Check for round number patterns (potential dummy data)
        if ($this->isPotentialDummyAmount($nominal)) {
            $this->failureMessage = 'Nominal JASPEL terdeteksi sebagai data dummy. Gunakan nominal yang lebih presisi.';
            return false;
        }

        // Rule 4: If linked to tindakan, validate against tarif
        if (isset($this->context['tindakan_id']) && $this->context['tindakan_id']) {
            return $this->validateNominalAgainstTindakan($nominal, $this->context['tindakan_id']);
        }

        return true;
    }

    /**
     * Validate jenis_jaspel
     */
    private function validateJenisJaspel($jenisJaspel): bool
    {
        $validTypes = [
            'dokter_jaga_pagi',
            'dokter_jaga_siang', 
            'dokter_jaga_malam',
            'tindakan_emergency',
            'konsultasi_khusus',
            'paramedis',
            'dokter_umum',
            'dokter_spesialis'
        ];

        if (!in_array($jenisJaspel, $validTypes)) {
            $this->failureMessage = 'Jenis JASPEL tidak valid. Pilih dari: ' . implode(', ', $validTypes);
            return false;
        }

        // Special validation for konsultasi_khusus
        if ($jenisJaspel === 'konsultasi_khusus') {
            return $this->validateKonsultasiKhusus();
        }

        return true;
    }

    /**
     * Validate tindakan_id reference
     */
    private function validateTindakanId($tindakanId): bool
    {
        if (!$tindakanId) {
            return true; // NULL is allowed for manual entries
        }

        // Check if tindakan exists
        $tindakan = Tindakan::find($tindakanId);
        if (!$tindakan) {
            $this->failureMessage = 'Tindakan dengan ID tersebut tidak ditemukan.';
            return false;
        }

        // Check if tindakan is validated
        if ($tindakan->status_validasi !== 'disetujui') {
            $this->failureMessage = 'Tindakan harus divalidasi terlebih dahulu sebelum membuat JASPEL.';
            return false;
        }

        // Check if JASPEL already exists for this tindakan
        $existingJaspel = Jaspel::where('tindakan_id', $tindakanId)
            ->where('user_id', $this->context['user_id'] ?? null)
            ->first();

        if ($existingJaspel) {
            $this->failureMessage = 'JASPEL untuk tindakan ini sudah ada (ID: ' . $existingJaspel->id . ').';
            return false;
        }

        return true;
    }

    /**
     * Validate tanggal
     */
    private function validateTanggal($tanggal): bool
    {
        $date = Carbon::parse($tanggal);
        $now = Carbon::now();

        // Rule 1: Not too far in the past
        if ($date->lt($now->copy()->subYear())) {
            $this->failureMessage = 'Tanggal JASPEL tidak boleh lebih dari 1 tahun yang lalu.';
            return false;
        }

        // Rule 2: Not in the future (with small tolerance)
        if ($date->gt($now->addDays(7))) {
            $this->failureMessage = 'Tanggal JASPEL tidak boleh lebih dari 7 hari ke depan.';
            return false;
        }

        return true;
    }

    /**
     * Validate no duplicate entries
     */
    private function validateNoDuplicate(): bool
    {
        $query = Jaspel::where('user_id', $this->context['user_id'] ?? null)
            ->where('tanggal', $this->context['tanggal'] ?? null)
            ->where('jenis_jaspel', $this->context['jenis_jaspel'] ?? null)
            ->where('nominal', $this->context['nominal'] ?? null);

        // Exclude current record if updating
        if (isset($this->context['exclude_id'])) {
            $query->where('id', '!=', $this->context['exclude_id']);
        }

        $duplicate = $query->first();

        if ($duplicate) {
            $this->failureMessage = 'Entry JASPEL yang sama sudah ada (ID: ' . $duplicate->id . ').';
            return false;
        }

        return true;
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules($value): bool
    {
        // Rule 1: Manual konsultasi_khusus entries need justification
        if (($this->context['jenis_jaspel'] ?? '') === 'konsultasi_khusus' && 
            !($this->context['tindakan_id'] ?? null)) {
            
            if (empty($this->context['catatan_validasi']) || 
                strlen($this->context['catatan_validasi']) < 20) {
                $this->failureMessage = 'Entry konsultasi_khusus manual memerlukan catatan validasi minimal 20 karakter.';
                return false;
            }
        }

        // Rule 2: Large amounts need additional validation
        if (($this->context['nominal'] ?? 0) > 1000000) { // > 1 million
            if (empty($this->context['catatan_validasi'])) {
                $this->failureMessage = 'JASPEL dengan nominal tinggi memerlukan catatan validasi.';
                return false;
            }
        }

        return true;
    }

    /**
     * General validation (called when no specific field is specified)
     */
    private function validateGeneral($value): bool
    {
        // Combine all validations
        $checks = [
            'nominal' => $this->context['nominal'] ?? null,
            'jenis_jaspel' => $this->context['jenis_jaspel'] ?? null,
            'tindakan_id' => $this->context['tindakan_id'] ?? null,
            'tanggal' => $this->context['tanggal'] ?? null,
        ];

        foreach ($checks as $field => $fieldValue) {
            if ($fieldValue !== null) {
                $rule = new ValidJaspelEntry($field, $this->context);
                if (!$rule->passes($field, $fieldValue)) {
                    $this->failureMessage = $rule->message();
                    return false;
                }
            }
        }

        // Check for duplicates
        $duplicateRule = new ValidJaspelEntry('duplicate', $this->context);
        if (!$duplicateRule->passes('duplicate', null)) {
            $this->failureMessage = $duplicateRule->message();
            return false;
        }

        // Check business rules
        $businessRule = new ValidJaspelEntry('business_rules', $this->context);
        if (!$businessRule->passes('business_rules', null)) {
            $this->failureMessage = $businessRule->message();
            return false;
        }

        return true;
    }

    /**
     * Helper methods
     */
    private function isPotentialDummyAmount($nominal): bool
    {
        // Check for suspiciously round numbers
        if ($nominal >= 100000 && $nominal % 10000 == 0) {
            return true; // Exact multiples of 10k over 100k
        }

        // Check for common dummy patterns
        $dummyPatterns = [
            123456, 234567, 345678, 456789,
            100000, 200000, 300000, 400000, 500000,
            111111, 222222, 333333, 444444, 555555,
            999999, 123123, 456456, 789789
        ];

        return in_array($nominal, $dummyPatterns);
    }

    private function validateNominalAgainstTindakan($nominal, $tindakanId): bool
    {
        $tindakan = Tindakan::with('jenisTindakan')->find($tindakanId);
        if (!$tindakan) {
            return true; // Let tindakan_id validation handle this
        }

        $expectedJaspel = $this->calculateExpectedJaspel($tindakan);
        
        // Allow 20% tolerance
        $tolerance = 0.20;
        $minExpected = $expectedJaspel * (1 - $tolerance);
        $maxExpected = $expectedJaspel * (1 + $tolerance);

        if ($nominal < $minExpected || $nominal > $maxExpected) {
            $this->failureMessage = "Nominal JASPEL tidak sesuai dengan tarif tindakan. " .
                "Ekspektasi: Rp " . number_format($expectedJaspel) . 
                " (toleransi ±20%). Actual: Rp " . number_format($nominal);
            return false;
        }

        return true;
    }

    private function calculateExpectedJaspel($tindakan): float
    {
        $jenisTindakan = $tindakan->jenisTindakan;
        
        if ($jenisTindakan && $jenisTindakan->persentase_jaspel > 0) {
            return $tindakan->tarif * ($jenisTindakan->persentase_jaspel / 100);
        }

        // Default percentages based on JASPEL type
        $jenisJaspel = $this->context['jenis_jaspel'] ?? 'paramedis';
        
        return match($jenisJaspel) {
            'paramedis' => $tindakan->tarif * 0.15,
            'dokter_umum' => $tindakan->tarif * 0.40,
            'dokter_spesialis' => $tindakan->tarif * 0.50,
            default => $tindakan->tarif * 0.20
        };
    }

    private function validateKonsultasiKhusus(): bool
    {
        // If no tindakan_id, require stronger justification
        if (!($this->context['tindakan_id'] ?? null)) {
            if (empty($this->context['catatan_validasi']) || 
                strlen($this->context['catatan_validasi']) < 30) {
                $this->failureMessage = 'Konsultasi khusus manual memerlukan catatan validasi minimal 30 karakter yang menjelaskan detail konsultasi.';
                return false;
            }

            // Check for suspicious patterns in konsultasi_khusus
            $nominal = $this->context['nominal'] ?? 0;
            if ($nominal > 0 && $this->isPotentialDummyAmount($nominal)) {
                $this->failureMessage = 'Nominal konsultasi khusus terdeteksi sebagai data dummy.';
                return false;
            }
        }

        return true;
    }
}