<x-filament-widgets::widget>
    <x-filament::section>
        <div class="p-6 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl border border-amber-200 dark:border-gray-700">
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
                Selamat Datang, {{ $this->getViewData()['user_name'] }}!
            </h1>
            <p class="text-gray-600 dark:text-gray-300 mt-2">
                Dashboard Petugas • {{ now()->format('l, d F Y') }}
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($this->getViewData()['stats'] as $stat)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30 flex items-center justify-center">
                            @switch($stat['icon'])
                                @case('users')
                                    <svg class="w-6 h-6 text-{{ $stat['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    @break
                                @case('currency-dollar')
                                    <svg class="w-6 h-6 text-{{ $stat['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    @break
                                @case('clipboard-list')
                                    <svg class="w-6 h-6 text-{{ $stat['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    @break
                                @case('banknotes')
                                    <svg class="w-6 h-6 text-{{ $stat['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    @break
                            @endswitch
                        </div>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">
                            {{ $stat['trend'] }}
                        </span>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $stat['title'] }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
/* Premium Dashboard Styling */
.premium-dashboard-widget {
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .premium-dashboard-widget .grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>