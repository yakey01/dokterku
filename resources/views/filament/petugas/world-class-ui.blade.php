{{-- World-Class UI System for Petugas Panel --}}
@push('styles')
{{-- Professional Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* ===================================
       1. CSS VARIABLES & DESIGN TOKENS
    =================================== */
    :root {
        /* Brand Colors */
        --medical-blue: #3b82f6;
        --healthcare-green: #10b981;
        --professional-gold: #f59e0b;
        --danger-red: #ef4444;
        --neutral-gray: #6b7280;
        
        /* Glass Morphism */
        --glass-white: rgba(255, 255, 255, 0.9);
        --glass-white-70: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.18);
        --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        --glass-shadow-hover: 0 12px 40px 0 rgba(31, 38, 135, 0.25);
        
        /* Typography */
        --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --font-display: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        
        /* Animations */
        --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ===================================
       2. BASE STYLES & TYPOGRAPHY - ENHANCED READABILITY
    =================================== */
    body {
        font-family: var(--font-sans);
        font-optical-sizing: auto;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    /* CRITICAL: Force BLACK text for maximum readability */
    [data-filament-panel-id="petugas"] * {
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
        text-shadow: none !important;
    }
    
    /* NUCLEAR OPTION: Override ALL button text */
    [data-filament-panel-id="petugas"] button,
    [data-filament-panel-id="petugas"] button *,
    [data-filament-panel-id="petugas"] .fi-btn,
    [data-filament-panel-id="petugas"] .fi-btn *,
    [data-filament-panel-id="petugas"] [class*="btn"],
    [data-filament-panel-id="petugas"] [class*="btn"] *,
    [data-filament-panel-id="petugas"] [role="button"],
    [data-filament-panel-id="petugas"] [role="button"] * {
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
        text-shadow: none !important;
    }
    
    [data-filament-panel-id="petugas"] .text-gray-500,
    [data-filament-panel-id="petugas"] .text-gray-600,
    [data-filament-panel-id="petugas"] .text-gray-700 {
        color: #1f2937 !important;
        font-weight: 500 !important;
    }
    
    h1, h2, h3, h4, h5, h6,
    .fi-header-heading,
    .fi-modal-heading {
        font-family: var(--font-display) !important;
        font-weight: 700 !important;
        letter-spacing: -0.02em;
        color: #000000 !important;
    }

    /* ===================================
       3. GLASS MORPHISM COMPONENTS
    =================================== */
    
    /* Glass Card Base */
    .glass-card,
    .fi-section,
    .fi-fo-section {
        background: var(--glass-white) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 16px !important;
        box-shadow: var(--glass-shadow) !important;
        transition: all var(--transition-base) !important;
        overflow: hidden;
    }
    
    .glass-card:hover,
    .fi-section:hover {
        box-shadow: var(--glass-shadow-hover) !important;
        transform: translateY(-2px);
    }

    /* ===================================
       4. ENHANCED TABLES - WORLD-CLASS CRUD
    =================================== */
    
    /* Table Container */
    [data-filament-panel-id="petugas"] .fi-ta-content,
    [data-filament-panel-id="petugas"] .fi-ta-table-wrp {
        background: #ffffff !important;
        backdrop-filter: blur(12px) !important;
        border-radius: 16px !important;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08) !important;
        overflow: hidden !important;
        border: 2px solid #f59e0b !important;
    }
    
    /* Table Header - Premium Healthcare Style */
    [data-filament-panel-id="petugas"] .fi-ta-header-cell {
        background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%) !important;
        border-bottom: 3px solid #f59e0b !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        color: #000000 !important;
        padding: 1.25rem 1.5rem !important;
        font-size: 0.875rem !important;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    /* Table Rows - Enhanced Contrast */
    [data-filament-panel-id="petugas"] .fi-ta-row {
        background: #ffffff !important;
        transition: all 0.2s ease !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-ta-row:nth-child(even) {
        background: #fffbf5 !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-ta-row:hover {
        background: linear-gradient(90deg, #fef3c7 0%, #fed7aa 100%) !important;
        transform: translateX(4px);
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
    }
    
    /* Table Cells - Maximum Readability */
    [data-filament-panel-id="petugas"] .fi-ta-cell {
        padding: 1.25rem 1.5rem !important;
        color: #000000 !important;
        font-weight: 500 !important;
        font-size: 0.95rem !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-ta-text {
        color: #000000 !important;
        font-weight: 500 !important;
    }
    
    /* Empty State - Professional Design */
    [data-filament-panel-id="petugas"] .fi-ta-empty-state {
        padding: 5rem 2rem !important;
        background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%) !important;
        text-align: center !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-ta-empty-state-heading {
        font-size: 1.75rem !important;
        font-weight: 700 !important;
        color: #000000 !important;
        margin-bottom: 1rem !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-ta-empty-state-description {
        font-size: 1.125rem !important;
        color: #374151 !important;
        font-weight: 500 !important;
    }

    /* ===================================
       5. ENHANCED BUTTONS
    =================================== */
    
    /* Primary Button */
    .fi-btn {
        position: relative;
        overflow: hidden;
        transition: all var(--transition-base) !important;
        font-weight: 500 !important;
        letter-spacing: 0.02em !important;
    }
    
    .fi-btn-color-primary,
    .fi-ac-btn-action[data-action="create"] .fi-btn {
        background: linear-gradient(135deg, var(--medical-blue) 0%, #2563eb 100%) !important;
        border: none !important;
        box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.25) !important;
    }
    
    .fi-btn-color-primary:hover,
    .fi-ac-btn-action[data-action="create"] .fi-btn:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px 0 rgba(59, 130, 246, 0.35) !important;
    }
    
    /* Button Ripple Effect */
    .fi-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .fi-btn:active::before {
        width: 300px;
        height: 300px;
    }

    /* ===================================
       6. ENHANCED FORMS
    =================================== */
    
    /* Input Fields */
    .fi-input,
    .fi-select-input,
    .fi-textarea {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(8px) !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 12px !important;
        transition: all var(--transition-base) !important;
        padding: 0.75rem 1rem !important;
    }
    
    .fi-input:focus,
    .fi-select-input:focus,
    .fi-textarea:focus {
        background: rgba(255, 255, 255, 0.95) !important;
        border-color: var(--medical-blue) !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        transform: translateY(-1px);
        outline: none !important;
    }

    /* ===================================
       7. ENHANCED BADGES & STATUS
    =================================== */
    
    /* Badge Base */
    .fi-badge {
        backdrop-filter: blur(8px) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        font-weight: 600 !important;
        letter-spacing: 0.03em !important;
        padding: 0.375rem 0.875rem !important;
        border-radius: 9999px !important;
        transition: all var(--transition-base) !important;
    }
    
    /* Status Badges with Animation */
    .fi-badge-color-warning {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        animation: pulse-warning 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    .fi-badge-color-success {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%) !important;
    }
    
    .fi-badge-color-danger {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%) !important;
    }
    
    @keyframes pulse-warning {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.05);
        }
    }

    /* ===================================
       8. BLACK ELEGANT SIDEBAR & LAYOUT
    =================================== */
    
    /* FORCE BLACK SIDEBAR - ULTRA PRIORITY WITH MAXIMUM SPECIFICITY */
    body [data-filament-panel-id="petugas"] .fi-sidebar,
    html body [data-filament-panel-id="petugas"] .fi-sidebar,
    .fi-layout [data-filament-panel-id="petugas"] .fi-sidebar,
    [data-filament-panel-id="petugas"] aside.fi-sidebar,
    [data-filament-panel-id="petugas"] .fi-sidebar-nav,
    [data-filament-panel-id="petugas"] .fi-sidebar > * {
        background: #000000 !important;
        background-color: #000000 !important;
        background-image: none !important;
        backdrop-filter: none !important;
        border-right: 1px solid #1a1a1a !important;
        box-shadow: 6px 0 30px rgba(0, 0, 0, 0.5) !important;
    }
    
    .fi-sidebar-nav {
        padding: 1.5rem 1rem !important;
    }
    
    /* Sidebar Brand */
    [data-filament-panel-id="petugas"] .fi-sidebar-header {
        background: #000000 !important;
        border-bottom: 1px solid #1a1a1a !important;
        padding: 2rem 1.5rem !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-sidebar-header .fi-logo {
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
    }
    
    /* Navigation Items */
    .fi-sidebar-item {
        margin-bottom: 0.5rem !important;
    }
    
    .fi-sidebar-item-button {
        background: transparent !important;
        color: #cbd5e1 !important;
        border-radius: 12px !important;
        padding: 0.875rem 1.25rem !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        font-weight: 500 !important;
        border: 1px solid transparent !important;
    }
    
    .fi-sidebar-item-button:hover {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%) !important;
        color: #f8fafc !important;
        transform: translateX(4px) !important;
        border-color: rgba(59, 130, 246, 0.2) !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15) !important;
    }
    
    .fi-sidebar-item-button[aria-current="page"] {
        background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%) !important;
        color: white !important;
        font-weight: 600 !important;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3) !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }
    
    .fi-sidebar-item-icon {
        margin-right: 0.875rem !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
    }
    
    /* Navigation Groups */
    .fi-sidebar-group-label {
        color: #94a3b8 !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        margin: 1.5rem 0 0.75rem 0 !important;
        padding: 0 1.25rem !important;
    }
    
    /* Sidebar Toggle */
    .fi-sidebar-toggle {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }
    
    .fi-sidebar-toggle:hover {
        background: linear-gradient(135deg, #334155 0%, #475569 100%) !important;
        transform: scale(1.05) !important;
    }
    
    /* Page Background - White Dashboard */
    .fi-page {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%) !important;
        min-height: 100vh;
        position: relative;
    }
    
    .fi-page::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 40% 20%, rgba(245, 158, 11, 0.02) 0%, transparent 50%);
        pointer-events: none;
    }
    
    /* Main Content Area */
    .fi-main {
        background: transparent !important;
    }
    
    /* Page Header Enhancement */
    .fi-page-header {
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
        margin-bottom: 2rem !important;
        border-radius: 16px !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04) !important;
    }

    /* ===================================
       9. ENHANCED WIDGET ANIMATIONS
    =================================== */
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInFromLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }
        100% {
            background-position: calc(200px + 100%) 0;
        }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease-out;
    }
    
    .animate-slide-up {
        animation: slideUp 0.6s ease-out;
    }
    
    .animate-slide-in-left {
        animation: slideInFromLeft 0.7s ease-out;
    }
    
    .animate-bounce-in {
        animation: bounceIn 0.8s ease-out;
    }
    
    /* Widget Loading Effect */
    .fi-widget.loading {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200px 100%;
        animation: shimmer 2s infinite;
    }
    
    /* Enhanced Widget Styling */
    .fi-widget {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border-radius: 16px !important;
        overflow: hidden !important;
        backdrop-filter: blur(12px) !important;
    }
    
    .fi-widget:hover {
        transform: translateY(-4px) !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
    }
    
    /* Widget Content Animation */
    .fi-widget > * {
        animation: slideUp 0.6s ease-out !important;
    }
    
    .fi-widget:nth-child(1) { animation-delay: 0.1s; }
    .fi-widget:nth-child(2) { animation-delay: 0.2s; }
    .fi-widget:nth-child(3) { animation-delay: 0.3s; }
    .fi-widget:nth-child(4) { animation-delay: 0.4s; }
    .fi-widget:nth-child(5) { animation-delay: 0.5s; }

    /* ===================================
       10. ACCESSIBILITY ENHANCEMENTS
    =================================== */
    
    /* Focus Indicators */
    *:focus-visible {
        outline: 3px solid var(--medical-blue) !important;
        outline-offset: 2px !important;
        border-radius: 4px;
    }
    
    /* Reduced Motion Support */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // FORCE BLACK SIDEBAR IMMEDIATELY
    function forceBlackSidebar() {
        const sidebar = document.querySelector('.fi-sidebar');
        if (sidebar) {
            sidebar.style.setProperty('background', '#000000', 'important');
            sidebar.style.setProperty('background-color', '#000000', 'important');
            sidebar.style.setProperty('background-image', 'none', 'important');
            sidebar.style.setProperty('border-right', '1px solid #1a1a1a', 'important');
            sidebar.style.setProperty('box-shadow', '6px 0 30px rgba(0, 0, 0, 0.5)', 'important');
            
            // Also force the header to be black
            const header = sidebar.querySelector('.fi-sidebar-header');
            if (header) {
                header.style.setProperty('background', '#000000', 'important');
                header.style.setProperty('background-color', '#000000', 'important');
                header.style.setProperty('background-image', 'none', 'important');
            }
        }
    }
    
    // Apply immediately
    forceBlackSidebar();
    
    // Apply after any DOM changes
    const observer = new MutationObserver(forceBlackSidebar);
    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    }
    
    // Apply on Livewire navigation
    document.addEventListener('livewire:navigated', forceBlackSidebar);
    
    // Fix ResizeObserver errors
    const resizeObserverErrorHandler = (e) => {
        if (e.message === 'ResizeObserver loop completed with undelivered notifications.' ||
            e.message === 'ResizeObserver loop limit exceeded') {
            e.stopImmediatePropagation();
        }
    };
    window.addEventListener('error', resizeObserverErrorHandler);
    
    // Smooth scroll
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Add ripple effect to buttons
    document.querySelectorAll('.fi-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255,255,255,0.6)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s ease-out';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Add animation classes to elements as they appear
    const intersectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    });
    
    document.querySelectorAll('.fi-section, .fi-ta-row').forEach(el => {
        intersectionObserver.observe(el);
    });
    
    // CRITICAL: Force black text on all buttons via JavaScript
    function forceBlackText() {
        const petugasPanel = document.querySelector('[data-filament-panel-id="petugas"]');
        if (petugasPanel) {
            // Target all buttons and their children
            const buttons = petugasPanel.querySelectorAll('button, .fi-btn, [role="button"], [class*="btn"]');
            buttons.forEach(button => {
                button.style.setProperty('color', '#000000', 'important');
                button.style.setProperty('-webkit-text-fill-color', '#000000', 'important');
                button.style.setProperty('text-shadow', 'none', 'important');
                
                // Force on all children too
                const children = button.querySelectorAll('*');
                children.forEach(child => {
                    child.style.setProperty('color', '#000000', 'important');
                    child.style.setProperty('-webkit-text-fill-color', '#000000', 'important');
                    child.style.setProperty('text-shadow', 'none', 'important');
                });
            });
            
            // Special targeting for "Input Pasien" text
            const allElements = petugasPanel.querySelectorAll('*');
            allElements.forEach(element => {
                if (element.textContent && (element.textContent.includes('Input Pasien') || element.textContent.includes('Tambah'))) {
                    element.style.setProperty('color', '#000000', 'important');
                    element.style.setProperty('-webkit-text-fill-color', '#000000', 'important');
                    element.style.setProperty('text-shadow', 'none', 'important');
                }
            });
        }
    }
    
    // Apply black text immediately
    forceBlackText();
    
    // Re-apply after Livewire updates
    document.addEventListener('livewire:navigated', forceBlackText);
    document.addEventListener('livewire:update', forceBlackText);
    
    // Use MutationObserver to catch dynamic content - FIXED variable name conflict
    if (!window.petugasMutationObserver && document.querySelector('[data-filament-panel-id="petugas"]')) {
        window.petugasMutationObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    setTimeout(forceBlackText, 50);
                }
            });
        });
        
        window.petugasMutationObserver.observe(document.querySelector('[data-filament-panel-id="petugas"]'), {
            childList: true,
            subtree: true
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N for new patient
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            const createButton = document.querySelector('[wire\\:click*="mountAction"]');
            if (createButton && createButton.textContent.includes('Input Pasien')) {
                createButton.click();
            }
        }
        
        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
});

// Ripple animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush