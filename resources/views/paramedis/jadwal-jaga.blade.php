<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jadwal Jaga - Dokterku Paramedis</title>
    
    <!-- Tailwind CSS v4 -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes pulseSoft {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1f2937;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
        
        /* Glassmorphism effect */
        .glass {
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Hover glow effect */
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        
        /* Calendar grid styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        
        .shift-indicator {
            position: relative;
            overflow: hidden;
        }
        
        .shift-indicator::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--shift-color, #3b82f6), transparent);
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased h-full overflow-x-hidden">
    <div class="flex min-h-screen">
        <!-- Dark Elegant Sidebar (Same structure as dashboard) -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-80 bg-gray-900 text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-20 px-6 border-b border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="heart-pulse" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold gradient-text">Dokterku</h1>
                        <p class="text-sm text-gray-400">Paramedis Panel</p>
                    </div>
                </div>
                <button id="closeSidebar" class="lg:hidden p-2 rounded-md hover:bg-gray-800 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- User Profile -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center font-bold text-lg">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-400">Paramedis</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <a href="/paramedis" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 group-hover:text-blue-400"></i>
                    <span class="font-medium group-hover:text-blue-400">Dashboard</span>
                </a>
                
                <a href="/paramedis/presensi" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="clock" class="w-5 h-5 group-hover:text-blue-400"></i>
                    <span class="font-medium group-hover:text-blue-400">Presensi</span>
                </a>
                
                <a href="/paramedis/jaspel" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="banknote" class="w-5 h-5 group-hover:text-green-400"></i>
                    <span class="font-medium group-hover:text-green-400">Jaspel</span>
                </a>
                
                <a href="/paramedis/jadwal-jaga" class="flex items-center space-x-3 px-4 py-3 rounded-md bg-blue-600 text-white hover-glow transition-all duration-200">
                    <i data-lucide="calendar" class="w-5 h-5"></i>
                    <span class="font-medium">Jadwal Jaga</span>
                </a>
                
                <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="settings" class="w-5 h-5 group-hover:text-gray-400"></i>
                    <span class="font-medium group-hover:text-gray-400">Pengaturan</span>
                </a>
            </nav>
            
            <!-- Footer -->
            <div class="absolute bottom-4 left-4 right-4">
                <div class="glass rounded-lg p-4">
                    <div class="flex items-center space-x-2 text-sm text-gray-300">
                        <i data-lucide="calendar-check" class="w-4 h-4 text-purple-400"></i>
                        <span>Schedule Management</span>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Overlay for mobile -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>
        
        <!-- Main Content -->
        <main class="flex-1 lg:ml-0">
            <!-- Mobile Header -->
            <header class="lg:hidden bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between h-16 px-4">
                    <button id="openSidebar" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i data-lucide="menu" class="w-6 h-6 text-gray-600 dark:text-gray-300"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Jadwal Jaga</h1>
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </header>
            
            <!-- Schedule Content -->
            <div class="p-4 lg:p-8 space-y-8 animate-fade-in">
                <!-- Header Section -->
                <div class="animate-slide-up">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-3">
                                <i data-lucide="calendar-days" class="w-8 h-8 text-purple-500"></i>
                                Jadwal Jaga Saya
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">{{ $user->name }} • Periode: {{ $currentWeek }}</p>
                        </div>
                        
                        <!-- Time Filter -->
                        <div class="flex items-center space-x-3">
                            <select class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option>Minggu Ini</option>
                                <option>Bulan Ini</option>
                                <option>3 Bulan Terakhir</option>
                            </select>
                            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Schedule Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-slide-up" style="animation-delay: 0.2s">
                    <!-- Total Shifts This Month -->
                    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium mb-1">Total Shift Bulan Ini</p>
                                <p class="text-3xl font-bold">{{ $scheduleStats['totalShiftsThisMonth'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">shift terjadwal</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="calendar" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Hours -->
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium mb-1">Total Jam Kerja</p>
                                <p class="text-3xl font-bold">{{ $scheduleStats['totalHoursThisMonth'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="clock" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">jam bulan ini</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="clock-8" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upcoming Shifts -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-100 text-sm font-medium mb-1">Shift Mendatang</p>
                                <p class="text-3xl font-bold">{{ $scheduleStats['upcomingShifts'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="calendar-plus" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">shift berikutnya</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="calendar-plus" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overtime Hours -->
                    <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm font-medium mb-1">Jam Lembur</p>
                                <p class="text-3xl font-bold">{{ $scheduleStats['overtimeHours'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="clock-alert" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">jam overtime</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="clock-alert" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Shift Highlight -->
                @php
                    $todayShift = $weeklySchedule->firstWhere('is_today', true);
                @endphp
                
                @if($todayShift && $todayShift['has_shift'])
                <div class="animate-slide-up" style="animation-delay: 0.3s">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-l-4 border-blue-500 rounded-xl p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center animate-pulse-soft">
                                    <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Shift Hari Ini</h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ $todayShift['day_name_id'] }}, {{ $todayShift['formatted_date'] }} — 
                                        <span class="font-semibold text-blue-600">Shift {{ $todayShift['shift']['type'] }}</span>
                                        ({{ $todayShift['shift']['time'] }}) • {{ $todayShift['shift']['unit'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Aktif</span>
                                <i data-lucide="check-circle" class="w-6 h-6 text-green-500"></i>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Weekly Schedule Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 animate-slide-up" style="animation-delay: 0.4s">
                    <!-- Weekly Calendar -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="calendar-weeks" class="w-5 h-5 text-purple-500"></i>
                                Jadwal Mingguan
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                                {{ $currentWeek }}
                            </span>
                        </div>
                        
                        <!-- Days of week header -->
                        <div class="calendar-grid mb-4">
                            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                            <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">{{ $day }}</div>
                            @endforeach
                        </div>
                        
                        <!-- Weekly schedule grid -->
                        <div class="calendar-grid gap-2">
                            @foreach($weeklySchedule as $day)
                            <div class="shift-indicator bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center transition-all hover:shadow-md {{ $day['is_today'] ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30' : '' }}"
                                 style="--shift-color: {{ $day['shift']['color'] === 'blue' ? '#3b82f6' : ($day['shift']['color'] === 'green' ? '#10b981' : ($day['shift']['color'] === 'purple' ? '#8b5cf6' : '#6b7280')) }}">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $day['date']->day }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $day['day_name_id'] }}</div>
                                @if($day['has_shift'])
                                    <div class="text-xs font-medium mt-2 px-2 py-1 rounded-full bg-{{ $day['shift']['color'] }}-100 text-{{ $day['shift']['color'] }}-800">
                                        {{ $day['shift']['type'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $day['shift']['unit'] }}</div>
                                @else
                                    <div class="text-xs font-medium mt-2 px-2 py-1 rounded-full bg-gray-100 text-gray-600">Off</div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Upcoming Shifts List -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="list-checks" class="w-5 h-5 text-green-500"></i>
                                Shift Mendatang
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $upcomingShifts->count() }} shift</span>
                        </div>
                        
                        <div class="space-y-4">
                            @foreach($upcomingShifts as $shift)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-all {{ $shift['status'] === 'confirmed' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $shift['day_name'] }}, {{ $shift['formatted_date'] }}</h4>
                                            <span class="text-xs font-medium px-2 py-1 rounded-full {{ $shift['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $shift['status'] === 'confirmed' ? 'Dikonfirmasi' : 'Pending' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span class="font-medium">Shift {{ $shift['shift_type'] }}</span> ({{ $shift['time'] }}) • {{ $shift['unit'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $shift['notes'] }}</p>
                                    </div>
                                    <div class="ml-4">
                                        @if($shift['status'] === 'confirmed')
                                            <i data-lucide="check-circle" class="w-6 h-6 text-green-500"></i>
                                        @else
                                            <i data-lucide="clock" class="w-6 h-6 text-yellow-500"></i>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                            <button class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                                Lihat Semua Jadwal
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Overview -->
                <div class="animate-slide-up" style="animation-delay: 0.6s">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="calendar-range" class="w-5 h-5 text-indigo-500"></i>
                                Ringkasan Bulanan - {{ $currentMonth }}
                            </h3>
                            <div class="flex items-center space-x-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span class="text-gray-600 dark:text-gray-400">Pagi</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-gray-600 dark:text-gray-400">Siang</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                    <span class="text-gray-600 dark:text-gray-400">Malam</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                                    <span class="text-gray-600 dark:text-gray-400">Off</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Monthly calendar grid -->
                        <div class="space-y-2">
                            @foreach($monthlyOverview as $week)
                            <div class="grid grid-cols-7 gap-2">
                                @foreach($week as $day)
                                <div class="aspect-square bg-gray-50 dark:bg-gray-700 rounded-lg p-2 text-center relative transition-all hover:shadow-md {{ $day['is_today'] ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30' : '' }}">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white {{ $day['is_past'] ? 'text-gray-400' : '' }}">{{ $day['date'] }}</div>
                                    @if($day['shift_type'] !== 'Off')
                                        <div class="absolute bottom-1 left-1 right-1">
                                            <div class="w-full h-1 rounded-full {{ $day['shift_type'] === 'Pagi' ? 'bg-blue-500' : ($day['shift_type'] === 'Siang' ? 'bg-green-500' : 'bg-purple-500') }}"></div>
                                        </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Sidebar toggle functionality (same as dashboard)
        const openSidebar = document.getElementById('openSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        }
        
        openSidebar?.addEventListener('click', toggleSidebar);
        closeSidebar?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);
        
        // Close sidebar on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });
        
        // Add entrance animations with staggered delays
        const cards = document.querySelectorAll('.grid > div');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize schedule interactions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Jadwal Jaga page loaded successfully!');
            
            // Add click handlers for calendar days
            const calendarDays = document.querySelectorAll('.shift-indicator');
            calendarDays.forEach(day => {
                day.addEventListener('click', function() {
                    // Add selection logic here if needed
                    console.log('Calendar day clicked');
                });
            });
        });
    </script>
</body>
</html>