<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;
use App\Traits\ValidatesWithAudit;
use App\Constants\ValidationStatus;

class JumlahPasienHarian extends Model
{
    use Auditable, ValidatesWithAudit;

    protected $fillable = [
        'tanggal',
        'poli',
        'shift',
        'shift_template_id', // Direct reference to shift template
        'dokter_umum_jaspel_id', // Optional - for future use if manual selection needed
        'jadwal_jaga_id', // Links to specific duty schedule
        'jumlah_pasien_umum',
        'jumlah_pasien_bpjs',
        'jaspel_rupiah',
        'dokter_id',
        'input_by',
        'status_validasi',
        'validasi_by',
        'validasi_at',
        'catatan_validasi',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_pasien_umum' => 'integer',
        'jumlah_pasien_bpjs' => 'integer',
        'jaspel_rupiah' => 'decimal:2',
        'validasi_at' => 'datetime',
    ];

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function validasiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validasi_by');
    }

    public function dokterUmumJaspel(): BelongsTo
    {
        return $this->belongsTo(DokterUmumJaspel::class);
    }

    public function jadwalJaga(): BelongsTo
    {
        return $this->belongsTo(JadwalJaga::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    // Accessor untuk total pasien
    public function getTotalPasienAttribute(): int
    {
        return $this->jumlah_pasien_umum + $this->jumlah_pasien_bpjs;
    }

    // Accessor untuk badge color poli
    public function getPoliBadgeColorAttribute(): string
    {
        return match ($this->poli) {
            'umum' => 'primary',
            'gigi' => 'success',
            default => 'gray',
        };
    }

    // Accessor untuk badge color shift
    public function getShiftBadgeColorAttribute(): string
    {
        return match ($this->shift) {
            'Pagi' => 'info',
            'Sore' => 'warning',
            'Hari Libur Besar' => 'success',
            default => 'gray',
        };
    }

    // Helper method untuk mendapatkan opsi shift
    public static function getShiftOptions(): array
    {
        return [
            'Pagi' => '🌅 Pagi',
            'Sore' => '🌇 Sore',
            'Hari Libur Besar' => '🏖️ Hari Libur Besar',
        ];
    }

    // Helper method untuk mendapatkan formula berdasarkan shift dengan jadwal jaga context
    public function getActiveFormula(): ?DokterUmumJaspel
    {
        // First try to get from relationship if explicitly set
        if ($this->dokterUmumJaspel) {
            return $this->dokterUmumJaspel;
        }

        // If linked to jadwal jaga, get shift from there for better accuracy
        if ($this->jadwalJaga && $this->jadwalJaga->shiftTemplate) {
            $shiftNama = $this->jadwalJaga->shiftTemplate->nama_shift;
            return DokterUmumJaspel::where('jenis_shift', $shiftNama)
                ->where('status_aktif', true)
                ->first();
        }

        // Otherwise, auto-select based on stored shift field
        if ($this->shift) {
            return DokterUmumJaspel::where('jenis_shift', $this->shift)
                ->where('status_aktif', true)
                ->first();
        }

        // Fallback to any active formula
        return DokterUmumJaspel::where('status_aktif', true)->first();
    }

    // Helper method untuk menghitung jaspel berdasarkan formula yang dipilih otomatis
    public function calculateJaspel(): array
    {
        $formula = $this->getActiveFormula();
        
        if (!$formula) {
            return [
                'fee_umum' => 0,
                'fee_bpjs' => 0,
                'uang_duduk' => 0,
                'total' => 0,
            ];
        }

        $totalPasien = $this->total_pasien;

        if ($totalPasien <= $formula->ambang_pasien) {
            return [
                'fee_umum' => 0,
                'fee_bpjs' => 0,
                'uang_duduk' => $formula->uang_duduk,
                'total' => $formula->uang_duduk,
                'formula' => $formula,
            ];
        }

        // Hitung dengan proporsi berdasarkan threshold
        $totalPasienDihitung = $totalPasien - $formula->ambang_pasien;
        $proporsiUmum = $totalPasien > 0 ? $this->jumlah_pasien_umum / $totalPasien : 0;
        $proporsiBpjs = $totalPasien > 0 ? $this->jumlah_pasien_bpjs / $totalPasien : 0;

        $pasienUmumDihitung = round($totalPasienDihitung * $proporsiUmum);
        $pasienBpjsDihitung = round($totalPasienDihitung * $proporsiBpjs);

        $feeUmum = $pasienUmumDihitung * $formula->fee_pasien_umum;
        $feeBpjs = $pasienBpjsDihitung * $formula->fee_pasien_bpjs;

        return [
            'fee_umum' => $feeUmum,
            'fee_bpjs' => $feeBpjs,
            'uang_duduk' => $formula->uang_duduk,
            'total' => $formula->uang_duduk + $feeUmum + $feeBpjs,
            'pasien_umum_dihitung' => $pasienUmumDihitung,
            'pasien_bpjs_dihitung' => $pasienBpjsDihitung,
            'formula' => $formula,
        ];
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('tanggal', $date);
    }

    // Scope untuk filter berdasarkan poli
    public function scopeByPoli($query, $poli)
    {
        return $query->where('poli', $poli);
    }

    // Scope untuk filter berdasarkan dokter
    public function scopeByDokter($query, $dokterId)
    {
        return $query->where('dokter_id', $dokterId);
    }

    // Scope untuk filter berdasarkan status validasi
    public function scopeByStatus($query, $status)
    {
        return $query->where('status_validasi', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status_validasi', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status_validasi', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status_validasi', 'rejected');
    }

    // Helper methods untuk validasi
    public function approve(User $validator, ?string $catatan = null): self
    {
        $this->update([
            'status_validasi' => 'approved',
            'validasi_by' => $validator->id,
            'validasi_at' => now(),
            'catatan_validasi' => $catatan,
        ]);

        return $this;
    }

    public function reject(User $validator, ?string $catatan = null): self
    {
        $this->update([
            'status_validasi' => 'rejected',
            'validasi_by' => $validator->id,
            'validasi_at' => now(),
            'catatan_validasi' => $catatan,
        ]);

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status_validasi === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status_validasi === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status_validasi === 'rejected';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->input_by = auth()->id();
            }
        });

        // Auto-reset validation status when approved data is edited
        static::updating(function ($model) {
            // Only reset if currently approved
            if ($model->getOriginal('status_validasi') === 'disetujui') {
                // Critical fields that require re-validation
                $criticalFields = [
                    'jumlah_pasien_umum',
                    'jumlah_pasien_bpjs', 
                    'jaspel_rupiah',
                    'dokter_id',
                    'tanggal',
                    'shift',
                    'poli'
                ];
                
                // Check if any critical field was changed
                if ($model->isDirty($criticalFields)) {
                    $changedFields = array_keys($model->getDirty($criticalFields));
                    
                    // Reset validation status
                    $model->status_validasi = 'pending';
                    $model->validasi_by = null;
                    $model->validasi_at = null;
                    $model->catatan_validasi = 'Data diubah oleh petugas - perlu validasi ulang. Fields: ' . implode(', ', $changedFields);
                    
                    \Illuminate\Support\Facades\Log::info('JumlahPasienHarian validation status reset due to edit', [
                        'id' => $model->id,
                        'original_status' => 'disetujui',
                        'new_status' => 'pending',
                        'changed_fields' => $changedFields,
                        'edited_by' => auth()->id(),
                        'user_name' => auth()->user()?->name ?? 'Unknown'
                    ]);

                    // Fire event for bendahara notification
                    try {
                        event(new \App\Events\ValidationStatusReset([
                            'model_type' => 'JumlahPasienHarian',
                            'model_id' => $model->id,
                            'original_status' => 'disetujui', 
                            'new_status' => 'pending',
                            'changed_fields' => $changedFields,
                            'edited_by' => auth()->id(),
                            'user_name' => auth()->user()?->name ?? 'System',
                            'date' => $model->tanggal?->format('d/m/Y'),
                            'doctor' => $model->dokter?->nama ?? 'Unknown',
                            'total_pasien' => ($model->jumlah_pasien_umum ?? 0) + ($model->jumlah_pasien_bpjs ?? 0)
                        ]));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to fire ValidationStatusReset event', [
                            'model' => 'JumlahPasienHarian',
                            'id' => $model->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        });
    }
}
