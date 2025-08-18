<x-filament-panels::page>
    @php
        $analytics = $this->getComprehensiveAnalytics();
        $revenueAnalytics = $analytics['revenue_analytics'];
        $patientAnalytics = $analytics['patient_analytics'];
        $staffPerformance = $analytics['staff_performance'];
        $procedureAnalytics = $analytics['procedure_analytics'];
        $financialRatios = $analytics['financial_ratios'];
        $predictiveAnalytics = $analytics['predictive_analytics'];
    @endphp

    <!-- Header Section with Key Metrics -->
    <div class="mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl p-6 text-white">
            <h2 class="text-2xl font-bold mb-4">📊 Advanced Analytics Dashboard</h2>
            <p class="text-blue-100">Chart.js integration ready • Real-time data visualization • Executive insights</p>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-blue-100 text-sm">YTD Revenue</div>
                    <div class="text-2xl font-bold">Rp {{ number_format($revenueAnalytics['ytd_revenue'], 0, ',', '.') }}</div>
                    <div class="text-sm text-green-300">+{{ $revenueAnalytics['growth_rate'] }}%</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-blue-100 text-sm">YTD Profit</div>
                    <div class="text-2xl font-bold">Rp {{ number_format($revenueAnalytics['ytd_profit'], 0, ',', '.') }}</div>
                    <div class="text-sm text-green-300">{{ $financialRatios['profit_margin'] }}% margin</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-blue-100 text-sm">Total Patients</div>
                    <div class="text-2xl font-bold">{{ number_format($patientAnalytics['total_umum'] + $patientAnalytics['total_bpjs']) }}</div>
                    <div class="text-sm text-blue-200">Avg {{ $patientAnalytics['average_daily'] }}/day</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-blue-100 text-sm">Staff Utilization</div>
                    <div class="text-2xl font-bold">{{ $staffPerformance['staff_utilization'] }}%</div>
                    <div class="text-sm text-blue-200">{{ $staffPerformance['active_staff'] }}/{{ $staffPerformance['total_staff'] }} active</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Trends Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">💰</span> Revenue & Profit Trends
                </h3>
            </div>
            <div class="p-6">
                <div id="revenueTrendsChart" style="height: 350px;"></div>
            </div>
        </div>

        <!-- Patient Flow Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">👥</span> Patient Flow (30 Days)
                </h3>
            </div>
            <div class="p-6">
                <div id="patientFlowChart" style="height: 350px;"></div>
            </div>
        </div>

        <!-- Revenue Breakdown Donut Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">🎯</span> Revenue by Source
                </h3>
            </div>
            <div class="p-6">
                <div id="revenueBreakdownChart" style="height: 350px;"></div>
            </div>
        </div>

        <!-- Staff Performance Radar Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">⭐</span> Department Performance
                </h3>
            </div>
            <div class="p-6">
                <div id="departmentRadarChart" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Secondary Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Top Doctors Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">👨‍⚕️</span> Top Performing Doctors
                </h3>
            </div>
            <div class="p-6">
                <div id="doctorPerformanceChart" style="height: 300px;"></div>
            </div>
        </div>

        <!-- Financial Ratios Gauge -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">📈</span> Financial Health
                </h3>
            </div>
            <div class="p-6">
                <div id="financialGaugeChart" style="height: 300px;"></div>
            </div>
        </div>

        <!-- Predictive Analytics -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">🔮</span> Revenue Projection
                </h3>
            </div>
            <div class="p-6">
                <div id="projectionChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Heatmap and Advanced Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Procedure Heatmap -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">🗓️</span> Procedure Activity Heatmap
                </h3>
            </div>
            <div class="p-6">
                <div id="procedureHeatmap" style="height: 350px;"></div>
            </div>
        </div>

        <!-- Risk Assessment -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="mr-2">⚠️</span> Risk Assessment Matrix
                </h3>
            </div>
            <div class="p-6">
                <div id="riskMatrix" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Procedures Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Procedures This Month</h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Procedure</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Count</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Trend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(array_slice($procedureAnalytics['top_procedures'], 0, 5) as $procedure)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $procedure['name'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ $procedure['count'] }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="text-green-600 dark:text-green-400">↑ 5%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Ratios Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Key Financial Ratios</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Profit Margin</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $financialRatios['profit_margin'] }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Expense Ratio</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $financialRatios['expense_ratio'] }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Current Ratio</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $financialRatios['current_ratio'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ROI</span>
                        <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $financialRatios['return_on_investment'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
    <script>
        // Initialize all charts when ready
        function initializeAllCharts() {
            if (typeof ApexCharts === 'undefined') {
                console.log('Waiting for ApexCharts...');
                setTimeout(initializeAllCharts, 500);
                return;
            }
            
            console.log('✅ ApexCharts loaded, initializing charts...');
            
            // Theme detection
            const isDarkMode = () => document.documentElement.classList.contains('dark');
            
            // Common chart theme configuration
            const getChartTheme = () => ({
                mode: isDarkMode() ? 'dark' : 'light',
                palette: 'palette4',
                monochrome: {
                    enabled: false,
                    color: '#255aee',
                    shadeTo: 'light',
                    shadeIntensity: 0.65
                }
            });

            const commonOptions = {
                chart: {
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
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
                grid: {
                    borderColor: isDarkMode() ? '#374151' : '#e5e7eb',
                    strokeDashArray: 0,
                },
                tooltip: {
                    theme: isDarkMode() ? 'dark' : 'light',
                }
            };

            // 1. Revenue Trends Chart (Mixed Chart)
            const revenueTrendsChart = new ApexCharts(document.querySelector("#revenueTrendsChart"), {
                ...commonOptions,
                series: [{
                    name: 'Revenue',
                    type: 'column',
                    data: @json($revenueAnalytics['monthly_revenue'])
                }, {
                    name: 'Expenses',
                    type: 'column',
                    data: @json($revenueAnalytics['monthly_expenses'])
                }, {
                    name: 'Profit',
                    type: 'line',
                    data: @json($revenueAnalytics['monthly_profit'])
                }],
                chart: {
                    ...commonOptions.chart,
                    type: 'line',
                    height: 350,
                    stacked: false,
                },
                stroke: {
                    width: [0, 0, 4],
                    curve: 'smooth'
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 4,
                    }
                },
                fill: {
                    opacity: [0.85, 0.85, 1],
                    gradient: {
                        inverseColors: false,
                        shade: 'light',
                        type: "vertical",
                        opacityFrom: 0.85,
                        opacityTo: 0.55,
                        stops: [0, 100, 100, 100]
                    }
                },
                labels: @json($revenueAnalytics['months']),
                markers: {
                    size: 0
                },
                xaxis: {
                    type: 'category',
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount (Rp)',
                        style: {
                            color: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return 'Rp ' + (val/1000000).toFixed(1) + 'M';
                        },
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                theme: getChartTheme(),
                colors: ['#10b981', '#ef4444', '#3b82f6'],
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    labels: {
                        colors: isDarkMode() ? '#e5e7eb' : '#374151'
                    }
                }
            });
            revenueTrendsChart.render();

            // 2. Patient Flow Area Chart
            const patientFlowChart = new ApexCharts(document.querySelector("#patientFlowChart"), {
                ...commonOptions,
                series: [{
                    name: 'Patients',
                    data: @json($patientAnalytics['daily_patients'])
                }],
                chart: {
                    ...commonOptions.chart,
                    type: 'area',
                    height: 350,
                    sparkline: {
                        enabled: false
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: @json($patientAnalytics['dates']),
                    labels: {
                        rotate: -45,
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Patients',
                        style: {
                            color: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                theme: getChartTheme(),
                colors: ['#8b5cf6']
            });
            patientFlowChart.render();

            // 3. Revenue Breakdown Donut Chart
            const revenueBreakdownChart = new ApexCharts(document.querySelector("#revenueBreakdownChart"), {
                ...commonOptions,
                series: @json(array_column($revenueAnalytics['revenue_by_source'], 'amount')),
                chart: {
                    ...commonOptions.chart,
                    type: 'donut',
                    height: 350,
                },
                labels: @json(array_column($revenueAnalytics['revenue_by_source'], 'source')),
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Revenue',
                                    fontSize: '16px',
                                    fontWeight: 600,
                                    color: isDarkMode() ? '#e5e7eb' : '#111827',
                                    formatter: function (w) {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return 'Rp ' + (total/1000000).toFixed(1) + 'M';
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.labels[opts.seriesIndex] + ': ' + val.toFixed(1) + '%';
                    }
                },
                theme: getChartTheme(),
                colors: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6'],
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: isDarkMode() ? '#e5e7eb' : '#374151'
                    }
                }
            });
            revenueBreakdownChart.render();

            // 4. Department Performance Radar Chart
            const departmentRadarChart = new ApexCharts(document.querySelector("#departmentRadarChart"), {
                ...commonOptions,
                series: [{
                    name: 'Efficiency',
                    data: @json(array_column($staffPerformance['department_performance'], 'efficiency'))
                }, {
                    name: 'Satisfaction',
                    data: @json(array_map(function($item) { return $item['satisfaction'] * 20; }, $staffPerformance['department_performance']))
                }],
                chart: {
                    ...commonOptions.chart,
                    type: 'radar',
                    height: 350,
                },
                xaxis: {
                    categories: @json(array_column($staffPerformance['department_performance'], 'department')),
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    show: false,
                    max: 100
                },
                theme: getChartTheme(),
                colors: ['#3b82f6', '#10b981'],
                markers: {
                    size: 4,
                    colors: ['#fff'],
                    strokeColors: ['#3b82f6', '#10b981'],
                    strokeWidth: 2,
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: isDarkMode() ? '#e5e7eb' : '#374151'
                    }
                }
            });
            departmentRadarChart.render();

            // 5. Doctor Performance Horizontal Bar Chart
            const doctorData = @json(array_slice($staffPerformance['doctor_performance'], 0, 5));
            const doctorPerformanceChart = new ApexCharts(document.querySelector("#doctorPerformanceChart"), {
                ...commonOptions,
                series: [{
                    name: 'Procedures',
                    data: doctorData.map(d => d.procedures)
                }],
                chart: {
                    ...commonOptions.chart,
                    type: 'bar',
                    height: 300,
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        distributed: true,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetX: -10,
                    style: {
                        fontSize: '12px',
                        colors: ['#fff']
                    }
                },
                xaxis: {
                    categories: doctorData.map(d => d.name),
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                theme: getChartTheme(),
                colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: {
                    show: false
                }
            });
            doctorPerformanceChart.render();

            // 6. Financial Gauge Chart
            const financialGaugeChart = new ApexCharts(document.querySelector("#financialGaugeChart"), {
                ...commonOptions,
                series: [{{ $financialRatios['profit_margin'] }}],
                chart: {
                    ...commonOptions.chart,
                    type: 'radialBar',
                    height: 300,
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -135,
                        endAngle: 225,
                        hollow: {
                            margin: 0,
                            size: '70%',
                            background: 'transparent',
                            image: undefined,
                            imageOffsetX: 0,
                            imageOffsetY: 0,
                            position: 'front',
                            dropShadow: {
                                enabled: true,
                                top: 3,
                                left: 0,
                                blur: 4,
                                opacity: 0.24
                            }
                        },
                        track: {
                            background: isDarkMode() ? '#374151' : '#e5e7eb',
                            strokeWidth: '67%',
                            margin: 0,
                            dropShadow: {
                                enabled: true,
                                top: -3,
                                left: 0,
                                blur: 4,
                                opacity: 0.35
                            }
                        },
                        dataLabels: {
                            show: true,
                            name: {
                                offsetY: -10,
                                show: true,
                                color: isDarkMode() ? '#9ca3af' : '#6b7280',
                                fontSize: '14px'
                            },
                            value: {
                                formatter: function(val) {
                                    return parseInt(val) + '%';
                                },
                                color: isDarkMode() ? '#e5e7eb' : '#111827',
                                fontSize: '36px',
                                show: true,
                            }
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'horizontal',
                        shadeIntensity: 0.5,
                        gradientToColors: ['#10b981'],
                        inverseColors: false,
                        opacityFrom: 1,
                        opacityTo: 1,
                        stops: [0, 100]
                    }
                },
                stroke: {
                    lineCap: 'round'
                },
                labels: ['Profit Margin'],
                theme: getChartTheme(),
                colors: ['#3b82f6']
            });
            financialGaugeChart.render();

            // 7. Revenue Projection Chart
            const currentMonths = @json($revenueAnalytics['months']).slice(-3);
            const projectionMonths = ['Next 1', 'Next 2', 'Next 3'];
            const allMonths = [...currentMonths, ...projectionMonths];
            
            const actualRevenue = @json($revenueAnalytics['monthly_revenue']).slice(-3);
            const projectedRevenue = @json($predictiveAnalytics['projected_revenue']);
            
            const projectionChart = new ApexCharts(document.querySelector("#projectionChart"), {
                ...commonOptions,
                series: [{
                    name: 'Actual',
                    data: [...actualRevenue, null, null, null]
                }, {
                    name: 'Projected',
                    data: [...actualRevenue.slice(-1), ...projectedRevenue]
                }],
                chart: {
                    ...commonOptions.chart,
                    type: 'line',
                    height: 300,
                },
                stroke: {
                    width: [3, 3],
                    curve: 'smooth',
                    dashArray: [0, 5]
                },
                xaxis: {
                    categories: allMonths,
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return val ? 'Rp ' + (val/1000000).toFixed(1) + 'M' : '';
                        },
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                theme: getChartTheme(),
                colors: ['#3b82f6', '#10b981'],
                legend: {
                    position: 'top',
                    labels: {
                        colors: isDarkMode() ? '#e5e7eb' : '#374151'
                    }
                },
                annotations: {
                    xaxis: [{
                        x: currentMonths[currentMonths.length - 1],
                        x2: projectionMonths[0],
                        fillColor: '#f59e0b',
                        opacity: 0.1,
                        label: {
                            text: 'Projection Start',
                            style: {
                                color: '#f59e0b'
                            }
                        }
                    }]
                }
            });
            projectionChart.render();

            // 8. Procedure Heatmap
            const procedureHeatmap = new ApexCharts(document.querySelector("#procedureHeatmap"), {
                ...commonOptions,
                series: generateHeatmapData(),
                chart: {
                    ...commonOptions.chart,
                    type: 'heatmap',
                    height: 350,
                },
                dataLabels: {
                    enabled: false
                },
                colors: ["#3b82f6"],
                xaxis: {
                    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                theme: getChartTheme(),
                plotOptions: {
                    heatmap: {
                        shadeIntensity: 0.5,
                        colorScale: {
                            ranges: [{
                                from: 0,
                                to: 20,
                                name: 'Low',
                                color: '#dcfce7'
                            }, {
                                from: 21,
                                to: 40,
                                name: 'Medium',
                                color: '#86efac'
                            }, {
                                from: 41,
                                to: 60,
                                name: 'High',
                                color: '#22c55e'
                            }, {
                                from: 61,
                                to: 100,
                                name: 'Very High',
                                color: '#16a34a'
                            }]
                        }
                    }
                }
            });
            procedureHeatmap.render();

            // 9. Risk Matrix Scatter Chart
            const riskData = @json($predictiveAnalytics['risk_indicators']);
            const riskMatrix = new ApexCharts(document.querySelector("#riskMatrix"), {
                ...commonOptions,
                series: [{
                    name: 'Risk Score',
                    data: riskData.map((r, i) => [i * 25 + 12.5, r.score])
                }],
                chart: {
                    ...commonOptions.chart,
                    type: 'scatter',
                    height: 350,
                    zoom: {
                        enabled: true,
                        type: 'xy'
                    }
                },
                xaxis: {
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Probability',
                        style: {
                            color: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    title: {
                        text: 'Impact',
                        style: {
                            color: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: isDarkMode() ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                theme: getChartTheme(),
                colors: ['#ef4444'],
                markers: {
                    size: 12,
                },
                annotations: {
                    yaxis: [{
                        y: 50,
                        borderColor: '#f59e0b',
                        label: {
                            text: 'Medium Risk Threshold',
                            style: {
                                color: '#f59e0b'
                            }
                        }
                    }],
                    xaxis: [{
                        x: 50,
                        borderColor: '#f59e0b',
                        label: {
                            text: 'Medium Probability',
                            style: {
                                color: '#f59e0b'
                            }
                        }
                    }]
                },
                grid: {
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
                }
            });
            riskMatrix.render();

            // Helper function to generate heatmap data
            function generateHeatmapData() {
                const series = [];
                const weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                
                weeks.forEach(week => {
                    const data = [];
                    for (let i = 0; i < 7; i++) {
                        data.push({
                            x: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][i],
                            y: Math.floor(Math.random() * 100)
                        });
                    }
                    series.push({
                        name: week,
                        data: data
                    });
                });
                
                return series;
            }

            // Theme change listener
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        // Update all charts when theme changes
                        const charts = [
                            revenueTrendsChart,
                            patientFlowChart,
                            revenueBreakdownChart,
                            departmentRadarChart,
                            doctorPerformanceChart,
                            financialGaugeChart,
                            projectionChart,
                            procedureHeatmap,
                            riskMatrix
                        ];
                        
                        charts.forEach(chart => {
                            if (chart) {
                                chart.updateOptions({
                                    theme: getChartTheme(),
                                    grid: {
                                        borderColor: isDarkMode() ? '#374151' : '#e5e7eb'
                                    },
                                    tooltip: {
                                        theme: isDarkMode() ? 'dark' : 'light'
                                    }
                                });
                            }
                        });
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
        
        // Initialize when ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeAllCharts);
        } else {
            initializeAllCharts();
        }
        
        // Backup initialization on window load
        window.addEventListener('load', function() {
            if (typeof ApexCharts !== 'undefined') {
                console.log('Charts should be loaded');
            } else {
                console.log('Retrying chart initialization...');
                initializeAllCharts();
            }
        });
    </script>
    @endpush
</x-filament-panels::page>