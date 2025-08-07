/**
 * Petugas Dashboard Charts - Isolated ApexCharts Implementation
 * This script is specifically designed for the Petugas dashboard
 * and uses unique IDs to avoid conflicts with Filament components
 */

(function() {
    'use strict';
    
    // Namespace for Petugas charts to avoid global conflicts
    window.petugasCharts = {
        charts: {},
        
        // Configuration for consistent theming
        getBaseConfig: function(isDark = false) {
            return {
                chart: {
                    fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        }
                    },
                    background: 'transparent'
                },
                theme: {
                    mode: isDark ? 'dark' : 'light'
                },
                colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4'],
                grid: {
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    strokeDashArray: 3
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    style: {
                        fontSize: '12px',
                        fontFamily: 'Inter, system-ui, -apple-system, sans-serif'
                    }
                }
            };
        },

        // Revenue Chart (Line/Area Chart)
        initRevenueChart: function() {
            const isDark = document.documentElement.classList.contains('dark');
            const element = document.querySelector('#revenue-chart');
            
            if (!element) return;

            const options = {
                ...this.getBaseConfig(isDark),
                series: [{
                    name: 'Pendapatan',
                    data: [
                        { x: 'Sen', y: 2100000 },
                        { x: 'Sel', y: 2800000 },
                        { x: 'Rab', y: 2200000 },
                        { x: 'Kam', y: 3100000 },
                        { x: 'Jum', y: 2900000 },
                        { x: 'Sab', y: 3500000 },
                        { x: 'Min', y: 2400000 }
                    ]
                }],
                chart: {
                    ...this.getBaseConfig(isDark).chart,
                    type: 'area',
                    height: 260,
                    id: 'petugas-revenue-chart'
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        type: 'vertical',
                        colorStops: [
                            {
                                offset: 0,
                                color: '#10b981',
                                opacity: 0.4
                            },
                            {
                                offset: 100,
                                color: '#10b981',
                                opacity: 0.1
                            }
                        ]
                    }
                },
                xaxis: {
                    type: 'category',
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                            fontSize: '12px'
                        },
                        formatter: function(value) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 6,
                    colors: ['#10b981'],
                    strokeColors: '#ffffff',
                    strokeWidth: 2,
                    hover: {
                        size: 8
                    }
                }
            };

            this.charts.revenue = new ApexCharts(element, options);
            this.charts.revenue.render();
        },

        // Donut Chart for procedure distribution
        initDonutChart: function() {
            const isDark = document.documentElement.classList.contains('dark');
            const element = document.querySelector('#donut-chart');
            
            if (!element) return;

            const options = {
                ...this.getBaseConfig(isDark),
                series: [45, 25, 20, 10],
                chart: {
                    ...this.getBaseConfig(isDark).chart,
                    type: 'donut',
                    height: 260,
                    id: 'petugas-donut-chart'
                },
                labels: ['Pemeriksaan Umum', 'Konsultasi', 'Tindakan Khusus', 'Follow Up'],
                colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: isDark ? '#f3f4f6' : '#374151',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => {
                                            return a + b;
                                        }, 0) + ' Tindakan';
                                    }
                                },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontWeight: 700,
                                    color: isDark ? '#f3f4f6' : '#374151'
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '12px',
                    fontWeight: 500,
                    labels: {
                        colors: isDark ? '#d1d5db' : '#6b7280'
                    },
                    markers: {
                        width: 8,
                        height: 8,
                        radius: 2
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '12px',
                        fontWeight: 600,
                        colors: ['#ffffff']
                    },
                    formatter: function(val) {
                        return Math.round(val) + '%';
                    }
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        chart: {
                            height: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            this.charts.donut = new ApexCharts(element, options);
            this.charts.donut.render();
        },

        // Update charts when data changes
        updateCharts: function() {
            // Generate new random data for demonstration
            const newRevenueData = [
                { x: 'Sen', y: Math.floor(Math.random() * 2000000) + 1500000 },
                { x: 'Sel', y: Math.floor(Math.random() * 2000000) + 1500000 },
                { x: 'Rab', y: Math.floor(Math.random() * 2000000) + 1500000 },
                { x: 'Kam', y: Math.floor(Math.random() * 2000000) + 1500000 },
                { x: 'Jum', y: Math.floor(Math.random() * 2000000) + 1500000 },
                { x: 'Sab', y: Math.floor(Math.random() * 2000000) + 1500000 },
                { x: 'Min', y: Math.floor(Math.random() * 2000000) + 1500000 }
            ];

            const newDonutData = [
                Math.floor(Math.random() * 30) + 30, // 30-60
                Math.floor(Math.random() * 20) + 15, // 15-35
                Math.floor(Math.random() * 15) + 10, // 10-25
                Math.floor(Math.random() * 10) + 5   // 5-15
            ];

            if (this.charts.revenue) {
                this.charts.revenue.updateSeries([{
                    name: 'Pendapatan',
                    data: newRevenueData
                }]);
            }

            if (this.charts.donut) {
                this.charts.donut.updateSeries(newDonutData);
            }
        },

        // Handle dark mode changes
        updateTheme: function() {
            const isDark = document.documentElement.classList.contains('dark');
            
            // Re-render charts with new theme
            this.destroy();
            setTimeout(() => {
                this.init();
            }, 100);
        },

        // Destroy all charts
        destroy: function() {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            this.charts = {};
        },

        // Initialize all charts
        init: function() {
            try {
                this.initRevenueChart();
                this.initDonutChart();
                
                // Listen for theme changes
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            this.updateTheme();
                        }
                    });
                });
                
                observer.observe(document.documentElement, { 
                    attributes: true, 
                    attributeFilter: ['class'] 
                });
                
            } catch (error) {
                console.error('Error initializing Petugas charts:', error);
            }
        }
    };

    // Auto-initialize when ApexCharts is loaded
    if (typeof ApexCharts !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
            window.petugasCharts.init();
        });
    } else {
        console.warn('ApexCharts not loaded. Charts will not be displayed.');
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        Object.values(window.petugasCharts.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    });

})();