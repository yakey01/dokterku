<x-filament-panels::page>
    @php
        // Data untuk dashboard metrics
        $totalPasienHariIni = \App\Models\JumlahPasienHarian::whereDate('tanggal', today())->sum('jumlah_pasien_umum');
        $totalPasienBpjsHariIni = \App\Models\JumlahPasienHarian::whereDate('tanggal', today())->sum('jumlah_pasien_bpjs');
        $totalRecordsHariIni = \App\Models\JumlahPasienHarian::whereDate('tanggal', today())->count();
        $totalSemuaPasienHariIni = $totalPasienHariIni + $totalPasienBpjsHariIni;
        
        $totalBulanIni = \App\Models\JumlahPasienHarian::whereMonth('tanggal', now()->month)->sum('jumlah_pasien_umum');
        $totalBpjsBulanIni = \App\Models\JumlahPasienHarian::whereMonth('tanggal', now()->month)->sum('jumlah_pasien_bpjs');
        
        $pendingValidation = \App\Models\JumlahPasienHarian::where('status_validasi', 'pending')->count();
        $approvedToday = \App\Models\JumlahPasienHarian::where('status_validasi', 'approved')->whereDate('tanggal', today())->count();
        
        $dataSaya = \App\Models\JumlahPasienHarian::where('input_by', auth()->id())->count();
        $dataSayaBulanIni = \App\Models\JumlahPasienHarian::where('input_by', auth()->id())->whereMonth('tanggal', now()->month)->count();
    @endphp

    <div class="space-y-6">
        <!-- World-Class SaaS Horizontal Stats Layout -->
        <div class="saas-stats-container">
            <div class="stats-horizontal-wrapper">

                <!-- Pasien Hari Ini -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-blue-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Pasien Hari Ini</div>
                        <div class="stat-value">{{ $totalSemuaPasienHariIni }}</div>
                        <div class="stat-desc">{{ $totalRecordsHariIni }} data entry</div>
                    </div>
                </div>

                <!-- Pending Validasi -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-clock" class="w-6 h-6 text-yellow-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Menunggu Validasi</div>
                        <div class="stat-value">{{ $pendingValidation }}</div>
                        <div class="stat-desc">Butuh persetujuan</div>
                    </div>
                </div>

                <!-- Total Bulan Ini -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6 text-green-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Total Bulan Ini</div>
                        <div class="stat-value">{{ $totalBulanIni + $totalBpjsBulanIni }}</div>
                        <div class="stat-desc">{{ date('F Y') }}</div>
                    </div>
                </div>

                <!-- Kontribusi Saya -->
                <div class="horizontal-stat">
                    <div class="stat-figure">
                        <x-filament::icon icon="heroicon-o-user-circle" class="w-6 h-6 text-purple-400" />
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Kontribusi Saya</div>
                        <div class="stat-value">{{ $dataSaya }}</div>
                        <div class="stat-desc">{{ $dataSayaBulanIni }} bulan ini</div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Main Table Section -->
        <x-filament::section>
            <x-slot name="heading">
                📋 Data Jumlah Pasien Harian
            </x-slot>
            <x-slot name="description">
                History lengkap data jumlah pasien per hari dengan filter dan pencarian
            </x-slot>

            <!-- Filter Tabs -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-2 p-4 bg-white/5 backdrop-blur-sm rounded-lg border border-white/10">
                    <button class="filter-tab active" data-filter="all">
                        👥 Semua Data ({{ \App\Models\JumlahPasienHarian::count() }})
                    </button>
                    <button class="filter-tab" data-filter="mine">
                        ✅ Data Saya ({{ $dataSaya }})
                    </button>
                    <button class="filter-tab" data-filter="today">
                        📅 Hari Ini ({{ $totalRecordsHariIni }})
                    </button>
                    <button class="filter-tab" data-filter="pending">
                        ⏳ Pending ({{ $pendingValidation }})
                    </button>
                    <button class="filter-tab" data-filter="approved">
                        ✅ Disetujui ({{ \App\Models\JumlahPasienHarian::where('status_validasi', 'approved')->count() }})
                    </button>
                </div>
            </div>

            <!-- Embedded Filament Table -->
            <div class="filament-table-container">
                {{ $this->table }}
            </div>
        </x-filament::section>

        <!-- Recent Activity & Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Aktivitas Terbaru -->
            <x-filament::section>
                <x-slot name="heading">
                    🕒 Aktivitas Terbaru
                </x-slot>
                <x-slot name="description">
                    5 data terakhir yang diinput
                </x-slot>

                <div class="space-y-3">
                    @php
                        $recentData = \App\Models\JumlahPasienHarian::with(['dokter', 'inputBy'])
                            ->latest()
                            ->take(5)
                            ->get();
                    @endphp

                    @foreach($recentData as $item)
                        <div class="activity-item">
                            <div class="activity-icon-container">
                                <div class="activity-icon bg-blue-500/20">
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-4 h-4 text-blue-400" />
                                </div>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    {{ $item->dokter?->nama_lengkap ?? 'Dokter tidak ditemukan' }}
                                </div>
                                <div class="activity-subtitle">
                                    {{ $item->tanggal->format('d/m/Y') }} • {{ ucfirst($item->poli) }} • {{ $item->shift }}
                                </div>
                                <div class="activity-details">
                                    Umum: {{ $item->jumlah_pasien_umum }} | BPJS: {{ $item->jumlah_pasien_bpjs }} | Total: {{ $item->jumlah_pasien_umum + $item->jumlah_pasien_bpjs }}
                                </div>
                            </div>
                            <div class="activity-meta">
                                <div class="activity-time">{{ $item->created_at->diffForHumans() }}</div>
                                <div class="activity-status status-{{ $item->status_validasi }}">
                                    @switch($item->status_validasi)
                                        @case('pending')
                                            ⏳ Pending
                                            @break
                                        @case('approved')
                                            ✅ Disetujui
                                            @break
                                        @case('rejected')
                                            ❌ Ditolak
                                            @break
                                        @default
                                            ⏳ Pending
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <!-- Performance Summary -->
            <x-filament::section>
                <x-slot name="heading">
                    📈 Performance Summary
                </x-slot>
                <x-slot name="description">
                    Ringkasan kinerja input data
                </x-slot>

                <div class="space-y-4">
                    <!-- Performance Metrics -->
                    <div class="performance-grid">
                        <div class="performance-item">
                            <div class="performance-value">{{ $totalBulanIni + $totalBpjsBulanIni }}</div>
                            <div class="performance-label">Total Pasien Bulan Ini</div>
                        </div>
                        
                        <div class="performance-item">
                            <div class="performance-value">{{ number_format(($approvedToday / max($totalRecordsHariIni, 1)) * 100, 1) }}%</div>
                            <div class="performance-label">Approval Rate Hari Ini</div>
                        </div>
                        
                        <div class="performance-item">
                            <div class="performance-value">{{ \App\Models\JumlahPasienHarian::distinct('dokter_id')->count('dokter_id') }}</div>
                            <div class="performance-label">Dokter Aktif</div>
                        </div>
                        
                        <div class="performance-item">
                            <div class="performance-value">{{ number_format(($dataSayaBulanIni / max(\App\Models\JumlahPasienHarian::whereMonth('tanggal', now()->month)->count(), 1)) * 100, 1) }}%</div>
                            <div class="performance-label">Kontribusi Saya</div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-white/70">Rata-rata Pasien per Hari:</span>
                            <span class="text-white font-semibold">{{ round(($totalBulanIni + $totalBpjsBulanIni) / max(now()->day, 1), 1) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-white/70">Poli Umum vs Gigi:</span>
                            <span class="text-white font-semibold">
                                {{ \App\Models\JumlahPasienHarian::where('poli', 'umum')->count() }} : {{ \App\Models\JumlahPasienHarian::where('poli', 'gigi')->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-white/70">Shift Pagi vs Sore:</span>
                            <span class="text-white font-semibold">
                                {{ \App\Models\JumlahPasienHarian::where('shift', 'Pagi')->count() }} : {{ \App\Models\JumlahPasienHarian::where('shift', 'Sore')->count() }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>

    <!-- Filter tabs functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter tabs functionality
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                // This would integrate with Livewire to filter the table
                console.log('Filter selected:', filter);
            });
        });
    });
    </script>

    <!-- CSS Styles -->
    <style>
        /* World-Class SaaS Horizontal Stats Layout */
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


        /* Horizontal Stat Cards (Linear/Notion-Style) */
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

        /* Mobile Responsive Adjustments */
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
        }


        /* Activity Items */
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

        /* Performance Grid */
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .performance-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }

        .performance-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .performance-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Filter Tabs */
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .filter-tab:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .filter-tab.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.3) 0%, rgba(99, 102, 241, 0.2) 100%);
            border-color: rgba(59, 130, 246, 0.4);
            color: #ffffff;
        }


        /* Filament Table Integration */
        .filament-table-container {
            background: rgba(10, 10, 11, 0.6);
            backdrop-filter: blur(12px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            /* Grid becomes 3 columns on tablet */
            .performance-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            /* Add button remains prominent on tablet */
            .add-button-card {
                padding: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            /* Grid becomes 2 columns on mobile */
            .add-button-card {
                padding: 1rem;
            }
            
            .add-button-title {
                font-size: 0.875rem;
            }
            
            .add-button-subtitle {
                font-size: 0.8125rem;
            }
        }

        @media (max-width: 640px) {
            /* Single column on small mobile */
            .stat-card {
                padding: 1rem;
            }
            
            .performance-item {
                padding: 0.75rem;
            }
            
            .filter-tab {
                font-size: 0.8125rem;
                padding: 0.375rem 0.75rem;
            }
            
            .add-button-card {
                padding: 0.875rem;
            }
            
            .add-button-content {
                gap: 0.5rem;
            }
        }
    </style>
</x-filament-panels::page>