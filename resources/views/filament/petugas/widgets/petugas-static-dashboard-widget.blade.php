<x-filament-widgets::widget>
    <div class="fi-wi-static">
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Dashboard Petugas</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Static Pasien Card -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Pasien</span>
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Hari ini</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">-</span>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Bulan ini</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Static Tindakan Card -->
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">Tindakan</span>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Hari ini</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">-</span>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Bulan ini</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Static Pendapatan Card -->
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-purple-600 dark:text-purple-400">Pendapatan</span>
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Hari ini</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">Rp -</span>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Bulan ini</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">Rp -</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>