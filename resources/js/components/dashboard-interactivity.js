/**
 * World-Class Dashboard Interactivity
 * Healthcare Dashboard Enhancement with Micro-interactions
 * 
 * Features:
 * - KPI card hover tooltips and animations
 * - Counter animations for number changes
 * - Chart filter interactions
 * - Activity feed real-time updates
 * - Performance optimization with debouncing
 */

class WorldClassDashboard {
    constructor() {
        this.initialized = false;
        this.animationObserver = null;
        this.tooltipElement = null;
        this.chartFilters = new Map();
        this.counterAnimations = new Map();
        
        // Configuration
        this.config = {
            animation: {
                duration: 600,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                counterSpeed: 2000, // 2 seconds for counter animation
                stagger: 100 // Stagger delay between elements
            },
            tooltip: {
                delay: 300,
                offset: 10,
                maxWidth: 280
            },
            performance: {
                debounceDelay: 150,
                throttleDelay: 16 // 60fps
            }
        };

        this.init();
    }

    init() {
        if (this.initialized) return;
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        try {
            this.createTooltipElement();
            this.initializeKPICards();
            this.initializeCounterAnimations();
            this.initializeChartFilters();
            this.initializeActivityFeeds();
            this.initializePerformanceObserver();
            this.setupAccessibility();
            
            this.initialized = true;
            console.log('🚀 World-Class Dashboard initialized successfully');
        } catch (error) {
            console.error('❌ Dashboard initialization failed:', error);
        }
    }

    /**
     * KPI Cards Enhancement with Tooltips and Hover Effects
     */
    initializeKPICards() {
        const kpiCards = document.querySelectorAll('.world-class-kpi-card');
        
        kpiCards.forEach((card, index) => {
            // Add entrance animation with stagger
            this.addEntranceAnimation(card, index);
            
            // Enhanced hover interactions
            this.addHoverInteractions(card);
            
            // Tooltip functionality
            this.addTooltipInteraction(card);
            
            // Click interactions for drill-down
            this.addClickInteraction(card);
        });
    }

    addEntranceAnimation(card, index) {
        // Initial state
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px) scale(0.95)';
        
