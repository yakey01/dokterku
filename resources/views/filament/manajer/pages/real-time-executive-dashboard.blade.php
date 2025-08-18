<x-filament-panels::page>
    @php
        $financialKPIs = $this->getFinancialKPIs();
        $strategicMetrics = $this->getStrategicMetrics();
        $staffPerformance = $this->getStaffPerformance();
        $approvalMetrics = $this->getApprovalMetrics();
        $departmentScores = $this->getDepartmentScores();
        $financialTrends = $this->getFinancialTrends();
        $topPerformers = $this->getTopPerformers();
        $criticalAlerts = $this->getCriticalAlerts();
    @endphp

    <!-- Real-Time Alert Banner -->
    @if($criticalAlerts['overdue_goals'] > 0 || $criticalAlerts['urgent_approvals'] > 0)
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 rounded-lg">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">🚨 Critical Alerts Requiring Immediate Attention</h3>
                <div class="mt-2 text-sm text-red-700">
                    @if($criticalAlerts['overdue_goals'] > 0)
                        • {{ $criticalAlerts['overdue_goals'] }} overdue strategic goals
                    @endif
                    @if($criticalAlerts['urgent_approvals'] > 0)
                        • {{ $criticalAlerts['urgent_approvals'] }} urgent approvals pending
                    @endif
                    @if($criticalAlerts['high_value_pending'] > 0)
                        • {{ $criticalAlerts['high_value_pending'] }} high-value approvals (>500K)
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Executive KPI Dashboard Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Financial Health -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">💰 Monthly Revenue</p>
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                        Rp {{ number_format($financialKPIs['current']['revenue'], 0, ',', '.') }}
                    </p>
                    <p class="text-sm {{ $financialKPIs['changes']['revenue'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $financialKPIs['changes']['revenue'] >= 0 ? '📈' : '📉' }} 
                        {{ $financialKPIs['changes']['revenue'] >= 0 ? '+' : '' }}{{ number_format($financialKPIs['changes']['revenue'], 1) }}% vs last month
                    </p>
                </div>
                <div class="p-3 bg-green-500 rounded-full">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="bg-gradient-to-br {{ $financialKPIs['current']['net_profit'] >= 0 ? 'from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20' : 'from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20' }} rounded-xl p-6 border {{ $financialKPIs['current']['net_profit'] >= 0 ? 'border-blue-200 dark:border-blue-700' : 'border-red-200 dark:border-red-700' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $financialKPIs['current']['net_profit'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">💹 Net Profit</p>
                    <p class="text-2xl font-bold {{ $financialKPIs['current']['net_profit'] >= 0 ? 'text-blue-900 dark:text-blue-100' : 'text-red-900 dark:text-red-100' }}">
                        Rp {{ number_format($financialKPIs['current']['net_profit'], 0, ',', '.') }}
                    </p>
                    <p class="text-sm {{ $financialKPIs['current']['net_profit'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        Margin: {{ number_format($financialKPIs['current']['profit_margin'], 1) }}%
                    </p>
                </div>
                <div class="p-3 {{ $financialKPIs['current']['net_profit'] >= 0 ? 'bg-blue-500' : 'bg-red-500' }} rounded-full">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Patient Metrics -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">👥 Patients This Month</p>
                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                        {{ number_format($financialKPIs['current']['patients']) }}
                    </p>
                    <p class="text-sm {{ $financialKPIs['changes']['patients'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $financialKPIs['changes']['patients'] >= 0 ? '📈' : '📉' }}
                        {{ $financialKPIs['changes']['patients'] >= 0 ? '+' : '' }}{{ number_format($financialKPIs['changes']['patients'], 1) }}%
                    </p>
                </div>
                <div class="p-3 bg-purple-500 rounded-full">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl p-6 border border-orange-200 dark:border-orange-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600 dark:text-orange-400">⏳ Pending Approvals</p>
                    <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                        {{ $approvalMetrics['pending_count'] }}
                    </p>
                    <p class="text-sm text-red-600">
                        🚨 {{ $approvalMetrics['urgent_count'] }} urgent
                    </p>
                </div>
                <div class="p-3 bg-orange-500 rounded-full">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Strategic Goals & Department Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Strategic Goals Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🎯 Strategic Goals Progress</h3>
                <span class="text-sm text-gray-500">{{ $strategicMetrics['active_goals'] }} Active</span>
            </div>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Average Progress</span>
                    <span class="text-lg font-bold text-green-600">{{ number_format($strategicMetrics['average_progress'], 1) }}%</span>
                </div>
                
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $strategicMetrics['average_progress'] }}%"></div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 mt-4">
                    <div class="text-center">
                        <p class="text-xl font-bold text-green-600">{{ $strategicMetrics['completed_this_month'] }}</p>
                        <p class="text-xs text-gray-500">✅ Completed</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-orange-600">{{ $strategicMetrics['active_goals'] }}</p>
                        <p class="text-xs text-gray-500">🟢 Active</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-red-600">{{ $strategicMetrics['overdue_goals'] }}</p>
                        <p class="text-xs text-gray-500">⚠️ Overdue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Scores -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Department Performance Scores</h3>
            
            <div class="space-y-4">
                @foreach([
                    'medical' => ['🏥 Medical', 'blue'],
                    'administrative' => ['📋 Administrative', 'green'], 
                    'financial' => ['💰 Financial', 'yellow'],
                    'support' => ['🛠️ Support', 'purple']
                ] as $dept => $info)
                    @php $score = $departmentScores[$dept] ?? 0; @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $info[0] }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-{{ $info[1] }}-500 h-2 rounded-full transition-all duration-500" style="width: {{ $score }}%"></div>
                            </div>
                            <span class="text-sm font-bold text-{{ $info[1] }}-600">{{ $score }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Financial Trends Chart & Top Performers -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Financial Trends Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📈 Financial Trends - Real Data</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">6-month performance based on actual transactions</p>
            </div>
            <div class="p-6">
                <div id="financialTrendsChart" style="height: 300px;" class="chart-container"></div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">⭐ Top Performers</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">This month's leaders</p>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">👨‍⚕️ Top Doctors</h4>
                    @foreach($topPerformers['top_doctors'] as $index => $performer)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <div class="flex items-center space-x-2">
                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-xs font-bold text-white">
                                {{ $index + 1 }}
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performer['name'] }}</span>
                        </div>
                        <span class="text-sm text-blue-600 dark:text-blue-400 font-semibold">{{ $performer['count'] }} procedures</span>
                    </div>
                    @endforeach

                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-4">👩‍⚕️ Top Paramedis</h4>
                    @foreach($topPerformers['top_paramedis'] as $index => $performer)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <div class="flex items-center space-x-2">
                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-xs font-bold text-white">
                                {{ $index + 1 }}
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performer['name'] }}</span>
                        </div>
                        <span class="text-sm text-green-600 dark:text-green-400 font-semibold">{{ $performer['count'] }} activities</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Performance & Operational Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Staff Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👥 Staff Performance Overview</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $staffPerformance['total_staff'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Staff</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600">{{ $staffPerformance['total_doctors'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Doctors</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">{{ number_format($staffPerformance['attendance_rate'], 1) }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Attendance Rate</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($staffPerformance['avg_procedures_per_doctor'], 1) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Procedures/Doctor</p>
                </div>
            </div>
        </div>

        <!-- Approval Workflow Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">✅ Approval Workflow Status</h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">⏳ Pending Approvals</span>
                    <span class="text-lg font-bold text-yellow-600">{{ $approvalMetrics['pending_count'] }}</span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <span class="text-sm font-medium text-red-800 dark:text-red-200">🚨 Urgent Priority</span>
                    <span class="text-lg font-bold text-red-600">{{ $approvalMetrics['urgent_count'] }}</span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">✅ Approved Today</span>
                    <span class="text-lg font-bold text-blue-600">{{ $approvalMetrics['approved_today'] }}</span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <span class="text-sm font-medium text-purple-800 dark:text-purple-200">💰 Pending Value</span>
                    <span class="text-lg font-bold text-purple-600">Rp {{ number_format($approvalMetrics['total_pending_value'], 0, ',', '.') }}</span>
                </div>
                
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-300">⏱️ Avg Approval Time</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $approvalMetrics['avg_approval_time_days'] }} days</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Dark mode detection
        function detectDarkMode() {
            return document.documentElement.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function getChartTheme() {
            const isDark = detectDarkMode();
            
            return {
                colors: ['#10B981', '#EF4444', '#3B82F6', '#8B5CF6'],
                chart: {
                    background: 'transparent',
                    foreColor: isDark ? '#ffffff' : '#374151'
                },
                grid: {
                    borderColor: isDark ? '#6b7280' : '#F3F4F6',
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    }
                }
            };
        }

        // Financial Trends Chart with Real Data
        function createFinancialChart() {
            const theme = getChartTheme();
            
            // Real financial data from server
            const financialData = {
                months: @json($financialTrends['months'] ?? []),
                revenue: @json($financialTrends['revenue'] ?? []),
                expenses: @json($financialTrends['expenses'] ?? []),
                netProfit: @json($financialTrends['net_profit'] ?? []),
                profitMargin: @json($financialTrends['profit_margin'] ?? [])
            };
            
            const options = {
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: false },
                    background: theme.chart.background,
                    foreColor: theme.chart.foreColor,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                series: [
                    {
                        name: 'Revenue',
                        data: financialData.revenue
                    },
                    {
                        name: 'Expenses', 
                        data: financialData.expenses
                    },
                    {
                        name: 'Net Profit',
                        data: financialData.netProfit
                    }
                ],
                xaxis: {
                    categories: financialData.months,
                    labels: {
                        style: theme.xaxis.labels.style
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
                    width: 3
                },
                grid: theme.grid,
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                },
                legend: {
                    position: 'top'
                }
            };

            return new ApexCharts(document.querySelector("#financialTrendsChart"), options);
        }

        // Initialize chart with error handling
        let financialChart;
        try {
            financialChart = createFinancialChart();
            if (financialChart && typeof financialChart.render === 'function') {
                financialChart.render();
            }
        } catch (error) {
            console.error('Error initializing chart:', error);
            const chartContainer = document.querySelector("#financialTrendsChart");
            if (chartContainer) {
                chartContainer.innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500"><p>📊 Chart loading...</p></div>';
            }
        }

        // Real-time updates every 30 seconds
        setInterval(function() {
            // Refresh page to get latest data
            window.location.reload();
        }, 30000);

        // Visual feedback for real-time updates
        function showUpdateIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm font-medium';
            indicator.innerHTML = '🔄 Dashboard Updated';
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                indicator.remove();
            }, 2000);
        }

        // Show update indicator on page load
        showUpdateIndicator();
    </script>
    @endpush
</x-filament-panels::page>