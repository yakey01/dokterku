<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sophisticated Duration Calculator Service
 * Implements 5-step effective duration calculation with break time overlap
 */
class EffectiveDurationCalculatorService
{
    /**
     * Calculate effective work duration using 5-step logic
     * 
     * @param Carbon|string $checkIn Check-in time
     * @param Carbon|string $checkOut Check-out time  
     * @param Carbon|string $shiftStart Shift start time
     * @param Carbon|string $shiftEnd Shift end time
     * @param array $breakTimes Array of break periods (optional)
     * @return array Calculation results
     */
    public function calculateEffectiveDuration(
        $checkIn,
        $checkOut, 
        $shiftStart,
        $shiftEnd,
        array $breakTimes = []
    ): array {
        
        // Step 1: Convert all times to minutes since midnight for easier calculation
        $checkInMinutes = $this->timeToMinutes($checkIn);
        $checkOutMinutes = $this->timeToMinutes($checkOut);
        $shiftStartMinutes = $this->timeToMinutes($shiftStart);
        $shiftEndMinutes = $this->timeToMinutes($shiftEnd);
        
        // Step 2: Handle overnight shifts
        $isOvernightShift = false;
        if ($shiftEndMinutes < $shiftStartMinutes) {
            $isOvernightShift = true;
            $shiftEndMinutes += 1440; // Add 24 hours
            
            // If check-out is early morning, assume next day
            if ($checkOutMinutes < 720) { // Before noon
                $checkOutMinutes += 1440;
            }
        }
        
        // Step 3: Validate inputs
        if ($checkInMinutes === null || $checkOutMinutes === null) {
            return $this->createErrorResult('Missing check-in or check-out time');
        }
        
        if ($shiftStartMinutes === null || $shiftEndMinutes === null) {
            return $this->createErrorResult('Missing shift start or end time');
        }
        
        // Step 4: Calculate effective times
        // Mulai efektif = max(check-in, shift_start)
        $effectiveStartMinutes = max($checkInMinutes, $shiftStartMinutes);
        
        // Akhir efektif = min(check-out, shift_end)  
        $effectiveEndMinutes = min($checkOutMinutes, $shiftEndMinutes);
        
        // Convert back to time strings for display
        $effectiveStart = $this->minutesToTimeString($effectiveStartMinutes % 1440);
        $effectiveEnd = $this->minutesToTimeString($effectiveEndMinutes % 1440);
        
        // Step 5: Calculate raw duration (simple minutes difference)
        $rawDurationMinutes = max(0, $effectiveEndMinutes - $effectiveStartMinutes);
        
        // Calculate break time overlap (using minutes approach)
        $breakOverlapMinutes = $this->calculateBreakOverlapMinutes(
            $effectiveStartMinutes,
            $effectiveEndMinutes,
            $breakTimes,
            $isOvernightShift
        );
        
        // Final duration = raw duration - break overlap
        $finalDurationMinutes = max(0, $rawDurationMinutes - $breakOverlapMinutes);
        
        // Calculate scheduled shift duration for comparison
        $scheduledDurationMinutes = max(0, $shiftEndMinutes - $shiftStartMinutes) - $this->getTotalBreakMinutes($breakTimes);
        
        // Calculate shortage (kekurangan)
        $shortageMinutes = max(0, $scheduledDurationMinutes - $finalDurationMinutes);
        
        return [
            'effective_start' => $effectiveStart,
            'effective_end' => $effectiveEnd,
            'raw_duration_minutes' => $rawDurationMinutes,
            'break_overlap_minutes' => $breakOverlapMinutes,
            'final_duration_minutes' => $finalDurationMinutes,
            'final_duration_hours' => $this->minutesToHours($finalDurationMinutes),
            'scheduled_duration_minutes' => $scheduledDurationMinutes,
            'shortage_minutes' => $shortageMinutes,
            'attendance_percentage' => $scheduledDurationMinutes > 0 ? 
                round(($finalDurationMinutes / $scheduledDurationMinutes) * 100, 1) : 0,
            'policies_applied' => [
                'early_checkin_ignored' => $checkInMinutes < $shiftStartMinutes,
                'late_checkout_ignored' => $checkOutMinutes > $shiftEndMinutes,
                'early_checkout' => $checkOutMinutes < $shiftEndMinutes,
                'is_overnight_shift' => $isOvernightShift
            ]
        ];
    }
    
