<?php

namespace App\Services;

use App\Models\User;
use App\Models\AttendanceToleranceSetting;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AttendanceToleranceService
{
    /**
     * Get tolerance settings for a user
     * Priority: User-specific > Role-based > Global > Work Location fallback
     */
    public function getToleranceForUser(User $user): ?AttendanceToleranceSetting
    {
        // Cache key for performance
        $cacheKey = "tolerance_settings_user_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function() use ($user) {
            // Try user-specific settings first
            $userSetting = AttendanceToleranceSetting::active()
                ->forScope('user', (string)$user->id)
                ->orderBy('priority')
                ->first();
            
            if ($userSetting) {
                Log::info('Using user-specific tolerance setting', [
                    'user_id' => $user->id,
                    'setting_id' => $userSetting->id,
                    'setting_name' => $userSetting->setting_name
                ]);
                return $userSetting;
            }
            
            // Try role-based settings
            $userRole = $user->role ?? 'dokter'; // Default to dokter if no role
            $roleSetting = AttendanceToleranceSetting::active()
                ->forScope('role', $userRole)
                ->orderBy('priority')
                ->first();
            
            if ($roleSetting) {
                Log::info('Using role-based tolerance setting', [
                    'user_id' => $user->id,
                    'role' => $userRole,
                    'setting_id' => $roleSetting->id,
                    'setting_name' => $roleSetting->setting_name
                ]);
                return $roleSetting;
            }
            
            // Try global settings
            $globalSetting = AttendanceToleranceSetting::active()
                ->forScope('global')
                ->orderBy('priority')
                ->first();
            
            if ($globalSetting) {
                Log::info('Using global tolerance setting', [
                    'user_id' => $user->id,
                    'setting_id' => $globalSetting->id,
                    'setting_name' => $globalSetting->setting_name
                ]);
                return $globalSetting;
            }
            
            Log::warning('No tolerance setting found for user, will use WorkLocation fallback', [
                'user_id' => $user->id,
                'role' => $userRole
            ]);
            
            return null;
        });
    }
    
    /**
     * Get checkout tolerance for a user
     * Returns early and late tolerance in minutes
     */
    public function getCheckoutTolerance(User $user, Carbon $date = null): array
    {
        $date = $date ?? Carbon::now('Asia/Jakarta');
        $isWeekend = $date->isWeekend();
        $isHoliday = $this->isHoliday($date);
        
        // Get tolerance setting
        $toleranceSetting = $this->getToleranceForUser($user);
        
        if ($toleranceSetting) {
            $tolerance = $toleranceSetting->getToleranceForAction('checkout', $isWeekend, $isHoliday);
            
            Log::info('Checkout tolerance from AttendanceToleranceSetting', [
                'user_id' => $user->id,
                'early_tolerance' => $tolerance['early'] ?? 0,
                'late_tolerance' => $tolerance['late'] ?? 0,
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'setting_id' => $toleranceSetting->id
            ]);
            
            return [
                'early' => $tolerance['early'] ?? 30,
                'late' => $tolerance['late'] ?? 60,
                'source' => 'AttendanceToleranceSetting',
                'setting_id' => $toleranceSetting->id,
                'setting_name' => $toleranceSetting->setting_name
            ];
        }
        
        // Fallback to WorkLocation settings
        $workLocation = $user->workLocation;
        if ($workLocation) {
            $earlyTolerance = $workLocation->early_departure_tolerance_minutes ?? 15;
            $lateTolerance = $workLocation->checkout_after_shift_minutes ?? 60;
            
            Log::info('Checkout tolerance from WorkLocation (fallback)', [
                'user_id' => $user->id,
                'early_tolerance' => $earlyTolerance,
                'late_tolerance' => $lateTolerance,
                'work_location_id' => $workLocation->id
            ]);
            
            return [
                'early' => $earlyTolerance,
                'late' => $lateTolerance,
                'source' => 'WorkLocation',
                'location_id' => $workLocation->id,
                'location_name' => $workLocation->name
            ];
        }
        
        // Default fallback
        Log::warning('Using default checkout tolerance (no settings found)', [
            'user_id' => $user->id
        ]);
        
        return [
            'early' => 30,
            'late' => 60,
            'source' => 'Default'
        ];
    }
    
    /**
     * Get checkin tolerance for a user
     */
    public function getCheckinTolerance(User $user, Carbon $date = null): array
    {
        $date = $date ?? Carbon::now('Asia/Jakarta');
        $isWeekend = $date->isWeekend();
        $isHoliday = $this->isHoliday($date);
        
        // Get tolerance setting
        $toleranceSetting = $this->getToleranceForUser($user);
        
        if ($toleranceSetting) {
            $tolerance = $toleranceSetting->getToleranceForAction('checkin', $isWeekend, $isHoliday);
            
            return [
                'early' => $tolerance['early'] ?? 15,
                'late' => $tolerance['late'] ?? 15,
                'source' => 'AttendanceToleranceSetting',
                'setting_id' => $toleranceSetting->id,
                'setting_name' => $toleranceSetting->setting_name
            ];
        }
        
        // Fallback to WorkLocation settings
        $workLocation = $user->workLocation;
        if ($workLocation) {
            $earlyTolerance = $workLocation->checkin_before_shift_minutes ?? 30;
            $lateTolerance = $workLocation->late_tolerance_minutes ?? 15;
            
            return [
                'early' => $earlyTolerance,
                'late' => $lateTolerance,
                'source' => 'WorkLocation',
                'location_id' => $workLocation->id,
                'location_name' => $workLocation->name
            ];
        }
        
        // Default fallback
        return [
            'early' => 15,
            'late' => 15,
            'source' => 'Default'
        ];
    }
    
    /**
     * Validate if checkout is allowed based on tolerance
     */
    public function validateCheckoutTime(User $user, Carbon $currentTime, Carbon $shiftEndTime): array
    {
        $tolerance = $this->getCheckoutTolerance($user, $currentTime);
        
        // Calculate allowed checkout window
        $earliestCheckout = $shiftEndTime->copy()->subMinutes($tolerance['early']);
        $latestCheckout = $shiftEndTime->copy()->addMinutes($tolerance['late']);
        
        // Check if current time is within allowed window
        if ($currentTime->lt($earliestCheckout)) {
            // Round minutes to avoid decimals in display
            $minutesRemaining = round($currentTime->diffInMinutes($earliestCheckout));
            
            Log::warning('Checkout too early', [
                'user_id' => $user->id,
                'current_time' => $currentTime->format('H:i:s'),
                'earliest_allowed' => $earliestCheckout->format('H:i:s'),
                'minutes_remaining' => $minutesRemaining,
                'tolerance_source' => $tolerance['source'],
                'early_tolerance_minutes' => $tolerance['early']
            ]);
            
            return [
                'allowed' => false,
                'code' => 'CHECKOUT_TOO_EARLY',
                'message' => "Check-out terlalu awal. Anda dapat check-out mulai pukul {$earliestCheckout->format('H:i')} ({$minutesRemaining} menit lagi).",
                'earliest_checkout' => $earliestCheckout->format('H:i:s'),
                'minutes_remaining' => $minutesRemaining,
                'tolerance_source' => $tolerance['source']
            ];
        }
        
        // Too late is still allowed but logged
        if ($currentTime->gt($latestCheckout)) {
            Log::info('Checkout after allowed window (still permitted)', [
                'user_id' => $user->id,
                'current_time' => $currentTime->format('H:i:s'),
                'latest_allowed' => $latestCheckout->format('H:i:s'),
                'tolerance_source' => $tolerance['source']
            ]);
        }
        
        return [
            'allowed' => true,
            'code' => 'CHECKOUT_ALLOWED',
            'message' => 'Check-out diizinkan',
            'tolerance_source' => $tolerance['source'],
            'early_tolerance' => $tolerance['early'],
            'late_tolerance' => $tolerance['late']
        ];
    }
    
    /**
     * Clear tolerance cache for a user
     */
    public function clearUserCache(User $user): void
    {
        $cacheKey = "tolerance_settings_user_{$user->id}";
        Cache::forget($cacheKey);
        
        Log::info('Cleared tolerance cache for user', [
            'user_id' => $user->id
        ]);
    }
    
    /**
     * Clear all tolerance caches
     */
    public function clearAllCaches(): void
    {
        // Clear all user-specific caches
        Cache::flush(); // Or use tags if available
        
        Log::info('Cleared all tolerance caches');
    }
    
    /**
     * Check if a date is a holiday
     * This is a placeholder - implement based on your holiday system
     */
    private function isHoliday(Carbon $date): bool
    {
        // TODO: Implement holiday checking logic
        // Could check against a holidays table or use a holiday API
        return false;
    }
}