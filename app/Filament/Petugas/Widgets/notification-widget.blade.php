<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Notifikasi
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $total }} total, {{ $unread }} belum dibaca
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    @if($unread > 0)
                        <x-filament::button
                            size="sm"
                            color="gray"
                            wire:click="clearAll"
                        >
                            <x-filament::icon
                                icon="heroicon-o-check-circle"
                                class="w-4 h-4 mr-1"
                            />
                            Tandai Semua
                        </x-filament::button>
                    @endif
                    
                    <div class="flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                        <div class="w-2 h-2 bg-success-500 dark:bg-success-400 rounded-full animate-pulse"></div>
                        <span>{{ $last_updated }}</span>
                    </div>
                </div>
            </div>

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
                            wire:poll.30s
                        >
                            Coba Lagi
                        </x-filament::button>
                    </div>
                </div>
            @elseif(empty($notifications))
                <div class="flex items-center justify-center p-8">
                    <div class="text-center">
                        <x-filament::icon
                            icon="heroicon-o-bell-slash"
                            class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4"
                        />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            Tidak ada notifikasi
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Semua notifikasi akan muncul di sini
                        </p>
                    </div>
                </div>
            @else
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($notifications as $notification)
                        <div class="flex items-start space-x-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors {{ $notification['read_at'] ? 'bg-gray-50 dark:bg-gray-800/30' : 'bg-white dark:bg-gray-800' }}">
                            <div class="flex-shrink-0">
                                @if($notification['type'] === 'success')
                                    <div class="w-8 h-8 bg-success-100 dark:bg-success-900/30 rounded-full flex items-center justify-center">
                                        <x-filament::icon
                                            icon="heroicon-o-check-circle"
                                            class="w-4 h-4 text-success-600 dark:text-success-400"
                                        />
                                    </div>
                                @elseif($notification['type'] === 'warning')
                                    <div class="w-8 h-8 bg-warning-100 dark:bg-warning-900/30 rounded-full flex items-center justify-center">
                                        <x-filament::icon
                                            icon="heroicon-o-exclamation-triangle"
                                            class="w-4 h-4 text-warning-600 dark:text-warning-400"
                                        />
                                    </div>
                                @elseif($notification['type'] === 'error')
                                    <div class="w-8 h-8 bg-danger-100 dark:bg-danger-900/30 rounded-full flex items-center justify-center">
                                        <x-filament::icon
                                            icon="heroicon-o-x-circle"
                                            class="w-4 h-4 text-danger-600 dark:text-danger-400"
                                        />
                                    </div>
                                @else
                                    <div class="w-8 h-8 bg-info-100 dark:bg-info-900/30 rounded-full flex items-center justify-center">
                                        <x-filament::icon
                                            icon="heroicon-o-information-circle"
                                            class="w-4 h-4 text-info-600 dark:text-info-400"
                                        />
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $notification['title'] ?? 'Notifikasi' }}
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            {{ $notification['message'] ?? $notification['data']['message'] ?? 'Tidak ada pesan' }}
                                        </p>
                                        <div class="flex items-center space-x-2 mt-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() }}
                                            </span>
                                            @if(!$notification['read_at'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                                                    Baru
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if(!$notification['read_at'])
                                        <x-filament::button
                                            size="sm"
                                            color="gray"
                                            wire:click="markAsRead('{{ $notification['id'] }}')"
                                            class="ml-2"
                                        >
                                            <x-filament::icon
                                                icon="heroicon-o-check"
                                                class="w-3 h-3"
                                            />
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($total > count($notifications))
                    <div class="text-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <x-filament::button
                            size="sm"
                            color="gray"
                            outlined
                        >
                            Lihat Semua ({{ $total }})
                        </x-filament::button>
                    </div>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>