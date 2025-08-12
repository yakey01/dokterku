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
                            wire:poll.120s
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
                            Performance Tracker
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Pantau produktivitas dan kualitas kerja Anda
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 bg-{{ $shift_info['status']['color'] }}-500 rounded-full"></span>
                            <span>{{ $shift_info['status']['label'] }}</span>
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $last_updated }}
                        </span>
                    </div>
                </div>

                <!-- Shift Progress -->
                <x-filament::card>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-lg">{{ $shift_info['status']['icon'] }}</span>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Jam Kerja Hari Ini
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $shift_info['work_start'] }} - {{ $shift_info['work_end'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ $shift_info['elapsed_hours'] }}h
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    dari 8 jam
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="relative">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div 
                                    class="bg-{{ $shift_info['status']['color'] }}-500 dark:bg-{{ $shift_info['status']['color'] }}-400 h-3 rounded-full transition-all duration-300"
                                    style="width: {{ $shift_info['progress_percentage'] }}%"
                                ></div>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-medium text-white mix-blend-difference">
                                    {{ round($shift_info['progress_percentage']) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </x-filament::card>

                <!-- Performance Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Productivity Score -->
                    <x-filament::card class="text-center">
                        <div class="p-4">
                            <div class="relative w-16 h-16 mx-auto mb-3">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" 
                                            class="text-blue-500 dark:text-blue-400" 
                                            stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $work_metrics['productivity_score'] / 100) }}"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $work_metrics['productivity_score'] }}%</span>
                                </div>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Produktivitas</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $work_metrics['inputs_per_hour'] }}/jam</p>
                        </div>
                    </x-filament::card>

                    <!-- Efficiency Score -->
                    <x-filament::card class="text-center">
                        <div class="p-4">
                            <div class="relative w-16 h-16 mx-auto mb-3">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" 
                                            class="text-green-500 dark:text-green-400" 
                                            stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $work_metrics['efficiency_score'] / 100) }}"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $work_metrics['efficiency_score'] }}%</span>
                                </div>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Efisiensi</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Waktu optimal</p>
                        </div>
                    </x-filament::card>

                    <!-- Quality Score -->
                    <x-filament::card class="text-center">
                        <div class="p-4">
                            <div class="relative w-16 h-16 mx-auto mb-3">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" 
                                            class="text-purple-500 dark:text-purple-400" 
                                            stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $work_metrics['quality_score'] / 100) }}"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $work_metrics['quality_score'] }}%</span>
                                </div>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Kualitas</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Data akurat</p>
                        </div>
                    </x-filament::card>

                    <!-- Approval Rate -->
                    <x-filament::card class="text-center">
                        <div class="p-4">
                            <div class="relative w-16 h-16 mx-auto mb-3">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" 
                                            class="text-emerald-500 dark:text-emerald-400" 
                                            stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $work_metrics['approval_rate'] / 100) }}"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $work_metrics['approval_rate'] }}%</span>
                                </div>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Approval</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Data disetujui</p>
                        </div>
                    </x-filament::card>
                </div>

                <!-- Summary Stats -->
                <x-filament::card>
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Ringkasan Aktivitas Hari Ini
                        </h4>
                        
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $work_metrics['total_inputs'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Total Input</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $work_metrics['inputs_per_hour'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Input/Jam</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ $work_metrics['work_hours'] }}h
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Jam Kerja</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-2xl font-bold {{ $work_metrics['pending_tasks'] > 5 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $work_metrics['pending_tasks'] }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
                            </div>
                        </div>
                    </div>
                </x-filament::card>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>