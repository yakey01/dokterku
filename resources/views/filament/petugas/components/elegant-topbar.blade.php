{{-- WORLD-CLASS MINIMALIST TOPBAR - INSPIRED BY LINEAR & VERCEL --}}
<div class="world-class-topbar">
    <div class="topbar-container">
        <!-- Left Section: Welcome Message & Context -->
        <div class="topbar-left">
            <div class="welcome-section">
                <h1 class="welcome-title">Selamat Datang, {{ auth()->user()->name ?? 'Fitri Tri' }}</h1>
                <p class="welcome-subtitle">Healthcare Management Dashboard • {{ now()->format('l, d F Y') }}</p>
            </div>
        </div>

        <!-- Center Section: Minimalist Status Indicator -->
        <div class="topbar-center">
            <div class="status-indicator">
                @php
                    $todayStats = \App\Models\Tindakan::whereDate('created_at', today())->count();
                @endphp
                <div class="status-badge">
                    <span class="status-dot"></span>
                    <span class="status-text">{{ $todayStats }} aktivitas hari ini</span>
                </div>
            </div>
        </div>

        <!-- Right Section: Minimalist Actions & Avatar -->
        <div class="topbar-right">
            <div class="action-buttons">
                <button class="minimalist-btn" title="Quick Search" onclick="document.querySelector('.form-control')?.focus()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <button class="minimalist-btn" title="Notifications">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-3.5-3.5a5.5 5.5 0 01-1.5-3.8V7a6 6 0 00-6-6v0a6 6 0 00-6 6v2.7c0 1.3-.6 2.6-1.5 3.8L0 17h5m10 0v1a3 3 0 11-6 0v-1"/>
                    </svg>
                </button>
            </div>
            
            <div class="minimalist-avatar">
                <div class="avatar-dropdown">
                    <div class="avatar-circle" onclick="toggleUserDropdown()">
                        <span class="avatar-initials">{{ strtoupper(substr(auth()->user()->name ?? 'FT', 0, 2)) }}</span>
                        <div class="avatar-status-dot"></div>
                    </div>
                    
                    <!-- User Dropdown Menu -->
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="dropdown-header">
                            <div class="user-info-dropdown">
                                <div class="user-name">{{ auth()->user()->name ?? 'Fitri Tri' }}</div>
                                <div class="user-email">{{ auth()->user()->email ?? 'fitri@dokterku.com' }}</div>
                                <div class="user-role-tag">Petugas</div>
                            </div>
                        </div>
                        
                        <div class="dropdown-divider"></div>
                        
                        <div class="dropdown-menu-items">
                            <a href="/petugas/profile" class="dropdown-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="item-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Profil Saya</span>
                            </a>
                            
                            <a href="/petugas/settings" class="dropdown-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="item-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Pengaturan</span>
                            </a>
                        </div>
                        
                        <div class="dropdown-divider"></div>
                        
                        <form method="POST" action="{{ route('filament.petugas.auth.logout') }}" class="logout-form">
                            @csrf
                            <button type="submit" class="dropdown-item logout-item">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="item-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ================================================================ */
/* WORLD-CLASS MINIMALIST TOPBAR - INSPIRED BY LINEAR & VERCEL */
/* ================================================================ */

.world-class-topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    height: 4rem;
    
    /* Minimalist Glass Background */
    background: rgba(10, 10, 11, 0.8);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    
    /* Ultra-subtle Border */
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    
    /* Minimal Shadow */
    box-shadow: 
        0 1px 3px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.02);
}

.topbar-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 100%;
    height: 4rem;
    margin: 0 auto;
    padding: 0 1.5rem;
    gap: 2rem;
}

/* ================================================================ */
/* LEFT SECTION: MINIMALIST WELCOME MESSAGE */
/* ================================================================ */

.topbar-left {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
}

.welcome-section {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.welcome-title {
    font-size: 1rem;
    font-weight: 600;
    color: #fafafa;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.01em;
}

.welcome-subtitle {
    font-size: 0.75rem;
    color: #71717a;
    margin: 0;
    font-weight: 400;
    opacity: 0.8;
}

/* ================================================================ */
/* CENTER SECTION: MINIMALIST STATUS INDICATOR */
/* ================================================================ */

.topbar-center {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
}

.status-indicator {
    display: flex;
    align-items: center;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.04);
    border-radius: 2rem;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}

.status-badge:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.08);
}

