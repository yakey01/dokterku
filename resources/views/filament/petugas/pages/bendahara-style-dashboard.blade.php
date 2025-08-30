<x-filament-panels::page>
    <!-- Force Black Theme CSS Override -->
    <link rel="stylesheet" href="{{ asset('petugas-black-theme.css') }}" type="text/css">
    @php
        $operational = $this->getOperationalSummary();
        $tasks = $this->getTaskMetrics();
        $trends = $this->getMonthlyTrends();
        $activities = $this->getRecentActivities();
    @endphp

    <!-- Bendahara-Style Petugas Dashboard -->
    <div class="space-y-6">
        
        <!-- Core Operational Metrics - 3 Essential Cards (Bendahara Style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Patients Card (Black Theme) -->
            <div class="bg-black rounded-xl p-6 shadow-2xl border border-gray-800 hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-300 hover:border-blue-500/50 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-500/20 rounded-lg ring-1 ring-blue-400/30 group-hover:bg-blue-500/30 group-hover:ring-blue-400/50 transition-all duration-200">
                        <svg class="w-6 h-6 text-blue-300 group-hover:text-blue-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    @if($operational['growth']['patients'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($operational['growth']['patients'] > 0)
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-400 font-medium">+{{ $operational['growth']['patients'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-400 font-medium">{{ $operational['growth']['patients'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-3xl font-bold text-white mb-1 tracking-tight">
                    {{ $operational['current']['patients'] }}
                </h3>
                <p class="text-sm text-gray-300 font-medium">Total Pasien Bulan Ini</p>
                <div class="mt-4 pt-3 border-t border-gray-700">
                    <p class="text-xs text-gray-400">Hari ini: <span class="font-semibold text-gray-200">{{ $operational['today']['patients'] }}</span></p>
                </div>
            </div>

            <!-- Actions Card (Black Theme) -->
            <div class="bg-black rounded-xl p-6 shadow-2xl border border-gray-800 hover:shadow-2xl hover:shadow-emerald-500/20 transition-all duration-300 hover:border-emerald-500/50 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-500/20 rounded-lg ring-1 ring-emerald-400/30 group-hover:bg-emerald-500/30 group-hover:ring-emerald-400/50 transition-all duration-200">
                        <svg class="w-6 h-6 text-emerald-300 group-hover:text-emerald-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    @if($operational['growth']['actions'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($operational['growth']['actions'] > 0)
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-400 font-medium">+{{ $operational['growth']['actions'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-400 font-medium">{{ $operational['growth']['actions'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-3xl font-bold text-white mb-1 tracking-tight">
                    {{ $operational['current']['actions'] }}
                </h3>
                <p class="text-sm text-gray-300 font-medium">Tindakan Bulan Ini</p>
                <div class="mt-4 pt-3 border-t border-gray-700">
                    <p class="text-xs text-gray-400">Hari ini: <span class="font-semibold text-gray-200">{{ $operational['today']['actions'] }}</span></p>
                </div>
            </div>

            <!-- Revenue Card (Black Theme) -->
            <div class="bg-black rounded-xl p-6 shadow-2xl border border-gray-800 hover:shadow-2xl hover:shadow-amber-500/20 transition-all duration-300 hover:border-amber-500/50 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-500/20 rounded-lg ring-1 ring-amber-400/30 group-hover:bg-amber-500/30 group-hover:ring-amber-400/50 transition-all duration-200">
                        <svg class="w-6 h-6 text-amber-300 group-hover:text-amber-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    @if($operational['growth']['revenue'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($operational['growth']['revenue'] > 0)
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-400 font-medium">+{{ $operational['growth']['revenue'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-400 font-medium">{{ $operational['growth']['revenue'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-3xl font-bold text-white mb-1 tracking-tight">
                    Rp {{ number_format($operational['current']['revenue'], 0, ',', '.') }}
                </h3>
                <p class="text-sm text-gray-300 font-medium">Revenue Bulan Ini</p>
            </div>

        </div>

        <!-- Dashboard Layout - Similar to Bendahara Double Column -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Left Column - Monthly Trends Chart (Bendahara Style) -->
            <div class="bg-black rounded-xl p-6 shadow-2xl border border-gray-800">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-white">Trend Operasional (6 Bulan)</h3>
                    <div class="flex items-center space-x-3 text-xs">
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-gray-300">Pasien</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            <span class="text-gray-300">Tindakan</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                            <span class="text-gray-300">Revenue</span>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <canvas id="monthly-trends-chart" height="250"></canvas>
                </div>
            </div>

            <!-- Right Column - Task Performance (Validation equivalent) -->
            <div class="bg-black rounded-xl p-6 shadow-2xl border border-gray-800">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-white">Task Performance</h3>
                    <div class="px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-xs font-semibold ring-1 ring-emerald-400/30">
                        {{ $tasks['efficiency_rate'] }}% Efficiency
                    </div>
                </div>
                
                <!-- Pending Tasks -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between p-3 bg-amber-500/20 rounded-lg ring-1 ring-amber-400/30">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-200">Tindakan Pending</span>
                        </div>
                        <span class="text-lg font-bold text-amber-400">{{ $tasks['pending']['tindakan'] }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-orange-500/20 rounded-lg ring-1 ring-orange-400/30">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-200">Validasi Pending</span>
                        </div>
                        <span class="text-lg font-bold text-orange-400">{{ $tasks['pending']['validasi'] }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-blue-500/20 rounded-lg ring-1 ring-blue-400/30">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-200">Update Diperlukan</span>
                        </div>
                        <span class="text-lg font-bold text-blue-400">{{ $tasks['pending']['pasien_update'] }}</span>
                    </div>
                </div>

                <!-- Performance Summary -->
                <div class="pt-4 border-t border-gray-700">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-emerald-400">{{ $tasks['total_completed'] }}</div>
                            <div class="text-xs text-gray-400">Completed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-amber-400">{{ $tasks['total_pending'] }}</div>
                            <div class="text-xs text-gray-400">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities - Full Width (Bendahara Style) -->
        <div class="bg-black rounded-xl p-6 shadow-2xl border border-gray-800">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white">Aktivitas Terkini</h3>
                <a href="#" class="text-sm text-amber-400 hover:text-amber-300 font-medium">Lihat Semua →</a>
            </div>
            <div class="space-y-4">
                @foreach($activities as $activity)
                <div class="flex items-start space-x-4 p-4 bg-gray-800/50 rounded-lg hover:bg-gray-800/70 transition-colors duration-200 ring-1 ring-gray-700">
                    <div class="p-2 {{ $activity['type'] === 'patient' ? 'bg-blue-500/20 ring-1 ring-blue-400/30' : 'bg-emerald-500/20 ring-1 ring-emerald-400/30' }} rounded-lg">
                        @if($activity['type'] === 'patient')
                            <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-white truncate">{{ $activity['title'] }}</h4>
                            <span class="px-2 py-1 text-xs font-medium 
                                {{ $activity['status'] === 'completed' ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30' : 'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/30' }} 
                                rounded-full">
                                {{ ucfirst($activity['status']) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-300 mt-1">{{ $activity['subtitle'] }}</p>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                            <span>{{ $activity['user'] }}</span>
                            <span>{{ $activity['date']->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
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
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
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
                                    color: function(context) {
                                        return document.documentElement.classList.contains('dark') ? '#d1d5db' : '#6b7280';
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    borderDash: [5, 5],
                                    color: function(context) {
                                        return document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb';
                                    },
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: function(context) {
                                        return document.documentElement.classList.contains('dark') ? '#d1d5db' : '#6b7280';
                                    },
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