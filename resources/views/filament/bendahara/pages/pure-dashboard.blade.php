<x-filament-panels::page>
    <div id="bendahara-dashboard-container">
        @php
            $financial = $this->getFinancialSummary();
            $validation = $this->getValidationMetrics();
            $trends = $this->getMonthlyTrends();
            $activities = $this->getRecentActivities();
        @endphp

        <!-- Inline CSS for maximum isolation -->
        <style>
            @import url('/css/bendahara-pure.css');
            
            /* Additional inline styles for guaranteed loading */
            #bendahara-dashboard-container .bendahara-metric-card {
                background: white !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 12px !important;
                padding: 24px !important;
                margin-bottom: 16px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                transition: all 0.2s ease !important;
            }
            
            #bendahara-dashboard-container .bendahara-metric-card:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 10px 15px rgba(0,0,0,0.1) !important;
            }
            
            #bendahara-dashboard-container .bendahara-value {
                font-size: 24px !important;
                font-weight: bold !important;
                color: #111827 !important;
                margin: 8px 0 !important;
                font-variant-numeric: tabular-nums !important;
            }
            
            #bendahara-dashboard-container .bendahara-label {
                font-size: 12px !important;
                color: #6b7280 !important;
                text-transform: uppercase !important;
                font-weight: 500 !important;
                letter-spacing: 0.05em !important;
            }
            
            #bendahara-dashboard-container .bendahara-icon-wrapper {
                width: 40px !important;
                height: 40px !important;
                border-radius: 8px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            /* Color variants */
            #bendahara-dashboard-container .bendahara-icon-revenue {
                background-color: rgba(16, 185, 129, 0.1) !important;
                color: #059669 !important;
            }
            
            #bendahara-dashboard-container .bendahara-icon-expense {
                background-color: rgba(239, 68, 68, 0.1) !important;
                color: #dc2626 !important;
            }
            
            #bendahara-dashboard-container .bendahara-icon-income {
                background-color: rgba(59, 130, 246, 0.1) !important;
                color: #2563eb !important;
            }
            
            #bendahara-dashboard-container .bendahara-icon-validation {
                background-color: rgba(147, 51, 234, 0.1) !important;
                color: #7c3aed !important;
            }
            
            #bendahara-dashboard-container .bendahara-growth-positive {
                color: #059669 !important;
            }
            
            #bendahara-dashboard-container .bendahara-growth-negative {
                color: #dc2626 !important;
            }
            
            #bendahara-dashboard-container .bendahara-value-positive {
                color: #059669 !important;
            }
            
            #bendahara-dashboard-container .bendahara-value-negative {
                color: #dc2626 !important;
            }
        </style>

        <!-- Main Dashboard Content -->
        <div class="bendahara-metrics-grid">
            
            <!-- Revenue Card -->
            <div class="bendahara-metric-card">
                <div class="bendahara-card-header">
                    <div class="bendahara-card-info">
                        <div class="bendahara-icon-wrapper bendahara-icon-revenue">
                            <x-filament::icon
                                icon="heroicon-s-banknotes"
                                class="w-5 h-5"
                            />
                        </div>
                        <div>
                            <div class="bendahara-label">Revenue</div>
                        </div>
                    </div>
                    @if($financial['growth']['revenue'] != 0)
                        <div class="flex items-center gap-1 {{ $financial['growth']['revenue'] > 0 ? 'bendahara-growth-positive' : 'bendahara-growth-negative' }}">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-{{ $financial['growth']['revenue'] > 0 ? 'up' : 'down' }}"
                                class="w-3 h-3"
                            />
                            <span style="font-size: 12px; font-weight: 600;">
                                {{ $financial['growth']['revenue'] > 0 ? '+' : '' }}{{ $financial['growth']['revenue'] }}%
                            </span>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="bendahara-value">
                        Rp {{ number_format($financial['current']['revenue'], 0, ',', '.') }}
                    </div>
                    <div class="bendahara-label">This month</div>
                </div>
            </div>

            <!-- Expenses Card -->
            <div class="bendahara-metric-card">
                <div class="bendahara-card-header">
                    <div class="bendahara-card-info">
                        <div class="bendahara-icon-wrapper bendahara-icon-expense">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-down"
                                class="w-5 h-5"
                            />
                        </div>
                        <div>
                            <div class="bendahara-label">Expenses</div>
                        </div>
                    </div>
                    @if($financial['growth']['expenses'] != 0)
                        <div class="flex items-center gap-1 {{ $financial['growth']['expenses'] > 0 ? 'bendahara-growth-negative' : 'bendahara-growth-positive' }}">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-{{ $financial['growth']['expenses'] > 0 ? 'up' : 'down' }}"
                                class="w-3 h-3"
                            />
                            <span style="font-size: 12px; font-weight: 600;">
                                {{ $financial['growth']['expenses'] > 0 ? '+' : '' }}{{ $financial['growth']['expenses'] }}%
                            </span>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="bendahara-value">
                        Rp {{ number_format($financial['current']['expenses'], 0, ',', '.') }}
                    </div>
                    <div class="bendahara-label">This month</div>
                </div>
            </div>

            <!-- Net Income Card -->
            <div class="bendahara-metric-card">
                <div class="bendahara-card-header">
                    <div class="bendahara-card-info">
                        <div class="bendahara-icon-wrapper bendahara-icon-income">
                            <x-filament::icon
                                icon="heroicon-s-chart-bar"
                                class="w-5 h-5"
                            />
                        </div>
                        <div>
                            <div class="bendahara-label">Net Income</div>
                        </div>
                    </div>
                    @if($financial['growth']['net_income'] != 0)
                        <div class="flex items-center gap-1 {{ $financial['growth']['net_income'] > 0 ? 'bendahara-growth-positive' : 'bendahara-growth-negative' }}">
                            <x-filament::icon
                                icon="heroicon-s-arrow-trending-{{ $financial['growth']['net_income'] > 0 ? 'up' : 'down' }}"
                                class="w-3 h-3"
                            />
                            <span style="font-size: 12px; font-weight: 600;">
                                {{ $financial['growth']['net_income'] > 0 ? '+' : '' }}{{ $financial['growth']['net_income'] }}%
                            </span>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="bendahara-value {{ $financial['current']['net_income'] >= 0 ? 'bendahara-value-positive' : 'bendahara-value-negative' }}">
                        Rp {{ number_format($financial['current']['net_income'], 0, ',', '.') }}
                    </div>
                    <div class="bendahara-label">This month</div>
                </div>
            </div>

            <!-- Validation Card -->
            <div class="bendahara-metric-card">
                <div class="bendahara-card-header">
                    <div class="bendahara-card-info">
                        <div class="bendahara-icon-wrapper bendahara-icon-validation">
                            <x-filament::icon
                                icon="heroicon-s-check-circle"
                                class="w-5 h-5"
                            />
                        </div>
                        <div>
                            <div class="bendahara-label">Validation</div>
                        </div>
                    </div>
                    @if($validation['total_pending'] > 0)
                        <span style="
                            display: inline-flex;
                            align-items: center;
                            padding: 4px 8px;
                            font-size: 11px;
                            font-weight: 500;
                            background-color: rgba(245, 158, 11, 0.1);
                            color: #d97706;
                            border-radius: 9999px;
                        ">
                            {{ $validation['total_pending'] }} pending
                        </span>
                    @endif
                </div>
                <div>
                    <div class="bendahara-value">
                        {{ $validation['total_approved'] }}
                    </div>
                    <div class="bendahara-label">Validated today</div>
                </div>
            </div>
        </div>

        <!-- Activities Section -->
        <div class="bendahara-activity-grid">
            
            <!-- Chart Area -->
            <div class="bendahara-activity-card">
                <div class="bendahara-activity-header">
                    <div>
                        <h3 class="bendahara-activity-title">Financial Trends</h3>
                        <p class="bendahara-label" style="margin-top: 4px;">6-month overview</p>
                    </div>
                    <div style="display: flex; gap: 16px; font-size: 12px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></div>
                            <span style="color: #6b7280; font-weight: 500;">Revenue</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%;"></div>
                            <span style="color: #6b7280; font-weight: 500;">Expenses</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;"></div>
                            <span style="color: #6b7280; font-weight: 500;">Net</span>
                        </div>
                    </div>
                </div>
                <div class="bendahara-chart-placeholder">
                    <div>
                        <x-filament::icon
                            icon="heroicon-o-chart-bar"
                            class="w-8 h-8"
                            style="margin: 0 auto 8px; color: #9ca3af;"
                        />
                        <p>Chart akan ditambahkan setelah error CSS diperbaiki</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div>
                <!-- Revenue Activity -->
                <div class="bendahara-activity-card" style="margin-bottom: 24px;">
                    <div class="bendahara-activity-header">
                        <h4 class="bendahara-activity-title">Revenue Activity</h4>
                        <div class="bendahara-icon-wrapper bendahara-icon-revenue" style="width: 32px; height: 32px;">
                            <x-filament::icon
                                icon="heroicon-s-arrow-up"
                                class="w-4 h-4"
                            />
                        </div>
                    </div>
                    <div>
                        @php
                            $revenueActivities = array_filter($activities, fn($activity) => $activity['type'] === 'revenue');
                            $revenueActivities = array_slice($revenueActivities, 0, 3);
                        @endphp
                        @forelse($revenueActivities as $activity)
                            <div class="bendahara-activity-item">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 14px; font-weight: 500; color: #111827; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ Str::limit($activity['title'], 30) }}
                                    </p>
                                    <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0 0;">
                                        {{ $activity['date']->diffForHumans() }}
                                    </p>
                                </div>
                                <div style="text-align: right; margin-left: 12px; flex-shrink: 0;">
                                    <p style="font-size: 14px; font-weight: 600; color: #059669; margin: 0;">
                                        +{{ number_format($activity['amount']/1000, 0) }}K
                                    </p>
                                    <div style="font-size: 12px; margin-top: 2px;">
                                        @if($activity['status'] === 'disetujui')
                                            <span style="color: #059669;">✓ Approved</span>
                                        @else
                                            <span style="color: #d97706;">⏳ Pending</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 32px 0; color: #6b7280;">
                                <p style="font-size: 14px; margin: 0;">No recent revenue</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Expense Activity -->
                <div class="bendahara-activity-card">
                    <div class="bendahara-activity-header">
                        <h4 class="bendahara-activity-title">Expense Activity</h4>
                        <div class="bendahara-icon-wrapper bendahara-icon-expense" style="width: 32px; height: 32px;">
                            <x-filament::icon
                                icon="heroicon-s-arrow-down"
                                class="w-4 h-4"
                            />
                        </div>
                    </div>
                    <div>
                        @php
                            $expenseActivities = array_filter($activities, fn($activity) => $activity['type'] === 'expense');
                            $expenseActivities = array_slice($expenseActivities, 0, 3);
                        @endphp
                        @forelse($expenseActivities as $activity)
                            <div class="bendahara-activity-item">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 14px; font-weight: 500; color: #111827; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ Str::limit($activity['title'], 30) }}
                                    </p>
                                    <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0 0;">
                                        {{ $activity['date']->diffForHumans() }}
                                    </p>
                                </div>
                                <div style="text-align: right; margin-left: 12px; flex-shrink: 0;">
                                    <p style="font-size: 14px; font-weight: 600; color: #dc2626; margin: 0;">
                                        -{{ number_format($activity['amount']/1000, 0) }}K
                                    </p>
                                    <div style="font-size: 12px; margin-top: 2px;">
                                        @if($activity['status'] === 'disetujui' || $activity['status'] === 'approved')
                                            <span style="color: #059669;">✓ Approved</span>
                                        @else
                                            <span style="color: #d97706;">⏳ Pending</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 32px 0; color: #6b7280;">
                                <p style="font-size: 14px; margin: 0;">No recent expenses</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>