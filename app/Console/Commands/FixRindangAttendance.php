<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixRindangAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fix-rindang {--dry-run : Preview without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix dr. Rindang Updated\'s stuck attendance record';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $now = Carbon::now('Asia/Jakarta');
        
        $this->info('=== FIX DR. RINDANG ATTENDANCE ISSUE ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN (preview only)' : 'LIVE (will make changes)'));
        $this->info('Current time: ' . $now->format('Y-m-d H:i:s'));
        $this->newLine();
        
        // Find dr. Rindang
        $user = User::where('email', 'dd@rrr.com')
            ->orWhere('name', 'LIKE', '%rindang%')
            ->first();
        
        if (!$user) {
            $this->error('Dr. Rindang not found');
            return Command::FAILURE;
        }
        
        $this->info("Found user: {$user->name} (ID: {$user->id}, Email: {$user->email})");
        
        // Find open attendance
        $openAttendance = Attendance::where('user_id', $user->id)
            ->whereNull('time_out')
            ->latest()
            ->first();
        
        if (!$openAttendance) {
            $this->info('✅ No open attendance found for dr. Rindang');
            return Command::SUCCESS;
        }
        
        $this->warn("Found open attendance ID: {$openAttendance->id}");
        $this->line("  Date: {$openAttendance->date}");
        $this->line("  Check-in: {$openAttendance->time_in}");
        $this->line("  Shift End: " . ($openAttendance->shift_end ?? 'NULL'));
        $this->newLine();
        
        // Calculate auto-close time
        $checkInTime = $openAttendance->time_in instanceof Carbon 
            ? $openAttendance->time_in 
            : Carbon::parse($openAttendance->time_in, 'Asia/Jakarta');
        
        // Since shift_end is null, use 8 hours from check-in
        $autoCheckoutTime = $checkInTime->copy()->addHours(8);
        
        // Don't exceed current time
        if ($autoCheckoutTime->gt($now)) {
            $autoCheckoutTime = $now->copy()->subMinutes(5);
        }
        
        $this->info("Calculated auto-checkout time: {$autoCheckoutTime->format('H:i:s')}");
        
        if (!$dryRun) {
            DB::beginTransaction();
            try {
                $openAttendance->time_out = $autoCheckoutTime->format('H:i:s');
                $openAttendance->logical_time_out = $autoCheckoutTime->format('H:i:s');
                
                // Apply 1-minute penalty for exceeding checkout tolerance
                $actualWorkMinutes = $checkInTime->diffInMinutes($autoCheckoutTime);
                $openAttendance->logical_work_minutes = 1; // 1 minute penalty
                
                $openAttendance->check_out_metadata = array_merge(
                    $openAttendance->check_out_metadata ?? [],
                    [
                        'auto_closed' => true,
                        'auto_close_reason' => 'exceeded_checkout_tolerance',
                        'auto_closed_at' => $now->format('Y-m-d H:i:s'),
                        'penalty_applied' => true,
                        'penalty_work_minutes' => 1,
                        'actual_work_minutes' => $actualWorkMinutes,
                        'fixed_by_command' => 'attendance:fix-rindang'
                    ]
                );
                
                $openAttendance->notes = ($openAttendance->notes ? $openAttendance->notes . ' | ' : '') 
                    . "Auto-closed: Exceeded checkout tolerance (1 minute penalty applied)";
                    
                $openAttendance->save();
                
                DB::commit();
                
                Log::info('FIXED dr. Rindang attendance - applied 1-minute penalty for late checkout', [
                    'attendance_id' => $openAttendance->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'date' => $openAttendance->date,
                    'time_in' => $checkInTime->format('H:i:s'),
                    'auto_time_out' => $autoCheckoutTime->format('H:i:s'),
                    'actual_work_minutes' => $actualWorkMinutes,
                    'penalty_work_minutes' => 1
                ]);
                
                $this->info('✅ Successfully closed attendance record with penalty');
                $this->info("  Actual work time: {$actualWorkMinutes} minutes");
                $this->info("  Penalty applied: 1 minute (for exceeding checkout tolerance)");
                $this->newLine();
                $this->info('Dr. Rindang can now check-in for her next shift');
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to close attendance: " . $e->getMessage());
                Log::error('Failed to fix dr. Rindang attendance', [
                    'attendance_id' => $openAttendance->id,
                    'error' => $e->getMessage()
                ]);
                return Command::FAILURE;
            }
        } else {
            $actualWorkMinutes = $checkInTime->diffInMinutes($autoCheckoutTime);
            $this->info('DRY RUN - Would perform the following actions:');
            $this->line("  Set time_out to: {$autoCheckoutTime->format('H:i:s')}");
            $this->line("  Actual work time: {$actualWorkMinutes} minutes");
            $this->line("  Apply penalty: 1 minute (for exceeding checkout tolerance)");
            $this->line("  Add penalty metadata");
            $this->newLine();
            $this->info('Run without --dry-run to apply changes');
        }
        
        return Command::SUCCESS;
    }
}