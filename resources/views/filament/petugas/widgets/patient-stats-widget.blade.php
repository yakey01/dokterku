<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $stats = $this->getPatientStats();
            $trend = $this->getMonthlyTrend();
        @endphp
        
        <div class="glass-card bg-cyan-50/70 dark:bg-cyan-900/20 p-4 rounded-lg">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                👥 Statistik Pasien
            </h3>
            
            <!-- Stats Overview -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xl font-bold text-blue-600">{{ number_format($stats['counts']['today']) }}</div>
                    <div class="text-xs text-gray-600">Hari Ini</div>
                </div>
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xl font-bold text-green-600">{{ number_format($stats['counts']['month']) }}</div>
                    <div class="text-xs text-gray-600">Bulan Ini</div>
                </div>
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xl font-bold text-purple-600">{{ number_format($stats['counts']['year']) }}</div>
                    <div class="text-xs text-gray-600">Tahun Ini</div>
                </div>
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xl font-bold text-orange-600">{{ number_format($stats['counts']['total']) }}</div>
                    <div class="text-xs text-gray-600">Total</div>
                </div>
            </div>
            
            <!-- Status Summary -->
            <div class="bg-white/40 dark:bg-gray-800/40 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-bold text-gray-700 mb-3">Status Verifikasi</h4>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">✅ Verified</span>
                        <span class="font-bold text-green-600">{{ $stats['status']['verified'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">⏳ Pending</span>
                        <span class="font-bold text-yellow-600">{{ $stats['status']['pending'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">❌ Rejected</span>
                        <span class="font-bold text-red-600">{{ $stats['status']['rejected'] }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Gender Distribution -->
            <div class="bg-white/40 dark:bg-gray-800/40 rounded-lg p-4">
                <h4 class="text-sm font-bold text-gray-700 mb-3">👫 Jenis Kelamin</h4>
                <div class="flex justify-between text-sm">
                    <span class="text-blue-600">👨 {{ $stats['gender']['male'] }}</span>
                    <span class="text-pink-600">👩 {{ $stats['gender']['female'] }}</span>
                </div>
            </div>
        </div>
    </x-filament::section>

</x-filament-widgets::widget>