.status-dot {
    width: 0.375rem;
    height: 0.375rem;
    background: #22d65f;
    border-radius: 50%;
    animation: statusPulse 2s ease-in-out infinite;
    flex-shrink: 0;
}

.status-text {
    font-size: 0.75rem;
    color: #a1a1aa;
    font-weight: 500;
    white-space: nowrap;
}

/* ================================================================ */
/* RIGHT SECTION: MINIMALIST ACTIONS & AVATAR */
/* ================================================================ */

.topbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.minimalist-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    color: #71717a;
    cursor: pointer;
    transition: all 0.2s ease;
}

.minimalist-btn:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #a1a1aa;
    transform: scale(1.05);
}

.minimalist-btn:active {
    transform: scale(0.95);
    transition: all 0.1s ease;
}

.minimalist-btn svg {
    width: 1rem;
    height: 1rem;
}

/* ================================================================ */
/* MINIMALIST AVATAR WITH DROPDOWN */
/* ================================================================ */

.minimalist-avatar {
    position: relative;
}

.avatar-dropdown {
    position: relative;
}

.avatar-circle {
    width: 2rem;
    height: 2rem;
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.06);
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
}

.avatar-circle:hover {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
    border-color: rgba(255, 255, 255, 0.1);
    transform: scale(1.05);
}

.avatar-initials {
    font-size: 0.75rem;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -0.01em;
}

.avatar-status-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 0.5rem;
    height: 0.5rem;
    background: #22d65f;
    border-radius: 50%;
    border: 2px solid rgba(10, 10, 11, 0.8);
    animation: statusPulse 2s ease-in-out infinite;
}

/* ================================================================ */
/* USER DROPDOWN MENU */
/* ================================================================ */

.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    width: 280px;
    background: rgba(15, 15, 18, 0.95);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.75rem;
    box-shadow: 
        0 20px 25px -5px rgba(0, 0, 0, 0.3),
        0 10px 10px -5px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px) scale(0.95);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
}

.user-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.dropdown-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.user-info-dropdown {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-info-dropdown .user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #f8fafc;
    margin: 0;
}

.user-info-dropdown .user-email {
    font-size: 0.75rem;
    color: #71717a;
    margin: 0;
}

.user-role-tag {
    align-self: flex-start;
    margin-top: 0.5rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(16, 185, 129, 0.2));
    color: #93c5fd;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.dropdown-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.06);
    margin: 0;
}

.dropdown-menu-items {
    padding: 0.5rem 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #e2e8f0;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #f8fafc;
    transform: translateX(4px);
}

.dropdown-item.logout-item {
    color: #f87171;
}

.dropdown-item.logout-item:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #fca5a5;
}

