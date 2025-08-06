<?php

namespace App\Filament\Pages;

use App\Models\DokterPresensi;
use App\Models\Dokter;
use App\Models\JadwalJaga;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Htmlable;

class DoctorAttendanceAnalytics extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.doctor-attendance-analytics';

    protected static ?string $navigationGroup = '👨‍⚕️ DOCTOR MANAGEMENT';

    protected static ?string $navigationLabel = 'Attendance Analytics';

    protected static ?int $navigationSort = 2;

    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $doctor_id = null;

    public function mount(): void
    {
        // Default to current month
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
        
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'doctor_id' => $this->doctor_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->default(now()->startOfMonth())
                    ->reactive(),
                    
                DatePicker::make('end_date')
                    ->label('End Date')
                    ->required()
                    ->default(now()->endOfMonth())
                    ->after('start_date')
                    ->reactive(),
                    
                Select::make('doctor_id')
                    ->label('Doctor (Optional)')
                    ->placeholder('All Doctors')
                    ->options(function () {
                        return Dokter::with('user')->get()->mapWithKeys(function ($dokter) {
                            return [$dokter->id => "{$dokter->nama} - {$dokter->spesialisasi}"];
                        });
                    })
                    ->searchable()
                    ->reactive(),
            ])
            ->columns(3);
    }

    public function updated($property): void
    {
        if (in_array($property, ['data.start_date', 'data.end_date', 'data.doctor_id'])) {
            $this->start_date = $this->form->getState()['start_date'];
            $this->end_date = $this->form->getState()['end_date'];
            $this->doctor_id = $this->form->getState()['doctor_id'];
        }
    }

    public function getOverviewStats(): array
    {
        $query = DokterPresensi::whereBetween('tanggal', [$this->start_date, $this->end_date]);
        
        if ($this->doctor_id) {
            $query->where('dokter_id', $this->doctor_id);
        }

        $totalAttendances = $query->count();
        $completedShifts = $query->whereNotNull('jam_masuk')->whereNotNull('jam_pulang')->count();
        $activeShifts = $query->whereNotNull('jam_masuk')->whereNull('jam_pulang')->count();
        $noShows = $query->whereNull('jam_masuk')->count();

        // Calculate average work hours
        $avgWorkHours = $query->whereNotNull('jam_masuk')->whereNotNull('jam_pulang')
            ->get()
            ->map(function ($record) {
                if (!$record->jam_masuk || !$record->jam_pulang) return 0;
                
                $checkIn = Carbon::createFromFormat('H:i:s', $record->jam_masuk);
                $checkOut = Carbon::createFromFormat('H:i:s', $record->jam_pulang);
                return $checkOut->diffInMinutes($checkIn);
            })
            ->average();

        $avgWorkHours = $avgWorkHours ? round($avgWorkHours / 60, 1) : 0;

        // Punctuality rate (on time check-ins)
        $onTimeCheckIns = $query->whereNotNull('jam_masuk')
            ->whereTime('jam_masuk', '<=', '08:00:00') // Assuming 8 AM is standard
            ->count();
        
        $totalCheckIns = $query->whereNotNull('jam_masuk')->count();
        $punctualityRate = $totalCheckIns > 0 ? round(($onTimeCheckIns / $totalCheckIns) * 100, 1) : 0;

        return [
            'total_attendances' => $totalAttendances,
            'completed_shifts' => $completedShifts,
            'active_shifts' => $activeShifts,
            'no_shows' => $noShows,
            'completion_rate' => $totalAttendances > 0 ? round(($completedShifts / $totalAttendances) * 100, 1) : 0,
            'avg_work_hours' => $avgWorkHours,
            'punctuality_rate' => $punctualityRate,
        ];
    }

    public function getDailyAttendanceChart(): array
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        
        $dailyData = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $query = DokterPresensi::whereDate('tanggal', $currentDate);
            
            if ($this->doctor_id) {
                $query->where('dokter_id', $this->doctor_id);
            }
            
            $dailyData[] = [
                'date' => $currentDate->format('M d'),
                'full_date' => $currentDate->format('Y-m-d'),
                'total' => $query->count(),
                'completed' => $query->whereNotNull('jam_masuk')->whereNotNull('jam_pulang')->count(),
                'active' => $query->whereNotNull('jam_masuk')->whereNull('jam_pulang')->count(),
                'no_show' => $query->whereNull('jam_masuk')->count(),
            ];
            
            $currentDate->addDay();
        }
        
        return $dailyData;
    }

    public function getDoctorPerformance(): array
    {
        $query = DokterPresensi::with('dokter')
            ->whereBetween('tanggal', [$this->start_date, $this->end_date]);
            
        if ($this->doctor_id) {
            $query->where('dokter_id', $this->doctor_id);
        }

        $doctorStats = $query->get()
            ->groupBy('dokter_id')
            ->map(function ($attendances, $dokterId) {
                $dokter = $attendances->first()->dokter;
                
                $total = $attendances->count();
                $completed = $attendances->where('jam_masuk', '!=', null)->where('jam_pulang', '!=', null)->count();
                $onTime = $attendances->where('jam_masuk', '!=', null)->filter(function ($attendance) {
                    return Carbon::createFromFormat('H:i:s', $attendance->jam_masuk)->lte(Carbon::createFromFormat('H:i:s', '08:00:00'));
                })->count();
                
                $avgWorkHours = $completed > 0 ? $attendances
                    ->where('jam_masuk', '!=', null)
                    ->where('jam_pulang', '!=', null)
                    ->map(function ($record) {
                        $checkIn = Carbon::createFromFormat('H:i:s', $record->jam_masuk);
                        $checkOut = Carbon::createFromFormat('H:i:s', $record->jam_pulang);
                        return $checkOut->diffInMinutes($checkIn);
                    })
                    ->average() : 0;

                return [
                    'id' => $dokterId,
                    'name' => $dokter->nama,
                    'specialization' => $dokter->spesialisasi,
                    'total_attendances' => $total,
                    'completed_shifts' => $completed,
                    'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                    'punctuality_rate' => $attendances->where('jam_masuk', '!=', null)->count() > 0 ? 
                        round(($onTime / $attendances->where('jam_masuk', '!=', null)->count()) * 100, 1) : 0,
                    'avg_work_hours' => round($avgWorkHours / 60, 1),
                ];
            })
            ->sortByDesc('completion_rate')
            ->values()
            ->toArray();

        return $doctorStats;
    }

    public function getScheduleCompliance(): array
    {
        $query = DokterPresensi::with('dokter')
            ->whereBetween('tanggal', [$this->start_date, $this->end_date]);
            
        if ($this->doctor_id) {
            $query->where('dokter_id', $this->doctor_id);
        }

        $attendances = $query->get();
        $complianceData = [];

        foreach ($attendances as $attendance) {
            // Find corresponding JadwalJaga
            $jadwal = JadwalJaga::whereDate('tanggal_jaga', $attendance->tanggal)
                ->where('pegawai_id', $attendance->dokter->user_id ?? null)
                ->with('shiftTemplate')
                ->first();

            if ($jadwal && $attendance->jam_masuk) {
                $scheduledTime = Carbon::createFromFormat('H:i', $jadwal->shift_template->jam_masuk ?? '08:00');
                $actualTime = Carbon::createFromFormat('H:i:s', $attendance->jam_masuk);
                
                $diffMinutes = $actualTime->diffInMinutes($scheduledTime, false);
                
                $complianceData[] = [
                    'date' => $attendance->tanggal->format('Y-m-d'),
                    'doctor' => $attendance->dokter->nama,
                    'scheduled_time' => $jadwal->shift_template->jam_masuk ?? '08:00',
                    'actual_time' => $attendance->jam_masuk,
                    'difference_minutes' => $diffMinutes,
                    'status' => $this->getComplianceStatus($diffMinutes),
                    'shift_name' => $jadwal->shift_template->nama_shift ?? 'Unknown',
                ];
            }
        }

        return collect($complianceData)->sortBy('date')->values()->toArray();
    }

    private function getComplianceStatus(int $diffMinutes): string
    {
        if ($diffMinutes <= -15) return 'Very Early';
        if ($diffMinutes <= 0) return 'On Time';
        if ($diffMinutes <= 15) return 'Acceptable Late';
        if ($diffMinutes <= 30) return 'Late';
        return 'Very Late';
    }

    public function getComplianceStatusColor(string $status): string
    {
        return match($status) {
            'Very Early' => 'text-blue-600',
            'On Time' => 'text-green-600',
            'Acceptable Late' => 'text-yellow-600',
            'Late' => 'text-orange-600',
            'Very Late' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    public function getTitle(): string|Htmlable
    {
        return 'Doctor Attendance Analytics';
    }

    public function getSubheading(): ?string
    {
        return 'Comprehensive analytics and insights for doctor attendance patterns';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'manajer']) ?? false;
    }
}