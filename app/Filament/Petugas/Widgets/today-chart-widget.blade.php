<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            @if(isset($error) && $error)
                <div class="flex items-center justify-center p-8">
                    <div class="text-center">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="w-12 h-12 mx-auto text-danger-500 dark:text-danger-400 mb-4"
                        />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ $error }}
                        </h3>
                        <x-filament::button
                            color="primary"
                            size="sm"
                            wire:poll.300s
                        >
                            Memuat ulang...
                        </x-filament::button>
                    </div>
                </div>
            @else
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $chart_data['title'] }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Aktivitas pasien sepanjang hari
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 bg-success-500 dark:bg-success-400 rounded-full animate-pulse"></span>
                            <span>{{ $last_updated }}</span>
                        </span>
                    </div>
                </div>

                <!-- Chart Container -->
                <x-filament::card>
                    <div class="p-6">
                        <div id="todayChart" style="height: 300px;" class="chart-container mb-4"></div>
                        
                        <!-- Chart Summary Stats -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $chart_summary['total_patients'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Total Pasien</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $chart_summary['average_per_hour'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Rata-rata/Jam</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ $chart_data['peak_hour'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Jam Tersibuk</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {{ $chart_summary['remaining_hours'] }}h
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Tersisa</div>
                            </div>
                        </div>
                    </div>
                </x-filament::card>

                <!-- Performance Indicator -->
                <x-filament::card>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Performance Rating
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Berdasarkan target {{ $chart_summary['working_hours'] }} jam kerja
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold {{ $chart_summary['performance_rating'] >= 80 ? 'text-success-600 dark:text-success-400' : ($chart_summary['performance_rating'] >= 60 ? 'text-warning-600 dark:text-warning-400' : 'text-danger-600 dark:text-danger-400') }}">
                                    {{ $chart_summary['performance_rating'] }}%
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Performance Score
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance Progress Bar -->
                        <div class="relative">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div 
                                    class="h-4 rounded-full transition-all duration-500 {{ $chart_summary['performance_rating'] >= 80 ? 'bg-success-500 dark:bg-success-400' : ($chart_summary['performance_rating'] >= 60 ? 'bg-warning-500 dark:bg-warning-400' : 'bg-danger-500 dark:bg-danger-400') }}"
                                    style="width: {{ min(100, $chart_summary['performance_rating']) }}%"
                                ></div>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-medium text-white mix-blend-difference">
                                    {{ round($chart_summary['performance_rating']) }}%
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                            <span>0%</span>
                            <span>Target: 80%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </x-filament::card>
            @endif
        </div>
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark mode detection for charts
            function detectDarkMode() {
                return document.documentElement.classList.contains('dark') || 
                       window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            function getChartTheme() {
                const isDark = detectDarkMode();
                
                return {
                    colors: isDark ? ['#10b981'] : ['#10B981'], // Green color
                    chart: {
                        background: 'transparent',
                        foreColor: isDark ? '#e5e7eb' : '#374151'
                    },
                    grid: {
                        borderColor: isDark ? '#4b5563' : '#f3f4f6',
                        strokeDashArray: 3,
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: isDark ? '#9ca3af' : '#6b7280'
                            }
                        },
                        axisBorder: {
                            color: isDark ? '#4b5563' : '#e5e7eb'
                        },
                        axisTicks: {
                            color: isDark ? '#4b5563' : '#e5e7eb'
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: isDark ? '#9ca3af' : '#6b7280'
                            }
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light'
                    }
                };
            }

            function createTodayChart() {
                const theme = getChartTheme();
                const chartData = @json($chart_data);
                
                const options = {
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: {
                            show: false
                        },
                        background: theme.chart.background,
                        foreColor: theme.chart.foreColor,
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    series: [{
                        name: chartData.datasets[0].label,
                        data: chartData.datasets[0].data
                    }],
                    xaxis: {
                        categories: chartData.labels,
                        labels: {
                            style: theme.xaxis.labels.style,
                            rotate: -45,
                            rotateAlways: false
                        },
                        axisBorder: {
                            show: true,
                            color: theme.xaxis.axisBorder.color
                        },
                        axisTicks: {
                            show: true,
                            color: theme.xaxis.axisTicks.color
                        },
                        title: {
                            text: 'Jam',
                            style: {
                                color: theme.chart.foreColor
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: theme.yaxis.labels.style,
                            formatter: function (value) {
                                return Math.round(value);
                            }
                        },
                        title: {
                            text: 'Jumlah Pasien',
                            style: {
                                color: theme.chart.foreColor
                            }
                        }
                    },
                    colors: theme.colors,
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            columnWidth: '60%',
                            distributed: false
                        }
                    },
                    fill: {
                        opacity: 0.8,
                        type: 'gradient',
                        gradient: {
                            shade: detectDarkMode() ? 'dark' : 'light',
                            type: 'vertical',
                            shadeIntensity: 0.5,
                            gradientToColors: undefined,
                            inverseColors: false,
                            opacityFrom: 0.85,
                            opacityTo: 0.5,
                            stops: [0, 100]
                        }
                    },
                    grid: theme.grid,
                    tooltip: {
                        theme: theme.tooltip.theme,
                        y: {
                            formatter: function (value) {
                                return value + ' pasien';
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (value) {
                            return value > 0 ? value : '';
                        },
                        style: {
                            fontSize: '12px',
                            colors: [theme.chart.foreColor]
                        }
                    }
                };

                return new ApexCharts(document.querySelector("#todayChart"), options);
            }

            // Initialize chart
            let todayChart = createTodayChart();
            todayChart.render();

            // Theme change detection
            function updateChartTheme() {
                if (todayChart) {
                    todayChart.destroy();
                    todayChart = createTodayChart();
                    todayChart.render();
                }
            }

            // Listen for theme changes
            const todayChartObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && 
                        mutation.attributeName === 'class' && 
                        mutation.target === document.documentElement) {
                        
                        clearTimeout(window.chartUpdateTimeout);
                        window.chartUpdateTimeout = setTimeout(updateChartTheme, 100);
                    }
                });
            });

            todayChartObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                setTimeout(updateChartTheme, 100);
            });

            // Cleanup
            window.addEventListener('beforeunload', function() {
                if (todayChartObserver) todayChartObserver.disconnect();
                if (todayChart) todayChart.destroy();
            });
        });
    </script>
    @endpush
</x-filament-widgets::widget>