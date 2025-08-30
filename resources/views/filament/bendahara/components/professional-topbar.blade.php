{{-- PROFESSIONAL SAAS TOP BAR - INSPIRED BY TABLER & MODERN SAAS --}}
<div class="professional-saas-topbar">
    <div class="topbar-container">
        <!-- Left Section: Breadcrumb & Page Info -->
        <div class="topbar-left">
            <div class="breadcrumb-section">
                <nav class="breadcrumb-nav">
                    <a href="/bendahara" class="breadcrumb-item">
                        <svg class="breadcrumb-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        Dashboard
                    </a>
                    <span class="breadcrumb-separator">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9.293 6.293a1 1 0 011.414 0L15 10.586a1 1 0 010 1.414L10.707 16.293a1 1 0 01-1.414-1.414L13.586 11 9.293 6.707a1 1 0 010-1.414z"/>
                        </svg>
                    </span>
                    <span class="breadcrumb-current">Bendahara</span>
                </nav>
            </div>
            <div class="page-info">
                <h1 class="page-title">💰 Financial Management</h1>
                <p class="page-subtitle">{{ now()->format('l, F j, Y') }} • {{ auth()->user()->name ?? 'Bendahara' }}</p>
            </div>
        </div>

        <!-- Center Section: Quick Stats -->
        <div class="topbar-center">
            <div class="quick-stats">
                @php
                    $quickFinancial = Cache::get('bendahara_financial_summary', ['current' => ['net_profit' => 0]]);
                @endphp
                <div class="stat-item">
                    <div class="stat-icon {{ ($quickFinancial['current']['net_profit'] ?? 0) >= 0 ? 'stat-positive' : 'stat-negative' }}">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="{{ ($quickFinancial['current']['net_profit'] ?? 0) >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Net Profit</span>
                        <span class="stat-value">Rp {{ number_format(abs($quickFinancial['current']['net_profit'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section: Actions & User Info -->
        <div class="topbar-right">
            <div class="action-buttons">
                <button class="action-btn" title="Refresh Data" onclick="window.location.reload()">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button class="action-btn" title="Export Report">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                </button>
                <button class="action-btn primary" title="Quick Validation">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="btn-text">Validate</span>
                </button>
            </div>
            
            <div class="user-section">
                <div class="user-avatar">
                    <div class="avatar-circle">
                        <span class="avatar-initials">{{ strtoupper(substr(auth()->user()->name ?? 'FT', 0, 2)) }}</span>
                    </div>
                    <div class="status-indicator"></div>
                </div>
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name ?? 'Fitri Tri' }}</span>
                    <span class="user-role">Treasury Manager</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ================================================================ */
/* PROFESSIONAL SAAS TOP BAR - INSPIRED BY TABLER & MODERN SAAS */
/* ================================================================ */

.professional-saas-topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    
    /* Glassmorphic Background */
    background: linear-gradient(135deg, 
        rgba(10, 10, 11, 0.95) 0%,
        rgba(17, 17, 24, 0.9) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    
    /* Elegant Border */
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    
    /* Premium Shadow */
    box-shadow: 
        0 1px 3px rgba(0, 0, 0, 0.2),
        0 1px 2px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.topbar-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 100%;
    margin: 0 auto;
    padding: 0.75rem 2rem;
    gap: 2rem;
}

/* ================================================================ */
/* LEFT SECTION: BREADCRUMB & PAGE INFO */
/* ================================================================ */

.topbar-left {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
    min-width: 0;
}

.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #a1a1aa;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #a1a1aa;
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb-item:hover {
    color: #fafafa;
}

.breadcrumb-icon {
    width: 0.875rem;
    height: 0.875rem;
}

.breadcrumb-separator {
    color: #71717a;
}

.breadcrumb-separator svg {
    width: 0.75rem;
    height: 0.75rem;
}

.breadcrumb-current {
    color: #fafafa;
    font-weight: 500;
}

.page-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.page-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fafafa;
    margin: 0;
    line-height: 1.2;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.page-subtitle {
    font-size: 0.75rem;
    color: #a1a1aa;
    margin: 0;
    opacity: 0.9;
    font-weight: 500;
}

/* ================================================================ */
/* CENTER SECTION: QUICK STATS */
/* ================================================================ */

.topbar-center {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
}

.quick-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.stat-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.stat-icon svg {
    width: 1rem;
    height: 1rem;
}

.stat-positive {
    background: linear-gradient(135deg, rgba(34, 214, 95, 0.2) 0%, rgba(34, 214, 95, 0.1) 100%);
    color: #22d65f;
    border: 1px solid rgba(34, 214, 95, 0.3);
}

.stat-negative {
    background: linear-gradient(135deg, rgba(248, 113, 113, 0.2) 0%, rgba(248, 113, 113, 0.1) 100%);
    color: #f87171;
    border: 1px solid rgba(248, 113, 113, 0.3);
}

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.stat-label {
    font-size: 0.6875rem;
    color: #a1a1aa;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: #fafafa;
    font-variant-numeric: tabular-nums;
}

/* ================================================================ */
/* RIGHT SECTION: ACTIONS & USER */
/* ================================================================ */

.topbar-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex: 0 0 auto;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    color: #a1a1aa;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
    font-weight: 500;
    backdrop-filter: blur(4px);
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: #fafafa;
    transform: translateY(-1px);
}

.action-btn.primary {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.8) 0%, rgba(217, 119, 6, 0.7) 100%);
    border-color: rgba(245, 158, 11, 0.3);
    color: #ffffff;
}

