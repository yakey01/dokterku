<x-filament-panels::page>
    <!-- Welcome Message -->
    <div class="mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h1 class="text-xl font-semibold">Welcome Petugas, {{ auth()->user()->name }}</h1>
                    <p class="text-blue-100 text-sm">Selamat bekerja pada {{ \Carbon\Carbon::now()->format('l, d F Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    @livewire(\App\Filament\Petugas\Widgets\PremiumStatsWidget::class)
    @livewire(\App\Filament\Petugas\Widgets\PremiumProgressWidget::class)
    @livewire(\App\Filament\Petugas\Widgets\PremiumActivitiesWidget::class)

    @php
        $operationalSummary = $this->getOperationalSummary();
        $dataEntryStats = $this->getDataEntryStats();
        $recentActivities = $this->getRecentActivities();
        $monthlyTrends = $this->getMonthlyTrends();
        $topPerformers = $this->getTopPerformers();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 hidden">
        <!-- Current Month Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Pendapatan Bulan Ini</h3>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($operationalSummary['current']['pendapatan'], 0, ',', '.') }}</p>
                    <p class="text-sm {{ $operationalSummary['changes']['pendapatan'] >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ $operationalSummary['changes']['pendapatan'] >= 0 ? '+' : '' }}{{ number_format($operationalSummary['changes']['pendapatan'], 1) }}% dari bulan lalu
                    </p>
                </div>
            </div>
        </div>

        <!-- Current Month Expenses -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Pengeluaran Bulan Ini</h3>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($operationalSummary['current']['pengeluaran'], 0, ',', '.') }}</p>
                    <p class="text-sm {{ $operationalSummary['changes']['pengeluaran'] <= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ $operationalSummary['changes']['pengeluaran'] >= 0 ? '+' : '' }}{{ number_format($operationalSummary['changes']['pengeluaran'], 1) }}% dari bulan lalu
                    </p>
                </div>
            </div>
        </div>

        <!-- Current Month Patients -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Pasien Bulan Ini</h3>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($operationalSummary['current']['pasien']) }}</p>
                    <p class="text-sm {{ $operationalSummary['changes']['pasien'] >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ $operationalSummary['changes']['pasien'] >= 0 ? '+' : '' }}{{ number_format($operationalSummary['changes']['pasien'], 1) }}% dari bulan lalu
                    </p>
                </div>
            </div>
        </div>

        <!-- Net Income -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 {{ $operationalSummary['current']['net_income'] >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 {{ $operationalSummary['current']['net_income'] >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Net Income Bulan Ini</h3>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($operationalSummary['current']['net_income'], 0, ',', '.') }}</p>
                    <p class="text-sm {{ $operationalSummary['changes']['net_income'] >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ $operationalSummary['changes']['net_income'] >= 0 ? '+' : '' }}{{ number_format($operationalSummary['changes']['net_income'], 1) }}% dari bulan lalu
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Data Entry Progress -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Progress Entry Data Hari Ini</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                            <span>Pendapatan Harian</span>
                            <span>{{ $dataEntryStats['completed']['pendapatan'] }}/{{ $dataEntryStats['targets']['pendapatan'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-green-600 dark:bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $dataEntryStats['targets']['pendapatan'] > 0 ? ($dataEntryStats['completed']['pendapatan'] / $dataEntryStats['targets']['pendapatan']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                            <span>Pengeluaran Harian</span>
                            <span>{{ $dataEntryStats['completed']['pengeluaran'] }}/{{ $dataEntryStats['targets']['pengeluaran'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-red-600 dark:bg-red-500 h-2 rounded-full transition-all duration-300" style="width: {{ $dataEntryStats['targets']['pengeluaran'] > 0 ? ($dataEntryStats['completed']['pengeluaran'] / $dataEntryStats['targets']['pengeluaran']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                            <span>Data Pasien</span>
                            <span>{{ $dataEntryStats['completed']['pasien'] }}/{{ $dataEntryStats['targets']['pasien'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: {{ $dataEntryStats['targets']['pasien'] > 0 ? ($dataEntryStats['completed']['pasien'] / $dataEntryStats['targets']['pasien']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-300">
                            Total Tindakan: <span class="font-medium text-gray-900 dark:text-white">{{ $dataEntryStats['completed']['tindakan'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach(array_slice($recentActivities, 0, 5) as $activity)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            @if($activity['type'] === 'pendapatan')
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center transition-colors duration-200">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @elseif($activity['type'] === 'pengeluaran')
                                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center transition-colors duration-200">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center transition-colors duration-200">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $activity['description'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-300">{{ $activity['created_by'] }} • {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}</p>
                        </div>
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                            @if($activity['amount'] > 0)
                                Rp {{ number_format($activity['amount'], 0, ',', '.') }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 mb-6 transition-all duration-200">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tren 6 Bulan Terakhir</h3>
        </div>
        <div class="p-6">
            <div id="monthlyTrendsChart" style="height: 300px;" class="chart-container"></div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Staff -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Staff Entry Data</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($topPerformers['staff'] as $index => $staff)
                    <div class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-sm font-medium text-blue-600 dark:text-blue-300">
                                {{ $index + 1 }}
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $staff['name'] }}</span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-300">{{ $staff['total'] }} entries</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Procedures -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tindakan Terpopuler</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($topPerformers['procedures'] as $index => $procedure)
                    <div class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-sm font-medium text-green-600 dark:text-green-300">
                                {{ $index + 1 }}
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $procedure['name'] }}</span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-300">{{ $procedure['total'] }} kali</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Dark mode detection and chart theme management
        function detectDarkMode() {
            return document.documentElement.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function getChartTheme() {
            const isDark = detectDarkMode();
            
            return {
                colors: isDark ? 
                    ['#34d399', '#fca5a5', '#93c5fd'] : // Dark mode colors - much brighter variants
                    ['#10B981', '#EF4444', '#3B82F6'],  // Light mode colors
                chart: {
                    background: 'transparent',
                    foreColor: isDark ? '#ffffff' : '#374151'
                },
                grid: {
                    borderColor: isDark ? '#6b7280' : '#F3F4F6',
                    strokeDashArray: 3,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    },
                    axisBorder: {
                        color: isDark ? '#6b7280' : '#e5e7eb'
                    },
                    axisTicks: {
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
                        colors: isDark ? '#ffffff' : '#374151',
                        useSeriesColors: false
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    style: {
                        fontSize: '12px'
                    }
                }
            };
        }

        // Monthly Trends Chart with dynamic theme
        function createMonthlyChart() {
            const theme = getChartTheme();
            
            const monthlyOptions = {
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    background: theme.chart.background,
                    foreColor: theme.chart.foreColor,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
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
                    labels: {
                        style: theme.xaxis.labels.style
                    },
                    axisBorder: {
                        show: true,
                        color: theme.xaxis.axisBorder.color
                    },
                    axisTicks: {
                        show: true,
                        color: theme.xaxis.axisTicks.color
                    }
                },
                yaxis: {
                    labels: {
                        style: theme.yaxis.labels.style,
                        formatter: function (value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'K';
                            }
                            return value;
                        }
                    }
                },
                colors: theme.colors,
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    lineCap: 'round'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    floating: false,
                    offsetY: -10,
                    labels: theme.legend.labels,
                    markers: {
                        width: 8,
                        height: 8,
                        radius: 4
                    }
                },
                grid: theme.grid,
                tooltip: {
                    theme: theme.tooltip.theme,
                    style: theme.tooltip.style,
                    shared: true,
                    intersect: false,
                    x: {
                        show: true
                    },
                    y: {
                        formatter: function (value, { seriesIndex }) {
                            if (seriesIndex === 0 || seriesIndex === 1) { // Revenue and expenses
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            } else { // Patients
                                return value + ' pasien';
                            }
                        }
                    }
                },
                markers: {
                    size: 4,
                    colors: theme.colors,
                    strokeColors: detectDarkMode() ? '#1f2937' : '#ffffff',
                    strokeWidth: 2,
                    hover: {
                        size: 6
                    }
                }
            };

            return new ApexCharts(document.querySelector("#monthlyTrendsChart"), monthlyOptions);
        }

        // Initialize chart
        let filamentMonthlyChart = createMonthlyChart();
        filamentMonthlyChart.render();

        // Theme change detection and chart update
        function updateChartTheme() {
            if (filamentMonthlyChart) {
                filamentMonthlyChart.destroy();
                filamentMonthlyChart = createMonthlyChart();
                filamentMonthlyChart.render();
            }
        }

        // Listen for theme changes
        const filamentDashboardObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'class' && 
                    mutation.target === document.documentElement) {
                    
                    // Debounce the update to avoid too frequent redraws
                    clearTimeout(window.chartUpdateTimeout);
                    window.chartUpdateTimeout = setTimeout(updateChartTheme, 100);
                }
            });
        });

        // Start observing theme changes
        filamentDashboardObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            setTimeout(updateChartTheme, 100);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (filamentDashboardObserver) filamentDashboardObserver.disconnect();
            if (filamentMonthlyChart) filamentMonthlyChart.destroy();
        });
    </script>
    @endpush
</x-filament-panels::page>