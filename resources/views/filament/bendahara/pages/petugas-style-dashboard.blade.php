<x-filament-panels::page>
    @php
        // APPLY SUCCESSFUL JASPEL DETAIL PATTERN - Direct service calls in template
        try {
            // DIRECT SERVICE CALLS (same pattern as jaspel detail page)
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Financial calculations - direct from models
            $currentPendapatan = \App\Models\Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $currentPengeluaran = \App\Models\Pengeluaran::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');
                
            $lastPendapatan = \App\Models\Pendapatan::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $lastPengeluaran = \App\Models\Pengeluaran::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');
            
            // Validation metrics - direct queries
            $pendingValidation = \App\Models\Pendapatan::where('status_validasi', 'pending')->count() +
                               \App\Models\Pengeluaran::where('status_validasi', 'pending')->count();
            $approvedValidation = \App\Models\Pendapatan::where('status_validasi', 'disetujui')->count() +
                                \App\Models\Pengeluaran::where('status_validasi', 'disetujui')->count();
            
            // Recent activities - direct queries
            $recentPendapatan = \App\Models\Pendapatan::with(['inputBy'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'income',
                        'description' => $item->nama_pendapatan,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi,
                        'date' => $item->updated_at->format('d/m/Y'),
                        'user' => $item->inputBy->name ?? 'System',
                        'time' => $item->updated_at->diffForHumans()
                    ];
                });
            
            $recentPengeluaran = \App\Models\Pengeluaran::with(['inputBy'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'expense',
                        'description' => $item->nama_pengeluaran,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi ?? 'approved',
                        'date' => $item->updated_at->format('d/m/Y'),
                        'user' => $item->inputBy->name ?? 'System',
                        'time' => $item->updated_at->diffForHumans()
                    ];
                });
            
            // Build final data structure
            $financial = [
                'current' => ['pendapatan' => $currentPendapatan, 'pengeluaran' => $currentPengeluaran],
                'changes' => [
                    'pendapatan' => $lastPendapatan > 0 ? round((($currentPendapatan - $lastPendapatan) / $lastPendapatan) * 100, 1) : 0,
                    'pengeluaran' => $lastPengeluaran > 0 ? round((($currentPengeluaran - $lastPengeluaran) / $lastPengeluaran) * 100, 1) : 0
                ]
            ];
            $validation = ['total_pending' => $pendingValidation, 'total_approved' => $approvedValidation];
            $activities = ['recent_activities' => $recentPendapatan->merge($recentPengeluaran)->sortByDesc('date')->take(6)->values()->toArray()];
            
            \Log::info('BendaharaDashboard: Direct service calls completed', [
                'pendapatan' => $currentPendapatan,
                'pengeluaran' => $currentPengeluaran,
                'pending' => $pendingValidation,
                'approved' => $approvedValidation
            ]);
            
        } catch (\Exception $e) {
            // Fallback data structure (same as jaspel detail pattern)
            $financial = ['current' => ['pendapatan' => 0, 'pengeluaran' => 0], 'changes' => ['pendapatan' => 0, 'pengeluaran' => 0]];
            $validation = ['total_pending' => 0, 'total_approved' => 0];
            $activities = ['recent_activities' => []];
            \Log::error('BendaharaDashboard: Direct queries failed - ' . $e->getMessage());
        }
        
        // Calculate key metrics (same as before)
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

    <!-- LIVEWIRE COMPLIANCE: Single root element wrapper -->
    <div>
        <div class="space-y-6">
        <!-- World-Class SaaS Horizontal Stats Layout (Petugas Pattern) -->
        <div class="saas-stats-container glass-fade-in">
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
            <x-filament::section class="glass-fade-in">
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
            <x-filament::section class="glass-fade-in">
                <x-slot name="heading">
                    🕒 Aktivitas Terbaru
                </x-slot>
                <x-slot name="description">
                    Transaksi dan validasi terbaru
                </x-slot>
                <x-slot name="headerActions">
                    <!-- Dual List Toggle Buttons -->
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button onclick="switchActivityLayout('single')" 
                                id="single-btn"
                                title="Single Column List"
                                style="padding: 0.5rem; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 0.5rem; color: #3b82f6; transition: all 0.2s ease; cursor: pointer;"
                                onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'"
                                onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'">
                            <!-- Single Column List Icon -->
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM3 16a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"/>
                            </svg>
                        </button>
                        <button onclick="switchActivityLayout('double')" 
                                id="double-btn"
                                title="Dual Column List"
                                style="padding: 0.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.5rem; color: #9ca3af; transition: all 0.2s ease; cursor: pointer;"
                                onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'"
                                onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'">
                            <!-- Dual Column List Icon -->
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 3h8v2H3V3zm10 0h8v2h-8V3zM3 7h8v2H3V7zm10 0h8v2h-8V7zM3 11h8v2H3v-2zm10 0h8v2h-8v-2zM3 15h8v2H3v-2zm10 0h8v2h-8v-2z"/>
                            </svg>
                        </button>
                    </div>
                </x-slot>

                <!-- LAYOUT 1: SINGLE COLUMN LIST (Default) -->
                <div id="activities-single-layout" class="space-y-3">
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

                <!-- LAYOUT 2: DUAL COLUMN LIST -->
                <div id="activities-double-layout" style="display: none; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    @foreach($recentActivities as $activity)
                        <div style="
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                            padding: 1rem;
                            background: rgba(255, 255, 255, 0.03);
                            border: 1px solid rgba(255, 255, 255, 0.05);
                            border-radius: 0.75rem;
                            transition: all 0.2s ease;
                            cursor: pointer;
                        "
                        class="activity-dual-item"
                        onmouseover="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.transform='translateY(-2px)';"
                        onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.transform='translateY(0)';">
                            
                            <!-- Compact Icon -->
                            <div style="
                                width: 1.75rem;
                                height: 1.75rem;
                                background: {{ $activity['type'] === 'income' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)' }};
                                border-radius: 0.5rem;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                flex-shrink: 0;
                            ">
                                <svg width="10" height="10" fill="{{ $activity['type'] === 'income' ? '#10b981' : '#ef4444' }}" viewBox="0 0 24 24">
                                    <path d="{{ $activity['type'] === 'income' ? 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12s-1.536-.219-2.121-.659c-1.172-.879-1.172-2.303 0-3.182C10.464 7.781 11.232 8 12 8s1.536.219 2.121.659' : 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H15m-6.75 0h5.25m-5.25 0c.621 0 1.125.504 1.125 1.125v.375M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5' }}"/>
                                </svg>
                            </div>
                            
                            <!-- Compact Content -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; color: #ffffff; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    {{ Str::limit($activity['description'] ?? 'Transaksi', 20) }}
                                </div>
                                <div style="font-size: 0.75rem; color: #9ca3af;">
                                    {{ $activity['date'] ?? date('d/m/Y') }}
                                </div>
                                <div style="font-weight: 700; color: {{ $activity['type'] === 'income' ? '#22d65f' : '#ef4444' }}; font-size: 0.875rem; margin-top: 0.25rem;">
                                    Rp {{ number_format($activity['amount'] ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <!-- Financial Summary Section -->
        <x-filament::section class="glass-fade-in">
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
        
        <!-- CSS Styles (Following Petugas Patterns) -->
        <style>
        /* GLASS EFFECTS VARIABLES */
        :root {
            --glass-frost-blur: 16px;
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-hover-border: rgba(255, 255, 255, 0.15);
        }
        
        /* HORIZONTAL STATS GLASS ENHANCEMENT */
        .horizontal-stat {
            background: linear-gradient(135deg, rgba(17, 17, 24, 0.8) 0%, rgba(26, 26, 32, 0.6) 100%) !important;
            backdrop-filter: blur(var(--glass-frost-blur)) saturate(150%) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 1.5rem !important;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.06) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
            overflow: hidden !important;
            cursor: pointer !important;
        }
        
        .horizontal-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, 
                var(--stat-gradient-start, #6b7280), 
                var(--stat-gradient-end, #9ca3af));
            opacity: 0.6;
            transition: all 0.3s ease;
        }
        
        .horizontal-stat:hover {
            transform: translateY(-8px) scale(1.02) !important;
            border-color: var(--glass-hover-border) !important;
            backdrop-filter: blur(24px) saturate(200%) !important;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.1) !important;
        }
        
        .horizontal-stat:hover::before {
            height: 4px;
            opacity: 1;
            box-shadow: 0 2px 8px var(--stat-glow, rgba(255, 255, 255, 0.2));
        }
        
        /* COLOR-CODED GLASS GRADIENTS */
        .horizontal-stat:nth-child(1) {
            --stat-gradient-start: #10b981;
            --stat-gradient-end: #059669;
            --stat-glow: rgba(16, 185, 129, 0.3);
        }
        
        .horizontal-stat:nth-child(2) {
            --stat-gradient-start: #ef4444;
            --stat-gradient-end: #dc2626;
            --stat-glow: rgba(239, 68, 68, 0.3);
        }
        
        .horizontal-stat:nth-child(3) {
            --stat-gradient-start: #3b82f6;
            --stat-gradient-end: #2563eb;
            --stat-glow: rgba(59, 130, 246, 0.3);
        }
        
        .horizontal-stat:nth-child(4) {
            --stat-gradient-start: #f59e0b;
            --stat-gradient-end: #d97706;
            --stat-glow: rgba(245, 158, 11, 0.3);
        }
        
        /* World-Class SaaS Horizontal Stats Layout (From Petugas) */
        .saas-stats-container {
            background: linear-gradient(135deg, rgba(10, 10, 11, 0.8) 0%, rgba(17, 17, 24, 0.6) 100%);
            backdrop-filter: blur(20px) saturate(140%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 8px 40px -12px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .saas-stats-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .saas-stats-container:hover {
            transform: translateY(-4px);
            backdrop-filter: blur(24px) saturate(160%);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 12px 60px -16px rgba(0, 0, 0, 0.5), inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
        }

        .saas-stats-container:hover::before {
            opacity: 1;
            height: 3px;
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
        
        /* WORLD-CLASS ANIMATIONS AND MICRO-INTERACTIONS */
        .glass-fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        
        .glass-fade-in.animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        .stat-figure {
            transition: all 0.3s ease !important;
        }
        
        .horizontal-stat:hover .stat-figure {
            transform: scale(1.1) rotate(5deg) !important;
        }
        
        .stat-value {
            transition: all 0.3s ease !important;
        }
        
        .horizontal-stat:hover .stat-value {
            transform: scale(1.05) !important;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.6) !important;
        }
        </style>
        
        <!-- WORLD-CLASS ANIMATION SYSTEM -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Progressive animation system
                const animateElements = () => {
                    const elements = document.querySelectorAll('.glass-fade-in');
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry, index) => {
                            if (entry.isIntersecting) {
                                setTimeout(() => {
                                    entry.target.classList.add('animate');
                                }, index * 150);
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    elements.forEach(el => observer.observe(el));
                };
                
                // Enhanced glass effect interactions
                const enhanceGlassEffects = () => {
                    const statCards = document.querySelectorAll('.horizontal-stat');
                    const sections = document.querySelectorAll('.fi-section');
                    
                    statCards.forEach((card, index) => {
                        card.style.animationDelay = `${index * 100}ms`;
                        
                        card.addEventListener('mouseenter', function() {
                            this.style.backdropFilter = 'blur(24px) saturate(200%)';
                        });
                        
                        card.addEventListener('mouseleave', function() {
                            this.style.backdropFilter = 'blur(16px) saturate(150%)';
                        });
                    });
                    
                    sections.forEach(section => {
                        section.addEventListener('mouseenter', function() {
                            this.style.backdropFilter = 'blur(20px) saturate(180%)';
                        });
                        
                        section.addEventListener('mouseleave', function() {
                            this.style.backdropFilter = 'blur(16px) saturate(150%)';
                        });
                    });
                };
                
                // Initialize enhancements
                setTimeout(() => {
                    animateElements();
                    enhanceGlassEffects();
                    
                    // Initialize dual layout system
                    const savedLayout = localStorage.getItem('bendahara_activities_layout') || 'single';
                    switchActivityLayout(savedLayout);
                }, 100);
            });
            
            // DUAL LIST LAYOUT SWITCHING SYSTEM
            function switchActivityLayout(layout) {
                const singleLayout = document.getElementById('activities-single-layout');
                const doubleLayout = document.getElementById('activities-double-layout');
                const singleBtn = document.getElementById('single-btn');
                const doubleBtn = document.getElementById('double-btn');
                
                if (!singleLayout || !doubleLayout || !singleBtn || !doubleBtn) {
                    console.warn('Activity layout elements not found');
                    return;
                }
                
                if (layout === 'single') {
                    singleLayout.style.display = 'block';
                    doubleLayout.style.display = 'none';
                    
                    // Update button states
                    singleBtn.style.background = 'rgba(59, 130, 246, 0.2)';
                    singleBtn.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                    singleBtn.style.color = '#3b82f6';
                    
                    doubleBtn.style.background = 'rgba(255, 255, 255, 0.05)';
                    doubleBtn.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    doubleBtn.style.color = '#9ca3af';
                    
                } else if (layout === 'double') {
                    singleLayout.style.display = 'none';
                    doubleLayout.style.display = 'grid';
                    
                    // Update button states
                    doubleBtn.style.background = 'rgba(59, 130, 246, 0.2)';
                    doubleBtn.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                    doubleBtn.style.color = '#3b82f6';
                    
                    singleBtn.style.background = 'rgba(255, 255, 255, 0.05)';
                    singleBtn.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    singleBtn.style.color = '#9ca3af';
                }
                
                // Store preference
                localStorage.setItem('bendahara_activities_layout', layout);
                console.log(`✅ Activity layout switched to: ${layout}`);
            }
        </script>
        </div>
    </div>
</x-filament-panels::page>