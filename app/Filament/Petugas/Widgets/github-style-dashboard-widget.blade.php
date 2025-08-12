<x-filament-widgets::widget>
    <x-filament::section>
        <div class="github-dashboard-wrapper">
            <div class="github-dashboard">
            <!-- GitHub-style Header -->
            <div class="header-section">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="avatar-container">
                            <img src="{{ $this->getDashboardData()['user']['avatar'] }}" 
                                 alt="Avatar" 
                                 class="w-16 h-16 rounded-full border-4 border-orange-200 shadow-lg">
                            <div class="status-indicator"></div>
                        </div>
                        <div>
                            <h1 class="github-title">
                                👋 Halo, {{ $this->getDashboardData()['user']['name'] }}
                            </h1>
                            <p class="github-subtitle">
                                {{ $this->getDashboardData()['user']['role'] }} • {{ $this->getDashboardData()['user']['location'] }}
                            </p>
                        </div>
                    </div>
                    <div class="date-badge">
                        <span class="date-text">{{ now()->format('d M Y') }}</span>
                        <span class="time-text">{{ now()->format('H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- GitHub-style Stats Cards -->
            <div class="stats-grid">
                @php $stats = $this->getDashboardData()['stats']['today']; @endphp
                
                <div class="stat-card patients">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Pasien Hari Ini</span>
                        <span class="stat-value">{{ $stats['patients'] }}</span>
                    </div>
                    <div class="stat-trend">+12%</div>
                </div>

                <div class="stat-card procedures">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Tindakan</span>
                        <span class="stat-value">{{ $stats['procedures'] }}</span>
                    </div>
                    <div class="stat-trend">+8%</div>
                </div>

                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Pendapatan</span>
                        <span class="stat-value">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="stat-trend">+15%</div>
                </div>

                <div class="stat-card efficiency">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Efisiensi</span>
                        <span class="stat-value">{{ $stats['efficiency'] }}%</span>
                    </div>
                    <div class="stat-trend">+5%</div>
                </div>
            </div>

            <!-- GitHub-style Main Content -->
            <div class="main-content">
                <!-- Activity Feed -->
                <div class="activity-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                            </svg>
                            Aktivitas Terbaru
                        </h3>
                        <span class="activity-count">{{ count($this->getDashboardData()['activities']) }}</span>
                    </div>
                    <div class="activity-list">
                        @foreach($this->getDashboardData()['activities'] as $activity)
                            <div class="activity-item">
                                <div class="activity-icon {{ $activity['type'] }}">
                                    @switch($activity['type'])
                                        @case('patient')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            @break
                                        @case('procedure')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                                            @break
                                        @case('payment')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                                            @break
                                        @case('appointment')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                                            @break
                                    @endswitch
                                </div>
                                <div class="activity-content">
                                    <span class="activity-message">{{ $activity['message'] }}</span>
                                    <span class="activity-time">{{ $activity['time'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="actions-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                            </svg>
                            Aksi Cepat
                        </h3>
                    </div>
                    <div class="actions-grid">
                        @foreach($this->getDashboardData()['quickActions'] as $action)
                            <a href="{{ $action['url'] }}" class="action-button {{ $action['color'] }}">
                                <div class="action-icon">
                                    @switch($action['icon'])
                                        @case('user-plus')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            @break
                                        @case('clipboard-list')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                                            @break
                                        @case('currency-dollar')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/></svg>
                                            @break
                                        @case('chart-bar')
                                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>
                                            @break
                                    @endswitch
                                </div>
                                <span class="action-title">{{ $action['title'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
/* GitHub-inspired Dashboard Styling */
.github-dashboard {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif;
    color: #24292f;
    line-height: 1.5;
}

.dark .github-dashboard {
    color: #f0f6fc;
}

/* Header Section */
.header-section {
    border-bottom: 1px solid #d0d7de;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
}

.dark .header-section {
    border-color: #30363d;
}

.avatar-container {
    position: relative;
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 16px;
    height: 16px;
    background: #00d084;
    border: 3px solid #ffffff;
    border-radius: 50%;
}

.dark .status-indicator {
    border-color: #0d1117;
}

.github-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #1f2328;
}

.dark .github-title {
    color: #f0f6fc;
}

.github-subtitle {
    color: #656d76;
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
}

.dark .github-subtitle {
    color: #7d8590;
}

.date-badge {
    display: flex;
    flex-direction: column;
    align-items: end;
    padding: 0.5rem 1rem;
    background: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 0.5rem;
}

.dark .date-badge {
    background: #161b22;
    border-color: #30363d;
}

.date-text {
    font-weight: 600;
    font-size: 0.875rem;
    color: #1f2328;
}

.dark .date-text {
    color: #f0f6fc;
}

.time-text {
    font-size: 0.75rem;
    color: #656d76;
    margin-top: 0.125rem;
}

.dark .time-text {
    color: #7d8590;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
    position: relative;
    overflow: hidden;
}

.dark .stat-card {
    background: #0d1117;
    border-color: #30363d;
}

.stat-card:hover {
    border-color: #fd7e14;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(253, 126, 20, 0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #fd7e14, #f59e0b);
    opacity: 0;
    transition: opacity 0.2s;
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.stat-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.stat-card.patients .stat-icon {
    background: #dbeafe;
    color: #2563eb;
}

.dark .stat-card.patients .stat-icon {
    background: rgba(37, 99, 235, 0.2);
    color: #60a5fa;
}

.stat-card.procedures .stat-icon {
    background: #dcfce7;
    color: #16a34a;
}

.dark .stat-card.procedures .stat-icon {
    background: rgba(22, 163, 74, 0.2);
    color: #4ade80;
}

.stat-card.revenue .stat-icon {
    background: #fef3c7;
    color: #d97706;
}

.dark .stat-card.revenue .stat-icon {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
}

.stat-card.efficiency .stat-icon {
    background: #f3e8ff;
    color: #9333ea;
}

.dark .stat-card.efficiency .stat-icon {
    background: rgba(147, 51, 234, 0.2);
    color: #c084fc;
}

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: #656d76;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.dark .stat-label {
    color: #7d8590;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2328;
    margin-top: 0.25rem;
}

.dark .stat-value {
    color: #f0f6fc;
}

.stat-trend {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1a7f37;
    background: #dcfce7;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    margin-left: 0.5rem;
}

.dark .stat-trend {
    color: #4ade80;
    background: rgba(22, 163, 74, 0.2);
}

/* Main Content */
.main-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

@media (max-width: 1024px) {
    .main-content {
        grid-template-columns: 1fr;
    }
}

/* Activity Card */
.activity-card, .actions-card {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 0.5rem;
    overflow: hidden;
}

.dark .activity-card,
.dark .actions-card {
    background: #0d1117;
    border-color: #30363d;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #d0d7de;
    background: #f6f8fa;
}

.dark .card-header {
    border-color: #30363d;
    background: #161b22;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #1f2328;
    margin: 0;
}

.dark .card-title {
    color: #f0f6fc;
}

.activity-count {
    background: #0969da;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
}

.activity-list {
    padding: 0;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #d0d7de;
    transition: background-color 0.15s ease-in-out;
}

.dark .activity-item {
    border-color: #30363d;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: #f6f8fa;
}

.dark .activity-item:hover {
    background: #161b22;
}

.activity-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-icon svg {
    width: 1rem;
    height: 1rem;
}

.activity-icon.patient {
    background: #dbeafe;
    color: #2563eb;
}

.dark .activity-icon.patient {
    background: rgba(37, 99, 235, 0.2);
    color: #60a5fa;
}

.activity-icon.procedure {
    background: #dcfce7;
    color: #16a34a;
}

.dark .activity-icon.procedure {
    background: rgba(22, 163, 74, 0.2);
    color: #4ade80;
}

.activity-icon.payment {
    background: #fef3c7;
    color: #d97706;
}

.dark .activity-icon.payment {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
}

.activity-icon.appointment {
    background: #f3e8ff;
    color: #9333ea;
}

.dark .activity-icon.appointment {
    background: rgba(147, 51, 234, 0.2);
    color: #c084fc;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-message {
    display: block;
    color: #1f2328;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.dark .activity-message {
    color: #f0f6fc;
}

.activity-time {
    display: block;
    color: #656d76;
    font-size: 0.75rem;
}

.dark .activity-time {
    color: #7d8590;
}

/* Actions Grid */
.actions-grid {
    display: grid;
    gap: 1rem;
    padding: 1.5rem;
}

.action-button {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 0.5rem;
    text-decoration: none;
    color: #1f2328;
    transition: all 0.15s ease-in-out;
    font-weight: 500;
}

.dark .action-button {
    background: #161b22;
    border-color: #30363d;
    color: #f0f6fc;
}

.action-button:hover {
    background: #ffffff;
    border-color: #0969da;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(9, 105, 218, 0.12);
}

.dark .action-button:hover {
    background: #0d1117;
    border-color: #1f6feb;
}

.action-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.action-icon svg {
    width: 1.125rem;
    height: 1.125rem;
}

.action-button.emerald .action-icon {
    background: #dcfce7;
    color: #16a34a;
}

.dark .action-button.emerald .action-icon {
    background: rgba(22, 163, 74, 0.2);
    color: #4ade80;
}

.action-button.blue .action-icon {
    background: #dbeafe;
    color: #2563eb;
}

.dark .action-button.blue .action-icon {
    background: rgba(37, 99, 235, 0.2);
    color: #60a5fa;
}

.action-button.amber .action-icon {
    background: #fef3c7;
    color: #d97706;
}

.dark .action-button.amber .action-icon {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
}

.action-button.purple .action-icon {
    background: #f3e8ff;
    color: #9333ea;
}

.dark .action-button.purple .action-icon {
    background: rgba(147, 51, 234, 0.2);
    color: #c084fc;
}

.action-title {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .github-dashboard {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .main-content {
        gap: 1rem;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
    }
    
    .activity-item {
        padding: 0.75rem 1rem;
    }
    
    .actions-grid {
        padding: 1rem;
    }
}
</style>