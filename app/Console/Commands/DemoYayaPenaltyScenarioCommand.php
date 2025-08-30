<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Services\AttendanceHistoryPenaltyService;
use Carbon\Carbon;

class DemoYayaPenaltyScenarioCommand extends Command
{
    protected $signature = 'demo:yaya-penalty {--create-demo : Create demo data} {--show-history : Show penalty history}';
    
    protected $description = 'Demo skenario Dr. Yaya dengan penalty 1 menit untuk incomplete checkout';
    
    protected AttendanceHistoryPenaltyService $penaltyService;
    
    public function __construct(AttendanceHistoryPenaltyService $penaltyService)
    {
        parent::__construct();
        $this->penaltyService = $penaltyService;
    }
    
    public function handle()
    {
        $this->info('🏥 DEMO: Skenario Dr. Yaya - Penalty 1 Menit Logic');
        $this->newLine();
        
        if ($this->option('create-demo')) {
            $this->createDemoScenario();
        }
        
        if ($this->option('show-history')) {
            $this->showPenaltyHistory();
        }
        
        if (!$this->option('create-demo') && !$this->option('show-history')) {
            $this->showExistingScenarios();
        }
        
        return Command::SUCCESS;
    }
    
    protected function createDemoScenario()
    {
        $this->info('📋 MEMBUAT DEMO SKENARIO DR. YAYA');
        $this->line('════════════════════════════════════');
        
        // 1. Buat atau cari user Dr. Yaya
        $yayaUser = $this->findOrCreateYayaUser();
        
        // 2. Buat shift template untuk jadwal 17:30-18:30
        $shiftTemplate = $this->createEveningShiftTemplate();
        
        // 3. Buat jadwal jaga untuk hari ini
        $jadwalJaga = $this->createTodaySchedule($yayaUser, $shiftTemplate);
        
        // 4. Buat attendance record dengan check-in saja
        $attendance = $this->createIncompleteAttendance($yayaUser, $jadwalJaga);
        
        // 5. Simulate time passage dan apply penalty
        $this->simulateTimePassageAndApplyPenalty($attendance);
        
        $this->info("✅ Demo scenario berhasil dibuat!");
        $this->line("📊 Attendance ID: {$attendance->id}");
        $this->line("👨⚕️ User: {$yayaUser->name}");
        $this->line("🕐 Jadwal: {$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}");
        $this->newLine();
    }
    
    protected function showPenaltyHistory()
    {
        $this->info('📈 HISTORY PENALTY ATTENDANCE');
        $this->line('════════════════════════════════════');
        
        // Cari user Dr. Yaya
        $yayaUser = User::where('name', 'like', '%yaya%')->first();
        if (!$yayaUser) {
            $this->warn('Dr. Yaya tidak ditemukan. Jalankan --create-demo terlebih dahulu.');
            return;
        }
        
        // Get penalty history
        $penaltyHistory = $this->penaltyService->getPenaltyAttendanceHistory($yayaUser->id);
        
        if (empty($penaltyHistory)) {
            $this->warn('Tidak ada history penalty ditemukan.');
            return;
        }
        
        $this->table([
            'Tanggal', 'Check-in', 'Check-out', 'Durasi Kerja', 'Jadwal Shift', 'Status', 'Keterangan'
        ], array_map(function ($item) {
            return [
                $item['date'] . ' (' . $item['day_name'] . ')',
                $item['time_in'] ?? '-',
                $item['time_out'] ?? '-',
                $item['work_duration_formatted'],
                ($item['shift_schedule']['scheduled_start'] ?? '-') . ' - ' . 
                ($item['shift_schedule']['scheduled_end'] ?? '-'),
                $item['status'],
                $item['penalty_info']['penalty_reason'] ?? '-'
            ];
        }, $penaltyHistory));
        
        $this->newLine();
        $this->info("📊 Total penalty records: " . count($penaltyHistory));
    }
    
