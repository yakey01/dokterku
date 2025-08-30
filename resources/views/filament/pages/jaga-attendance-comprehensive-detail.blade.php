@php
    use App\Models\AttendanceJagaRecap;
    
    $currentMonth = request()->get('tableFilters.month.value', now()->month);
    $currentYear = request()->get('tableFilters.year.value', now()->year);
    
    // Get comprehensive detailed data
    $detailData = AttendanceJagaRecap::getUserDetailedData($record->user_id, $currentMonth, $currentYear);
    $user = $detailData['user'];
    $period = $detailData['period'];
    $summary = $detailData['summary'];
    $compliance = $detailData['compliance'];
    $dailyBreakdown = $detailData['daily_breakdown'];
    $monthlyTrends = $detailData['monthly_trends'];
    $performanceInsights = $detailData['performance_insights'];
    $professionalCompliance = $detailData['professional_compliance'];
    $recommendations = $detailData['recommendations'];
@endphp

<div class="space-y-6 max-h-[80vh] overflow-y-auto">
    {{-- Header Section with User Info --}}
    <div class="bg-gradient-to-r from-{{ $record->profession === 'Dokter' ? 'green' : ($record->profession === 'Paramedis' ? 'blue' : 'orange') }}-500 to-{{ $record->profession === 'Dokter' ? 'green' : ($record->profession === 'Paramedis' ? 'blue' : 'orange') }}-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-2xl">
                    {{ $record->profession === 'Dokter' ? '👨‍⚕️' : ($record->profession === 'Paramedis' ? '👩‍⚕️' : '👤') }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold">{{ $record->staff_name }}</h2>
                    <p class="text-lg opacity-90">{{ $user['position'] ?? $record->position }}</p>
                    <p class="text-sm opacity-75">{{ $record->profession }} • {{ $user['email'] ?? 'N/A' }}</p>
                    @if($user['phone'])
                        <p class="text-sm opacity-75">📞 {{ $user['phone'] }}</p>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">{{ number_format($summary['attendance_percentage'] ?? 0, 1) }}%</div>
                <div class="text-sm opacity-90">Kehadiran {{ $period['month_name'] ?? '' }}</div>
                @if($user['join_date'])
                    <div class="text-xs opacity-75 mt-2">Bergabung: {{ $user['join_date'] }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Attendance Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $summary['attended_shifts'] ?? 0 }}/{{ $summary['total_scheduled_shifts'] ?? 0 }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Shift Hadir</div>
                <div class="text-xs text-red-500 mt-1">{{ $summary['missed_shifts'] ?? 0 }} terlewat</div>
            </div>
        </div>

        {{-- Working Hours --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($summary['total_working_hours'] ?? 0, 1) }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Jam Kerja</div>
                <div class="text-xs text-gray-500 mt-1">Target: {{ number_format($summary['total_scheduled_hours'] ?? 0, 1) }}j</div>
            </div>
        </div>

        {{-- GPS Validation --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ number_format($compliance['gps_validation_rate'] ?? 0, 1) }}%</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">GPS Valid</div>
                <div class="text-xs {{ ($compliance['gps_validation_rate'] ?? 0) >= 90 ? 'text-green-500' : 'text-orange-500' }} mt-1">
                    {{ ($compliance['gps_validation_rate'] ?? 0) >= 90 ? 'Excellent' : 'Perlu perbaikan' }}
                </div>
            </div>
        </div>

        {{-- Punctuality --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ number_format($compliance['punctuality_score'] ?? 0, 1) }}%</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Ketepatan</div>
                <div class="text-xs text-gray-500 mt-1">Terlambat: {{ $compliance['late_arrivals'] ?? 0 }}x</div>
            </div>
        </div>
    </div>

    {{-- Professional Standards Compliance --}}
    @if(!empty($professionalCompliance))
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <span class="mr-2">🎯</span> Standar Profesi: {{ $professionalCompliance['profession'] }}
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Attendance Standard --}}
            <div class="p-4 rounded-lg {{ $professionalCompliance['compliance_status']['attendance_compliant'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium">Kehadiran</span>
                    <span class="text-2xl">{{ $professionalCompliance['compliance_status']['attendance_compliant'] ? '✅' : '❌' }}</span>
                </div>
                <div class="text-sm">
                    <div class="flex justify-between">
                        <span>Target:</span>
                        <span class="font-medium">≥{{ $professionalCompliance['standards']['minimum_attendance'] }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Actual:</span>
                        <span class="font-medium {{ $professionalCompliance['compliance_status']['attendance_compliant'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($professionalCompliance['current_performance']['attendance_rate'], 1) }}%
                        </span>
                    </div>
                </div>
            </div>

            {{-- Punctuality Standard --}}
            <div class="p-4 rounded-lg {{ $professionalCompliance['compliance_status']['punctuality_compliant'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium">Ketepatan</span>
                    <span class="text-2xl">{{ $professionalCompliance['compliance_status']['punctuality_compliant'] ? '✅' : '⚠️' }}</span>
                </div>
                <div class="text-sm">
                    <div class="flex justify-between">
                        <span>Max Terlambat:</span>
                        <span class="font-medium">{{ $professionalCompliance['standards']['maximum_late_per_month'] }}x/bulan</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Bulan Ini:</span>
                        <span class="font-medium {{ $professionalCompliance['compliance_status']['punctuality_compliant'] ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $professionalCompliance['current_performance']['late_count'] }}x
                        </span>
                    </div>
                </div>
            </div>

            {{-- GPS Standard --}}
            <div class="p-4 rounded-lg {{ $professionalCompliance['compliance_status']['gps_compliant'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium">GPS Valid</span>
                    <span class="text-2xl">{{ $professionalCompliance['compliance_status']['gps_compliant'] ? '✅' : '📍' }}</span>
                </div>
                <div class="text-sm">
                    <div class="flex justify-between">
                        <span>Target:</span>
                        <span class="font-medium">≥{{ $professionalCompliance['standards']['required_gps_accuracy'] }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Actual:</span>
                        <span class="font-medium {{ $professionalCompliance['compliance_status']['gps_compliant'] ? 'text-green-600' : 'text-orange-600' }}">
                            {{ number_format($professionalCompliance['current_performance']['gps_validation_rate'], 1) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Monthly Trends Chart with Visual Chart --}}
    @if(!empty($monthlyTrends))
    @include('filament.pages.components.performance-chart', [
        'data' => $monthlyTrends, 
        'title' => '📈 Tren Kehadiran 6 Bulan Terakhir'
    ])
    @endif

    {{-- Achievement Badges --}}
    @include('filament.pages.components.achievement-badges', [
        'summary' => $summary,
        'compliance' => $compliance,
        'profession' => $record->profession
    ])

    {{-- Advanced Performance Analytics --}}
    @include('filament.pages.components.performance-analytics', [
        'summary' => $summary,
        'compliance' => $compliance,
        'professionalCompliance' => $professionalCompliance
    ])

    {{-- Performance Insights --}}
    @if(!empty($performanceInsights))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Day of Week Performance --}}
        @if(isset($performanceInsights['day_of_week_performance']))
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-md font-semibold mb-4 flex items-center">
                <span class="mr-2">📅</span> Performa per Hari
            </h4>
            
            <div class="space-y-3">
                @foreach($performanceInsights['day_of_week_performance'] as $day => $stats)
                @php
                    $percentage = $stats['scheduled'] > 0 ? ($stats['attended'] / $stats['scheduled']) * 100 : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $day }}</span>
                        <span class="font-medium">{{ $stats['attended'] }}/{{ $stats['scheduled'] }} ({{ number_format($percentage, 0) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-{{ $percentage >= 90 ? 'green' : ($percentage >= 80 ? 'yellow' : 'red') }}-500 h-2 rounded-full transition-all duration-300" 
                             style="width: {{ min(100, $percentage) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Check-in Time Patterns --}}
        @if(isset($performanceInsights['checkin_patterns']))
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-md font-semibold mb-4 flex items-center">
                <span class="mr-2">🕐</span> Pola Check-in
            </h4>
            
            <div class="space-y-3">
                @foreach($performanceInsights['checkin_patterns'] as $pattern => $count)
                <div class="flex justify-between items-center">
                    <span class="text-sm">{{ $pattern }}</span>
                    <div class="flex items-center">
                        <span class="font-medium mr-2">{{ $count }}x</span>
                        <div class="w-16 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            @php
                                $maxCount = max(array_values($performanceInsights['checkin_patterns']));
                                $percentage = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                            @endphp
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- GPS Tracking Section --}}
    @if(!empty($dailyBreakdown))
    @include('filament.pages.components.gps-tracking-map', [
        'dailyBreakdown' => $dailyBreakdown,
        'userId' => $user['id'] ?? 0
    ])
    @endif

    {{-- Daily Breakdown Table --}}
    @if(!empty($dailyBreakdown))
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold flex items-center">
                <span class="mr-2">📋</span> Detail Harian - {{ $period['month_name'] ?? '' }}
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">Tanggal</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">Shift</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">Jadwal</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">Check In</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">Check Out</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">GPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($dailyBreakdown as $day)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</div>
                            <div class="text-xs text-gray-500">{{ $day['day_name'] }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium">{{ $day['shift_name'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm">{{ $day['scheduled_start'] }} - {{ $day['scheduled_end'] }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($day['status'] === 'present')
                                <div class="text-sm {{ isset($day['is_late']) && $day['is_late'] ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $day['actual_check_in'] ?? '--:--' }}
                                </div>
                                @if(isset($day['is_late']) && $day['is_late'])
                                    <div class="text-xs text-red-500">+{{ $day['late_minutes'] ?? 0 }} menit</div>
                                @endif
                            @else
                                <span class="text-gray-400">Tidak hadir</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($day['status'] === 'present')
                                <div class="text-sm">{{ $day['actual_check_out'] ?? '--:--' }}</div>
                                @if(isset($day['work_duration']))
                                    <div class="text-xs text-gray-500">{{ number_format($day['work_duration']/60, 1) }}j</div>
                                @endif
                            @else
                                <span class="text-gray-400">--:--</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $day['status'] === 'present' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                {{ $day['status'] === 'present' ? 'Hadir' : 'Tidak Hadir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($day['status'] === 'present')
                                <span class="text-{{ isset($day['gps_valid']) && $day['gps_valid'] ? 'green' : 'red' }}-600">
                                    {{ isset($day['gps_valid']) && $day['gps_valid'] ? '✅' : '❌' }}
                                </span>
                                @if(isset($day['location_name']))
                                    <div class="text-xs text-gray-500 mt-1">{{ $day['location_name'] }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Recommendations --}}
    @if(!empty($recommendations))
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <span class="mr-2">💡</span> Rekomendasi Perbaikan
        </h3>
        
        <div class="space-y-4">
            @foreach($recommendations as $recommendation)
            <div class="p-4 rounded-lg border-l-4 
                {{ $recommendation['priority'] === 'high' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 
                   ($recommendation['priority'] === 'medium' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 
                    'border-blue-500 bg-blue-50 dark:bg-blue-900/20') }}">
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <span class="text-lg">
                            {{ $recommendation['priority'] === 'high' ? '🚨' : ($recommendation['priority'] === 'medium' ? '⚠️' : 'ℹ️') }}
                        </span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{ $recommendation['title'] }}
                        </h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                            {{ $recommendation['description'] }}
                        </p>
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-gray-100">Aksi:</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $recommendation['action'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Export Options & Actions --}}
    @include('filament.pages.components.export-options', [
        'record' => $record,
        'period' => $period
    ])
</div>

<style>
    /* Custom scrollbar for the modal */
    .max-h-\[80vh\]::-webkit-scrollbar {
        width: 6px;
    }
    .max-h-\[80vh\]::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .max-h-\[80vh\]::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    .max-h-\[80vh\]::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>