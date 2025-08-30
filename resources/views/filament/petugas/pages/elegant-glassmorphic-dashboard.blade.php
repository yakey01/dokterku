<x-filament-panels::page>
    {{-- ELEGANT GLASSMORPHIC DASHBOARD - PETUGAS PANEL --}}
    <div class="elegant-petugas-dashboard" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
        
        <!-- MINIMALIST DASHBOARD - WELCOME MOVED TO TOPBAR -->

        @php
            $operational = $this->getOperationalSummary();
            $dataEntry = $this->getDataEntryStats();
            $activities = $this->getRecentActivities();
            $trends = $this->getMonthlyTrends();
            $performers = $this->getTopPerformers();
        @endphp

        <!-- ELEGANT BLACK STATS GRID WITH GLASSMORPHIC EFFECTS -->
        <div class="stats-grid">
            <!-- Patient Statistics Card -->
            <div class="stat-card stat-card-patients" tabindex="0" role="button">
                <div class="stat-card-content">
                    <div class="stat-icon-container stat-icon-healthcare">
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-label">Total Pasien</h3>
                        <p class="stat-value">{{ number_format($operational['current']['pasien'], 0, ',', '.') }}</p>
                        <div class="stat-change {{ $operational['changes']['pasien'] >= 0 ? 'positive' : 'negative' }}">
                            <svg class="change-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $operational['changes']['pasien'] >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/>
                            </svg>
                            <span>{{ abs($operational['changes']['pasien']) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="stat-overlay"></div>
            </div>

            <!-- Medical Procedures Card -->
            <div class="stat-card stat-card-procedures" tabindex="0" role="button">
                <div class="stat-card-content">
                    <div class="stat-icon-container stat-icon-medical">
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-label">Total Tindakan</h3>
                        <p class="stat-value">{{ number_format($operational['current']['tindakan'], 0, ',', '.') }}</p>
                        <div class="stat-change {{ $operational['changes']['tindakan'] >= 0 ? 'positive' : 'negative' }}">
                            <svg class="change-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $operational['changes']['tindakan'] >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/>
                            </svg>
                            <span>{{ abs($operational['changes']['tindakan']) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="stat-overlay"></div>
            </div>

            <!-- Revenue Card -->
            <div class="stat-card stat-card-revenue" tabindex="0" role="button">
                <div class="stat-card-content">
                    <div class="stat-icon-container stat-icon-revenue">
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-label">Revenue Bulan Ini</h3>
                        <p class="stat-value">Rp {{ number_format($operational['current']['pendapatan'], 0, ',', '.') }}</p>
                        <div class="stat-change {{ $operational['changes']['pendapatan'] >= 0 ? 'positive' : 'negative' }}">
                            <svg class="change-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $operational['changes']['pendapatan'] >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/>
                            </svg>
                            <span>{{ abs($operational['changes']['pendapatan']) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="stat-overlay"></div>
            </div>

            <!-- Data Entry Progress Card -->
            <div class="stat-card stat-card-entries" tabindex="0" role="button">
                <div class="stat-card-content">
                    <div class="stat-icon-container stat-icon-progress">
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-label">Entries Hari Ini</h3>
                        <p class="stat-value">{{ $dataEntry['total_entries'] }}</p>
                        <div class="stat-progress">
                            @php 
                                $totalTarget = array_sum($dataEntry['targets']);
                                $progress = $totalTarget > 0 ? ($dataEntry['total_entries'] / $totalTarget * 100) : 0;
                            @endphp
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ min($progress, 100) }}%"></div>
                            </div>
                            <span class="progress-text">{{ round($progress) }}% dari target</span>
                        </div>
                    </div>
                </div>
                <div class="stat-overlay"></div>
            </div>
        </div>

        <!-- NAVIGATION LINKS WITH GLASSMORPHIC CARDS -->
        <div class="navigation-grid">
            <!-- Patient Management Card -->
            <div class="nav-card nav-card-patients">
                <div class="nav-card-header">
                    <div class="nav-icon-container">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="nav-card-title">Manajemen Pasien</h3>
                </div>
                <div class="nav-card-links">
                    <a href="{{ route('filament.petugas.resources.pasiens.index') }}" class="nav-link">
                        <svg class="link-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span>Kelola Data Pasien</span>
                    </a>
                    <a href="{{ route('filament.petugas.resources.jumlah-pasien-harians.index') }}" class="nav-link">
                        <svg class="link-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Jumlah Pasien Harian</span>
                    </a>
                </div>
            </div>

            <!-- Medical Procedures Card -->
            <div class="nav-card nav-card-medical">
                <div class="nav-card-header">
                    <div class="nav-icon-container">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="nav-card-title">Tindakan Medis</h3>
                </div>
                <div class="nav-card-links">
                    <a href="{{ route('filament.petugas.resources.tindakans.index') }}" class="nav-link">
                        <svg class="link-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Input Tindakan</span>
                    </a>
                    <a href="{{ route('filament.petugas.resources.tindakans.create') }}" class="nav-link nav-link-primary">
                        <svg class="link-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Tindakan Baru</span>
                    </a>
                </div>
            </div>

            <!-- Financial Management Card -->
            <div class="nav-card nav-card-financial">
                <div class="nav-card-header">
                    <div class="nav-icon-container">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h3 class="nav-card-title">Manajemen Keuangan</h3>
                </div>
                <div class="nav-card-links">
                    <a href="{{ route('filament.petugas.resources.pendapatan-harians.index') }}" class="nav-link">
                        <svg class="link-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span>Pendapatan Harian</span>
                    </a>
                    <a href="{{ route('filament.petugas.resources.pengeluaran-harians.index') }}" class="nav-link">
                        <svg class="link-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                        <span>Pengeluaran Harian</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITIES WITH GLASSMORPHIC DESIGN -->
        <div class="activities-section">
            <div class="activities-header">
                <h2 class="section-title">Aktivitas Terbaru</h2>
                <p class="section-subtitle">Pantau aktivitas operasional terkini</p>
            </div>
            
            <div class="activities-grid">
                @if(count($activities) > 0)
                    @foreach(array_slice($activities, 0, 8) as $index => $activity)
                        <div class="activity-item" style="animation-delay: {{ $index * 0.1 }}s">
                            <div class="activity-icon-container activity-icon-{{ $activity['type'] }}">
                                @if($activity['type'] === 'pendapatan')
                                    <svg class="activity-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                @elseif($activity['type'] === 'pengeluaran')
                                    <svg class="activity-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                    </svg>
                                @else
                                    <svg class="activity-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="activity-content">
                                <h4 class="activity-title">{{ $activity['description'] }}</h4>
                                <div class="activity-meta">
                                    @if($activity['amount'] > 0)
                                        <span class="activity-amount">Rp {{ number_format($activity['amount'], 0, ',', '.') }}</span>
                                    @endif
                                    <span class="activity-date">{{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}</span>
                                </div>
                                <p class="activity-author">oleh {{ $activity['created_by'] }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <h3>Belum Ada Aktivitas</h3>
                        <p>Mulai dengan input data pendapatan atau tindakan medis</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <style>
        /* ================================================================ */
        /* ELEGANT GLASSMORPHIC HEALTHCARE DASHBOARD - BENDAHARA INSPIRED */
        /* ================================================================ */

        .elegant-petugas-dashboard {
            background: linear-gradient(135deg, #060608 0%, #0d0d0f 100%);
            min-height: 100vh;
            padding: 1.5rem 2rem 2rem 2rem;
        }

        /* ================================================================ */
        /* MINIMALIST DASHBOARD - CLEAN FOCUS ON CONTENT */
        /* ================================================================ */

        /* ================================================================ */
        /* ELEGANT BLACK STATS GRID - INSPIRED BY WAVE DESIGN SYSTEM */
        /* ================================================================ */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            position: relative;
            background: linear-gradient(135deg, 
                rgba(10, 10, 11, 0.9) 0%, 
                rgba(17, 17, 24, 0.8) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            padding: 2rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.25),
                0 2px 12px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255, 255, 255, 0.3) 50%, 
                transparent 100%);
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(255, 255, 255, 0.15);
            background: linear-gradient(135deg, 
                rgba(17, 17, 24, 0.9) 0%, 
                rgba(26, 26, 32, 0.8) 100%);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.4),
                0 8px 24px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .stat-card:focus {
            outline: none;
            border-color: rgba(100, 116, 139, 0.4);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.4),
                0 8px 24px rgba(0, 0, 0, 0.2),
                0 0 0 3px rgba(100, 116, 139, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .stat-card-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .stat-icon-container {
            width: 4rem;
            height: 4rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            width: 1.75rem;
            height: 1.75rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .stat-icon-healthcare {
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.2) 0%, 
                rgba(37, 99, 235, 0.1) 100%);
            color: #60a5fa;
        }

        .stat-icon-medical {
            background: linear-gradient(135deg, 
                rgba(34, 197, 94, 0.2) 0%, 
                rgba(16, 185, 129, 0.1) 100%);
            color: #22d65f;
        }

        .stat-icon-revenue {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.2) 0%, 
                rgba(124, 58, 237, 0.1) 100%);
            color: #a855f7;
        }

        .stat-icon-progress {
            background: linear-gradient(135deg, 
                rgba(245, 158, 11, 0.2) 0%, 
                rgba(217, 119, 6, 0.1) 100%);
            color: #f59e0b;
        }

        .stat-card:hover .stat-icon-container {
            transform: scale(1.05) rotate(2deg);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .stat-content {
            flex: 1;
            min-width: 0;
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #a1a1aa;
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #fafafa;
            margin: 0 0 0.75rem 0;
            font-variant-numeric: tabular-nums;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-change.positive {
            background: rgba(34, 197, 94, 0.1);
            color: #22d65f;
            border-color: rgba(34, 197, 94, 0.2);
        }

        .stat-change.negative {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .change-icon {
            width: 0.875rem;
            height: 0.875rem;
        }

        .stat-progress {
            margin-top: 0.75rem;
        }

        .progress-bar {
            width: 100%;
            height: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.25rem;
            overflow: hidden;
            backdrop-filter: blur(4px);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
            border-radius: 0.25rem;
            transition: width 1s ease;
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.3);
        }

        .progress-text {
            font-size: 0.75rem;
            color: #a1a1aa;
            margin-top: 0.5rem;
            display: block;
        }

        .stat-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, 
                rgba(100, 116, 139, 0.03) 0%, 
                transparent 50%, 
                rgba(100, 116, 139, 0.03) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .stat-card:hover .stat-overlay {
            opacity: 1;
        }

        /* ================================================================ */
        /* NAVIGATION CARDS WITH GLASSMORPHIC EFFECTS */
        /* ================================================================ */

        .navigation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .nav-card {
            background: linear-gradient(135deg, 
                rgba(10, 10, 11, 0.8) 0%, 
                rgba(17, 17, 24, 0.7) 100%);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            padding: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.2),
                0 2px 8px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .nav-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.12);
            background: linear-gradient(135deg, 
                rgba(17, 17, 24, 0.9) 0%, 
                rgba(26, 26, 32, 0.8) 100%);
            box-shadow: 
                0 12px 32px rgba(0, 0, 0, 0.3),
                0 4px 16px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .nav-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .nav-icon-container {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .nav-icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #ffffff;
            filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.3));
        }

        .nav-card:hover .nav-icon-container {
            transform: scale(1.05) rotate(-2deg);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fafafa;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .nav-card-links {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.75rem;
            color: #e4e4e7;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
            color: #fafafa;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .nav-link-primary {
            background: linear-gradient(135deg, 
                rgba(100, 116, 139, 0.2) 0%, 
                rgba(71, 85, 105, 0.1) 100%);
            border-color: rgba(100, 116, 139, 0.3);
            color: #ffffff;
        }

        .nav-link-primary:hover {
            background: linear-gradient(135deg, 
                rgba(100, 116, 139, 0.3) 0%, 
                rgba(71, 85, 105, 0.2) 100%);
            border-color: rgba(100, 116, 139, 0.5);
        }

        .link-icon {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        /* ================================================================ */
        /* ACTIVITIES SECTION - GLASSMORPHIC DESIGN */
        /* ================================================================ */

        .activities-section {
            background: linear-gradient(135deg, 
                rgba(10, 10, 11, 0.6) 0%, 
                rgba(17, 17, 24, 0.5) 100%);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.2),
                0 2px 8px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .activities-header {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fafafa;
            margin: 0 0 0.5rem 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .section-subtitle {
            font-size: 0.875rem;
            color: #a1a1aa;
            margin: 0;
            opacity: 0.9;
        }

        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .activity-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .activity-icon-container {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .activity-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        .activity-icon-pendapatan {
            background: rgba(34, 197, 94, 0.1);
            color: #22d65f;
        }

        .activity-icon-pengeluaran {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
        }

        .activity-icon-tindakan {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
        }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fafafa;
            margin: 0 0 0.25rem 0;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .activity-amount {
            font-size: 0.75rem;
            font-weight: 600;
            color: #22d65f;
            background: rgba(34, 197, 94, 0.1);
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .activity-date {
            font-size: 0.75rem;
            color: #a1a1aa;
            font-weight: 500;
        }

        .activity-author {
            font-size: 0.75rem;
            color: #71717a;
            margin: 0;
            opacity: 0.8;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            grid-column: 1 / -1;
        }

        .empty-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            color: #71717a;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #a1a1aa;
            margin: 0 0 0.5rem 0;
        }

        .empty-state p {
            color: #71717a;
            margin: 0;
        }

        /* ================================================================ */
        /* RESPONSIVE DESIGN */
        /* ================================================================ */

        @media (max-width: 768px) {
            .elegant-petugas-dashboard {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-card:hover {
                transform: translateY(-4px) scale(1.01);
            }

            .navigation-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .nav-card {
                padding: 1.25rem;
            }

            .activities-section {
                padding: 1.5rem;
            }

            .activities-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .stat-card-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .stat-value {
                font-size: 1.75rem;
            }

            .nav-card-header {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }
        }

        /* ================================================================ */
        /* ACCESSIBILITY & REDUCED MOTION */
        /* ================================================================ */

        @media (prefers-reduced-motion: reduce) {
            .stat-card,
            .nav-card,
            .activity-item,
            .nav-link,
            .stat-icon-container {
                transition: none;
                animation: none;
            }

            .stat-card:hover,
            .nav-card:hover,
            .activity-item:hover,
            .nav-link:hover {
                transform: none;
            }

            .welcome-hero-section::before {
                animation: none;
            }

            .progress-fill {
                transition: none;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .stat-card,
            .nav-card,
            .activity-item {
                border-width: 2px;
            }

            .stat-change,
            .activity-amount {
                border-width: 2px;
            }
        }

        /* ================================================================ */
        /* GLASSMORPHIC ENHANCEMENTS */
        /* ================================================================ */

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.02) 0%,
                transparent 50%,
                rgba(255, 255, 255, 0.02) 100%);
            border-radius: inherit;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        /* Micro-interactions for enhanced UX */
        .stat-card:active {
            transform: translateY(-4px) scale(0.98);
            transition: all 0.1s ease;
        }

        .nav-link:active {
            transform: translateX(2px) scale(0.98);
        }

        /* Enhanced glass effects for premium look */
        .stat-card,
        .nav-card,
        .activities-section,
        .welcome-hero-section {
            background-image: 
                linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0%, transparent 50%),
                var(--bg-gradient, linear-gradient(135deg, rgba(10, 10, 11, 0.8) 0%, rgba(17, 17, 24, 0.7) 100%));
        }

        /* Staggered animation for cards */
        .stat-card:nth-child(1) { animation: slideInFromLeft 0.6s ease 0.1s forwards; opacity: 0; }
        .stat-card:nth-child(2) { animation: slideInFromLeft 0.6s ease 0.2s forwards; opacity: 0; }
        .stat-card:nth-child(3) { animation: slideInFromLeft 0.6s ease 0.3s forwards; opacity: 0; }
        .stat-card:nth-child(4) { animation: slideInFromLeft 0.6s ease 0.4s forwards; opacity: 0; }

        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-30px) translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateX(0) translateY(0);
            }
        }

        .nav-card:nth-child(1) { animation: slideInFromBottom 0.6s ease 0.5s forwards; opacity: 0; }
        .nav-card:nth-child(2) { animation: slideInFromBottom 0.6s ease 0.6s forwards; opacity: 0; }
        .nav-card:nth-child(3) { animation: slideInFromBottom 0.6s ease 0.7s forwards; opacity: 0; }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    {{-- ENHANCED INTERACTIVITY SCRIPT --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🏥 Elegant Glassmorphic Dashboard - Initializing...');
            
            // Enhanced card interactions
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.addEventListener('mouseenter', function() {
                    this.style.setProperty('--shadow-intensity', '1.5');
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.setProperty('--shadow-intensity', '1');
                });
                
                // Add subtle parallax effect on mouse move
                card.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = (y - centerY) / 20;
                    const rotateY = (centerX - x) / 20;
                    
                    this.style.transform = `translateY(-8px) scale(1.02) perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Progress bar animation
            const progressBars = document.querySelectorAll('.progress-fill');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const width = entry.target.style.width;
                        entry.target.style.width = '0%';
                        setTimeout(() => {
                            entry.target.style.width = width;
                        }, 500);
                    }
                });
            });

            progressBars.forEach(bar => observer.observe(bar));

            // Enhanced ripple effect on card click
            function createRipple(event) {
                const button = event.currentTarget;
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;

                const ripple = document.createElement('div');
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: rippleAnimation 0.6s ease-out;
                    pointer-events: none;
                `;

                button.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }

            // Add ripple effect to all interactive cards
            document.querySelectorAll('.stat-card, .nav-card').forEach(card => {
                card.addEventListener('click', createRipple);
            });

            console.log('✅ Elegant Glassmorphic Dashboard - Ready');
        });

        // CSS keyframes for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rippleAnimation {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</x-filament-panels::page>