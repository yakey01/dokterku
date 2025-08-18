<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\JumlahPasienHarian;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Services\AttendanceToleranceService;
use App\Services\EffectiveDurationCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DokterDashboardControllerFixed extends Controller
{
    /**
     * Dashboard utama dokter dengan stats real dari JumlahPasienHarian
     * FIXED: Menggunakan JumlahPasienHarian sebagai sumber utama jumlah pasien
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Handle missing user gracefully
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' => null
                ], 401);
            }
            
            // Try to find dokter record
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();

            // Cache dashboard stats untuk 2 menit
            $cacheKey = "dokter_dashboard_stats_fixed_{$user->id}";
            $stats = Cache::remember($cacheKey, 120, function () use ($dokter, $user) {
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                $thisWeek = Carbon::now()->startOfWeek();

                // FIXED: Use JumlahPasienHarian for patient counts
                $patientsToday = 0;
                $patientsMonth = 0;
                $tindakanToday = 0;
                
                if ($dokter) {
                    // Get today's patient count from JumlahPasienHarian
                    $jumlahPasienToday = JumlahPasienHarian::where('dokter_id', $dokter->id)
                        ->whereDate('tanggal', $today)
                        ->whereIn('status_validasi', ['approved', 'disetujui'])
                        ->first();
                    
                    if ($jumlahPasienToday) {
                        $patientsToday = $jumlahPasienToday->jumlah_pasien_umum + 
                                        $jumlahPasienToday->jumlah_pasien_bpjs;
                    }
                    
                    // Get this month's patient count from JumlahPasienHarian
                    $jumlahPasienMonth = JumlahPasienHarian::where('dokter_id', $dokter->id)
                        ->whereMonth('tanggal', $thisMonth->month)
                        ->whereYear('tanggal', $thisMonth->year)
                        ->whereIn('status_validasi', ['approved', 'disetujui'])
                        ->get();
                    
                    $patientsMonth = $jumlahPasienMonth->sum('jumlah_pasien_umum') + 
                                   $jumlahPasienMonth->sum('jumlah_pasien_bpjs');

                    // Still use Tindakan for procedures count
                    $tindakanToday = Tindakan::where('dokter_id', $dokter->id)
                        ->whereDate('tanggal_tindakan', $today)
                        ->count();
                }

                // JASPEL calculation remains the same
                $jaspelMonth = Jaspel::where('user_id', $user->id)
                    ->whereMonth('tanggal', $thisMonth->month)
                    ->whereYear('tanggal', $thisMonth->year)
                    ->whereIn('status_validasi', ['disetujui', 'approved'])
                    ->sum('nominal');

                // Shifts calculation
                $shiftsWeek = JadwalJaga::where('pegawai_id', $user->id)
                    ->where('tanggal_jaga', '>=', $thisWeek)
                    ->where('tanggal_jaga', '<=', Carbon::now()->endOfWeek())
                    ->count();

                // Attendance today
                $attendanceToday = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();

                return [
                    'patients_today' => $patientsToday,
                    'patients_month' => $patientsMonth, // Added monthly total
                    'tindakan_today' => $tindakanToday,
                    'jaspel_month' => $jaspelMonth,
                    'shifts_week' => $shiftsWeek,
                    'attendance_today' => $attendanceToday ? [
                        'check_in' => $attendanceToday->time_in?->format('H:i'),
                        'check_out' => $attendanceToday->time_out?->format('H:i'),
                        'status' => $attendanceToday->time_out ? 'checked_out' : 'checked_in',
                        'duration' => $attendanceToday->formatted_work_duration
                    ] : null
                ];
            });

            // Get patient details from JumlahPasienHarian
            $patientDetails = [];
            if ($dokter) {
                $recentPatientData = JumlahPasienHarian::where('dokter_id', $dokter->id)
                    ->whereIn('status_validasi', ['approved', 'disetujui'])
                    ->orderBy('tanggal', 'desc')
                    ->limit(5)
                    ->get();
                
                $patientDetails = $recentPatientData->map(function($record) {
                    return [
                        'tanggal' => $record->tanggal->format('d/m/Y'),
                        'poli' => ucfirst($record->poli),
                        'shift' => $record->shift,
                        'pasien_umum' => $record->jumlah_pasien_umum,
                        'pasien_bpjs' => $record->jumlah_pasien_bpjs,
                        'total' => $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs,
                        'jaspel' => $record->jaspel_rupiah,
                        'status' => $record->status_validasi
                    ];
                })->toArray();
            }

            // Performance metrics
            $performanceStats = $dokter ? $this->getPerformanceStats($dokter) : [
                'average_response_time' => 0,
                'patient_satisfaction' => 0,
                'treatment_success_rate' => 0
            ];
            
            // Next schedule
            $nextSchedule = $this->getNextSchedule($user);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data berhasil dimuat',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $dokter ? $dokter->nama_lengkap : $user->name,
                        'email' => $dokter ? $dokter->email : $user->email,
                        'phone' => $dokter ? $dokter->no_telepon : $user->phone,
                        'address' => $dokter ? $dokter->alamat : $user->address,
                        'date_of_birth' => $dokter ? $dokter->tanggal_lahir?->format('d F Y') : $user->date_of_birth?->format('d F Y'),
                        'gender' => $this->formatGender($dokter ? $dokter->jenis_kelamin : $user->gender),
                        'bio' => $dokter ? $dokter->keterangan : $user->bio,
                        'nik' => $dokter ? $dokter->nik : null,
                        'nomor_sip' => $dokter ? $dokter->nomor_sip : null,
                        'jabatan' => $dokter ? $dokter->jabatan : 'Dokter',
                        'spesialis' => $dokter ? $dokter->spesialis : null,
                        'aktif' => $dokter ? $dokter->aktif : true,
                    ],
                    'stats' => $stats,
                    'patient_details' => $patientDetails, // Added patient details
                    'performance' => $performanceStats,
                    'next_schedule' => $nextSchedule,
                    'notification_count' => $this->getUnreadNotificationCount($user),
                    'last_updated' => now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats($dokter)
    {
        // Implementation remains the same as original
        return [
            'average_response_time' => rand(10, 30),
            'patient_satisfaction' => rand(85, 98),
            'treatment_success_rate' => rand(90, 99)
        ];
    }

    /**
     * Get next schedule
     */
    private function getNextSchedule($user)
    {
        $nextJadwal = JadwalJaga::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>=', Carbon::today())
            ->orderBy('tanggal_jaga')
            ->first();

        if ($nextJadwal) {
            return [
                'date' => $nextJadwal->tanggal_jaga->format('d F Y'),
                'shift' => $nextJadwal->shift,
                'location' => $nextJadwal->lokasi ?? 'Klinik Utama'
            ];
        }

        return null;
    }

    /**
     * Format gender for display
     */
    private function formatGender($gender)
    {
        return match(strtolower($gender)) {
            'l', 'laki-laki', 'male' => 'Laki-laki',
            'p', 'perempuan', 'female' => 'Perempuan',
            default => $gender
        };
    }

    /**
     * Get unread notification count
     */
    private function getUnreadNotificationCount($user)
    {
        // Implement notification counting logic
        return 0;
    }
}