<x-filament-panels::page>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 0; margin: 0;">
        
        <!-- CSS Animations untuk glassmorphic effects -->
        <style>
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            
            @keyframes shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
            
            .glassmorphic-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
                transform: translateX(-100%);
                transition: transform 0.6s ease;
                pointer-events: none;
            }
            
            .glassmorphic-card:hover::before {
                animation: shimmer 1.5s ease-in-out;
            }
        </style>
        
        @php
            $financial = $this->getFinancialSummary();
            $validation = $this->getValidationMetrics();
            $trends = $this->getMonthlyTrends();
            $activities = $this->getRecentActivities();
        @endphp

        <!-- Metrics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 32px;">
            
            <!-- Revenue Card - Elegant Black Glassmorphic -->
            <div style="
                background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                border: 1px solid #333340;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            " 
            onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='#333340';">
                
                <!-- Glassmorphic overlay effect -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(16, 185, 129, 0.3);">
                            <x-filament::icon
                                icon="heroicon-s-banknotes"
                                style="width: 24px; height: 24px; color: #22d65f; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"
                            />
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: #a1a1aa; margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                Revenue
                            </h3>
                            <p style="font-size: 11px; color: #71717a; margin: 4px 0 0 0; opacity: 0.8;">
                                This month
                            </p>
                        </div>
                    </div>
                    @if($financial['growth']['revenue'] != 0)
                        <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['revenue'] > 0 ? '#22d65f' : '#f87171' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-{{ $financial['growth']['revenue'] > 0 ? 'up' : 'down' }}"
                                style="width: 14px; height: 14px;"
                            />
                            <span style="font-size: 11px; font-weight: 600; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">
                                {{ $financial['growth']['revenue'] > 0 ? '+' : '' }}{{ $financial['growth']['revenue'] }}%
                            </span>
                        </div>
                    @endif
                </div>
                <div style="font-size: 28px; font-weight: 700; color: #fafafa; font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                    Rp {{ number_format($financial['current']['revenue'], 0, ',', '.') }}
                </div>
            </div>

            <!-- Expenses Card - Elegant Black Glassmorphic -->
            <div style="
                background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                border: 1px solid #333340;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            " 
            onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='#333340';">
                
                <!-- Glassmorphic overlay effect -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-down"
                                style="width: 24px; height: 24px; color: #f87171; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"
                            />
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: #a1a1aa; margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                Expenses
                            </h3>
                            <p style="font-size: 11px; color: #71717a; margin: 4px 0 0 0; opacity: 0.8;">
                                This month
                            </p>
                        </div>
                    </div>
                    @if($financial['growth']['expenses'] != 0)
                        <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['expenses'] > 0 ? '#f87171' : '#22d65f' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-{{ $financial['growth']['expenses'] > 0 ? 'up' : 'down' }}"
                                style="width: 14px; height: 14px;"
                            />
                            <span style="font-size: 11px; font-weight: 600; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">
                                {{ $financial['growth']['expenses'] > 0 ? '+' : '' }}{{ $financial['growth']['expenses'] }}%
                            </span>
                        </div>
                    @endif
                </div>
                <div style="font-size: 28px; font-weight: 700; color: #fafafa; font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                    Rp {{ number_format($financial['current']['expenses'], 0, ',', '.') }}
                </div>
            </div>

            <!-- Net Income Card - Elegant Black Glassmorphic -->
            <div style="
                background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                border: 1px solid #333340;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            " 
            onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='#333340';">
                
                <!-- Glassmorphic overlay effect -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(59, 130, 246, 0.3);">
                            <x-filament::icon
                                icon="heroicon-s-chart-bar"
                                style="width: 24px; height: 24px; color: #60a5fa; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"
                            />
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: #a1a1aa; margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                Net Income
                            </h3>
                            <p style="font-size: 11px; color: #71717a; margin: 4px 0 0 0; opacity: 0.8;">
                                This month
                            </p>
                        </div>
                    </div>
                    @if($financial['growth']['net_income'] != 0)
                        <div style="display: flex; align-items: center; gap: 4px; color: {{ $financial['growth']['net_income'] > 0 ? '#22d65f' : '#f87171' }}; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px);">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-{{ $financial['growth']['net_income'] > 0 ? 'up' : 'down' }}"
                                style="width: 14px; height: 14px;"
                            />
                            <span style="font-size: 11px; font-weight: 600; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">
                                {{ $financial['growth']['net_income'] > 0 ? '+' : '' }}{{ $financial['growth']['net_income'] }}%
                            </span>
                        </div>
                    @endif
                </div>
                <div style="font-size: 28px; font-weight: 700; color: {{ $financial['current']['net_income'] >= 0 ? '#22d65f' : '#f87171' }}; font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                    Rp {{ number_format($financial['current']['net_income'], 0, ',', '.') }}
                </div>
            </div>

            <!-- Validation Card - Elegant Black Glassmorphic -->
            <div style="
                background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                border: 1px solid #333340;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            " 
            onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.12)'; this.style.borderColor='#404050';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'; this.style.borderColor='#333340';">
                
                <!-- Glassmorphic overlay effect -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(147, 51, 234, 0.05) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(147, 51, 234, 0.2) 0%, rgba(147, 51, 234, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1px solid rgba(147, 51, 234, 0.3);">
                            <x-filament::icon
                                icon="heroicon-s-check-circle"
                                style="width: 24px; height: 24px; color: #a855f7; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"
                            />
                        </div>
                        <div>
                            <h3 style="font-size: 12px; font-weight: 600; color: #a1a1aa; margin: 0; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                Validation
                            </h3>
                            <p style="font-size: 11px; color: #71717a; margin: 4px 0 0 0; opacity: 0.8;">
                                Today
                            </p>
                        </div>
                    </div>
                    @if($validation['total_pending'] > 0)
                        <span style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 600; backdrop-filter: blur(4px); border: 1px solid rgba(245, 158, 11, 0.3); animation: pulse 2s infinite;">
                            {{ $validation['total_pending'] }} pending
                        </span>
                    @endif
                </div>
                <div style="font-size: 28px; font-weight: 700; color: #fafafa; font-variant-numeric: tabular-nums; margin: 8px 0; text-shadow: 0 1px 3px rgba(0,0,0,0.5); position: relative; z-index: 2;">
                    {{ $validation['total_approved'] }}
                </div>
            </div>
        </div>

        <!-- Activities Section -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
            
            <!-- Chart Area - Elegant Black Glassmorphic -->
            <div style="
                background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                border: 1px solid #333340;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(10px);
                position: relative;
                overflow: hidden;
            ">
                <!-- Glassmorphic overlay -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative; z-index: 2;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 600; color: #fafafa; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Financial Trends</h3>
                        <p style="font-size: 14px; color: #a1a1aa; margin: 4px 0 0 0; opacity: 0.8;">6-month overview</p>
                    </div>
                    <div style="display: flex; gap: 16px; font-size: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: #22d65f; border-radius: 50%; box-shadow: 0 0 8px rgba(34, 214, 95, 0.3);"></div>
                            <span style="color: #a1a1aa; font-weight: 500;">Revenue</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: #f87171; border-radius: 50%; box-shadow: 0 0 8px rgba(248, 113, 113, 0.3);"></div>
                            <span style="color: #a1a1aa; font-weight: 500;">Expenses</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: #60a5fa; border-radius: 50%; box-shadow: 0 0 8px rgba(96, 165, 250, 0.3);"></div>
                            <span style="color: #a1a1aa; font-weight: 500;">Net Income</span>
                        </div>
                    </div>
                </div>
                <div id="financial-trends-chart" style="height: 300px; background: linear-gradient(135deg, #1a1a20 0%, #2a2a32 100%); border: 1px solid #404050; border-radius: 12px; backdrop-filter: blur(4px); position: relative; z-index: 2;"></div>
            </div>

            <!-- Recent Activities -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                
                <!-- Revenue Activities - Elegant Black -->
                <div style="
                    background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                    border: 1px solid #333340;
                    border-radius: 16px;
                    padding: 20px;
                    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(10px);
                    position: relative;
                    overflow: hidden;
                ">
                    <!-- Glassmorphic overlay -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #fafafa; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Revenue Activity</h4>
                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); border: 1px solid rgba(16, 185, 129, 0.3);">
                            <x-filament::icon
                                icon="heroicon-s-arrow-up"
                                style="width: 20px; height: 20px; color: #22d65f; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"
                            />
                        </div>
                    </div>
                    <div>
                        @php
                            $revenueActivities = array_filter($activities, fn($activity) => $activity['type'] === 'revenue');
                            $revenueActivities = array_slice($revenueActivities, 0, 3);
                        @endphp
                        @forelse($revenueActivities as $activity)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); position: relative; z-index: 2;">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 14px; font-weight: 500; color: #fafafa; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                        {{ Str::limit($activity['title'], 25) }}
                                    </p>
                                    <p style="font-size: 12px; color: #a1a1aa; margin: 2px 0 0 0; opacity: 0.8;">
                                        {{ $activity['date']->diffForHumans() }}
                                    </p>
                                </div>
                                <div style="text-align: right; margin-left: 12px;">
                                    <p style="font-size: 14px; font-weight: 600; color: #22d65f; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                        +{{ number_format($activity['amount']/1000, 0) }}K
                                    </p>
                                    <p style="font-size: 11px; margin: 2px 0 0 0; color: {{ $activity['status'] === 'disetujui' ? '#22d65f' : '#fbbf24' }};">
                                        {{ $activity['status'] === 'disetujui' ? '✓ Approved' : '⏳ Pending' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 24px 0; color: #a1a1aa; position: relative; z-index: 2;">
                                <p style="font-size: 14px; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">No recent revenue</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Expense Activities - Elegant Black -->
                <div style="
                    background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                    border: 1px solid #333340;
                    border-radius: 16px;
                    padding: 20px;
                    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(10px);
                    position: relative;
                    overflow: hidden;
                ">
                    <!-- Glassmorphic overlay -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(239, 68, 68, 0.03) 0%, transparent 100%); pointer-events: none; border-radius: 16px;"></div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; position: relative; z-index: 2;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #fafafa; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Expense Activity</h4>
                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <x-filament::icon
                                icon="heroicon-s-arrow-down"
                                style="width: 20px; height: 20px; color: #f87171; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"
                            />
                        </div>
                    </div>
                    <div>
                        @php
                            $expenseActivities = array_filter($activities, fn($activity) => $activity['type'] === 'expense');
                            $expenseActivities = array_slice($expenseActivities, 0, 3);
                        @endphp
                        @forelse($expenseActivities as $activity)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); position: relative; z-index: 2;">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 14px; font-weight: 500; color: #fafafa; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                        {{ Str::limit($activity['title'], 25) }}
                                    </p>
                                    <p style="font-size: 12px; color: #a1a1aa; margin: 2px 0 0 0; opacity: 0.8;">
                                        {{ $activity['date']->diffForHumans() }}
                                    </p>
                                </div>
                                <div style="text-align: right; margin-left: 12px;">
                                    <p style="font-size: 14px; font-weight: 600; color: #f87171; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                                        -{{ number_format($activity['amount']/1000, 0) }}K
                                    </p>
                                    <p style="font-size: 11px; margin: 2px 0 0 0; color: {{ ($activity['status'] === 'disetujui' || $activity['status'] === 'approved') ? '#22d65f' : '#fbbf24' }};">
                                        {{ ($activity['status'] === 'disetujui' || $activity['status'] === 'approved') ? '✓ Approved' : '⏳ Pending' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 24px 0; color: #a1a1aa; position: relative; z-index: 2;">
                                <p style="font-size: 14px; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">No recent expenses</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ApexJS Chart Implementation - Inline untuk avoid conflicts -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Wait untuk DOM fully loaded
            setTimeout(function() {
                const chartContainer = document.querySelector('#financial-trends-chart');
                if (!chartContainer) {
                    console.log('Chart container not found');
                    return;
                }
                
                console.log('🚀 Initializing ApexJS Chart...');
                
                // Chart data from PHP
                const chartData = {
                    revenue: @json($trends['data']['revenue']),
                    expenses: @json($trends['data']['expenses']),
                    netIncome: @json($trends['data']['net_income']),
                    labels: @json($trends['labels'])
                };
                
                // ApexCharts options untuk elegant black theme
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
                        height: 300,
                        background: 'transparent',
                        toolbar: { show: false },
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                        foreColor: '#a1a1aa',
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    colors: ['#22d65f', '#f87171', '#60a5fa'],
                    stroke: {
                        curve: 'smooth',
                        width: [3, 3, 2],
                        lineCap: 'round'
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'dark',
                            type: 'vertical',
                            shadeIntensity: 0.2,
                            opacityFrom: 0.1,
                            opacityTo: 0,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: { 
                        enabled: false 
                    },
                    grid: {
                        show: true,
                        borderColor: '#333340',
                        strokeDashArray: 3,
                        position: 'back',
                        xaxis: { lines: { show: false } },
                        yaxis: { lines: { show: true } },
                        padding: {
                            top: 0,
                            right: 20,
                            bottom: 0,
                            left: 20
                        }
                    },
                    xaxis: {
                        categories: chartData.labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: {
                                colors: '#a1a1aa',
                                fontSize: '12px',
                                fontWeight: '500'
                            },
                            offsetY: 5
                        }
                    },
                    yaxis: {
                        show: true,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: {
                                colors: '#a1a1aa',
                                fontSize: '12px',
                                fontWeight: '500'
                            },
                            formatter: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K';
                                }
                                return value.toLocaleString();
                            },
                            offsetX: -10
                        }
                    },
                    legend: { 
                        show: false 
                    },
                    tooltip: {
                        theme: 'dark',
                        style: {
                            fontSize: '12px',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
                        },
                        custom: function({series, seriesIndex, dataPointIndex, w}) {
                            const category = w.globals.labels[dataPointIndex];
                            const value = series[seriesIndex][dataPointIndex];
                            const seriesName = w.globals.seriesNames[seriesIndex];
                            const color = w.globals.colors[seriesIndex];
                            
                            return `
                                <div style="
                                    background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%);
                                    border: 1px solid #333340;
                                    border-radius: 8px;
                                    padding: 12px;
                                    backdrop-filter: blur(10px);
                                    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
                                ">
                                    <div style="color: #fafafa; font-weight: 600; margin-bottom: 4px; font-size: 12px;">${category}</div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: ${color};"></div>
                                        <span style="color: #a1a1aa; font-size: 11px;">${seriesName}:</span>
                                        <span style="color: #fafafa; font-weight: 600; font-size: 12px;">Rp ${value.toLocaleString()}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    markers: {
                        size: 0,
                        hover: { 
                            size: 6,
                            sizeOffset: 2
                        }
                    }
                };

                // Render chart
                const chart = new ApexCharts(chartContainer, chartOptions);
                chart.render().then(() => {
                    console.log('✅ ApexJS Chart rendered successfully');
                }).catch((error) => {
                    console.error('❌ Chart render error:', error);
                });

            }, 1000); // Delay untuk ensure DOM ready
        });
        </script>
    </div>
</x-filament-panels::page>