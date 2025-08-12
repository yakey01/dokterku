<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;

class Pasien extends Model
{
    use HasFactory, SoftDeletes, Cacheable, LogsActivity;

    protected $table = 'pasien';

    protected $fillable = [
        'no_rekam_medis',
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_telepon',
        'email',
        'pekerjaan',
        'status_pernikahan',
        'kontak_darurat_nama',
        'kontak_darurat_telepon',
        'input_by',
        'status',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'verified_at' => 'datetime',
    ];
    
    // Custom cache TTL for this model
    protected int $customCacheTtl = 3600; // 1 hour

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('jenis_kelamin', $gender);
    }

    public function getUmurAttribute()
    {
        return $this->cacheAttribute('umur', function() {
            return $this->tanggal_lahir?->age;
        });
    }
    
    // Cache commonly used statistics
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('patient_stats', function() {
            return [
                'total_count' => static::count(),
                'male_count' => static::where('jenis_kelamin', 'L')->count(),
                'female_count' => static::where('jenis_kelamin', 'P')->count(),
                'recent_count' => static::whereDate('created_at', today())->count(),
                'avg_age' => static::whereNotNull('tanggal_lahir')
                    ->get()
                    ->avg(function ($pasien) {
                        return $pasien->tanggal_lahir ? $pasien->tanggal_lahir->age : 0;
                    }) ?? 0,
            ];
        });
    }
    
    // Cache tindakan count for this patient
    public function getTindakanCountAttribute(): int
    {
        return $this->cacheCount('tindakan_count', function() {
            return $this->tindakan()->count();
        });
    }
    
    // Cache last tindakan for this patient
    public function getLastTindakanAttribute(): ?Tindakan
    {
        return $this->cacheAttribute('last_tindakan', function() {
            return $this->tindakan()->latest()->first();
        });
    }
    
    /**
     * Get validation rules for bulk operations
     */
    public function getBulkValidationRules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'no_rekam_medis' => 'nullable|string|max:255|unique:pasien,no_rekam_medis',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'nullable|string|max:500',
            'no_telepon' => 'nullable|string|max:20',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->no_rekam_medis)) {
                $model->no_rekam_medis = 'RM-' . date('Y') . '-' . str_pad(static::count() + 1, 3, '0', STR_PAD_LEFT);
            }
            if (empty($model->input_by)) {
                $model->input_by = auth()->id();
            }
            // Auto-set as verified if status not explicitly set
            if (empty($model->status)) {
                $model->status = 'verified';
                $model->verified_at = now();
                $model->verified_by = auth()->id();
            }
        });
    }

    /**
     * Relationship with User who created this patient
     */
    public function inputBy()
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    /**
     * Relationship with User who verified this patient
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for pending patients
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for verified patients
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope for rejected patients
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
