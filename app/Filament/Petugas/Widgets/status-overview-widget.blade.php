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
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Notifications Card --}}
                <div class="petugas-glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Notifikasi
                        </h3>
                        <div class="petugas-live-indicator">
                            <div class="petugas-live-dot"></div>
                            <span class="text-xs">Live</span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($notifications as $notification)
                            <div class="flex items-start space-x-3 p-3 rounded-xl" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                               {{ $notification['color'] === 'red' ? 'bg-red-100 dark:bg-red-900/30' : '' }}
                                               {{ $notification['color'] === 'blue' ? 'bg-blue-100 dark:bg-blue-900/30' : '' }}
                                               {{ $notification['color'] === 'green' ? 'bg-green-100 dark:bg-green-900/30' : '' }}">
                                        <x-filament::icon
                                            :icon="$notification['icon']"
                                            class="w-4 h-4 
                                                   {{ $notification['color'] === 'red' ? 'text-red-600 dark:text-red-400' : '' }}
                                                   {{ $notification['color'] === 'blue' ? 'text-blue-600 dark:text-blue-400' : '' }}
                                                   {{ $notification['color'] === 'green' ? 'text-green-600 dark:text-green-400' : '' }}"
                                        />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $notification['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $notification['message'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ $notification['time'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada notifikasi baru</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Pending Tasks Card --}}
                <div class="petugas-glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Tugas Pending
                        </h3>
                        <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-1 rounded-full">
                            {{ count($pending_tasks) }} tugas
                        </span>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($pending_tasks as $task)
                            <div class="p-4 rounded-xl" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $task['title'] }}
                                    </h4>
                                    <span class="text-xs px-2 py-1 rounded-full
                                               {{ $task['priority'] === 'high' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : '' }}
                                               {{ $task['priority'] === 'medium' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' : '' }}
                                               {{ $task['priority'] === 'low' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : '' }}">
                                        {{ ucfirst($task['priority']) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                    {{ $task['description'] }}
                                </p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $task['due_date'] }}
                                    </span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full">
                                            <div class="h-2 bg-blue-500 rounded-full transition-all duration-500" 
                                                 style="width: {{ $task['progress'] }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $task['progress'] }}%</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Semua tugas selesai!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Today's Schedule Card --}}
                <div class="petugas-glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Jadwal Hari Ini
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ now()->format('d M Y') }}
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($today_schedule as $schedule)
                            <div class="flex items-center space-x-3 p-3 rounded-xl transition-all duration-200
                                       {{ $schedule['status'] === 'completed' ? 'opacity-60' : '' }}" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <div class="flex-shrink-0">
                                    <div class="w-3 h-3 rounded-full
                                               {{ $schedule['status'] === 'completed' ? 'bg-green-500' : '' }}
                                               {{ $schedule['status'] === 'in_progress' ? 'bg-blue-500 animate-pulse' : '' }}
                                               {{ $schedule['status'] === 'pending' ? 'bg-gray-400' : '' }}">
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $schedule['time'] }}
                                        </span>
                                        <span class="text-xs px-2 py-1 rounded-full
                                                   {{ $schedule['status'] === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : '' }}
                                                   {{ $schedule['status'] === 'in_progress' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : '' }}
                                                   {{ $schedule['status'] === 'pending' ? 'bg-gray-100 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400' : '' }}">
                                            {{ $schedule['status'] === 'completed' ? 'Selesai' : '' }}
                                            {{ $schedule['status'] === 'in_progress' ? 'Berlangsung' : '' }}
                                            {{ $schedule['status'] === 'pending' ? 'Pending' : '' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $schedule['activity'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada jadwal hari ini</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Patient Queue and System Alerts Row --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Patient Queue Card --}}
                <div class="petugas-glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Antrian Pasien
                        </h3>
                        <div class="petugas-live-indicator">
                            <div class="petugas-live-dot"></div>
                            <span class="text-xs">{{ count($patient_queue) }} menunggu</span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($patient_queue as $patient)
                            <div class="flex items-center space-x-3 p-3 rounded-xl
                                       {{ $patient['status'] === 'urgent' ? 'border border-red-200 dark:border-red-800' : '' }}" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm
                                               {{ $patient['priority'] === 'urgent' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' }}">
                                        {{ $patient['number'] }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $patient['name'] }}
                                        </p>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $patient['wait_time'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $patient['type'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada antrian</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- System Alerts Card --}}
                <div class="petugas-glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Alert Sistem
                        </h3>
                        <span class="text-xs bg-gray-100 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-full">
                            {{ count($system_alerts) }} alert
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($system_alerts as $alert)
                            <div class="flex items-start space-x-3 p-3 rounded-xl" 
                                 style="background: var(--glass-bg-light); backdrop-filter: blur(10px); border: 1px solid var(--glass-border-light);">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                               {{ $alert['type'] === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                                        <x-filament::icon
                                            icon="{{ $alert['type'] === 'warning' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-information-circle' }}"
                                            class="w-4 h-4 
                                                   {{ $alert['type'] === 'warning' ? 'text-yellow-600 dark:text-yellow-400' : 'text-blue-600 dark:text-blue-400' }}"
                                        />
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $alert['message'] }}
                                    </p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $alert['action'] }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $alert['time'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <x-filament::icon
                                    icon="heroicon-o-check-circle"
                                    class="w-12 h-12 mx-auto text-green-500 dark:text-green-400 mb-2"
                                />
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Sistem berjalan normal</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Footer with Last Updated --}}
            <div class="petugas-glass-card">
                <div class="flex items-center justify-center py-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Terakhir diperbarui: {{ $last_updated }}
                    </span>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>