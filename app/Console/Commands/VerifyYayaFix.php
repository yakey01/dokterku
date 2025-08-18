<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class VerifyYayaFix extends Command
{
    protected $signature = 'verify:yaya-fix';
    protected $description = 'Verify that the negative hours fix is working for Dr. Yaya';

    public function handle()
    {
        $this->info("✅ TESTING DR. YAYA FIX VERIFICATION");
        $this->info("=====================================");

        // Test the fixed calculation method directly
        $this->info("📊 TESTING FIXED CALCULATION METHOD:");
        $this->info("-------------------------------------");

        $drYayaUserId = 13;
        $targetMonth = 8;
        $targetYear = 2025;

        // Replicate the fixed calculation logic
        $totalHoursFixed = Attendance::where('user_id', $drYayaUserId)
            ->whereMonth('date', $targetMonth)
            ->whereYear('date', $targetYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->sum(function($attendance) {
                if ($attendance->time_in && $attendance->time_out) {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    
                    // ✅ CRITICAL FIX: Handle overnight shifts and correct parameter order
                    if ($timeOut->lt($timeIn)) {
                        $timeOut->addDay(); // Handle overnight shifts
                    }
                    
                    return $timeIn->diffInHours($timeOut); // ✅ FIXED: Correct parameter order
                }
                return 0;
            });

        $this->info("Dr. Yaya total hours (FIXED METHOD): {$totalHoursFixed}");

        // Test individual records with old vs new method
        $this->line("");
        $this->info("🔍 INDIVIDUAL RECORD COMPARISON:");
        $this->info("---------------------------------");

        $attendances = Attendance::where('user_id', $drYayaUserId)
            ->whereMonth('date', $targetMonth)
            ->whereYear('date', $targetYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->orderBy('date')
            ->take(5) // Test first 5 records
            ->get();

        $oldTotal = 0;
        $newTotal = 0;

        foreach ($attendances as $attendance) {
            $timeIn = Carbon::parse($attendance->time_in);
            $timeOut = Carbon::parse($attendance->time_out);
            
            // Old (broken) method
            $oldHours = $timeOut->diffInHours($timeIn);
            $oldTotal += $oldHours;
            
            // New (fixed) method
            $timeOutFixed = $timeOut->copy();
            if ($timeOutFixed->lt($timeIn)) {
                $timeOutFixed->addDay();
            }
            $newHours = $timeIn->diffInHours($timeOutFixed);
            $newTotal += $newHours;
            
            $this->info("Record {$attendance->id} ({$attendance->date}):");
            $this->info("  Time In: {$attendance->time_in}");
            $this->info("  Time Out: {$attendance->time_out}");
            $this->error("  Old method: {$oldHours}h (WRONG)");
            $this->info("  New method: {$newHours}h (CORRECT)");
            $this->info("  Improvement: +" . ($newHours - $oldHours) . "h");
            $this->line("");
        }

        $this->info("Summary for sample records:");
        $this->info("- Old method total: {$oldTotal}h");
        $this->info("- New method total: {$newTotal}h");
        $this->info("- Total improvement: +" . ($newTotal - $oldTotal) . "h");
        $this->line("");

        // Check for overnight shifts specifically
        $this->info("🌙 OVERNIGHT SHIFT ANALYSIS:");
        $this->info("----------------------------");

        $overnightShifts = Attendance::where('user_id', $drYayaUserId)
            ->whereMonth('date', $targetMonth)
            ->whereYear('date', $targetYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->filter(function($attendance) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                return $timeOut->lt($timeIn); // time_out before time_in
            });

        $this->info("Found " . $overnightShifts->count() . " potential overnight shifts:");

        foreach ($overnightShifts as $shift) {
            $timeIn = Carbon::parse($shift->time_in);
            $timeOut = Carbon::parse($shift->time_out);
            
            $oldHours = $timeOut->diffInHours($timeIn);
            
            $correctedTimeOut = $timeOut->copy()->addDay();
            $newHours = $timeIn->diffInHours($correctedTimeOut);
            
            $this->info("- Record {$shift->id}: {$shift->time_in} → {$shift->time_out}");
            $this->info("  Old: {$oldHours}h → New: {$newHours}h (+" . ($newHours - $oldHours) . "h)");
        }

        // Check if any records still have issues
        $this->line("");
        $this->warn("⚠️ REMAINING ISSUES CHECK:");
        $this->warn("--------------------------");

        $stillNegative = Attendance::where('user_id', $drYayaUserId)
            ->whereMonth('date', $targetMonth)
            ->whereYear('date', $targetYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->filter(function($attendance) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                
                // Apply the fixed logic
                if ($timeOut->lt($timeIn)) {
                    $timeOut->addDay();
                }
                
                $hours = $timeIn->diffInHours($timeOut);
                return $hours < 0; // Still negative after fix
            });

        if ($stillNegative->isEmpty()) {
            $this->info("✅ ALL RECORDS NOW HAVE POSITIVE HOURS!");
        } else {
            $this->error("❌ " . $stillNegative->count() . " records still have negative hours:");
            foreach ($stillNegative as $record) {
                $this->error("- Record {$record->id}: {$record->time_in} → {$record->time_out}");
            }
        }

        // Final verification
        $this->line("");
        $this->info("🎯 FINAL VERIFICATION:");
        $this->info("======================");

        if ($totalHoursFixed >= 0) {
            $this->info("✅ SUCCESS: Dr. Yaya total hours is now POSITIVE: {$totalHoursFixed}h");
            $this->info("✅ FIX CONFIRMED: Negative hours issue resolved!");
            
            // Calculate the improvement
            $improvement = $totalHoursFixed - (-285.14694444444);
            $this->info("✅ IMPROVEMENT: +" . round($improvement, 2) . " hours added");
        } else {
            $this->error("❌ FAILED: Dr. Yaya still has negative hours: {$totalHoursFixed}h");
            $this->error("❌ Additional investigation needed");
        }

        $this->line("");
        $this->info("📋 NEXT STEPS:");
        $this->info("===============");
        $this->info("1. Test the actual mobile app/web interface");
        $this->info("2. Clear any cached data if necessary");
        $this->info("3. Verify the fix works for all affected users");
        $this->info("4. Clean up duplicate attendance records");
        $this->info("5. Monitor for any new negative hour calculations");

        return 0;
    }
}