        // Animate in with stagger
        setTimeout(() => {
            card.style.transition = `opacity ${this.config.animation.duration}ms ${this.config.animation.easing}, transform ${this.config.animation.duration}ms ${this.config.animation.easing}`;
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
        }, index * this.config.animation.stagger);
    }

    addHoverInteractions(card) {
        let isHovered = false;
        
        card.addEventListener('mouseenter', this.debounce(() => {
            if (!isHovered) {
                isHovered = true;
                this.enhanceCardHover(card, true);
            }
        }, 50));

        card.addEventListener('mouseleave', this.debounce(() => {
            if (isHovered) {
                isHovered = false;
                this.enhanceCardHover(card, false);
            }
        }, 50));
    }

    enhanceCardHover(card, isHovering) {
        const icon = card.querySelector('.premium-stat-icon');
        const value = card.querySelector('.world-class-kpi-value');
        const trendIndicator = card.querySelector('.world-class-trend-indicator');

        if (isHovering) {
            // Enhanced hover effects
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(3deg)';
                icon.style.boxShadow = '0 12px 40px rgba(59, 130, 246, 0.4)';
            }
            
            if (value) {
                value.style.transform = 'scale(1.05)';
                value.style.textShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
            }
            
            if (trendIndicator) {
                trendIndicator.style.transform = 'scale(1.05)';
            }
        } else {
            // Reset hover effects
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
                icon.style.boxShadow = '';
            }
            
            if (value) {
                value.style.transform = 'scale(1)';
                value.style.textShadow = '';
            }
            
            if (trendIndicator) {
                trendIndicator.style.transform = 'scale(1)';
            }
        }
    }

    addTooltipInteraction(card) {
        let tooltipTimeout;

        card.addEventListener('mouseenter', (e) => {
            tooltipTimeout = setTimeout(() => {
                this.showTooltip(card, e);
            }, this.config.tooltip.delay);
        });

        card.addEventListener('mouseleave', () => {
            clearTimeout(tooltipTimeout);
            this.hideTooltip();
        });

        card.addEventListener('mousemove', this.throttle((e) => {
            if (this.tooltipElement && this.tooltipElement.style.opacity === '1') {
                this.updateTooltipPosition(e);
            }
        }, this.config.performance.throttleDelay));
    }

    /**
     * Counter Animations for KPI Values
     */
    initializeCounterAnimations() {
        const counterElements = document.querySelectorAll('[data-animate="counter"]');
        
        // Create intersection observer for counter animations
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.dataset.animated) {
                    this.animateCounter(entry.target);
                    entry.target.dataset.animated = 'true';
                }
            });
        }, { threshold: 0.5 });

        counterElements.forEach(el => counterObserver.observe(el));
    }

    animateCounter(element) {
        const text = element.textContent.trim();
        const number = this.extractNumber(text);
        const prefix = text.replace(/[\d,.-]/g, '').trim();
        const suffix = text.match(/[a-zA-Z%]+$/)?.[0] || '';
        
        if (number === null) return;

        let startValue = 0;
        const duration = this.config.animation.counterSpeed;
        const startTime = performance.now();
        
        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easedProgress = this.easeOutExpo(progress);
            const currentValue = Math.floor(startValue + (number - startValue) * easedProgress);
            
            element.textContent = `${prefix}${this.formatNumber(currentValue)}${suffix}`.trim();
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                // Ensure final value is exact
                element.textContent = text;
            }
        };
        
        requestAnimationFrame(updateCounter);
    }

    /**
     * Chart Filter Interactions
     */
    initializeChartFilters() {
        const filterButtons = document.querySelectorAll('.world-class-charts-grid button');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleChartFilter(button);
            });
        });
    }

    handleChartFilter(clickedButton) {
        const container = clickedButton.closest('.world-class-chart-container');
        const filterButtons = container.querySelectorAll('button');
        
        // Update active state
        filterButtons.forEach(btn => {
            btn.classList.remove('bg-blue-100', 'text-blue-700');
            btn.classList.add('text-gray-500', 'hover:bg-gray-100');
        });
        
        clickedButton.classList.add('bg-blue-100', 'text-blue-700');
        clickedButton.classList.remove('text-gray-500', 'hover:bg-gray-100');
        
        // Add ripple effect
        this.addRippleEffect(clickedButton);
        
        // Simulate chart update (placeholder)
        this.simulateChartUpdate(container);
    }

    simulateChartUpdate(container) {
        const chartPlaceholder = container.querySelector('.h-64');
        if (!chartPlaceholder) return;
        
        // Add loading animation
        chartPlaceholder.style.opacity = '0.5';
        chartPlaceholder.style.transform = 'scale(0.98)';
        
        setTimeout(() => {
            chartPlaceholder.style.opacity = '1';
            chartPlaceholder.style.transform = 'scale(1)';
        }, 300);
    }

    /**
     * Activity Feed Real-time Updates
     */
    initializeActivityFeeds() {
        const activityContainer = document.querySelector('.world-class-chart-container:last-child .space-y-4');
        if (!activityContainer) return;
        
        // Simulate real-time updates every 30 seconds
        setInterval(() => {
            this.addNewActivity(activityContainer);
        }, 30000);
    }

    addNewActivity(container) {
        const activities = [
            { type: 'info', color: 'blue', title: 'Data backup completed', time: 'now' },
            { type: 'success', color: 'green', title: 'Payment processed', time: 'now' },
            { type: 'warning', color: 'amber', title: 'System maintenance scheduled', time: 'now' }
        ];
        
        const newActivity = activities[Math.floor(Math.random() * activities.length)];
        const activityElement = this.createActivityElement(newActivity);
        
        // Add with animation
        activityElement.style.opacity = '0';
        activityElement.style.transform = 'translateX(-20px)';
        container.insertBefore(activityElement, container.firstChild);
        
        // Remove oldest activity if more than 3
        const allActivities = container.children;
        if (allActivities.length > 3) {
            const oldest = allActivities[allActivities.length - 1];
            oldest.style.opacity = '0';
            oldest.style.transform = 'translateX(20px)';
            setTimeout(() => oldest.remove(), 300);
        }
        
        // Animate in new activity
        requestAnimationFrame(() => {
            activityElement.style.transition = 'opacity 300ms ease, transform 300ms ease';
            activityElement.style.opacity = '1';
            activityElement.style.transform = 'translateX(0)';
        });
    }

    /**
     * Tooltip Management
     */
    createTooltipElement() {
        this.tooltipElement = document.createElement('div');
        this.tooltipElement.className = 'dashboard-tooltip';
        this.tooltipElement.style.cssText = `
            position: fixed;
            z-index: 10000;
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(12px);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            max-width: ${this.config.tooltip.maxWidth}px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 200ms ease, transform 200ms ease;
            pointer-events: none;
        `;
        document.body.appendChild(this.tooltipElement);
    }

    showTooltip(card, event) {
        const title = card.querySelector('.world-class-kpi-title')?.textContent || 'KPI';
        const value = card.querySelector('.world-class-kpi-value')?.textContent || '';
        const description = card.querySelector('.world-class-kpi-description')?.textContent || '';
        
        this.tooltipElement.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 4px;">${title}</div>
            <div style="font-size: 18px; font-weight: 700; margin-bottom: 6px; color: #60a5fa;">${value}</div>
            <div style="font-size: 12px; opacity: 0.8;">${description}</div>
        `;
        
        this.updateTooltipPosition(event);
        
        // Show with animation
        requestAnimationFrame(() => {
            this.tooltipElement.style.opacity = '1';
            this.tooltipElement.style.transform = 'translateY(0)';
        });
    }

    hideTooltip() {
        if (this.tooltipElement) {
            this.tooltipElement.style.opacity = '0';
            this.tooltipElement.style.transform = 'translateY(10px)';
        }
    }

    updateTooltipPosition(event) {
        if (!this.tooltipElement) return;
        
        const { clientX: x, clientY: y } = event;
        const { innerWidth: windowWidth, innerHeight: windowHeight } = window;
        const tooltipRect = this.tooltipElement.getBoundingClientRect();
        
        let left = x + this.config.tooltip.offset;
        let top = y - tooltipRect.height - this.config.tooltip.offset;
        
        // Adjust if tooltip goes off-screen
        if (left + tooltipRect.width > windowWidth) {
            left = x - tooltipRect.width - this.config.tooltip.offset;
        }
        
        if (top < 0) {
            top = y + this.config.tooltip.offset;
        }
        
        this.tooltipElement.style.left = `${left}px`;
        this.tooltipElement.style.top = `${top}px`;
    }

    /**
     * Performance and Accessibility
     */
    initializePerformanceObserver() {
        if ('IntersectionObserver' in window) {
            this.animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            // Observe all animated elements
            document.querySelectorAll('.world-class-kpi-card, .world-class-chart-container').forEach(el => {
                this.animationObserver.observe(el);
            });
        }
    }

    setupAccessibility() {
        // Enhanced keyboard navigation
        document.querySelectorAll('.world-class-kpi-card').forEach(card => {
            if (!card.hasAttribute('tabindex')) {
                card.setAttribute('tabindex', '0');
            }
            
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
        });
        
        // Screen reader announcements
        this.setupAriaLive();
    }

    setupAriaLive() {
        const ariaLive = document.createElement('div');
        ariaLive.setAttribute('aria-live', 'polite');
        ariaLive.setAttribute('aria-atomic', 'true');
        ariaLive.className = 'sr-only';
        ariaLive.id = 'dashboard-announcements';
        document.body.appendChild(ariaLive);
        
        this.ariaLiveRegion = ariaLive;
    }

    announceToScreenReader(message) {
        if (this.ariaLiveRegion) {
            this.ariaLiveRegion.textContent = message;
        }
    }

    /**
     * Utility Functions
     */
    addRippleEffect(element) {
        const ripple = document.createElement('div');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.3);
            transform: scale(0);
            animation: ripple 600ms ease-out;
            pointer-events: none;
            width: ${size}px;
            height: ${size}px;
            left: 50%;
            top: 50%;
            margin-left: -${size/2}px;
            margin-top: -${size/2}px;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }

    createActivityElement(activity) {
        const div = document.createElement('div');
        div.className = `flex items-center space-x-4 p-3 bg-${activity.color}-50 rounded-xl border border-${activity.color}-200`;
        div.innerHTML = `
            <div class="w-2 h-2 bg-${activity.color}-500 rounded-full"></div>
            <div class="flex-1">
                <p class="text-sm font-medium text-${activity.color}-700">${activity.title}</p>
                <p class="text-xs text-${activity.color}-600">${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} - Just now</p>
            </div>
        `;
        return div;
    }

    extractNumber(text) {
        const match = text.replace(/[^\d,.-]/g, '').replace(/,/g, '');
        const number = parseFloat(match);
        return isNaN(number) ? null : number;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    easeOutExpo(t) {
        return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    addClickInteraction(card) {
        card.addEventListener('click', () => {
            this.addRippleEffect(card);
            
            // Simulate drill-down or detailed view
            const title = card.querySelector('.world-class-kpi-title')?.textContent || 'KPI';
            this.announceToScreenReader(`Opening detailed view for ${title}`);
            
            // Add visual feedback
            card.style.transform = 'scale(0.98)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, 150);
        });
    }

    /**
     * Public API for external integrations
     */
    refreshData(newData) {
        if (newData && typeof newData === 'object') {
            this.updateKPIValues(newData);
            this.announceToScreenReader('Dashboard data refreshed');
        }
    }

    updateKPIValues(data) {
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[data-kpi="${key}"] .world-class-kpi-value`);
            if (element) {
                this.animateCounter(element);
            }
        });
    }

    destroy() {
        if (this.animationObserver) {
            this.animationObserver.disconnect();
        }
        
        if (this.tooltipElement) {
            this.tooltipElement.remove();
        }
        
        this.counterAnimations.clear();
        this.chartFilters.clear();
        this.initialized = false;
    }
}

// CSS for animations
const animationCSS = `
@keyframes ripple {
    to {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-in {
    animation: slideInUp 0.6s ease-out forwards;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .world-class-kpi-card,
    .world-class-chart-container,
    .dashboard-tooltip {
        transition: none;
        animation: none;
    }
}
`;

// Inject CSS
const styleSheet = document.createElement('style');
styleSheet.textContent = animationCSS;
document.head.appendChild(styleSheet);

// Auto-initialize when script loads
document.addEventListener('DOMContentLoaded', () => {
    window.worldClassDashboard = new WorldClassDashboard();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WorldClassDashboard;
}