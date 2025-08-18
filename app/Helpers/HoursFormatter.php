<?php

namespace App\Helpers;

/**
 * Indonesian Hours and Minutes Formatter
 * Converts decimal hours to "jam menit" format
 */
class HoursFormatter
{
    /**
     * Format decimal hours to Indonesian "jam menit" format
     * 
     * @param float $decimalHours
     * @return string
     */
    public static function formatHoursMinutes(float $decimalHours): string
    {
        if ($decimalHours <= 0) {
            return '0 jam 0 menit';
        }
        
        $hours = intval($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);
        
        // Handle rounding edge case where minutes = 60
        if ($minutes >= 60) {
            $hours += 1;
            $minutes = 0;
        }
        
        // Format with proper Indonesian terms
        $jamText = $hours === 1 ? 'jam' : 'jam';
        $menitText = $minutes === 1 ? 'menit' : 'menit';
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours} {$jamText} {$minutes} {$menitText}";
        } elseif ($hours > 0) {
            return "{$hours} {$jamText}";
        } else {
            return "{$minutes} {$menitText}";
        }
    }
    
    /**
     * Format decimal hours to compact "j m" format
     * 
     * @param float $decimalHours
     * @return string
     */
    public static function formatCompact(float $decimalHours): string
    {
        if ($decimalHours <= 0) {
            return '0j 0m';
        }
        
        $hours = intval($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);
        
        // Handle rounding edge case
        if ($minutes >= 60) {
            $hours += 1;
            $minutes = 0;
        }
        
        return sprintf('%dj %dm', $hours, $minutes);
    }
    
    /**
     * Format decimal hours to "HH:MM" time format
     * 
     * @param float $decimalHours
     * @return string
     */
    public static function formatTime(float $decimalHours): string
    {
        if ($decimalHours <= 0) {
            return '00:00';
        }
        
        $hours = intval($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);
        
        // Handle rounding edge case
        if ($minutes >= 60) {
            $hours += 1;
            $minutes = 0;
        }
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }
    
    /**
     * Parse various time formats to decimal hours
     * 
     * @param string $timeString
     * @return float
     */
    public static function parseToDecimal(string $timeString): float
    {
        // Handle "8j 30m" format
        if (preg_match('/(\d+)j\s*(\d+)m/', $timeString, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            return $hours + ($minutes / 60);
        }
        
        // Handle "8 jam 30 menit" format
        if (preg_match('/(\d+)\s*jam\s*(\d+)\s*menit/', $timeString, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            return $hours + ($minutes / 60);
        }
        
        // Handle "HH:MM" format
        if (preg_match('/(\d+):(\d+)/', $timeString, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            return $hours + ($minutes / 60);
        }
        
        // Handle plain decimal string
        if (is_numeric($timeString)) {
            return (float)$timeString;
        }
        
        return 0.0;
    }
    
    /**
     * Get total hours with proper Indonesian formatting for API responses
     * 
     * @param float $decimalHours
     * @return array
     */
    public static function formatForApi(float $decimalHours): array
    {
        return [
            'decimal' => round($decimalHours, 2),
            'formatted' => self::formatHoursMinutes($decimalHours),
            'compact' => self::formatCompact($decimalHours),
            'time' => self::formatTime($decimalHours)
        ];
    }
}