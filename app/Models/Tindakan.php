<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;

class Tindakan extends Model
{
    use HasFactory, SoftDeletes, Cacheable, LogsActivity;

    protected $table = 'tindakan';

    protected $fillable = [
        'pasien_id',
        'jenis_tindakan_id',
        'dokter_id',
        'paramedis_id',
        'non_paramedis_id',
        'shift_id',
        'tanggal_tindakan',
        'tarif',
        'jasa_dokter',
        'jasa_paramedis',
        'jasa_non_paramedis',
        'catatan',
        'status',
        'status_validasi',
        'validated_by',
        'validated_at',
        'komentar_validasi',
        'input_by',
    ];

    protected $casts = [
        'tanggal_tindakan' => 'datetime',
        'validated_at' => 'datetime',
        'tarif' => 'decimal:2',
        'jasa_dokter' => 'decimal:2',
        'jasa_paramedis' => 'decimal:2',
        'jasa_non_paramedis' => 'decimal:2',
    ];

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(JenisTindakan::class);
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function paramedis(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'paramedis_id');
    }

    public function nonParamedis(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'non_paramedis_id');
    }

    public function shift(): BelongsTo
    {
        // Now points to ShiftTemplate since we updated the foreign key
        return $this->belongsTo(ShiftTemplate::class, 'shift_id');
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_id');
    }


    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function jaspel(): HasMany
    {
        return $this->hasMany(Jaspel::class);
    }

    public function pendapatan(): HasMany
    {
        return $this->hasMany(Pendapatan::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_tindakan', [$startDate, $endDate]);
    }

    public function scopeByDokter($query, $dokterId)
    {
        return $query->where('dokter_id', $dokterId);
    }

    public function scopePendingValidasi($query)
    {
        return $query->where('status_validasi', 'pending');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status_validasi', 'disetujui');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status_validasi', 'ditolak');
    }
    
    // Cache commonly used statistics
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('tindakan_stats', function() {
            return [
                'total_count' => static::count(),
                'pending_count' => static::where('status_validasi', 'pending')->count(),
                'approved_count' => static::where('status_validasi', 'disetujui')->count(),
                'rejected_count' => static::where('status_validasi', 'ditolak')->count(),
                'today_count' => static::whereDate('tanggal_tindakan', today())->count(),
                'this_month_count' => static::whereMonth('tanggal_tindakan', now()->month)
                    ->whereYear('tanggal_tindakan', now()->year)
                    ->count(),
                'total_revenue' => static::where('status_validasi', 'disetujui')
                    ->sum('tarif') ?? 0,
                'avg_tarif' => static::where('status_validasi', 'disetujui')
                    ->avg('tarif') ?? 0,
            ];
        });
    }
    
    // Cache total jasa for this tindakan
    public function getTotalJasaAttribute(): float
    {
        return $this->cacheAttribute('total_jasa', function() {
            return ($this->jasa_dokter ?? 0) + 
                   ($this->jasa_paramedis ?? 0) + 
                   ($this->jasa_non_paramedis ?? 0);
        });
    }
    
    // Cache formatted status
    public function getStatusFormattedAttribute(): string
    {
        return $this->cacheAttribute('status_formatted', function() {
            return match($this->status_validasi) {
                'pending' => 'Menunggu Validasi',
                'disetujui' => 'Disetujui',
                'ditolak' => 'Ditolak',
                default => ucfirst($this->status_validasi)
            };
        });
    }
    
    // Cache jaspel count for this tindakan
    public function getJaspelCountAttribute(): int
    {
        return $this->cacheCount('jaspel_count', function() {
            return $this->jaspel()->count();
        });
    }
    
    // Cache pendapatan count for this tindakan
    public function getPendapatanCountAttribute(): int
    {
        return $this->cacheCount('pendapatan_count', function() {
            return $this->pendapatan()->count();
        });
    }
    
    // Virtual column for Jaspel Diterima (direct calculation from jenis_tindakan)
    public function getJaspelDiterimaAttribute(): float
    {
        return $this->cacheAttribute('jaspel_diterima', function() {
            // Always calculate based on tarif and persentase_jaspel from jenis_tindakan
            if (!$this->jenisTindakan) {
                return 0;
            }
            
            // Get persentase_jaspel from jenis_tindakan
            $persentaseJaspel = $this->jenisTindakan->persentase_jaspel ?? 0;
            
            // If no persentase set, return 0
            if ($persentaseJaspel <= 0) {
                return 0;
            }
            
            // Calculate: tarif * (persentase_jaspel / 100)
            return $this->tarif * ($persentaseJaspel / 100);
        });
    }
}
