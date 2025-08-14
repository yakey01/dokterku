<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokterUmumJaspel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'jenis_shift',
        'ambang_pasien',
        'fee_pasien_umum',
        'fee_pasien_bpjs',
        'uang_duduk',
        'status_aktif',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ambang_pasien' => 'integer',
        'fee_pasien_umum' => 'decimal:2',
        'fee_pasien_bpjs' => 'decimal:2',
        'uang_duduk' => 'decimal:2',
        'status_aktif' => 'boolean',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status_aktif', true);
    }

    public function scopeByShift($query, $shift)
    {
        return $query->where('jenis_shift', $shift);
    }

    // Accessors & Mutators
    public function getShiftDisplayAttribute(): string
    {
        return match ($this->jenis_shift) {
            'Pagi' => '🌅 Pagi',
            'Sore' => '🌇 Sore',
            'Hari Libur Besar' => '🏖️ Hari Libur Besar',
            default => $this->jenis_shift,
        };
    }

    public function getShiftBadgeColorAttribute(): string
    {
        return match ($this->jenis_shift) {
            'Pagi' => 'info',
            'Sore' => 'warning',
            'Hari Libur Besar' => 'success',
            default => 'gray',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->status_aktif ? 'success' : 'danger';
    }

    public function getStatusTextAttribute(): string
    {
        return $this->status_aktif ? 'Aktif' : 'Nonaktif';
    }

    public function getFeeUmumFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->fee_pasien_umum, 0, ',', '.');
    }

    public function getFeeBpjsFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->fee_pasien_bpjs, 0, ',', '.');
    }

    public function getUangDudukFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->uang_duduk, 0, ',', '.');
    }

    // Helper methods untuk perhitungan jasa pelayanan dokter umum
    /**
     * Menghitung fee berdasarkan jumlah pasien dengan logika threshold
     * 
     * Logika perhitungan:
     * - Jika pasien <= threshold: hanya dapat uang duduk
     * - Jika pasien > threshold: uang duduk + (fee per pasien × (pasien - threshold))
     * 
     * Contoh: pasien=50, threshold=10, fee_umum=7000, uang_duduk=50000
     * Hasil: 50000 + (7000 × (50-10)) = 50000 + (7000 × 40) = 330000
     */
    public function calculateFee(int $jumlahPasien, string $jenisPasien = 'umum'): float
    {
        if ($jumlahPasien <= $this->ambang_pasien) {
            return $this->uang_duduk; // Hanya dapat uang duduk jika belum mencapai threshold
        }

        // Hitung pasien yang melebihi threshold
        $pasienDihitung = $jumlahPasien - $this->ambang_pasien;
        $feePerPasien = $jenisPasien === 'bpjs' ? $this->fee_pasien_bpjs : $this->fee_pasien_umum;
        
        // Total = uang duduk + (fee per pasien * pasien yang melebihi threshold)
        return $this->uang_duduk + ($pasienDihitung * $feePerPasien);
    }

    /**
     * Menghitung fee berdasarkan total pasien untuk threshold check dengan proporsi individual
     * 
     * Logika perhitungan:
     * - Jika total pasien <= threshold: hanya dapat uang duduk
     * - Jika total pasien > threshold: uang duduk + (fee per pasien × proportional count after threshold)
     * 
     * Contoh: total_pasien=100, threshold=10, individual_count=50, fee_umum=7000, uang_duduk=50000
     * Pasien dihitung = (100-10) × (50/100) = 90 × 0.5 = 45
     * Hasil: 50000 + (7000 × 45) = 50000 + 315000 = 365000
     */
    public function calculateFeeByTotal(int $totalPasien, int $individualCount, string $jenisPasien = 'umum'): float
    {
        if ($totalPasien <= $this->ambang_pasien) {
            return $this->uang_duduk; // Hanya dapat uang duduk jika total belum mencapai threshold
        }

        // Hitung pasien yang melebihi threshold
        $totalPasienDihitung = $totalPasien - $this->ambang_pasien;
        
        // Hitung proporsi individual dari total
        $proporsi = $totalPasien > 0 ? $individualCount / $totalPasien : 0;
        $individualDihitung = round($totalPasienDihitung * $proporsi);
        
        $feePerPasien = $jenisPasien === 'bpjs' ? $this->fee_pasien_bpjs : $this->fee_pasien_umum;
        
        // Total = uang duduk + (fee per pasien * proportional count)
        return $this->uang_duduk + ($individualDihitung * $feePerPasien);
    }

    public static function getShiftOptions(): array
    {
        return [
            'Pagi' => '🌅 Pagi',
            'Sore' => '🌇 Sore', 
            'Hari Libur Besar' => '🏖️ Hari Libur Besar',
        ];
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
