<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm overflow-hidden">
        <!-- Dashboard Header -->
        <div class="bg-white dark:bg-gray-900 px-8 py-6 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                        Petugas Dashboard
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Kelola data pasien dengan efisien dan professional.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <x-filament::button
                        wire:click="refreshData"
                        color="gray"
                        size="sm"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 mr-2" />
                        Refresh Data
                    </x-filament::button>
                </div>
            </div>
        </div>

        @if($error)
            <div class="flex items-center justify-center p-8">
                <div class="text-center">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="w-12 h-12 mx-auto text-danger-500 mb-4"
                    />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ $message }}
                    </h3>
                    <x-filament::button
                        wire:click="refreshData"
                        color="primary"
                        size="sm"
                    >
                        Coba Lagi
                    </x-filament::button>
                </div>
            </div>
        @else
        <!-- Main Dashboard Content -->
        <div class="px-8 py-6">
            @php
                $kpiData = $this->getKpiData();
                $quickStats = $this->getQuickStats();
                $performanceData = $this->getPerformanceData();
            @endphp
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Pasien Hari Ini Card -->
                <div class="relative group">
                    <div class="relative bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 rounded-2xl p-6 shadow-2xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl shadow-lg">
                                <x-filament::icon icon="heroicon-o-users" class="w-7 h-7 text-white" />
                            </div>
                            <div class="flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">+25%</span>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pasien Hari Ini</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $kpiData['pasien']['today'] ?? 15 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span class="inline-flex items-center">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="w-3 h-3 mr-1 text-emerald-500" />
                                    +25% bulan ini
                                </span>
                            </p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-1.5 rounded-full transition-all duration-1000" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tindakan Selesai Card -->
                <div class="relative group">
                    <div class="relative bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 rounded-2xl p-6 shadow-2xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl shadow-lg">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-7 h-7 text-white" />
                            </div>
                            <div class="flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">+27.8%</span>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tindakan Selesai</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $kpiData['tindakan']['completed'] ?? 23 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span class="inline-flex items-center">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="w-3 h-3 mr-1 text-emerald-500" />
                                    +27.8% bulan ini
                                </span>
                            </p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-1.5 rounded-full transition-all duration-1000" style="width: 78%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendapatan Hari Ini Card -->
                <div class="relative group">
                    <div class="relative bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 rounded-2xl p-6 shadow-2xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-lg">
                                <x-filament::icon icon="heroicon-o-chart-bar-square" class="w-7 h-7 text-white" />
                            </div>
                            <div class="flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">+31%</span>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendapatan Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($kpiData['pendapatan']['today'] ?? 2750000, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span class="inline-flex items-center">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="w-3 h-3 mr-1 text-emerald-500" />
                                    +31% bulan ini
                                </span>
                            </p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-1.5 rounded-full transition-all duration-1000" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 mb-1">
                            {{ $performanceData['metrics']['efficiency'] ?? '92' }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Efisiensi Kerja
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 mb-1">
                            {{ $performanceData['metrics']['satisfaction'] ?? '88' }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Kepuasan Pasien
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 mb-1">
                            {{ $performanceData['metrics']['response_time'] ?? '4.2' }}min
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Waktu Respons
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 mb-1">
                            {{ $performanceData['metrics']['quality'] ?? '95' }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Kualitas Pelayanan
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-widgets::widget>