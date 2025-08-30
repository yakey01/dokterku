<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Detail Rekapitulasi Absensi</h1>
                        <p class="text-gray-600 mt-2">{{ $monthName }} {{ $year }}</p>
                    </div>
                    <a href="{{ $returnUrl }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="space-y-6">
                <!-- Staff Information -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-blue-600 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Informasi Staff</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Nama Lengkap</label>
                                <p class="text-lg font-semibold text-gray-900">{{ $record->staff_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Kategori</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                    {{ $record->staff_type === 'Dokter' ? 'bg-green-100 text-green-800' : 
                                       ($record->staff_type === 'Paramedis' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ $record->staff_type }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Jabatan</label>
                                <p class="text-lg font-semibold text-gray-900">{{ $record->position }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Ranking Kehadiran</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-lg font-bold
                                    {{ $record->rank <= 3 ? 'bg-yellow-100 text-yellow-800' : 
                                       ($record->rank <= 10 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                    #{{ $record->rank }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-green-600 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Statistik Kehadiran</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center bg-green-50 rounded-lg p-4">
                                <div class="text-3xl font-bold text-green-600 mb-2">{{ $record->days_present }}</div>
                                <div class="text-sm text-gray-600">Hari Hadir</div>
                                <div class="text-xs text-gray-500 mt-1">dari {{ $record->total_working_days }} hari kerja</div>
                            </div>
                            <div class="text-center bg-blue-50 rounded-lg p-4">
                                <div class="text-3xl font-bold text-blue-600 mb-2">{{ $record->total_working_days }}</div>
                                <div class="text-sm text-gray-600">Total Hari Kerja</div>
                                <div class="text-xs text-gray-500 mt-1">periode {{ $monthName }} {{ $year }}</div>
                            </div>
                            <div class="text-center bg-purple-50 rounded-lg p-4">
                                <div class="text-3xl font-bold text-purple-600 mb-2">{{ number_format($record->attendance_percentage, 1) }}%</div>
                                <div class="text-sm text-gray-600">Persentase Kehadiran</div>
                                <div class="text-xs text-gray-500 mt-1">basis ranking</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-6">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Progress Kehadiran</span>
                                <span>{{ number_format($record->attendance_percentage, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all duration-500
                                    {{ $record->attendance_percentage >= 95 ? 'bg-green-600' : 
                                       ($record->attendance_percentage >= 85 ? 'bg-blue-600' : 
                                        ($record->attendance_percentage >= 75 ? 'bg-yellow-600' : 'bg-red-600')) }}"
                                     style="width: {{ min($record->attendance_percentage, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Details -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-gray-600 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Detail Waktu & Status</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Rata-rata Check In</label>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ $record->average_check_in ? \Carbon\Carbon::parse($record->average_check_in)->format('H:i') : '--:--' }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">waktu masuk</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Rata-rata Check Out</label>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ $record->average_check_out ? \Carbon\Carbon::parse($record->average_check_out)->format('H:i') : '--:--' }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">waktu keluar</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Total Jam Kerja</label>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ number_format($record->total_working_hours ?? 0, 1) }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">jam</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Status Kehadiran</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $record->status === 'excellent' ? 'bg-green-100 text-green-800' : 
                                       ($record->status === 'good' ? 'bg-blue-100 text-blue-800' : 
                                        ($record->status === 'average' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ $record->getStatusLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-indigo-600 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Informasi Tambahan</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Perhitungan Kehadiran</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total hari kerja:</span>
                                        <span class="font-medium">{{ $record->total_working_days }} hari</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Hari hadir:</span>
                                        <span class="font-medium">{{ $record->days_present }} hari</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Hari tidak hadir:</span>
                                        <span class="font-medium">{{ $record->total_working_days - $record->days_present }} hari</span>
                                    </div>
                                    <hr>
                                    <div class="flex justify-between font-semibold">
                                        <span>Persentase kehadiran:</span>
                                        <span class="
                                            {{ $record->attendance_percentage >= 95 ? 'text-green-600' : 
                                               ($record->attendance_percentage >= 85 ? 'text-blue-600' : 
                                                ($record->attendance_percentage >= 75 ? 'text-yellow-600' : 'text-red-600')) }}">
                                            {{ number_format($record->attendance_percentage, 2) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Status & Ranking</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Ranking:</span>
                                        <span class="font-medium">#{{ $record->rank }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Status:</span>
                                        <span class="font-medium">{{ $record->getStatusLabel() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Periode:</span>
                                        <span class="font-medium">{{ $monthName }} {{ $year }}</span>
                                    </div>
                                    <hr>
                                    <div class="text-xs text-gray-500 mt-2">
                                        <p><strong>Catatan:</strong> Ranking berdasarkan persentase kehadiran tertinggi. Status excellent (≥95%), good (85-94%), average (75-84%), poor (<75%).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Analytics & Insights -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Analisis Kinerja & Wawasan
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Punctuality Score -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-blue-700">Tingkat Ketepatan Waktu</span>
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            @php
                                $onTimeCount = $dailyAttendance->where('late_minutes', 0)->where('actual_checkin', '!=', null)->count();
                                $totalAttendance = $dailyAttendance->where('actual_checkin', '!=', null)->count();
                                $punctualityScore = $totalAttendance > 0 ? round(($onTimeCount / $totalAttendance) * 100, 1) : 0;
                            @endphp
                            <div class="text-2xl font-bold text-blue-900">{{ $punctualityScore }}%</div>
                            <div class="text-xs text-blue-600">{{ $onTimeCount }}/{{ $totalAttendance }} tepat waktu</div>
                        </div>

                        <!-- Consistency Score -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-green-700">Konsistensi Kehadiran</span>
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            @php
                                $scheduledDays = $dailyAttendance->where('scheduled_hours', '>', 0)->count();
                                $consistencyScore = $scheduledDays > 0 ? round(($record->days_present / $scheduledDays) * 100, 1) : 0;
                            @endphp
                            <div class="text-2xl font-bold text-green-900">{{ $consistencyScore }}%</div>
                            <div class="text-xs text-green-600">{{ $record->days_present }}/{{ $scheduledDays }} hari hadir</div>
                        </div>

                        <!-- Overtime Hours -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-purple-700">Total Jam Lembur</span>
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            @php
                                $totalOvertimeHours = $dailyAttendance->sum(function($day) {
                                    return max(0, ($day['actual_hours'] ?? 0) - ($day['scheduled_hours'] ?? 0));
                                });
                            @endphp
                            <div class="text-2xl font-bold text-purple-900">{{ number_format($totalOvertimeHours, 1) }}</div>
                            <div class="text-xs text-purple-600">jam tambahan</div>
                        </div>

                        <!-- Average Daily Performance -->
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-orange-700">Rata-rata Harian</span>
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            @php
                                $avgDailyHours = $record->days_present > 0 ? round(($record->total_working_hours ?? 0) / $record->days_present, 1) : 0;
                            @endphp
                            <div class="text-2xl font-bold text-orange-900">{{ $avgDailyHours }}</div>
                            <div class="text-xs text-orange-600">jam per hari hadir</div>
                        </div>
                    </div>

                    <!-- Performance Trends -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            Wawasan Kinerja
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div class="bg-white p-3 rounded border">
                                <div class="font-medium text-gray-700 mb-1">📊 Tren Kehadiran</div>
                                <div class="text-gray-600">
                                    @if($record->attendance_percentage >= 95)
                                        Sangat konsisten, pertahankan kinerja excellent!
                                    @elseif($record->attendance_percentage >= 85)
                                        Kinerja baik, ada ruang untuk improvement.
                                    @elseif($record->attendance_percentage >= 75)
                                        Perlu peningkatan konsistensi kehadiran.
                                    @else
                                        Memerlukan perhatian khusus dan perbaikan.
                                    @endif
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded border">
                                <div class="font-medium text-gray-700 mb-1">⏰ Pola Waktu</div>
                                <div class="text-gray-600">
                                    Rata-rata masuk: {{ $record->average_check_in ? \Carbon\Carbon::parse($record->average_check_in)->format('H:i') : 'N/A' }}<br>
                                    Rata-rata pulang: {{ $record->average_check_out ? \Carbon\Carbon::parse($record->average_check_out)->format('H:i') : 'N/A' }}
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded border">
                                <div class="font-medium text-gray-700 mb-1">🎯 Rekomendasi</div>
                                <div class="text-gray-600">
                                    @if($punctualityScore < 80)
                                        Fokus pada ketepatan waktu masuk.
                                    @elseif($record->attendance_percentage < 90)
                                        Tingkatkan konsistensi kehadiran.
                                    @else
                                        Kinerja sudah optimal, pertahankan!
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- World-Class Daily Breakdown Table -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v8a1 1 0 01-1 1H5a1 1 0 01-1-1V8a1 1 0 011-1h3z"/>
                            </svg>
                            Rincian Kehadiran Harian
                        </h2>
                        <div class="flex items-center space-x-3">
                            <span class="text-purple-100 text-sm">{{ $monthName }} {{ $year }}</span>
                            <button onclick="exportTableToCSV('daily-attendance-table', '{{ $record->staff_name }}_{{ $monthName }}_{{ $year }}')" 
                                    class="inline-flex items-center px-3 py-1 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export CSV
                            </button>
                            <button onclick="window.print()" 
                                    class="inline-flex items-center px-3 py-1 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="daily-attendance-table" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jadwal Jaga</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Jadwal</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Aktual</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($dailyAttendance as $day)
                            <tr class="hover:bg-gray-50 transition-colors duration-200
                                {{ $day['is_weekend'] ? 'bg-blue-50' : '' }}
                                {{ $day['status'] === 'hadir_penuh' ? 'bg-green-50' : '' }}
                                {{ $day['status'] === 'hadir_sebagian' ? 'bg-yellow-50' : '' }}">
                                
                                <!-- Date Column -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($day['date'])->format('d') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($day['date'])->format('M') }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Day Name -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900
                                        {{ $day['is_weekend'] ? 'text-blue-600' : '' }}">
                                        {{ $day['day_name_id'] }}
                                    </span>
                                    @if($day['is_weekend'])
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Weekend
                                        </span>
                                    @endif
                                </td>

                                <!-- Scheduled Hours -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($day['scheduled_start'] && $day['scheduled_end'])
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($day['scheduled_start'])->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($day['scheduled_end'])->format('H:i') }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Scheduled Hours Count -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($day['scheduled_hours'] > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ number_format($day['scheduled_hours'], 1) }}h
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Actual Check In -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($day['actual_checkin'])
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($day['actual_checkin'])->format('H:i') }}
                                        </div>
                                        @if($day['late_minutes'] > 0)
                                            <div class="text-xs text-red-500">
                                                +{{ $day['late_minutes'] }}m terlambat
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Actual Check Out -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($day['actual_checkout'])
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($day['actual_checkout'])->format('H:i') }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Actual Hours -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($day['actual_hours'] > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                            {{ $day['actual_hours'] >= $day['scheduled_hours'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ number_format($day['actual_hours'], 1) }}h
                                        </span>
                                        @if($day['actual_hours'] > $day['scheduled_hours'])
                                            <div class="text-xs text-green-600 mt-1">
                                                +{{ number_format($day['actual_hours'] - $day['scheduled_hours'], 1) }}h lembur
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @php
                                        $statusConfig = [
                                            'hadir_penuh' => ['text' => 'Hadir Penuh', 'class' => 'bg-green-100 text-green-800', 'icon' => '✓'],
                                            'hadir_sebagian' => ['text' => 'Hadir Sebagian', 'class' => 'bg-yellow-100 text-yellow-800', 'icon' => '⚠'],
                                            'dijadwalkan' => ['text' => 'Dijadwalkan', 'class' => 'bg-blue-100 text-blue-800', 'icon' => '📅'],
                                            'libur' => ['text' => 'Libur', 'class' => 'bg-gray-100 text-gray-600', 'icon' => '🏠'],
                                            'alpha' => ['text' => 'Tidak Hadir', 'class' => 'bg-red-100 text-red-800', 'icon' => '✗']
                                        ];
                                        $status = $statusConfig[$day['status']] ?? $statusConfig['libur'];
                                    @endphp
                                    
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status['class'] }}">
                                        <span class="mr-1">{{ $status['icon'] }}</span>
                                        {{ $status['text'] }}
                                    </span>
                                </td>

                                <!-- Location -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($day['location'])
                                        <span class="text-sm text-gray-900">{{ $day['location'] }}</span>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v8a1 1 0 01-1 1H5a1 1 0 01-1-1V8a1 1 0 011-1h3z"/>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Jadwal Jaga</h3>
                                        <p class="text-gray-600 text-center max-w-md">
                                            Tidak ada jadwal jaga yang ditemukan untuk <strong>{{ $record->staff_name }}</strong> 
                                            pada periode <strong>{{ $monthName }} {{ $year }}</strong>.
                                        </p>
                                        <p class="text-sm text-gray-500 mt-2">
                                            Hanya tanggal dengan jadwal jaga yang akan ditampilkan dalam tabel ini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Table Summary -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <div class="text-lg font-bold text-green-600">{{ $dailyAttendance->where('status', 'hadir_penuh')->count() }}</div>
                            <div class="text-xs text-gray-600">Hadir Penuh</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-yellow-600">{{ $dailyAttendance->where('status', 'hadir_sebagian')->count() }}</div>
                            <div class="text-xs text-gray-600">Hadir Sebagian</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-red-600">{{ $dailyAttendance->where('scheduled_hours', '>', 0)->where('actual_hours', 0)->count() }}</div>
                            <div class="text-xs text-gray-600">Tidak Hadir</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-purple-600">{{ number_format($dailyAttendance->sum('actual_hours'), 1) }}</div>
                            <div class="text-xs text-gray-600">Total Jam Aktual</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex justify-between">
                <a href="{{ $returnUrl }}" 
                   class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Daftar
                </a>
                
                <div class="flex space-x-3">
                    <button onclick="window.print()" 
                            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export to CSV Functionality -->
    <script>
        function exportTableToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) {
                alert('Table not found!');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            // Add header with staff info
            csv.push([
                'Nama Staff: {{ $record->staff_name }}',
                'Kategori: {{ $record->staff_type }}', 
                'Periode: {{ $monthName }} {{ $year }}',
                'Total Kehadiran: {{ $record->attendance_percentage }}%'
            ].join(','));
            csv.push(''); // Empty line
            
            // Process table rows
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cols = row.querySelectorAll('td, th');
                let csvRow = [];
                
                for (let j = 0; j < cols.length; j++) {
                    let cellText = cols[j].innerText.trim();
                    // Clean up text (remove extra whitespace and newlines)
                    cellText = cellText.replace(/\s+/g, ' ').replace(/\n/g, ' ');
                    // Escape commas and quotes
                    cellText = cellText.replace(/"/g, '""');
                    if (cellText.includes(',')) {
                        cellText = `"${cellText}"`;
                    }
                    csvRow.push(cellText);
                }
                csv.push(csvRow.join(','));
            }
            
            // Create and download CSV
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `${filename}_detail_kehadiran.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Show success message
                showNotification('✅ Data berhasil diekspor ke CSV!', 'success');
            }
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Add smooth scrolling for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll to daily table when page loads
            const dailyTable = document.getElementById('daily-attendance-table');
            if (dailyTable && window.location.hash === '#daily') {
                dailyTable.scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>

    <!-- Print Styles -->
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
            
            /* Optimize table for printing */
            table { font-size: 11px; }
            .bg-gradient-to-r, .bg-gradient-to-br { background: #f3f4f6 !important; color: #1f2937 !important; }
            .text-white { color: #1f2937 !important; }
        }
        
        /* Enhanced responsive design */
        @media (max-width: 768px) {
            .overflow-x-auto {
                max-width: 100vw;
                overflow-x: scroll;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 800px;
            }
            
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
        }
    </style>
</body>
</html>