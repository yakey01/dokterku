<x-filament-widgets::widget>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        @php 
            $recentActivities = $this->getRecentActivities();
            $topPerformers = $this->getTopPerformers();
            $monthlyTrends = $this->getMonthlyTrends();
        @endphp
        
        <!-- Recent Activities -->
        <div class="xl:col-span-2 group relative overflow-hidden rounded-3xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-500/10 dark:shadow-black/20 transition-all duration-700 hover:shadow-2xl hover:shadow-gray-500/20 dark:hover:shadow-black/40 hover:-translate-y-1">
            <!-- Animated background -->
            <div class="absolute -top-32 -right-32 w-64 h-64 bg-gradient-to-br from-blue-500/10 to-purple-600/10 rounded-full blur-3xl transition-all duration-1000 group-hover:scale-150 group-hover:rotate-12"></div>
            
            <div class="relative z-10 p-8">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg shadow-blue-500/30 transition-all duration-500 group-hover:shadow-blue-500/50 group-hover:scale-110 group-hover:rotate-6">
                            <x-heroicon-o-clock class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Recent Activities</h3>
                            <p class="text-gray-600 dark:text-gray-400">Latest updates and entries</p>
                        </div>
                    </div>
                    <div class="px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20">
                        <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">{{ count($recentActivities) }} items</span>
                    </div>
                </div>
                
                <div class="space-y-4">
                    @foreach($recentActivities as $activity)
                        <div class="group/item relative overflow-hidden rounded-2xl bg-gradient-to-r from-{{ $activity['color'] }}-50/30 to-white dark:from-{{ $activity['color'] }}-900/10 dark:to-gray-800 border border-{{ $activity['color'] }}-200/50 dark:border-{{ $activity['color'] }}-700/30 p-6 transition-all duration-500 hover:shadow-lg hover:shadow-{{ $activity['color'] }}-500/20 hover:scale-[1.02] hover:-translate-y-1">
                            <!-- Activity decoration -->
                            <div class="absolute top-0 right-0 w-20 h-20 bg-{{ $activity['color'] }}-500/5 rounded-full blur-xl transition-all duration-500 group-hover/item:scale-150"></div>
                            
                            <div class="relative z-10 flex items-center space-x-4">
                                <!-- Avatar -->
                                <div class="relative flex-shrink-0">
                                    <img src="{{ $activity['avatar'] }}" alt="{{ $activity['created_by'] }}" class="w-12 h-12 rounded-full border-2 border-{{ $activity['color'] }}-200 dark:border-{{ $activity['color'] }}-700 shadow-lg transition-all duration-300 group-hover/item:scale-110 group-hover/item:border-{{ $activity['color'] }}-400">
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-{{ $activity['color'] }}-500 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center transition-all duration-300 group-hover/item:scale-110">
                                        @if($activity['icon'] === 'heroicon-o-banknotes')
                                            <x-heroicon-o-banknotes class="w-3 h-3 text-white" />
                                        @elseif($activity['icon'] === 'heroicon-o-arrow-trending-down')
                                            <x-heroicon-o-arrow-trending-down class="w-3 h-3 text-white" />
                                        @elseif($activity['icon'] === 'heroicon-o-clipboard-document-list')
                                            <x-heroicon-o-clipboard-document-list class="w-3 h-3 text-white" />
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white transition-colors duration-300 group-hover/item:text-{{ $activity['color'] }}-700 dark:group-hover/item:text-{{ $activity['color'] }}-300">
                                                {{ $activity['title'] }}
                                            </h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $activity['description'] }}</p>
                                            <div class="flex items-center space-x-2 mt-2">
                                                <span class="text-xs font-medium text-{{ $activity['color'] }}-700 dark:text-{{ $activity['color'] }}-300">{{ $activity['created_by'] }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">•</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($activity['time'])->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Time badge -->
                                        <div class="flex-shrink-0 ml-4">
                                            <div class="px-3 py-1 rounded-full bg-{{ $activity['color'] }}-500/10 border border-{{ $activity['color'] }}-500/20 transition-all duration-300 group-hover/item:bg-{{ $activity['color'] }}-500/20">
                                                <span class="text-xs font-medium text-{{ $activity['color'] }}-700 dark:text-{{ $activity['color'] }}-300">
                                                    {{ \Carbon\Carbon::parse($activity['date'])->format('M d') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Top Performers & Analytics -->
        <div class="space-y-8">
            <!-- Top Staff -->
            <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-500/10 dark:shadow-black/20 transition-all duration-700 hover:shadow-2xl hover:shadow-gray-500/20 dark:hover:shadow-black/40 hover:-translate-y-1">
                <div class="absolute -top-24 -left-24 w-48 h-48 bg-gradient-to-br from-emerald-500/10 to-teal-600/10 rounded-full blur-3xl transition-all duration-1000 group-hover:scale-150 group-hover:-rotate-12"></div>
                
                <div class="relative z-10 p-6">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/30 transition-all duration-500 group-hover:shadow-emerald-500/50 group-hover:scale-110">
                            <x-heroicon-o-trophy class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Performers</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">This month's leaders</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($topPerformers['staff'] as $index => $staff)
                            <div class="group/staff flex items-center space-x-3 p-3 rounded-xl bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 border border-gray-200 dark:border-gray-600 transition-all duration-300 hover:shadow-md hover:scale-[1.02]">
                                <div class="flex items-center space-x-3 flex-1">
                                    <!-- Rank badge -->
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                                        {{ $index + 1 }}
                                    </div>
                                    
                                    <!-- Avatar -->
                                    <img src="{{ $staff['avatar'] }}" alt="{{ $staff['name'] }}" class="w-10 h-10 rounded-full border-2 border-indigo-200 dark:border-indigo-700 transition-all duration-300 group-hover/staff:scale-110">
                                    
                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $staff['name'] }}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $staff['total'] }} entries</p>
                                    </div>
                                </div>
                                
                                <!-- Progress indicator -->
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-500" style="width: {{ min(100, $topPerformers['staff'] && count($topPerformers['staff']) > 0 && max(array_column($topPerformers['staff'], 'total')) > 0 ? ($staff['total'] / max(array_column($topPerformers['staff'], 'total'))) * 100 : 0) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Top Procedures -->
            <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-500/10 dark:shadow-black/20 transition-all duration-700 hover:shadow-2xl hover:shadow-gray-500/20 dark:hover:shadow-black/40 hover:-translate-y-1">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-gradient-to-br from-purple-500/10 to-pink-600/10 rounded-full blur-3xl transition-all duration-1000 group-hover:scale-150 group-hover:rotate-12"></div>
                
                <div class="relative z-10 p-6">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 shadow-lg shadow-purple-500/30 transition-all duration-500 group-hover:shadow-purple-500/50 group-hover:scale-110">
                            <x-heroicon-o-chart-pie class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Popular Procedures</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Most performed</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        @foreach($topPerformers['procedures'] as $index => $procedure)
                            <div class="group/procedure">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ $procedure['name'] }}</h4>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">{{ $procedure['total'] }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $procedure['percentage'] }}%</span>
                                    </div>
                                </div>
                                <div class="relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-purple-500 to-pink-600 rounded-full transition-all duration-1000 ease-out" style="width: {{ $procedure['percentage'] }}%">
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent rounded-full transform -skew-x-12 animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Trends Chart -->
    <div class="mt-8 group relative overflow-hidden rounded-3xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-500/10 dark:shadow-black/20 transition-all duration-700 hover:shadow-2xl hover:shadow-gray-500/20 dark:hover:shadow-black/40 hover:-translate-y-1">
        <div class="absolute -top-32 -left-32 w-64 h-64 bg-gradient-to-br from-indigo-500/10 to-blue-600/10 rounded-full blur-3xl transition-all duration-1000 group-hover:scale-150 group-hover:rotate-12"></div>
        
        <div class="relative z-10 p-8">
            <div class="flex items-center space-x-4 mb-8">
                <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-500/30 transition-all duration-500 group-hover:shadow-indigo-500/50 group-hover:scale-110 group-hover:rotate-6">
                    <x-heroicon-o-chart-bar class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Monthly Trends</h3>
                    <p class="text-gray-600 dark:text-gray-400">6-month performance overview</p>
                </div>
            </div>
            
            <div id="monthlyTrendsChart" style="height: 400px;" class="chart-container"></div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Enhanced chart with premium styling
        function detectDarkMode() {
            return document.documentElement.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function getChartTheme() {
            const isDark = detectDarkMode();
            
            return {
                colors: isDark ? 
                    ['#34d399', '#fca5a5', '#93c5fd'] : 
                    ['#10B981', '#EF4444', '#3B82F6'],
                chart: {
                    background: 'transparent',
                    foreColor: isDark ? '#ffffff' : '#374151'
                },
                grid: {
                    borderColor: isDark ? '#6b7280' : '#F3F4F6',
                    strokeDashArray: 3,
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    },
                    axisBorder: {
                        color: isDark ? '#6b7280' : '#e5e7eb'
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    }
                },
                legend: {
                    labels: {
                        colors: isDark ? '#ffffff' : '#374151'
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light'
                }
            };
        }

        function createMonthlyChart() {
            const theme = getChartTheme();
            
            const monthlyOptions = {
                chart: {
                    type: 'line',
                    height: 400,
                    toolbar: { show: false },
                    background: theme.chart.background,
                    foreColor: theme.chart.foreColor,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 1200,
                    }
                },
                series: [
                    {
                        name: 'Pendapatan',
                        data: @json($monthlyTrends['pendapatan'])
                    },
                    {
                        name: 'Pengeluaran',
                        data: @json($monthlyTrends['pengeluaran'])
                    },
                    {
                        name: 'Pasien',
                        data: @json($monthlyTrends['pasien'])
                    }
                ],
                xaxis: {
                    categories: @json($monthlyTrends['months']),
                    labels: { style: theme.xaxis.labels.style },
                    axisBorder: { color: theme.xaxis.axisBorder.color }
                },
                yaxis: {
                    labels: {
                        style: theme.yaxis.labels.style,
                        formatter: function (value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value;
                        }
                    }
                },
                colors: theme.colors,
                stroke: {
                    curve: 'smooth',
                    width: 4,
                    lineCap: 'round'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1
                    }
                },
                legend: {
                    position: 'top',
                    labels: theme.legend.labels,
                    markers: { width: 12, height: 12, radius: 6 }
                },
                grid: theme.grid,
                tooltip: {
                    theme: theme.tooltip.theme,
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (value, { seriesIndex }) {
                            if (seriesIndex === 0 || seriesIndex === 1) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            } else {
                                return value + ' pasien';
                            }
                        }
                    }
                },
                markers: {
                    size: 6,
                    colors: theme.colors,
                    strokeColors: detectDarkMode() ? '#1f2937' : '#ffffff',
                    strokeWidth: 3,
                    hover: { size: 8 }
                }
            };

            return new ApexCharts(document.querySelector("#monthlyTrendsChart"), monthlyOptions);
        }

        let premiumActivitiesMonthlyChart = createMonthlyChart();
        premiumActivitiesMonthlyChart.render();

        // Theme change detection
        const premiumActivitiesObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'class' && 
                    mutation.target === document.documentElement) {
                    if (premiumActivitiesMonthlyChart) {
                        premiumActivitiesMonthlyChart.destroy();
                        premiumActivitiesMonthlyChart = createMonthlyChart();
                        premiumActivitiesMonthlyChart.render();
                    }
                }
            });
        });

        premiumActivitiesObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        window.addEventListener('beforeunload', function() {
            if (premiumActivitiesObserver) premiumActivitiesObserver.disconnect();
            if (premiumActivitiesMonthlyChart) premiumActivitiesMonthlyChart.destroy();
        });
    </script>
    @endpush
</x-filament-widgets::widget>