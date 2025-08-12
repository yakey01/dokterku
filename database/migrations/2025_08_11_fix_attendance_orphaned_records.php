<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations to fix orphaned attendance records
     */
    public function up(): void
    {
        // 1. Close all orphaned attendance records from previous days
        $now = Carbon::now('Asia/Jakarta');
        $today = Carbon::today('Asia/Jakarta');
        
        // Find and auto-close old open sessions
        $orphanedRecords = DB::table('attendances')
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->where('date', '<', $today->format('Y-m-d'))
            ->get();
        
        foreach ($orphanedRecords as $record) {
            // Set checkout time to 8 hours after check-in or end of day
            $checkInTime = Carbon::parse($record->date . ' ' . $record->time_in);
            $checkOutTime = $checkInTime->copy()->addHours(8);
            
            // Don't exceed end of day
            $endOfDay = Carbon::parse($record->date)->endOfDay();
            if ($checkOutTime->gt($endOfDay)) {
                $checkOutTime = $endOfDay;
            }
            
            DB::table('attendances')
                ->where('id', $record->id)
                ->update([
                    'time_out' => $checkOutTime->format('H:i:s'),
                    'updated_at' => $now
                ]);
        }
        
        echo "Closed " . count($orphanedRecords) . " orphaned attendance records\n";
        
        // 2. Close today's abandoned sessions (>12 hours old)
        $abandonedToday = DB::table('attendances')
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->where('date', $today->format('Y-m-d'))
            ->get();
        
        $closedToday = 0;
        foreach ($abandonedToday as $record) {
            $checkInTime = Carbon::parse($record->date . ' ' . $record->time_in);
            $hoursSince = $checkInTime->diffInHours($now);
            
            if ($hoursSince > 12) {
                // Session is abandoned, close it
                $checkOutTime = $checkInTime->copy()->addHours(8);
                
                if ($checkOutTime->gt($now)) {
                    $checkOutTime = $now;
                }
                
                DB::table('attendances')
                    ->where('id', $record->id)
                    ->update([
                        'time_out' => $checkOutTime->format('H:i:s'),
                        'updated_at' => $now
                    ]);
                
                $closedToday++;
            }
        }
        
        echo "Closed $closedToday abandoned sessions from today\n";
        
        // 3. Add indexes for better performance
        Schema::table('attendances', function (Blueprint $table) {
            // Add composite index for open attendance queries
            if (!Schema::hasIndex('attendances', 'idx_open_attendance')) {
                $table->index(['user_id', 'date', 'time_out'], 'idx_open_attendance');
            }
            
            // Add index for date queries
            if (!Schema::hasIndex('attendances', 'idx_attendance_date')) {
                $table->index(['date'], 'idx_attendance_date');
            }
        });
        
        echo "Added performance indexes\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_open_attendance');
            $table->dropIndex('idx_attendance_date');
        });
    }
};