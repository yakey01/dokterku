{{-- MINIMALIST ELEGANT TOP BAR WELCOME - SINGLE LINE --}}
<div class="minimalist-topbar-welcome">
    <div class="compact-welcome-badge">
        <svg class="welcome-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
        </svg>
        <span class="compact-greeting">Selamat pagi, {{ auth()->user()->name ?? 'Fitri Tri' }}! Kelola keuangan dengan mudah dan efisien</span>
        <div class="status-dot"></div>
    </div>
</div>

<style>
/* MINIMALIST ELEGANT TOP BAR - SINGLE LINE DESIGN */

.minimalist-topbar-welcome {
    position: fixed;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    padding-top: 0.375rem;
    pointer-events: none;
    z-index: 9999;
}

.compact-welcome-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem; /* Increased spacing for larger text */
    padding: 0.5rem 1.25rem; /* Generous padding for world-class feel */
    pointer-events: auto;
    
    /* MINIMALIST GLASS DESIGN */
    background: linear-gradient(135deg, 
        rgba(245, 158, 11, 0.85) 0%,
        rgba(217, 119, 6, 0.8) 100%);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    
    /* CLEAN BORDERS & SHADOWS */
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 1.25rem;
    box-shadow: 
        0 2px 12px rgba(245, 158, 11, 0.15),
        0 1px 4px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
    
    /* SMOOTH TRANSITIONS */
    transition: all 0.25s ease;
    animation: minimalistEntry 0.4s ease-out forwards;
}

/* COMPACT HOVER EFFECTS */
.compact-welcome-badge:hover {
    transform: translateY(-1px);
    box-shadow: 
        0 4px 16px rgba(245, 158, 11, 0.2),
        0 2px 6px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    background: linear-gradient(135deg, 
        rgba(245, 158, 11, 0.9) 0%,
        rgba(217, 119, 6, 0.85) 100%);
}

/* WORLD-CLASS WELCOME ICON */
.welcome-icon {
    width: 1.25rem; /* 20px - Proportional to larger text */
    height: 1.25rem;
    color: #ffffff;
    
    /* Premium Icon Enhancement */
    filter: 
        drop-shadow(0 1px 3px rgba(0, 0, 0, 0.3)) /* Enhanced depth */
        drop-shadow(0 1px 1px rgba(0, 0, 0, 0.2));
    
    flex-shrink: 0;
    
    /* Smooth Icon Transitions */
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* WORLD-CLASS GREETING TYPOGRAPHY */
.compact-greeting {
    /* Premium Typography Scale */
    font-size: 1rem; /* 16px - World-class readable size */
    font-weight: 600;
    
    /* Premium Letter Spacing */
    letter-spacing: -0.02em; /* Tighter for premium feel */
    
    /* High-Contrast Colors */
    color: #ffffff;
    text-shadow: 
        0 1px 3px rgba(0, 0, 0, 0.4), /* Enhanced depth */
        0 1px 1px rgba(0, 0, 0, 0.2);
    
    /* World-Class Line Height */
    line-height: 1.4; /* Improved readability */
    
    /* Responsive Text Wrapping */
    white-space: normal; /* Allow wrapping for better UX */
    word-wrap: break-word;
    
    /* Premium Font Stack */
    font-family: 
        "SF Pro Display", /* Apple system font */
        -apple-system, 
        BlinkMacSystemFont, 
        "Inter", /* Modern web font */
        "Segoe UI", 
        "Roboto", 
        sans-serif;
    
    /* Enhanced Text Rendering */
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}

/* STATUS DOT */
.status-dot {
    width: 0.375rem;
    height: 0.375rem;
    background: #10b981;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.3);
    flex-shrink: 0;
    animation: statusPulse 2s infinite ease-in-out;
}

/* MINIMALIST ANIMATIONS */
@keyframes minimalistEntry {
    0% {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes statusPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.1);
    }
}

/* WORLD-CLASS RESPONSIVE TYPOGRAPHY */
@media (max-width: 1400px) {
    .compact-greeting {
        font-size: 0.9375rem; /* 15px - Still readable */
    }
    
    .compact-welcome-badge {
        padding: 0.375rem 0.875rem; /* Slightly reduced padding */
        gap: 0.5rem;
    }
    
    .welcome-icon {
        width: 0.9375rem; /* Proportional scaling */
        height: 0.9375rem;
    }
}

@media (max-width: 1200px) {
    .compact-greeting {
        font-size: 0.875rem; /* 14px - Minimum readable size */
        max-width: 280px; /* Generous text space */
        line-height: 1.3; /* Tighter for smaller screens */
    }
}

@media (max-width: 1024px) {
    .compact-greeting {
        font-size: 0.8125rem; /* 13px - Tablet optimized */
        max-width: 240px;
        /* Keep full text visible, no truncation */
    }
}

@media (max-width: 768px) {
    .compact-greeting {
        font-size: 0.75rem; /* 12px - Mobile minimum */
        max-width: 200px;
        line-height: 1.25; /* Compact but readable */
    }
    
    .compact-welcome-badge {
        padding: 0.25rem 0.625rem; /* Mobile-optimized padding */
    }
}

@media (max-width: 640px) {
    .minimalist-topbar-welcome {
        display: none; /* Clean mobile experience when too small */
    }
}

/* DARK MODE COMPATIBILITY */
.dark .compact-welcome-badge {
    background: linear-gradient(135deg, 
        rgba(245, 158, 11, 0.8) 0%,
        rgba(217, 119, 6, 0.75) 100%);
    border-color: rgba(255, 255, 255, 0.15);
}

.dark .compact-greeting {
    color: #fef3c7;
}

/* PERFORMANCE OPTIMIZATIONS */
.minimalist-topbar-welcome {
    contain: layout style;
    will-change: transform;
}

.compact-welcome-badge {
    contain: layout style paint;
    transform: translate3d(0, 0, 0); /* GPU acceleration */
}

/* ACCESSIBILITY */
.compact-welcome-badge {
    outline: none;
}

.compact-welcome-badge:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.8);
    outline-offset: 2px;
}

/* SUBTLE MICRO-INTERACTION */
.compact-welcome-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 255, 255, 0.08) 50%, 
        transparent 100%);
    border-radius: inherit;
    opacity: 0;
    transform: translateX(-100%);
    transition: all 0.5s ease;
    pointer-events: none;
}

.compact-welcome-badge:hover::before {
    opacity: 1;
    transform: translateX(100%);
}

/* ENSURE PROPER POSITIONING */
.compact-welcome-badge {
    position: relative;
    overflow: hidden;
}
</style>

{{-- MINIMALIST JAVASCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✨ Minimalist Top Bar Welcome - Initializing...');
    
    // SIMPLE TIME-BASED GREETING UPDATE
    function updateGreeting() {
        const greetingElement = document.querySelector('.compact-greeting');
        if (!greetingElement) return;
        
        const hour = new Date().getHours();
        const userName = '{{ auth()->user()->name ?? "Fitri Tri" }}';
        
        let timeGreeting = '';
        if (hour < 11) timeGreeting = 'Selamat pagi';
        else if (hour < 15) timeGreeting = 'Selamat siang';
        else if (hour < 18) timeGreeting = 'Selamat sore';
        else timeGreeting = 'Selamat malam';
        
        greetingElement.textContent = `${timeGreeting}, ${userName}! Kelola keuangan dengan mudah dan efisien`;
    }
    
    // UPDATE GREETING
    updateGreeting();
    setInterval(updateGreeting, 30 * 60 * 1000); // Every 30 minutes
    
    console.log('✅ Minimalist Welcome Message - Ready');
});
</script>