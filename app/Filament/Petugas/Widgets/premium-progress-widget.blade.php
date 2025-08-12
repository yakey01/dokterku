<x-filament-widgets::widget>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @php 
            $progressData = $this->getProgressData();
            $totalProgress = $this->getTotalProgress();
        @endphp
        
        <!-- Progress Tracking Panel -->
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-500/10 dark:shadow-black/20 transition-all duration-700 hover:shadow-2xl hover:shadow-gray-500/20 dark:hover:shadow-black/40 hover:-translate-y-1">
            <!-- Animated background decoration -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full blur-3xl transition-all duration-1000 group-hover:scale-150 group-hover:rotate-45"></div>
            <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-gradient-to-br from-emerald-500/20 to-blue-500/20 rounded-full blur-2xl transition-all duration-1000 group-hover:scale-125 group-hover:-rotate-12"></div>
            
            <div class="relative z-10 p-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg shadow-blue-500/30 transition-all duration-500 group-hover:shadow-blue-500/50 group-hover:scale-110 group-hover:rotate-6">
                            <x-heroicon-o-chart-bar-square class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-300">Progress Entry Data</h3>
                            <p class="text-gray-600 dark:text-gray-400 transition-colors duration-300">Hari Ini - {{ now()->format('d M Y') }}</p>
                        </div>
                    </div>
                    
                    <!-- Overall Progress Ring -->
                    <div class="relative w-20 h-20">
                        <svg class="w-20 h-20 transform -rotate-90 transition-transform duration-500 group-hover:rotate-0" viewBox="0 0 36 36">
                            <path class="text-gray-200 dark:text-gray-700" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-blue-500 transition-all duration-1000 ease-out" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-dasharray="{{ $totalProgress['percentage'] }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ round($totalProgress['percentage']) }}%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Items -->
                <div class="space-y-6">
                    @foreach($progressData as $key => $item)
                        <div class="group/item relative overflow-hidden rounded-2xl bg-gradient-to-r from-{{ $item['color'] }}-50/50 to-{{ $item['color'] }}-100/50 dark:from-{{ $item['color'] }}-900/20 dark:to-{{ $item['color'] }}-800/20 border border-{{ $item['color'] }}-200/50 dark:border-{{ $item['color'] }}-700/30 p-6 transition-all duration-500 hover:shadow-lg hover:shadow-{{ $item['color'] }}-500/20 hover:scale-[1.02]">
                            <!-- Progress item decoration -->
                            <div class="absolute top-0 right-0 w-24 h-24 bg-{{ $item['color'] }}-500/10 rounded-full blur-xl transition-all duration-500 group-hover/item:scale-150"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-3 rounded-xl bg-{{ $item['color'] }}-500 shadow-lg shadow-{{ $item['color'] }}-500/30 transition-all duration-300 group-hover/item:shadow-{{ $item['color'] }}-500/50 group-hover/item:scale-110">
                                            @if($item['icon'] === 'heroicon-o-banknotes')
                                                <x-heroicon-o-banknotes class="w-5 h-5 text-white" />
                                            @elseif($item['icon'] === 'heroicon-o-arrow-trending-down')
                                                <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-white" />
                                            @elseif($item['icon'] === 'heroicon-o-users')
                                                <x-heroicon-o-users class="w-5 h-5 text-white" />
                                            @elseif($item['icon'] === 'heroicon-o-clipboard-document-list')
                                                <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-white" />
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white transition-colors duration-300">{{ $item['label'] }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $item['completed'] }}/{{ $item['target'] }} completed</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Badge -->
                                    @if($item['status'] === 'excellent')
                                        <div class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30">
                                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Excellent!</span>
                                        </div>
                                    @elseif($item['status'] === 'good')
                                        <div class="px-3 py-1 rounded-full bg-blue-500/20 border border-blue-500/30">
                                            <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">Good</span>
                                        </div>
                                    @elseif($item['status'] === 'average')
                                        <div class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/30">
                                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">Average</span>
                                        </div>
                                    @else
                                        <div class="px-3 py-1 rounded-full bg-red-500/20 border border-red-500/30">
                                            <span class="text-xs font-semibold text-red-700 dark:text-red-300">Need Focus</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="relative">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                                        <span class="text-sm font-bold text-{{ $item['color'] }}-700 dark:text-{{ $item['color'] }}-300">{{ round($item['percentage']) }}%</span>
                                    </div>
                                    
                                    <div class="relative h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <!-- Background glow effect -->
                                        <div class="absolute inset-0 bg-gradient-to-r from-{{ $item['color'] }}-200/50 to-{{ $item['color'] }}-300/50 dark:from-{{ $item['color'] }}-800/50 dark:to-{{ $item['color'] }}-700/50 rounded-full"></div>
                                        
                                        <!-- Progress fill with animation -->
                                        <div class="relative h-full bg-gradient-to-r from-{{ $item['color'] }}-500 to-{{ $item['color'] }}-600 rounded-full transition-all duration-1000 ease-out"
                                             style="width: {{ $item['percentage'] }}%">
                                            <!-- Shimmer effect -->
                                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent rounded-full transform -skew-x-12 animate-pulse"></div>
                                        </div>
                                        
                                        <!-- Completion sparkle effect -->
                                        @if($item['percentage'] >= 100)
                                            <div class="absolute right-1 top-1/2 transform -translate-y-1/2">
                                                <div class="w-2 h-2 bg-white rounded-full animate-ping"></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Summary Footer -->
                <div class="mt-8 p-6 rounded-2xl bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Total Progress Today</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $totalProgress['completed'] }} of {{ $totalProgress['target'] }} tasks completed</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ round($totalProgress['percentage']) }}%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                @if($totalProgress['status'] === 'excellent')
                                    🎉 Outstanding work!
                                @elseif($totalProgress['status'] === 'good')
                                    💪 Keep it up!
                                @elseif($totalProgress['status'] === 'average')
                                    📈 You're on track
                                @else
                                    🎯 Let's focus more
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Panel -->
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-500/10 dark:shadow-black/20 transition-all duration-700 hover:shadow-2xl hover:shadow-gray-500/20 dark:hover:shadow-black/40 hover:-translate-y-1">
            <!-- Animated background -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-gradient-to-br from-emerald-500/20 to-teal-600/20 rounded-full blur-3xl transition-all duration-1000 group-hover:scale-150 group-hover:-rotate-45"></div>
            
            <div class="relative z-10 p-8">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/30 transition-all duration-500 group-hover:shadow-emerald-500/50 group-hover:scale-110 group-hover:-rotate-6">
                        <x-heroicon-o-rocket-launch class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Quick Actions</h3>
                        <p class="text-gray-600 dark:text-gray-400">Speed up your workflow</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-4">
                    @php
                        $quickActions = [
                            ['title' => 'Daftar Pasien Baru', 'icon' => 'heroicon-o-user-plus', 'color' => 'emerald', 'url' => '#'],
                            ['title' => 'Input Tindakan', 'icon' => 'heroicon-o-clipboard-document-list', 'color' => 'blue', 'url' => '#'],
                            ['title' => 'Catat Pendapatan', 'icon' => 'heroicon-o-banknotes', 'color' => 'amber', 'url' => '#'],
                            ['title' => 'Input Pengeluaran', 'icon' => 'heroicon-o-arrow-trending-down', 'color' => 'red', 'url' => '#'],
                            ['title' => 'Lihat Laporan', 'icon' => 'heroicon-o-chart-bar', 'color' => 'purple', 'url' => '#'],
                            ['title' => 'Export Data', 'icon' => 'heroicon-o-arrow-down-tray', 'color' => 'indigo', 'url' => '#']
                        ];
                    @endphp
                    
                    @foreach($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="group/action relative overflow-hidden rounded-xl bg-gradient-to-r from-{{ $action['color'] }}-50 to-{{ $action['color'] }}-100 dark:from-{{ $action['color'] }}-900/20 dark:to-{{ $action['color'] }}-800/20 border border-{{ $action['color'] }}-200 dark:border-{{ $action['color'] }}-700/50 p-4 transition-all duration-300 hover:shadow-lg hover:shadow-{{ $action['color'] }}-500/25 hover:scale-105 hover:-translate-y-1">
                            <div class="absolute top-0 right-0 w-16 h-16 bg-{{ $action['color'] }}-500/10 rounded-full blur-lg transition-all duration-300 group-hover/action:scale-150"></div>
                            
                            <div class="relative z-10 flex items-center space-x-4">
                                <div class="p-3 rounded-lg bg-{{ $action['color'] }}-500 shadow-md shadow-{{ $action['color'] }}-500/30 transition-all duration-300 group-hover/action:shadow-{{ $action['color'] }}-500/50 group-hover/action:scale-110">
                                    @if($action['icon'] === 'heroicon-o-user-plus')
                                        <x-heroicon-o-user-plus class="w-5 h-5 text-white" />
                                    @elseif($action['icon'] === 'heroicon-o-clipboard-document-list')
                                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-white" />
                                    @elseif($action['icon'] === 'heroicon-o-banknotes')
                                        <x-heroicon-o-banknotes class="w-5 h-5 text-white" />
                                    @elseif($action['icon'] === 'heroicon-o-arrow-trending-down')
                                        <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-white" />
                                    @elseif($action['icon'] === 'heroicon-o-chart-bar')
                                        <x-heroicon-o-chart-bar class="w-5 h-5 text-white" />
                                    @elseif($action['icon'] === 'heroicon-o-arrow-down-tray')
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-white" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 dark:text-white transition-colors duration-300 group-hover/action:text-{{ $action['color'] }}-700 dark:group-hover/action:text-{{ $action['color'] }}-300">{{ $action['title'] }}</h4>
                                </div>
                                <div class="opacity-50 transition-all duration-300 group-hover/action:opacity-100 group-hover/action:translate-x-1">
                                    <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>