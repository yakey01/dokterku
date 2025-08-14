{{-- World-Class UI/UX 2025 - Top 10 Trends Implementation --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* World-Class UI/UX 2025 Inline Styles */
    /* Additional inline enhancements */
    [data-filament-panel-id="petugas"] {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    /* GLASSMORPHISM SIDEBAR - Ultimate Black Glass */
    body [data-filament-panel-id="petugas"] .fi-sidebar,
    body [data-filament-panel-id="petugas"] aside.fi-sidebar,
    body [data-filament-panel-id="petugas"] .fi-sidebar-nav,
    html body [data-filament-panel-id="petugas"] .fi-sidebar {
        background: #000000 !important;
        background-color: #000000 !important;
        background-image: linear-gradient(135deg, 
            rgba(0, 0, 0, 0.95) 0%, 
            rgba(10, 10, 10, 0.9) 50%, 
            rgba(20, 20, 20, 0.85) 100%) !important;
        backdrop-filter: blur(30px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(30px) saturate(200%) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 
            inset 0 0 30px rgba(100, 126, 234, 0.05),
            4px 0 24px rgba(0, 0, 0, 0.5) !important;
    }
    
    /* Force all child elements to inherit */
    body [data-filament-panel-id="petugas"] .fi-sidebar * {
        background-color: transparent !important;
    }
    
    /* AI-Powered Navigation Items */
    [data-filament-panel-id="petugas"] .fi-sidebar-item button {
        position: relative;
        overflow: hidden;
        background: transparent !important;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
    }
    
    [data-filament-panel-id="petugas"] .fi-sidebar-item button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(100, 126, 234, 0.2), 
            transparent);
        transition: left 0.5s ease;
    }
    
    [data-filament-panel-id="petugas"] .fi-sidebar-item button:hover::before {
        left: 100%;
    }
    
    [data-filament-panel-id="petugas"] .fi-sidebar-item button:hover {
        background: linear-gradient(135deg, 
            rgba(100, 126, 234, 0.1) 0%, 
            rgba(118, 75, 162, 0.1) 100%) !important;
        border: 1px solid rgba(100, 126, 234, 0.3);
        transform: translateX(5px);
        color: #ffffff !important;
    }
    
    /* Active state with AI gradient border */
    [data-filament-panel-id="petugas"] .fi-sidebar-item-active button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 
            0 0 20px rgba(100, 126, 234, 0.4),
            inset 0 0 20px rgba(255, 255, 255, 0.1) !important;
    }
    
    /* BENTO GRID DASHBOARD */
    [data-filament-panel-id="petugas"] .fi-page,
    [data-filament-panel-id="petugas"] .fi-main,
    [data-filament-panel-id="petugas"] .fi-main-ctn {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
        min-height: 100vh;
        position: relative;
    }
    
    [data-filament-panel-id="petugas"] .fi-page::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20% 50%, rgba(100, 126, 234, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(245, 87, 108, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 40% 20%, rgba(0, 242, 254, 0.05) 0%, transparent 50%);
        pointer-events: none;
    }
    
    /* GLASSMORPHISM CARDS */
    [data-filament-panel-id="petugas"] .fi-section,
    [data-filament-panel-id="petugas"] .fi-card,
    [data-filament-panel-id="petugas"] .fi-wi-stats-overview-card,
    [data-filament-panel-id="petugas"] .fi-ta-table,
    [data-filament-panel-id="petugas"] .fi-fo-field-wrp {
        background: rgba(255, 255, 255, 0.25) !important;
        backdrop-filter: blur(20px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
        border-radius: 24px !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        box-shadow: 
            0 8px 32px 0 rgba(31, 38, 135, 0.15),
            inset 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-section:hover,
    [data-filament-panel-id="petugas"] .fi-card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 
            0 15px 40px 0 rgba(31, 38, 135, 0.25),
            inset 0 0 0 1px rgba(255, 255, 255, 0.2) !important;
    }
    
    /* MICRO-INTERACTIONS FOR BUTTONS */
    [data-filament-panel-id="petugas"] .fi-btn {
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        border-radius: 12px;
    }
    
    [data-filament-panel-id="petugas"] .fi-btn::after {
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
    
    [data-filament-panel-id="petugas"] .fi-btn:active::after {
        width: 300px;
        height: 300px;
    }
    
    [data-filament-panel-id="petugas"] .fi-btn:hover {
        transform: scale(1.05) rotate(0.5deg);
        box-shadow: 0 10px 30px rgba(100, 126, 234, 0.3);
    }
    
    /* AI GRADIENT INDICATORS */
    [data-filament-panel-id="petugas"] .fi-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        background-size: 200% 200%;
        animation: aiGradientShift 3s ease infinite;
        color: white !important;
        font-weight: 600;
        border: none;
        box-shadow: 0 4px 15px rgba(100, 126, 234, 0.3);
    }
    
    @keyframes aiGradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* WORLD-CLASS FORM INPUTS - FORCE ALL FORMS */
    [data-filament-panel-id="petugas"] .fi-input,
    [data-filament-panel-id="petugas"] input[type="text"],
    [data-filament-panel-id="petugas"] input[type="number"],
    [data-filament-panel-id="petugas"] input[type="date"],
    [data-filament-panel-id="petugas"] select,
    [data-filament-panel-id="petugas"] textarea {
        background: white !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 14px !important;
        padding: 0.875rem 1.25rem !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-input:focus,
    [data-filament-panel-id="petugas"] input:focus,
    [data-filament-panel-id="petugas"] select:focus,
    [data-filament-panel-id="petugas"] textarea:focus {
        border-color: #667eea !important;
        box-shadow: 
            0 0 0 4px rgba(102, 126, 234, 0.1),
            0 4px 16px rgba(102, 126, 234, 0.1) !important;
        transform: translateY(-2px) !important;
        outline: none !important;
    }
    
    /* WORLD-CLASS FORM SECTIONS - FORCE ALL SECTIONS */
    [data-filament-panel-id="petugas"] .fi-fo-section,
    [data-filament-panel-id="petugas"] .fi-form-section,
    [data-filament-panel-id="petugas"] section {
        background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%) !important;
        border-radius: 20px !important;
        box-shadow: 
            0 4px 16px rgba(0, 0, 0, 0.04),
            0 8px 32px rgba(0, 0, 0, 0.02) !important;
        padding: 2rem !important;
        margin-bottom: 1.5rem !important;
        position: relative !important;
        overflow: hidden !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-fo-section::before,
    [data-filament-panel-id="petugas"] section::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    [data-filament-panel-id="petugas"] .fi-fo-section:hover::before,
    [data-filament-panel-id="petugas"] section:hover::before {
        opacity: 1;
    }
    
    [data-filament-panel-id="petugas"] .fi-fo-section:hover,
    [data-filament-panel-id="petugas"] section:hover {
        transform: translateX(8px) scale(1.01) !important;
        box-shadow: 
            0 8px 24px rgba(100, 126, 234, 0.12),
            0 16px 48px rgba(100, 126, 234, 0.08) !important;
    }
    
    /* WORLD-CLASS BUTTONS - MATCHING PATIENT TABLE */
    [data-filament-panel-id="petugas"] .fi-btn,
    [data-filament-panel-id="petugas"] button[type="submit"],
    [data-filament-panel-id="petugas"] button[type="button"] {
        padding: 0.75rem 2rem !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        letter-spacing: 0.025em !important;
        transition: all 0.3s ease !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-btn-color-primary,
    [data-filament-panel-id="petugas"] button[type="submit"] {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border: none !important;
    }
    
    [data-filament-panel-id="petugas"] .fi-btn-color-primary::before,
    [data-filament-panel-id="petugas"] button[type="submit"]::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255, 255, 255, 0.3), 
            transparent);
        transition: left 0.5s ease;
    }
    
    [data-filament-panel-id="petugas"] .fi-btn-color-primary:hover::before,
    [data-filament-panel-id="petugas"] button[type="submit"]:hover::before {
        left: 100%;
    }
    
    [data-filament-panel-id="petugas"] .fi-btn-color-primary:hover,
    [data-filament-panel-id="petugas"] button[type="submit"]:hover {
        transform: translateY(-3px) scale(1.05) !important;
        box-shadow: 
            0 8px 24px rgba(102, 126, 234, 0.3),
            0 12px 36px rgba(118, 75, 162, 0.2) !important;
    }
    
    /* 3D DEPTH EFFECTS FOR WIDGETS */
    [data-filament-panel-id="petugas"] .fi-widget {
        transform-style: preserve-3d;
        transition: transform 0.3s ease;
    }
    
    [data-filament-panel-id="petugas"] .fi-widget:hover {
        transform: rotateY(5deg) rotateX(-5deg) translateZ(10px);
    }
    
    /* EMOTIONAL LOADING STATES */
    @keyframes emotionalPulse {
        0%, 100% { 
            transform: scale(1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        25% { 
            transform: scale(1.05);
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        50% { 
            transform: scale(1.1);
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        75% { 
            transform: scale(1.05);
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
    }
    
    .loading-emotional {
        animation: emotionalPulse 2s ease infinite;
    }
    
    /* LIGHTNING EFFECTS FOR DARK MODE */
    @media (prefers-color-scheme: dark) {
        [data-filament-panel-id="petugas"] .fi-page {
            background: #0a0a0a;
            position: relative;
        }
        
        [data-filament-panel-id="petugas"] .fi-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(100, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(245, 87, 108, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(0, 242, 254, 0.15) 0%, transparent 50%);
            pointer-events: none;
            animation: lightningShift 10s ease infinite;
        }
        
        @keyframes lightningShift {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
        
        [data-filament-panel-id="petugas"] .fi-section,
        [data-filament-panel-id="petugas"] .fi-card {
            background: rgba(20, 20, 20, 0.7) !important;
            border: 1px solid rgba(100, 126, 234, 0.2) !important;
        }
    }
    
    /* FLOATING ACTION BUTTONS */
    .fab-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }
    
    .fab {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        box-shadow: 
            0 10px 30px rgba(100, 126, 234, 0.3),
            0 0 0 0 rgba(100, 126, 234, 0.4);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .fab:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 
            0 15px 40px rgba(100, 126, 234, 0.4),
            0 0 0 10px rgba(100, 126, 234, 0.1);
    }
    
    .fab:active {
        transform: scale(0.95);
    }
    
    /* PERFORMANCE OPTIMIZATIONS */
    [data-filament-panel-id="petugas"] * {
        will-change: auto;
    }
    
    [data-filament-panel-id="petugas"] .animate {
        will-change: transform, opacity;
    }
    
    [data-filament-panel-id="petugas"] .contain {
        contain: layout style paint;
    }
</style>

<script>
// Force black glassmorphism sidebar immediately
(function() {
    let appliedCount = 0;
    
    const forceBlackSidebar = () => {
        // Try multiple selectors
        const selectors = [
            '[data-filament-panel-id="petugas"] .fi-sidebar',
            '.fi-sidebar',
            'aside.fi-sidebar',
            '[class*="fi-sidebar"]'
        ];
        
        let sidebar = null;
        for (const selector of selectors) {
            sidebar = document.querySelector(selector);
            if (sidebar) {
                console.log('[World-Class UI] Found sidebar with selector:', selector);
                break;
            }
        }
        
        if (sidebar) {
            appliedCount++;
            console.log('[World-Class UI] Applying black glassmorphism to sidebar (attempt #' + appliedCount + ')');
            // Apply the elegant black glassmorphism directly
            sidebar.style.cssText = `
                background: linear-gradient(135deg, 
                    rgba(0, 0, 0, 0.95) 0%, 
                    rgba(10, 10, 10, 0.9) 50%, 
                    rgba(20, 20, 20, 0.85) 100%) !important;
                backdrop-filter: blur(30px) saturate(200%) !important;
                -webkit-backdrop-filter: blur(30px) saturate(200%) !important;
                border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
                box-shadow: 
                    inset 0 0 30px rgba(100, 126, 234, 0.05),
                    4px 0 24px rgba(0, 0, 0, 0.5) !important;
            `;
            
            // Also force the nav items to be visible on black
            document.querySelectorAll('[data-filament-panel-id="petugas"] .fi-sidebar-item').forEach(item => {
                const button = item.querySelector('button, a');
                if (button) {
                    button.style.color = 'rgba(255, 255, 255, 0.9)';
                }
            });
            
            // Make active items stand out
            document.querySelectorAll('[data-filament-panel-id="petugas"] .fi-sidebar-item-active button').forEach(button => {
                button.style.cssText = `
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    color: white !important;
                    box-shadow: 
                        0 0 20px rgba(100, 126, 234, 0.4),
                        inset 0 0 20px rgba(255, 255, 255, 0.1) !important;
                `;
            });
            
            // Add a visual indicator that styles were applied
            if (!sidebar.querySelector('.world-class-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'world-class-indicator';
                indicator.style.cssText = `
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 5px 10px;
                    border-radius: 20px;
                    font-size: 10px;
                    font-weight: bold;
                    z-index: 9999;
                `;
                indicator.textContent = 'World-Class UI Active';
                sidebar.appendChild(indicator);
            }
        } else {
            console.log('[World-Class UI] Sidebar not found yet');
        }
    };
    
    // Apply immediately
    forceBlackSidebar();
    console.log('[World-Class UI] Initial force applied');
    
    // Apply on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            forceBlackSidebar();
            console.log('[World-Class UI] DOM ready force applied');
        });
    }
    
    // Apply after a small delay to override any late-loading styles
    setTimeout(forceBlackSidebar, 100);
    setTimeout(forceBlackSidebar, 500);
    setTimeout(forceBlackSidebar, 1000);
    
    // Watch for dynamic changes
    if (document.body) {
        const observer = new MutationObserver(forceBlackSidebar);
        observer.observe(document.body, { childList: true, subtree: true });
    } else {
        // If body doesn't exist yet, wait for it
        const waitForBody = setInterval(() => {
            if (document.body) {
                clearInterval(waitForBody);
                const observer = new MutationObserver(forceBlackSidebar);
                observer.observe(document.body, { childList: true, subtree: true });
            }
        }, 10);
    }
    
    // Also watch for Livewire navigation
    document.addEventListener('livewire:navigated', forceBlackSidebar);
    document.addEventListener('livewire:load', forceBlackSidebar);
    window.addEventListener('load', forceBlackSidebar);
    
    // Force on Filament events
    document.addEventListener('filament:mounted', forceBlackSidebar);
    document.addEventListener('filament:loaded', forceBlackSidebar);
})();

document.addEventListener('DOMContentLoaded', function() {
    // ADVANCED MICRO-INTERACTIONS
    
    // Magnetic buttons
    document.querySelectorAll('.fi-btn').forEach(button => {
        button.addEventListener('mousemove', (e) => {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            button.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px) scale(1.05)`;
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = 'translate(0, 0) scale(1)';
        });
    });
    
    // AI-powered suggestions with typing effect
    function typeWriter(element, text, speed = 50) {
        let i = 0;
        element.innerHTML = '';
        
        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        }
        
        type();
    }
    
    // Parallax scrolling effect
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallax = document.querySelector('.fi-page');
        if (parallax) {
            parallax.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });
    
    // Smooth reveal animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.fi-section, .fi-card').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
    
    // 3D tilt effect for cards
    document.querySelectorAll('.fi-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        });
    });
    
    // Haptic feedback simulation (visual)
    document.querySelectorAll('button, .fi-btn').forEach(button => {
        button.addEventListener('click', () => {
            button.style.animation = 'hapticFeedback 0.2s ease';
            setTimeout(() => {
                button.style.animation = '';
            }, 200);
        });
    });
    
    // AI gradient animation for special elements
    const aiElements = document.querySelectorAll('.ai-powered');
    aiElements.forEach(element => {
        element.addEventListener('mouseenter', () => {
            element.style.animationDuration = '1s';
        });
        
        element.addEventListener('mouseleave', () => {
            element.style.animationDuration = '3s';
        });
    });
    
    // Performance monitoring
    if ('PerformanceObserver' in window) {
        const perfObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                // Log slow interactions for optimization
                if (entry.duration > 100) {
                    console.log('Slow interaction detected:', entry.name, entry.duration);
                }
            }
        });
        
        perfObserver.observe({ entryTypes: ['measure'] });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Cmd/Ctrl + K for quick search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.style.animation = 'glow 1s ease';
            }
        }
        
        // Cmd/Ctrl + N for new entry
        if ((e.metaKey || e.ctrlKey) && e.key === 'n') {
            e.preventDefault();
            const createButton = document.querySelector('[wire\\:click*="create"]');
            if (createButton) {
                createButton.click();
            }
        }
    });
});

// CSS for animations - check if not already added
if (!document.getElementById('world-class-animation-styles')) {
    const animationStyles = document.createElement('style');
    animationStyles.id = 'world-class-animation-styles';
    animationStyles.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes hapticFeedback {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(0.95); }
    }
    
    @keyframes glow {
        0%, 100% { box-shadow: 0 0 5px rgba(100, 126, 234, 0.5); }
        50% { box-shadow: 0 0 20px rgba(100, 126, 234, 0.8); }
    }
`;
    document.head.appendChild(animationStyles);
}
</script>

{{-- Floating Action Button --}}
<div class="fab-container">
    <button class="fab" onclick="alert('AI Assistant Ready! 🚀')">
        ✨
    </button>
</div>