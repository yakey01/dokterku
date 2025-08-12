<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php $stats = $this->getStatsData(); @endphp
        
        <!-- Pendapatan Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 border border-emerald-200 dark:border-emerald-700/50 p-6 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/25 hover:-translate-y-2 hover:scale-105">
            <!-- Gradient overlay on hover -->
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-emerald-600/10 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
            
            <!-- Background decoration -->
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl transition-all duration-500 group-hover:scale-150 group-hover:bg-emerald-500/20"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-xl bg-emerald-500 shadow-lg shadow-emerald-500/30 transition-all duration-500 group-hover:shadow-emerald-500/50 group-hover:scale-110">
                            <x-heroicon-o-banknotes class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300 transition-colors duration-300">Pendapatan</p>
                            <p class="text-xs text-emerald-600/70 dark:text-emerald-400/70">Bulan Ini</p>
                        </div>
                    </div>
                    @if($stats['pendapatan']['change'] >= 0)
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">
                            <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format($stats['pendapatan']['change'], 1) }}%</span>
                        </div>
                    @else
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-red-500/20 text-red-700 dark:text-red-300">
                            <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format(abs($stats['pendapatan']['change']), 1) }}%</span>
                        </div>
                    @endif
                </div>
                
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-all duration-300 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                        Rp {{ number_format($stats['pendapatan']['current'], 0, ',', '.') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bulan lalu: <span class="font-medium">Rp {{ number_format($stats['pendapatan']['previous'], 0, ',', '.') }}</span>
                    </p>
                </div>
                
                <!-- Progress line -->
                <div class="mt-4 h-1 bg-emerald-200 dark:bg-emerald-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full transition-all duration-1000 group-hover:from-emerald-400 group-hover:to-emerald-500" style="width: {{ min(100, ($stats['pendapatan']['current'] / max($stats['pendapatan']['previous'], 1)) * 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Pengeluaran Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border border-red-200 dark:border-red-700/50 p-6 transition-all duration-500 hover:shadow-2xl hover:shadow-red-500/25 hover:-translate-y-2 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-br from-red-400/10 to-red-600/10 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-red-500/10 rounded-full blur-xl transition-all duration-500 group-hover:scale-150 group-hover:bg-red-500/20"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-xl bg-red-500 shadow-lg shadow-red-500/30 transition-all duration-500 group-hover:shadow-red-500/50 group-hover:scale-110">
                            <x-heroicon-o-arrow-trending-down class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-300 transition-colors duration-300">Pengeluaran</p>
                            <p class="text-xs text-red-600/70 dark:text-red-400/70">Bulan Ini</p>
                        </div>
                    </div>
                    @if($stats['pengeluaran']['change'] <= 0)
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">
                            <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format(abs($stats['pengeluaran']['change']), 1) }}%</span>
                        </div>
                    @else
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-red-500/20 text-red-700 dark:text-red-300">
                            <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format($stats['pengeluaran']['change'], 1) }}%</span>
                        </div>
                    @endif
                </div>
                
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-all duration-300 group-hover:text-red-700 dark:group-hover:text-red-300">
                        Rp {{ number_format($stats['pengeluaran']['current'], 0, ',', '.') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bulan lalu: <span class="font-medium">Rp {{ number_format($stats['pengeluaran']['previous'], 0, ',', '.') }}</span>
                    </p>
                </div>
                
                <div class="mt-4 h-1 bg-red-200 dark:bg-red-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-red-500 to-red-600 rounded-full transition-all duration-1000 group-hover:from-red-400 group-hover:to-red-500" style="width: {{ min(100, ($stats['pengeluaran']['current'] / max($stats['pengeluaran']['previous'], 1)) * 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Pasien Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700/50 p-6 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/25 hover:-translate-y-2 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-blue-600/10 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-blue-500/10 rounded-full blur-xl transition-all duration-500 group-hover:scale-150 group-hover:bg-blue-500/20"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-xl bg-blue-500 shadow-lg shadow-blue-500/30 transition-all duration-500 group-hover:shadow-blue-500/50 group-hover:scale-110">
                            <x-heroicon-o-users class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-300 transition-colors duration-300">Pasien</p>
                            <p class="text-xs text-blue-600/70 dark:text-blue-400/70">Bulan Ini</p>
                        </div>
                    </div>
                    @if($stats['pasien']['change'] >= 0)
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">
                            <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format($stats['pasien']['change'], 1) }}%</span>
                        </div>
                    @else
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-red-500/20 text-red-700 dark:text-red-300">
                            <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format(abs($stats['pasien']['change']), 1) }}%</span>
                        </div>
                    @endif
                </div>
                
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-all duration-300 group-hover:text-blue-700 dark:group-hover:text-blue-300">
                        {{ number_format($stats['pasien']['current']) }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bulan lalu: <span class="font-medium">{{ number_format($stats['pasien']['previous']) }}</span>
                    </p>
                </div>
                
                <div class="mt-4 h-1 bg-blue-200 dark:bg-blue-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-1000 group-hover:from-blue-400 group-hover:to-blue-500" style="width: {{ min(100, ($stats['pasien']['current'] / max($stats['pasien']['previous'], 1)) * 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Net Income Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-{{ $stats['net_income']['color'] }}-50 to-{{ $stats['net_income']['color'] }}-100 dark:from-{{ $stats['net_income']['color'] }}-900/20 dark:to-{{ $stats['net_income']['color'] }}-800/20 border border-{{ $stats['net_income']['color'] }}-200 dark:border-{{ $stats['net_income']['color'] }}-700/50 p-6 transition-all duration-500 hover:shadow-2xl hover:shadow-{{ $stats['net_income']['color'] }}-500/25 hover:-translate-y-2 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-br from-{{ $stats['net_income']['color'] }}-400/10 to-{{ $stats['net_income']['color'] }}-600/10 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-{{ $stats['net_income']['color'] }}-500/10 rounded-full blur-xl transition-all duration-500 group-hover:scale-150 group-hover:bg-{{ $stats['net_income']['color'] }}-500/20"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-xl bg-{{ $stats['net_income']['color'] }}-500 shadow-lg shadow-{{ $stats['net_income']['color'] }}-500/30 transition-all duration-500 group-hover:shadow-{{ $stats['net_income']['color'] }}-500/50 group-hover:scale-110">
                            <x-heroicon-o-chart-bar class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-{{ $stats['net_income']['color'] }}-700 dark:text-{{ $stats['net_income']['color'] }}-300 transition-colors duration-300">Net Income</p>
                            <p class="text-xs text-{{ $stats['net_income']['color'] }}-600/70 dark:text-{{ $stats['net_income']['color'] }}-400/70">Bulan Ini</p>
                        </div>
                    </div>
                    @if($stats['net_income']['change'] >= 0)
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">
                            <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format($stats['net_income']['change'], 1) }}%</span>
                        </div>
                    @else
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full bg-red-500/20 text-red-700 dark:text-red-300">
                            <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                            <span class="text-xs font-semibold">{{ number_format(abs($stats['net_income']['change']), 1) }}%</span>
                        </div>
                    @endif
                </div>
                
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-all duration-300 group-hover:text-{{ $stats['net_income']['color'] }}-700 dark:group-hover:text-{{ $stats['net_income']['color'] }}-300">
                        Rp {{ number_format($stats['net_income']['current'], 0, ',', '.') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bulan lalu: <span class="font-medium">Rp {{ number_format($stats['net_income']['previous'], 0, ',', '.') }}</span>
                    </p>
                </div>
                
                <div class="mt-4 h-1 bg-{{ $stats['net_income']['color'] }}-200 dark:bg-{{ $stats['net_income']['color'] }}-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-{{ $stats['net_income']['color'] }}-500 to-{{ $stats['net_income']['color'] }}-600 rounded-full transition-all duration-1000 group-hover:from-{{ $stats['net_income']['color'] }}-400 group-hover:to-{{ $stats['net_income']['color'] }}-500" style="width: {{ $stats['net_income']['current'] >= 0 ? 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>