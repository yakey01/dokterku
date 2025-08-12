{{-- World-Class Dashboard Styles and Scripts - Loaded via Panel Configuration --}}
{{-- Assets are loaded through PetugasPanelProvider viteTheme for better compatibility --}}

<x-filament-widgets::widget>
    <div class="world-class-dashboard premium-dashboard-container space-y-8">
            <!-- Enhanced Welcome Header -->
            <div class="premium-welcome-header">
                <div class="flex items-center justify-between">
                    <div class="space-y-3">
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 bg-clip-text text-transparent tracking-tight leading-tight">
                            Selamat Datang, {{ $this->getViewData()['user_name'] }}!
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-300 font-semibold">
                            Dashboard Petugas • {{ now()->format('l, d F Y') }}
                        </p>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span class="flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Real-time Data</span>
                            </span>
                            <span class="flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Sistem Aktif</span>
                            </span>
                        </div>
                    </div>
                    <div class="premium-time-badge">
                        <div class="flex items-center space-x-3 px-6 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200 dark:border-blue-700 backdrop-blur-sm">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-right">
                                <div class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ now()->format('H:i') }}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-400">{{ now()->format('d M') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- World-Class KPI Grid with Enhanced Spacing -->
            <div class="world-class-kpi-grid">
                @foreach($this->getViewData()['stats'] as $stat)
                    <div class="world-class-kpi-card group cursor-pointer" 
                         data-category="{{ strtolower(str_replace(' ', '', $stat['title'])) }}"
                         tabindex="0" 
                         role="button" 
                         aria-label="KPI {{ $stat['title'] }}: {{ $stat['value'] }}">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <p class="world-class-kpi-title">
                                    {{ $stat['title'] }}
                                </p>
                                <p class="world-class-kpi-description">
                                    {{ $stat['description'] }}
                                </p>
                            </div>
                            <div class="premium-stat-icon flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-{{ $stat['color'] }}-500 to-{{ $stat['color'] }}-600 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-lg shadow-{{ $stat['color'] }}-500/30">
                                @switch($stat['icon'])
                                    @case('users')
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                        @break
                                    @case('currency-dollar')
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        @break
                                    @case('clipboard-document-list')
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        @break
                                    @case('banknotes')
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        @break
                                @endswitch
                            </div>
                        </div>

                        <!-- Enhanced KPI Value -->
                        <div class="flex-1 flex flex-col justify-center">
                            <div class="world-class-kpi-value" data-animate="counter">
                                {{ $stat['value'] }}
                            </div>
                            
                            <!-- Enhanced Color-Coded Trend Indicators -->
                            <div class="flex items-center justify-between mt-3">
                                @if($stat['trend_direction'] === 'up')
                                    <div class="world-class-trend-indicator trend-up">
                                        <svg class="world-class-trend-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                        <span>+{{ number_format($stat['trend'], 1) }}%</span>
                                    </div>
                                @elseif($stat['trend_direction'] === 'down')
                                    <div class="world-class-trend-indicator trend-down">
                                        <svg class="world-class-trend-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                        </svg>
                                        <span>-{{ number_format($stat['trend'], 1) }}%</span>
                                    </div>
                                @else
                                    <div class="world-class-trend-indicator trend-neutral">
                                        <svg class="world-class-trend-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"></path>
                                        </svg>
                                        <span>Stabil</span>
                                    </div>
                                @endif
                                <span class="text-xs text-gray-400 font-medium">vs kemarin</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- World-Class Charts Section -->
            <div class="world-class-charts-grid">
                <!-- Weekly Trend Chart -->
                <div class="world-class-chart-container">
                    <div class="mb-6">
                        <h3 class="world-class-chart-title">Trend Mingguan</h3>
                        <p class="world-class-chart-subtitle">Jumlah pasien & tindakan per hari - 7 hari terakhir</p>
                        
                        <!-- Chart Filter Options -->
                        <div class="flex items-center space-x-3 mt-4">
                            <button class="px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">7 Hari</button>
                            <button class="px-3 py-1 text-xs font-semibold text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">30 Hari</button>
                            <button class="px-3 py-1 text-xs font-semibold text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">Custom</button>
                        </div>
                    </div>
                    
                    <!-- Placeholder for Chart -->
                    <div class="h-64 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl flex items-center justify-center border-2 border-dashed border-blue-200">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-blue-600 font-medium">Grafik Trend Mingguan</p>
                            <p class="text-blue-500 text-sm">Data interaktif akan tampil di sini</p>
                        </div>
                    </div>
                </div>

                <!-- Category Performance Pie Chart -->
                <div class="world-class-chart-container">
                    <div class="mb-6">
                        <h3 class="world-class-chart-title">Kategori Layanan</h3>
                        <p class="world-class-chart-subtitle">Distribusi tindakan berdasarkan kategori</p>
                    </div>
                    
                    <!-- Placeholder for Pie Chart -->
                    <div class="h-64 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl flex items-center justify-center border-2 border-dashed border-emerald-200">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-emerald-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                            </svg>
                            <p class="text-emerald-600 font-medium">Pie Chart Kategori</p>
                            <p class="text-emerald-500 text-sm">Visualisasi kategori layanan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Info Cards with Activity Preview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Validation Summary with Enhanced Status -->
                <div class="world-class-chart-container">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="world-class-chart-title">Status Validasi</h3>
                            <p class="world-class-chart-subtitle">Ringkasan persetujuan hari ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="space-y-5">
                        <!-- Pending Validations -->
                        <div class="flex items-center justify-between p-4 bg-orange-50 rounded-xl border border-orange-200">
                            <span class="world-class-kpi-title text-orange-700">Pending Validasi</span>
                            <span class="world-class-kpi-value text-2xl text-orange-600">{{ $this->getViewData()['validation_summary']['pending_validations'] }}</span>
                        </div>
                        
                        <!-- Approval Rate with Progress -->
                        <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="world-class-kpi-title text-green-700">Approval Rate</span>
                                <div class="world-class-status-badge status-excellent">
                                    {{ $this->getViewData()['validation_summary']['approval_rate'] }}%
                                </div>
                            </div>
                            <div class="w-full bg-green-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full" style="width: {{ $this->getViewData()['validation_summary']['approval_rate'] }}%"></div>
                            </div>
                        </div>
                        
                        <!-- Approved Today -->
                        <div class="flex items-center justify-between p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                            <span class="world-class-kpi-title text-emerald-700">Disetujui Hari Ini</span>
                            <span class="world-class-kpi-value text-2xl text-emerald-600">{{ $this->getViewData()['validation_summary']['approved_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Performance Metrics -->
                <div class="world-class-chart-container">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="world-class-chart-title">Performance Score</h3>
                            <p class="world-class-chart-subtitle">Efisiensi & kepuasan pasien</p>
                        </div>
                        <div class="world-class-status-badge status-excellent">
                            Excellent
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Efficiency Score -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="world-class-kpi-title text-purple-700">Efisiensi Kerja</span>
                                <span class="text-2xl font-bold text-purple-600">{{ number_format($this->getViewData()['performance_metrics']['efficiency_score'] ?? 87.5, 1) }}%</span>
                            </div>
                            <div class="w-full bg-purple-100 rounded-full h-3">
                                <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-3 rounded-full transition-all duration-1000" style="width: {{ $this->getViewData()['performance_metrics']['efficiency_score'] ?? 87.5 }}%"></div>
                            </div>
                        </div>
                        
                        <!-- Patient Satisfaction -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="world-class-kpi-title text-pink-700">Kepuasan Pasien</span>
                                <span class="text-2xl font-bold text-pink-600">{{ number_format($this->getViewData()['performance_metrics']['patient_satisfaction'] ?? 92.3, 1) }}%</span>
                            </div>
                            <div class="w-full bg-pink-100 rounded-full h-3">
                                <div class="bg-gradient-to-r from-pink-400 to-pink-600 h-3 rounded-full transition-all duration-1000" style="width: {{ $this->getViewData()['performance_metrics']['patient_satisfaction'] ?? 92.3 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Preview -->
                <div class="world-class-chart-container">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="world-class-chart-title">Aktivitas Terkini</h3>
                            <p class="world-class-chart-subtitle">3 aktivitas terakhir hari ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Activity Items -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4 p-3 bg-blue-50 rounded-xl border border-blue-200">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-blue-700">Pasien baru terdaftar</p>
                                <p class="text-xs text-blue-600">{{ now()->subMinutes(15)->format('H:i') }} - 2 pasien</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4 p-3 bg-green-50 rounded-xl border border-green-200">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-green-700">Pembayaran diverifikasi</p>
                                <p class="text-xs text-green-600">{{ now()->subMinutes(32)->format('H:i') }} - Rp 150.000</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4 p-3 bg-amber-50 rounded-xl border border-amber-200">
                            <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-amber-700">Laporan bulanan siap</p>
                                <p class="text-xs text-amber-600">{{ now()->subHour(1)->format('H:i') }} - Export completed</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- View All Button -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <button class="w-full text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors flex items-center justify-center space-x-2">
                            <span>Lihat Semua Aktivitas</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
    </div>
</x-filament-widgets::widget>

<style>
/* Premium Dashboard Custom Styles */
/* Using built-in system fonts */

.premium-dashboard-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-feature-settings: 'cv01', 'cv03', 'cv04', 'cv11';
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
    padding: 1.5rem;
    background: transparent;
}

/* Override default Filament widget styling */
.fi-wi-premium-dashboard-widget {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.premium-welcome-header {
    padding: 1.5rem;
    border-radius: 1.5rem;
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.9) 0%, 
        rgba(249, 250, 251, 0.8) 100%);
    border: 1px solid rgba(245, 158, 11, 0.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 32px rgba(245, 158, 11, 0.1);
}

.dark .premium-welcome-header {
    background: linear-gradient(135deg, 
        rgba(31, 41, 55, 0.9) 0%, 
        rgba(17, 24, 39, 0.8) 100%);
    border-color: rgba(245, 158, 11, 0.2);
}

.premium-stat-card:hover .premium-stat-icon {
    box-shadow: 0 12px 40px rgba(245, 158, 11, 0.4);
}

.premium-progress-fill {
    background-size: 200% 100%;
    animation: shimmer 2s infinite ease-in-out;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.premium-info-card {
    position: relative;
}

.premium-info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #d97706, #b45309);
    border-radius: 1rem 1rem 0 0;
}

/* Typography Improvements */
.premium-dashboard-container h1,
.premium-dashboard-container h2,
.premium-dashboard-container h3 {
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.premium-dashboard-container p,
.premium-dashboard-container span {
    line-height: 1.5;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .premium-welcome-header {
        padding: 1rem;
    }
    
    .premium-welcome-header h1 {
        font-size: 1.875rem;
    }
    
    .premium-stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .premium-info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

/* Dark mode specific improvements */
@media (prefers-color-scheme: dark) {
    .premium-stat-card {
        backdrop-filter: blur(16px);
    }
    
    .premium-info-card {
        backdrop-filter: blur(16px);
    }
}
</style>