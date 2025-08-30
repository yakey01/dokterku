<div class="space-y-6">
    {{-- Header Information --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $record->staff_name }}
            </h3>
            <div class="flex space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    {{ $record->profession === 'Dokter' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 
                       ($record->profession === 'Paramedis' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : 
                        'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100') }}">
                    {{ $record->profession }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $record->status === 'excellent' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100' :
                       ($record->status === 'good' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' :
                        ($record->status === 'average' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' :
                         'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100')) }}">
                    {{ $record->status_label }}
                </span>
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->position }}</p>
    </div>

    {{-- Key Metrics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Attendance Percentage --}}
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Persentase Kehadiran</p>
                    <p class="text-2xl font-bold">{{ number_format($record->attendance_percentage, 1) }}%</p>
                </div>
                <div class="text-3xl opacity-80">📊</div>
            </div>
        </div>

        {{-- Schedule Compliance --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Kepatuhan Jadwal</p>
                    <p class="text-2xl font-bold">{{ number_format($record->schedule_compliance_rate, 1) }}%</p>
                </div>
                <div class="text-3xl opacity-80">⏰</div>
            </div>
        </div>

        {{-- GPS Validation --}}
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Validasi GPS</p>
                    <p class="text-2xl font-bold">{{ number_format($record->gps_validation_rate, 1) }}%</p>
                </div>
                <div class="text-3xl opacity-80">📍</div>
            </div>
        </div>

        {{-- Working Hours --}}
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Jam Kerja</p>
                    <p class="text-2xl font-bold">{{ number_format($record->total_working_hours, 1) }}</p>
                    <p class="text-xs opacity-75">jam</p>
                </div>
                <div class="text-3xl opacity-80">🕒</div>
            </div>
        </div>
    </div>

    {{-- Detailed Statistics --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Attendance Details --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <span class="mr-2">📅</span> Detail Kehadiran
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hari Dijadwalkan:</span>
                    <span class="font-medium">{{ $record->total_scheduled_days ?? 0 }} hari</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hari Hadir:</span>
                    <span class="font-medium text-green-600">{{ $record->days_present ?? 0 }} hari</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hari Tidak Hadir:</span>
                    <span class="font-medium text-red-600">{{ ($record->total_scheduled_days ?? 0) - ($record->days_present ?? 0) }} hari</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Kekurangan Menit:</span>
                    <span class="font-medium {{ $record->total_shortfall_minutes > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $record->formatted_shortfall }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Time Analysis --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <span class="mr-2">⏱️</span> Analisis Waktu
            </h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Rata-rata Check In:</span>
                    <span class="font-medium">{{ $record->average_check_in ?? '--:--' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Rata-rata Check Out:</span>
                    <span class="font-medium">{{ $record->average_check_out ?? '--:--' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Jam Scheduled:</span>
                    <span class="font-medium">{{ number_format($record->total_scheduled_hours ?? 0, 1) }} jam</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Jam Actual:</span>
                    <span class="font-medium">{{ number_format($record->total_working_hours ?? 0, 1) }} jam</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Performance Indicators --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <span class="mr-2">🎯</span> Indikator Kinerja
        </h4>
        
        {{-- Progress Bars --}}
        <div class="space-y-4">
            {{-- Attendance Percentage --}}
            <div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Persentase Kehadiran</span>
                    <span>{{ number_format($record->attendance_percentage, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ min(100, $record->attendance_percentage) }}%"></div>
                </div>
            </div>

            {{-- Schedule Compliance --}}
            <div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Kepatuhan Jadwal</span>
                    <span>{{ number_format($record->schedule_compliance_rate, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ min(100, $record->schedule_compliance_rate) }}%"></div>
                </div>
            </div>

            {{-- GPS Validation --}}
            <div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Validasi GPS</span>
                    <span>{{ number_format($record->gps_validation_rate, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="bg-purple-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ min(100, $record->gps_validation_rate) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Professional Standards --}}
    @if($record->profession === 'Dokter')
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
        <h4 class="text-md font-medium text-green-900 dark:text-green-100 mb-2 flex items-center">
            <span class="mr-2">👨‍⚕️</span> Standar Dokter
        </h4>
        <p class="text-sm text-green-800 dark:text-green-200">
            Target: ≥95% kehadiran untuk menjaga kontinuitas pelayanan pasien dan kesiapan emergency
        </p>
    </div>
    @elseif($record->profession === 'Paramedis')
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
        <h4 class="text-md font-medium text-blue-900 dark:text-blue-100 mb-2 flex items-center">
            <span class="mr-2">👩‍⚕️</span> Standar Paramedis
        </h4>
        <p class="text-sm text-blue-800 dark:text-blue-200">
            Target: ≥90% kehadiran dengan fokus pada coverage shift dan rasio perawat-pasien
        </p>
    </div>
    @else
    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-800">
        <h4 class="text-md font-medium text-orange-900 dark:text-orange-100 mb-2 flex items-center">
            <span class="mr-2">👤</span> Standar Non-Paramedis
        </h4>
        <p class="text-sm text-orange-800 dark:text-orange-200">
            Target: ≥85% kehadiran untuk memastikan dukungan administratif dan operasional
        </p>
    </div>
    @endif

    {{-- Period Information --}}
    <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-2">
        Data periode: {{ \Carbon\Carbon::createFromDate($record->year, $record->month, 1)->format('F Y') }}
    </div>
</div>