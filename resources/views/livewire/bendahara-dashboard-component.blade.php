<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 0; margin: 0; position: relative;">
    
    <!-- Inline CSS untuk glassmorphic effects -->
    <style>
        .bendahara-root {
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

    <!-- Main Dashboard Content -->
    <div class="bendahara-root" style="padding: 0; margin: 0;">
        
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
                
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(34, 214, 95, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(34, 214, 95, 0.2) 0%, rgba(34, 214, 95, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(34, 214, 95, 0.3);">
                            <svg style="width: 24px; height: 24px; color: var(--green-success); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2v20m5-5l-5 5-5-5m10-10h8m-8 0h-8m8 0V2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Revenue</h3>
                            <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">This month</p>
                        </div>
                    </div>
                    @if($financial['growth']['revenue'] != 0)
                        <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['revenue'] > 0 ? 'var(--green-success)' : 'var(--red-danger)' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
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
                            <svg style="width: 24px; height: 24px; color: var(--red-danger); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 13l-7 7-7-7m14-8l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Expenses</h3>
                            <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">This month</p>
                        </div>
                    </div>
                    @if($financial['growth']['expenses'] != 0)
                        <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['expenses'] > 0 ? 'var(--red-danger)' : 'var(--green-success)' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
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
                            <svg style="width: 24px; height: 24px; color: var(--blue-info); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 13h2l3-9 4 9h2l3-9"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: var(--muted-text); margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Net Income</h3>
                            <p style="font-size: 11px; color: var(--subtle-text); margin: 4px 0 0 0; opacity: 0.8;">This month</p>
                        </div>
                    </div>
                    @if($financial['growth']['net_income'] != 0)
                        <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['net_income'] > 0 ? 'var(--green-success)' : 'var(--red-danger)' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
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
                            <svg style="width: 24px; height: 24px; color: var(--purple-accent); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
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
    
    <!-- ApexJS Implementation dengan proper Livewire structure -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const chartContainer = document.querySelector('#financial-trends-chart');
            if (!chartContainer) return;
            
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
                    foreColor: '#a1a1aa'
                },
                colors: ['#22d65f', '#f87171', '#60a5fa'],
                stroke: { curve: 'smooth', width: [3, 3, 2.5], lineCap: 'round' },
                fill: {
                    type: 'gradient',
                    gradient: { shade: 'dark', type: 'vertical', opacityFrom: 0.15, opacityTo: 0 }
                },
                dataLabels: { enabled: false },
                grid: { show: true, borderColor: '#333340', strokeDashArray: 2 },
                xaxis: {
                    categories: chartData.labels,
                    axisBorder: { show: false },
                    labels: { style: { colors: '#a1a1aa', fontSize: '11px' } }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#a1a1aa', fontSize: '11px' },
                        formatter: function(value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value.toLocaleString();
                        }
                    }
                },
                legend: { show: false },
                markers: { size: 0, hover: { size: 6 } }
            };

            const chart = new ApexCharts(chartContainer, chartOptions);
            chart.render();
        }, 1000);
    });
    </script>
</div>