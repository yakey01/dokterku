<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AttendanceRecapDetailController extends Controller
{
    public function show(Request $request)
    {
        $staffId = $request->get('staff_id');
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $staffType = $request->get('staff_type');
        $returnUrl = $request->get('return_url', route('filament.admin.resources.attendance-recaps.index'));
        
        if (!$staffId) {
            abort(404, 'Staff ID not provided');
        }
        
        // Get the attendance data
        $data = AttendanceRecap::getRecapData($month, $year, $staffType);
        $record = $data->firstWhere('staff_id', $staffId);
        
        if (!$record) {
            abort(404, 'Attendance record not found');
        }
        
        // Convert to model for easier use in view
        $attendanceRecord = new AttendanceRecap();
        $attendanceRecord->fill($record);
        $attendanceRecord->id = $record['staff_id'];
        
        // Get daily attendance breakdown
        $dailyAttendance = $this->getDailyAttendanceData($staffId, $month, $year, $record['staff_type']);
        
        // Month name for display
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return view('admin.attendance-recap.detail', [
            'record' => $attendanceRecord,
            'dailyAttendance' => $dailyAttendance,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthNames[$month],
            'returnUrl' => $returnUrl,
            'pageTitle' => 'Detail Rekapitulasi Absensi - ' . $attendanceRecord->staff_name,
        ]);
    }

    /**
     * Get daily attendance breakdown for a specific staff member
     */
    private function getDailyAttendanceData($staffId, $month, $year, $staffType)
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $dailyData = collect();

        // Get scheduled dates first, then process only those dates
        $scheduledDates = collect();
        
        if ($staffType === 'Dokter') {
            // Find pegawai_id for this doctor (JadwalJaga uses pegawai_id, not dokter_id)
            $pegawai = \App\Models\Pegawai::where('user_id', $staffId)->first();
            
            if (!$pegawai) {
                // No pegawai record found, return empty data
                return $dailyData;
            }
            
            // Get all scheduled dates for this dokter using pegawai_id
            $schedules = \App\Models\JadwalJaga::where('pegawai_id', $pegawai->id)
                ->whereBetween('tanggal_jaga', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('tanggal_jaga')
                ->get();
                
            foreach ($schedules as $schedule) {
                $date = \Carbon\Carbon::parse($schedule->tanggal_jaga);
                $dayData = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'day_name_id' => $this->getDayNameIndonesian($date->format('l')),
                    'is_weekend' => $date->isWeekend(),
                    'scheduled_start' => $schedule->jam_mulai,
                    'scheduled_end' => $schedule->jam_selesai,
                    'scheduled_hours' => $this->calculateHoursDifference($schedule->jam_mulai, $schedule->jam_selesai),
                    'actual_checkin' => null,
                    'actual_checkout' => null,
                    'actual_hours' => 0,
                    'status' => 'dijadwalkan',
                    'late_minutes' => 0,
                    'early_checkout' => 0,
                    'overtime_hours' => 0,
                    'location' => $schedule->tempat_jaga ?? 'Klinik',
                    'notes' => $schedule->keterangan
                ];

                // Get actual attendance data for this scheduled date (DokterPresensi uses dokter_id)
                $dokter = \App\Models\Dokter::where('user_id', $staffId)->first();
                $attendance = null;
                if ($dokter) {
                    $attendance = \App\Models\DokterPresensi::where('dokter_id', $dokter->id)
                        ->where('tanggal', $date->format('Y-m-d'))
                        ->first();
                }
                    
                if ($attendance) {
                    $dayData['actual_checkin'] = $attendance->jam_masuk;
                    $dayData['actual_checkout'] = $attendance->jam_pulang;
                    if ($attendance->jam_masuk && $attendance->jam_pulang) {
                        $dayData['actual_hours'] = $this->calculateHoursDifference($attendance->jam_masuk, $attendance->jam_pulang);
                    }
                    $dayData['status'] = $attendance->jam_pulang ? 'hadir_penuh' : 'hadir_sebagian';
                    
                    // Calculate late/early metrics
                    if ($attendance->jam_masuk && $schedule->jam_mulai) {
                        $scheduledTime = \Carbon\Carbon::parse($schedule->jam_mulai);
                        $actualTime = \Carbon\Carbon::parse($attendance->jam_masuk);
                        if ($actualTime->gt($scheduledTime)) {
                            $dayData['late_minutes'] = $actualTime->diffInMinutes($scheduledTime);
                        }
                    }
                } else {
                    // No attendance record for scheduled date = absent
                    $dayData['status'] = 'alpha';
                }

                $dailyData->push($dayData);
            }
            
        } elseif ($staffType === 'Paramedis') {
            // Get all scheduled dates for this paramedis in the month
            $schedules = \App\Models\JadwalJaga::where('pegawai_id', $staffId)
                ->whereBetween('tanggal_jaga', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('tanggal_jaga')
                ->get();
                
            foreach ($schedules as $schedule) {
                $date = \Carbon\Carbon::parse($schedule->tanggal_jaga);
                $dayData = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'day_name_id' => $this->getDayNameIndonesian($date->format('l')),
                    'is_weekend' => $date->isWeekend(),
                    'scheduled_start' => $schedule->jam_mulai,
                    'scheduled_end' => $schedule->jam_selesai,
                    'scheduled_hours' => $this->calculateHoursDifference($schedule->jam_mulai, $schedule->jam_selesai),
                    'actual_checkin' => null,
                    'actual_checkout' => null,
                    'actual_hours' => 0,
                    'status' => 'dijadwalkan',
                    'late_minutes' => 0,
                    'early_checkout' => 0,
                    'overtime_hours' => 0,
                    'location' => $schedule->tempat_jaga ?? 'Klinik',
                    'notes' => $schedule->keterangan
                ];

                // Get actual attendance data for this scheduled date
                $attendance = \App\Models\Attendance::where('user_id', $staffId)
                    ->where('date', $date->format('Y-m-d'))
                    ->first();
                    
                if ($attendance) {
                    $dayData['actual_checkin'] = $attendance->time_in;
                    $dayData['actual_checkout'] = $attendance->time_out;
                    if ($attendance->time_in && $attendance->time_out) {
                        $dayData['actual_hours'] = $this->calculateHoursDifference($attendance->time_in, $attendance->time_out);
                    }
                    $dayData['status'] = $attendance->time_out ? 'hadir_penuh' : 'hadir_sebagian';
                } else {
                    // No attendance record for scheduled date = absent
                    $dayData['status'] = 'alpha';
                }

                $dailyData->push($dayData);
            }
            
        } else { // Non-Paramedis
            // For Non-Paramedis, check for actual attendance records first
            $attendanceRecords = \App\Models\NonParamedisAttendance::where('user_id', $staffId)
                ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orderBy('attendance_date')
                ->get();
                
            foreach ($attendanceRecords as $attendance) {
                $date = \Carbon\Carbon::parse($attendance->attendance_date);
                $dayData = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'day_name_id' => $this->getDayNameIndonesian($date->format('l')),
                    'is_weekend' => $date->isWeekend(),
                    'scheduled_start' => '08:00:00', // Default non-paramedis schedule
                    'scheduled_end' => '17:00:00',
                    'scheduled_hours' => 9,
                    'actual_checkin' => $attendance->check_in_time,
                    'actual_checkout' => $attendance->check_out_time,
                    'actual_hours' => 0,
                    'status' => 'dijadwalkan',
                    'late_minutes' => 0,
                    'early_checkout' => 0,
                    'overtime_hours' => 0,
                    'location' => 'Klinik',
                    'notes' => null
                ];
                
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $dayData['actual_hours'] = $this->calculateHoursDifference($attendance->check_in_time, $attendance->check_out_time);
                    $dayData['status'] = 'hadir_penuh';
                } elseif ($attendance->check_in_time) {
                    $dayData['status'] = 'hadir_sebagian';
                } else {
                    $dayData['status'] = 'alpha';
                }

                $dailyData->push($dayData);
            }
        }

        return $dailyData;
    }

    /**
     * Calculate hours difference between two time strings
     */
    private function calculateHoursDifference($startTime, $endTime)
    {
        if (!$startTime || !$endTime) return 0;
        
        try {
            $start = \Carbon\Carbon::parse($startTime);
            $end = \Carbon\Carbon::parse($endTime);
            return round($end->diffInMinutes($start) / 60, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get Indonesian day name
     */
    private function getDayNameIndonesian($dayName)
    {
        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        
        return $days[$dayName] ?? $dayName;
    }

    /**
     * Get default shift times when ShiftTemplate is empty
     */
    private function getDefaultShiftTime($shiftName, $type)
    {
        $defaults = [
            'Pagi' => ['start' => '06:00:00', 'end' => '12:00:00'],
            'Siang' => ['start' => '12:00:00', 'end' => '18:00:00'], 
            'Sore' => ['start' => '14:00:00', 'end' => '20:00:00'],
            'Malam' => ['start' => '20:00:00', 'end' => '06:00:00'],
            'Early Morning' => ['start' => '05:00:00', 'end' => '11:00:00'],
            'Extended Evening' => ['start' => '18:00:00', 'end' => '22:00:00']
        ];
        
        $shiftName = $shiftName ?? 'Pagi';
        $shift = $defaults[$shiftName] ?? $defaults['Pagi'];
        
        return $shift[$type] ?? ($type === 'start' ? '08:00:00' : '17:00:00');
    }
}