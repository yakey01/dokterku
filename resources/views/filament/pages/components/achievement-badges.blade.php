{{-- Achievement Badges Component --}}
@props(['summary', 'compliance', 'profession'])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
    <h4 class="text-md font-semibold mb-4 flex items-center">
        <span class="mr-2">🏆</span> Pencapaian & Penghargaan
    </h4>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Perfect Attendance Badge --}}
        @if(($summary['attendance_percentage'] ?? 0) >= 100)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-yellow-100 to-yellow-200 dark:from-yellow-900/30 dark:to-yellow-800/30 border border-yellow-300 dark:border-yellow-600">
            <div class="text-3xl mb-2">🥇</div>
            <div class="font-bold text-yellow-800 dark:text-yellow-200 text-sm">Perfect Attendance</div>
            <div class="text-xs text-yellow-700 dark:text-yellow-300">100% Kehadiran</div>
        </div>
        @endif

        {{-- Excellent Performance Badge --}}
        @if(($summary['attendance_percentage'] ?? 0) >= 95)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 border border-green-300 dark:border-green-600">
            <div class="text-3xl mb-2">⭐</div>
            <div class="font-bold text-green-800 dark:text-green-200 text-sm">Excellent</div>
            <div class="text-xs text-green-700 dark:text-green-300">≥95% Attendance</div>
        </div>
        @endif

        {{-- Punctuality Champion --}}
        @if(($compliance['late_arrivals'] ?? 0) <= 1)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 border border-blue-300 dark:border-blue-600">
            <div class="text-3xl mb-2">⏰</div>
            <div class="font-bold text-blue-800 dark:text-blue-200 text-sm">Punctual</div>
            <div class="text-xs text-blue-700 dark:text-blue-300">Jarang Terlambat</div>
        </div>
        @endif

        {{-- GPS Compliance Star --}}
        @if(($compliance['gps_validation_rate'] ?? 0) >= 95)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/30 dark:to-purple-800/30 border border-purple-300 dark:border-purple-600">
            <div class="text-3xl mb-2">📍</div>
            <div class="font-bold text-purple-800 dark:text-purple-200 text-sm">GPS Pro</div>
            <div class="text-xs text-purple-700 dark:text-purple-300">95%+ GPS Valid</div>
        </div>
        @endif

        {{-- Consistent Worker --}}
        @if(($summary['attended_shifts'] ?? 0) >= 20 && ($summary['attendance_percentage'] ?? 0) >= 90)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/30 dark:to-indigo-800/30 border border-indigo-300 dark:border-indigo-600">
            <div class="text-3xl mb-2">🎯</div>
            <div class="font-bold text-indigo-800 dark:text-indigo-200 text-sm">Consistent</div>
            <div class="text-xs text-indigo-700 dark:text-indigo-300">High Reliability</div>
        </div>
        @endif

        {{-- Overtime Hero --}}
        @if(($summary['overtime_hours'] ?? 0) >= 10)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-orange-100 to-orange-200 dark:from-orange-900/30 dark:to-orange-800/30 border border-orange-300 dark:border-orange-600">
            <div class="text-3xl mb-2">💪</div>
            <div class="font-bold text-orange-800 dark:text-orange-200 text-sm">Dedicated</div>
            <div class="text-xs text-orange-700 dark:text-orange-300">{{ number_format($summary['overtime_hours'], 1) }}j Overtime</div>
        </div>
        @endif

        {{-- Professional Standard Met --}}
        @php
            $professionStandards = [
                'Dokter' => 95,
                'Paramedis' => 90,
                'NonParamedis' => 85
            ];
            $requiredRate = $professionStandards[$profession] ?? 80;
        @endphp
        
        @if(($summary['attendance_percentage'] ?? 0) >= $requiredRate)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900/30 dark:to-emerald-800/30 border border-emerald-300 dark:border-emerald-600">
            <div class="text-3xl mb-2">🏥</div>
            <div class="font-bold text-emerald-800 dark:text-emerald-200 text-sm">{{ $profession }} Standard</div>
            <div class="text-xs text-emerald-700 dark:text-emerald-300">≥{{ $requiredRate }}% Met</div>
        </div>
        @endif

        {{-- Team Player --}}
        @if(($compliance['schedule_compliance_rate'] ?? 0) >= 90)
        <div class="text-center p-4 rounded-lg bg-gradient-to-br from-pink-100 to-pink-200 dark:from-pink-900/30 dark:to-pink-800/30 border border-pink-300 dark:border-pink-600">
            <div class="text-3xl mb-2">🤝</div>
            <div class="font-bold text-pink-800 dark:text-pink-200 text-sm">Team Player</div>
            <div class="text-xs text-pink-700 dark:text-pink-300">Schedule Compliant</div>
        </div>
        @endif
    </div>
    
    {{-- Achievement Progress --}}
    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Progress Menuju Achievement</h5>
        
        <div class="space-y-3">
            {{-- Perfect Month Progress --}}
            @php
                $perfectProgress = min(100, ($summary['attendance_percentage'] ?? 0));
            @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Perfect Month (100%)</span>
                    <span>{{ number_format($perfectProgress, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-600">
                    <div class="bg-yellow-500 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $perfectProgress }}%"></div>
                </div>
            </div>

            {{-- GPS Master Progress --}}
            @php
                $gpsProgress = min(100, ($compliance['gps_validation_rate'] ?? 0));
            @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>GPS Master (95%)</span>
                    <span>{{ number_format($gpsProgress, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-600">
                    <div class="bg-purple-500 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ min(100, $gpsProgress / 95 * 100) }}%"></div>
                </div>
            </div>

            {{-- Punctuality Expert Progress --}}
            @php
                $maxLate = 2; // Max allowed late for highest standard
                $actualLate = $compliance['late_arrivals'] ?? 0;
                $punctualityProgress = max(0, 100 - ($actualLate / $maxLate * 100));
            @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Punctuality Expert (≤2 late)</span>
                    <span>{{ $actualLate }} late this month</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-600">
                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $punctualityProgress }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>