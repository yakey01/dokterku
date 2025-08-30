<x-filament-panels::page>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif; padding: 0; margin: 0;">
        @php
            $financial = $this->getFinancialSummary();
            $validationStats = $this->getValidationStats();
            $monthlyTrends = $this->getMonthlyTrends();
        @endphp
        
        <!-- Compact 4-Card Row Layout -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
            
            <!-- Revenue Card (Compact) -->
            <div class="compact-glass-card card-revenue" data-card="revenue">
                <div style="text-align: center;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(34, 214, 95, 0.2) 0%, rgba(34, 214, 95, 0.1) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(34, 214, 95, 0.3); margin: 0 auto 12px auto;">
                        <svg style="width: 18px; height: 18px; color: #22d65f;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2v20m5-10l-5-5-5 5"/>
                        </svg>
                    </div>
                    <p style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">REVENUE</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 8px 0;">This month</p>
                    <div style="font-size: 20px; font-weight: 700; color: #22d65f; margin: 4px 0; font-variant-numeric: tabular-nums; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                        Rp {{ number_format($financial['current']['pendapatan'], 0, ',', '.') }}
                    </div>
                    @if($financial['changes']['pendapatan'] != 0)
                        <div style="display: inline-flex; align-items: center; gap: 4px; color: {{ $financial['changes']['pendapatan'] > 0 ? '#22d65f' : '#f87171' }}; background: rgba(255,255,255,0.08); padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px); margin-top: 6px;">
                            <svg style="width: 10px; height: 10px;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $financial['changes']['pendapatan'] > 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/>
                            </svg>
                            <span style="font-size: 10px; font-weight: 600;">{{ $financial['changes']['pendapatan'] > 0 ? '+' : '' }}{{ $financial['changes']['pendapatan'] }}%</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Expenses Card (Compact) -->
            <div class="compact-glass-card card-expenses" data-card="expenses">
                <div style="text-align: center;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(248, 113, 113, 0.2) 0%, rgba(248, 113, 113, 0.1) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(248, 113, 113, 0.3); margin: 0 auto 12px auto;">
                        <svg style="width: 18px; height: 18px; color: #f87171;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 22v-20m-5 10l5 5 5-5"/>
                        </svg>
                    </div>
                    <p style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">EXPENSES</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 8px 0;">This month</p>
                    <div style="font-size: 20px; font-weight: 700; color: #f87171; margin: 4px 0; font-variant-numeric: tabular-nums; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                        Rp {{ number_format($financial['current']['pengeluaran'], 0, ',', '.') }}
                    </div>
                    @if($financial['changes']['pengeluaran'] != 0)
                        <div style="display: inline-flex; align-items: center; gap: 4px; color: {{ $financial['changes']['pengeluaran'] > 0 ? '#f87171' : '#22d65f' }}; background: rgba(255,255,255,0.08); padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px); margin-top: 6px;">
                            <svg style="width: 10px; height: 10px;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $financial['changes']['pengeluaran'] > 0 ? 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' : 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' }}"/>
                            </svg>
                            <span style="font-size: 10px; font-weight: 600;">{{ $financial['changes']['pengeluaran'] > 0 ? '+' : '' }}{{ $financial['changes']['pengeluaran'] }}%</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Net Profit Card (Compact) -->
            <div class="compact-glass-card card-net-profit" data-card="net-profit">
                <div style="text-align: center;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, {{ ($financial['current']['net_profit'] ?? 0) >= 0 ? 'rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1)' : 'rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1)' }} 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid {{ ($financial['current']['net_profit'] ?? 0) >= 0 ? 'rgba(59, 130, 246, 0.3)' : 'rgba(239, 68, 68, 0.3)' }}; margin: 0 auto 12px auto;">
                        <svg style="width: 18px; height: 18px; color: {{ ($financial['current']['net_profit'] ?? 0) >= 0 ? '#3b82f6' : '#ef4444' }};" fill="currentColor" viewBox="0 0 24 24">
                            <path d="{{ ($financial['current']['net_profit'] ?? 0) >= 0 ? 'M3 13h18M3 13l6-6m-6 6l6 6M21 13l-6-6m6 6l-6 6' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                        </svg>
                    </div>
                    <p style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">NET PROFIT</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 8px 0;">{{ ($financial['current']['net_profit'] ?? 0) >= 0 ? 'Positive' : 'Negative' }}</p>
                    <div style="font-size: 20px; font-weight: 700; color: {{ ($financial['current']['net_profit'] ?? 0) >= 0 ? '#3b82f6' : '#ef4444' }}; margin: 4px 0; font-variant-numeric: tabular-nums; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                        Rp {{ number_format($financial['current']['net_profit'] ?? 0, 0, ',', '.') }}
                    </div>
                    @if(($financial['changes']['net_profit'] ?? 0) != 0)
                        <div style="display: inline-flex; align-items: center; gap: 4px; color: {{ ($financial['changes']['net_profit'] ?? 0) > 0 ? '#3b82f6' : '#ef4444' }}; background: rgba(255,255,255,0.08); padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px); margin-top: 6px;">
                            <svg style="width: 10px; height: 10px;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ ($financial['changes']['net_profit'] ?? 0) > 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/>
                            </svg>
                            <span style="font-size: 10px; font-weight: 600;">{{ ($financial['changes']['net_profit'] ?? 0) > 0 ? '+' : '' }}{{ $financial['changes']['net_profit'] ?? 0 }}%</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Validation Card (Compact) -->
            <div class="compact-glass-card card-validation" data-card="validation">
                <div style="text-align: center;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(168, 85, 247, 0.1) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(168, 85, 247, 0.3); margin: 0 auto 12px auto;">
                        <svg style="width: 18px; height: 18px; color: #a855f7;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">VALIDATION</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 8px 0;">Today</p>
                    <div style="font-size: 20px; font-weight: 700; color: #a855f7; margin: 4px 0; font-variant-numeric: tabular-nums; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
                        {{ $validationStats['total_approved'] }}
                    </div>
                    @if($validationStats['total_pending'] > 0)
                        <div style="display: inline-flex; align-items: center; gap: 4px; color: #fbbf24; background: rgba(251, 191, 36, 0.1); padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px); border: 1px solid rgba(251, 191, 36, 0.2); margin-top: 6px;">
                            <svg style="width: 10px; height: 10px;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 6v6h.01M12 18h.01"/>
                            </svg>
                            <span style="font-size: 10px; font-weight: 600;">{{ $validationStats['total_pending'] }} pending</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Single Minimalist Chart Section -->
        <div class="compact-glass-card chart-container" style="min-height: 320px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding: 4px 0;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 600; color: rgba(255,255,255,0.9); margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Financial Overview</h3>
                    <p style="font-size: 13px; color: rgba(255,255,255,0.6); margin: 4px 0 0 0;">6-month trends</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; background: #22d65f; border-radius: 50%; box-shadow: 0 0 6px rgba(34, 214, 95, 0.4);"></div>
                        <span style="font-size: 11px; color: rgba(255,255,255,0.7); font-weight: 500;">Revenue</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; background: #f87171; border-radius: 50%; box-shadow: 0 0 6px rgba(248, 113, 113, 0.4);"></div>
                        <span style="font-size: 11px; color: rgba(255,255,255,0.7); font-weight: 500;">Expenses</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 10px; height: 10px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 6px rgba(59, 130, 246, 0.4);"></div>
                        <span style="font-size: 11px; color: rgba(255,255,255,0.7); font-weight: 500;">Net Profit</span>
                    </div>
                </div>
            </div>
            
            <!-- Chart Canvas -->
            <div style="position: relative; height: 240px; display: flex; align-items: center; justify-content: center;">
                <canvas id="compactFinancialChart" style="max-width: 100%; max-height: 100%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));"></canvas>
            </div>
        </div>
    </div>

    <!-- Compact Glassmorphic CSS Styles -->
    <style>
        /* ===== COMPACT GLASSMORPHIC CARD SYSTEM ===== */
        .compact-glass-card {
            /* Enhanced Glassmorphic Background */
            background: rgba(255, 255, 255, 0.06) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            
            /* Premium Border & Shadow */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 16px !important;
            box-shadow: 
                0 6px 20px rgba(0, 0, 0, 0.25),
                0 2px 6px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
            
            /* Compact Padding */
            padding: 20px 16px !important;
            position: relative !important;
            overflow: hidden !important;
            
            /* Smooth Transitions */
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1) !important;
            transform-style: preserve-3d !important;
            
            /* Subtle Glass Gradient */
            background-image: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.08) 0%, 
                rgba(255, 255, 255, 0.04) 50%, 
                rgba(0, 0, 0, 0.02) 100%) !important;
        }
        
        /* ===== MAGNETIC HOVER EFFECTS (Enhanced) ===== */
        .compact-glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.12) 0%, 
                rgba(255, 255, 255, 0.06) 50%, 
                rgba(255, 255, 255, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: inherit;
            pointer-events: none;
        }
        
        .compact-glass-card:hover::before {
            opacity: 1;
        }
        
        .compact-glass-card:hover {
            transform: translateY(-6px) scale(1.02) !important;
            box-shadow: 
                0 16px 32px rgba(0, 0, 0, 0.3),
                0 6px 12px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.12) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
        
        /* ===== SHIMMER BORDER ANIMATION ===== */
        .compact-glass-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(45deg, 
                transparent, 
                rgba(255, 255, 255, 0.08), 
                transparent, 
                rgba(255, 255, 255, 0.08), 
                transparent);
            background-size: 200% 200%;
            opacity: 0;
            animation: compactBorderShine 4s ease-in-out infinite;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: destination-out;
            pointer-events: none;
        }
        
        .compact-glass-card:hover::after {
            opacity: 1;
        }
        
        /* ===== CARD SPECIFIC ACCENTS (Compact) ===== */
        .card-revenue:hover {
            background-image: linear-gradient(135deg, 
                rgba(34, 214, 95, 0.06) 0%, 
                rgba(255, 255, 255, 0.04) 50%, 
                rgba(0, 0, 0, 0.02) 100%) !important;
        }
        
        .card-expenses:hover {
            background-image: linear-gradient(135deg, 
                rgba(248, 113, 113, 0.06) 0%, 
                rgba(255, 255, 255, 0.04) 50%, 
                rgba(0, 0, 0, 0.02) 100%) !important;
        }
        
        .card-net-profit:hover {
            background-image: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.06) 0%, 
                rgba(255, 255, 255, 0.04) 50%, 
                rgba(0, 0, 0, 0.02) 100%) !important;
        }
        
        .card-validation:hover {
            background-image: linear-gradient(135deg, 
                rgba(168, 85, 247, 0.06) 0%, 
                rgba(255, 255, 255, 0.04) 50%, 
                rgba(0, 0, 0, 0.02) 100%) !important;
        }
        
        /* ===== CHART CONTAINER SPECIAL STYLING ===== */
        .chart-container {
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        
        .chart-container:hover {
            background: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        /* ===== RESPONSIVE DESIGN FOR COMPACT LAYOUT ===== */
        @media (max-width: 1200px) {
            /* 4 cards become 2x2 on tablets */
            [data-filament-panel-id="bendahara"] div[style*="grid-template-columns: repeat(4, 1fr)"] {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }
        }
        
        @media (max-width: 768px) {
            /* Stack vertically on mobile */
            [data-filament-panel-id="bendahara"] div[style*="grid-template-columns: repeat(4, 1fr)"] {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
            
            .compact-glass-card {
                padding: 16px 12px !important;
                border-radius: 12px !important;
            }
            
            .chart-container {
                min-height: 280px !important;
            }
        }
        
        @media (max-width: 480px) {
            /* Extra compact for small phones */
            .compact-glass-card {
                padding: 14px 10px !important;
            }
            
            /* Smaller text on very small screens */
            .compact-glass-card div[style*="font-size: 20px"] {
                font-size: 18px !important;
            }
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes compactBorderShine {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes compactPulse {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1); 
            }
            50% { 
                opacity: 0.8; 
                transform: scale(1.05); 
            }
        }
        
        /* ===== STAGGERED CARD ANIMATIONS ===== */
        .compact-glass-card:nth-child(1) { animation-delay: 0ms; }
        .compact-glass-card:nth-child(2) { animation-delay: 100ms; }
        .compact-glass-card:nth-child(3) { animation-delay: 200ms; }
        .compact-glass-card:nth-child(4) { animation-delay: 300ms; }
        
        /* ===== ACCESSIBILITY ===== */
        .compact-glass-card:focus-visible {
            outline: 2px solid rgba(59, 130, 246, 0.8);
            outline-offset: 3px;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .compact-glass-card,
            .compact-glass-card::before,
            .compact-glass-card::after {
                animation: none !important;
                transition: none !important;
            }
        }
        
        /* ===== ENHANCED VISUAL DEPTH ===== */
        .compact-glass-card {
            /* Multiple shadow layers for depth */
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.12),
                0 1px 2px rgba(0, 0, 0, 0.08),
                0 4px 16px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.08),
                inset 0 -1px 0 rgba(0, 0, 0, 0.05) !important;
        }
        
        .compact-glass-card:hover {
            /* Enhanced hover shadows */
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.2),
                0 4px 8px rgba(0, 0, 0, 0.12),
                0 12px 32px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.12),
                inset 0 -1px 0 rgba(0, 0, 0, 0.08) !important;
        }
    </style>

    <!-- Chart.js Implementation (Compact) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📊 Compact Chart: Initializing...');
            
            setTimeout(initCompactChart, 500);
        });
        
        function initCompactChart() {
            const canvas = document.getElementById('compactFinancialChart');
            if (!canvas) {
                console.log('❌ Compact chart canvas not found');
                return;
            }
            
            // Destroy existing chart if exists
            if (window.compactChart) {
                window.compactChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            // Data from Laravel backend with error handling
            let monthlyData;
            try {
                monthlyData = {!! json_encode($monthlyTrends ?? [
                    'months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'pendapatan' => [800000, 950000, 1200000, 1100000, 1300000, 1000000],
                    'pengeluaran' => [300000, 350000, 400000, 380000, 450000, 300000],
                    'jaspel' => [0, 0, 0, 0, 0, 0]
                ]) !!};
            } catch (e) {
                console.error('📊 Error parsing data:', e);
                monthlyData = {
                    months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    pendapatan: [800000, 950000, 1200000, 1100000, 1300000, 1000000],
                    pengeluaran: [300000, 350000, 400000, 380000, 450000, 300000],
                    jaspel: [0, 0, 0, 0, 0, 0]
                };
            }
            
            // Calculate net profit for each month
            const netProfit = monthlyData.pendapatan.map((rev, index) => 
                rev - (monthlyData.pengeluaran[index] || 0) - (monthlyData.jaspel[index] || 0)
            );
            
            window.compactChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthlyData.months,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: monthlyData.pendapatan,
                            borderColor: '#22d65f',
                            backgroundColor: 'rgba(34, 214, 95, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#22d65f',
                            pointBorderColor: 'rgba(255, 255, 255, 0.8)',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#22d65f',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        },
                        {
                            label: 'Expenses',
                            data: monthlyData.pengeluaran,
                            borderColor: '#f87171',
                            backgroundColor: 'rgba(248, 113, 113, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#f87171',
                            pointBorderColor: 'rgba(255, 255, 255, 0.8)',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#f87171',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        },
                        {
                            label: 'Net Profit',
                            data: netProfit,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.08)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: 'rgba(255, 255, 255, 0.9)',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#3b82f6',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.15)',
                            borderWidth: 1,
                            cornerRadius: 10,
                            padding: 10,
                            displayColors: true,
                            boxShadow: '0 6px 16px rgba(0, 0, 0, 0.3)',
                            callbacks: {
                                title: function(context) {
                                    return monthlyData.months[context[0].dataIndex];
                                },
                                label: function(context) {
                                    const value = context.parsed.y;
                                    const formattedValue = new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(value);
                                    return context.dataset.label + ': ' + formattedValue;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: true,
                                color: 'rgba(255, 255, 255, 0.06)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.06)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1800,
                        easing: 'easeInOutCubic'
                    }
                }
            });
            
            console.log('✅ Compact chart initialized successfully');
        }
    </script>
</x-filament-panels::page>