<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $financial = $this->getFinancialData();
            $weeklyTrend = $this->getWeeklyTrend();
        @endphp
        
        <div class="glass-card bg-amber-50/70 dark:bg-amber-900/20 p-4 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    💰 Ringkasan Keuangan
                </h3>
                <div class="text-center">
                    <div class="text-sm font-bold text-gray-700">{{ $financial['financial_health']['score'] }}</div>
                    <div class="text-xs {{ $financial['financial_health']['status'] === 'Excellent' ? 'text-green-600' : ($financial['financial_health']['status'] === 'Good' ? 'text-blue-600' : ($financial['financial_health']['status'] === 'Fair' ? 'text-yellow-600' : 'text-red-600')) }}">
                        {{ $financial['financial_health']['status'] }}
                    </div>
                </div>
            </div>
            
            <!-- Financial Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">
                <!-- Daily -->
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xs text-gray-500 mb-1">Hari Ini</div>
                    <div class="text-lg font-bold text-green-600">
                        Rp {{ number_format($financial['daily']['pendapatan'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs {{ $financial['daily']['net_income'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        Net: Rp {{ number_format($financial['daily']['net_income'], 0, ',', '.') }}
                    </div>
                </div>
                
                <!-- Monthly -->
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xs text-gray-500 mb-1">Bulan Ini</div>
                    <div class="text-lg font-bold text-green-600">
                        Rp {{ number_format($financial['monthly']['pendapatan'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs {{ $financial['monthly']['net_income'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        Net: Rp {{ number_format($financial['monthly']['net_income'], 0, ',', '.') }}
                    </div>
                </div>
                
                <!-- Yearly -->
                <div class="text-center p-3 bg-white/60 dark:bg-gray-800/60 rounded">
                    <div class="text-xs text-gray-500 mb-1">Tahun Ini</div>
                    <div class="text-lg font-bold text-green-600">
                        Rp {{ number_format($financial['yearly']['pendapatan'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs {{ $financial['yearly']['net_income'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        Net: Rp {{ number_format($financial['yearly']['net_income'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
            
            <!-- Financial Health Recommendation -->
            <div class="bg-white/40 rounded-lg p-3">
                <div class="flex items-center text-sm">
                    <x-filament::icon 
                        icon="{{ $financial['financial_health']['status'] === 'Excellent' || $financial['financial_health']['status'] === 'Good' ? 'heroicon-o-light-bulb' : 'heroicon-o-exclamation-triangle' }}" 
                        class="w-4 h-4 text-{{ $financial['financial_health']['status'] === 'Excellent' ? 'green' : ($financial['financial_health']['status'] === 'Good' ? 'blue' : ($financial['financial_health']['status'] === 'Fair' ? 'yellow' : 'red')) }}-600 mr-2" 
                    />
                    <span class="text-gray-700">{{ $financial['financial_health']['recommendation'] }}</span>
                </div>
            </div>
        </div>
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Financial Trend Chart
            const trendCtx = document.getElementById('financialTrendChart');
            if (trendCtx) {
                const trendData = @json($weeklyTrend);
                
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendData.days,
                        datasets: [
                            {
                                label: 'Pendapatan',
                                data: trendData.pendapatan,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Pengeluaran',
                                data: trendData.pengeluaran,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Net Income',
                                data: trendData.net_income,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        family: 'Inter, sans-serif',
                                        size: 11,
                                        weight: 500
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#374151',
                                bodyColor: '#6B7280',
                                borderColor: 'rgba(0, 0, 0, 0.1)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                titleFont: {
                                    family: 'Inter, sans-serif',
                                    size: 12,
                                    weight: 600
                                },
                                bodyFont: {
                                    family: 'Inter, sans-serif',
                                    size: 11
                                },
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': Rp ' + 
                                               new Intl.NumberFormat('id-ID').format(context.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        family: 'Inter, sans-serif',
                                        size: 10
                                    },
                                    color: '#9CA3AF'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        family: 'Inter, sans-serif',
                                        size: 10
                                    },
                                    color: '#9CA3AF',
                                    callback: function(value) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        }).format(value);
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1200,
                            easing: 'easeInOutCubic'
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-filament-widgets::widget>