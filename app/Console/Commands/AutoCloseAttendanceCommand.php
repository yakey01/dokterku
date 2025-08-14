<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceToleranceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AutoCloseAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-close {--dry-run : Preview without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-close attendance records that have exceeded checkout tolerance with 1 minute work time penalty';

    protected AttendanceToleranceService $toleranceService;

    public function __construct(AttendanceToleranceService $toleranceService)
    {
        parent::__construct();
        $this->toleranceService = $toleranceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $now = Carbon::now('Asia/Jakarta');
        $today = Carbon::today('Asia/Jakarta');
        
        $this->info('=== AUTO-CLOSE ATTENDANCE BASED ON TOLERANCE ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN (preview only)' : 'LIVE (will make changes)'));
        $this->info('Current time: ' . $now->format('Y-m-d H:i:s'));
        $this->newLine();
        
        // Find open attendance records for today
        $openAttendances = Attendance::with(['user', 'user.workLocation'])
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('date', '<=', $today)
            ->get();
        
        if ($openAttendances->isEmpty()) {
            $this->info('✅ No open attendance records found');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$openAttendances->count()} open attendance records");
        $processedCount = 0;
        $autoClosedCount = 0;
        
        foreach ($openAttendances as $attendance) {
            $processedCount++;
            
            // Skip if no user
            if (!$attendance->user) {
                $this->warn("Skipping attendance ID {$attendance->id} - user not found");
                continue;
            }
            
            $user = $attendance->user;
            // Parse check-in time correctly
            $dateString = $attendance->date instanceof Carbon 
                ? $attendance->date->format('Y-m-d') 
                : $attendance->date;
            
            // Handle time_in which might be a full datetime or just time
            if ($attendance->time_in instanceof Carbon) {
                $checkInTime = $attendance->time_in->setTimezone('Asia/Jakarta');
            } elseif (strpos($attendance->time_in, '-') !== false) {
                // time_in already contains date, parse as is
                $checkInTime = Carbon::parse($attendance->time_in, 'Asia/Jakarta');
            } else {
                // time_in is just time, combine with date
                $checkInTime = Carbon::parse($dateString . ' ' . $attendance->time_in, 'Asia/Jakarta');
            }
            
            // Get checkout tolerance for this user
            $toleranceData = $this->toleranceService->getCheckoutTolerance($user, $attendance->date);
            $lateTolerance = $toleranceData['late'] ?? 60; // Default 60 minutes
            
            // Calculate the maximum allowed checkout time based on shift end + tolerance
            $shiftEnd = null;
            if ($attendance->shift_end) {
                // Handle if shift_end is already a Carbon instance or a time string
                if ($attendance->shift_end instanceof Carbon) {
                    $shiftEnd = Carbon::parse($dateString . ' ' . $attendance->shift_end->format('H:i:s'), 'Asia/Jakarta');
                } else {
                    $shiftEnd = Carbon::parse($dateString . ' ' . $attendance->shift_end, 'Asia/Jakarta');
                }
            } else {
                $shiftEnd = $checkInTime->copy()->addHours(8); // Default 8 hour shift if no shift end
            }
            
            $maxCheckoutTime = $shiftEnd->copy()->addMinutes($lateTolerance);
            
            // Check if current time has exceeded the tolerance
            if ($now->gt($maxCheckoutTime)) {
                // Auto-close with 1 minute work time as penalty
                $autoCheckoutTime = $checkInTime->copy()->addMinute();
                
                // Ensure auto-checkout doesn't exceed current time
                if ($autoCheckoutTime->gt($now)) {
                    $autoCheckoutTime = $now->copy();
                }
                
                // Format for database storage
                $checkoutTimeFormatted = $autoCheckoutTime->format('H:i:s');
                
                if (!$dryRun) {
                    DB::beginTransaction();
                    try {
                        $attendance->time_out = $checkoutTimeFormatted;
                        $attendance->logical_time_out = $checkoutTimeFormatted;
                        $attendance->logical_work_minutes = 1; // 1 minute penalty
                        $attendance->check_out_metadata = array_merge(
                            $attendance->check_out_metadata ?? [],
                            [
                                'auto_closed' => true,
                                'auto_close_reason' => 'exceeded_checkout_tolerance',
                                'tolerance_minutes' => $lateTolerance,
                                'max_checkout_time' => $maxCheckoutTime->format('Y-m-d H:i:s'),
                                'auto_closed_at' => $now->format('Y-m-d H:i:s'),
                                'penalty_work_minutes' => 1,
                                'tolerance_source' => $toleranceData['source'] ?? 'Default'
                            ]
                        );
                        $attendance->notes = ($attendance->notes ? $attendance->notes . ' | ' : '') 
                            . "Auto-closed: Exceeded checkout tolerance (1 minute work time penalty)";
                        $attendance->save();
                        
                        DB::commit();
                        
                        Log::warning('AUTO-CLOSED attendance - exceeded checkout tolerance', [
                            'attendance_id' => $attendance->id,
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'date' => $attendance->date,
                            'time_in' => $attendance->time_in,
                            'auto_time_out' => $checkoutTimeFormatted,
                            'shift_end' => $shiftEnd->format('H:i:s'),
                            'tolerance_minutes' => $lateTolerance,
                            'max_checkout_time' => $maxCheckoutTime->format('H:i:s'),
                            'exceeded_by_minutes' => $now->diffInMinutes($maxCheckoutTime),
                            'penalty_work_minutes' => 1,
                            'tolerance_source' => $toleranceData['source'] ?? 'Default'
                        ]);
                        
                        $autoClosedCount++;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("Failed to auto-close attendance ID {$attendance->id}: " . $e->getMessage());
                        Log::error('Failed to auto-close attendance', [
                            'attendance_id' => $attendance->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    // Dry run - just show what would happen
                    $this->line("Would auto-close attendance ID {$attendance->id}:");
                    $this->line("  User: {$user->name} (ID: {$user->id})");
                    $this->line("  Check-in: {$checkInTime->format('H:i:s')}");
                    $this->line("  Shift end: {$shiftEnd->format('H:i:s')}");
                    $this->line("  Tolerance: {$lateTolerance} minutes (Source: {$toleranceData['source']})");
                    $this->line("  Max checkout: {$maxCheckoutTime->format('H:i:s')}");
                    $this->line("  Exceeded by: {$now->diffInMinutes($maxCheckoutTime)} minutes");
                    $this->line("  Auto-checkout: {$checkoutTimeFormatted} (1 minute work time penalty)");
                    $this->newLine();
                    
                    $autoClosedCount++;
                }
            } else {
                $remainingMinutes = $now->diffInMinutes($maxCheckoutTime);
                $this->info("Attendance ID {$attendance->id} for {$user->name} - Still within tolerance ({$remainingMinutes} minutes remaining)");
            }
        }
        
        $this->newLine();
        
        if ($dryRun) {
            $this->info("DRY RUN SUMMARY:");
            $this->info("  Processed: {$processedCount} records");
            $this->info("  Would auto-close: {$autoClosedCount} records");
            $this->info("Run without --dry-run to apply changes");
        } else {
            $this->info("✅ AUTO-CLOSE COMPLETED");
            $this->info("  Processed: {$processedCount} records");
            $this->info("  Auto-closed: {$autoClosedCount} records");
        }
        
        return Command::SUCCESS;
    }
}