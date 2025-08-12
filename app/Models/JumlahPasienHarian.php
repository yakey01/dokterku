<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class JumlahPasienHarian extends Model
{
    use Auditable;

    protected $fillable = [
        'tanggal',
        'poli',
        'jumlah_pasien_umum',
        'jumlah_pasien_bpjs',
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
    public function approve(User $validator, string $catatan = null): self
    {
        $this->update([
            'status_validasi' => 'approved',
            'validasi_by' => $validator->id,
            'validasi_at' => now(),
            'catatan_validasi' => $catatan,
        ]);

        return $this;
    }

    public function reject(User $validator, string $catatan = null): self
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
    }
}
