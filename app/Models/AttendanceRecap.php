<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRecap extends Model
{
    // This is a virtual model - no actual table
    public $timestamps = false;
    protected $table = 'attendance_recaps'; // Dummy table name for Filament

    protected $fillable = [
        'staff_id',
        'staff_name',
        'staff_type',
        'position',
        'total_working_days',
        'days_present',
        'average_check_in',
        'average_check_out',
        'total_working_hours',
        'attendance_percentage',
        'status',
        'rank'
    ];

    protected $casts = [
        'total_working_days' => 'integer',
        'days_present' => 'integer',
        'total_working_hours' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'rank' => 'integer',
        'average_check_in' => 'datetime',
        'average_check_out' => 'datetime',
    ];

    /**
     * Get attendance recap data for all staff types
     */
    public static function getRecapData($month = null, $year = null, $staffType = null, $statusFilter = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        $recapData = collect();

        // Get data from all three attendance systems
        $doctorData = self::getDoctorAttendanceData($month, $year);
        $paramedisData = self::getParamedisAttendanceData($month, $year);
        $nonParamedisData = self::getNonParamedisAttendanceData($month, $year);

        // Merge all data
        $allData = $doctorData->merge($paramedisData)->merge($nonParamedisData);

        // Filter by staff type if specified
        if ($staffType) {
            $allData = $allData->where('staff_type', $staffType);
        }

        // Filter by status if specified
        if ($statusFilter) {
            $allData = $allData->where('status', $statusFilter);
        }

        // Calculate ranking based on attendance percentage (highest first)
        $rankedData = $allData->sortByDesc('attendance_percentage')->values();
        
        // Assign ranking numbers
        $rankedData = $rankedData->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        return $rankedData;
    }

    /**
     * Get doctor attendance data
     */
    private static function getDoctorAttendanceData($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = self::getWorkingDaysInMonth($month, $year);

        return DB::table('dokter_presensis as dp')
            ->join('dokters as d', 'dp.dokter_id', '=', 'd.id')
            ->join('users as u', 'd.user_id', '=', 'u.id')
            ->select([
                'u.id as staff_id',
                DB::raw("COALESCE(d.nama_lengkap, u.name) as staff_name"),
                DB::raw("'Dokter' as staff_type"),
                DB::raw("COALESCE(d.jabatan, 'Dokter Umum') as position"),
                DB::raw("$workingDays as total_working_days"),
                DB::raw('COUNT(DISTINCT dp.tanggal) as days_present'),
                DB::raw('AVG(TIME(dp.jam_masuk)) as average_check_in'),
                DB::raw('AVG(TIME(dp.jam_pulang)) as average_check_out'),
                DB::raw('SUM(CASE 
                    WHEN dp.jam_pulang IS NOT NULL 
                    THEN (julianday(dp.jam_pulang) - julianday(dp.jam_masuk)) * 24 
                    ELSE 0 
                END) as total_working_hours'),
                DB::raw("ROUND((CAST(COUNT(DISTINCT dp.tanggal) AS REAL) / CAST($workingDays AS REAL)) * 100, 2) as attendance_percentage")
            ])
            ->whereNull('u.deleted_at') // Filter out soft deleted users
            ->whereNull('d.deleted_at') // Filter out soft deleted dokters
            ->whereBetween('dp.tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('u.id', 'u.name', 'd.nama_lengkap', 'd.jabatan')
            ->get()
            ->map(function ($item) {
                $item->status = self::getAttendanceStatus($item->attendance_percentage);
                return (array) $item;
            });
    }

    /**
     * Get paramedis attendance data
     */
    private static function getParamedisAttendanceData($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = self::getWorkingDaysInMonth($month, $year);

        // First get all active paramedis users
        $allParamedis = DB::table('users as u')
            ->join('pegawais as p', 'u.id', '=', 'p.user_id')
            ->where('p.jenis_pegawai', 'Paramedis')
            ->whereNull('u.deleted_at')
            ->whereNull('p.deleted_at')
            ->select('u.id', 'u.name', 'p.jabatan')
            ->get();

        // Get all paramedis with their individual working days
        $paramedisData = collect();
        
        foreach ($allParamedis as $paramedis) {
            // Calculate working days for this specific user
            $userWorkingDays = self::getWorkingDaysInMonth($month, $year, $paramedis->id, 'Paramedis');
            
            // Get attendance data for this user
            $attendanceData = DB::table('attendances as a')
                ->where('a.user_id', $paramedis->id)
                ->whereBetween('a.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->selectRaw('
                    COUNT(DISTINCT date) as days_present,
                    AVG(TIME(time_in)) as average_check_in,
                    AVG(TIME(time_out)) as average_check_out,
                    SUM(CASE 
                        WHEN time_out IS NOT NULL 
                        THEN (julianday(time_out) - julianday(time_in)) * 24 
                        ELSE 0 
                    END) as total_working_hours
                ')
                ->first();
            
            $daysPresent = $attendanceData->days_present ?? 0;
            $attendancePercentage = $userWorkingDays > 0 ? 
                round(($daysPresent / $userWorkingDays) * 100, 2) : 0;
            
            $paramedisData->push([
                'staff_id' => $paramedis->id,
                'staff_name' => $paramedis->name,
                'staff_type' => 'Paramedis',
                'position' => $paramedis->jabatan,
                'total_working_days' => $userWorkingDays,
                'days_present' => $daysPresent,
                'average_check_in' => $attendanceData->average_check_in,
                'average_check_out' => $attendanceData->average_check_out,
                'total_working_hours' => $attendanceData->total_working_hours ?? 0,
                'attendance_percentage' => $attendancePercentage,
                'status' => self::getAttendanceStatus($attendancePercentage)
            ]);
        }
        
        return $paramedisData;
    }

    /**
     * Get non-paramedis attendance data
     */
    private static function getNonParamedisAttendanceData($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = self::getWorkingDaysInMonth($month, $year);

        return DB::table('non_paramedis_attendances as npa')
            ->join('users as u', 'npa.user_id', '=', 'u.id')
            ->join('pegawais as p', 'u.id', '=', 'p.user_id')
            ->select([
                'u.id as staff_id',
                'u.name as staff_name',
                DB::raw("'Non-Paramedis' as staff_type"),
                'p.jabatan as position',
                DB::raw("$workingDays as total_working_days"),
                DB::raw('COUNT(DISTINCT npa.attendance_date) as days_present'),
                DB::raw('AVG(TIME(npa.check_in_time)) as average_check_in'),
                DB::raw('AVG(TIME(npa.check_out_time)) as average_check_out'),
                DB::raw('SUM(CASE 
                    WHEN npa.check_out_time IS NOT NULL 
                    THEN (julianday(npa.check_out_time) - julianday(npa.check_in_time)) * 24 
                    ELSE 0 
                END) as total_working_hours'),
                DB::raw("ROUND((CAST(COUNT(DISTINCT npa.attendance_date) AS REAL) / CAST($workingDays AS REAL)) * 100, 2) as attendance_percentage")
            ])
            ->where('p.jenis_pegawai', 'non_paramedis')
            ->whereNull('u.deleted_at') // Filter out soft deleted users
            ->whereNull('p.deleted_at') // Filter out soft deleted pegawai
            ->whereBetween('npa.attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('u.id', 'u.name', 'p.jabatan')
            ->get()
            ->map(function ($item) {
                $item->status = self::getAttendanceStatus($item->attendance_percentage);
                return (array) $item;
            });
    }

    /**
     * Calculate working days in a month (exclude weekends)
     * Updated to consider scheduled shifts and employee start dates
     */
    private static function getWorkingDaysInMonth($month, $year, $userId = null, $staffType = null)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // If we have a specific user, check their scheduled shifts first
        if ($userId && $staffType === 'Paramedis') {
            // Count scheduled shifts (jadwal jaga) for Paramedis
            $scheduledShifts = \App\Models\JadwalJaga::where('pegawai_id', $userId)
                ->whereBetween('tanggal_jaga', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
            
            // If no scheduled shifts, return 0 working days (no absent days)
            if ($scheduledShifts == 0) {
                return 0;
            }
            
            // Return scheduled shifts as working days
            return $scheduledShifts;
        }
        
        // Calculate working days from start date
        $workingDays = 0;
        $tempDate = $startDate->copy();
        
        while ($tempDate->lte($endDate) && $tempDate->lte(now())) {
            // Count Monday to Saturday as working days (exclude Sunday)
            if ($tempDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $tempDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Get attendance status based on percentage
     */
    private static function getAttendanceStatus($percentage)
    {
        if ($percentage >= 95) {
            return 'excellent';
        } elseif ($percentage >= 85) {
            return 'good';
        } elseif ($percentage >= 75) {
            return 'average';
        } else {
            return 'poor';
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor()
    {
        return match($this->status) {
            'excellent' => 'success',
            'good' => 'info',
            'average' => 'warning',
            'poor' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        return match($this->status) {
            'excellent' => 'Excellent (≥95%)',
            'good' => 'Good (85-94%)',
            'average' => 'Average (75-84%)',
            'poor' => 'Poor (<75%)',
            default => 'Unknown'
        };
    }

    /**
     * Get formatted working hours
     */
    public function getFormattedWorkingHours()
    {
        $hours = floor($this->total_working_hours);
        $minutes = round(($this->total_working_hours - $hours) * 60);
        
        return sprintf('%d jam %d menit', $hours, $minutes);
    }
    
    /**
     * Override to handle virtual model resolution
     * This method is called by Filament when resolving models for actions
     */
    public static function resolveRecordRouteBinding($value, $field = null)
    {
        // Extract context from various possible sources
        $context = static::extractActionContext();
        
        $data = static::getRecapData(
            $context['month'], 
            $context['year'], 
            $context['staffType']
        );
        
        $record = $data->firstWhere('staff_id', $value);
        
        if ($record) {
            $model = new static();
            $model->fill($record);
            $model->exists = true;
            $model->id = $record['staff_id'];
            $model->setAttribute('staff_id', $record['staff_id']);
            return $model;
        }
        
        return null;
    }
    
    /**
     * Extract context from multiple sources for action resolution
     */
    private static function extractActionContext(): array
    {
        // Try multiple sources for context
        $month = null;
        $year = null;
        $staffType = null;
        
        // Source 1: Direct request parameters
        $month = request('month') ?? request('tableFilters.month.value');
        $year = request('year') ?? request('tableFilters.year.value');
        $staffType = request('staff_type') ?? request('tableFilters.staff_type.value');
        
        // Source 2: Session storage
        if (!$month || !$year) {
            $month = session('attendance_recap_month', now()->month);
            $year = session('attendance_recap_year', now()->year);
            $staffType = session('attendance_recap_staff_type');
        }
        
        // Source 3: Parse from referer URL
        if (!$month || !$year) {
            $referer = request()->server('HTTP_REFERER', '');
            if (preg_match('/tableFilters.*month.*value.*?([0-9]+)/', $referer, $matches)) {
                $month = (int) $matches[1];
            }
            if (preg_match('/tableFilters.*year.*value.*?([0-9]{4})/', $referer, $matches)) {
                $year = (int) $matches[1];
            }
        }
        
        return [
            'month' => $month ?: now()->month,
            'year' => $year ?: now()->year,
            'staffType' => $staffType
        ];
    }
    
    /**
     * Get the primary key for the model
     */
    public function getKeyName()
    {
        return 'id';
    }
    
    /**
     * Get the value of the model's primary key
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }
    
    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }
    
    /**
     * Retrieve the model for a bound value
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::resolveRecordRouteBinding($value, $field);
    }
    
    /**
     * Set the primary key for the model
     */
    public function setId($id)
    {
        $this->attributes['id'] = $id;
        return $this;
    }
    
    /**
     * Static method to create a properly configured virtual model
     * This helps ensure consistent model creation for Filament
     */
    public static function createVirtualModel(array $data): self
    {
        $model = new static();
        $model->fill($data);
        $model->exists = true;
        $model->id = $data['staff_id'];
        $model->setAttribute('id', $data['staff_id']);
        $model->setAttribute('staff_id', $data['staff_id']);
        
        // Set the connection to null since this is a virtual model
        $model->setConnection(null);
        
        return $model;
    }
    
    /**
     * Enhanced method to get a single record by staff_id
     */
    public static function findByStaffId(int $staffId, $month = null, $year = null, $staffType = null): ?self
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;
        
        $data = static::getRecapData($month, $year, $staffType);
        $record = $data->firstWhere('staff_id', $staffId);
        
        if ($record) {
            return static::createVirtualModel($record);
        }
        
        return null;
    }
}