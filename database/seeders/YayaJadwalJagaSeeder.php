<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\JadwalJaga;
use App\Models\User;
use App\Models\Role;
use App\Models\ShiftTemplate;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\Jaspel;
use App\Models\Pasien;
use App\Models\PermohonanCuti;
use Carbon\Carbon;

class YayaJadwalJagaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating Dr. Yaya user and jadwal jaga entries...');

        // Get or create dokter role
        $dokterRole = Role::firstOrCreate(
            ['name' => 'dokter'],
            [
                'display_name' => 'Dokter',
                'description' => 'Dokter yang bertanggung jawab atas pelayanan medis pasien',
                'permissions' => [
                    'view_dashboard_dokter',
                    'view_jadwal_jaga',
                    'view_presensi',
                    'manage_tindakan',
                    'view_laporan',
                    'view_profil',
                ],
                'is_active' => true,
            ]
        );

        // Create or update Dr. Yaya user
        $yaya = User::firstOrCreate(
            ['email' => 'yaya@dokterku.com'],
            [
                'role_id' => $dokterRole->id,
                'name' => 'dr. Yaya Rindang',
                'username' => 'yaya',
                'password' => Hash::make('password123'),
                'nip' => '198801012023001',
                'no_telepon' => '081234567890',
                'tanggal_bergabung' => Carbon::now()->subMonths(6),
                'is_active' => true,
                // Profile fields
                'phone' => '081234567890',
                'gender' => 'female',
                'date_of_birth' => Carbon::parse('1988-01-01'),
                'bio' => 'Dokter umum yang berpengalaman dalam pelayanan kesehatan masyarakat',
                'emergency_contact_name' => 'Rindang Yaya (Suami)',
                'emergency_contact_phone' => '081234567891',
                // Work settings
                'auto_check_out' => true,
                'overtime_alerts' => true,
                // Notification settings
                'email_notifications' => true,
                'push_notifications' => true,
                'attendance_reminders' => true,
                'schedule_updates' => true,
                // Privacy settings
                'profile_visibility' => 'public',
                'location_sharing' => true,
                'activity_status' => true,
                // App settings
                'language' => 'id',
                'timezone' => 'Asia/Jakarta',
                'theme' => 'light',
            ]
        );

        // Assign dokter role using Spatie Permission (if available)
        try {
            if (method_exists($yaya, 'assignRole')) {
                $yaya->assignRole('dokter');
            }
        } catch (\Exception $e) {
            // If Spatie Permission is not properly set up, continue without it
        }

        $this->command->info("✅ Dr. Yaya user created/updated:");
        $this->command->info("📧 Email: yaya@dokterku.com");
        $this->command->info("👤 Username: yaya");
        $this->command->info("🔑 Password: password123");
        $this->command->info("📋 NIP: 198801012023001");

        $this->command->info("📋 User ID: {$yaya->id}");

        // Get or create shift templates
        $shiftPagi = ShiftTemplate::firstOrCreate(
            ['nama_shift' => 'Pagi'],
            [
                'jam_masuk' => '07:00',
                'jam_pulang' => '14:00',
            ]
        );

        $shiftSiang = ShiftTemplate::firstOrCreate(
            ['nama_shift' => 'Siang'],
            [
                'jam_masuk' => '14:00',
                'jam_pulang' => '21:00',
            ]
        );

        $shiftMalam = ShiftTemplate::firstOrCreate(
            ['nama_shift' => 'Malam'],
            [
                'jam_masuk' => '21:00',
                'jam_pulang' => '07:00',
            ]
        );

        $shiftTemplates = collect([$shiftPagi, $shiftSiang, $shiftMalam]);
        
        if ($shiftTemplates->isEmpty()) {
            $this->command->error('❌ No main shift templates found!');
            return;
        }

        $this->command->info("⏰ Available shifts: " . $shiftTemplates->pluck('nama_shift')->implode(', '));

        // Create structured jadwal jaga for Dr. Yaya - 2 weeks pattern
        $startDate = Carbon::now()->addDays(1); // Start from tomorrow
        $jadwalCount = 0;
        
        // Extended 3-month schedule pattern for comprehensive data
        $schedulePattern = [];
        
        // Generate 3 months of realistic schedule data
        for ($month = 0; $month < 3; $month++) {
            $monthStart = $month * 30; // Approximate days per month
            
            // Week pattern for each month
            $weekPatterns = [
                // Week 1 of month
                ['day' => $monthStart + 0, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 1, 'shift' => $shiftSiang, 'status' => 'Aktif'],
                ['day' => $monthStart + 2, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 3, 'shift' => $shiftSiang, 'status' => 'Aktif'],
                ['day' => $monthStart + 4, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 5, 'shift' => $shiftMalam, 'status' => 'OnCall'],
                
                // Week 2 of month  
                ['day' => $monthStart + 7, 'shift' => $shiftSiang, 'status' => 'Aktif'],
                ['day' => $monthStart + 8, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 9, 'shift' => $shiftSiang, 'status' => 'Aktif'],
                ['day' => $monthStart + 10, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 11, 'shift' => $shiftMalam, 'status' => 'OnCall'],
                
                // Week 3 of month
                ['day' => $monthStart + 14, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 15, 'shift' => $shiftSiang, 'status' => 'Aktif'],
                ['day' => $monthStart + 16, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 17, 'shift' => $shiftSiang, 'status' => $month == 1 ? 'Cuti' : 'Aktif'], // Cuti in month 2
                ['day' => $monthStart + 18, 'shift' => $shiftMalam, 'status' => 'OnCall'],
                
                // Week 4 of month
                ['day' => $monthStart + 21, 'shift' => $shiftSiang, 'status' => 'Aktif'],
                ['day' => $monthStart + 22, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 23, 'shift' => $shiftSiang, 'status' => $month == 2 ? 'Izin' : 'Aktif'], // Izin in month 3
                ['day' => $monthStart + 24, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 25, 'shift' => $shiftMalam, 'status' => 'OnCall'],
                
                // Extra days for 30-day month
                ['day' => $monthStart + 28, 'shift' => $shiftPagi, 'status' => 'Aktif'],
                ['day' => $monthStart + 29, 'shift' => $shiftSiang, 'status' => 'Aktif'],
            ];
            
            $schedulePattern = array_merge($schedulePattern, $weekPatterns);
        }

        foreach ($schedulePattern as $pattern) {
            $date = $startDate->copy()->addDays($pattern['day']);
            $shiftTemplate = $pattern['shift'];
            $status = $pattern['status'];
            
            // Skip if date is Sunday
            if ($date->dayOfWeek === 0) {
                continue;
            }

            // Generate keterangan based on status and unit
            $keterangan = $this->generateKeterangan($status, 'Dokter Jaga', $shiftTemplate->nama_shift);

            // Create jadwal jaga
            $jadwal = JadwalJaga::firstOrCreate(
                [
                    'tanggal_jaga' => $date->format('Y-m-d'),
                    'shift_template_id' => $shiftTemplate->id,
                    'pegawai_id' => $yaya->id,
                ],
                [
                    'unit_instalasi' => 'Dokter Jaga', // Legacy field
                    'unit_kerja' => 'Dokter Jaga',
                    'peran' => 'Dokter',
                    'status_jaga' => $status,
                    'keterangan' => $keterangan,
                ]
            );

            if ($jadwal->wasRecentlyCreated) {
                $jadwalCount++;
                $this->command->info("✅ Created: {$date->format('Y-m-d')} | {$shiftTemplate->nama_shift} ({$shiftTemplate->jam_masuk_format} - {$shiftTemplate->jam_pulang_format}) | Dokter Jaga | {$status}");
            } else {
                $this->command->info("ℹ️  Exists: {$date->format('Y-m-d')} | {$shiftTemplate->nama_shift} | {$status}");
            }
        }

        $this->command->info("🎉 Successfully created {$jadwalCount} new jadwal jaga entries for dr Yaya!");
        $this->command->info("📱 These schedules will appear in both admin panel and doctor mobile dashboard.");
        $this->command->info("📅 Schedule covers next 3 months with realistic patterns");
        
        // Now create additional related data
        $this->createAttendanceRecords($yaya);
        $this->createMedicalActions($yaya);
        $this->createJaspelRecords($yaya);
        $this->createLeaveRequests($yaya);
        
        $this->command->info("🔑 Login credentials:");
        $this->command->info("   📧 Email: yaya@dokterku.com");
        $this->command->info("   👤 Username: yaya");
        $this->command->info("   🔑 Password: password123");
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

        if ($status === 'Izin') {
            return 'Izin keperluan keluarga';
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

    /**
     * Create realistic attendance records for Dr. Yaya's past schedules
     */
    private function createAttendanceRecords($user)
    {
        $this->command->info("📋 Creating attendance records for Dr. Yaya...");
        
        // Note: This would require an attendance model
        // Since we don't have direct access to attendance model structure,
        // we'll create placeholder logic that can be adapted
        
        $this->command->info("ℹ️  Attendance records would be created here (model structure needed)");
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
                'alasan' => 'Liburan keluarga ke Bali',
                'status' => 'pending',
                'keterangan' => 'Permohonan cuti 3 hari untuk berlibur bersama keluarga',
            ],
            [
                'tanggal_mulai' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(8)->format('Y-m-d'),
                'jenis_cuti' => 'Cuti Sakit',
                'alasan' => 'Demam dan flu',
                'status' => 'approved',
                'keterangan' => 'Cuti sakit dengan surat keterangan dokter',
                'disetujui_oleh' => 1, // Admin user
                'tanggal_disetujui' => Carbon::now()->subDays(12),
            ],
            [
                'tanggal_mulai' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(28)->format('Y-m-d'),
                'jenis_cuti' => 'Cuti Khusus',
                'alasan' => 'Acara keluarga (pernikahan saudara)',
                'status' => 'approved',
                'keterangan' => 'Menghadiri pernikahan saudara kandung',
                'disetujui_oleh' => 1, // Admin user
                'tanggal_disetujui' => Carbon::now()->subDays(35),
            ],
        ];
        
        foreach ($leaveRequests as $request) {
            $cuti = PermohonanCuti::firstOrCreate([
                'pegawai_id' => $user->id,
                'tanggal_mulai' => $request['tanggal_mulai'],
                'tanggal_selesai' => $request['tanggal_selesai'],
            ], [
                'jenis_cuti' => $request['jenis_cuti'],
                'alasan' => $request['alasan'],
                'keterangan' => $request['keterangan'],
                'status' => $request['status'],
                'disetujui_oleh' => $request['disetujui_oleh'] ?? null,
                'tanggal_disetujui' => $request['tanggal_disetujui'] ?? null,
                'tanggal_pengajuan' => Carbon::parse($request['tanggal_mulai'])->subDays(7),
            ]);
            
            if ($cuti->wasRecentlyCreated) {
                $cutiCount++;
            }
        }
        
        $this->command->info("✅ Created {$cutiCount} leave request records");
    }
}