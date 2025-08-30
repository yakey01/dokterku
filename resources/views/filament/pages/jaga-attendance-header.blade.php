{{-- Summary Statistics Header --}}
<div class="mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @php
            try {
                $allData = \App\Models\AttendanceJagaRecap::getJagaRecapData(null, $currentMonth, $currentYear);
                $dokterData = $allData->where('profession', 'Dokter');
                $paramedisData = $allData->where('profession', 'Paramedis');
                $nonParamedisData = $allData->where('profession', 'NonParamedis');
                
                $totalStaff = $allData->count();
                $excellentStaff = $allData->where('status', 'excellent')->count();
                $avgAttendance = $allData->avg('attendance_percentage') ?? 0;
                $avgCompliance = $allData->avg('schedule_compliance_rate') ?? 0;
            } catch (\Exception $e) {
                $totalStaff = $excellentStaff = $avgAttendance = $avgCompliance = 0;
                $dokterData = $paramedisData = $nonParamedisData = collect();
            }
        @endphp

        {{-- Total Staff --}}
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Staff</p>
                    <p class="text-2xl font-bold">{{ $totalStaff }}</p>
                    <p class="text-xs opacity-75">staff aktif</p>
                </div>
                <div class="text-3xl opacity-80">👥</div>
            </div>
        </div>

        {{-- Excellent Performance --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Performa Excellent</p>
                    <p class="text-2xl font-bold">{{ $excellentStaff }}/{{ $totalStaff }}</p>
                    <p class="text-xs opacity-75">{{ $totalStaff > 0 ? number_format(($excellentStaff / $totalStaff) * 100, 1) : 0 }}%</p>
                </div>
                <div class="text-3xl opacity-80">⭐</div>
            </div>
        </div>

        {{-- Average Attendance --}}
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Rata-rata Kehadiran</p>
                    <p class="text-2xl font-bold">{{ number_format($avgAttendance, 1) }}%</p>
                    <p class="text-xs opacity-75">semua profesi</p>
                </div>
                <div class="text-3xl opacity-80">📊</div>
            </div>
        </div>

        {{-- Average Compliance --}}
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Rata-rata Kepatuhan</p>
                    <p class="text-2xl font-bold">{{ number_format($avgCompliance, 1) }}%</p>
                    <p class="text-xs opacity-75">sesuai jadwal</p>
                </div>
                <div class="text-3xl opacity-80">⏰</div>
            </div>
        </div>
    </div>
</div>

{{-- Profession Breakdown --}}
<div class="mb-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
        Breakdown by Profession - {{ \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->format('F Y') }}
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Dokter Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="mr-2">👨‍⚕️</span> Dokter
                </h4>
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                    {{ $dokterData->count() }} staff
                </span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Excellent:</span>
                    <span class="text-green-600 font-medium">{{ $dokterData->where('status', 'excellent')->count() }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Good:</span>
                    <span class="text-blue-600 font-medium">{{ $dokterData->where('status', 'good')->count() }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Avg Attendance:</span>
                    <span class="font-medium">{{ number_format($dokterData->avg('attendance_percentage') ?? 0, 1) }}%</span>
                </div>
            </div>
        </div>

        {{-- Paramedis Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="mr-2">👩‍⚕️</span> Paramedis
                </h4>
                <span class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                    {{ $paramedisData->count() }} staff
                </span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Excellent:</span>
                    <span class="text-green-600 font-medium">{{ $paramedisData->where('status', 'excellent')->count() }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Good:</span>
                    <span class="text-blue-600 font-medium">{{ $paramedisData->where('status', 'good')->count() }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Avg Attendance:</span>
                    <span class="font-medium">{{ number_format($paramedisData->avg('attendance_percentage') ?? 0, 1) }}%</span>
                </div>
            </div>
        </div>

        {{-- Non-Paramedis Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="mr-2">👤</span> Non-Paramedis
                </h4>
                <span class="text-sm text-orange-600 dark:text-orange-400 font-medium">
                    {{ $nonParamedisData->count() }} staff
                </span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Excellent:</span>
                    <span class="text-green-600 font-medium">{{ $nonParamedisData->where('status', 'excellent')->count() }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Good:</span>
                    <span class="text-blue-600 font-medium">{{ $nonParamedisData->where('status', 'good')->count() }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Avg Attendance:</span>
                    <span class="font-medium">{{ number_format($nonParamedisData->avg('attendance_percentage') ?? 0, 1) }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Performance Alert Indicators --}}
@if($avgAttendance < 85)
<div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
    <div class="flex items-center">
        <div class="text-red-400 mr-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <h4 class="font-medium text-red-800 dark:text-red-200">Perhatian: Rata-rata kehadiran di bawah target</h4>
            <p class="text-sm text-red-700 dark:text-red-300">
                Diperlukan evaluasi dan perbaikan untuk mencapai standar minimal 85%
            </p>
        </div>
    </div>
</div>
@elseif($avgAttendance >= 95)
<div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
    <div class="flex items-center">
        <div class="text-green-400 mr-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div>
            <h4 class="font-medium text-green-800 dark:text-green-200">Excellent: Target kehadiran tercapai</h4>
            <p class="text-sm text-green-700 dark:text-green-300">
                Rata-rata kehadiran memenuhi standar excellent (≥95%)
            </p>
        </div>
    </div>
</div>
@endif