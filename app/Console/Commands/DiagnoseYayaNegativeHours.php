<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;

class DiagnoseYayaNegativeHours extends Command
{
    protected $signature = 'debug:yaya-negative-hours';
    protected $description = 'Diagnose Dr. Yaya negative hours calculation issue';

    public function handle()
    {
        // Configuration
        $drYayaUserId = 13;
        $targetMonth = 8;   // August 2025
        $targetYear = 2025;

        $this->info("🚨 CRITICAL DEBUGGING: DR. YAYA NEGATIVE HOURS INVESTIGATION");
        $this->info("============================================================");
        $this->info("Target: User ID {$drYayaUserId} (Dr. Yaya)");
        $this->info("Period: {$targetMonth}/{$targetYear}");
        $this->info("Problem: Total hours showing -285.14694444444");
        $this->line("");

        // Step 1: Get ALL attendance records for Dr. Yaya
        $this->info("📊 STEP 1: RAW ATTENDANCE DATA ANALYSIS");
        $this->info("=========================================");

        $allAttendances = Attendance::where('user_id', $drYayaUserId)
            ->whereMonth('date', $targetMonth)
            ->whereYear('date', $targetYear)
            ->orderBy('date')
            ->get();

        $this->info("Total attendance records found: " . $allAttendances->count());
        $this->line("");

        $completedRecords = [];
        $incompleteRecords = [];
        $problematicRecords = [];

        foreach ($allAttendances as $index => $attendance) {
            $recordNumber = $index + 1;
            $this->info("Record #{$recordNumber} - ID: {$attendance->id}");
            $this->info("  Date: {$attendance->date}");
            $this->info("  Time In: " . ($attendance->time_in ?? 'NULL'));
            $this->info("  Time Out: " . ($attendance->time_out ?? 'NULL'));
            
            // Check for completed records
            if ($attendance->time_in && $attendance->time_out) {
                try {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    
                    // Check for invalid timestamps
                    if (!$timeIn->isValid() || !$timeOut->isValid()) {
                        $this->error("  ❌ STATUS: INVALID TIMESTAMPS");
                        $problematicRecords[] = [
                            'id' => $attendance->id,
                            'issue' => 'invalid_timestamps',
                            'time_in' => $attendance->time_in,
                            'time_out' => $attendance->time_out
                        ];
                    } else {
                        // Calculate difference
                        $hours = $timeOut->diffInHours($timeIn, false); // Allow negative
                        $minutes = $timeOut->diffInMinutes($timeIn, false);
                        $hoursFloat = $minutes / 60;
                        
                        $this->info("  Hours: {$hours} ({$hoursFloat} float)");
                        $this->info("  Minutes: {$minutes}");
                        
                        // Check for anomalies
                        if ($hours < 0) {
                            $this->warn("  ⚠️ STATUS: NEGATIVE HOURS DETECTED");
                            $problematicRecords[] = [
                                'id' => $attendance->id,
                                'issue' => 'negative_hours',
                                'hours' => $hours,
                                'hours_float' => $hoursFloat,
                                'time_in' => $attendance->time_in,
                                'time_out' => $attendance->time_out,
                                'time_in_parsed' => $timeIn->format('Y-m-d H:i:s'),
                                'time_out_parsed' => $timeOut->format('Y-m-d H:i:s')
                            ];
                        } else if ($hours > 24) {
                            $this->warn("  ⚠️ STATUS: EXCESSIVE HOURS (>24h)");
                            $problematicRecords[] = [
                                'id' => $attendance->id,
                                'issue' => 'excessive_hours',
                                'hours' => $hours,
                                'hours_float' => $hoursFloat
                            ];
                        } else {
                            $this->info("  ✅ STATUS: NORMAL");
                            $completedRecords[] = [
                                'id' => $attendance->id,
                                'hours' => $hours,
                                'hours_float' => $hoursFloat
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("  ❌ STATUS: PARSING ERROR - " . $e->getMessage());
                    $problematicRecords[] = [
                        'id' => $attendance->id,
                        'issue' => 'parsing_error',
                        'error' => $e->getMessage(),
                        'time_in' => $attendance->time_in,
                        'time_out' => $attendance->time_out
                    ];
                }
            } else {
                $this->info("  ⏳ STATUS: INCOMPLETE (missing time_in or time_out)");
                $incompleteRecords[] = [
                    'id' => $attendance->id,
                    'missing' => !$attendance->time_in ? 'time_in' : 'time_out'
                ];
            }
            $this->line("");
        }

        // Step 2: Analyze the calculation issue
        $this->info("🔍 STEP 2: CALCULATION ANALYSIS");
        $this->info("================================");

        $this->info("Completed records: " . count($completedRecords));
        $this->info("Incomplete records: " . count($incompleteRecords));
        $this->info("Problematic records: " . count($problematicRecords));
        $this->line("");

        // Calculate totals using different methods
        $totalHoursInteger = array_sum(array_column($completedRecords, 'hours'));
        $totalHoursFloat = array_sum(array_column($completedRecords, 'hours_float'));

        $this->info("Total hours (integer method): {$totalHoursInteger}");
        $this->info("Total hours (float method): {$totalHoursFloat}");

        // Current calculation method from DokterDashboardController
        $currentCalculationTotal = Attendance::where('user_id', $drYayaUserId)
            ->whereMonth('date', $targetMonth)
            ->whereYear('date', $targetYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->sum(function($attendance) {
                if ($attendance->time_in && $attendance->time_out) {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    return $timeOut->diffInHours($timeIn);
                }
                return 0;
            });

        $this->info("Current calculation method result: {$currentCalculationTotal}");
        $this->line("");

        // Step 3: Detailed problematic records analysis
        if (!empty($problematicRecords)) {
            $this->error("🚨 STEP 3: PROBLEMATIC RECORDS DETAILED ANALYSIS");
            $this->error("=================================================");
            
            foreach ($problematicRecords as $record) {
                $this->error("Problematic Record ID: {$record['id']}");
                $this->error("Issue Type: {$record['issue']}");
                
                switch ($record['issue']) {
                    case 'negative_hours':
                        $this->error("Time In: {$record['time_in']} → Parsed: {$record['time_in_parsed']}");
                        $this->error("Time Out: {$record['time_out']} → Parsed: {$record['time_out_parsed']}");
                        $this->error("Hours: {$record['hours']} (Float: {$record['hours_float']})");
                        
                        // Additional analysis for negative hours
                        $timeInObj = Carbon::parse($record['time_in']);
                        $timeOutObj = Carbon::parse($record['time_out']);
                        
                        $this->error("Time comparison:");
                        $this->error("  Time In timestamp: " . $timeInObj->timestamp);
                        $this->error("  Time Out timestamp: " . $timeOutObj->timestamp);
                        $this->error("  Difference (seconds): " . ($timeOutObj->timestamp - $timeInObj->timestamp));
                        
                        // Check if overnight shift
                        if ($timeOutObj->lt($timeInObj)) {
                            $this->error("  🌙 OVERNIGHT SHIFT DETECTED: time_out < time_in");
                            $correctedTimeOut = $timeOutObj->copy()->addDay();
                            $correctedHours = $correctedTimeOut->diffInHours($timeInObj);
                            $this->error("  Corrected hours (with +1 day): {$correctedHours}");
                        }
                        break;
                        
                    case 'invalid_timestamps':
                        $this->error("Invalid Time In: {$record['time_in']}");
                        $this->error("Invalid Time Out: {$record['time_out']}");
                        break;
                        
                    case 'parsing_error':
                        $this->error("Error: {$record['error']}");
                        $this->error("Time In: {$record['time_in']}");
                        $this->error("Time Out: {$record['time_out']}");
                        break;
                }
                $this->line("");
            }
        }

        // Step 4: Data integrity checks
        $this->info("🔧 STEP 4: DATA INTEGRITY CHECKS");
        $this->info("==================================");

        // Check for duplicates
        $dateGroups = $allAttendances->groupBy('date');
        $duplicatesFound = false;

        foreach ($dateGroups as $date => $attendancesForDate) {
            if ($attendancesForDate->count() > 1) {
                $this->warn("⚠️ DUPLICATE ATTENDANCE FOUND for date: {$date}");
                $this->warn("  Records: " . $attendancesForDate->pluck('id')->implode(', '));
                $duplicatesFound = true;
            }
        }

        if (!$duplicatesFound) {
            $this->info("✅ No duplicate attendance records found");
        }

        // Check for timezone issues
        $this->line("");
        $this->info("Timezone Analysis:");
        $sampleRecord = $allAttendances->where('time_in', '!=', null)->where('time_out', '!=', null)->first();
        if ($sampleRecord) {
            $timeIn = Carbon::parse($sampleRecord->time_in);
            $timeOut = Carbon::parse($sampleRecord->time_out);
            
            $this->info("Sample record timezone info:");
            $this->info("  Time In timezone: " . $timeIn->timezone->getName());
            $this->info("  Time Out timezone: " . $timeOut->timezone->getName());
            $this->info("  App timezone: " . config('app.timezone'));
        }

        // Step 5: Recommendations
        $this->line("");
        $this->info("💡 STEP 5: DIAGNOSTIC RECOMMENDATIONS");
        $this->info("======================================");

        if (!empty($problematicRecords)) {
            $this->error("CRITICAL ISSUES FOUND:");
            
            $negativeHoursCount = count(array_filter($problematicRecords, fn($r) => $r['issue'] === 'negative_hours'));
            if ($negativeHoursCount > 0) {
                $this->error("- {$negativeHoursCount} records with negative hours");
                $this->error("  → Likely cause: time_out < time_in (overnight shifts not handled)");
                $this->error("  → Solution: Add overnight shift detection logic");
            }
            
            $invalidTimestampCount = count(array_filter($problematicRecords, fn($r) => $r['issue'] === 'invalid_timestamps'));
            if ($invalidTimestampCount > 0) {
                $this->error("- {$invalidTimestampCount} records with invalid timestamps");
                $this->error("  → Solution: Data cleanup required");
            }
            
            $parsingErrorCount = count(array_filter($problematicRecords, fn($r) => $r['issue'] === 'parsing_error'));
            if ($parsingErrorCount > 0) {
                $this->error("- {$parsingErrorCount} records with parsing errors");
                $this->error("  → Solution: Fix timestamp format issues");
            }
        } else {
            $this->info("✅ NO CRITICAL ISSUES FOUND in attendance records");
            $this->info("The negative hours issue might be coming from:");
            $this->info("- Calculation logic elsewhere");
            $this->info("- Different data source");
            $this->info("- Cached values");
        }

        $this->line("");
        $this->info("IMMEDIATE ACTIONS NEEDED:");
        if (!empty($problematicRecords)) {
            $this->info("1. Fix overnight shift handling in time calculation");
            $this->info("2. Clean up invalid timestamp data");
            $this->info("3. Add defensive programming to prevent negative calculations");
        } else {
            $this->info("1. Check for cached data or different calculation methods");
            $this->info("2. Verify the source of the -285 hours calculation");
            $this->info("3. Add logging to track calculation sources");
        }

        $this->line("");
        $this->info("🎯 SUMMARY");
        $this->info("===========");
        $this->info("This diagnostic analyzed Dr. Yaya's attendance data and found:");
        $this->info("- Total records: " . $allAttendances->count());
        $this->info("- Completed: " . count($completedRecords));
        $this->info("- Problematic: " . count($problematicRecords));
        $this->info("- Current total hours: {$currentCalculationTotal}");

        if (abs($currentCalculationTotal - (-285.14694444444)) < 0.0001) {
            $this->error("🎯 ROOT CAUSE CONFIRMED: The calculation matches the reported negative value");
        } else {
            $this->warn("🤔 CALCULATION MISMATCH: Expected -285.14694444444, got {$currentCalculationTotal}");
        }

        $this->line("");
        $this->info("Next steps: Run data cleaning and implement overnight shift handling.");

        return 0;
    }
}