<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm overflow-hidden">
        <!-- Dashboard Header -->
        <div class="bg-white dark:bg-gray-900 px-8 py-6 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1 dashboard-title">
                        Petugas Dashboard
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Selamat malam, Fitri. Kelola data pasien dengan efisien dan professional.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <x-filament::button
                        wire:click="refreshData"
                        color="gray"
                        size="sm"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 mr-2" />
                        Refresh Data
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        size="sm"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        <x-filament::icon icon="heroicon-o-squares-2x2" class="w-4 h-4" />
                    </x-filament::button>
                </div>
            </div>
        </div>

        @if($error)
            <div class="flex items-center justify-center p-8">
                <div class="text-center">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="w-12 h-12 mx-auto text-danger-500 mb-4"
                    />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ $message }}
                    </h3>
                    <x-filament::button
                        wire:click="refreshData"
                        color="primary"
                        size="sm"
                    >
                        🔄 Coba Lagi
                    </x-filament::button>
                </div>
            </div>
        @else
        <!-- Main Dashboard Content -->
        <div class="px-8 py-6">
            @php
                $kpiData = $this->getKpiData();
                $quickStats = $this->getQuickStats();
                $performanceData = $this->getPerformanceData();
            @endphp
            
            <!-- World-Class Glass Morphism KPI Cards dengan Desain Premium -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Pasien Hari Ini Card -->
                <div class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-2xl blur-xl opacity-25 group-hover:opacity-40 transition-opacity duration-500"></div>
                    <div class="relative bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 rounded-2xl p-6 shadow-2xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl shadow-lg">
                                <x-filament::icon icon="heroicon-o-users" class="w-7 h-7 text-white" />
                            </div>
                            <div class="flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">+25%</span>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pasien Hari Ini</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $kpiData['pasien']['today'] ?? 15 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span class="inline-flex items-center">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="w-3 h-3 mr-1 text-emerald-500" />
                                    +25% bulan ini
                                </span>
                            </p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-1.5 rounded-full transition-all duration-1000" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tindakan Selesai Card -->
                <div class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl blur-xl opacity-25 group-hover:opacity-40 transition-opacity duration-500"></div>
                    <div class="relative bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 rounded-2xl p-6 shadow-2xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl shadow-lg">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-7 h-7 text-white" />
                            </div>
                            <div class="flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">+27.8%</span>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tindakan Selesai</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $kpiData['tindakan']['completed'] ?? 23 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span class="inline-flex items-center">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="w-3 h-3 mr-1 text-emerald-500" />
                                    +27.8% bulan ini
                                </span>
                            </p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-1.5 rounded-full transition-all duration-1000" style="width: 78%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendapatan Hari Ini Card -->
                <div class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl blur-xl opacity-25 group-hover:opacity-40 transition-opacity duration-500"></div>
                    <div class="relative bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 rounded-2xl p-6 shadow-2xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-lg">
                                <x-filament::icon icon="heroicon-o-chart-bar-square" class="w-7 h-7 text-white" />
                            </div>
                            <div class="flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">+31%</span>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendapatan Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($kpiData['pendapatan']['today'] ?? 2750000, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span class="inline-flex items-center">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="w-3 h-3 mr-1 text-emerald-500" />
                                    +31% bulan ini
                                </span>
                            </p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-1.5 rounded-full transition-all duration-1000" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section - React Component -->
            <div id="petugas-dashboard-charts" 
                 data-charts='@json([
                     "patientCategories" => [
                         ["name" => "Umum", "value" => 45, "color" => "#3B82F6"],
                         ["name" => "BPJS", "value" => 35, "color" => "#10B981"],
                         ["name" => "Asuransi", "value" => 20, "color" => "#F59E0B"]
                     ],
                     "procedureTypes" => [
                         ["name" => "Pemeriksaan", "value" => 35, "color" => "#8B5CF6"],
                         ["name" => "Konsultasi", "value" => 25, "color" => "#EC4899"],
                         ["name" => "Tindakan", "value" => 20, "color" => "#06B6D4"],
                         ["name" => "Laboratorium", "value" => 15, "color" => "#14B8A6"],
                         ["name" => "Radiologi", "value" => 5, "color" => "#F97316"]
                     ]
                 ])'>
                <!-- React chart components will be rendered here -->
                <div class="flex items-center justify-center p-8">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4"></div>
                        <p class="text-gray-600 dark:text-gray-400">Loading charts...</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 dashboard-card metric-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 mb-1">
                            {{ $performanceData['metrics']['efficiency'] ?? '92' }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Efisiensi Kerja
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 dashboard-card metric-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 mb-1">
                            {{ $performanceData['metrics']['satisfaction'] ?? '88' }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Kepuasan Pasien
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 dashboard-card metric-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 mb-1">
                            {{ $performanceData['metrics']['response_time'] ?? '4.2' }}min
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Waktu Respons
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 dashboard-card metric-card">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 mb-1">
                            {{ $performanceData['metrics']['quality'] ?? '95' }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Kualitas Pelayanan
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/petugas-glass-morphism-cards.css') }}">
    <style>
        /* World-Class Glass Morphism Effects */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
            }
            50% { 
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.8);
            }
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glass Morphism Card Base */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .dark .glass-card {
            background: rgba(17, 24, 39, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        /* Animated Gradient Backgrounds */
        .gradient-animated {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient-shift 15s ease infinite;
        }
        
        /* Premium Shadow Effects */
        .shadow-3xl {
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3);
        }
        
        /* Professional Dashboard Enhancements */
        .dashboard-card {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        
        .kpi-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .dark .kpi-card {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(71, 85, 105, 0.3);
        }
        
        .performance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .metric-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .metric-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(71, 85, 105, 0.3);
        }
        
        /* Animation for charts */
        .chart-container {
            position: relative;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Growth indicator styling */
        .growth-indicator {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }
        
        /* Professional typography */
        .dashboard-title {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        /* Status indicator */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Chart card specific styling */
        .chart-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .chart-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(71, 85, 105, 0.3);
        }
        
        /* Legend item styling */
        .legend-item {
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(248, 250, 252, 0.5);
            border: 1px solid rgba(226, 232, 240, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .legend-item:hover {
            background: rgba(248, 250, 252, 0.8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .dark .legend-item {
            background: rgba(51, 65, 85, 0.3);
            border: 1px solid rgba(71, 85, 105, 0.3);
        }
        
        .dark .legend-item:hover {
            background: rgba(51, 65, 85, 0.5);
        }
    </style>
    @endpush
    
    {{-- Load React Charts via Vite --}}
    @vite('resources/js/petugas-dashboard-app.tsx')
</x-filament-widgets::widget>