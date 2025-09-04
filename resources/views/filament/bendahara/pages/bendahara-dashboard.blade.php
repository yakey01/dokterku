<x-filament-panels::page>
    @php
        $financialSummary = $this->getFinancialSummary();
        $validationStats = $this->getValidationStats();
        $recentTransactions = $this->getRecentTransactions();
        $monthlyTrends = $this->getMonthlyTrends();
        $topPerformers = $this->getTopPerformers();
    @endphp
    
    <div class="space-y-6">
        <!-- Financial Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Pendapatan -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Pendapatan
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['pendapatan'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['pendapatan'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$financialSummary['changes']['pendapatan'] >= 0 ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['pendapatan'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $financialSummary['changes']['pendapatan'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-success-100 dark:bg-success-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-banknotes"
                            class="w-6 h-6 text-success-600 dark:text-success-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Pengeluaran -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Pengeluaran
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['pengeluaran'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['pengeluaran'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$financialSummary['changes']['pengeluaran'] >= 0 ? 'text-danger-500' : 'text-success-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['pengeluaran'] >= 0 ? 'text-danger-600' : 'text-success-600' }}">
                                {{ $financialSummary['changes']['pengeluaran'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-danger-100 dark:bg-danger-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-arrow-trending-down"
                            class="w-6 h-6 text-danger-600 dark:text-danger-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Jaspel -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Jaspel
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['jaspel'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['jaspel'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$financialSummary['changes']['jaspel'] >= 0 ? 'text-warning-500' : 'text-success-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['jaspel'] >= 0 ? 'text-warning-600' : 'text-success-600' }}">
                                {{ $financialSummary['changes']['jaspel'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-warning-100 dark:bg-warning-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-user-group"
                            class="w-6 h-6 text-warning-600 dark:text-warning-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Net Profit -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Net Profit
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['net_profit'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['net_profit'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$financialSummary['changes']['net_profit'] >= 0 ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['net_profit'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $financialSummary['changes']['net_profit'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 {{ $financialSummary['current']['net_profit'] >= 0 ? 'bg-success-100 dark:bg-success-900' : 'bg-danger-100 dark:bg-danger-900' }} rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-chart-bar"
                            class="w-6 h-6 {{ $financialSummary['current']['net_profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}"
                        />
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Financial Analytics Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Financial Trends Chart -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Financial Trends
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        6-month overview
                    </p>
                </div>
                
                <!-- Chart Container -->
                <div class="relative">
                    <canvas id="financialTrendsChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Legend -->
                <div class="flex items-center justify-center space-x-6 mt-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-success-500 rounded-full"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Revenue</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-danger-500 rounded-full"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Expenses</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Net Income</span>
                    </div>
                </div>
            </x-filament::section>

            <!-- Financial Distribution Donut Chart -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Financial Distribution
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Current month breakdown
                    </p>
                </div>
                
                <!-- Donut Chart Container -->
                <div class="flex items-center justify-center">
                    <div class="relative">
                        <canvas id="financialDonutChart" width="280" height="280"></canvas>
                        <!-- Center Info -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    @php
                                        $netTotal = $financialSummary['current']['pendapatan'] - $financialSummary['current']['pengeluaran'] - $financialSummary['current']['jaspel'];
                                    @endphp
                                    {{ $netTotal >= 0 ? '+' : '' }}{{ number_format($netTotal / 1000000, 1) }}M
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Net Income</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Donut Legend -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="text-center">
                        <div class="flex items-center justify-center space-x-2 mb-1">
                            <div class="w-3 h-3 bg-success-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Revenue</span>
                        </div>
                        <div class="text-lg font-bold text-success-600">
                            Rp {{ number_format($financialSummary['current']['pendapatan'] / 1000000, 1) }}M
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="flex items-center justify-center space-x-2 mb-1">
                            <div class="w-3 h-3 bg-danger-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Expenses</span>
                        </div>
                        <div class="text-lg font-bold text-danger-600">
                            Rp {{ number_format($financialSummary['current']['pengeluaran'] / 1000000, 1) }}M
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="flex items-center justify-center space-x-2 mb-1">
                            <div class="w-3 h-3 bg-warning-500 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Jaspel</span>
                        </div>
                        <div class="text-lg font-bold text-warning-600">
                            Rp {{ number_format($financialSummary['current']['jaspel'] / 1000000, 1) }}M
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Validation Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Validation Queue -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Antrian Validasi
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Status validasi transaksi
                    </p>
                </div>
                
                <div class="space-y-4">
                    <!-- Pending Items -->
                    <div class="flex items-center justify-between p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                icon="heroicon-o-clock"
                                class="w-5 h-5 text-warning-600"
                            />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    Menunggu Validasi
                                </p>
                                <div class="text-sm text-gray-500 space-x-2">
                                    <span>{{ $validationStats['pending']['pendapatan'] }} Pendapatan</span>
                                    <span>{{ $validationStats['pending']['pengeluaran'] }} Pengeluaran</span>
                                    <span>{{ $validationStats['pending']['jaspel'] }} Jaspel</span>
                                </div>
                            </div>
                        </div>
                        <x-filament::badge color="warning" size="lg">
                            {{ $validationStats['total_pending'] }}
                        </x-filament::badge>
                    </div>
                    
                    <!-- Approved Items -->
                    <div class="flex items-center justify-between p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                icon="heroicon-o-check-circle"
                                class="w-5 h-5 text-success-600"
                            />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    Sudah Disetujui
                                </p>
                                <div class="text-sm text-gray-500 space-x-2">
                                    <span>{{ $validationStats['approved']['pendapatan'] }} Pendapatan</span>
                                    <span>{{ $validationStats['approved']['pengeluaran'] }} Pengeluaran</span>
                                    <span>{{ $validationStats['approved']['jaspel'] }} Jaspel</span>
                                </div>
                            </div>
                        </div>
                        <x-filament::badge color="success" size="lg">
                            {{ $validationStats['total_approved'] }}
                        </x-filament::badge>
                    </div>
                    
                    <!-- Rejected Items -->
                    <div class="flex items-center justify-between p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                icon="heroicon-o-x-circle"
                                class="w-5 h-5 text-danger-600"
                            />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    Ditolak
                                </p>
                                <div class="text-sm text-gray-500 space-x-2">
                                    <span>{{ $validationStats['rejected']['pendapatan'] }} Pendapatan</span>
                                    <span>{{ $validationStats['rejected']['pengeluaran'] }} Pengeluaran</span>
                                    <span>{{ $validationStats['rejected']['jaspel'] }} Jaspel</span>
                                </div>
                            </div>
                        </div>
                        <x-filament::badge color="danger" size="lg">
                            {{ $validationStats['total_rejected'] }}
                        </x-filament::badge>
                    </div>
                </div>
            </x-filament::section>

            <!-- Monthly Trends Summary -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Monthly Summary
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Recent 6 months performance
                    </p>
                </div>
                
                <div class="space-y-3">
                    @foreach(array_slice($monthlyTrends['months'], -3) as $index => $month)
                        @php
                            $actualIndex = count($monthlyTrends['months']) - 3 + $index;
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $month }}
                                </div>
                                <div class="flex items-center space-x-4 mt-1">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-success-500 rounded-full"></div>
                                        <span class="text-xs text-gray-500">
                                            {{ number_format(($monthlyTrends['pendapatan'][$actualIndex] ?? 0) / 1000000, 1) }}M
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-danger-500 rounded-full"></div>
                                        <span class="text-xs text-gray-500">
                                            {{ number_format(($monthlyTrends['pengeluaran'][$actualIndex] ?? 0) / 1000000, 1) }}M
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                @php
                                    $net = ($monthlyTrends['pendapatan'][$actualIndex] ?? 0) - ($monthlyTrends['pengeluaran'][$actualIndex] ?? 0) - ($monthlyTrends['jaspel'][$actualIndex] ?? 0);
                                @endphp
                                <div class="text-sm font-semibold {{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $net >= 0 ? '+' : '' }}{{ number_format($net / 1000000, 1) }}M
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <!-- Recent Transactions and Top Performers -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Transactions -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Transaksi Terbaru
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        10 transaksi terakhir
                    </p>
                </div>
                
                <div class="space-y-3">
                    @foreach($recentTransactions as $transaction)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 {{ $transaction['type'] === 'pendapatan' ? 'bg-success-100 dark:bg-success-900' : ($transaction['type'] === 'pengeluaran' ? 'bg-danger-100 dark:bg-danger-900' : 'bg-warning-100 dark:bg-warning-900') }} rounded-full">
                                    <x-filament::icon
                                        :icon="$transaction['type'] === 'pendapatan' ? 'heroicon-o-arrow-trending-up' : ($transaction['type'] === 'pengeluaran' ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-user-group')"
                                        class="w-4 h-4 {{ $transaction['type'] === 'pendapatan' ? 'text-success-600' : ($transaction['type'] === 'pengeluaran' ? 'text-danger-600' : 'text-warning-600') }}"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $transaction['code'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ Str::limit($transaction['description'], 30) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($transaction['amount'], 0, ',', '.') }}
                                </p>
                                <x-filament::badge
                                    :color="$transaction['status'] === 'pending' ? 'warning' : ($transaction['status'] === 'disetujui' || $transaction['status'] === 'approved' ? 'success' : 'danger')"
                                    size="sm"
                                >
                                    {{ $transaction['status'] }}
                                </x-filament::badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <!-- Top Performers -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Performa Terbaik
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Top dokter dan prosedur
                    </p>
                </div>
                
                <div class="space-y-4">
                    <!-- Top Doctors -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            💼 Top Dokter (Jaspel)
                        </h4>
                        <div class="space-y-2">
                            @foreach($topPerformers['doctors'] as $doctor)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $doctor['name'] }}
                                    </span>
                                    <span class="text-sm font-medium text-success-600">
                                        Rp {{ number_format($doctor['total'], 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Top Procedures -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🏥 Top Prosedur
                        </h4>
                        <div class="space-y-2">
                            @foreach($topPerformers['procedures'] as $procedure)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $procedure['name'] }}
                                    </span>
                                    <x-filament::badge color="info" size="sm">
                                        {{ $procedure['total'] }}x
                                    </x-filament::badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
    
    {{-- DEBUGGING & FORCE ELEGANT BLACK THEME --}}
    <style>
        /* ===== ULTIMATE BLACK CARDS FIX - COMPREHENSIVE ===== */
        
        /* FORCE BLACK THEME - ANY CARD-LIKE ELEMENT */
        [data-filament-panel-id="bendahara"] div:not(.fi-sidebar):not(.fi-topbar):not(.fi-main),
        div[class*="bg-white"]:not(.fi-sidebar *):not(.fi-topbar *),
        div[class*="bg-gray-"]:not(.fi-sidebar *):not(.fi-topbar *),
        .fi-wi,
        .fi-section,
        .fi-sta-overview-stat,
        .rounded-lg:not(.fi-sidebar *):not(.fi-topbar *),
        .shadow:not(.fi-sidebar *):not(.fi-topbar *) {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border: 1px solid #333340 !important;
            border-radius: 1rem !important;
            box-shadow: 
                0 4px 12px -2px rgba(0, 0, 0, 0.8),
                0 2px 6px -2px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
            color: #fafafa !important;
        }
        
        /* ALL TEXT WHITE IN CARDS */
        [data-filament-panel-id="bendahara"] div:not(.fi-sidebar):not(.fi-topbar) *,
        .fi-wi *,
        .fi-section *,
        .bg-white:not(.fi-sidebar *):not(.fi-topbar *) *,
        .rounded-lg:not(.fi-sidebar *):not(.fi-topbar *) * {
            color: #fafafa !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }
        
        /* ===== DEBUGGING SECTION - IDENTIFY REAL SELECTORS ===== */
        /* This will help us see what elements we're actually targeting */
        
        /* Step 1: Debug - Add red border to ALL potential card elements */
        [data-filament-panel-id="bendahara"] * {
            /* Temporary debug border - remove after identifying correct selectors */
            /* border: 2px solid red !important; */
        }
        
        /* Step 2: Debug - Add green background to grid containers */
        [data-filament-panel-id="bendahara"] .grid {
            /* background: rgba(0, 255, 0, 0.1) !important; */
        }
        
        /* Step 3: Debug - Add blue background to potential card elements */
        [data-filament-panel-id="bendahara"] .grid > div {
            /* background: rgba(0, 0, 255, 0.1) !important; */
        }
        
        /* ===== NUCLEAR DEBUGGING APPROACH ===== */
        /* Let's try to target EVERYTHING and see what sticks */
        
        /* Method 1: Target by tag name */
        [data-filament-panel-id="bendahara"] section {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border-radius: 1rem !important;
        }
        
        /* Method 2: Target by any div that might be a card */
        [data-filament-panel-id="bendahara"] div[class] {
            /* Apply to any div with a class attribute */
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
        }
        
        /* Method 3: Target by specific layout classes from screenshot */
        [data-filament-panel-id="bendahara"] .grid-cols-4 > div,
        [data-filament-panel-id="bendahara"] .grid-cols-2 > div,
        [data-filament-panel-id="bendahara"] .md\:grid-cols-4 > div,
        [data-filament-panel-id="bendahara"] .lg\:grid-cols-4 > div {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border-radius: 1rem !important;
        }
        
        /* Method 4: Brutforce - Target everything in the main content area */
        [data-filament-panel-id="bendahara"] main * {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            color: #ffffff !important;
        }
        
        /* ===== ULTRA SPECIFIC - Target exact card elements visible in dashboard ===== */
        [data-filament-panel-id="bendahara"] .grid > div,
        [data-filament-panel-id="bendahara"] .grid > div > *,
        [data-filament-panel-id="bendahara"] .space-y-6 .grid > div,
        [data-filament-panel-id="bendahara"] .space-y-6 .grid > div > *,
        [data-filament-panel-id="bendahara"] .md\\:grid-cols-2 > div,
        [data-filament-panel-id="bendahara"] .lg\\:grid-cols-4 > div,
        [data-filament-panel-id="bendahara"] .lg\\:grid-cols-2 > div,
        [data-filament-panel-id="bendahara"] section[class*="fi-"],
        [data-filament-panel-id="bendahara"] div[class*="fi-section"] {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border: 1px solid #333340 !important;
            border-radius: 1rem !important;
            box-shadow: 
                0 4px 12px -2px rgba(0, 0, 0, 0.8),
                0 2px 6px -2px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
        }

        /* FORCE Premium Black Background for Financial Cards */
        [data-filament-panel-id="bendahara"] .grid .space-y-6 > div,
        [data-filament-panel-id="bendahara"] .grid-cols-1 > div,
        [data-filament-panel-id="bendahara"] .md\\:grid-cols-2 > div,
        [data-filament-panel-id="bendahara"] .lg\\:grid-cols-4 > div {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border: 1px solid #404050 !important;
            border-radius: 1rem !important;
        }

        /* Enhanced Text Contrast - Override dark theme text */
        [data-filament-panel-id="bendahara"] .grid h3,
        [data-filament-panel-id="bendahara"] .grid p,
        [data-filament-panel-id="bendahara"] .grid span,
        [data-filament-panel-id="bendahara"] .grid div {
            color: #fafafa !important;
        }

        /* Financial Values - Premium Typography */
        [data-filament-panel-id="bendahara"] .text-2xl,
        [data-filament-panel-id="bendahara"] [class*="text-2xl"] {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 2.25rem !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4) !important;
            letter-spacing: -0.02em !important;
        }

        /* Labels and Descriptions */
        [data-filament-panel-id="bendahara"] .text-sm,
        [data-filament-panel-id="bendahara"] .text-xs {
            color: #e4e4e7 !important;
            font-weight: 500 !important;
        }

        /* Chart and Content Areas */
        [data-filament-panel-id="bendahara"] .space-y-6 > div,
        [data-filament-panel-id="bendahara"] .space-y-4 > div,
        [data-filament-panel-id="bendahara"] .space-y-3 > div {
            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
            border: 1px solid #333340 !important;
            border-radius: 0.875rem !important;
        }

        /* Luxury Hover Effects - Enhanced for Premium Feel */
        [data-filament-panel-id="bendahara"] .grid > div:hover,
        [data-filament-panel-id="bendahara"] .space-y-6 > div:hover {
            background: linear-gradient(135deg, #111118 0%, #1a1a20 100%) !important;
            transform: translateY(-3px) !important;
            box-shadow: 
                0 12px 32px -8px rgba(0, 0, 0, 0.8),
                0 8px 16px -4px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.12) !important;
            border-color: #404050 !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Status Colors Enhancement */
        [data-filament-panel-id="bendahara"] .text-green-500,
        [data-filament-panel-id="bendahara"] .text-green-600 {
            color: #22d65f !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }

        [data-filament-panel-id="bendahara"] .text-red-500,
        [data-filament-panel-id="bendahara"] .text-red-600 {
            color: #f87171 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }

        [data-filament-panel-id="bendahara"] .text-amber-500,
        [data-filament-panel-id="bendahara"] .text-amber-600 {
            color: #fbbf24 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }

        /* ===== MODERN CHART STYLING ===== */
        
        /* Chart Containers */
        #financialTrendsChart,
        #financialDonutChart {
            max-width: 100% !important;
            height: auto !important;
            background: transparent !important;
        }
        
        /* Chart Section Enhancements */
        [data-filament-panel-id="bendahara"] .grid .relative {
            padding: 1rem !important;
            border-radius: 0.75rem !important;
        }
        
        /* Legend Styling */
        [data-filament-panel-id="bendahara"] .flex.items-center.justify-center.space-x-6 {
            margin-top: 1rem !important;
            padding: 1rem !important;
            background: rgba(0, 0, 0, 0.05) !important;
            border-radius: 0.5rem !important;
        }
        
        /* Donut Chart Center Text */
        [data-filament-panel-id="bendahara"] .absolute.inset-0.flex.items-center.justify-center {
            pointer-events: none !important;
        }
        
        /* Responsive Chart Adjustments */
        @media (max-width: 1024px) {
            #financialTrendsChart {
                height: 250px !important;
            }
            
            #financialDonutChart {
                width: 200px !important;
                height: 200px !important;
            }
            
            /* Stack legend vertically on mobile */
            [data-filament-panel-id="bendahara"] .grid-cols-3 {
                grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
                gap: 0.5rem !important;
            }
        }
        
        @media (max-width: 640px) {
            #financialTrendsChart {
                height: 200px !important;
            }
            
            #financialDonutChart {
                width: 180px !important;
                height: 180px !important;
            }
            
            /* Adjust font sizes on mobile */
            [data-filament-panel-id="bendahara"] .text-2xl {
                font-size: 1.5rem !important;
            }
            
            [data-filament-panel-id="bendahara"] .text-lg {
                font-size: 1rem !important;
            }
        }
        
        /* Chart Hover Effects */
        [data-filament-panel-id="bendahara"] canvas:hover {
            cursor: pointer !important;
            transform: scale(1.02) !important;
            transition: transform 0.2s ease-in-out !important;
        }
        
        /* Financial Distribution Grid Layout */
        [data-filament-panel-id="bendahara"] .grid-cols-3.gap-4 > div {
            background: rgba(255, 255, 255, 0.05) !important;
            padding: 0.75rem !important;
            border-radius: 0.5rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Chart Section Headers */
        [data-filament-panel-id="bendahara"] .grid .mb-4 h3 {
            color: #fafafa !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }
        
        [data-filament-panel-id="bendahara"] .grid .mb-4 p {
            color: #d4d4d8 !important;
            opacity: 0.9 !important;
        }
    </style>
    
    {{-- CHART.JS INTEGRATION --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    {{-- CHART IMPLEMENTATION --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📊 BENDAHARA CHARTS: Initializing modern charts...');
            
            // Initialize charts after delay to ensure DOM is ready
            setTimeout(initializeCharts, 500);
            
            // Also observe for dynamic content loading (Livewire compatibility)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        // Re-initialize charts if needed
                        setTimeout(initializeCharts, 300);
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
        
        function initializeCharts() {
            // Data from Laravel backend - with robust error handling
            let monthlyTrendsData, financialSummary;
            
            try {
                monthlyTrendsData = {!! json_encode($monthlyTrends ?? [
                    'months' => [],
                    'pendapatan' => [], 
                    'pengeluaran' => [],
                    'jaspel' => []
                ]) !!};
            } catch (e) {
                console.error('📊 Error parsing monthly trends data:', e);
                monthlyTrendsData = {
                    months: [],
                    pendapatan: [],
                    pengeluaran: [],
                    jaspel: []
                };
            }
            
            try {
                financialSummary = {!! json_encode($financialSummary ?? [
                    'current' => [
                        'pendapatan' => 0,
                        'pengeluaran' => 0, 
                        'jaspel' => 0
                    ]
                ]) !!};
            } catch (e) {
                console.error('📊 Error parsing financial summary data:', e);
                financialSummary = {
                    current: {
                        pendapatan: 0,
                        pengeluaran: 0,
                        jaspel: 0
                    }
                };
            }
            
            console.log('📊 Monthly trends data:', monthlyTrendsData);
            console.log('📊 Financial summary:', financialSummary);
            
            // Initialize Line Chart
            initializeLineChart(monthlyTrendsData);
            
            // Initialize Donut Chart
            initializeDonutChart(financialSummary);
        }
        
        function initializeLineChart(trendsData) {
            const canvas = document.getElementById('financialTrendsChart');
            if (!canvas) {
                console.log('❌ Financial trends canvas not found');
                return;
            }
            
            // Destroy existing chart if exists
            if (window.financialTrendsChart) {
                window.financialTrendsChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            // Prepare data
            const months = trendsData.months || [];
            const pendapatan = trendsData.pendapatan || [];
            const pengeluaran = trendsData.pengeluaran || [];
            const jaspel = trendsData.jaspel || [];
            
            // Calculate net income for each month
            const netIncome = pendapatan.map((rev, index) => 
                rev - (pengeluaran[index] || 0) - (jaspel[index] || 0)
            );
            
            window.financialTrendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: pendapatan,
                            borderColor: '#22d65f',
                            backgroundColor: 'rgba(34, 214, 95, 0.1)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.4,
                            pointBackgroundColor: '#22d65f',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Expenses',
                            data: pengeluaran,
                            borderColor: '#f87171',
                            backgroundColor: 'rgba(248, 113, 113, 0.1)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.4,
                            pointBackgroundColor: '#f87171',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Net Income',
                            data: netIncome,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false // We're using custom legend
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y;
                                    const formattedValue = new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(value);
                                    return context.dataset.label + ': ' + formattedValue;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(107, 114, 128, 0.1)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('✅ Line chart initialized successfully');
        }
        
        function initializeDonutChart(financialData) {
            const canvas = document.getElementById('financialDonutChart');
            if (!canvas) {
                console.log('❌ Financial donut canvas not found');
                return;
            }
            
            // Destroy existing chart if exists
            if (window.financialDonutChart) {
                window.financialDonutChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            // Prepare data
            const pendapatan = financialData.current?.pendapatan || 0;
            const pengeluaran = financialData.current?.pengeluaran || 0;
            const jaspel = financialData.current?.jaspel || 0;
            
            window.financialDonutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Revenue', 'Expenses', 'Jaspel'],
                    datasets: [{
                        data: [pendapatan, pengeluaran, jaspel],
                        backgroundColor: [
                            '#22d65f',
                            '#f87171', 
                            '#fbbf24'
                        ],
                        borderColor: [
                            '#16a34a',
                            '#dc2626',
                            '#d97706'
                        ],
                        borderWidth: 2,
                        hoverBorderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            display: false // We're using custom legend below
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    const formattedValue = new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(value);
                                    return context.label + ': ' + formattedValue + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    }
                }
            });
            
            console.log('✅ Donut chart initialized successfully');
        }
        
        // Apply black theme detection for cards (existing functionality)
        setTimeout(applyBlackThemeDetection, 1000);
        
        function applyBlackThemeDetection() {
            // Find all potential card elements
            let cardElements = document.querySelectorAll('[data-filament-panel-id="bendahara"] .grid > div');
            
            // Force apply black theme
            if (cardElements.length > 0) {
                console.log('🎨 Applying black theme to', cardElements.length, 'cards');
                cardElements.forEach((card) => {
                    card.style.setProperty('background', 'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)', 'important');
                    card.style.setProperty('border', '1px solid #333340', 'important');
                    card.style.setProperty('border-radius', '1rem', 'important');
                    card.style.setProperty('box-shadow', '0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6)', 'important');
                });
                
                // Apply to text elements
                const textElements = document.querySelectorAll('[data-filament-panel-id="bendahara"] .grid *');
                textElements.forEach(element => {
                    element.style.setProperty('color', '#fafafa', 'important');
                });
            }
            
            return cardElements.length;
        }
    </script>
</x-filament-panels::page>