    protected function showExistingScenarios()
    {
        $this->info('🔍 MENCARI SKENARIO EXISTING');
        $this->line('════════════════════════════════════');
        
        // Cari attendance yang sudah auto-closed dengan penalty
        $penaltyAttendances = Attendance::whereNotNull('logical_work_minutes')
            ->where('logical_work_minutes', '<=', 5)
            ->with(['user', 'jadwalJaga.shiftTemplate'])
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();
        
        if ($penaltyAttendances->isEmpty()) {
            $this->warn('Tidak ada skenario penalty ditemukan.');
            $this->line('💡 Jalankan: php artisan demo:yaya-penalty --create-demo');
            return;
        }
        
        $this->info("Ditemukan {$penaltyAttendances->count()} skenario penalty:");
        $this->newLine();
        
        foreach ($penaltyAttendances as $attendance) {
            $this->showAttendanceScenarioDetail($attendance);
            $this->newLine();
        }
    }
    
    protected function showAttendanceScenarioDetail(Attendance $attendance)
    {
        $metadata = $attendance->check_out_metadata ?? [];
        
        $this->line("📋 ATTENDANCE ID: {$attendance->id}");
        $this->line("👨⚕️ User: {$attendance->user->name}");
        $this->line("📅 Tanggal: {$attendance->date->format('Y-m-d l')}");
        $this->line("🕐 Check-in: " . ($attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : '-'));
        $this->line("🕑 Check-out: " . ($attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : '-'));
        $this->line("⏱️ Durasi Kerja: {$attendance->logical_work_minutes} menit");
        
        if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shift = $attendance->jadwalJaga->shiftTemplate;
            $this->line("🎯 Jadwal Shift: {$shift->jam_masuk} - {$shift->jam_pulang}");
        }
        
        if (!empty($metadata)) {
            $this->line("⚠️ Penalty Info:");
            $this->line("   - Reason: " . ($metadata['auto_close_reason'] ?? 'Unknown'));
            $this->line("   - Exceeded by: " . ($metadata['exceeded_by_minutes'] ?? 0) . ' minutes');
            $this->line("   - Tolerance: " . ($metadata['tolerance_minutes'] ?? 60) . ' minutes');
            $this->line("   - Auto-closed at: " . ($metadata['auto_closed_at'] ?? 'Unknown'));
        }
        
