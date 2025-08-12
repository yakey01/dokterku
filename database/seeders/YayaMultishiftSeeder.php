<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

class YayaMultishiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating multishift schedules for Yaya...');

        // Find Yaya
        $yaya = User::where('name', 'LIKE', '%yaya%')->first();
        if (!$yaya) {
            $this->command->error('❌ User Yaya not found!');
            return;
        }

        $this->command->info("✅ Found Yaya: {$yaya->name} (ID: {$yaya->id})");

        // Get or create shift templates
        $shiftTemplates = $this->getOrCreateShiftTemplates();
        
        // Clear existing schedules for Yaya today
        $today = Carbon::today();
        JadwalJaga::where('pegawai_id', $yaya->id)
            ->whereDate('tanggal_jaga', $today)
            ->delete();

        $this->command->info("🗑️  Cleared existing schedules for today");

        // Create 3 shifts with proper sequence
        $shiftsCreated = 0;
        foreach ($shiftTemplates as $index => $shiftTemplate) {
            $shiftSequence = $index + 1; // 1, 2, 3
            
            $jadwal = JadwalJaga::create([
                'tanggal_jaga' => $today->format('Y-m-d'),
                'shift_template_id' => $shiftTemplate->id,
                'pegawai_id' => $yaya->id,
                'shift_sequence' => $shiftSequence, // This is the key field!
                'unit_kerja' => 'Dokter Jaga',
                'unit_instalasi' => 'UGD',
                'peran' => 'Dokter',
                'status_jaga' => 'Aktif',
                'is_overtime' => $shiftSequence > 2, // Mark 3rd shift as overtime
                'keterangan' => "Shift ke-{$shiftSequence} - {$shiftTemplate->nama_shift}",
            ]);
            
            $shiftsCreated++;
            
            $this->command->info("   ✅ Shift {$shiftSequence}: {$shiftTemplate->nama_shift} ({$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang})");
        }

        $this->command->info("🎉 Successfully created {$shiftsCreated} multishift schedules for Yaya!");
        
        // Verify what was created
        $this->verifySchedules($yaya, $today);
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
     * Verify the created schedules
     */
    private function verifySchedules(User $yaya, Carbon $date): void
    {
        $this->command->info("\n📋 Verification of created schedules:");
        
        $schedules = JadwalJaga::where('pegawai_id', $yaya->id)
            ->whereDate('tanggal_jaga', $date)
            ->with(['shiftTemplate'])
            ->orderBy('shift_sequence')
            ->get();

        foreach ($schedules as $schedule) {
            $this->command->info("   📅 Shift {$schedule->shift_sequence}: {$schedule->shiftTemplate->nama_shift} ({$schedule->shiftTemplate->jam_masuk} - {$schedule->shiftTemplate->jam_pulang})");
        }

        $this->command->info("\n🔍 Database verification:");
        $this->command->info("   - Total schedules: {$schedules->count()}");
        $this->command->info("   - Shift sequences: " . $schedules->pluck('shift_sequence')->implode(', '));
        $this->command->info("   - Overtime shifts: " . $schedules->where('is_overtime', true)->count());
    }
}
