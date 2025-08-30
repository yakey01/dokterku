<?php

namespace App\Services;

use App\Models\User;
use App\Models\Dokter;
use App\Models\Jaspel;
use App\Models\JumlahPasienHarian;
use App\Models\JadwalJaga;
use App\Models\Tindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Recent Achievements Service
 * 
 * Handles calculation of doctor achievements data for dashboard display
 * Integrates with existing jaspel validation system
 */
class RecentAchievementsService
{
    /**
     * Get recent achievements data for a doctor
     * 
     * @param User $user
     * @param int|null $month
     * @param int|null $year
     * @return array
     */
    public function getRecentAchievements(User $user, ?int $month = null, ?int $year = null): array
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;
        
        // Cache key for 2 minutes (same as dashboard cache)
        $cacheKey = "recent_achievements_{$user->id}_{$month}_{$year}";
        
        return Cache::remember($cacheKey, 120, function () use ($user, $month, $year) {
            Log::info('Calculating recent achievements', [
                'user_id' => $user->id,
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'current_month' => $this->getCurrentMonthProgress($user, $month, $year),
                'achievements' => $this->getAchievementsList($user, $month, $year),
                'validation_summary' => $this->getValidationSummary($user, $month, $year),
                'live_status' => $this->getLiveStatus($user)
            ];
        });
    }
    
    /**
     * Get current month progress data (like Dr. Yaya's example)
     * 
     * @param User $user
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getCurrentMonthProgress(User $user, int $month, int $year): array
    {
        // Get validated jaspel amount for current month
        $totalEarned = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->sum('nominal');
        
        // Target amount (could be configurable per doctor)
        $targetAmount = 2000000; // 2M IDR default target
        
        // Calculate progress percentage
        $progressPercentage = $targetAmount > 0 ? round(($totalEarned / $targetAmount) * 100, 1) : 0;
        $progressPercentage = min($progressPercentage, 100); // Cap at 100%
        
        // Get month name in Indonesian
        $monthName = Carbon::create($year, $month, 1)->locale('id')->monthName;
        
        // Calculate days
        $currentDate = Carbon::now();
        $monthStart = Carbon::create($year, $month, 1);
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        
        $daysElapsed = $currentDate->greaterThan($monthEnd) 
            ? $monthEnd->day 
            : max($currentDate->day, 1);
        $daysRemaining = max($monthEnd->day - $daysElapsed, 0);
        
        return [
            'total_earned' => (int)$totalEarned,
            'formatted_amount' => $this->formatCurrency($totalEarned),
            'compact_amount' => $this->formatCompactCurrency($totalEarned),
            'target_amount' => $targetAmount,
            'progress_percentage' => $progressPercentage,
            'month_name' => $monthName,
            'validation_status' => 'validated',
            'is_live' => true,
            'last_updated' => Carbon::now()->toISOString(),
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining
        ];
    }
    
    /**
     * Get achievements list
     * 
     * @param User $user
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getAchievementsList(User $user, int $month, int $year): array
    {
        $achievements = [];
        
        // Total Gold Earned Achievement
        $totalJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->sum('nominal');
        
        if ($totalJaspel > 0) {
            $achievements[] = [
                'title' => 'Total Gold Earned',
                'value' => (int)$totalJaspel,
                'formatted_value' => $this->formatCurrency($totalJaspel),
                'compact_value' => $this->formatCompactCurrency($totalJaspel),
                'status' => 'validated',
                'validated_at' => Carbon::now()->toISOString(),
                'icon' => 'trophy',
                'type' => 'financial',
                'description' => 'Jaspel yang telah divalidasi bendahara'
            ];
        }
        
        // Patient Count Achievement
        $dokter = Dokter::where('user_id', $user->id)->first();
        if ($dokter) {
            $totalPatients = JumlahPasienHarian::where('dokter_id', $dokter->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->whereIn('status_validasi', ['approved', 'disetujui'])
                ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
            if ($totalPatients > 0) {
                $achievements[] = [
                    'title' => 'Pasien Bulan Ini',
                    'value' => (int)$totalPatients,
                    'formatted_value' => number_format($totalPatients, 0, ',', '.') . ' pasien',
                    'compact_value' => $totalPatients . ' pasien',
                    'status' => 'validated',
                    'validated_at' => Carbon::now()->toISOString(),
                    'icon' => 'heart',
                    'type' => 'medical',
                    'description' => 'Total pasien yang telah dilayani bulan ini'
                ];
            }
        }
        
        // Attendance Achievement
        $attendanceRate = $this->calculateAttendanceRate($user, $month, $year);
        if ($attendanceRate > 0) {
            $achievements[] = [
                'title' => 'Tingkat Kehadiran',
                'value' => $attendanceRate,
                'formatted_value' => $attendanceRate . '%',
                'compact_value' => $attendanceRate . '%',
                'status' => 'active',
                'validated_at' => Carbon::now()->toISOString(),
                'icon' => 'calendar',
                'type' => 'attendance',
                'description' => 'Persentase kehadiran bulan ini'
            ];
        }
        
        return $achievements;
    }
    
    /**
     * Get validation summary
     * 
     * @param User $user
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getValidationSummary(User $user, int $month, int $year): array
    {
        $totalValidated = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->sum('nominal');
        
        $pendingValidation = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereIn('status_validasi', ['pending', 'menunggu'])
            ->sum('nominal');
        
        $lastValidation = Jaspel::where('user_id', $user->id)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->latest('updated_at')
            ->first();
        
        return [
            'total_validated' => (int)$totalValidated,
            'formatted_validated' => $this->formatCurrency($totalValidated),
            'pending_validation' => (int)$pendingValidation,
            'formatted_pending' => $this->formatCurrency($pendingValidation),
            'last_validation' => $lastValidation ? $lastValidation->updated_at->toISOString() : null,
            'validator' => 'bendahara',
            'validation_rate' => $pendingValidation > 0 
                ? round(($totalValidated / ($totalValidated + $pendingValidation)) * 100, 1)
                : 100
        ];
    }
    
    /**
     * Get live status
     * 
     * @param User $user
     * @return array
     */
    public function getLiveStatus(User $user): array
    {
        // Check if user has any recent activity (last 24 hours)
        $lastActivity = Jaspel::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->latest()
            ->first();
        
        return [
            'is_live' => $lastActivity !== null,
            'last_entry' => $lastActivity ? $lastActivity->created_at->toISOString() : null,
            'last_updated' => Carbon::now()->toISOString(),
            'status' => $lastActivity ? 'active' : 'idle'
        ];
    }
    
    /**
     * Calculate attendance rate for the month
     * 
     * @param User $user
     * @param int $month
     * @param int $year
     * @return float
     */
    private function calculateAttendanceRate(User $user, int $month, int $year): float
    {
        // This is a simplified calculation
        // In real implementation, you would use the attendance system
        $workingDays = 22; // Average working days per month
        $presentDays = JadwalJaga::where('pegawai_id', $user->id)
            ->whereMonth('tanggal_jaga', $month)
            ->whereYear('tanggal_jaga', $year)
            ->count();
        
        return $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 1) : 0;
    }
    
    /**
     * Format currency to Indonesian format
     * 
     * @param float $amount
     * @return string
     */
    private function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    
    /**
     * Format currency in full format (e.g., Rp 1.199.500)
     * 
     * @param float $amount
     * @return string
     */
    private function formatCompactCurrency(float $amount): string
    {
        // Always return full format like formatCurrency
        return $this->formatCurrency($amount);
    }
}