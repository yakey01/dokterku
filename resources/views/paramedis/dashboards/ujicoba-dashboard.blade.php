<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>UjiCoba Dashboard - Dokterku</title>
    
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
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
                    }
                }
            }
        }
    </script>
    
    <!-- Using built-in system fonts -->
    
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
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased h-full overflow-x-hidden">
    <div class="flex min-h-screen">
        <!-- Dark Elegant Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-80 bg-gray-900 text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-20 px-6 border-b border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="heart-pulse" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold gradient-text">Dokterku</h1>
                        <p class="text-sm text-gray-400">UjiCoba Dashboard</p>
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
                <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-md bg-blue-600 text-white hover-glow transition-all duration-200">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                
                <a href="/paramedis/presensi" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
                    <i data-lucide="clock" class="w-5 h-5 group-hover:text-blue-400"></i>
                    <span class="font-medium group-hover:text-blue-400">Presensi</span>
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
                        <i data-lucide="zap" class="w-4 h-4 text-yellow-400"></i>
                        <span>Experimental Dashboard</span>
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
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">UjiCoba Dashboard</h1>
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="p-4 lg:p-8 space-y-8 animate-fade-in">
                <!-- Welcome Section -->
                <div class="animate-slide-up">
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 18 ? 'Siang' : 'Malam') }}! 👋
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">{{ $user->name }}</p>
                </div>
                
                <!-- Stats Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 animate-slide-up" style="animation-delay: 0.2s">
                    <!-- Total Jaspel Card -->
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-100 text-sm font-medium mb-1">Total Jaspel Bulan Ini</p>
                                <p class="text-3xl font-bold">Rp {{ number_format($dashboardStats['totalJaspel'], 0, ',', '.') }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">+{{ $dashboardStats['completionRate'] }}% dari target</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="banknote" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Weekly Attendance Card -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium mb-1">Kehadiran Mingguan</p>
                                <p class="text-3xl font-bold">{{ $dashboardStats['weeklyAttendance'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">hari hadir</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="clock" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Shifts Card -->
                    <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-100 text-sm font-medium mb-1">Shift Aktif</p>
                                <p class="text-3xl font-bold">{{ $dashboardStats['activeShifts'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">jadwal tersedia</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="calendar" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Tindakan Card -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg hover-glow transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium mb-1">Total Tindakan Medis</p>
                                <p class="text-3xl font-bold">{{ $dashboardStats['totalTindakan'] }}</p>
                                <div class="flex items-center mt-2">
                                    <i data-lucide="activity" class="w-4 h-4 mr-1"></i>
                                    <span class="text-sm">tindakan selesai</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 animate-slide-up" style="animation-delay: 0.4s">
                    <!-- Jaspel Trend Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Tren Jaspel 30 Hari Terakhir</h3>
                            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span>Earnings</span>
                            </div>
                        </div>
                        <div class="relative h-80">
                            <canvas id="jaspenTrendChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Shift Comparison Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Perbandingan Shift Pagi vs Malam</h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span>Pagi</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span>Siang</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                    <span>Malam</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative h-80">
                            <canvas id="shiftComparisonChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="animate-bounce-in" style="animation-delay: 0.6s">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Aksi Cepat</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="/paramedis/presensi" class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-blue-500 transition-all duration-300 group block">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="clock-in" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="font-semibold text-gray-900 dark:text-white">Presensi</p>
                        </a>
                        
                        <a href="/paramedis/jaspel" class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-green-500 transition-all duration-300 group block">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="banknote" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="font-semibold text-gray-900 dark:text-white">Jaspel</p>
                        </a>
                        
                        <a href="/paramedis/jadwal-jaga" class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-purple-500 transition-all duration-300 group block">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="calendar-plus" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="font-semibold text-gray-900 dark:text-white">Jadwal</p>
                        </a>
                        
                        <button class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-orange-500 transition-all duration-300 group">
                            <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="clipboard-list" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="font-semibold text-gray-900 dark:text-white">Tindakan</p>
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
        
        // Chart.js configurations
        const chartData = @json($chartData);
        
        // Jaspel Trend Chart
        const jaspenCtx = document.getElementById('jaspenTrendChart').getContext('2d');
        new Chart(jaspenCtx, {
            type: 'line',
            data: {
                labels: chartData.jaspenTrend.labels,
                datasets: [{
                    label: 'Jaspel (Rp)',
                    data: chartData.jaspenTrend.data,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000) + 'K';
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
                },
                elements: {
                    point: {
                        hoverBackgroundColor: 'rgb(59, 130, 246)'
                    }
                }
            }
        });
        
        // Shift Comparison Chart
        const shiftCtx = document.getElementById('shiftComparisonChart').getContext('2d');
        new Chart(shiftCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.shiftComparison.labels,
                datasets: [{
                    data: chartData.shiftComparison.data,
                    backgroundColor: [
                        'rgb(234, 179, 8)',  // Yellow for Pagi
                        'rgb(59, 130, 246)',  // Blue for Siang
                        'rgb(147, 51, 234)'   // Purple for Malam
                    ],
                    borderColor: [
                        'rgb(234, 179, 8)',
                        'rgb(59, 130, 246)',
                        'rgb(147, 51, 234)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 10,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
        
        // Add entrance animations with staggered delays
        const cards = document.querySelectorAll('.grid > div');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Add smooth scrolling for better UX
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize tooltips or additional interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add any additional initialization here
            console.log('UjiCoba Dashboard loaded successfully!');
        });
    </script>
</body>
</html>