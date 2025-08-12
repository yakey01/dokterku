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
       2. BASE STYLES & TYPOGRAPHY
    =================================== */
    body {
        font-family: var(--font-sans);
        font-optical-sizing: auto;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    h1, h2, h3, h4, h5, h6,
    .fi-header-heading,
    .fi-modal-heading {
        font-family: var(--font-display) !important;
        font-weight: 700 !important;
        letter-spacing: -0.02em;
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
       4. ENHANCED TABLES
    =================================== */
    
    /* Table Container */
    .fi-ta-table-wrp {
        background: var(--glass-white) !important;
        backdrop-filter: blur(12px) !important;
        border-radius: 16px !important;
        box-shadow: var(--glass-shadow) !important;
        overflow: hidden !important;
        border: 1px solid var(--glass-border) !important;
    }
    
    /* Table Header */
    .fi-ta-header-cell {
        background: linear-gradient(135deg, 
            rgba(59, 130, 246, 0.1) 0%, 
            rgba(16, 185, 129, 0.05) 100%) !important;
        backdrop-filter: blur(10px) !important;
        border-bottom: 2px solid var(--medical-blue) !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        color: var(--medical-blue) !important;
        padding: 1rem 1.25rem !important;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    /* Table Rows */
    .fi-ta-row {
        transition: all var(--transition-fast) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    
    .fi-ta-row:hover {
        background: linear-gradient(90deg, 
            rgba(59, 130, 246, 0.05) 0%, 
            rgba(16, 185, 129, 0.03) 100%) !important;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    }
    
    /* Table Cells */
    .fi-ta-cell {
        padding: 1rem 1.25rem !important;
    }
    
    /* Make No. RM stand out */
    .fi-ta-text[data-column="no_rekam_medis"] {
        font-family: 'Monaco', 'Courier New', monospace !important;
        font-weight: 600 !important;
        color: var(--medical-blue) !important;
        font-size: 0.95rem !important;
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
    
    /* Sidebar Styling */
    .fi-sidebar {
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #334155 100%) !important;
        backdrop-filter: blur(20px) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15) !important;
    }
    
    .fi-sidebar-nav {
        padding: 1.5rem 1rem !important;
    }
    
    /* Sidebar Brand */
    .fi-sidebar-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding: 2rem 1.5rem !important;
    }
    
    .fi-sidebar-header .fi-logo {
        color: #f8fafc !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
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
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    });
    
    document.querySelectorAll('.fi-section, .fi-ta-row').forEach(el => {
        observer.observe(el);
    });
    
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