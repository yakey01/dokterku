<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JadwalJaga;
use App\Models\User;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

class YayaJadwalJagaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating 10 new jadwal jaga entries for dr Yaya Mulyana...');

        // Find Yaya
        $yaya = User::find(10);
        
        if (!$yaya) {
            $this->command->error('❌ User Yaya (ID: 10) not found!');
            return;
        }

        $this->command->info("📋 Found: {$yaya->name} (User ID: {$yaya->id})");

        // Get only main shift templates (skip test templates)
        $shiftTemplates = ShiftTemplate::whereIn('nama_shift', ['Pagi', 'Siang', 'Malam'])->get();
        
        if ($shiftTemplates->isEmpty()) {
            $this->command->error('❌ No main shift templates found!');
            return;
        }

        $this->command->info("⏰ Available shifts: " . $shiftTemplates->pluck('nama_shift')->implode(', '));

        // Create 10 jadwal jaga entries starting from next week
        $startDate = Carbon::now()->addWeek()->startOfWeek(); // Start from next Monday
        $jadwalCount = 0;
        $targetCount = 10;

        // Define statuses
        $statuses = ['Aktif', 'Aktif', 'Aktif', 'Aktif', 'Cuti', 'OnCall', 'Izin']; // Allowed: Aktif, Cuti, Izin, OnCall

        // Try to create 10 entries, but handle duplicates gracefully
        $attemptCount = 0;
        $maxAttempts = 30; // Prevent infinite loop

        while ($jadwalCount < $targetCount && $attemptCount < $maxAttempts) {
            $attemptCount++;
            
            // Calculate date (spread over several weeks)
            $date = $startDate->copy()->addDays(rand(0, 21)); // Spread over 3 weeks
            
            // Skip Sundays
            if ($date->dayOfWeek === 0) {
                continue;
            }

            // Select shift template randomly
            $shiftTemplate = $shiftTemplates->random();
            
            // Select work unit and role
            $unitKerja = 'Dokter Jaga'; // Fixed value for doctors
            $peran = 'Dokter'; // Fixed value for doctors
            $status = $statuses[array_rand($statuses)];

            // Generate keterangan based on status and unit
            $keterangan = $this->generateKeterangan($status, $unitKerja, $shiftTemplate->nama_shift);

            // Check if this exact combination already exists
            $exists = JadwalJaga::where('pegawai_id', $yaya->id)
                ->where('tanggal_jaga', $date->format('Y-m-d'))
                ->where('shift_template_id', $shiftTemplate->id)
                ->exists();

            if ($exists) {
                continue; // Skip silently and try another combination
            }

            // Create jadwal jaga
            $jadwal = JadwalJaga::create([
                'tanggal_jaga' => $date->format('Y-m-d'),
                'shift_template_id' => $shiftTemplate->id,
                'pegawai_id' => $yaya->id,
                'unit_kerja' => $unitKerja,
                'peran' => $peran,
                'status_jaga' => $status,
                'keterangan' => $keterangan,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $jadwalCount++;
            $this->command->info("✅ Created: {$date->format('Y-m-d')} | {$shiftTemplate->nama_shift} ({$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}) | {$unitKerja} | {$status}");
        }

        $this->command->info("🎉 Successfully created {$jadwalCount} new jadwal jaga entries for dr Yaya!");
        $this->command->info("📱 These schedules will appear in both admin panel and doctor mobile dashboard.");
    }

    /**
     * Generate realistic keterangan based on status and context
     */
    private function generateKeterangan($status, $unitKerja, $shiftName): string
    {
        if ($status === 'Cuti') {
            return 'Cuti tahunan - jadwal digantikan';
        }

        if ($status === 'OnCall') {
            return 'Standby panggilan darurat';
        }

        // Generate based on unit and shift
        $keteranganMap = [
            'Dokter Jaga' => [
                'Pagi' => 'Jaga pagi rutin - pemeriksaan pasien rawat jalan',
                'Siang' => 'Shift siang - konsultasi dan tindakan medis',
                'Malam' => 'Jaga malam - standby emergency dan rawat inap'
            ]
        ];

        return $keteranganMap[$unitKerja][$shiftName] ?? "Tugas {$unitKerja} shift {$shiftName}";
    }
}