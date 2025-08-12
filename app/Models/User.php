<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auditable;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Auditable, HasApiTokens, HasRoles, Cacheable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'location_id',
        'work_location_id',
        'pegawai_id',
        'name',
        'email',
        'username',
        'password',
        'nip',
        'no_telepon',
        'tanggal_bergabung',
        'is_active',
        'last_login_at',
        // Profile fields
        'phone',
        'address',
        'bio',
        'date_of_birth',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'profile_photo_path',
        // Work settings
        'default_location_id',
        'auto_check_out',
        'overtime_alerts',
        // Notification settings
        'email_notifications',
        'push_notifications',
        'attendance_reminders',
        'schedule_updates',
        // Privacy settings
        'profile_visibility',
        'location_sharing',
        'activity_status',
        // App settings
        'language',
        'timezone',
        'theme',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tanggal_bergabung' => 'date',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relationship to pegawai - multiple users can belong to one pegawai (different roles)
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function permohonanCutis(): HasMany
    {
        return $this->hasMany(PermohonanCuti::class, 'pegawai_id');
    }

    /**
     * Legacy custom role relationship - kept for backward compatibility during migration
     */
    public function customRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Work location relationship for geofencing (legacy)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Work location relationship for enhanced geofencing and scheduling
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }

    /**
     * Get the primary role name (first role if multiple)
     */
    public function getPrimaryRoleName(): ?string
    {
        return $this->roles()->first()?->name;
    }

    /**
     * Check if user has a specific role (supports both single string and array)
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        // Check direct role relationship first (for role_id field)
        if (is_string($roles) && $this->role && $this->role->name === $roles) {
            return true;
        }
        
        if (is_array($roles) && $this->role && in_array($this->role->name, $roles)) {
            return true;
        }
        
        // Then check Spatie roles
        if (is_string($roles)) {
            return $this->roles()->where('name', $roles)->exists();
        }
        
        if (is_array($roles)) {
            return $this->roles()->whereIn('name', $roles)->exists();
        }
        
        return false;
    }

    /**
     * Legacy compatibility - check if user has any of the given roles
     */
    public function hasAnyLegacyRole($roles)
    {
        return $this->hasAnyRole($roles);
    }

    /**
     * Legacy compatibility - check if user has a specific role
     */
    public function hasLegacyRole($roleName)
    {
        return $this->hasRole($roleName);
    }

    public function tindakanAsDokter(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'dokter_id');
    }

    public function tindakanAsParamedis(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'paramedis_id');
    }

    public function tindakanAsNonParamedis(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'non_paramedis_id');
    }

    public function jaspel(): HasMany
    {
        return $this->hasMany(Jaspel::class);
    }

    public function uangDuduk(): HasMany
    {
        return $this->hasMany(UangDuduk::class);
    }

    /**
     * Assignment history relationship
     */
    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(AssignmentHistory::class);
    }

    /**
     * Assignments made by this user (as admin/manager)
     */
    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(AssignmentHistory::class, 'assigned_by');
    }

    public function pendapatanInput(): HasMany
    {
        return $this->hasMany(Pendapatan::class, 'input_by');
    }

    public function pengeluaranInput(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'input_by');
    }

    public function pendapatanHarian(): HasMany
    {
        return $this->hasMany(PendapatanHarian::class, 'user_id');
    }

    public function pengeluaranHarian(): HasMany
    {
        return $this->hasMany(PengeluaranHarian::class, 'user_id');
    }

    public function tindakanInput(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'input_by');
    }

    // LocationValidation relationship removed - functionality moved to GPS spoofing detection

    /**
     * Get attendances for this user
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    public function gpsSpoofingDetections(): HasMany
    {
        return $this->hasMany(GpsSpoofingDetection::class);
    }

    public function nonParamedisAttendances(): HasMany
    {
        return $this->hasMany(NonParamedisAttendance::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function jadwalJagas(): HasMany
    {
        return $this->hasMany(JadwalJaga::class, 'pegawai_id');
    }
    
    // Relationship to dokter if user is a dokter
    public function dokter()
    {
        return $this->hasOne(Dokter::class, 'user_id');
    }
    
    // Legacy relationship - removed in favor of new pegawai() method above

    // Relationship to two factor authentication
    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuth::class);
    }

    // Relationship to user sessions
    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }



    /**
     * Enhanced can method that integrates with Spatie Permission
     */
    public function can($abilities, $arguments = []): bool
    {
        // First check Spatie permissions
        if (is_string($abilities) && $this->hasPermissionTo($abilities)) {
            return true;
        }
        
        // Fall back to Laravel's default can method
        return parent::can($abilities, $arguments);
    }

    /**
     * Override hasPermissionTo to check both Spatie permissions and custom role permissions
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // First check Spatie Permission package
        try {
            if (parent::hasPermissionTo($permission, $guardName)) {
                return true;
            }
        } catch (\Exception $e) {
            // If Spatie permission check fails, continue to custom role check
        }
        
        // Check custom role permissions array
        if ($this->role && $this->role->permissions) {
            return in_array($permission, $this->role->permissions);
        }
        
        return false;
    }

    /**
     * Find user by email or username for authentication
     * Prioritizes exact matches to avoid multi-role conflicts
     */
    public static function findForAuth(string $identifier): ?self
    {
        // Check if identifier is an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // If it's an email, search by email first
            return static::where('email', $identifier)->first();
        }
        
        // If it's not an email, it's likely a username
        // Search by username first (exact match)
        $user = static::where('username', $identifier)->first();
        
        if ($user) {
            return $user;
        }
        
        // Fallback: check if it might be an email without @ 
        // (unlikely but kept for backward compatibility)
        return static::where('email', $identifier)->first();
    }

    /**
     * Get the identifier field used for authentication
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';  // Always use ID as the primary identifier
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Admin panel - requires admin role OR admin panel permission
        if ($panel->getId() === 'admin') {
            return $this->hasRole('admin') || $this->hasPermissionTo('view_admin_panel');
        }
        
        // Role-based panel access using Spatie Permission
        $panelRoleMapping = [
            'bendahara' => 'bendahara',
            'petugas' => 'petugas', 
            'paramedis' => 'paramedis',
            'dokter' => 'dokter',
            'manajer' => 'manajer',
            'nonparamedis' => 'non_paramedis',
            'non-paramedis' => 'non_paramedis'
        ];
        
        $requiredRole = $panelRoleMapping[$panel->getId()] ?? null;
        
        if ($requiredRole) {
            return $this->hasRole($requiredRole);
        }
        
        return false;
    }
    
    // Cache commonly used statistics
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('user_stats', function() {
            return [
                'total_count' => static::count(),
                'active_count' => static::where('is_active', true)->count(),
                'inactive_count' => static::where('is_active', false)->count(),
                'with_roles_count' => static::whereHas('roles')->count(),
                'recent_login_count' => static::where('last_login_at', '>=', now()->subDays(7))->count(),
                'new_this_month_count' => static::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'dokter_count' => static::whereHas('dokter')->count(),
                'pegawai_count' => static::whereHas('pegawai')->count(),
            ];
        });
    }
    
    // Cache user's primary role
    public function getCachedPrimaryRoleAttribute(): ?string
    {
        return $this->cacheAttribute('cached_primary_role', function() {
            return $this->getPrimaryRoleName();
        });
    }
    
    // Cache user's permissions
    public function getCachedPermissionsAttribute(): array
    {
        return $this->cacheAttribute('cached_permissions', function() {
            return $this->getAllPermissions()->pluck('name')->toArray();
        });
    }
    
    // Cache user's roles
    public function getCachedRolesAttribute(): array
    {
        return $this->cacheAttribute('cached_roles', function() {
            return $this->roles()->pluck('name')->toArray();
        });
    }
    
    // Cache if user is active
    public function getIsActiveFormattedAttribute(): string
    {
        return $this->cacheAttribute('is_active_formatted', function() {
            return $this->is_active ? 'Aktif' : 'Tidak Aktif';
        });
    }
    
    // Cache tindakan count for this user
    public function getTindakanCountAttribute(): int
    {
        return $this->cacheCount('tindakan_count', function() {
            return $this->tindakanAsDokter()->count() +
                   $this->tindakanAsParamedis()->count() +
                   $this->tindakanAsNonParamedis()->count();
        });
    }
    
    // Cache jaspel total for this user
    public function getTotalJaspelAttribute(): float
    {
        return $this->cacheAttribute('total_jaspel', function() {
            return $this->jaspel()->sum('nominal') ?? 0;
        });
    }
    
    // Cache days since last login
    public function getDaysSinceLastLoginAttribute(): ?int
    {
        return $this->cacheAttribute('days_since_last_login', function() {
            return $this->last_login_at ? now()->diffInDays($this->last_login_at) : null;
        });
    }

    /**
     * Check if NIP is already taken by another user
     * 
     * @param string $nip
     * @param int|null $excludeUserId
     * @return array
     */
    public static function checkNipAvailability(string $nip, ?int $excludeUserId = null): array
    {
        $query = static::where('nip', $nip);
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        
        $existingUser = $query->first();
        
        if ($existingUser) {
            return [
                'available' => false,
                'message' => "NIP '{$nip}' sudah digunakan oleh user '{$existingUser->name}' (Username: {$existingUser->username}). Silakan gunakan NIP yang berbeda.",
                'existing_user' => [
                    'id' => $existingUser->id,
                    'name' => $existingUser->name,
                    'username' => $existingUser->username,
                    'email' => $existingUser->email,
                    'role' => $existingUser->role?->name
                ]
            ];
        }
        
        return [
            'available' => true,
            'message' => "NIP '{$nip}' tersedia untuk digunakan."
        ];
    }

    /**
     * Check if username is already taken by another user
     * 
     * @param string $username
     * @param int|null $excludeUserId
     * @return array
     */
    public static function checkUsernameAvailability(string $username, ?int $excludeUserId = null): array
    {
        $query = static::where('username', $username);
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        
        $existingUser = $query->first();
        
        if ($existingUser) {
            return [
                'available' => false,
                'message' => "Username '{$username}' sudah digunakan oleh user '{$existingUser->name}' (NIP: {$existingUser->nip}). Silakan gunakan username yang berbeda.",
                'existing_user' => [
                    'id' => $existingUser->id,
                    'name' => $existingUser->name,
                    'nip' => $existingUser->nip,
                    'email' => $existingUser->email,
                    'role' => $existingUser->role?->name
                ]
            ];
        }
        
        return [
            'available' => true,
            'message' => "Username '{$username}' tersedia untuk digunakan."
        ];
    }

    /**
     * Get detailed error message for constraint violations
     * 
     * @param \Exception $e
     * @return string
     */
    public static function getConstraintViolationMessage(\Exception $e): string
    {
        if ($e instanceof \Illuminate\Database\QueryException) {
            $message = $e->getMessage();
            
            // Check for different constraint violations
            if (str_contains($message, 'users.nip') || str_contains($message, 'UNIQUE constraint failed: users.nip')) {
                return 'NIP sudah digunakan oleh user lain. Silakan gunakan NIP yang berbeda.';
            } elseif (str_contains($message, 'users.username') || str_contains($message, 'UNIQUE constraint failed: users.username')) {
                return 'Username sudah digunakan oleh user lain. Silakan gunakan username yang berbeda.';
            } elseif (str_contains($message, 'users.email') || str_contains($message, 'UNIQUE constraint failed: users.email')) {
                return 'Email sudah digunakan oleh user lain. Silakan gunakan email yang berbeda.';
            } elseif (str_contains($message, 'UNIQUE constraint') || str_contains($message, 'Integrity constraint violation')) {
                return 'Data yang dimasukkan sudah ada di sistem. Periksa NIP, username, atau email yang Anda masukkan.';
            }
        }
        
        return 'Terjadi kesalahan saat menyimpan data user. Silakan coba lagi.';
    }

    /**
     * Send custom password reset notification for admin users
     */
    public function sendPasswordResetNotification($token)
    {
        if ($this->hasRole('admin')) {
            $this->notify(new \App\Notifications\AdminPasswordReset($token));
        } else {
            // Use default Laravel password reset notification for non-admin users
            $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
        }
    }
}
