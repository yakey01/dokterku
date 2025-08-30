<div class="world-class-petugas-header">
    <div class="header-background-wrapper">
        <!-- Header Content Container -->
        <div class="header-content-grid">
            
            <!-- Main Welcome Section -->
            <div class="welcome-main-section">
                <div class="header-title-section">
                    <h1 class="main-dashboard-title">
                        Sistem Manajemen Pasien
                    </h1>
                    <div class="header-subtitle">
                        Kelola data pasien dengan mudah dan efisien melalui sistem manajemen terpadu yang canggih
                    </div>
                </div>
                
                <div class="user-greeting-section">
                    <div class="user-greeting">
                        {{ $greeting }}, <span class="user-name-highlight">{{ $user->name ?? 'Petugas' }}</span>! 👋
                    </div>
                    <div class="user-details">
                        <span class="user-role-badge">{{ $userRole }}</span>
                        <span class="last-login">Login terakhir: {{ $lastLoginTime }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Time and Date Section -->
            <div class="time-date-section">
                <div class="current-time-display">
                    <div class="time-main">{{ $currentTime }}</div>
                    <div class="date-main">{{ $currentDate }}</div>
                </div>
                <div class="timezone-info">
                    <span class="timezone-label">WIB</span>
                    <span class="timezone-location">Indonesia</span>
                </div>
            </div>
            
            <!-- Enhanced Quick Stats -->
            <div class="enhanced-quick-stats">
                <div class="stat-card patients-today">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $totalPatientsToday }}</div>
                        <div class="stat-label">Pasien Hari Ini</div>
                    </div>
                </div>
                
                <div class="stat-card system-status">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-content">
                        <div class="stat-value">{{ $systemStatus['database'] }}</div>
                        <div class="stat-label">Database</div>
                    </div>
                </div>
                
                <div class="stat-card panel-mode">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <div class="stat-value">Aktif</div>
                        <div class="stat-label">Status Panel</div>
                    </div>
                </div>
                
                <div class="stat-card real-time">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-content">
                        <div class="stat-value">Live</div>
                        <div class="stat-label">Sinkronisasi</div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Quick Actions Bar -->
        <div class="quick-actions-bar">
            <div class="action-button primary">
                <span class="action-icon">➕</span>
                <span class="action-text">Tambah Pasien</span>
            </div>
            <div class="action-button secondary">
                <span class="action-icon">📊</span>
                <span class="action-text">Lihat Laporan</span>
            </div>
            <div class="action-button tertiary">
                <span class="action-icon">⚙️</span>
                <span class="action-text">Pengaturan</span>
            </div>
        </div>
    </div>
</div>

<style>
/* ========================================
   WORLD-CLASS PETUGAS HEADER DESIGN
   Premium Dashboard Header with Modern Aesthetics
   ======================================== */

.world-class-petugas-header {
    position: relative;
    margin-bottom: 2rem;
    border-radius: 1.5rem;
    overflow: hidden;
    background: linear-gradient(135deg, 
        rgba(15, 23, 42, 0.98) 0%, 
        rgba(30, 41, 59, 0.95) 25%, 
        rgba(51, 65, 85, 0.92) 50%, 
        rgba(71, 85, 105, 0.90) 100%);
    backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.4),
        0 10px 25px -5px rgba(59, 130, 246, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.header-background-wrapper {
    position: relative;
    padding: 2rem;
    z-index: 1;
}

.header-background-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.12) 0%, transparent 60%),
        radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.12) 0%, transparent 60%),
        radial-gradient(circle at 50% 40%, rgba(245, 158, 11, 0.08) 0%, transparent 60%);
    pointer-events: none;
    z-index: -1;
}

/* Header Content Grid Layout */
.header-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1.5fr;
    gap: 2rem;
    align-items: center;
    margin-bottom: 1.5rem;
}

/* Main Dashboard Title Section */
.header-title-section {
    margin-bottom: 1.5rem;
}