.action-btn.primary:hover {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.9) 0%, rgba(217, 119, 6, 0.8) 100%);
    border-color: rgba(245, 158, 11, 0.5);
}

.action-btn svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.btn-text {
    display: none;
}

@media (min-width: 768px) {
    .action-btn .btn-text {
        display: inline;
    }
    
    .action-btn {
        padding: 0.5rem 0.75rem;
    }
}

/* ================================================================ */
/* USER SECTION */
/* ================================================================ */

.user-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.375rem 0.75rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.75rem;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
    cursor: pointer;
}

.user-section:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
}

.user-avatar {
    position: relative;
}

.avatar-circle {
    width: 2rem;
    height: 2rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.avatar-initials {
    font-size: 0.75rem;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.status-indicator {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 0.5rem;
    height: 0.5rem;
    background: #22d65f;
    border: 2px solid rgba(10, 10, 11, 0.8);
    border-radius: 50%;
    animation: statusPulse 2s infinite;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #fafafa;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.6875rem;
    color: #a1a1aa;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ================================================================ */
/* ANIMATIONS */
/* ================================================================ */

@keyframes statusPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.1);
    }
}

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.professional-saas-topbar {
    animation: slideInFromTop 0.5s ease-out;
}

/* ================================================================ */
/* RESPONSIVE DESIGN */
/* ================================================================ */

@media (max-width: 1024px) {
    .topbar-container {
        padding: 0.75rem 1.5rem;
        gap: 1.5rem;
    }
    
    .topbar-center {
        display: none;
    }
    
    .page-title {
        font-size: 1.125rem;
    }
    
    .user-info {
        display: none;
    }
}

@media (max-width: 768px) {
    .topbar-container {
        padding: 0.5rem 1rem;
        gap: 1rem;
    }
    
    .breadcrumb-section {
        display: none;
    }
    
    .page-title {
        font-size: 1rem;
    }
    
    .action-buttons {
        gap: 0.25rem;
    }
    
    .action-btn {
        padding: 0.375rem;
    }
}

/* ================================================================ */
/* DARK MODE ENHANCEMENTS */
/* ================================================================ */

.dark .professional-saas-topbar {
    background: linear-gradient(135deg, 
        rgba(10, 10, 11, 0.98) 0%,
        rgba(17, 17, 24, 0.95) 100%);
    border-bottom-color: rgba(255, 255, 255, 0.15);
}

/* ================================================================ */
/* ACCESSIBILITY */
/* ================================================================ */

.action-btn:focus-visible {
    outline: 2px solid rgba(245, 158, 11, 0.8);
    outline-offset: 2px;
}

.user-section:focus-visible {
    outline: 2px solid rgba(245, 158, 11, 0.8);
    outline-offset: 2px;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .professional-saas-topbar,
    .action-btn,
    .user-section,
    .stat-item {
        animation: none;
        transition: none;
    }
    
    .status-indicator {
        animation: none;
    }
}

/* ================================================================ */
/* INTEGRATION WITH BODY */
/* ================================================================ */

/* Add top padding to body untuk compensate fixed topbar */
body {
    padding-top: 4.5rem;
}

@media (max-width: 768px) {
    body {
        padding-top: 4rem;
    }
}
</style>

{{-- ENHANCED JAVASCRIPT FOR SAAS FUNCTIONALITY --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Professional SaaS Top Bar - Initializing...');
    
    // Dynamic greeting based on time
    function updateGreeting() {
        const hour = new Date().getHours();
        const userName = '{{ auth()->user()->name ?? "Fitri Tri" }}';
        
        let timeGreeting = '';
        if (hour < 11) timeGreeting = 'Good morning';
        else if (hour < 15) timeGreeting = 'Good afternoon';
        else if (hour < 18) timeGreeting = 'Good evening';
        else timeGreeting = 'Good evening';
        
        // Update page subtitle dengan time-based greeting
        const subtitle = document.querySelector('.page-subtitle');
        if (subtitle) {
            const date = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            subtitle.textContent = `${date} • ${timeGreeting}, ${userName}`;
        }
    }
    
    // Auto-refresh quick stats
    function refreshQuickStats() {
        // Add subtle animation untuk indicate refresh
        const statItems = document.querySelectorAll('.stat-item');
        statItems.forEach(item => {
            item.style.opacity = '0.7';
            setTimeout(() => {
                item.style.opacity = '1';
            }, 300);
        });
    }
    
    // Initialize
    updateGreeting();
    setInterval(updateGreeting, 30 * 60 * 1000); // Every 30 minutes
    
    // Auto-refresh stats every 5 minutes
    setInterval(refreshQuickStats, 5 * 60 * 1000);
    
    console.log('✅ Professional SaaS Top Bar - Ready');
});
</script>