.item-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.logout-form {
    margin: 0;
    padding: 0.5rem 0;
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

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.world-class-topbar {
    animation: fadeInDown 0.3s ease-out;
}

/* ================================================================ */
/* RESPONSIVE DESIGN - MINIMALIST APPROACH */
/* ================================================================ */

@media (max-width: 1024px) {
    .topbar-container {
        padding: 0 1rem;
        gap: 1rem;
    }
    
    .topbar-center {
        display: none;
    }
    
    .welcome-title {
        font-size: 0.875rem;
    }
    
    .welcome-subtitle {
        font-size: 0.6875rem;
    }
}

@media (max-width: 768px) {
    .topbar-container {
        padding: 0 0.75rem;
        gap: 0.75rem;
    }
    
    .welcome-subtitle {
        display: none;
    }
    
    .action-buttons {
        gap: 0.125rem;
    }
    
    .minimalist-btn {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .minimalist-btn svg {
        width: 0.875rem;
        height: 0.875rem;
    }
    
    .avatar-circle {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .avatar-initials {
        font-size: 0.6875rem;
    }
}

/* ================================================================ */
/* ACCESSIBILITY & INTEGRATION */
/* ================================================================ */

.minimalist-btn:focus-visible {
    outline: 2px solid rgba(100, 116, 139, 0.6);
    outline-offset: 2px;
}

.avatar-circle:focus-visible {
    outline: 2px solid rgba(100, 116, 139, 0.6);
    outline-offset: 2px;
}

/* Animation for loading and spinning */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Responsive dropdown adjustments */
@media (max-width: 768px) {
    .user-dropdown-menu {
        width: 260px;
        right: -0.5rem;
    }
}

@media (max-width: 480px) {
    .user-dropdown-menu {
        width: 240px;
        right: -1rem;
    }
    
    .dropdown-item {
        padding: 0.625rem 0.875rem;
        font-size: 0.8125rem;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .world-class-topbar,
    .minimalist-btn,
    .avatar-circle,
    .status-badge,
    .user-dropdown-menu,
    .dropdown-item {
        animation: none;
        transition: none;
    }
    
    .status-dot,
    .avatar-status-dot {
        animation: none;
    }
}

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

{{-- MINIMALIST TOPBAR SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌟 World-Class Minimalist Topbar - Initializing...');
    
    // Remove any duplicate topbars that might be created dynamically
    function removeDuplicateTopbars() {
        // Hide all Filament topbars
        const filamentTopbars = document.querySelectorAll('.fi-topbar, .fi-header, .fi-page-header');
        filamentTopbars.forEach(topbar => {
            topbar.style.display = 'none';
            topbar.style.visibility = 'hidden';
            topbar.style.height = '0';
            topbar.style.padding = '0';
            topbar.style.margin = '0';
        });
        
        // Hide other custom topbars except ours
        const otherTopbars = document.querySelectorAll('.elegant-healthcare-topbar, .professional-saas-topbar');
        otherTopbars.forEach(topbar => {
            topbar.style.display = 'none';
        });
        
        // Ensure our topbar is visible
        const ourTopbar = document.querySelector('.world-class-topbar');
        if (ourTopbar) {
            ourTopbar.style.display = 'block';
            ourTopbar.style.visibility = 'visible';
            ourTopbar.style.zIndex = '9999';
        }
    }
    
    // Dynamic time-based greeting
    function updateDynamicGreeting() {
        const hour = new Date().getHours();
        const userName = '{{ auth()->user()->name ?? "Fitri Tri" }}';
        
        let greeting = 'Selamat Datang';
        if (hour >= 5 && hour < 12) greeting = 'Selamat Pagi';
        else if (hour >= 12 && hour < 15) greeting = 'Selamat Siang';
        else if (hour >= 15 && hour < 18) greeting = 'Selamat Sore';
        else greeting = 'Selamat Malam';
        
        const titleElement = document.querySelector('.welcome-title');
        if (titleElement) {
            titleElement.textContent = `${greeting}, ${userName}`;
        }
    }
    
    // Minimalist status updates
    function updateStatusBadge() {
        const badge = document.querySelector('.status-badge');
        if (badge) {
            badge.style.opacity = '0.7';
            setTimeout(() => {
                badge.style.opacity = '1';
            }, 200);
        }
    }
    
    // Keyboard shortcuts for quick actions
    document.addEventListener('keydown', function(e) {
        // Cmd/Ctrl + K for search (Linear-inspired)
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchBtn = document.querySelector('[title="Quick Search"]');
            if (searchBtn) searchBtn.click();
        }
        
        // Cmd/Ctrl + N for new patient (SaaS standard)
        if ((e.metaKey || e.ctrlKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/petugas/resources/pasiens/create';
        }
    });
    
    // NUCLEAR SIDEBAR COLOR FIX - FORCE ALL ELEMENTS BLACK
    function forceSidebarBlackTheme() {
        // Find all sidebar elements
        const sidebar = document.querySelector('.fi-sidebar');
        if (!sidebar) return;
        
        // Force all elements in sidebar to black theme
        const allSidebarElements = sidebar.querySelectorAll('*');
        allSidebarElements.forEach(element => {
            // Force background to black gradient
            element.style.setProperty('background', 
                'linear-gradient(135deg, rgba(10, 10, 11, 0.95) 0%, rgba(17, 17, 24, 0.8) 100%)', 
                'important');
            
            // Force text to white
            element.style.setProperty('color', '#fafafa', 'important');
            
            // Force borders to subtle white
            if (element.style.borderColor || getComputedStyle(element).borderColor !== 'rgba(0, 0, 0, 0)') {
                element.style.setProperty('border-color', 'rgba(255, 255, 255, 0.08)', 'important');
            }
        });
        
        // Specifically target buttons and interactive elements
        const buttons = sidebar.querySelectorAll('button, [role="button"], [aria-expanded], .fi-sidebar-collapse-btn');
        buttons.forEach(btn => {
            btn.style.setProperty('background', 
                'linear-gradient(135deg, rgba(26, 26, 32, 0.8) 0%, rgba(42, 42, 50, 0.6) 100%)', 
                'important');
            btn.style.setProperty('color', '#a1a1aa', 'important');
            btn.style.setProperty('border', '1px solid rgba(255, 255, 255, 0.08)', 'important');
            btn.style.setProperty('border-radius', '0.5rem', 'important');
        });
        
        // Fix any tooltips or dropdowns
        setTimeout(() => {
            const tooltips = document.querySelectorAll('.tippy-box, [data-tippy-root]');
            tooltips.forEach(tip => {
                tip.style.setProperty('background', 
                    'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)', 
                    'important');
                tip.style.setProperty('color', '#fafafa', 'important');
            });
        }, 100);
    }

    // Enhanced sidebar interactions
    function enhanceSidebarInteractions() {
        const sidebarItems = document.querySelectorAll('.fi-sidebar-nav-item');
        
        sidebarItems.forEach(item => {
            // Track mouse position for ripple effects
            item.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                
                this.style.setProperty('--mouse-x', `${x}%`);
                this.style.setProperty('--mouse-y', `${y}%`);
            });
            
            // Enhanced click feedback
            item.addEventListener('click', function(e) {
                // Add temporary glow effect
                this.style.boxShadow = `
                    0 8px 32px rgba(100, 116, 139, 0.3),
                    0 4px 16px rgba(71, 85, 105, 0.2),
                    inset 0 1px 0 rgba(255, 255, 255, 0.1)`;
                
                setTimeout(() => {
                    this.style.boxShadow = '';
                }, 300);
            });
        });
        
        // Enhance collapse functionality
        const collapseButtons = document.querySelectorAll('[aria-expanded], .fi-sidebar-group-label');
        collapseButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Force black theme after collapse animation
                setTimeout(() => {
                    forceSidebarBlackTheme();
                }, 350); // After animation completes
            });
        });
    }
    
    // Initialize
    removeDuplicateTopbars();
    updateDynamicGreeting();
    forceSidebarBlackTheme();
    enhanceSidebarInteractions();
    
    // Run fixes periodically (for dynamic content)
    setTimeout(removeDuplicateTopbars, 1000); // After 1 second
    setTimeout(forceSidebarBlackTheme, 1500); // Force sidebar colors
    setTimeout(removeDuplicateTopbars, 3000); // After 3 seconds
    setTimeout(forceSidebarBlackTheme, 3500); // Force sidebar colors again
    
    // Continuous monitoring for dynamic elements
    setInterval(forceSidebarBlackTheme, 10000); // Every 10 seconds
    setInterval(updateDynamicGreeting, 5 * 60 * 1000); // Every 5 minutes
    setInterval(updateStatusBadge, 30 * 1000); // Every 30 seconds
    
    // Observe for DOM changes and re-apply styling
    const observer = new MutationObserver(() => {
        setTimeout(forceSidebarBlackTheme, 100);
    });
    
    const sidebar = document.querySelector('.fi-sidebar');
    if (sidebar) {
        observer.observe(sidebar, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    }
    
    console.log('✅ Nuclear Sidebar Color Fix + Elegant Topbar - Ready');
});