        if ($attendance->notes) {
            $this->line("📝 Notes: {$attendance->notes}");
        }
    }
    
    protected function findOrCreateYayaUser(): User
    {
        $user = User::where('name', 'like', '%yaya%')->first();
        
        if (!$user) {
            $this->line('👤 Membuat user Dr. Yaya...');
            $user = User::create([
                'name' => 'Dr. Yaya',
                'email' => 'yaya@dokterku.com',
                'password' => bcrypt('password123'),
                'role' => 'dokter',
                'status' => 'active'
            ]);
            $this->info("✅ User Dr. Yaya berhasil dibuat (ID: {$user->id})");
        } else {
            $this->info("✅ User Dr. Yaya ditemukan (ID: {$user->id})");
        }
        
        return $user;
    }
    
    protected function createEveningShiftTemplate(): ShiftTemplate
    {
        $shift = ShiftTemplate::firstOrCreate([
            'nama_shift' => 'Evening Clinic - Yaya'
        ], [
            'jam_masuk' => '17:30:00',
            'jam_pulang' => '18:30:00',
            'durasi_jam' => 1,
            'durasi_menit' => 0,
            'is_overnight' => false,
            'break_time_minutes' => 0,
            'status' => 'active'
        ]);
        
        if ($shift->wasRecentlyCreated) {
            $this->info("✅ Shift template berhasil dibuat: {$shift->nama_shift}");
        } else {
            $this->info("✅ Shift template ditemukan: {$shift->nama_shift}");
        }
        
        return $shift;
    }
    
    protected function createTodaySchedule(User $user, ShiftTemplate $shift): JadwalJaga
    {
        $today = Carbon::today('Asia/Jakarta');
        
        $jadwal = JadwalJaga::firstOrCreate([
            'pegawai_id' => $user->id,
            'tanggal_jaga' => $today
        ], [
            'shift_template_id' => $shift->id,
            'jam_shift' => $shift->jam_masuk . ' - ' . $shift->jam_pulang,
            'unit_kerja' => 'Dokter Jaga',
            'peran' => 'Dokter',
            'status_jaga' => 'Aktif',
            'keterangan' => 'Demo scenario for Dr. Yaya penalty logic'
        ]);
        
        if ($jadwal->wasRecentlyCreated) {
            $this->info("✅ Jadwal jaga hari ini berhasil dibuat");
        } else {
            $this->info("✅ Jadwal jaga hari ini sudah ada");
        }
        
        return $jadwal;
    }
    
    protected function createIncompleteAttendance(User $user, JadwalJaga $jadwal): Attendance
    {
        $today = Carbon::today('Asia/Jakarta');
        $checkInTime = Carbon::parse($today->format('Y-m-d') . ' 17:30:00', 'Asia/Jakarta');
        
        // Delete existing attendance for demo
        Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->delete();
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => $checkInTime->format('H:i:s'),
            'time_out' => null, // 🎯 TIDAK ADA CHECK-OUT
            'status' => 'present',
            'jadwal_jaga_id' => $jadwal->id,
            'shift_id' => $jadwal->shift_template_id,
            'shift_start' => '17:30:00',
            'shift_end' => '18:30:00',
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'location_name_in' => 'Klinik Dokterku',
            'check_in_metadata' => [
                'demo_scenario' => true,
                'created_for' => 'yaya_penalty_demo'
            ]
        ]);
        
        $this->info("✅ Attendance record dibuat (Check-in: 17:30, No check-out)");
        
        return $attendance;
    }
    
    protected function simulateTimePassageAndApplyPenalty(Attendance $attendance)
    {
        $this->line('⏳ Simulasi: Waktu berlalu melewati toleransi...');
        
        // Simulate current time is way past tolerance (e.g., 20:00)
        $simulatedTime = Carbon::parse($attendance->date->format('Y-m-d') . ' 20:00:00', 'Asia/Jakarta');
        
        $this->line("🕘 Waktu sekarang (simulasi): {$simulatedTime->format('H:i')}");
        $this->line("🎯 Shift berakhir: 18:30");
        $this->line("⚠️ Toleransi: 60 menit (maksimal check-out: 19:30)");
        $this->line("📊 Status: MELEWATI TOLERANSI sebesar " . $simulatedTime->diffInMinutes(Carbon::parse('19:30')) . " menit");
        
        // Apply penalty manually for demo
        $checkInTime = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->time_in, 'Asia/Jakarta');
        $penaltyCheckout = $checkInTime->copy()->addMinute();
        
        $attendance->time_out = $penaltyCheckout->format('H:i:s');
        $attendance->logical_time_out = $penaltyCheckout->format('H:i:s');
        $attendance->logical_work_minutes = 1;
        
        $attendance->check_out_metadata = [
            'demo_scenario' => true,
            'auto_closed' => true,
            'auto_close_reason' => 'exceeded_checkout_tolerance',
            'penalty_applied' => true,
            'penalty_work_minutes' => 1,
            'tolerance_minutes' => 60,
            'max_checkout_time' => '19:30:00',
            'simulated_current_time' => $simulatedTime->format('H:i:s'),
            'exceeded_by_minutes' => $simulatedTime->diffInMinutes(Carbon::parse('19:30')),
            'tolerance_source' => 'Demo Default'
        ];
        
        $attendance->notes = "DEMO: Auto-closed with 1 minute penalty (Dr. Yaya scenario)";
        $attendance->save();
        
        $this->info("🎯 PENALTY APPLIED:");
        $this->line("   ✅ Check-out otomatis: {$penaltyCheckout->format('H:i')}");
        $this->line("   ⚡ Durasi kerja: 1 menit (PENALTY)");
        $this->line("   📝 Metadata lengkap tersimpan untuk audit trail");
        
        $this->newLine();
        $this->info("🏆 HASIL AKHIR - SKENARIO DR. YAYA:");
        $this->line("════════════════════════════════════");
        $this->line("📅 Tanggal: {$attendance->date->format('Y-m-d')}");
        $this->line("🕐 Jadwal Jaga: 17:30 - 18:30");
        $this->line("✅ Check-in: 17:30");
        $this->line("❌ Check-out: Tidak ada (melewati toleransi)");
        $this->line("⚡ Auto Check-out: 17:31 (1 menit setelah check-in)");
        $this->line("🎯 Total Waktu Kerja: 1 MENIT (PENALTY)");
        $this->line("📋 Status History: Tercatat sebagai masuk kerja 1 menit");
    }
}