    /**
     * Calculate break time overlap with work interval (minutes approach)
     */
    private function calculateBreakOverlapMinutes(int $workStartMinutes, int $workEndMinutes, array $breakTimes, bool $isOvernightShift = false): int
    {
        $totalOverlap = 0;
        
        foreach ($breakTimes as $breakTime) {
            $breakStartMinutes = $this->timeToMinutes($breakTime['start'] ?? null);
            $breakEndMinutes = $this->timeToMinutes($breakTime['end'] ?? null);
            
            if ($breakStartMinutes === null || $breakEndMinutes === null) continue;
            
            // Handle overnight breaks
            if ($breakEndMinutes < $breakStartMinutes) {
                $breakEndMinutes += 1440;
            }
            
            // Calculate overlap between work interval and break interval
            $overlapStart = max($workStartMinutes, $breakStartMinutes);
            $overlapEnd = min($workEndMinutes, $breakEndMinutes);
            
            if ($overlapStart < $overlapEnd) {
                $totalOverlap += ($overlapEnd - $overlapStart);
            }
        }
        
        return $totalOverlap;
    }
    
    /**
     * Calculate break time overlap with work interval (legacy Carbon method)
     */
    private function calculateBreakOverlap(Carbon $workStart, Carbon $workEnd, array $breakTimes): int
    {
        $totalOverlap = 0;
        
        foreach ($breakTimes as $breakTime) {
            $breakStart = $this->parseTime($breakTime['start'] ?? null);
            $breakEnd = $this->parseTime($breakTime['end'] ?? null);
            
            if (!$breakStart || !$breakEnd) continue;
            
            // Handle overnight breaks
            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }
            
            // Calculate overlap between work interval and break interval
            $overlapStart = $workStart->gt($breakStart) ? $workStart : $breakStart;
            $overlapEnd = $workEnd->lt($breakEnd) ? $workEnd : $breakEnd;
            
            if ($overlapStart->lt($overlapEnd)) {
                $totalOverlap += $overlapEnd->diffInMinutes($overlapStart);
            }
        }
        
