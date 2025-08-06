<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\Pasien;
use App\Models\PermohonanCuti;
use Carbon\Carbon;

class YayaAdditionalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📊 Creating additional data for Dr. Yaya...');

        // Find Dr. Yaya
        $yaya = User::where('email', 'yaya@dokterku.com')->first();
        
        if (!$yaya) {
            $this->command->error('❌ Dr. Yaya user not found!');
            return;
        }

        $this->command->info("📋 Found: {$yaya->name} (User ID: {$yaya->id})");

        // Create additional related data
        $this->createMedicalActions($yaya);
        $this->createJaspelRecords($yaya);
        $this->createLeaveRequests($yaya);
        
        $this->command->info("🎉 Additional data creation completed for Dr. Yaya!");
    }

    /**
     * Create medical actions (tindakan) data for Dr. Yaya
     */
    private function createMedicalActions($user)
    {
        $this->command->info("🏥 Creating medical actions (tindakan) for Dr. Yaya...");
        
        // Get some common medical action types
        $jenisTindakan = JenisTindakan::take(5)->get();
        
        if ($jenisTindakan->isEmpty()) {
            $this->command->info("ℹ️  No medical action types found, skipping tindakan creation");
            return;
        }
        
        // Get some patients to assign actions to
        $patients = Pasien::take(10)->get();
        
        if ($patients->isEmpty()) {
            $this->command->info("ℹ️  No patients found, skipping tindakan creation");
            return;
        }
        
        $tindakanCount = 0;
        
        // Create medical actions for past 30 days
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Create 1-3 actions per day (randomly)
            $actionsPerDay = rand(1, 3);
            
            for ($j = 0; $j < $actionsPerDay; $j++) {
                $jenis = $jenisTindakan->random();
                $patient = $patients->random();
                
                $tindakan = Tindakan::firstOrCreate([
                    'pasien_id' => $patient->id,
                    'dokter_id' => $user->id,
                    'jenis_tindakan_id' => $jenis->id,
                    'tanggal_tindakan' => $date->format('Y-m-d'),
                    'jam_tindakan' => $date->copy()->addHours(rand(8, 16))->format('H:i:s'),
                ], [
                    'keterangan' => "Tindakan {$jenis->nama_tindakan} untuk {$patient->nama_pasien}",
                    'tarif' => $jenis->tarif ?? rand(50000, 500000),
                    'status' => 'completed',
                    'input_by' => $user->id,
                ]);
                
                if ($tindakan->wasRecentlyCreated) {
                    $tindakanCount++;
                }
            }
        }
        
        $this->command->info("✅ Created {$tindakanCount} medical action records");
    }

    /**
     * Create jaspel (incentive) records for Dr. Yaya
     */
    private function createJaspelRecords($user)
    {
        $this->command->info("💰 Creating jaspel (incentive) records for Dr. Yaya...");
        
        $jasgelCount = 0;
        
        // Create jaspel records for past 30 days based on actual model structure
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Create 0-2 jaspel entries per day (not every day has jaspel)
            $jasgelPerDay = rand(0, 2);
            
            for ($j = 0; $j < $jasgelPerDay; $j++) {
                // Calculate realistic jaspel amounts
                $amount = rand(100000, 500000); // 100k-500k per jaspel
                
                $jaspelTypes = [
                    'dokter_jaga_pagi',
                    'dokter_jaga_siang', 
                    'dokter_jaga_malam',
                    'konsultasi_khusus',
                    'tindakan_emergency',
                ];
                
                $jenisJaspel = $jaspelTypes[array_rand($jaspelTypes)];
                
                $jaspel = Jaspel::firstOrCreate([
                    'user_id' => $user->id,
                    'tanggal' => $date->format('Y-m-d'),
                    'jenis_jaspel' => $jenisJaspel,
                ], [
                    'nominal' => $amount,
                    'total_jaspel' => $amount,
                    'status_validasi' => rand(0, 1) ? 'disetujui' : 'pending',
                    'input_by' => $user->id,
                    'validasi_by' => rand(0, 1) ? 1 : null, // Admin user
                    'validasi_at' => rand(0, 1) ? $date->copy()->addHours(2) : null,
                    'catatan_validasi' => rand(0, 1) ? 'Jaspel telah diverifikasi dan disetujui' : null,
                ]);
                
                if ($jaspel->wasRecentlyCreated) {
                    $jasgelCount++;
                }
            }
        }
        
        $this->command->info("✅ Created {$jasgelCount} jaspel (incentive) records");
    }

    /**
     * Create leave requests (permohonan cuti) for Dr. Yaya
     */
    private function createLeaveRequests($user)
    {
        $this->command->info("🏖️ Creating leave requests for Dr. Yaya...");
        
        $cutiCount = 0;
        
        // Create some leave requests - both approved and pending
        $leaveRequests = [
            [
                'tanggal_mulai' => Carbon::now()->addDays(45)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->addDays(47)->format('Y-m-d'),
                'jenis_cuti' => 'Cuti Tahunan',
                'status' => 'Menunggu',
                'keterangan' => 'Permohonan cuti 3 hari untuk berlibur bersama keluarga ke Bali',
            ],
            [
                'tanggal_mulai' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(8)->format('Y-m-d'),
                'jenis_cuti' => 'Cuti Sakit',
                'status' => 'Disetujui',
                'keterangan' => 'Cuti sakit karena demam dan flu dengan surat keterangan dokter',
                'disetujui_oleh' => 1, // Admin user
                'tanggal_keputusan' => Carbon::now()->subDays(12),
            ],
            [
                'tanggal_mulai' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(28)->format('Y-m-d'),
                'jenis_cuti' => 'Cuti Khusus',
                'status' => 'Disetujui',
                'keterangan' => 'Menghadiri acara pernikahan saudara kandung',
                'disetujui_oleh' => 1, // Admin user
                'tanggal_keputusan' => Carbon::now()->subDays(35),
            ],
        ];
        
        foreach ($leaveRequests as $request) {
            $cuti = PermohonanCuti::firstOrCreate([
                'pegawai_id' => $user->id,
                'tanggal_mulai' => $request['tanggal_mulai'],
                'tanggal_selesai' => $request['tanggal_selesai'],
            ], [
                'jenis_cuti' => $request['jenis_cuti'],
                'keterangan' => $request['keterangan'],
                'status' => $request['status'],
                'disetujui_oleh' => $request['disetujui_oleh'] ?? null,
                'tanggal_keputusan' => $request['tanggal_keputusan'] ?? null,
                'catatan_approval' => $request['status'] === 'Disetujui' ? 'Permohonan disetujui' : null,
            ]);
            
            if ($cuti->wasRecentlyCreated) {
                $cutiCount++;
            }
        }
        
        $this->command->info("✅ Created {$cutiCount} leave request records");
    }
}