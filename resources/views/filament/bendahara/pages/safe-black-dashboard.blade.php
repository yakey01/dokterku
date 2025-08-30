<x-filament-panels::page>
    <!-- SINGLE ROOT ELEMENT - STRICT LIVEWIRE COMPLIANCE -->
    <div id="bendahara-dashboard-safe-root" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 0; margin: 0; position: relative;">
        
        @php
            $financial = $this->getFinancialSummary();
            $validation = $this->getValidationMetrics();
            $trends = $this->getMonthlyTrends();
            $activities = $this->getRecentActivities();
        @endphp

        <!-- Inline CSS untuk glassmorphic effects -->
        <style>
            #bendahara-dashboard-safe-root {
                --black-primary: #0a0a0b;
                --black-secondary: #111118;
                --black-border: #333340;
                --white-text: #fafafa;
                --muted-text: #a1a1aa;
                --subtle-text: #71717a;
                --green-success: #22d65f;
                --red-danger: #f87171;
                --blue-info: #60a5fa;
                --purple-accent: #a855f7;
                --amber-warning: #fbbf24;
            }
            
            @keyframes pulse-glow {
                0%, 100% { opacity: 1; box-shadow: 0 0 5px rgba(251, 191, 36, 0.3); }
                50% { opacity: 0.8; box-shadow: 0 0 15px rgba(251, 191, 36, 0.5); }
            }
            
            @keyframes card-enter {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .card-animated {
                animation: card-enter 0.6s ease-out forwards;
            }
        </style>

        <!-- Dashboard Content -->
        <div style="padding: 0; margin: 0;">
            
            <!-- Metrics Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 32px;">
                
                <!-- Revenue Card -->
                <div class="card-animated" style="
                    background: linear-gradient(135deg, var(--black-primary) 0%, var(--black-secondary) 100%);
                    border: 1px solid var(--black-border);
                    border-radius: 16px;
                    padding: 24px;
                    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(10px);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                    animation-delay: 0.1s;
                " 
                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='var(--black-border)';">
                    
                    <!-- Glassmorphic overlay -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(34, 214, 95, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(34, 214, 95, 0.2) 0%, rgba(34, 214, 95, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(34, 214, 95, 0.3);">
                                <x-filament::icon icon="heroicon-s-banknotes" style="width: 24px; height: 24px; color: var(--green-success); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" />
                            </div>
                            <div>
                                <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Revenue</h3>
                                <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">This month</p>
                            </div>
                        </div>
                        @if($financial['growth']['revenue'] != 0)
                            <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['revenue'] > 0 ? 'var(--green-success)' : 'var(--red-danger)' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
                                <x-filament::icon icon="heroicon-s-arrow-trending-{{ $financial['growth']['revenue'] > 0 ? 'up' : 'down' }}" style="width: 14px; height: 14px;" />
                                <span style="font-size: 11px; font-weight: 600; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">{{ $financial['growth']['revenue'] > 0 ? '+' : '' }}{{ $financial['growth']['revenue'] }}%</span>
                            </div>
                        @endif
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: var(--white-text); font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                        Rp {{ number_format($financial['current']['revenue'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- Expenses Card -->
                <div class="card-animated" style="
                    background: linear-gradient(135deg, var(--black-primary) 0%, var(--black-secondary) 100%);
                    border: 1px solid var(--black-border);
                    border-radius: 16px;
                    padding: 24px;
                    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(10px);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                    animation-delay: 0.2s;
                " 
                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='var(--black-border)';">
                    
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(248, 113, 113, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(248, 113, 113, 0.2) 0%, rgba(248, 113, 113, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(248, 113, 113, 0.3);">
                                <x-filament::icon icon="heroicon-s-arrow-trending-down" style="width: 24px; height: 24px; color: var(--red-danger); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" />
                            </div>
                            <div>
                                <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Expenses</h3>
                                <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">This month</p>
                            </div>
                        </div>
                        @if($financial['growth']['expenses'] != 0)
                            <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['expenses'] > 0 ? 'var(--red-danger)' : 'var(--green-success)' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
                                <x-filament::icon icon="heroicon-s-arrow-trending-{{ $financial['growth']['expenses'] > 0 ? 'up' : 'down' }}" style="width: 14px; height: 14px;" />
                                <span style="font-size: 11px; font-weight: 600; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">{{ $financial['growth']['expenses'] > 0 ? '+' : '' }}{{ $financial['growth']['expenses'] }}%</span>
                            </div>
                        @endif
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: var(--white-text); font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                        Rp {{ number_format($financial['current']['expenses'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- Net Income Card -->
                <div class="card-animated" style="
                    background: linear-gradient(135deg, var(--black-primary) 0%, var(--black-secondary) 100%);
                    border: 1px solid var(--black-border);
                    border-radius: 16px;
                    padding: 24px;
                    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(10px);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                    animation-delay: 0.3s;
                " 
                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='var(--black-border)';">
                    
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(96, 165, 250, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(96, 165, 250, 0.2) 0%, rgba(96, 165, 250, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(96, 165, 250, 0.3);">
                                <x-filament::icon icon="heroicon-s-chart-bar" style="width: 24px; height: 24px; color: var(--blue-info); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" />
                            </div>
                            <div>
                                <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Net Income</h3>
                                <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">This month</p>
                            </div>
                        </div>
                        @if($financial['growth']['net_income'] != 0)
                            <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['net_income'] > 0 ? 'var(--green-success)' : 'var(--red-danger)' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
                                <x-filament::icon icon="heroicon-s-arrow-trending-{{ $financial['growth']['net_income'] > 0 ? 'up' : 'down' }}" style="width: 14px; height: 14px;" />
                                <span style="font-size: 11px; font-weight: 600; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">{{ $financial['growth']['net_income'] > 0 ? '+' : '' }}{{ $financial['growth']['net_income'] }}%</span>
                            </div>
                        @endif
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $financial['current']['net_income'] >= 0 ? 'var(--green-success)' : 'var(--red-danger)' }}; font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                        Rp {{ number_format($financial['current']['net_income'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- Validation Card -->
                <div class="card-animated" style="
                    background: linear-gradient(135deg, var(--black-primary) 0%, var(--black-secondary) 100%);
                    border: 1px solid var(--black-border);
                    border-radius: 16px;
                    padding: 24px;
                    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(10px);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                    animation-delay: 0.4s;
                " 
                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='var(--black-border)';">
                    
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(168, 85, 247, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(168, 85, 247, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(168, 85, 247, 0.3);">
                                <x-filament::icon icon="heroicon-s-check-circle" style="width: 24px; height: 24px; color: var(--purple-accent); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" />
                            </div>
                            <div>
                                <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Validation</h3>
                                <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">Today</p>
                            </div>
                        </div>
                        @if($validation['total_pending'] > 0)
                            <span style="background: rgba(251, 191, 36, 0.15); color: var(--amber-warning); padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 600; backdrop-filter: blur(4px); border: 1px solid rgba(251, 191, 36, 0.3); animation: pulse-glow 2s infinite;">
                                {{ $validation['total_pending'] }} pending
                            </span>
                        @endif
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: var(--white-text); font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                        {{ $validation['total_approved'] }}
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div style="
                background: linear-gradient(135deg, var(--black-primary) 0%, var(--black-secondary) 100%);
                border: 1px solid var(--black-border);
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(10px);
                position: relative;
                overflow: hidden;
                margin-bottom: 24px;
            ">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(96, 165, 250, 0.03) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative; z-index: 2;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 600; color: var(--white-text); margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Financial Trends</h3>
                        <p style="font-size: 14px; color: var(--muted-text); margin: 4px 0 0 0; opacity: 0.8;">6-month overview</p>
                    </div>
                    <div style="display: flex; gap: 16px; font-size: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: var(--green-success); border-radius: 50%; box-shadow: 0 0 8px rgba(34, 214, 95, 0.3);"></div>
                            <span style="color: var(--muted-text); font-weight: 500;">Revenue</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: var(--red-danger); border-radius: 50%; box-shadow: 0 0 8px rgba(248, 113, 113, 0.3);"></div>
                            <span style="color: var(--muted-text); font-weight: 500;">Expenses</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: var(--blue-info); border-radius: 50%; box-shadow: 0 0 8px rgba(96, 165, 250, 0.3);"></div>
                            <span style="color: var(--muted-text); font-weight: 500;">Net Income</span>
                        </div>
                    </div>
                </div>
                <div id="financial-trends-chart" style="height: 320px; position: relative; z-index: 2;"></div>
            </div>
        </div>
        
        <!-- ApexJS Chart Implementation dengan Elegant Black Theme -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
        <script>
        // Chart initialization dalam single root element
        (() => {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    const chartContainer = document.querySelector('#financial-trends-chart');
                    if (!chartContainer) {
                        console.log('❌ Chart container not found');
                        return;
                    }
                    
                    console.log('🚀 Initializing Elegant Black ApexJS Chart...');
                    
                    const chartData = {
                        revenue: @json($trends['data']['revenue']),
                        expenses: @json($trends['data']['expenses']),
                        netIncome: @json($trends['data']['net_income']),
                        labels: @json($trends['labels'])
                    };
                    
                    const chartOptions = {
                        series: [{
                            name: 'Revenue',
                            data: chartData.revenue,
                            color: '#22d65f'
                        }, {
                            name: 'Expenses', 
                            data: chartData.expenses,
                            color: '#f87171'
                        }, {
                            name: 'Net Income',
                            data: chartData.netIncome,
                            color: '#60a5fa'
                        }],
                        chart: {
                            type: 'line',
                            height: 320,
                            background: 'transparent',
                            toolbar: { show: false },
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                            foreColor: '#a1a1aa',
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 1200,
                                animateGradually: {
                                    enabled: true,
                                    delay: 150
                                }
                            }
                        },
                        colors: ['#22d65f', '#f87171', '#60a5fa'],
                        stroke: {
                            curve: 'smooth',
                            width: [3, 3, 2.5],
                            lineCap: 'round'
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'dark',
                                type: 'vertical',
                                shadeIntensity: 0.3,
                                opacityFrom: 0.15,
                                opacityTo: 0,
                                stops: [0, 85, 100]
                            }
                        },
                        dataLabels: { enabled: false },
                        grid: {
                            show: true,
                            borderColor: '#333340',
                            strokeDashArray: 2,
                            position: 'back',
                            xaxis: { lines: { show: false } },
                            yaxis: { lines: { show: true } },
                            padding: { top: 0, right: 20, bottom: 0, left: 20 }
                        },
                        xaxis: {
                            categories: chartData.labels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            labels: {
                                style: { colors: '#a1a1aa', fontSize: '11px', fontWeight: '500' },
                                offsetY: 5
                            }
                        },
                        yaxis: {
                            show: true,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            labels: {
                                style: { colors: '#a1a1aa', fontSize: '11px', fontWeight: '500' },
                                formatter: function(value) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                                    return value.toLocaleString();
                                },
                                offsetX: -10
                            }
                        },
                        legend: { show: false },
                        tooltip: {
                            theme: 'dark',
                            style: { fontSize: '12px' },
                            custom: function({series, seriesIndex, dataPointIndex, w}) {
                                const category = w.globals.labels[dataPointIndex];
                                const value = series[seriesIndex][dataPointIndex];
                                const seriesName = w.globals.seriesNames[seriesIndex];
                                const color = w.globals.colors[seriesIndex];
                                
                                return `<div style="background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%); border: 1px solid #333340; border-radius: 8px; padding: 12px; backdrop-filter: blur(10px);">
                                    <div style="color: #fafafa; font-weight: 600; margin-bottom: 4px; font-size: 12px;">${category}</div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: ${color}; box-shadow: 0 0 4px ${color};"></div>
                                        <span style="color: #a1a1aa; font-size: 11px;">${seriesName}:</span>
                                        <span style="color: #fafafa; font-weight: 600; font-size: 12px;">Rp ${value.toLocaleString()}</span>
                                    </div>
                                </div>`;
                            }
                        },
                        markers: {
                            size: 0,
                            hover: { size: 6, sizeOffset: 2 }
                        }
                    };

                    const chart = new ApexCharts(chartContainer, chartOptions);
                    chart.render().then(() => {
                        console.log('✅ Elegant Black Chart rendered successfully');
                    }).catch((error) => {
                        console.error('❌ Chart render error:', error);
                    });

                }, 1500);
            });
        })();
        </script>
    </div>
</x-filament-panels::page>