// User Dropdown Functions
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (!dropdown) return;
    
    const isVisible = dropdown.classList.contains('show');
    
    if (isVisible) {
        hideUserDropdown();
    } else {
        showUserDropdown();
    }
}

function showUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (!dropdown) return;
    
    dropdown.classList.add('show');
    
    // Add click outside listener
    setTimeout(() => {
        document.addEventListener('click', handleClickOutside);
    }, 10);
}

function hideUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    if (!dropdown) return;
    
    dropdown.classList.remove('show');
    document.removeEventListener('click', handleClickOutside);
}

function handleClickOutside(event) {
    const dropdown = document.getElementById('userDropdownMenu');
    const avatar = document.querySelector('.avatar-circle');
    
    if (!dropdown || !avatar) return;
    
    // If click is outside dropdown and avatar
    if (!dropdown.contains(event.target) && !avatar.contains(event.target)) {
        hideUserDropdown();
    }
}

// Close dropdown on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideUserDropdown();
    }
});

// Logout confirmation
document.addEventListener('DOMContentLoaded', function() {
    const logoutForm = document.querySelector('.logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Sweet confirmation dialog
            if (confirm('Apakah Anda yakin ingin keluar dari sistem?')) {
                // Add loading state
                const logoutBtn = this.querySelector('.logout-item');
                if (logoutBtn) {
                    logoutBtn.innerHTML = `
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="item-icon animate-spin">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Mengeluarkan...</span>
                    `;
                }
                
                // Submit after short delay for better UX
                setTimeout(() => {
                    this.submit();
                }, 500);
            }
        });
    }
});
</script>