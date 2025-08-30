{{-- World-Class Jaspel Detail Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Jaspel Breakdown Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            📊 Jaspel Breakdown
        </h3>
        <canvas id="jaspelBreakdownChart" width="400" height="300"></canvas>
    </div>

    {{-- Monthly Trend Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            📈 Monthly Trend
        </h3>
        <canvas id="monthlyTrendChart" width="400" height="300"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // World-Class Chart Configuration
    const chartConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12,
                        family: 'Inter, system-ui, sans-serif'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                titleColor: '#F9FAFB',
                bodyColor: '#F9FAFB',
                borderColor: '#374151',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': Rp ' + 
                               new Intl.NumberFormat('id-ID').format(context.parsed);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(156, 163, 175, 0.1)'
                },
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                            notation: 'compact',
                            compactDisplay: 'short'
                        }).format(value);
                    }
                }
            }
        }
    };

    // Jaspel Breakdown Pie Chart
    const breakdownCtx = document.getElementById('jaspelBreakdownChart').getContext('2d');
    new Chart(breakdownCtx, {
        type: 'doughnut',
        data: {
            labels: ['Tindakan Medis', 'Pasien Harian'],
            datasets: [{
                data: [{{ $tindakanJaspel ?? 45000 }}, {{ $pasienJaspel ?? 695000 }}],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',   // Blue for Tindakan
                    'rgba(16, 185, 129, 0.8)',   // Green for Pasien
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                ],
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            ...chartConfig,
            cutout: '60%',
            plugins: {
                ...chartConfig.plugins,
                legend: {
                    ...chartConfig.plugins.legend,
                    position: 'right'
                }
            }
        }
    });

    // Monthly Trend Line Chart
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jul 2025', 'Aug 2025', 'Sep 2025', 'Oct 2025', 'Nov 2025', 'Dec 2025'],
            datasets: [{
                label: 'Jaspel Monthly',
                data: [650000, 740000, 820000, 780000, 890000, 920000],
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            ...chartConfig,
            elements: {
                point: {
                    hoverBackgroundColor: 'rgba(16, 185, 129, 1)',
                }
            }
        }
    });

    // Add animation and interactivity
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stats on load
        const statsElements = document.querySelectorAll('[data-animate="counter"]');
        statsElements.forEach(el => {
            const target = parseInt(el.dataset.target);
            const duration = 2000;
            const start = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(progress * target);
                
                el.textContent = new Intl.NumberFormat('id-ID').format(current);
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        });
    });
</script>

<style>
    /* World-Class Styling */
    .jaspel-detail-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 2rem;
    }
    
    .detail-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 1rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .stat-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 0.75rem;
        padding: 1.5rem;
        border: 1px solid rgba(59, 130, 246, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
    }
    
    .procedure-item {
        background: linear-gradient(90deg, #fafafa 0%, #f8fafc 100%);
        border-left: 4px solid #3b82f6;
        padding: 1rem;
        margin: 0.5rem 0;
        border-radius: 0 0.5rem 0.5rem 0;
        transition: all 0.2s ease;
    }
    
    .procedure-item:hover {
        background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 100%);
        border-left-color: #1d4ed8;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .animate-delay-100 { animation-delay: 0.1s; }
    .animate-delay-200 { animation-delay: 0.2s; }
    .animate-delay-300 { animation-delay: 0.3s; }
</style>