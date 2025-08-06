<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Patient Reporting - Dokterku</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'medical': {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-card {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
        
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Calendar Styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .calendar-day {
            background: white;
            min-height: 80px;
            padding: 0.5rem;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .calendar-day:hover {
            background: #f3f4f6;
        }
        
        .calendar-day.other-month {
            background: #f9fafb;
            color: #9ca3af;
        }
        
        .calendar-day.today {
            background: #dbeafe;
            border: 2px solid #3b82f6;
        }
        
        .calendar-day.has-data {
            background: linear-gradient(135deg, #e0f2fe 0%, #b3e5fc 100%);
        }
        
        .intensity-none { background: #f9fafb; }
        .intensity-low { background: #dcfce7; }
        .intensity-medium { background: #bbf7d0; }
        .intensity-high { background: #86efac; }
        .intensity-very-high { background: #4ade80; }
        
        .calendar-counter {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: #1f2937;
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 9999px;
            min-width: 20px;
            text-align: center;
        }
        
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e5e7eb;
            border-radius: 0.5rem 0.5rem 0 0;
            overflow: hidden;
        }
        
        .calendar-header-day {
            background: #374151;
            color: white;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .patient-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .dark .patient-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.1) 100%);
            border-color: rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div x-data="patientReportingDashboard()" x-init="init()">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('petugas.enhanced.dashboard') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Jumlah Pasien</h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Analisis dan tracking pasien dengan kalendar view</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- View Toggle -->
                        <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <button @click="activeTab = 'dashboard'" 
                                    :class="activeTab === 'dashboard' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                Dashboard
                            </button>
                            <button @click="activeTab = 'calendar'" 
                                    :class="activeTab === 'calendar' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                Kalendar
                            </button>
                            <button @click="activeTab = 'analytics'" 
                                    :class="activeTab === 'analytics' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                Analytics
                            </button>
                        </div>
                        
                        <!-- Export Button -->
                        <button @click="exportReport()" 
                                class="px-4 py-2 bg-medical-600 text-white rounded-lg hover:bg-medical-700 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div x-show="activeTab === 'dashboard'" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Today's New Patients -->
                <div class="patient-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-medical-500 to-medical-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pasien Baru Hari Ini</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today_new'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Today's Visits -->
                <div class="patient-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Kunjungan Hari Ini</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today_visits'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Monthly Growth -->
                <div class="patient-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pertumbuhan Bulan Ini</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                @if($stats['growth_percentage'] >= 0)
                                    +{{ $stats['growth_percentage'] }}%
                                @else
                                    {{ $stats['growth_percentage'] }}%
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Active Patients -->
                <div class="patient-card rounded-xl p-6 shadow-lg animate-fade-in hover-lift">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pasien Aktif</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_patients'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Monthly Trends Chart -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Trend Bulanan</h3>
                    <div id="monthly-trends-chart"></div>
                </div>

                <!-- Age Distribution Chart -->
                <div class="glass-card rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribusi Usia</h3>
                    <div id="age-distribution-chart"></div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="glass-card rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aktivitas Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pasien</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tindakan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentActivity as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['pasien_nama'] }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $activity['pasien_nomor'] }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $activity['tindakan'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $activity['formatted_date'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $activity['time_ago'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Calendar Tab -->
        <div x-show="activeTab === 'calendar'" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Calendar Controls -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <!-- Month/Year Navigation -->
                    <div class="flex items-center space-x-2">
                        <button @click="previousMonth()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="currentMonthYear"></h2>
                        <button @click="nextMonth()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Today Button -->
                    <button @click="goToToday()" class="px-3 py-2 text-sm bg-medical-600 text-white rounded-lg hover:bg-medical-700">
                        Hari Ini
                    </button>
                </div>

                <!-- View Type Toggle -->
                <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button @click="calendarView = 'registration'" 
                            :class="calendarView === 'registration' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        Registrasi
                    </button>
                    <button @click="calendarView = 'visits'" 
                            :class="calendarView === 'visits' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        Kunjungan
                    </button>
                    <button @click="calendarView = 'procedures'" 
                            :class="calendarView === 'procedures' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        Tindakan
                    </button>
                </div>
            </div>

            <!-- Calendar -->
            <div class="glass-card rounded-xl shadow-lg overflow-hidden">
                <!-- Calendar Header -->
                <div class="calendar-header">
                    <div class="calendar-header-day">Min</div>
                    <div class="calendar-header-day">Sen</div>
                    <div class="calendar-header-day">Sel</div>
                    <div class="calendar-header-day">Rab</div>
                    <div class="calendar-header-day">Kam</div>
                    <div class="calendar-header-day">Jum</div>
                    <div class="calendar-header-day">Sab</div>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid" x-show="!calendarLoading">
                    <template x-for="day in calendarData" :key="day.date">
                        <div @click="selectDate(day)" 
                             :class="`calendar-day intensity-${day.intensity} ${day.is_today ? 'today' : ''} ${day.count > 0 ? 'has-data' : ''}`">
                            <div class="text-sm font-medium" x-text="day.display_date"></div>
                            <div x-show="day.count > 0" class="calendar-counter" x-text="day.count"></div>
                        </div>
                    </template>
                </div>

                <!-- Loading State -->
                <div x-show="calendarLoading" class="p-8 text-center">
                    <div class="loading-skeleton h-8 w-48 mx-auto mb-4"></div>
                    <div class="loading-skeleton h-6 w-32 mx-auto"></div>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-6 flex items-center justify-center space-x-6 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded intensity-none border"></div>
                    <span>Tidak ada</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded intensity-low"></div>
                    <span>Rendah</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded intensity-medium"></div>
                    <span>Sedang</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded intensity-high"></div>
                    <span>Tinggi</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded intensity-very-high"></div>
                    <span>Sangat Tinggi</span>
                </div>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div x-show="activeTab === 'analytics'" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Advanced Analytics</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fitur analytics mendalam akan segera tersedia</p>
            </div>
        </div>

        <!-- Selected Date Modal -->
        <div x-show="selectedDateModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="selectedDateModal = false"></div>
                
                <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white" x-text="selectedDateStats?.formatted_date"></h3>
                        <button @click="selectedDateModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4" x-show="selectedDateStats">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="selectedDateStats?.new_registrations || 0"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Registrasi Baru</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="selectedDateStats?.unique_visitors || 0"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Pengunjung</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="selectedDateStats?.total_procedures || 0"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Tindakan</div>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div x-show="selectedDateStats?.procedures?.length > 0">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Tindakan Hari Ini</h4>
                            <div class="max-h-60 overflow-y-auto space-y-2">
                                <template x-for="procedure in (selectedDateStats?.procedures || [])" :key="procedure.id">
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white" x-text="procedure.pasien_nama"></div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400" x-text="procedure.tindakan"></div>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400" x-text="procedure.waktu"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function patientReportingDashboard() {
            return {
                activeTab: 'dashboard',
                calendarView: 'registration',
                currentDate: new Date(),
                calendarData: [],
                calendarLoading: false,
                selectedDateModal: false,
                selectedDateStats: null,

                get currentMonthYear() {
                    return this.currentDate.toLocaleDateString('id-ID', { 
                        month: 'long', 
                        year: 'numeric' 
                    });
                },

                init() {
                    this.setupAxios();
                    this.loadCalendarData();
                    this.initCharts();
                },

                setupAxios() {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },

                previousMonth() {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    this.loadCalendarData();
                },

                nextMonth() {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    this.loadCalendarData();
                },

                goToToday() {
                    this.currentDate = new Date();
                    this.loadCalendarData();
                },

                async loadCalendarData() {
                    if (this.activeTab !== 'calendar') return;
                    
                    this.calendarLoading = true;
                    
                    try {
                        const response = await axios.get('/petugas/enhanced/jumlah-pasien/calendar-data', {
                            params: {
                                month: this.currentDate.getMonth() + 1,
                                year: this.currentDate.getFullYear(),
                                view: this.calendarView
                            }
                        });

                        if (response.data.success) {
                            this.calendarData = this.enrichCalendarData(response.data.data);
                        }
                    } catch (error) {
                        console.error('Error loading calendar data:', error);
                    } finally {
                        this.calendarLoading = false;
                    }
                },

                enrichCalendarData(data) {
                    const today = new Date();
                    return data.map(day => ({
                        ...day,
                        is_today: day.date === today.toISOString().split('T')[0]
                    }));
                },

                async selectDate(day) {
                    if (day.count === 0) return;
                    
                    try {
                        const response = await axios.get('/petugas/enhanced/jumlah-pasien/date-stats', {
                            params: {
                                date: day.date,
                                type: this.calendarView
                            }
                        });

                        if (response.data.success) {
                            this.selectedDateStats = response.data.data;
                            this.selectedDateModal = true;
                        }
                    } catch (error) {
                        console.error('Error loading date stats:', error);
                    }
                },

                async exportReport() {
                    try {
                        const response = await axios.post('/petugas/enhanced/jumlah-pasien/export', {
                            format: 'excel',
                            period: 'month',
                            include_charts: true
                        });

                        if (response.data.success) {
                            alert('Export berhasil! File akan segera tersedia.');
                        }
                    } catch (error) {
                        console.error('Error exporting:', error);
                        alert('Gagal mengekspor data');
                    }
                },

                initCharts() {
                    // Monthly Trends Chart
                    const monthlyTrendsOptions = {
                        series: [{
                            name: 'Pasien Baru',
                            data: @json(array_column($monthlyTrends, 'new_patients'))
                        }, {
                            name: 'Total Kunjungan',
                            data: @json(array_column($monthlyTrends, 'total_visits'))
                        }],
                        chart: {
                            type: 'line',
                            height: 300,
                            toolbar: { show: false }
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        colors: ['#10b981', '#3b82f6'],
                        xaxis: {
                            categories: @json(array_column($monthlyTrends, 'formatted_month'))
                        },
                        yaxis: {
                            title: { text: 'Jumlah' }
                        },
                        legend: {
                            position: 'top'
                        }
                    };

                    new ApexCharts(document.querySelector("#monthly-trends-chart"), monthlyTrendsOptions).render();

                    // Age Distribution Chart
                    const ageDistributionOptions = {
                        series: Object.values(@json($ageDistribution)),
                        chart: {
                            type: 'donut',
                            height: 300
                        },
                        labels: Object.keys(@json($ageDistribution)),
                        colors: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#f97316', '#6b7280'],
                        legend: {
                            position: 'bottom'
                        }
                    };

                    new ApexCharts(document.querySelector("#age-distribution-chart"), ageDistributionOptions).render();
                }
            }
        }

        // Watch for tab changes to load calendar data
        document.addEventListener('alpine:init', () => {
            Alpine.store('app', {
                watchCalendarTab() {
                    this.$watch('activeTab', (value) => {
                        if (value === 'calendar') {
                            this.loadCalendarData();
                        }
                    });
                },
                watchCalendarView() {
                    this.$watch('calendarView', () => {
                        if (this.activeTab === 'calendar') {
                            this.loadCalendarData();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>