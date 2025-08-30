{{-- Advanced Performance Analytics Component --}}
@props(['summary', 'compliance', 'professionalCompliance'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Performance Radar Chart Simulation --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-semibold mb-4 flex items-center">
            <span class="mr-2">📊</span> Analisis Performa 360°
        </h4>
        
        @php
            $metrics = [
                'Kehadiran' => $summary['attendance_percentage'] ?? 0,
                'Ketepatan' => $compliance['punctuality_score'] ?? 0,
                'GPS Valid' => $compliance['gps_validation_rate'] ?? 0,
                'Kepatuhan' => $compliance['schedule_compliance_rate'] ?? 0,
                'Konsistensi' => min(100, ($summary['attended_shifts'] ?? 0) > 0 ? 
                    (($summary['total_working_hours'] ?? 0) / ($summary['total_scheduled_hours'] ?? 1)) * 100 : 0),
            ];
            
            $maxValue = 100;
        @endphp
        
        <div class="relative">
            {{-- Circular Performance Visualization --}}
            <div class="grid grid-cols-1 gap-4">
                @foreach($metrics as $metric => $value)
                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                    <div class="flex items-center">
                        <div class="w-12 h-12 relative mr-4">
                            {{-- Circular Progress --}}
                            <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-300 dark:text-gray-600"
                                      stroke="currentColor" stroke-width="3" fill="none"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="text-{{ $value >= 90 ? 'green' : ($value >= 80 ? 'blue' : ($value >= 70 ? 'yellow' : 'red')) }}-500"
                                      stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"
                                      stroke-dasharray="{{ $value }}, 100"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ number_format($value, 0) }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $metric }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($value, 1) }}%</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg">
                            {{ $value >= 95 ? '🟢' : ($value >= 85 ? '🔵' : ($value >= 75 ? '🟡' : '🔴')) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $value >= 95 ? 'Excellent' : ($value >= 85 ? 'Good' : ($value >= 75 ? 'Average' : 'Poor')) }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        {{-- Overall Score --}}
        @php
            $overallScore = collect($metrics)->avg();
            $grade = match(true) {
                $overallScore >= 95 => ['grade' => 'A+', 'color' => 'green', 'description' => 'Outstanding Performance'],
                $overallScore >= 90 => ['grade' => 'A', 'color' => 'green', 'description' => 'Excellent Performance'],
                $overallScore >= 85 => ['grade' => 'B+', 'color' => 'blue', 'description' => 'Very Good Performance'],
                $overallScore >= 80 => ['grade' => 'B', 'color' => 'blue', 'description' => 'Good Performance'],
                $overallScore >= 75 => ['grade' => 'B-', 'color' => 'yellow', 'description' => 'Average Performance'],
                $overallScore >= 70 => ['grade' => 'C+', 'color' => 'orange', 'description' => 'Below Average'],
                default => ['grade' => 'C', 'color' => 'red', 'description' => 'Needs Improvement']
            };
        @endphp
        
        <div class="mt-6 p-4 rounded-lg bg-gradient-to-r from-{{ $grade['color'] }}-50 to-{{ $grade['color'] }}-100 dark:from-{{ $grade['color'] }}-900/20 dark:to-{{ $grade['color'] }}-800/20">
            <div class="text-center">
                <div class="text-3xl font-bold text-{{ $grade['color'] }}-600">{{ $grade['grade'] }}</div>
                <div class="text-sm font-medium text-{{ $grade['color'] }}-700 dark:text-{{ $grade['color'] }}-300">{{ $grade['description'] }}</div>
                <div class="text-xs text-{{ $grade['color'] }}-600 dark:text-{{ $grade['color'] }}-400">Overall Score: {{ number_format($overallScore, 1) }}%</div>
            </div>
        </div>
    </div>

    {{-- Time-based Analytics --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-semibold mb-4 flex items-center">
            <span class="mr-2">⏰</span> Analisis Waktu & Produktivitas
        </h4>
        
        {{-- Working Hours Analysis --}}
        <div class="space-y-4">
            {{-- Scheduled vs Actual Hours --}}
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
                <div class="flex justify-between items-center mb-3">
                    <span class="font-medium">Jam Kerja</span>
                    <span class="text-sm text-gray-600">Actual vs Target</span>
                </div>
                
                @php
                    $scheduledHours = $summary['total_scheduled_hours'] ?? 0;
                    $actualHours = $summary['total_working_hours'] ?? 0;
                    $efficiency = $scheduledHours > 0 ? ($actualHours / $scheduledHours) * 100 : 0;
                @endphp
                
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>Target: {{ number_format($scheduledHours, 1) }} jam</span>
                        <span>Actual: {{ number_format($actualHours, 1) }} jam</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-600">
                        <div class="bg-{{ $efficiency >= 100 ? 'green' : ($efficiency >= 90 ? 'blue' : ($efficiency >= 80 ? 'yellow' : 'red')) }}-500 h-3 rounded-full transition-all duration-300" 
                             style="width: {{ min(100, $efficiency) }}%"></div>
                    </div>
                    <div class="text-center text-sm font-medium text-{{ $efficiency >= 100 ? 'green' : ($efficiency >= 90 ? 'blue' : ($efficiency >= 80 ? 'yellow' : 'red')) }}-600">
                        {{ number_format($efficiency, 1) }}% Efisiensi
                    </div>
                </div>
            </div>

            {{-- Average Times --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                    <div class="text-2xl font-bold text-blue-600">{{ $summary['average_check_in'] ?? '--:--' }}</div>
                    <div class="text-sm text-blue-700 dark:text-blue-300">Rata² Check-in</div>
                    <div class="text-xs text-gray-600">
                        {{ \Carbon\Carbon::parse($summary['average_check_in'] ?? '08:00')->hour < 8 ? '✅ Early' : 
                           (\Carbon\Carbon::parse($summary['average_check_in'] ?? '08:00')->hour == 8 ? '👍 On Time' : '⚠️ Late') }}
                    </div>
                </div>
                
                <div class="text-center p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                    <div class="text-2xl font-bold text-purple-600">{{ $summary['average_check_out'] ?? '--:--' }}</div>
                    <div class="text-sm text-purple-700 dark:text-purple-300">Rata² Check-out</div>
                    <div class="text-xs text-gray-600">consistent timing</div>
                </div>
            </div>

            {{-- Overtime Analysis --}}
            @if(isset($summary['overtime_hours']) && $summary['overtime_hours'] > 0)
            <div class="p-4 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-orange-800 dark:text-orange-200">Overtime</div>
                        <div class="text-sm text-orange-700 dark:text-orange-300">{{ number_format($summary['overtime_hours'], 1) }} jam bulan ini</div>
                    </div>
                    <div class="text-2xl">⏱️</div>
                </div>
            </div>
            @endif

            {{-- Productivity Score --}}
            @php
                $productivityFactors = [
                    'attendance' => $summary['attendance_percentage'] ?? 0,
                    'punctuality' => $compliance['punctuality_score'] ?? 0,
                    'consistency' => $efficiency,
                    'gps_compliance' => $compliance['gps_validation_rate'] ?? 0,
                ];
                
                $productivityScore = collect($productivityFactors)->avg();
            @endphp
            
            <div class="p-4 rounded-lg bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20">
                <div class="text-center">
                    <div class="text-lg font-bold text-indigo-600">Skor Produktivitas</div>
                    <div class="text-3xl font-bold text-indigo-700 my-2">{{ number_format($productivityScore, 1) }}/100</div>
                    <div class="text-sm text-indigo-600">
                        {{ $productivityScore >= 90 ? '🏆 Sangat Produktif' : 
                           ($productivityScore >= 80 ? '🎯 Produktif' : 
                            ($productivityScore >= 70 ? '📈 Cukup Produktif' : '🔧 Perlu Perbaikan')) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>