<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

class CreateYayaScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find user Yaya
        $yaya = User::where('name', 'like', '%Yaya%')
            ->orWhere('email', 'like', '%yaya%')
            ->first();

        if (!$yaya) {
            $this->command->error('User Yaya not found!');
            return;
        }

        $this->command->info("Found user: {$yaya->name} (ID: {$yaya->id})");

        // Get or create shift template
        $shiftTemplate = ShiftTemplate::firstOrCreate([
            'nama_shift' => 'Shift Pagi'
        ], [
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'durasi_jam' => 8,
            'warna' => '#10b981'
        ]);

        $this->command->info("Using shift template: {$shiftTemplate->nama_shift}");

        // Create schedule for today
        $today = Carbon::today();
        
        // Check if schedule already exists
        $existingSchedule = JadwalJaga::where('pegawai_id', $yaya->id)
            ->whereDate('tanggal_jaga', $today)
            ->first();

        if ($existingSchedule) {
            $this->command->warn("Schedule for today already exists (ID: {$existingSchedule->id})");
            
            // Update to active if not active
            if ($existingSchedule->status_jaga !== 'Aktif') {
                $existingSchedule->update(['status_jaga' => 'Aktif']);
                $this->command->info("Updated schedule status to 'Aktif'");
            }
            
            return;
        }

        // Create new schedule
        $schedule = JadwalJaga::create([
            'tanggal_jaga' => $today,
            'shift_template_id' => $shiftTemplate->id,
            'pegawai_id' => $yaya->id,
            'unit_kerja' => 'Dokter Jaga',
            'unit_instalasi' => 'UGD',
            'peran' => 'Dokter',
            'status_jaga' => 'Aktif',
            'keterangan' => 'Jadwal jaga untuk testing validasi presensi'
        ]);

        $this->command->info("✅ Created schedule for Yaya:");
        $this->command->info("   - Date: {$schedule->tanggal_jaga->format('Y-m-d')}");
        $this->command->info("   - Shift: {$shiftTemplate->nama_shift}");
        $this->command->info("   - Time: {$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}");
        $this->command->info("   - Status: {$schedule->status_jaga}");
        $this->command->info("   - Unit: {$schedule->unit_kerja}");

        // Create schedules for next few days
        for ($i = 1; $i <= 7; $i++) {
            $futureDate = $today->copy()->addDays($i);
            
            // Skip Sundays
            if ($futureDate->dayOfWeek === 0) {
                continue;
            }

            $futureSchedule = JadwalJaga::create([
                'tanggal_jaga' => $futureDate,
                'shift_template_id' => $shiftTemplate->id,
                'pegawai_id' => $yaya->id,
                'unit_kerja' => 'Dokter Jaga',
                'unit_instalasi' => 'UGD',
                'peran' => 'Dokter',
                'status_jaga' => 'Aktif',
                'keterangan' => 'Jadwal jaga untuk testing validasi presensi'
            ]);

            $this->command->info("✅ Created schedule for {$futureDate->format('Y-m-d')}");
        }

        $this->command->info("🎉 Successfully created schedules for Yaya!");
    }
}
