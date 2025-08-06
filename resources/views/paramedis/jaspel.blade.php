<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jaspel - Dokterku Paramedis</title>
    
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
                        'count-up': 'countUp 2s ease-out',
                    }
                }
            }
        }
    </script>
    
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        @keyframes countUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
        
        /* Card hover effects */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Number animation */
        .animate-number {
            animation: countUp 2s ease-out;
        }
        
        /* Table row hover */
        .table-row:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.02) 100%);
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased h-full overflow-x-hidden">
    <div class="flex min-h-screen">
        <!-- Dark Elegant Sidebar (Same as Dashboard) -->
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
                        {{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">{{ auth()->user()->name ?? 'Paramedis' }}</h3>
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
                
                <a href="/paramedis/jaspel" class="flex items-center space-x-3 px-4 py-3 rounded-md bg-blue-600 text-white hover-glow transition-all duration-200">
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                    <span class="font-medium">Jaspel</span>
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
                        <i data-lucide="banknote" class="w-4 h-4 text-green-400"></i>
                        <span>Jaspel Management</span>
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
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Jaspel</h1>
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}
                    </div>
                </div>
            </header>
            
            <!-- Jaspel Content -->
            <div class="p-4 lg:p-8 space-y-8 animate-fade-in">
                <!-- Page Header -->
                <div class="animate-slide-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                                Jaspel Dashboard 💰
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">Kelola dan pantau pendapatan jasa pelayanan Anda</p>
                        </div>
                        <div class="hidden md:flex items-center space-x-4">
                            <div class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-medium">
                                <i data-lucide="trending-up" class="w-4 h-4 inline mr-1"></i>
                                Target 83%
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Jaspel Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-slide-up" style="animation-delay: 0.2s">
                    <!-- Monthly Jaspel Card -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Jaspel Bulan Ini</h3>
                                <p class="text-3xl font-bold animate-number" data-value="8720000">Rp 0</p>
                                <div class="flex items-center mt-2 text-blue-100">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">{{ now()->format('F Y') }}</span>
                                </div>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-full p-4">
                                <i data-lucide="banknote" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Weekly Jaspel Card -->
                    <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-gray-900 rounded-xl p-6 shadow-lg card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Jaspel Minggu Ini</h3>
                                <p class="text-3xl font-bold animate-number" data-value="1560000">Rp 0</p>
                                <div class="flex items-center mt-2 text-yellow-800">
                                    <i data-lucide="clock" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">7 hari terakhir</span>
                                </div>
                            </div>
                            <div class="bg-yellow-600 bg-opacity-20 rounded-full p-4">
                                <i data-lucide="wallet" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Jaspel Card -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg card-hover md:col-span-2 lg:col-span-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Jaspel Hari Ini</h3>
                                <p class="text-3xl font-bold animate-number" data-value="425000">Rp 0</p>
                                <div class="flex items-center mt-2 text-green-100">
                                    <i data-lucide="zap" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">{{ now()->format('d M Y') }}</span>
                                </div>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-full p-4">
                                <i data-lucide="trending-up" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Chart Visualization -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-slide-up" style="animation-delay: 0.4s">
                    <!-- Monthly Trend Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Tren Jaspel Bulanan</h4>
                            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span>Pendapatan</span>
                            </div>
                        </div>
                        <div class="relative h-80">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Category Distribution Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Distribusi Tindakan</h4>
                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span>Umum</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span>Khusus</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span>Darurat</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative h-80">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Jaspel History Table -->
                <div class="animate-slide-up" style="animation-delay: 0.6s">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">Riwayat Jaspel Terbaru</h4>
                                <div class="flex items-center space-x-2">
                                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                        <i data-lucide="download" class="w-4 h-4 inline mr-1"></i>
                                        Export
                                    </button>
                                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                        <i data-lucide="filter" class="w-4 h-4 inline mr-1"></i>
                                        Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Tindakan
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Kategori
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Nominal
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr class="table-row hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            14 Jul 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            Pemeriksaan Umum
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Umum
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-300">
                                            Rp 75.000
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i data-lucide="check" class="w-3 h-3 mr-1"></i>
                                                Lunas
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="table-row hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            14 Jul 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            Perawatan Luka
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Khusus
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-300">
                                            Rp 120.000
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i data-lucide="check" class="w-3 h-3 mr-1"></i>
                                                Lunas
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="table-row hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            13 Jul 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            Injeksi Vitamin
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Umum
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-300">
                                            Rp 85.000
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                                Pending
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="table-row hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            13 Jul 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            Konsultasi Gizi
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Khusus
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-300">
                                            Rp 65.000
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i data-lucide="check" class="w-3 h-3 mr-1"></i>
                                                Lunas
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="table-row hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            12 Jul 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                            Penanganan Darurat
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Darurat
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-300">
                                            Rp 350.000
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i data-lucide="check" class="w-3 h-3 mr-1"></i>
                                                Lunas
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    Menampilkan <span class="font-medium">1</span> sampai <span class="font-medium">5</span> dari <span class="font-medium">47</span> hasil
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        Previous
                                    </button>
                                    <button class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 transition-colors">
                                        1
                                    </button>
                                    <button class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        2
                                    </button>
                                    <button class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="animate-bounce-in" style="animation-delay: 0.8s">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Aksi Cepat</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 group text-left">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="plus" class="w-6 h-6 text-white"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white text-center">Tambah Jaspel</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mt-1">Input tindakan baru</p>
                        </button>
                        
                        <button class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 group text-left">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white text-center">Laporan</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mt-1">Generate laporan</p>
                        </button>
                        
                        <button class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-300 group text-left">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="target" class="w-6 h-6 text-white"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white text-center">Target</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mt-1">Atur target bulanan</p>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Sidebar toggle functionality
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
        
        // Animate numbers
        function animateNumber(element) {
            const target = parseInt(element.getAttribute('data-value'));
            const duration = 2000;
            const start = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function
                const easeOutExpo = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const current = Math.floor(target * easeOutExpo);
                
                element.textContent = 'Rp ' + current.toLocaleString('id-ID');
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }
        
        // Start number animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const numberElements = document.querySelectorAll('.animate-number');
                numberElements.forEach(animateNumber);
            }, 500);
        });
        
        // Chart.js configurations
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        };
        
        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Jaspel',
                    data: [6500000, 7200000, 6800000, 8100000, 7900000, 8500000, 8720000],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        },
                        grid: {
                            color: 'rgba(156, 163, 175, 0.2)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(156, 163, 175, 0.2)'
                        }
                    }
                }
            }
        });
        
        // Category Distribution Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pemeriksaan Umum', 'Tindakan Khusus', 'Penanganan Darurat'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: [
                        'rgb(59, 130, 246)',   // Blue
                        'rgb(16, 185, 129)',   // Green
                        'rgb(245, 158, 11)'    // Yellow
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        // Add staggered animation delays
        const cards = document.querySelectorAll('.card-hover');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
        
        console.log('Jaspel Dashboard loaded successfully!');
    </script>
</body>
</html>