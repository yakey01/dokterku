<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\Pasien;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PatientStatsWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.patient-stats-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    
    public function getPatientStats(): array
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $thisYear = Carbon::now()->startOfYear();
            
            // Patient counts by period
            $todayCount = Pasien::whereDate('created_at', $today)->count();
            $monthCount = Pasien::whereBetween('created_at', [$thisMonth, Carbon::now()])->count();
            $yearCount = Pasien::whereBetween('created_at', [$thisYear, Carbon::now()])->count();
            $totalCount = Pasien::count();
            
            // Patient distribution by gender
            $genderStats = Pasien::select('jenis_kelamin', DB::raw('count(*) as count'))
                ->groupBy('jenis_kelamin')
                ->get()
                ->pluck('count', 'jenis_kelamin')
                ->toArray();
            
            // Patient distribution by status
            $statusStats = Pasien::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            // Age distribution
            $ageGroups = [
                '0-17' => 0,
                '18-30' => 0,
                '31-45' => 0,
                '46-60' => 0,
                '60+' => 0
            ];
            
            $patients = Pasien::select('tanggal_lahir')->whereNotNull('tanggal_lahir')->get();
            foreach ($patients as $patient) {
                $age = Carbon::parse($patient->tanggal_lahir)->age;
                if ($age <= 17) $ageGroups['0-17']++;
                elseif ($age <= 30) $ageGroups['18-30']++;
                elseif ($age <= 45) $ageGroups['31-45']++;
                elseif ($age <= 60) $ageGroups['46-60']++;
                else $ageGroups['60+']++;
            }
            
            return [
                'counts' => [
                    'today' => $todayCount,
                    'month' => $monthCount,
                    'year' => $yearCount,
                    'total' => $totalCount,
                ],
                'gender' => [
                    'male' => $genderStats['L'] ?? 0,
                    'female' => $genderStats['P'] ?? 0,
                ],
                'status' => [
                    'verified' => $statusStats['verified'] ?? 0,
                    'pending' => $statusStats['pending'] ?? 0,
                    'rejected' => $statusStats['rejected'] ?? 0,
                ],
                'age_groups' => $ageGroups,
                'recent_patients' => $this->getRecentPatients(),
            ];
            
        } catch (\Exception $e) {
            return [
                'counts' => ['today' => 0, 'month' => 0, 'year' => 0, 'total' => 0],
                'gender' => ['male' => 0, 'female' => 0],
                'status' => ['verified' => 0, 'pending' => 0, 'rejected' => 0],
                'age_groups' => ['0-17' => 0, '18-30' => 0, '31-45' => 0, '46-60' => 0, '60+' => 0],
                'recent_patients' => [],
            ];
        }
    }
    
    public function getMonthlyTrend(): array
    {
        try {
            $months = [];
            $counts = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthlyCount = Pasien::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count();
                    
                $counts[] = $monthlyCount;
            }
            
            return [
                'months' => $months,
                'counts' => $counts,
            ];
        } catch (\Exception $e) {
            return [
                'months' => [],
                'counts' => [],
            ];
        }
    }
    
    private function getRecentPatients(): array
    {
        try {
            return Pasien::select('nama', 'jenis_kelamin', 'created_at', 'status')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($patient) {
                    return [
                        'nama' => $patient->nama,
                        'jenis_kelamin' => $patient->jenis_kelamin,
                        'created_at' => $patient->created_at->format('d M, H:i'),
                        'status' => $patient->status,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}