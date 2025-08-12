<x-filament-widgets::widget>
    <div class="premium-dashboard-container space-y-8">
            <!-- Welcome Header -->
            <div class="premium-welcome-header">
                <div class="flex items-center justify-between">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 bg-clip-text text-transparent tracking-tight">
                            Selamat Datang, {{ $this->getViewData()['user_name'] }}!
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-300 font-medium">
                            Dashboard Petugas • {{ now()->format('l, d F Y') }}
                        </p>
                    </div>
                    <div class="premium-time-badge">
                        <div class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border border-amber-200 dark:border-amber-700">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ now()->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Stats Grid -->
            <div class="premium-stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($this->getViewData()['stats'] as $stat)
                    <div class="premium-stat-card group cursor-pointer transform transition-all duration-500 hover:scale-105 hover:-translate-y-2">
                        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg hover:shadow-2xl hover:shadow-{{ $stat['color'] }}-500/25 dark:hover:shadow-{{ $stat['color'] }}-400/20 transition-all duration-500">
                            <!-- Background Pattern -->
                            <div class="absolute inset-0 opacity-5 group-hover:opacity-10 transition-opacity duration-500">
                                <div class="absolute -top-4 -right-4 w-24 h-24 bg-{{ $stat['color'] }}-500 rounded-full blur-3xl"></div>
                                <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-{{ $stat['color'] }}-300 rounded-full blur-2xl"></div>
                            </div>
                            
                            <!-- Card Content -->
                            <div class="relative p-6 space-y-4">
                                <!-- Header with Icon -->
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">
                                            {{ $stat['title'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 leading-relaxed">
                                            {{ $stat['description'] }}
                                        </p>
                                    </div>
                                    <div class="premium-stat-icon flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-{{ $stat['color'] }}-500 to-{{ $stat['color'] }}-600 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-lg shadow-{{ $stat['color'] }}-500/30">
                                        @switch($stat['icon'])
                                            @case('users')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                @break
                                            @case('currency-dollar')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                </svg>
                                                @break
                                            @case('clipboard-document-list')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                @break
                                            @case('banknotes')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                @break
                                        @endswitch
                                    </div>
                                </div>

                                <!-- Value -->
                                <div class="space-y-2">
                                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white group-hover:text-{{ $stat['color'] }}-600 dark:group-hover:text-{{ $stat['color'] }}-400 transition-colors duration-500 tracking-tight">
                                        {{ $stat['value'] }}
                                    </h3>
                                    
                                    <!-- Trend -->
                                    <div class="flex items-center space-x-2">
                                        @if($stat['trend_direction'] === 'up')
                                            <div class="flex items-center px-2.5 py-1 bg-green-100 dark:bg-green-900/30 rounded-full">
                                                <svg class="w-3 h-3 text-green-600 dark:text-green-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                                </svg>
                                                <span class="text-xs font-semibold text-green-600 dark:text-green-400">+{{ number_format($stat['trend'], 1) }}%</span>
                                            </div>
                                        @elseif($stat['trend_direction'] === 'down')
                                            <div class="flex items-center px-2.5 py-1 bg-red-100 dark:bg-red-900/30 rounded-full">
                                                <svg class="w-3 h-3 text-red-600 dark:text-red-400 mr-1 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                                </svg>
                                                <span class="text-xs font-semibold text-red-600 dark:text-red-400">-{{ number_format($stat['trend'], 1) }}%</span>
                                            </div>
                                        @else
                                            <div class="flex items-center px-2.5 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                                                <svg class="w-3 h-3 text-gray-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                </svg>
                                                <span class="text-xs font-semibold text-gray-500">Stabil</span>
                                            </div>
                                        @endif
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">dari kemarin</span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="premium-progress-bar w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="premium-progress-fill h-full bg-gradient-to-r from-{{ $stat['color'] }}-400 to-{{ $stat['color'] }}-600 rounded-full transition-all duration-1000 ease-out group-hover:animate-pulse" 
                                         style="width: {{ min(100, abs($stat['trend']) * 2) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Additional Info Cards -->
            <div class="premium-info-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Validation Summary -->
                <div class="premium-info-card transform transition-all duration-500 hover:scale-[1.02] hover:-translate-y-1">
                    <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg hover:shadow-2xl hover:shadow-blue-500/25 dark:hover:shadow-blue-400/20 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 opacity-50"></div>
                        
                        <div class="relative p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Status Validasi</h3>
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Validasi</span>
                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $this->getViewData()['validation_summary']['pending_validations'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Approval Rate</span>
                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ $this->getViewData()['validation_summary']['approval_rate'] }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Disetujui Hari Ini</span>
                                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $this->getViewData()['validation_summary']['approved_today'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="premium-info-card transform transition-all duration-500 hover:scale-[1.02] hover:-translate-y-1">
                    <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg hover:shadow-2xl hover:shadow-purple-500/25 dark:hover:shadow-purple-400/20 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 opacity-50"></div>
                        
                        <div class="relative p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Performa</h3>
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Efisiensi</span>
                                        <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ number_format($this->getViewData()['performance_metrics']['efficiency_score'] ?? 87.5, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $this->getViewData()['performance_metrics']['efficiency_score'] ?? 87.5 }}%"></div>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Kepuasan Pasien</span>
                                        <span class="text-lg font-bold text-pink-600 dark:text-pink-400">{{ number_format($this->getViewData()['performance_metrics']['patient_satisfaction'] ?? 92.3, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-pink-400 to-pink-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $this->getViewData()['performance_metrics']['patient_satisfaction'] ?? 92.3 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
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