<x-filament-panels::page>
    @php
        // Financial data untuk dashboard metrics
        $financial = $this->getFinancialSummary();
        $validation = $this->getValidationMetrics();
        $activities = $this->getRecentActivities();
        $trends = $this->getMonthlyTrends();
        
        // Calculate key metrics
        $totalPendapatan = $financial['current']['pendapatan'] ?? 0;
        $totalPengeluaran = $financial['current']['pengeluaran'] ?? 0;
        $netIncome = $totalPendapatan - $totalPengeluaran;
        $pendingValidation = $validation['total_pending'] ?? 0;
        $approvedValidation = $validation['total_approved'] ?? 0;
        $totalValidation = $pendingValidation + $approvedValidation;
        
        // Growth calculations
        $pendapatanGrowth = $financial['changes']['pendapatan'] ?? 0;
        $pengeluaranGrowth = $financial['changes']['pengeluaran'] ?? 0;
        
        // Current month data
        $currentMonth = date('F Y');
    @endphp

    <div class="space-y-6">
        <!-- World-Class SaaS Horizontal Stats Layout (Petugas Pattern) -->
        <div class="saas-stats-container">
            <div class="stats-horizontal-wrapper">
                <!-- Total Pendapatan -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-green-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Total Pendapatan</div>
                        <div class="stat-value">{{ number_format($totalPendapatan, 0, ',', '.') }}</div>
                        <div class="stat-desc">{{ $pendapatanGrowth >= 0 ? '+' : '' }}{{ number_format($pendapatanGrowth, 1) }}% bulan ini</div>
                    </div>
                </div>

                <!-- Total Pengeluaran -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-6 h-6 text-red-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Total Pengeluaran</div>
                        <div class="stat-value">{{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
                        <div class="stat-desc">{{ $pengeluaranGrowth >= 0 ? '+' : '' }}{{ number_format($pengeluaranGrowth, 1) }}% bulan ini</div>
                    </div>
                </div>

                <!-- Net Income -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 {{ $netIncome >= 0 ? 'text-green-400' : 'text-red-400' }}" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Net Income</div>
                        <div class="stat-value {{ $netIncome >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ number_format($netIncome, 0, ',', '.') }}
                        </div>
                        <div class="stat-desc">{{ $currentMonth }}</div>
                    </div>
                </div>

                <!-- Validasi Status -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6 text-purple-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Data Tervalidasi</div>
                        <div class="stat-value">{{ $approvedValidation }}</div>
                        <div class="stat-desc">{{ $pendingValidation }} pending review</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid (Following Petugas Pattern) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Validation Center -->
            <x-filament::section>
                <x-slot name="heading">
                    ✅ Pusat Validasi
                </x-slot>
                <x-slot name="description">
                    Status validasi transaksi dan data operasional
                </x-slot>
                <x-slot name="headerActions">
                    <x-filament::button
                        tag="a"
                        href="/bendahara/validation-center"
                        color="warning"
                        icon="heroicon-o-clipboard-document-check"
                        size="sm"
                    >
                        Lihat Semua
                    </x-filament::button>
                </x-slot>

                <div class="space-y-4">
                    <!-- Validation Stats Grid -->
                    <div class="validation-stats-grid">
                        <div class="validation-stat-item bg-yellow-500/10">
                            <div class="validation-stat-icon text-yellow-400">
                                <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5" />
                            </div>
                            <div class="validation-stat-content">
                                <div class="validation-stat-value">{{ $pendingValidation }}</div>
                                <div class="validation-stat-label">Pending</div>
                            </div>
                        </div>

                        <div class="validation-stat-item bg-green-500/10">
                            <div class="validation-stat-icon text-green-400">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5" />
                            </div>
                            <div class="validation-stat-content">
                                <div class="validation-stat-value">{{ $approvedValidation }}</div>
                                <div class="validation-stat-label">Approved</div>
                            </div>
                        </div>

                        <div class="validation-stat-item bg-blue-500/10">
                            <div class="validation-stat-icon text-blue-400">
                                <x-filament::icon icon="heroicon-o-calculator" class="w-5 h-5" />
                            </div>
                            <div class="validation-stat-content">
                                <div class="validation-stat-value">{{ $totalValidation }}</div>
                                <div class="validation-stat-label">Total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions-grid">
                        <a href="/bendahara/validasi-jumlah-pasiens" class="quick-action-item">
                            <x-filament::icon icon="heroicon-o-users" class="w-5 h-5 text-blue-400" />
                            <span>Validasi Pasien</span>
                        </a>
                        <a href="/bendahara/validation-center" class="quick-action-item">
                            <x-filament::icon icon="heroicon-o-currency-dollar" class="w-5 h-5 text-green-400" />
                            <span>Validasi Keuangan</span>
                        </a>
                    </div>
                </div>
            </x-filament::section>

            <!-- Recent Activities -->
            <x-filament::section>
                <x-slot name="heading">
                    🕒 Aktivitas Terbaru
                </x-slot>
                <x-slot name="description">
                    Transaksi dan validasi terbaru
                </x-slot>

                <div class="space-y-3">
                    @php
                        $recentActivities = collect($activities['recent_activities'] ?? [])
                            ->take(6);
                    @endphp

                    @forelse($recentActivities as $activity)
                        <div class="activity-item">
                            <div class="activity-icon-container">
                                <div class="activity-icon {{ $activity['type'] === 'income' ? 'bg-green-500/20' : 'bg-red-500/20' }}">
                                    <x-filament::icon 
                                        icon="{{ $activity['type'] === 'income' ? 'heroicon-o-plus-circle' : 'heroicon-o-minus-circle' }}" 
                                        class="w-4 h-4 {{ $activity['type'] === 'income' ? 'text-green-400' : 'text-red-400' }}" 
                                    />
                                </div>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $activity['description'] ?? 'Transaksi' }}</div>
                                <div class="activity-subtitle">{{ $activity['date'] ?? date('d/m/Y') }} • {{ $activity['user'] ?? 'System' }}</div>
                                <div class="activity-details">
                                    Rp {{ number_format($activity['amount'] ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="activity-meta">
                                <div class="activity-time">{{ $activity['time'] ?? 'Baru saja' }}</div>
                                <div class="activity-status status-{{ $activity['status'] ?? 'approved' }}">
                                    {{ match($activity['status'] ?? 'approved') {
                                        'pending' => '⏳ Pending',
                                        'approved' => '✅ Approved',
                                        'rejected' => '❌ Rejected',
                                        default => '✅ Approved'
                                    } }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-8 h-8 text-white/30 mx-auto mb-2" />
                            <p class="text-white/50 text-sm text-center">Belum ada aktivitas terbaru</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <!-- Financial Summary Section -->
        <x-filament::section>
            <x-slot name="heading">
                📊 Ringkasan Keuangan
            </x-slot>
            <x-slot name="description">
                Overview keuangan dan trend bulanan
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Revenue Summary -->
                <div class="financial-summary-card revenue">
                    <div class="financial-summary-header">
                        <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-6 h-6 text-green-400" />
                        <span class="financial-summary-title">Pendapatan</span>
                    </div>
                    <div class="financial-summary-content">
                        <div class="financial-summary-value">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
                        <div class="financial-summary-growth {{ $pendapatanGrowth >= 0 ? 'positive' : 'negative' }}">
                            {{ $pendapatanGrowth >= 0 ? '↗' : '↘' }} {{ abs($pendapatanGrowth) }}% dari bulan lalu
                        </div>
                    </div>
                </div>

                <!-- Expense Summary -->
                <div class="financial-summary-card expense">
                    <div class="financial-summary-header">
                        <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-6 h-6 text-red-400" />
                        <span class="financial-summary-title">Pengeluaran</span>
                    </div>
                    <div class="financial-summary-content">
                        <div class="financial-summary-value">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
                        <div class="financial-summary-growth {{ $pengeluaranGrowth <= 0 ? 'positive' : 'negative' }}">
                            {{ $pengeluaranGrowth >= 0 ? '↗' : '↘' }} {{ abs($pengeluaranGrowth) }}% dari bulan lalu
                        </div>
                    </div>
                </div>

                <!-- Net Income Summary -->
                <div class="financial-summary-card net-income">
                    <div class="financial-summary-header">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 {{ $netIncome >= 0 ? 'text-green-400' : 'text-red-400' }}" />
                        <span class="financial-summary-title">Net Income</span>
                    </div>
                    <div class="financial-summary-content">
                        <div class="financial-summary-value {{ $netIncome >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            Rp {{ number_format($netIncome, 0, ',', '.') }}
                        </div>
                        <div class="financial-summary-growth {{ $netIncome >= 0 ? 'positive' : 'negative' }}">
                            {{ $netIncome >= 0 ? 'Profit' : 'Loss' }} - {{ $currentMonth }}
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    <!-- CSS Styles (Following Petugas Patterns) -->
    <style>
        /* World-Class SaaS Horizontal Stats Layout (From Petugas) */
        .saas-stats-container {
            background: rgba(10, 10, 11, 0.6);
            backdrop-filter: blur(20px) saturate(140%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 8px 40px -12px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .saas-stats-container:hover {
            backdrop-filter: blur(24px) saturate(160%);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 12px 60px -16px rgba(0, 0, 0, 0.5), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
        }

        .stats-horizontal-wrapper {
            display: flex;
            gap: 0;
            overflow-x: auto;
            scroll-behavior: smooth;
        }

        /* Horizontal Stat Cards (Petugas Pattern) */
        .horizontal-stat {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 180px;
        }

        .horizontal-stat:last-child {
            border-right: none;
        }

        .horizontal-stat:hover {
            background: rgba(255, 255, 255, 0.03);
            transform: scale(1.01);
        }

        .stat-figure {
            flex-shrink: 0;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-content {
            flex-grow: 1;
            min-width: 0;
        }

        .stat-title {
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 0.125rem;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        }

        .stat-desc {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
        }

        /* Validation Stats Grid */
        .validation-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .validation-stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        .validation-stat-item:hover {
            transform: scale(1.02);
        }

        .validation-stat-icon {
            padding: 0.5rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
        }

        .validation-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
        }

        .validation-stat-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
        }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .quick-action-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            text-decoration: none;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .quick-action-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* Financial Summary Cards */
        .financial-summary-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(8px) saturate(110%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .financial-summary-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .financial-summary-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .financial-summary-title {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
        }

        .financial-summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            font-family: 'SF Mono', 'Monaco', monospace;
        }

        .financial-summary-growth {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .financial-summary-growth.positive {
            color: #4ade80;
        }

        .financial-summary-growth.negative {
            color: #f87171;
        }

        /* Activity Items (From Petugas Pattern) */
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .activity-icon-container {
            flex-shrink: 0;
        }

        .activity-icon {
            padding: 0.375rem;
            border-radius: 0.375rem;
            backdrop-filter: blur(8px);
        }

        .activity-content {
            flex-grow: 1;
            min-width: 0;
        }

        .activity-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.125rem;
        }

        .activity-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.25rem;
        }

        .activity-details {
            font-size: 0.6875rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .activity-meta {
            flex-shrink: 0;
            text-align: right;
        }

        .activity-time {
            font-size: 0.6875rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 0.25rem;
        }

        .activity-status {
            font-size: 0.6875rem;
            font-weight: 500;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
        }

        .status-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-approved { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .stats-horizontal-wrapper {
                flex-wrap: wrap;
                gap: 1px;
            }
            
            .horizontal-stat {
                flex: 1 1 calc(50% - 1px);
                min-width: 140px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .validation-stats-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }

        @media (max-width: 640px) {
            .stats-horizontal-wrapper {
                flex-direction: column;
            }
            
            .horizontal-stat {
                flex: 1 1 auto;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                min-width: auto;
            }
            
            .horizontal-stat:last-child {
                border-bottom: none;
            }
            
            .saas-stats-container {
                padding: 1rem;
                border-radius: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</x-filament-panels::page>