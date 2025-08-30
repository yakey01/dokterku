<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jaspel extends Model
{
    use SoftDeletes;

    protected $table = 'jaspel';

    protected $fillable = [
        'tindakan_id',
        'user_id',
        'jenis_jaspel',
        'nominal',
        'total_jaspel',
        'tanggal',
        'shift_id',
        'input_by',
        'status_validasi',
        'validasi_by',
        'validasi_at',
        'catatan_validasi',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'total_jaspel' => 'decimal:2',
        'tanggal' => 'date',
        'validasi_at' => 'datetime',
    ];

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(Tindakan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function validasiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validasi_by');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status_validasi', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    public function scopeByJenisJaspel($query, $jenis)
    {
        return $query->where('jenis_jaspel', $jenis);
    }

    public function scopePending($query)
    {
        return $query->where('status_validasi', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status_validasi', 'disetujui');
    }

    /**
     * Scope for validated (approved) JASPEL only
     * This enforces bendahara validation as single source of truth
     */
    public function scopeValidated($query)
    {
        return $query->where('status_validasi', 'disetujui');
    }

    /**
     * Scope for rejected JASPEL
     */
    public function scopeRejected($query)
    {
        return $query->where('status_validasi', 'ditolak');
    }

    /**
     * WORKFLOW COMPLIANCE: Scope for JASPEL input by petugas role only
     * This enforces bendahara validation workflow - only validate data from petugas input
     */
    public function scopeInputByPetugasOnly($query)
    {
        return $query->whereIn('jaspel.input_by', function($subQuery) {
            $subQuery->select('users.id')
                     ->from('users')
                     ->join('roles', 'users.role_id', '=', 'roles.id')
                     ->where('roles.name', 'petugas');
        });
    }

    /**
     * Get only validated JASPEL for a user and period - WORKFLOW COMPLIANCE: PETUGAS INPUT ONLY
     */
    public function scopeValidatedForUser($query, $userId, $month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        return $query->where('user_id', $userId)
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    // CRITICAL: Only show data input by petugas for proper workflow compliance
                    ->inputByPetugasOnly();
    }

    /**
     * Check if this JASPEL is validated by bendahara
     */
    public function isValidated(): bool
    {
        return $this->status_validasi === 'disetujui';
    }

    /**
     * Check if this JASPEL is safe for gaming UI
     * Only validated amounts should be shown in gaming interface
     */
    public function isSafeForGaming(): bool
    {
        return $this->isValidated();
    }

    /**
     * WORKFLOW COMPLIANCE: Check if this JASPEL was input by petugas role
     * This enforces proper workflow where petugas inputs data for bendahara validation
     */
    public function isInputByPetugas(): bool
    {
        if (!$this->input_by) {
            return false;
        }
        
        $inputUser = User::with('role')->find($this->input_by);
        return $inputUser && $inputUser->role && $inputUser->role->name === 'petugas';
    }

    /**
     * WORKFLOW COMPLIANCE: Check if this JASPEL should be visible to bendahara
     * Only show data that follows proper workflow (petugas input → bendahara validation)
     */
    public function isValidForBendaharaValidation(): bool
    {
        return $this->isInputByPetugas();
    }

    /**
     * Accessor for compatibility with views
     */
    public function getStatusAttribute()
    {
        return $this->status_validasi;
    }

    /**
     * Accessor for tanggal_pengajuan compatibility
     */
    public function getTanggalPengajuanAttribute()
    {
        return $this->created_at;
    }

    /**
     * Accessor for tanggal_validasi compatibility
     */
    public function getTanggalValidasiAttribute()
    {
        return $this->validasi_at;
    }

    /**
     * Accessor for validator compatibility
     */
    public function getValidatorAttribute()
    {
        return $this->validasiBy;
    }

    /**
     * Accessor for keterangan
     */
    public function getKeteranganAttribute()
    {
        if ($this->tindakan) {
            $tindakan = $this->tindakan;
            $jenisTindakan = $tindakan->jenisTindakan;
            $pasien = $tindakan->pasien;
            
            return "Jaspel untuk tindakan: " . ($jenisTindakan ? $jenisTindakan->nama_tindakan : 'N/A') . 
                   " - Pasien: " . ($pasien ? $pasien->nama : 'N/A') . 
                   " - Tindakan ID: " . $tindakan->id;
        }
        
        return "Jaspel " . ucwords(str_replace('_', ' ', $this->jenis_jaspel));
    }
}
