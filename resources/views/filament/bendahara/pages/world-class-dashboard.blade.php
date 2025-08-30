<x-filament-panels::page>
    <!-- Single Root Element Container for Livewire Compatibility -->
    <div class="bendahara-dashboard-root">
        @php
            $financial = $this->getFinancialSummary();
            $validation = $this->getValidationMetrics();
            $trends = $this->getMonthlyTrends();
            $activities = $this->getRecentActivities();
        @endphp
        
        <!-- DEBUG: Show data for troubleshooting -->
        @if(config('app.debug'))
            <!-- Debug Info - Only visible in development -->
            <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded-lg text-sm">
                <strong>Debug Info:</strong>
                Revenue: Rp {{ number_format($financial['current']['revenue'], 0, ',', '.') }} |
                Expenses: Rp {{ number_format($financial['current']['expenses'], 0, ',', '.') }} |
                Validation: {{ $validation['total_approved'] }} approved
            </div>
        @endif
        
        <!-- Minimalist World-Class Dashboard - SaaS Inspired -->
        <div class="space-y-8">
        
        <!-- Key Performance Metrics - Modern SaaS Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <!-- Revenue Card - Modern SaaS Design -->
            <div class="bendahara-stats-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl p-6 border border-gray-700 hover:shadow-2xl transition-all duration-500 hover:scale-[1.02] hover:-translate-y-2">
                <!-- Subtle background pattern -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/20 to-transparent"></div>
                </div>
                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center group-hover:bg-emerald-500/30 transition-colors duration-300 group-hover:scale-110">
                            <x-filament::icon
                                icon="heroicon-s-banknotes"
                                class="w-6 h-6 text-emerald-400 group-hover:text-emerald-300 transition-colors duration-300"
                            />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-300 uppercase tracking-wider opacity-90">Revenue</p>
                            <p class="text-xs text-gray-400 mt-0.5">Monthly Growth</p>
                        </div>
                    </div>
                    @if($financial['growth']['revenue'] != 0)
                        <div class="flex items-center space-x-1">
                            @if($financial['growth']['revenue'] > 0)
                                <x-filament::icon
                                    icon="heroicon-s-arrow-trending-up"
                                    class="w-3.5 h-3.5 text-emerald-400"
                                />
                                <span class="text-xs font-semibold text-emerald-400">+{{ $financial['growth']['revenue'] }}%</span>
                            @else
                                <x-filament::icon
                                    icon="heroicon-s-arrow-trending-down"
                                    class="w-3.5 h-3.5 text-red-400"
                                />
                                <span class="text-xs font-semibold text-red-400">{{ $financial['growth']['revenue'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="relative z-10 space-y-2">
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold text-white tabular-nums tracking-tight">
                            Rp {{ number_format($financial['current']['revenue'], 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-400 font-medium">This month</p>
                        @if($financial['current']['revenue'] > 0)
                            <div class="px-2 py-1 bg-emerald-500/20 rounded-full">
                                <p class="text-xs text-emerald-400 font-semibold">Active</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Expenses Card - Modern SaaS Design -->
            <div class="bendahara-stats-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl p-6 border border-gray-700 hover:shadow-2xl transition-all duration-500 hover:scale-[1.02] hover:-translate-y-2">
                <!-- Subtle background pattern -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0 bg-gradient-to-r from-red-400/20 to-transparent"></div>
                </div>
                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center group-hover:bg-red-500/30 transition-colors duration-300 group-hover:scale-110">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-down"
                                class="w-6 h-6 text-red-400 group-hover:text-red-300 transition-colors duration-300"
                            />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-300 uppercase tracking-wider opacity-90">Expenses</p>
                            <p class="text-xs text-gray-400 mt-0.5">Monthly Spend</p>
                        </div>
                    </div>
                    @if($financial['growth']['expenses'] != 0)
                        <div class="flex items-center space-x-1">
                            @if($financial['growth']['expenses'] > 0)
                                <x-filament::icon
                                    icon="heroicon-s-arrow-trending-up"
                                    class="w-3.5 h-3.5 text-red-400"
                                />
                                <span class="text-xs font-semibold text-red-400">+{{ $financial['growth']['expenses'] }}%</span>
                            @else
                                <x-filament::icon
                                    icon="heroicon-s-arrow-trending-down"
                                    class="w-3.5 h-3.5 text-emerald-400"
                                />
                                <span class="text-xs font-semibold text-emerald-400">{{ $financial['growth']['expenses'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="space-y-1">
                    <h3 class="text-2xl font-bold text-white tabular-nums">
                        Rp {{ number_format($financial['current']['expenses'], 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-gray-400">This month</p>
                </div>
            </div>

            <!-- Net Income Card - Modern SaaS Design -->
            <div class="bendahara-stats-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl p-6 border border-gray-700 hover:shadow-2xl transition-all duration-500 hover:scale-[1.02] hover:-translate-y-2">
                <!-- Dynamic background pattern based on profit/loss -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0 bg-gradient-to-r {{ $financial['current']['net_income'] >= 0 ? 'from-blue-400/20' : 'from-red-400/20' }} to-transparent"></div>
                </div>
                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 {{ $financial['current']['net_income'] >= 0 ? 'bg-blue-500/20' : 'bg-red-500/20' }} rounded-xl flex items-center justify-center group-hover:{{ $financial['current']['net_income'] >= 0 ? 'bg-blue-500/30' : 'bg-red-500/30' }} transition-colors duration-300 group-hover:scale-110">
                            <x-filament::icon
                                icon="{{ $financial['current']['net_income'] >= 0 ? 'heroicon-s-chart-bar' : 'heroicon-s-chart-bar-square' }}"
                                class="w-6 h-6 {{ $financial['current']['net_income'] >= 0 ? 'text-blue-400 group-hover:text-blue-300' : 'text-red-400 group-hover:text-red-300' }} transition-colors duration-300"
                            />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-300 uppercase tracking-wider opacity-90">Net Income</p>
                            <p class="text-xs {{ $financial['current']['net_income'] >= 0 ? 'text-blue-400' : 'text-red-400' }} mt-0.5">{{ $financial['current']['net_income'] >= 0 ? 'Profit' : 'Loss' }}</p>
                        </div>
                    </div>
                    @if($financial['growth']['net_income'] != 0)
                        <div class="flex items-center space-x-1">
                            @if($financial['growth']['net_income'] > 0)
                                <x-filament::icon
                                    icon="heroicon-s-arrow-trending-up"
                                    class="w-3.5 h-3.5 text-emerald-400"
                                />
                                <span class="text-xs font-semibold text-emerald-400">+{{ $financial['growth']['net_income'] }}%</span>
                            @else
                                <x-filament::icon
                                    icon="heroicon-s-arrow-trending-down"
                                    class="w-3.5 h-3.5 text-red-400"
                                />
                                <span class="text-xs font-semibold text-red-400">{{ $financial['growth']['net_income'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="relative z-10 space-y-2">
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold {{ $financial['current']['net_income'] >= 0 ? 'text-emerald-400' : 'text-red-400' }} tabular-nums tracking-tight">
                            Rp {{ number_format($financial['current']['net_income'], 0, ',', '.') }}
                        </h3>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-400 font-medium">This month</p>
                        <div class="px-2 py-1 {{ $financial['current']['net_income'] >= 0 ? 'bg-emerald-500/20' : 'bg-red-500/20' }} rounded-full">
                            <p class="text-xs {{ $financial['current']['net_income'] >= 0 ? 'text-emerald-400' : 'text-red-400' }} font-semibold">
                                {{ $financial['current']['net_income'] >= 0 ? '↗ Positive' : '↘ Negative' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Status Card - Modern SaaS Design -->
            <div class="bendahara-stats-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl p-6 border border-gray-700 hover:shadow-2xl transition-all duration-500 hover:scale-[1.02] hover:-translate-y-2">
                <!-- Subtle background pattern -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-400/20 to-transparent"></div>
                </div>
                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-violet-500/20 rounded-xl flex items-center justify-center group-hover:bg-violet-500/30 transition-colors duration-300 group-hover:scale-110">
                            <x-filament::icon
                                icon="heroicon-s-check-circle"
                                class="w-6 h-6 text-violet-400 group-hover:text-violet-300 transition-colors duration-300"
                            />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-300 uppercase tracking-wider opacity-90">Validation</p>
                            <p class="text-xs text-gray-400 mt-0.5">Status Check</p>
                        </div>
                    </div>
                    @if($validation['total_pending'] > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/20 text-amber-400 border border-amber-500/30">
                            {{ $validation['total_pending'] }} pending
                        </span>
                    @endif
                </div>
                <div class="relative z-10 space-y-2">
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold text-white tabular-nums tracking-tight">
                            {{ $validation['total_approved'] }}
                        </h3>
                        <span class="text-sm text-violet-400 font-medium">approved</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-400 font-medium">Today</p>
                        @if($validation['total_pending'] > 0)
                            <div class="px-2 py-1 bg-amber-500/20 rounded-full animate-pulse">
                                <p class="text-xs text-amber-400 font-semibold">{{ $validation['total_pending'] }} pending</p>
                            </div>
                        @else
                            <div class="px-2 py-1 bg-emerald-500/20 rounded-full">
                                <p class="text-xs text-emerald-400 font-semibold">All Clear</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Overview Section -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
            
            <!-- Financial Trends Chart - Modern SaaS Design -->
            <div class="xl:col-span-2 bendahara-chart-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl border border-gray-700 p-8 hover:shadow-2xl transition-all duration-500">
                <!-- Subtle animated background -->
                <div class="absolute inset-0 opacity-3">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 via-transparent to-emerald-400/10 animate-pulse"></div>
                </div>
                <div class="relative z-10 flex items-start justify-between mb-8">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-3">
                            <h3 class="text-xl font-bold text-white">Financial Trends</h3>
                            <div class="px-3 py-1 bg-blue-500/20 rounded-full">
                                <span class="text-xs text-blue-400 font-semibold">6M</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-400 font-medium">Performance overview & insights</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs">
                        <div class="flex items-center space-x-2 px-3 py-2 bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                            <div class="w-3 h-3 bg-emerald-400 rounded-full shadow-lg shadow-emerald-400/50"></div>
                            <span class="text-emerald-300 font-semibold">Revenue</span>
                        </div>
                        <div class="flex items-center space-x-2 px-3 py-2 bg-red-500/10 rounded-lg border border-red-500/20">
                            <div class="w-3 h-3 bg-red-400 rounded-full shadow-lg shadow-red-400/50"></div>
                            <span class="text-red-300 font-semibold">Expenses</span>
                        </div>
                        <div class="flex items-center space-x-2 px-3 py-2 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <div class="w-3 h-3 bg-blue-400 rounded-full shadow-lg shadow-blue-400/50"></div>
                            <span class="text-blue-300 font-semibold">Net Income</span>
                        </div>
                    </div>
                </div>
                <div class="relative z-10 h-80 bg-gradient-to-br from-gray-800/50 to-gray-900/50 rounded-xl flex items-center justify-center border border-gray-600 group-hover:border-gray-500 transition-colors duration-300">
                    <div class="text-center space-y-4">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-emerald-400/20 rounded-full blur-xl opacity-50 animate-pulse"></div>
                            <x-filament::icon
                                icon="heroicon-s-chart-bar"
                                class="relative w-16 h-16 text-gray-400 mx-auto mb-2 group-hover:text-gray-300 transition-colors duration-300"
                            />
                        </div>
                        <div class="space-y-2">
                            <p class="text-lg font-semibold text-gray-300">Interactive Chart Coming Soon</p>
                            <p class="text-sm text-gray-400 max-w-xs mx-auto leading-relaxed">Advanced financial trends visualization with real-time data insights</p>
                            <div class="flex justify-center space-x-2 mt-4">
                                <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                                <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                                <div class="w-2 h-2 bg-violet-400 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities - Modern Design -->
            <div class="space-y-6">
                
                <!-- Recent Revenue -->
                <div class="bendahara-activity-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl border border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
                    <!-- Subtle background accent -->
                    <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/10 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative z-10 flex items-center justify-between mb-6">
                        <div class="space-y-1">
                            <h4 class="text-lg font-bold text-white">Revenue Activity</h4>
                            <p class="text-xs text-gray-400 font-medium">Latest transactions</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center group-hover:bg-emerald-500/30 transition-colors duration-300 group-hover:scale-110">
                            <x-filament::icon
                                icon="heroicon-s-arrow-up"
                                class="w-6 h-6 text-emerald-400 group-hover:text-emerald-300 transition-colors duration-300"
                            />
                        </div>
                    </div>
                    <div class="space-y-3">
                        @php
                            $revenueActivities = array_filter($activities, fn($activity) => $activity['type'] === 'revenue');
                            $revenueActivities = array_slice($revenueActivities, 0, 3);
                        @endphp
                        @forelse($revenueActivities as $activity)
                            <div class="relative z-10 flex items-center justify-between py-3 px-3 rounded-xl hover:bg-emerald-500/5 transition-colors duration-200 group/item">
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="w-2 h-2 bg-emerald-400 rounded-full flex-shrink-0 group-hover/item:scale-150 transition-transform duration-200"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-white truncate group-hover/item:text-emerald-100 transition-colors duration-200">
                                            {{ $activity['title'] ?: 'Revenue Entry' }}
                                        </p>
                                        <p class="text-xs text-gray-400 font-medium">
                                            {{ $activity['date']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0 space-y-1">
                                    <p class="text-sm font-bold text-emerald-400 group-hover/item:text-emerald-300 transition-colors duration-200">
                                        +{{ number_format($activity['amount']/1000, 0) }}K
                                    </p>
                                    <div class="text-xs">
                                        @if($activity['status'] === 'disetujui')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-semibold">
                                                ✓ Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 font-semibold animate-pulse">
                                                ⏳ Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="relative z-10 text-center py-8">
                                <div class="w-12 h-12 bg-gray-700/50 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <x-filament::icon
                                        icon="heroicon-s-banknotes"
                                        class="w-6 h-6 text-gray-500"
                                    />
                                </div>
                                <p class="text-sm text-gray-400 font-medium">No recent revenue activity</p>
                                <p class="text-xs text-gray-500 mt-1">Transactions will appear here</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Expenses -->
                <div class="bendahara-activity-card group relative overflow-hidden bg-gradient-to-br from-gray-900 to-black rounded-2xl border border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
                    <!-- Subtle background accent -->
                    <div class="absolute top-0 right-0 w-20 h-20 bg-red-500/10 rounded-full -translate-y-8 translate-x-8 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="relative z-10 flex items-center justify-between mb-6">
                        <div class="space-y-1">
                            <h4 class="text-lg font-bold text-white">Expense Activity</h4>
                            <p class="text-xs text-gray-400 font-medium">Recent spending</p>
                        </div>
                        <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center group-hover:bg-red-500/30 transition-colors duration-300 group-hover:scale-110">
                            <x-filament::icon
                                icon="heroicon-s-arrow-down"
                                class="w-6 h-6 text-red-400 group-hover:text-red-300 transition-colors duration-300"
                            />
                        </div>
                    </div>
                    <div class="space-y-3">
                        @php
                            $expenseActivities = array_filter($activities, fn($activity) => $activity['type'] === 'expense');
                            $expenseActivities = array_slice($expenseActivities, 0, 3);
                        @endphp
                        @forelse($expenseActivities as $activity)
                            <div class="relative z-10 flex items-center justify-between py-3 px-3 rounded-xl hover:bg-red-500/5 transition-colors duration-200 group/item">
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <div class="w-2 h-2 bg-red-400 rounded-full flex-shrink-0 group-hover/item:scale-150 transition-transform duration-200"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-white truncate group-hover/item:text-red-100 transition-colors duration-200">
                                            {{ $activity['title'] ?: 'Expense Entry' }}
                                        </p>
                                        <p class="text-xs text-gray-400 font-medium">
                                            {{ $activity['date']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0 space-y-1">
                                    <p class="text-sm font-bold text-red-400 group-hover/item:text-red-300 transition-colors duration-200">
                                        -{{ number_format($activity['amount']/1000, 0) }}K
                                    </p>
                                    <div class="text-xs">
                                        @if($activity['status'] === 'disetujui' || $activity['status'] === 'approved')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-semibold">
                                                ✓ Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 font-semibold animate-pulse">
                                                ⏳ Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="relative z-10 text-center py-8">
                                <div class="w-12 h-12 bg-gray-700/50 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <x-filament::icon
                                        icon="heroicon-s-arrow-trending-down"
                                        class="w-6 h-6 text-gray-500"
                                    />
                                </div>
                                <p class="text-sm text-gray-400 font-medium">No recent expense activity</p>
                                <p class="text-xs text-gray-500 mt-1">Expenses will appear here</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>