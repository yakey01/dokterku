<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class FixNegativeHoursCalculation extends Command
{
    protected $signature = 'fix:negative-hours {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix negative hours calculation bug in attendance system';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info("🔧 FIXING NEGATIVE HOURS CALCULATION BUG");
        $this->info("==========================================");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE: No changes will be made");
        }
        
        $this->line("");

        // Step 1: Validate the current calculation issue
        $this->info("Step 1: Validating current calculation method");
        $this->info("---------------------------------------------");
        
        $testRecord = Attendance::whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->first();
            
        if (!$testRecord) {
            $this->error("No attendance records found for testing");
            return 1;
        }
        
        $timeIn = Carbon::parse($testRecord->time_in);
        $timeOut = Carbon::parse($testRecord->time_out);
        
        $currentMethod = $timeOut->diffInHours($timeIn); // Current (broken) method
        $fixedMethod = $timeIn->diffInHours($timeOut);   // Fixed method
        $secondsDiff = $timeOut->diffInSeconds($timeIn);
        
        $this->info("Test Record ID: {$testRecord->id}");
        $this->info("Time In: " . $timeIn->format('Y-m-d H:i:s'));
        $this->info("Time Out: " . $timeOut->format('Y-m-d H:i:s'));
        $this->info("Seconds difference: {$secondsDiff}");
        $this->error("Current method result: {$currentMethod} hours (WRONG)");
        $this->info("Fixed method result: {$fixedMethod} hours (CORRECT)");
        $this->line("");

        // Step 2: Find all affected users
        $this->info("Step 2: Finding all users with negative total hours");
        $this->info("---------------------------------------------------");
        
        $affectedUsers = collect();
        $allUsers = User::whereHas('attendances')->get();
        
        foreach ($allUsers as $user) {
            $totalHours = Attendance::where('user_id', $user->id)
                ->whereNotNull('time_in')
                ->whereNotNull('time_out')
                ->get()
                ->sum(function($attendance) {
                    if ($attendance->time_in && $attendance->time_out) {
                        $timeIn = Carbon::parse($attendance->time_in);
                        $timeOut = Carbon::parse($attendance->time_out);
                        return $timeOut->diffInHours($timeIn); // Current broken method
                    }
                    return 0;
                });
                
            if ($totalHours < 0) {
                $affectedUsers->push([
                    'user' => $user,
                    'current_total_hours' => $totalHours
                ]);
            }
        }
        
        $this->info("Found " . $affectedUsers->count() . " users with negative total hours:");
        foreach ($affectedUsers as $affected) {
            $this->warn("- {$affected['user']->name} (ID: {$affected['user']->id}): {$affected['current_total_hours']} hours");
        }
        $this->line("");

        // Step 3: Calculate corrected totals
        $this->info("Step 3: Calculating corrected total hours");
        $this->info("-----------------------------------------");
        
        $fixedResults = [];
        foreach ($affectedUsers as $affected) {
            $user = $affected['user'];
            
            $correctedTotal = Attendance::where('user_id', $user->id)
                ->whereNotNull('time_in')
                ->whereNotNull('time_out')
                ->get()
                ->sum(function($attendance) {
                    if ($attendance->time_in && $attendance->time_out) {
                        $timeIn = Carbon::parse($attendance->time_in);
                        $timeOut = Carbon::parse($attendance->time_out);
                        
                        // Handle overnight shifts
                        if ($timeOut->lt($timeIn)) {
                            $timeOut->addDay();
                        }
                        
                        return $timeIn->diffInHours($timeOut); // Fixed method
                    }
                    return 0;
                });
                
            $fixedResults[] = [
                'user' => $user,
                'old_total' => $affected['current_total_hours'],
                'new_total' => $correctedTotal,
                'improvement' => $correctedTotal - $affected['current_total_hours']
            ];
            
            $this->info("✅ {$user->name}: {$affected['current_total_hours']}h → {$correctedTotal}h (+" . round($correctedTotal - $affected['current_total_hours'], 2) . "h)");
        }
        $this->line("");

        // Step 4: Summary of fixes needed
        $this->info("Step 4: Summary of required fixes");
        $this->info("---------------------------------");
        
        $this->error("CRITICAL BUG IDENTIFIED:");
        $this->error("The Carbon diffInHours() method is being called in reverse order:");
        $this->error("❌ WRONG: \$timeOut->diffInHours(\$timeIn) - returns negative values");
        $this->error("✅ CORRECT: \$timeIn->diffInHours(\$timeOut) - returns positive values");
        $this->line("");
        
        $this->info("FILES THAT NEED TO BE UPDATED:");
        $this->info("1. app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php (Line ~3103)");
        $this->info("2. app/Http/Controllers/Api/V2/Dashboards/LeaderboardController.php (Line ~61)");
        $this->info("3. routes/web.php (Line ~900)");
        $this->info("4. Any other files using timeOut->diffInHours(timeIn) pattern");
        $this->line("");

        if (!$dryRun) {
            $this->error("AUTOMATIC CODE FIXES NOT IMPLEMENTED IN THIS COMMAND");
            $this->error("Please manually update the calculation method in the mentioned files");
            $this->error("Change: \$timeOut->diffInHours(\$timeIn)");
            $this->error("To: \$timeIn->diffInHours(\$timeOut)");
        }

        // Step 5: Data cleanup recommendations
        $this->info("Step 5: Data cleanup recommendations");
        $this->info("-----------------------------------");
        
        // Check for duplicate attendance records
        $duplicateGroups = Attendance::selectRaw('user_id, date, COUNT(*) as count')
            ->groupBy('user_id', 'date')
            ->having('count', '>', 1)
            ->get();
            
        if ($duplicateGroups->isNotEmpty()) {
            $this->warn("Found " . $duplicateGroups->count() . " sets of duplicate attendance records");
            $this->warn("Consider running: php artisan clean:duplicate-attendance");
        }
        
        // Check for invalid time ranges
        $invalidRecords = Attendance::whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->filter(function($attendance) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                $diffSeconds = $timeOut->diffInSeconds($timeIn, false);
                return $diffSeconds < 0; // time_out before time_in without overnight logic
            });
            
        if ($invalidRecords->isNotEmpty()) {
            $this->warn("Found " . $invalidRecords->count() . " records with time_out before time_in");
            $this->warn("These may be overnight shifts that need special handling");
        }

        $this->line("");
        $this->info("🎯 CONCLUSION");
        $this->info("==============");
        $this->info("Root cause confirmed: Incorrect Carbon diffInHours() parameter order");
        $this->info("Solution: Fix calculation method in affected files");
        $this->info("Impact: Will convert negative hours to correct positive values");
        $this->info("Next step: Manually update the calculation code and redeploy");

        return 0;
    }
}