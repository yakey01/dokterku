<x-filament-panels::page>
    <!-- Custom Styles for Elegant Black Sidebar -->
    <style>
        /* Elegant Black Sidebar for Petugas Panel */
        .fi-sidebar {
            background: #0f0f0f !important;
            border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1) !important;
        }
        
        .fi-sidebar-header {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            padding: 1.5rem !important;
        }
        
        .fi-sidebar-header h2,
        .fi-sidebar-header span {
            color: #ffffff !important;
        }
        
        .fi-sidebar-item a {
            color: rgba(255, 255, 255, 0.85) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
        }
        
        .fi-sidebar-item a:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            transform: translateX(4px) !important;
        }
        
        .fi-sidebar-item-active a {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%) !important;
            color: #3b82f6 !important;
            border-left: 3px solid #3b82f6 !important;
        }
        
        .fi-sidebar-group-button {
            color: rgba(255, 255, 255, 0.7) !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }
        
        .fi-sidebar-group-button:hover {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
        }
        
        /* White Dashboard Area */
        .fi-main {
            background: #ffffff !important;
        }
        
        .fi-topbar {
            background: #ffffff !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        /* Remove Dark Mode Toggle */
        .fi-theme-switcher {
            display: none !important;
        }
    </style>
    
    @php
        $metrics = $this->getMetricsSummary();
        $performance = $this->getPerformanceMetrics();
        $trends = $this->getWeeklyTrends();
        $activities = $this->getRecentActivities();
        $categories = $this->getCategoryPerformance();
    @endphp

    <!-- World-Class Petugas Dashboard with Double Layout -->
    <div class="space-y-6">
        
        <!-- Top Metrics Cards - Premium Design -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Patients Today Card -->
            <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-50 rounded-xl group-hover:bg-blue-100 transition-colors duration-300">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    @if($metrics['growth']['patients'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($metrics['growth']['patients'] > 0)
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-green-600 font-semibold">+{{ $metrics['growth']['patients'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-red-600 font-semibold">{{ $metrics['growth']['patients'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1">
                    {{ $metrics['today']['patients'] }}
                </h3>
                <p class="text-sm text-gray-500 font-medium">Pasien Hari Ini</p>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Total bulan ini: <span class="font-semibold text-gray-600">{{ $metrics['monthly']['patients'] }}</span></p>
                </div>
            </div>

            <!-- Actions Today Card -->
            <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-50 rounded-xl group-hover:bg-emerald-100 transition-colors duration-300">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    @if($metrics['growth']['actions'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($metrics['growth']['actions'] > 0)
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-green-600 font-semibold">+{{ $metrics['growth']['actions'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-red-600 font-semibold">{{ $metrics['growth']['actions'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1">
                    {{ $metrics['today']['actions'] }}
                </h3>
                <p class="text-sm text-gray-500 font-medium">Tindakan Selesai</p>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Total bulan ini: <span class="font-semibold text-gray-600">{{ $metrics['monthly']['actions'] }}</span></p>
                </div>
            </div>

            <!-- Revenue Card -->
            <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-50 rounded-xl group-hover:bg-amber-100 transition-colors duration-300">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    @if($metrics['growth']['revenue'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($metrics['growth']['revenue'] > 0)
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-green-600 font-semibold">+{{ $metrics['growth']['revenue'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-red-600 font-semibold">{{ $metrics['growth']['revenue'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">
                    Rp {{ number_format($metrics['today']['revenue'], 0, ',', '.') }}
                </h3>
                <p class="text-sm text-gray-500 font-medium">Pendapatan Hari Ini</p>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Total bulan ini: <span class="font-semibold text-gray-600">Rp {{ number_format($metrics['monthly']['revenue'], 0, ',', '.') }}</span></p>
                </div>
            </div>

            <!-- Performance Score Card -->
            <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-50 rounded-xl group-hover:bg-purple-100 transition-colors duration-300">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">
                        Excellent
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1">
                    {{ $performance['efficiency_score'] }}%
                </h3>
                <p class="text-sm text-gray-500 font-medium">Performance Score</p>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Akurasi: <span class="font-semibold text-gray-600">{{ $performance['accuracy_rate'] }}%</span></p>
                </div>
            </div>
        </div>

        <!-- Double Layout Section - Charts and Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Left Column - Weekly Trends Chart -->
            <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Trend Mingguan</h3>
                    <div class="flex items-center space-x-3 text-xs">
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-gray-600">Pasien</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            <span class="text-gray-600">Tindakan</span>
                        </div>
                    </div>
                </div>
                <div x-data="weeklyChart()" x-init="initChart()" class="relative">
                    <canvas id="weekly-trends-chart" height="250"></canvas>
                </div>
            </div>

            <!-- Right Column - Split Layout for Activities -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Patient Categories -->
                <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-gray-900">Kategori Pasien</h3>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div x-data="patientChart()" x-init="initChart()" class="relative">
                        <canvas id="patient-categories-chart" height="180"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($categories['patient_categories'] as $category)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $category['color'] }}"></div>
                                <span class="text-xs text-gray-600">{{ $category['name'] }}</span>
                            </div>
                            <span class="text-xs font-semibold text-gray-900">{{ $category['value'] }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Action Categories -->
                <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-gray-900">Jenis Tindakan</h3>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div x-data="actionChart()" x-init="initChart()" class="relative">
                        <canvas id="action-categories-chart" height="180"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($categories['action_categories'] as $category)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $category['color'] }}"></div>
                                <span class="text-xs text-gray-600">{{ $category['name'] }}</span>
                            </div>
                            <span class="text-xs font-semibold text-gray-900">{{ $category['value'] }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Activities - Full Width Below -->
                <div class="md:col-span-2 bg-white rounded-xl p-5 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-gray-900">Aktivitas Terkini</h3>
                        <a href="#" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Lihat Semua →</a>
                    </div>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        @foreach($activities as $activity)
                        <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors duration-200 cursor-pointer">
                            <div class="p-2 {{ $activity['color'] === 'blue' ? 'bg-blue-100' : 'bg-emerald-100' }} rounded-lg flex-shrink-0">
                                @if($activity['icon'] === 'users')
                                    <svg class="w-4 h-4 {{ $activity['color'] === 'blue' ? 'text-blue-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 {{ $activity['color'] === 'blue' ? 'text-blue-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $activity['title'] }}</p>
                                <p class="text-xs text-gray-500">{{ $activity['subtitle'] }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-xs text-gray-400">{{ $activity['time'] }}</p>
                                <p class="text-xs text-gray-500">{{ $activity['user'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-indigo-50 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Waktu Rata-rata</p>
                        <p class="text-sm font-bold text-gray-900">{{ $performance['average_time'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-green-50 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Akurasi Input</p>
                        <p class="text-sm font-bold text-gray-900">{{ $performance['accuracy_rate'] }}%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-amber-50 rounded-lg">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Produktivitas</p>
                        <p class="text-sm font-bold text-gray-900">{{ $performance['productivity_index'] }}%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-rose-50 rounded-lg">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Pending Validasi</p>
                        <p class="text-sm font-bold text-gray-900">{{ $performance['validation_pending'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js Charts Integration -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Weekly Trends Chart
        function weeklyChart() {
            return {
                initChart() {
                    const ctx = document.getElementById('weekly-trends-chart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($trends['labels']),
                            datasets: [{
                                label: 'Pasien',
                                data: @json($trends['data']['patients']),
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#3b82f6',
                                pointBorderWidth: 2,
                            }, {
                                label: 'Tindakan',
                                data: @json($trends['data']['actions']),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#10b981',
                                pointBorderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    cornerRadius: 8,
                                    titleFont: {
                                        size: 13,
                                        weight: 'normal'
                                    },
                                    bodyFont: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    displayColors: true,
                                    boxWidth: 10,
                                    boxHeight: 10,
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false,
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        color: '#6b7280'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        borderDash: [5, 5],
                                        color: '#e5e7eb',
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        color: '#6b7280'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        // Patient Categories Doughnut Chart
        function patientChart() {
            return {
                initChart() {
                    const ctx = document.getElementById('patient-categories-chart').getContext('2d');
                    const categories = @json($categories['patient_categories']);
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: categories.map(c => c.name),
                            datasets: [{
                                data: categories.map(c => c.value),
                                backgroundColor: categories.map(c => c.color),
                                borderWidth: 0,
                                spacing: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ' + context.parsed + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        // Action Categories Doughnut Chart
        function actionChart() {
            return {
                initChart() {
                    const ctx = document.getElementById('action-categories-chart').getContext('2d');
                    const categories = @json($categories['action_categories']);
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: categories.map(c => c.name),
                            datasets: [{
                                data: categories.map(c => c.value),
                                backgroundColor: categories.map(c => c.color),
                                borderWidth: 0,
                                spacing: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ' + context.parsed + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
</x-filament-panels::page>