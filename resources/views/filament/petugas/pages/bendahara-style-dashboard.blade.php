<x-filament-panels::page>
    @php
        $operational = $this->getOperationalSummary();
        $tasks = $this->getTaskMetrics();
        $trends = $this->getMonthlyTrends();
        $activities = $this->getRecentActivities();
    @endphp

    <!-- Bendahara-Style Petugas Dashboard -->
    <div class="space-y-6">
        
        <!-- Core Operational Metrics - 4 Essential Cards (Bendahara Style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Patients Card (Revenue equivalent) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    @if($operational['growth']['patients'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($operational['growth']['patients'] > 0)
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-600 font-medium">+{{ $operational['growth']['patients'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-600 font-medium">{{ $operational['growth']['patients'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    {{ $operational['current']['patients'] }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pasien Bulan Ini</p>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400">Hari ini: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $operational['today']['patients'] }}</span></p>
                </div>
            </div>

            <!-- Actions Card (Expenses equivalent) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    @if($operational['growth']['actions'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($operational['growth']['actions'] > 0)
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-600 font-medium">+{{ $operational['growth']['actions'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-600 font-medium">{{ $operational['growth']['actions'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    {{ $operational['current']['actions'] }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan Bulan Ini</p>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400">Hari ini: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $operational['today']['actions'] }}</span></p>
                </div>
            </div>

            <!-- Revenue Card (Same as Bendahara) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    @if($operational['growth']['revenue'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($operational['growth']['revenue'] > 0)
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-600 font-medium">+{{ $operational['growth']['revenue'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-600 font-medium">{{ $operational['growth']['revenue'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    Rp {{ number_format($operational['current']['revenue'], 0, ',', '.') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Revenue Bulan Ini</p>
            </div>

            <!-- Efficiency Card (Net Income equivalent) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">
                        Excellent
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    {{ $operational['current']['efficiency'] }}%
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Efficiency Score</p>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400">Avg time: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $tasks['avg_completion_time'] }}</span></p>
                </div>
            </div>
        </div>

        <!-- Dashboard Layout - Similar to Bendahara Double Column -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Left Column - Monthly Trends Chart (Bendahara Style) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Trend Operasional (6 Bulan)</h3>
                    <div class="flex items-center space-x-3 text-xs">
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">Pasien</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">Tindakan</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">Revenue</span>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <canvas id="monthly-trends-chart" height="250"></canvas>
                </div>
            </div>

            <!-- Right Column - Task Performance (Validation equivalent) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Task Performance</h3>
                    <div class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                        {{ $tasks['efficiency_rate'] }}% Efficiency
                    </div>
                </div>
                
                <!-- Pending Tasks -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tindakan Pending</span>
                        </div>
                        <span class="text-lg font-bold text-amber-600">{{ $tasks['pending']['tindakan'] }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Validasi Pending</span>
                        </div>
                        <span class="text-lg font-bold text-orange-600">{{ $tasks['pending']['validasi'] }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Update Diperlukan</span>
                        </div>
                        <span class="text-lg font-bold text-blue-600">{{ $tasks['pending']['pasien_update'] }}</span>
                    </div>
                </div>

                <!-- Performance Summary -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-emerald-600">{{ $tasks['total_completed'] }}</div>
                            <div class="text-xs text-gray-500">Completed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-amber-600">{{ $tasks['total_pending'] }}</div>
                            <div class="text-xs text-gray-500">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities - Full Width (Bendahara Style) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aktivitas Terkini</h3>
                <a href="#" class="text-sm text-amber-600 hover:text-amber-700 font-medium">Lihat Semua →</a>
            </div>
            <div class="space-y-4">
                @foreach($activities as $activity)
                <div class="flex items-start space-x-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <div class="p-2 {{ $activity['type'] === 'patient' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-emerald-100 dark:bg-emerald-900/30' }} rounded-lg">
                        @if($activity['type'] === 'patient')
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $activity['title'] }}</h4>
                            <span class="px-2 py-1 text-xs font-medium 
                                {{ $activity['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} 
                                rounded-full">
                                {{ ucfirst($activity['status']) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $activity['subtitle'] }}</p>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-400">
                            <span>{{ $activity['user'] }}</span>
                            <span>{{ $activity['date']->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Task Summary Cards Row (Similar to Bendahara Validation Cards) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Waktu Rata-rata</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tasks['avg_completion_time'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Efficiency Rate</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tasks['efficiency_rate'] }}%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pasien Input</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tasks['completed']['pasien_input'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Pending</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tasks['total_pending'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Integration (Same as Bendahara) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Monthly Trends Chart (Bendahara Style)
            const ctx = document.getElementById('monthly-trends-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($trends['labels']) !!},
                        datasets: [{
                            label: 'Pasien',
                            data: {!! json_encode($trends['data']['patients']) !!},
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#3b82f6',
                            pointBorderWidth: 2,
                        }, {
                            label: 'Tindakan',
                            data: {!! json_encode($trends['data']['actions']) !!},
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#10b981',
                            pointBorderWidth: 2,
                        }, {
                            label: 'Revenue',
                            data: {!! json_encode($trends['data']['revenue']) !!},
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#f59e0b',
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
                                display: false // Legend shown above chart
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
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.datasetIndex === 2) { // Revenue
                                            label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                        } else {
                                            label += context.parsed.y;
                                        }
                                        return label;
                                    }
                                }
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
                                    color: '#6b7280',
                                    callback: function(value, index, values) {
                                        // Format large numbers
                                        if (value >= 1000000) {
                                            return (value / 1000000).toFixed(1) + 'M';
                                        } else if (value >= 1000) {
                                            return (value / 1000).toFixed(0) + 'K';
                                        }
                                        return value;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-filament-panels::page>