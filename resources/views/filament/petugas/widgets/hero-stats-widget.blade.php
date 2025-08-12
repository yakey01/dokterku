<x-filament-widgets::widget>
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
                        wire:poll.5s
                    >
                        Memuat ulang...
                    </x-filament::button>
                </div>
            </div>
        @else
            <!-- Hero Metrics Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($hero_metrics as $key => $metric)
                    <x-filament::card class="overflow-hidden hover:shadow-lg dark:hover:shadow-2xl transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-2xl">{{ $metric['icon'] }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $metric['label'] }}
                                        </p>
                                    </div>
                                </div>
                                
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                                    {{ $metric['value'] }}
                                </p>
                                
                                <div class="flex items-center space-x-1">
                                    @if($metric['trend']['direction'] === 'up')
                                        <x-filament::icon
                                            icon="heroicon-m-arrow-trending-up"
                                            class="w-4 h-4 text-success-500 dark:text-success-400"
                                        />
                                        <span class="text-xs text-success-600 dark:text-success-400 font-medium">
                                            {{ $metric['trend']['description'] }}
                                        </span>
                                    @elseif($metric['trend']['direction'] === 'down')
                                        <x-filament::icon
                                            icon="heroicon-m-arrow-trending-down"
                                            class="w-4 h-4 text-danger-500 dark:text-danger-400"
                                        />
                                        <span class="text-xs text-danger-600 dark:text-danger-400 font-medium">
                                            {{ $metric['trend']['description'] }}
                                        </span>
                                    @else
                                        <x-filament::icon
                                            icon="heroicon-m-minus"
                                            class="w-4 h-4 text-gray-500 dark:text-gray-400"
                                        />
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                            {{ $metric['trend']['description'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="ml-4">
                                <div class="w-12 h-12 bg-{{ $metric['color'] }}-100 dark:bg-{{ $metric['color'] }}-900/30 rounded-full flex items-center justify-center">
                                    @if($key === 'patients')
                                        <x-filament::icon
                                            icon="heroicon-o-users"
                                            class="w-6 h-6 text-{{ $metric['color'] }}-600 dark:text-{{ $metric['color'] }}-400"
                                        />
                                    @elseif($key === 'procedures')
                                        <x-filament::icon
                                            icon="heroicon-o-clipboard-document-list"
                                            class="w-6 h-6 text-{{ $metric['color'] }}-600 dark:text-{{ $metric['color'] }}-400"
                                        />
                                    @elseif($key === 'revenue')
                                        <x-filament::icon
                                            icon="heroicon-o-banknotes"
                                            class="w-6 h-6 text-{{ $metric['color'] }}-600 dark:text-{{ $metric['color'] }}-400"
                                        />
                                    @else
                                        <x-filament::icon
                                            icon="heroicon-o-chart-bar"
                                            class="w-6 h-6 text-{{ $metric['color'] }}-600 dark:text-{{ $metric['color'] }}-400"
                                        />
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-filament::card>
                @endforeach
            </div>

            <!-- Performance Summary -->
            <x-filament::card>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Ringkasan Performance
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Update terakhir: {{ $last_updated }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-success-500 dark:bg-success-400 rounded-full animate-pulse"></div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Live</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Efficiency Score -->
                        <div class="text-center">
                            <div class="relative w-16 h-16 mx-auto mb-2">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" 
                                            class="text-purple-500 dark:text-purple-400" 
                                            stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $performance_summary['efficiency_score'] / 100) }}"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $performance_summary['efficiency_score'] }}%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Efficiency</p>
                        </div>

                        <!-- Approval Rate -->
                        <div class="text-center">
                            <div class="relative w-16 h-16 mx-auto mb-2">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" 
                                            class="text-green-500 dark:text-green-400" 
                                            stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $performance_summary['approval_rate'] / 100) }}"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ $performance_summary['approval_rate'] }}%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Approval</p>
                        </div>

                        <!-- Total Input -->
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-2 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $performance_summary['total_input'] }}</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Input</p>
                        </div>

                        <!-- Net Income -->
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-2 flex items-center justify-center {{ $performance_summary['net_income'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-full">
                                <x-filament::icon
                                    icon="{{ $performance_summary['net_income'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down' }}"
                                    class="w-6 h-6 {{ $performance_summary['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}"
                                />
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Net Income</p>
                            <p class="text-xs font-medium {{ $performance_summary['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                Rp {{ number_format($performance_summary['net_income'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-widgets::widget>