<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Presensi Harian - Dokterku Paramedis</title>
    
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
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                        'heartbeat': 'heartbeat 1.5s ease-in-out infinite',
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
        
        @keyframes heartbeat {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
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
        
        /* GPS tracking animation */
        .gps-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        /* Location indicator */
        .location-dot {
            position: relative;
        }
        
        .location-dot::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            transform: translate(-50%, -50%);
            animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
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
                
                <a href="/paramedis/presensi" class="flex items-center space-x-3 px-4 py-3 rounded-md bg-blue-600 text-white hover-glow transition-all duration-200">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                    <span class="font-medium">Presensi</span>
                </a>
                
                <a href="/paramedis/jaspel" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="banknote" class="w-5 h-5 group-hover:text-green-400"></i>
                    <span class="font-medium group-hover:text-green-400">Jaspel</span>
                </a>
                
                <a href="/paramedis/jadwal-jaga" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="calendar" class="w-5 h-5 group-hover:text-purple-400"></i>
                    <span class="font-medium group-hover:text-purple-400">Jadwal Jaga</span>
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
                        <i data-lucide="map-pin" class="w-4 h-4 text-blue-400"></i>
                        <span>Attendance System</span>
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
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Presensi Harian</h1>
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </header>
            
            <!-- Attendance Content -->
            <div class="p-4 lg:p-8 space-y-8 animate-fade-in">
                <!-- Header Section -->
                <div class="animate-slide-up">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-3">
                                <i data-lucide="clock" class="w-8 h-8 text-blue-500"></i>
                                Presensi Harian
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">{{ $user->name }} • {{ $currentDate }}</p>
                        </div>
                        
                        <!-- Real-time Clock -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                            <div class="text-center">
                                <div id="current-time" class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $currentTime }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Waktu Saat Ini</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-slide-up" style="animation-delay: 0.2s">
                    <!-- Monthly Attendance -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium mb-1">Kehadiran Bulan Ini</p>
                                <p class="text-3xl font-bold">{{ $attendanceStats['monthlyAttendance'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">hari hadir</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="calendar" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Hours -->
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-100 text-sm font-medium mb-1">Total Jam Kerja</p>
                                <p class="text-3xl font-bold">{{ $attendanceStats['totalHoursThisMonth'] }}</p>
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
                    
                    <!-- On Time Percentage -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium mb-1">Ketepatan Waktu</p>
                                <p class="text-3xl font-bold">{{ $attendanceStats['onTimePercentage'] }}%</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="zap" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">rata-rata tepat waktu</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="target" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overtime Hours -->
                    <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm font-medium mb-1">Jam Lembur</p>
                                <p class="text-3xl font-bold">{{ $attendanceStats['overtimeHours'] }}</p>
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
                
                <!-- Today's Attendance Status -->
                <div class="animate-slide-up" style="animation-delay: 0.3s">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="calendar-days" class="w-5 h-5 text-blue-500"></i>
                                Status Kehadiran Hari Ini
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $todayAttendance['day_name'] }}, {{ $todayAttendance['date'] }}</span>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Check-in Status -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Check In</h4>
                                        @if($todayAttendance['has_checked_in'])
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Jam masuk: <span class="font-medium text-green-600">{{ $todayAttendance['check_in_time'] }}</span></p>
                                            <div class="flex items-center mt-2">
                                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">✅ Sudah Check In</span>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Belum melakukan check in hari ini</p>
                                            <div class="flex items-center mt-2">
                                                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">⏳ Menunggu Check In</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="w-12 h-12 {{ $todayAttendance['has_checked_in'] ? 'bg-green-500' : 'bg-gray-400' }} rounded-full flex items-center justify-center">
                                        <i data-lucide="{{ $todayAttendance['has_checked_in'] ? 'check' : 'clock' }}" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Check-out Status -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-l-4 border-blue-500 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Check Out</h4>
                                        @if($todayAttendance['has_checked_out'])
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Jam pulang: <span class="font-medium text-blue-600">{{ $todayAttendance['check_out_time'] }}</span></p>
                                            <div class="flex items-center mt-2">
                                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">🏁 Sudah Check Out</span>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Belum melakukan check out</p>
                                            <div class="flex items-center mt-2">
                                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">⏰ Sedang Bekerja</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="w-12 h-12 {{ $todayAttendance['has_checked_out'] ? 'bg-blue-500' : 'bg-gray-400' }} rounded-full flex items-center justify-center">
                                        <i data-lucide="{{ $todayAttendance['has_checked_out'] ? 'log-out' : 'clock' }}" class="w-6 h-6 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Check-in/out Actions & Location -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 animate-slide-up" style="animation-delay: 0.4s">
                    <!-- Action Buttons -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-5 h-5 text-purple-500"></i>
                                Aksi Presensi
                            </h3>
                            <button id="detectLocationBtn" class="text-sm bg-purple-100 text-purple-800 px-3 py-1 rounded-full hover:bg-purple-200 transition-colors">
                                <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                                Deteksi GPS
                            </button>
                        </div>
                        
                        <!-- GPS Permission Alert -->
                        <div id="gpsPermissionAlert" class="mb-4 p-4 rounded-lg bg-blue-50 border border-blue-200">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                                <span class="font-medium text-blue-800">🚀 Meminta Permission GPS...</span>
                            </div>
                            <p class="text-sm text-blue-600">
                                📍 Silakan izinkan akses lokasi untuk melakukan presensi
                            </p>
                            <p class="text-xs text-blue-500 mt-1">
                                Browser akan meminta permission GPS untuk deteksi lokasi otomatis
                            </p>
                        </div>
                        
                        <!-- Location Status -->
                        <div id="locationStatus" class="mb-6 p-4 rounded-lg bg-gray-50 border border-gray-200" style="display: none;">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="location-dot w-3 h-3 bg-gray-500 rounded-full"></div>
                                <span class="font-medium text-gray-800">
                                    Status Lokasi: Menunggu deteksi GPS...
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">
                                📍 ---.------, ---.------
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Jarak dari klinik: --- meter • Akurasi: --- meter
                            </p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <button onclick="checkIn()" 
                                    class="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-bold py-4 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg {{ !$todayAttendance['has_checked_in'] && $geofenceStatus['is_valid'] ? '' : 'opacity-50 cursor-not-allowed' }}"
                                    {{ $todayAttendance['has_checked_in'] || !$geofenceStatus['is_valid'] ? 'disabled' : '' }}>
                                <div class="flex items-center justify-center gap-2">
                                    <i data-lucide="log-in" class="w-5 h-5"></i>
                                    <span>Check In</span>
                                </div>
                                <div class="text-xs opacity-80 mt-1">
                                    {{ !$todayAttendance['has_checked_in'] ? 'Masuk kerja' : 'Sudah check in' }}
                                </div>
                            </button>
                            
                            <button onclick="checkOut()" 
                                    class="bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-bold py-4 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg {{ $todayAttendance['has_checked_in'] && !$todayAttendance['has_checked_out'] && $geofenceStatus['is_valid'] ? '' : 'opacity-50 cursor-not-allowed' }}"
                                    {{ !$todayAttendance['has_checked_in'] || $todayAttendance['has_checked_out'] || !$geofenceStatus['is_valid'] ? 'disabled' : '' }}>
                                <div class="flex items-center justify-center gap-2">
                                    <i data-lucide="log-out" class="w-5 h-5"></i>
                                    <span>Check Out</span>
                                </div>
                                <div class="text-xs opacity-80 mt-1">
                                    {{ $todayAttendance['has_checked_in'] && !$todayAttendance['has_checked_out'] ? 'Pulang kerja' : (!$todayAttendance['has_checked_in'] ? 'Check in dulu' : 'Sudah check out') }}
                                </div>
                            </button>
                        </div>
                        
                        <!-- GPS Info -->
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>📍 {{ $workLocation['name'] }}</span>
                                <span>Radius: {{ $workLocation['radius'] }}m</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $workLocation['address'] }}</p>
                        </div>
                    </div>
                    
                    <!-- Mini Map Placeholder -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="map" class="w-5 h-5 text-green-500"></i>
                                Lokasi Saat Ini
                            </h3>
                            <a href="https://maps.google.com/maps?q={{ $currentLocation['latitude'] }},{{ $currentLocation['longitude'] }}" 
                               target="_blank" 
                               class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full hover:bg-blue-200 transition-colors">
                                <i data-lucide="external-link" class="w-4 h-4 inline mr-1"></i>
                                Buka Maps
                            </a>
                        </div>
                        
                        <!-- Static Map Placeholder -->
                        <div class="bg-gradient-to-br from-blue-100 to-green-100 rounded-lg h-64 flex items-center justify-center relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/20 to-green-400/20"></div>
                            <div class="text-center z-10">
                                <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-4 location-dot animate-heartbeat">
                                    <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
                                </div>
                                <p class="font-semibold text-gray-700">Lokasi Anda</p>
                                <p class="text-sm text-gray-600">{{ $currentLocation['latitude'] }}, {{ $currentLocation['longitude'] }}</p>
                                <p class="text-xs text-gray-500 mt-2">Akurasi: ±{{ $currentLocation['accuracy'] }} meter</p>
                            </div>
                            
                            <!-- Distance indicator -->
                            @if($geofenceStatus['is_valid'])
                            <div class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-medium">
                                ✅ Dalam Area
                            </div>
                            @else
                            <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-medium">
                                ❌ Di Luar Area
                            </div>
                            @endif
                        </div>
                        
                        <!-- Location Details -->
                        <div class="mt-4 space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Jarak ke Klinik:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($currentLocation['distance_from_clinic'] * 1000, 0) }}m</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Status GPS:</span>
                                <span class="font-medium text-green-600">{{ $currentLocation['accuracy'] }}m akurasi</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Alamat:</span>
                                <span class="font-medium text-gray-900 dark:text-white text-right">{{ $currentLocation['address'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance History -->
                <div class="animate-slide-up" style="animation-delay: 0.6s">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="history" class="w-5 h-5 text-indigo-500"></i>
                                Riwayat Presensi (10 Hari Terakhir)
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $attendanceHistory->count() }} entri</span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="py-3 px-4 text-left font-medium text-gray-700 dark:text-gray-300">Tanggal</th>
                                        <th class="py-3 px-4 text-left font-medium text-gray-700 dark:text-gray-300">Hari</th>
                                        <th class="py-3 px-4 text-center font-medium text-gray-700 dark:text-gray-300">Check In</th>
                                        <th class="py-3 px-4 text-center font-medium text-gray-700 dark:text-gray-300">Check Out</th>
                                        <th class="py-3 px-4 text-center font-medium text-gray-700 dark:text-gray-300">Jam Kerja</th>
                                        <th class="py-3 px-4 text-center font-medium text-gray-700 dark:text-gray-300">Status</th>
                                        <th class="py-3 px-4 text-center font-medium text-gray-700 dark:text-gray-300">Lembur</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    @foreach($attendanceHistory as $record)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $record['is_today'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                        <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">
                                            {{ $record['date'] }}
                                            @if($record['is_today'])
                                                <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded-full">Hari Ini</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $record['day_name'] }}</td>
                                        <td class="py-3 px-4 text-center font-medium text-gray-900 dark:text-white">{{ $record['check_in'] }}</td>
                                        <td class="py-3 px-4 text-center font-medium text-gray-900 dark:text-white">{{ $record['check_out'] }}</td>
                                        <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">{{ $record['work_hours'] }}</td>
                                        <td class="py-3 px-4 text-center">
                                            @if($record['status'] === 'on_time')
                                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">✅ Tepat Waktu</span>
                                            @else
                                                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">⏰ Terlambat</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($record['overtime'])
                                                <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2 py-1 rounded-full">{{ $record['overtime'] }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div class="text-center">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $attendanceStats['perfectAttendanceDays'] }}</div>
                                    <div class="text-gray-600 dark:text-gray-400">Hari Tepat Waktu</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $attendanceStats['averageCheckInTime'] }}</div>
                                    <div class="text-gray-600 dark:text-gray-400">Rata-rata Check In</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $attendanceStats['onTimePercentage'] }}%</div>
                                    <div class="text-gray-600 dark:text-gray-400">Tingkat Ketepatan</div>
                                </div>
                            </div>
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
        
        // Real-time clock update
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            
            const clockElement = document.getElementById('current-time');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }
        
        // Update clock every second
        setInterval(updateClock, 1000);
        
        // GPS Detection and Geolocation Variables
        let currentLatitude = null;
        let currentLongitude = null;
        let currentAccuracy = null;
        let locationDetected = false;
        let withinGeofence = false;
        let watchId = null;
        
        // Clinic coordinates (work location)
        const clinicLat = {{ $workLocation['latitude'] }};
        const clinicLng = {{ $workLocation['longitude'] }};
        const clinicRadius = {{ $workLocation['radius'] }}; // meters
        
        // Work location config (console logging disabled for production)
        
        // Real GPS Detection with Permission Request
        async function detectLocation() {
            const detectBtn = document.getElementById('detectLocationBtn');
            
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                showLocationError('❌ GPS tidak didukung di browser ini');
                return;
            }
            
            // Check HTTPS requirement
            if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                showLocationError('🔒 GPS memerlukan HTTPS. Gunakan localhost untuk testing.');
                return;
            }
            
            detectBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 inline mr-1 animate-spin"></i>Meminta Permission...';
            lucide.createIcons();
            
            try {
                // Request permission explicitly
                const permission = await navigator.permissions.query({name: 'geolocation'});
                // GPS Permission status logged (disabled for performance)
                
                if (permission.state === 'denied') {
                    showLocationError('❌ Permission GPS ditolak. Silakan aktifkan GPS di pengaturan browser.');
                    return;
                }
                
                detectBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 inline mr-1 animate-spin"></i>Mendeteksi GPS...';
                
                // Get current position with high accuracy
                const options = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                };
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        handleLocationSuccess(position);
                        detectBtn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i>GPS Aktif';
                        detectBtn.classList.remove('bg-purple-100', 'text-purple-800');
                        detectBtn.classList.add('bg-green-100', 'text-green-800');
                        lucide.createIcons();
                    },
                    (error) => {
                        handleLocationError(error);
                        detectBtn.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 inline mr-1"></i>GPS Error';
                        detectBtn.classList.remove('bg-purple-100', 'text-purple-800');
                        detectBtn.classList.add('bg-red-100', 'text-red-800');
                        lucide.createIcons();
                    },
                    options
                );
                
            } catch (error) {
                // Permission API error (logging disabled for performance)
                showLocationError('⚠️ Tidak dapat mengakses permission API. Mencoba deteksi langsung...');
                
                // Fallback: direct geolocation call
                navigator.geolocation.getCurrentPosition(
                    handleLocationSuccess,
                    handleLocationError,
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }
        }
        
        function handleLocationSuccess(position) {
            currentLatitude = position.coords.latitude;
            currentLongitude = position.coords.longitude;
            currentAccuracy = position.coords.accuracy;
            locationDetected = true;
            
            // GPS detected (console logging disabled for performance)
            
            // Calculate distance to clinic
            const distance = calculateDistance(currentLatitude, currentLongitude, clinicLat, clinicLng);
            withinGeofence = distance <= clinicRadius;
            
            // Update UI
            updateLocationDisplay(distance);
            updateActionButtons();
            
            // Show success notification
            const accuracyText = currentAccuracy ? ` (±${Math.round(currentAccuracy)}m)` : '';
            const distanceText = Math.round(distance);
            const statusText = withinGeofence ? '✅ Dalam radius klinik' : '❌ Di luar radius klinik';
            
            alert(`📍 GPS Berhasil Terdeteksi${accuracyText}\n\nLokasi: ${currentLatitude.toFixed(6)}, ${currentLongitude.toFixed(6)}\nJarak ke klinik: ${distanceText}m\nStatus: ${statusText}`);
        }
        
        function handleLocationError(error) {
            let errorMessage = '❌ Gagal mendeteksi lokasi GPS';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = '❌ Permission GPS ditolak.\nSilakan aktifkan GPS dan izinkan akses lokasi di browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = '📡 GPS tidak tersedia.\nPastikan GPS aktif dan anda berada di area terbuka.';
                    break;
                case error.TIMEOUT:
                    errorMessage = '⏱️ Timeout GPS.\nDeteksi lokasi memakan waktu terlalu lama, coba lagi.';
                    break;
                default:
                    errorMessage = `❌ Error GPS: ${error.message}`;
                    break;
            }
            
            // GPS Error logged (console logging disabled for performance)
            showLocationError(errorMessage);
        }
        
        function showLocationError(message) {
            alert(message);
            
            // Update UI to show error state
            const permissionAlert = document.getElementById('gpsPermissionAlert');
            if (permissionAlert) {
                permissionAlert.innerHTML = `
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="font-medium text-red-800">❌ GPS Error</span>
                    </div>
                    <p class="text-sm text-red-600">
                        Gagal mendeteksi lokasi. Silakan coba lagi atau aktifkan GPS.
                    </p>
                    <p class="text-xs text-red-500 mt-1">
                        Pastikan browser memiliki permission untuk mengakses lokasi
                    </p>
                `;
                permissionAlert.className = 'mb-4 p-4 rounded-lg bg-red-50 border border-red-200';
                permissionAlert.style.display = 'block';
            }
            
            // Reset location detection
            locationDetected = false;
            withinGeofence = false;
            updateActionButtons();
        }
        
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c; // Distance in meters
        }
        
        function updateLocationDisplay(distance) {
            // Hide GPS permission alert
            const permissionAlert = document.getElementById('gpsPermissionAlert');
            if (permissionAlert) {
                permissionAlert.style.display = 'none';
            }
            
            // Show and update location status
            const locationStatus = document.getElementById('locationStatus');
            if (locationStatus) {
                locationStatus.style.display = 'block';
                
                const locationDiv = locationStatus.querySelector('.location-dot').parentElement;
                const statusText = locationDiv.querySelector('span');
                const coordinateText = locationDiv.nextElementSibling;
                const distanceText = coordinateText.nextElementSibling;
                
                if (withinGeofence) {
                    statusText.textContent = 'Status Lokasi: Dalam zona klinik';
                    statusText.className = 'font-medium text-green-800';
                    document.querySelector('.location-dot').className = 'location-dot w-3 h-3 bg-green-500 rounded-full';
                    locationStatus.className = 'mb-6 p-4 rounded-lg bg-green-50 border border-green-200';
                } else {
                    statusText.textContent = 'Status Lokasi: Di luar zona yang diizinkan';
                    statusText.className = 'font-medium text-red-800';
                    document.querySelector('.location-dot').className = 'location-dot w-3 h-3 bg-red-500 rounded-full';
                    locationStatus.className = 'mb-6 p-4 rounded-lg bg-red-50 border border-red-200';
                }
                
                coordinateText.textContent = `📍 ${currentLatitude.toFixed(6)}, ${currentLongitude.toFixed(6)}`;
                coordinateText.className = withinGeofence ? 'text-sm text-green-600' : 'text-sm text-red-600';
                
                distanceText.textContent = `Jarak dari klinik: ${Math.round(distance)}m • Akurasi: ±${Math.round(currentAccuracy)}m`;
                distanceText.className = withinGeofence ? 'text-xs text-green-500 mt-1' : 'text-xs text-red-500 mt-1';
            }
        }
        
        function updateActionButtons() {
            const checkInBtn = document.querySelector('button[onclick="checkIn()"]');
            const checkOutBtn = document.querySelector('button[onclick="checkOut()"]');
            
            if (checkInBtn) {
                if (locationDetected && withinGeofence) {
                    checkInBtn.disabled = false;
                    checkInBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    checkInBtn.disabled = true;
                    checkInBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
            
            if (checkOutBtn) {
                if (locationDetected && withinGeofence) {
                    checkOutBtn.disabled = false;
                    checkOutBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    checkOutBtn.disabled = true;
                    checkOutBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        }
        
        // Real attendance functions with GPS validation
        function checkIn() {
            if (!locationDetected) {
                alert('❌ Lokasi belum terdeteksi!\nSilakan deteksi GPS terlebih dahulu.');
                return;
            }
            
            if (!withinGeofence) {
                alert('❌ Anda berada di luar radius klinik!\nMendekatlah ke area klinik untuk melakukan check-in.');
                return;
            }
            
            const currentTime = new Date().toLocaleTimeString('id-ID');
            alert(`🎉 Check In berhasil!\n\nWaktu: ${currentTime}\nLokasi: ${currentLatitude.toFixed(6)}, ${currentLongitude.toFixed(6)}\nJarak: ${Math.round(calculateDistance(currentLatitude, currentLongitude, clinicLat, clinicLng))}m dari klinik\n\nSelamat bekerja! 💪`);
        }
        
        function checkOut() {
            if (!locationDetected) {
                alert('❌ Lokasi belum terdeteksi!\nSilakan deteksi GPS terlebih dahulu.');
                return;
            }
            
            if (!withinGeofence) {
                alert('❌ Anda berada di luar radius klinik!\nMendekatlah ke area klinik untuk melakukan check-out.');
                return;
            }
            
            const currentTime = new Date().toLocaleTimeString('id-ID');
            alert(`👋 Check Out berhasil!\n\nWaktu: ${currentTime}\nLokasi: ${currentLatitude.toFixed(6)}, ${currentLongitude.toFixed(6)}\nJarak: ${Math.round(calculateDistance(currentLatitude, currentLongitude, clinicLat, clinicLng))}m dari klinik\n\nTerima kasih atas kerja kerasnya hari ini! 🙏`);
        }
        
        // Auto-detect location on page load
        function autoDetectOnLoad() {
            // Auto-detecting GPS on page load (logging disabled for performance)
            detectLocation();
        }
        
        // GPS Detection button event
        document.getElementById('detectLocationBtn')?.addEventListener('click', detectLocation);
        
        // Add entrance animations with staggered delays
        const cards = document.querySelectorAll('.grid > div');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize attendance interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Presensi page loaded (logging disabled for performance)
            updateClock(); // Initial clock update
            
            // Auto-detect GPS on page load (with slight delay)
            setTimeout(() => {
                autoDetectOnLoad();
            }, 1000);
        });
    </script>
</body>
</html>