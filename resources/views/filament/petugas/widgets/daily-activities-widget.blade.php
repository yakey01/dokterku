<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $activities = $this->getDailyActivities();
            $hourlyActivity = $this->getHourlyActivity();
        @endphp
        
        <div class="glass-card bg-emerald-50/70 dark:bg-emerald-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    📊 Aktivitas Harian
                </h3>
                <div class="text-lg font-bold text-emerald-600">
                    {{ $activities['counts']['new_patients'] + $activities['counts']['medical_actions'] + $activities['counts']['revenue_entries'] }}
                </div>
            </div>
            
            <!-- Activity Summary -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="text-center p-3 bg-blue-50/70 rounded">
                    <div class="text-lg font-bold text-blue-600">{{ $activities['counts']['new_patients'] }}</div>
                    <div class="text-xs text-gray-600">👥 Pasien</div>
                </div>
                <div class="text-center p-3 bg-green-50/70 rounded">
                    <div class="text-lg font-bold text-green-600">{{ $activities['counts']['medical_actions'] }}</div>
                    <div class="text-xs text-gray-600">🩺 Tindakan</div>
                </div>
                <div class="text-center p-3 bg-amber-50/70 rounded">
                    <div class="text-lg font-bold text-amber-600">{{ $activities['counts']['revenue_entries'] }}</div>
                    <div class="text-xs text-gray-600">💰 Revenue</div>
                </div>
                <div class="text-center p-3 bg-orange-50/70 rounded">
                    <div class="text-lg font-bold text-orange-600">{{ $activities['counts']['pending_verification'] }}</div>
                    <div class="text-xs text-gray-600">⏳ Pending</div>
                </div>
            </div>
            
            <!-- Performance Summary -->
            <div class="bg-white/40 dark:bg-gray-800/40 rounded-lg p-3">
                <h4 class="text-sm font-bold text-gray-700 mb-2">⚡ Performance</h4>
                <div class="flex justify-between text-sm">
                    <span>Efficiency: {{ $activities['performance_metrics']['daily_efficiency'] }}</span>
                    <span>Completion: {{ $activities['performance_metrics']['completion_rate'] }}%</span>
                </div>
            </div>
        </div>
    </x-filament::section>

</x-filament-widgets::widget>