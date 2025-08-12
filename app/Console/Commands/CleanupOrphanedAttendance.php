<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:cleanup {--days=1 : Number of days to look back} {--dry-run : Preview without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned attendance records (open check-ins without check-out)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysBack = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info('=== ATTENDANCE CLEANUP STARTING ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN (preview only)' : 'LIVE (will make changes)'));
        $this->info('Looking back: ' . $daysBack . ' days');
        $this->newLine();
        
        $now = Carbon::now('Asia/Jakarta');
        $today = Carbon::today('Asia/Jakarta');
        $cutoffDate = $today->copy()->subDays($daysBack);
        
        // Find orphaned records
        $orphanedQuery = Attendance::whereNotNull('time_in')
            ->whereNull('time_out')
            ->where('date', '<=', $cutoffDate->format('Y-m-d'));
        
        $orphanedCount = $orphanedQuery->count();
        
        if ($orphanedCount === 0) {
            $this->info('✅ No orphaned records found older than ' . $daysBack . ' days');
            return Command::SUCCESS;
        }
        
        $this->warn("Found {$orphanedCount} orphaned attendance records");
        
        // Process each orphaned record
        $orphanedRecords = $orphanedQuery->with('user')->get();
        $processed = 0;
        
        $this->withProgressBar($orphanedRecords, function ($attendance) use (&$processed, $dryRun, $now) {
            $checkInTime = Carbon::parse($attendance->date . ' ' . $attendance->time_in);
            $daysSince = $checkInTime->diffInDays($now);
            
            // Calculate auto-checkout time
            $autoCheckoutTime = $checkInTime->copy()->addHours(8); // Default 8 hour shift
            
            // Don't exceed end of that day
            $endOfDay = Carbon::parse($attendance->date)->endOfDay();
            if ($autoCheckoutTime->gt($endOfDay)) {
                $autoCheckoutTime = $endOfDay;
            }
            
            if (!$dryRun) {
                $attendance->time_out = $autoCheckoutTime->format('H:i:s');
                $attendance->save();
                
                Log::info('AUTO-CLOSED orphaned attendance via command', [
                    'attendance_id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'user_name' => $attendance->user->name ?? 'Unknown',
                    'date' => $attendance->date,
                    'time_in' => $attendance->time_in,
                    'auto_time_out' => $autoCheckoutTime->format('H:i:s'),
                    'days_old' => $daysSince
                ]);
            }
            
            $processed++;
        });
        
        $this->newLine(2);
        
        if ($dryRun) {
            $this->info("DRY RUN: Would have closed {$processed} orphaned records");
            $this->info("Run without --dry-run to apply changes");
        } else {
            $this->info("✅ Successfully closed {$processed} orphaned attendance records");
        }
        
        // Also check for today's abandoned sessions
        $this->newLine();
        $this->info('Checking for abandoned sessions from today...');
        
        $todayAbandoned = Attendance::whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('date', $today)
            ->get();
        
        $abandonedCount = 0;
        foreach ($todayAbandoned as $attendance) {
            $checkInTime = Carbon::parse($attendance->date . ' ' . $attendance->time_in);
            $hoursSince = $checkInTime->diffInHours($now);
            
            if ($hoursSince > 12) {
                $abandonedCount++;
                
                if (!$dryRun) {
                    $autoCheckoutTime = $checkInTime->copy()->addHours(8);
                    if ($autoCheckoutTime->gt($now)) {
                        $autoCheckoutTime = $now->copy()->subMinutes(5);
                    }
                    
                    $attendance->time_out = $autoCheckoutTime->format('H:i:s');
                    $attendance->save();
                    
                    Log::warning('AUTO-CLOSED abandoned same-day session via command', [
                        'attendance_id' => $attendance->id,
                        'user_id' => $attendance->user_id,
                        'hours_since_checkin' => $hoursSince
                    ]);
                }
            }
        }
        
        if ($abandonedCount > 0) {
            if ($dryRun) {
                $this->warn("DRY RUN: Would close {$abandonedCount} abandoned sessions from today");
            } else {
                $this->info("✅ Closed {$abandonedCount} abandoned sessions from today");
            }
        } else {
            $this->info("No abandoned sessions from today");
        }
        
        $this->newLine();
        $this->info('=== CLEANUP COMPLETED ===');
        
        return Command::SUCCESS;
    }
}