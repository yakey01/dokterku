<x-filament-widgets::widget>
    {{-- Include Glass Morphism Styles and Scripts --}}
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/petugas-glass-morphism-cards.css') }}">
    @endpush
    
    @push('scripts')
        <script src="{{ asset('js/petugas-glass-interactions.js') }}" defer></script>
    @endpush

    <div class="space-y-6">
        @if(isset($error) && $error)
            <div class="petugas-glass-card petugas-error flex items-center justify-center p-8">
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
                        wire:poll.5s
                    >
                        Memuat ulang...
                    </x-filament::button>
                </div>
            </div>
        @else
            <!-- Glass Morphism Hero Metrics Grid -->
            <div class="petugas-hero-metrics-grid">
                @foreach($hero_metrics as $key => $metric)
                    <div class="petugas-metric-card" 
                         role="button" 
                         tabindex="0"
                         aria-label="{{ $metric['label'] }}: {{ $metric['value'] }}, {{ $metric['trend']['description'] }}">
                        
                        <!-- Icon Container with Glass Effect -->
                        <div class="petugas-metric-icon {{ $metric['color'] }}">
                            @if($key === 'patients')
                                <x-filament::icon
                                    icon="heroicon-o-users"
                                    class="w-8 h-8 text-white"
                                />
                            @elseif($key === 'procedures')
                                <x-filament::icon
                                    icon="heroicon-o-clipboard-document-list"
                                    class="w-8 h-8 text-white"
                                />
                            @elseif($key === 'revenue')
                                <x-filament::icon
                                    icon="heroicon-o-banknotes"
                                    class="w-8 h-8 text-white"
                                />
                            @else
                                <x-filament::icon
                                    icon="heroicon-o-chart-bar"
                                    class="w-8 h-8 text-white"
                                />
                            @endif
                        </div>

                        <!-- Metric Content -->
                        <div class="flex-1">
                            <div class="petugas-metric-label">
                                {{ $metric['label'] }}
                            </div>
                            
                            <div class="petugas-metric-value">
                                {{ $metric['value'] }}
                            </div>
                            
                            <!-- Enhanced Trend Indicator -->
                            <div class="petugas-trend-indicator petugas-trend-{{ $metric['trend']['direction'] }}">
                                @if($metric['trend']['direction'] === 'up')
                                    <x-filament::icon
                                        icon="heroicon-m-arrow-trending-up"
                                        class="w-4 h-4 petugas-trend-icon"
                                    />
                                @elseif($metric['trend']['direction'] === 'down')
                                    <x-filament::icon
                                        icon="heroicon-m-arrow-trending-down"
                                        class="w-4 h-4 petugas-trend-icon"
                                    />
                                @else
                                    <x-filament::icon
                                        icon="heroicon-m-minus"
                                        class="w-4 h-4"
                                    />
                                @endif
                                <span class="text-xs font-semibold">
                                    {{ $metric['trend']['description'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Quick Actions Glass Cards -->
            <div class="petugas-quick-actions">
                <div class="petugas-action-card" 
                     role="button" 
                     tabindex="0"
                     onclick="window.location.href='{{ url('petugas/pasiens/create') }}'"
                     aria-label="Tambah Pasien Baru">
                    <x-filament::icon
                        icon="heroicon-o-user-plus"
                        class="w-8 h-8 text-blue-500 mb-2"
                    />
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Tambah Pasien
                    </span>
                </div>
                
                <div class="petugas-action-card" 
                     role="button" 
                     tabindex="0"
                     onclick="window.location.href='{{ url('petugas/tindakans/create') }}'"
                     aria-label="Buat Jadwal Baru">
                    <x-filament::icon
                        icon="heroicon-o-calendar-plus"
                        class="w-8 h-8 text-green-500 mb-2"
                    />
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Jadwal Baru
                    </span>
                </div>
                
                <div class="petugas-action-card" 
                     role="button" 
                     tabindex="0"
                     onclick="window.location.href='{{ url('petugas/laporan') }}'"
                     aria-label="Lihat Laporan">
                    <x-filament::icon
                        icon="heroicon-o-chart-bar-square"
                        class="w-8 h-8 text-purple-500 mb-2"
                    />
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Laporan
                    </span>
                </div>
                
                <div class="petugas-action-card" 
                     role="button" 
                     tabindex="0"
                     onclick="window.location.href='{{ url('petugas/settings') }}'"
                     aria-label="Pengaturan">
                    <x-filament::icon
                        icon="heroicon-o-cog-6-tooth"
                        class="w-8 h-8 text-gray-500 mb-2"
                    />
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Pengaturan
                    </span>
                </div>
            </div>

            <!-- Glass Morphism Performance Summary -->
            <div class="petugas-performance-card">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Ringkasan Performance
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Update terakhir: {{ $last_updated }}
                        </p>
                    </div>
                    
                    <!-- Enhanced Live Indicator -->
                    <div class="petugas-live-indicator">
                        <div class="petugas-live-dot"></div>
                        <span>Live</span>
                    </div>
                </div>

                <!-- Performance Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Efficiency Score with Glass Circular Progress -->
                    <div class="text-center">
                        <div class="petugas-circular-progress">
                            <svg viewBox="0 0 80 80">
                                <circle class="progress-bg" cx="40" cy="40" r="32"/>
                                <circle class="progress-fill progress-purple" 
                                        cx="40" cy="40" r="32"
                                        stroke-dasharray="201.06"
                                        stroke-dashoffset="{{ 201.06 * (1 - $performance_summary['efficiency_score'] / 100) }}"/>
                            </svg>
                            <div class="petugas-progress-value">{{ $performance_summary['efficiency_score'] }}%</div>
                        </div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Efficiency Score
                        </p>
                    </div>

                    <!-- Approval Rate with Glass Circular Progress -->
                    <div class="text-center">
                        <div class="petugas-circular-progress">
                            <svg viewBox="0 0 80 80">
                                <circle class="progress-bg" cx="40" cy="40" r="32"/>
                                <circle class="progress-fill progress-green" 
                                        cx="40" cy="40" r="32"
                                        stroke-dasharray="201.06"
                                        stroke-dashoffset="{{ 201.06 * (1 - $performance_summary['approval_rate'] / 100) }}"/>
                            </svg>
                            <div class="petugas-progress-value">{{ $performance_summary['approval_rate'] }}%</div>
                        </div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Approval Rate
                        </p>
                    </div>

                    <!-- Total Input with Glass Container -->
                    <div class="text-center">
                        <div class="petugas-circular-progress">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $performance_summary['total_input'] }}
                                </span>
                            </div>
                        </div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Total Input
                        </p>
                    </div>

                    <!-- Net Income with Glass Container -->
                    <div class="text-center">
                        <div class="petugas-circular-progress">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <x-filament::icon
                                    icon="{{ $performance_summary['net_income'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down' }}"
                                    class="w-8 h-8 {{ $performance_summary['net_income'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}"
                                />
                            </div>
                        </div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Net Income
                        </p>
                        <p class="text-sm font-semibold mt-1 {{ $performance_summary['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            Rp {{ number_format($performance_summary['net_income'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                <!-- Additional Status Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 pt-6 border-t border-gray-200/20 dark:border-gray-700/20">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">15</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Antrian Aktif</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">8</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Selesai Hari Ini</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">3</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Pending Review</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">97%</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Satisfaction</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>