.main-dashboard-title {
    font-size: 2.25rem;
    font-weight: 800;
    color: #f8fafc;
    margin: 0 0 0.75rem 0;
    line-height: 1.1;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    background: linear-gradient(135deg, 
        #f8fafc 0%, 
        #60a5fa 50%, 
        #34d399 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.header-subtitle {
    font-size: 1rem;
    color: #cbd5e1;
    line-height: 1.6;
    font-weight: 400;
    max-width: 500px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* User Greeting Section */
.user-greeting-section {
    border-left: 3px solid rgba(59, 130, 246, 0.4);
    padding-left: 1rem;
}

.user-greeting {
    font-size: 1.375rem;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.user-name-highlight {
    color: #60a5fa;
    background: linear-gradient(45deg, #3b82f6, #10b981);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
}

.user-details {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.user-role-badge {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(16, 185, 129, 0.2));
    color: #93c5fd;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid rgba(59, 130, 246, 0.3);
    backdrop-filter: blur(5px);
}

.last-login {
    color: #94a3b8;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Time and Date Section */
.time-date-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.6);
    border-radius: 1rem;
    border: 1px solid rgba(75, 85, 99, 0.3);
    backdrop-filter: blur(10px);
}

.current-time-display {
    text-align: center;
    margin-bottom: 0.75rem;
}

.time-main {
    font-size: 2.5rem;
    font-weight: 700;
    color: #60a5fa;
    font-family: 'JetBrains Mono', 'Fira Code', 'SF Mono', monospace;
    text-shadow: 0 0 20px rgba(96, 165, 250, 0.4);
    margin-bottom: 0.25rem;
}

.date-main {
    font-size: 0.875rem;
    color: #e2e8f0;
    font-weight: 500;
    text-transform: capitalize;
}

.timezone-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #94a3b8;
}

.timezone-label {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 600;
}

.timezone-location {
    color: #cbd5e1;
}

/* Enhanced Quick Stats Grid */
.enhanced-quick-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(30, 41, 59, 0.7);
    border: 1px solid rgba(75, 85, 99, 0.3);
    border-radius: 0.75rem;
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, 
        rgba(59, 130, 246, 0.05) 0%, 
        rgba(16, 185, 129, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.stat-card:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.4);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 0.125rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.stat-label {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Specific stat card colors */
.stat-card.patients-today:hover {
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.4);
}

.stat-card.system-status:hover {
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.4);
}

.stat-card.panel-mode:hover {
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
}

.stat-card.real-time:hover {
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.4);
}

/* Quick Actions Bar */
.quick-actions-bar {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(75, 85, 99, 0.3);
}

.action-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    user-select: none;
    backdrop-filter: blur(10px);
    border: 1px solid transparent;
}

.action-button.primary {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(16, 185, 129, 0.8));
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.action-button.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.action-button.secondary {
    background: rgba(75, 85, 99, 0.6);
    color: #e2e8f0;
    border-color: rgba(75, 85, 99, 0.8);
}

.action-button.secondary:hover {
    background: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.4);
    color: #fbbf24;
    transform: translateY(-2px);
}

.action-button.tertiary {
    background: rgba(75, 85, 99, 0.4);
    color: #cbd5e1;
    border-color: rgba(75, 85, 99, 0.6);
}

.action-button.tertiary:hover {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
    color: #c4b5fd;
    transform: translateY(-2px);
}

.action-icon {
    font-size: 1rem;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.action-text {
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .header-content-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .enhanced-quick-stats {
        grid-column: 1 / -1;
        grid-template-columns: repeat(4, 1fr);
        margin-top: 1rem;
    }
}

@media (max-width: 768px) {
    .header-background-wrapper {
        padding: 1.5rem;
    }
    
    .header-content-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        text-align: center;
    }
    
    .main-dashboard-title {
        font-size: 1.75rem;
    }
    
    .header-subtitle {
        font-size: 0.875rem;
        max-width: none;
    }
    
    .user-greeting-section {
        border-left: none;
        border-top: 3px solid rgba(59, 130, 246, 0.4);
        border-radius: 0.5rem;
        padding: 1rem;
        background: rgba(30, 41, 59, 0.3);
    }
    
    .time-date-section {
        padding: 1rem;
    }
    
    .time-main {
        font-size: 2rem;
    }
    
    .enhanced-quick-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .quick-actions-bar {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .action-button {
        justify-content: center;
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .main-dashboard-title {
        font-size: 1.5rem;
    }
    
    .enhanced-quick-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 0.75rem;
    }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
    .world-class-petugas-header {
        box-shadow: 
            0 25px 50px -12px rgba(0, 0, 0, 0.6),
            0 10px 25px -5px rgba(59, 130, 246, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }
    
    .main-dashboard-title {
        background: linear-gradient(135deg, 
            #cbd5e1 0%, 
            #60a5fa 50%, 
            #34d399 100%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
}

/* Animation for live elements */
@keyframes pulse {
    0%, 100% { 
        opacity: 1; 
        transform: scale(1);
    }
    50% { 
        opacity: 0.8; 
        transform: scale(1.05);
    }
}

.stat-card.real-time .stat-icon {
    animation: pulse 2s ease-in-out infinite;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .stat-card,
    .action-button,
    .stat-card.real-time .stat-icon {
        animation: none;
        transition: none;
    }
}
</style>