        return $totalOverlap;
    }
    
    /**
     * Get total break minutes from break times array
     */
    private function getTotalBreakMinutes(array $breakTimes): int
    {
        $total = 0;
        
        foreach ($breakTimes as $breakTime) {
            $duration = $breakTime['duration_minutes'] ?? 0;
            if ($duration > 0) {
                $total += $duration;
            } else {
                // Calculate from start/end times
                $start = $this->parseTime($breakTime['start'] ?? null);
                $end = $this->parseTime($breakTime['end'] ?? null);
                if ($start && $end) {
                    if ($end->lt($start)) $end->addDay();
                    $total += $end->diffInMinutes($start);
                }
            }
        }
        
        return $total;
    }
    
    /**
     * Parse time string or Carbon instance with proper date context
     */
    private function parseTime($time): ?Carbon
    {
        if (!$time) return null;
        
        if ($time instanceof Carbon) {
            return $time->copy();
        }
        
        try {
            // Handle various time formats with consistent date context
            if (is_string($time)) {
                // HH:MM format - use today as base date
                if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                    $baseDate = Carbon::today();
                    $timeComponents = explode(':', $time);
                    return $baseDate->setTime((int)$timeComponents[0], (int)$timeComponents[1], 0);
                }
                // HH:MM:SS format - use today as base date
                if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
                    $baseDate = Carbon::today();
                    $timeComponents = explode(':', $time);
                    return $baseDate->setTime((int)$timeComponents[0], (int)$timeComponents[1], (int)$timeComponents[2]);
                }
                // ISO datetime format
                return Carbon::parse($time);
            }
            
            return Carbon::parse($time);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Convert time to minutes since midnight
     */
    private function timeToMinutes($time): ?int
    {
        if (!$time) return null;
        
        try {
            if ($time instanceof Carbon) {
                return $time->hour * 60 + $time->minute;
            }
            
            if (is_string($time)) {
                // Handle HH:MM or HH:MM:SS format
                if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $matches)) {
                    return (int)$matches[1] * 60 + (int)$matches[2];
                }
                
                // Handle full datetime
                $parsed = Carbon::parse($time);
                return $parsed->hour * 60 + $parsed->minute;
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Convert minutes since midnight to time string
     */
    private function minutesToTimeString(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
    
    /**
     * Convert minutes to hours format
     */
    private function minutesToHours(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}j {$mins}m";
    }
    
    /**
     * Create error result structure
     */
    private function createErrorResult(string $message): array
    {
        return [
            'error' => true,
            'message' => $message,
            'effective_start' => null,
            'effective_end' => null,
            'raw_duration_minutes' => 0,
            'break_overlap_minutes' => 0,
            'final_duration_minutes' => 0,
            'final_duration_hours' => '0j 0m',
            'scheduled_duration_minutes' => 0,
            'shortage_minutes' => 0,
            'attendance_percentage' => 0,
            'policies_applied' => []
        ];
    }
    
    /**
     * Calculate duration for multiple shifts in a day
     */
    public function calculateMultiShiftDuration(array $shifts): array
    {
        $totalFinalMinutes = 0;
        $totalScheduledMinutes = 0;
        $totalShortageMinutes = 0;
        $shiftCalculations = [];
        
        foreach ($shifts as $shift) {
            $calculation = $this->calculateEffectiveDuration(
                $shift['check_in'] ?? null,
                $shift['check_out'] ?? null,
                $shift['shift_start'] ?? null,
                $shift['shift_end'] ?? null,
                $shift['break_times'] ?? []
            );
            
            if (!isset($calculation['error'])) {
                $totalFinalMinutes += $calculation['final_duration_minutes'];
                $totalScheduledMinutes += $calculation['scheduled_duration_minutes'];
                $totalShortageMinutes += $calculation['shortage_minutes'];
            }
            
            $shiftCalculations[] = $calculation;
        }
        
        return [
            'total_final_duration_minutes' => $totalFinalMinutes,
            'total_final_duration_hours' => $this->minutesToHours($totalFinalMinutes),
            'total_scheduled_duration_minutes' => $totalScheduledMinutes,
            'total_shortage_minutes' => $totalShortageMinutes,
            'overall_attendance_percentage' => $totalScheduledMinutes > 0 ? 
                round(($totalFinalMinutes / $totalScheduledMinutes) * 100, 1) : 0,
            'shift_calculations' => $shiftCalculations
        ];
    }
    
    /**
     * Get standard break times based on shift template
     */
    public function getStandardBreakTimes(string $shiftName, Carbon $shiftStart, Carbon $shiftEnd): array
    {
        $breakTimes = [];
        
        // Define standard break times based on shift type
        switch (strtolower($shiftName)) {
            case 'pagi':
                if ($shiftStart->hour <= 8 && $shiftEnd->hour >= 12) {
                    $breakTimes[] = [
                        'start' => '12:00',
                        'end' => '13:00',
                        'duration_minutes' => 60,
                        'type' => 'lunch'
                    ];
                }
                break;
                
            case 'siang':
                if ($shiftStart->hour <= 14 && $shiftEnd->hour >= 17) {
                    $breakTimes[] = [
                        'start' => '15:30',
                        'end' => '16:00', 
                        'duration_minutes' => 30,
                        'type' => 'afternoon_break'
                    ];
                }
                break;
                
            case 'sore':
                if ($shiftStart->hour <= 16 && $shiftEnd->hour >= 19) {
                    $breakTimes[] = [
                        'start' => '18:00',
                        'end' => '18:30',
                        'duration_minutes' => 30,
                        'type' => 'dinner_break'
                    ];
                }
                break;
                
            case 'malam':
                // Night shift break at midnight
                $breakTimes[] = [
                    'start' => '00:00',
                    'end' => '00:30',
                    'duration_minutes' => 30,
                    'type' => 'midnight_break'
                ];
                break;
        }
        
        return $breakTimes;
    }
}