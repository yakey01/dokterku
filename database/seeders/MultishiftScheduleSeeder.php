<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MultishiftScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating proper multishift schedules...');

        // Get or create shift templates for multishift
        $shiftTemplates = $this->getOrCreateShiftTemplates();
        
        // Get test users (doctors/paramedis)
        $testUsers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['dokter', 'dokter_gigi', 'paramedis']);
        })->take(5)->get();

        if ($testUsers->isEmpty()) {
            $this->command->warn('⚠️  No test users found. Creating sample user...');
            $testUsers = collect([User::first()]);
        }

        $this->command->info("Found {$testUsers->count()} test users");

        // Create multishift schedules for the next 7 days
        $startDate = Carbon::today();
        $createdCount = 0;

        foreach ($testUsers as $user) {
            $this->command->info("📅 Creating multishift schedules for: {$user->name}");
            
            for ($day = 0; $day < 7; $day++) {
                $date = $startDate->copy()->addDays($day);
                
                // Skip Sundays
                if ($date->dayOfWeek === 0) continue;
                
                // Create 3 shifts per day with proper sequence
                $shiftsCreated = $this->createDailyMultishift($user, $date, $shiftTemplates);
                $createdCount += $shiftsCreated;
                
                $this->command->info("   ✅ Created {$shiftsCreated} shifts for {$date->format('Y-m-d')}");
            }
        }

        $this->command->info("🎉 Successfully created {$createdCount} multishift schedules!");
        
        // Show sample of what was created
        $this->showSampleSchedules();
    }

    /**
     * Get or create shift templates for multishift
     */
    private function getOrCreateShiftTemplates(): array
    {
        $templates = [
            [
                'nama_shift' => 'Shift Pagi',
                'jam_masuk' => '06:00:00',
                'jam_pulang' => '14:00:00'
            ],
            [
                'nama_shift' => 'Shift Siang',
                'jam_masuk' => '14:00:00',
                'jam_pulang' => '22:00:00'
            ],
            [
                'nama_shift' => 'Shift Malam',
                'jam_masuk' => '22:00:00',
                'jam_pulang' => '06:00:00'
            ]
        ];

        $createdTemplates = [];
        foreach ($templates as $template) {
            $createdTemplate = ShiftTemplate::firstOrCreate(
                ['nama_shift' => $template['nama_shift']],
                $template
            );
            $createdTemplates[] = $createdTemplate;
        }

        return $createdTemplates;
    }

    /**
     * Create multiple shifts for a single day with proper sequence
     */
    private function createDailyMultishift(User $user, Carbon $date, array $shiftTemplates): int
    {
        $shiftsCreated = 0;
        
        // Clear existing schedules for this user on this date
        JadwalJaga::where('pegawai_id', $user->id)
            ->whereDate('tanggal_jaga', $date)
            ->delete();

        // Create 3 shifts with proper sequence
        foreach ($shiftTemplates as $index => $shiftTemplate) {
            $shiftSequence = $index + 1; // 1, 2, 3
            
            $jadwal = JadwalJaga::create([
                'tanggal_jaga' => $date->format('Y-m-d'),
                'shift_template_id' => $shiftTemplate->id,
                'pegawai_id' => $user->id,
                'shift_sequence' => $shiftSequence, // This is the key field!
                'unit_kerja' => 'Dokter Jaga',
                'unit_instalasi' => 'UGD',
                'peran' => 'Dokter',
                'status_jaga' => 'Aktif',
                'is_overtime' => $shiftSequence > 2, // Mark 3rd shift as overtime
                'keterangan' => "Shift ke-{$shiftSequence} - {$shiftTemplate->nama_shift}",
            ]);
            
            $shiftsCreated++;
            
            $this->command->info("      - Shift {$shiftSequence}: {$shiftTemplate->nama_shift} ({$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang})");
        }
        
        return $shiftsCreated;
    }

    /**
     * Show sample of created schedules
     */
    private function showSampleSchedules(): void
    {
        $this->command->info("\n📋 Sample of created multishift schedules:");
        
        $sampleSchedules = JadwalJaga::where('unit_kerja', 'Multishift Testing')
            ->with(['shiftTemplate'])
            ->orderBy('tanggal_jaga')
            ->orderBy('pegawai_id')
            ->orderBy('shift_sequence')
            ->take(9)
            ->get();

        foreach ($sampleSchedules as $schedule) {
            $this->command->info("   📅 {$schedule->tanggal_jaga} | User {$schedule->pegawai_id} | Shift {$schedule->shift_sequence} | {$schedule->shiftTemplate->nama_shift}");
        }
    }
}
