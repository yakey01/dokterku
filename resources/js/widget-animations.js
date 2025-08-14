/**
 * Advanced Widget Animation System
 * World-class micro-interactions for Filament widgets
 */

class WidgetAnimationController {
    constructor() {
        this.init();
        this.setupObservers();
        this.setupEventListeners();
    }

    init() {
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.start());
        } else {
            this.start();
        }
    }

    start() {
        this.animateCounters();
        this.setupProgressCircles();
        this.setupHoverEffects();
        this.setupTooltips();
        this.setupLoadingStates();
    }

    setupObservers() {
        // Intersection Observer for scroll-triggered animations
        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.triggerEntryAnimation(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        // Observe all widget cards
        document.querySelectorAll('.fi-wi-stats-overview-card').forEach(card => {
            this.intersectionObserver.observe(card);
        });
    }

    setupEventListeners() {
        // Listen for Livewire updates
        document.addEventListener('livewire:update', () => {
            setTimeout(() => this.start(), 100);
        });

        // Listen for Filament panel updates
        document.addEventListener('filament-panel-update', () => {
            setTimeout(() => this.start(), 100);
        });
    }

    animateCounters() {
        const counters = document.querySelectorAll('.animate-counter');
        
        counters.forEach(counter => {
            if (counter.dataset.animated) return;
            
            const target = this.extractNumber(counter.textContent);
            if (target === null) return;

            counter.dataset.animated = 'true';
            this.animateCounterValue(counter, 0, target, 1000);
        });
    }

    extractNumber(text) {
        const match = text.match(/[\d,]+/);
        return match ? parseInt(match[0].replace(/,/g, '')) : null;
    }

    animateCounterValue(element, start, end, duration) {
        const startTime = performance.now();
        const originalText = element.textContent;
        const prefix = originalText.replace(/[\d,]+/, '');
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (end - start) * easeOut);
            
            // Format number with thousands separator
            const formattedNumber = current.toLocaleString('id-ID');
            element.textContent = originalText.replace(/[\d,]+/, formattedNumber);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    setupProgressCircles() {
        const circles = document.querySelectorAll('.progress-circle');
        
        circles.forEach(circle => {
            const percentage = circle.dataset.percentage || 0;
            const progressFill = circle.querySelector('.progress-fill');
            
            if (progressFill) {
                setTimeout(() => {
                    const circumference = 2 * Math.PI * 16; // radius = 16
                    const offset = circumference - (percentage / 100) * circumference;
                    progressFill.style.strokeDashoffset = offset;
                    circle.classList.add('animate');
                }, 500);
            }
        });
    }

    setupHoverEffects() {
        // Enhanced hover effects for interactive elements
        const interactiveElements = document.querySelectorAll('.quick-action-card, .notification-card, .fi-wi-stats-overview-card');
        
        interactiveElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.addHoverGlow(e.target);
                this.animateIcons(e.target);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.removeHoverGlow(e.target);
            });
        });
    }

    addHoverGlow(element) {
        const glowElement = document.createElement('div');
        glowElement.className = 'absolute inset-0 bg-gradient-to-r from-primary-500/10 to-transparent rounded-2xl pointer-events-none transition-opacity duration-300';
        glowElement.style.opacity = '0';
        
        element.style.position = 'relative';
        element.appendChild(glowElement);
        
        requestAnimationFrame(() => {
            glowElement.style.opacity = '1';
        });
    }

    removeHoverGlow(element) {
        const glowElement = element.querySelector('.absolute.inset-0.bg-gradient-to-r');
        if (glowElement) {
            glowElement.style.opacity = '0';
            setTimeout(() => {
                try {
                    if (glowElement && glowElement.parentNode && document.contains(glowElement)) {
                        glowElement.parentNode.removeChild(glowElement);
                    }
                } catch (error) {
                    if (error.name !== 'NotFoundError') {
                        console.warn('⚠️ Glow element removal failed:', error.message);
                    }
                }
            }, 300);
        }
    }

    animateIcons(element) {
        const icons = element.querySelectorAll('svg, .bounce-icon, .rotate-icon');
        icons.forEach((icon, index) => {
            setTimeout(() => {
                if (icon.classList.contains('bounce-icon')) {
                    icon.style.animation = 'smooth-bounce 0.6s ease-in-out';
                } else if (icon.classList.contains('rotate-icon')) {
                    icon.style.animation = 'rotate-scale 0.8s ease-in-out';
                } else {
                    icon.style.transform = 'scale(1.1) rotate(5deg)';
                    icon.style.transition = 'transform 0.3s ease-out';
                }
                
                setTimeout(() => {
                    icon.style.animation = '';
                    icon.style.transform = '';
                }, 600);
            }, index * 100);
        });
    }

    setupTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }

    showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        
        // Safety check for document.body
        if (document.body) {
            document.body.appendChild(tooltip);
        } else {
            // Fallback: append to document.documentElement if body not ready
            document.documentElement.appendChild(tooltip);
        }
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) + 'px';
        tooltip.style.top = rect.top - 40 + 'px';
        
        requestAnimationFrame(() => {
            tooltip.classList.add('show');
        });
        
        element._tooltip = tooltip;
    }

    hideTooltip(element) {
        if (element._tooltip) {
            element._tooltip.classList.remove('show');
            setTimeout(() => {
                try {
                    if (element._tooltip && element._tooltip.parentNode && document.contains(element._tooltip)) {
                        element._tooltip.parentNode.removeChild(element._tooltip);
                    }
                } catch (error) {
                    if (error.name !== 'NotFoundError') {
                        console.warn('⚠️ Tooltip removal failed:', error.message);
                    }
                }
                element._tooltip = null;
            }, 200);
        }
    }

    setupLoadingStates() {
        // Enhanced loading animations
        const loadingElements = document.querySelectorAll('.skeleton-loading');
        
        loadingElements.forEach((element, index) => {
            element.style.animationDelay = (index * 0.1) + 's';
        });
        
        // Loading dots animation
        const loadingDots = document.querySelectorAll('.loading-dots');
        loadingDots.forEach(container => {
            if (container.children.length === 0) {
                for (let i = 0; i < 3; i++) {
                    const dot = document.createElement('div');
                    container.appendChild(dot);
                }
            }
        });
    }

    triggerEntryAnimation(element) {
        if (element.dataset.entryAnimated) return;
        
        element.dataset.entryAnimated = 'true';
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        
        requestAnimationFrame(() => {
            element.style.transition = 'all 0.6s ease-out';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        });
    }

    // Utility methods for external use
    static animateValue(element, targetValue, duration = 1000) {
        const controller = new WidgetAnimationController();
        controller.animateCounterValue(element, 0, targetValue, duration);
    }

    static addLoadingState(element) {
        element.classList.add('skeleton-loading');
        const loadingDots = document.createElement('div');
        loadingDots.className = 'loading-dots';
        for (let i = 0; i < 3; i++) {
            loadingDots.appendChild(document.createElement('div'));
        }
        element.appendChild(loadingDots);
    }

    static removeLoadingState(element) {
        element.classList.remove('skeleton-loading');
        const loadingDots = element.querySelector('.loading-dots');
        if (loadingDots) {
            loadingDots.remove();
        }
    }
}

// Chart animation utilities
class ChartAnimationController {
    static animateChartBars(chartContainer) {
        const bars = chartContainer.querySelectorAll('rect, .chart-bar');
        bars.forEach((bar, index) => {
            bar.style.transform = 'scaleY(0)';
            bar.style.transformOrigin = 'bottom';
            bar.style.transition = `transform ${0.5 + index * 0.1}s ease-out`;
            
            setTimeout(() => {
                bar.style.transform = 'scaleY(1)';
            }, index * 100);
        });
    }

    static animateChartLines(chartContainer) {
        const lines = chartContainer.querySelectorAll('path, .chart-line');
        lines.forEach((line, index) => {
            const length = line.getTotalLength ? line.getTotalLength() : 100;
            line.style.strokeDasharray = length;
            line.style.strokeDashoffset = length;
            line.style.transition = `stroke-dashoffset ${1 + index * 0.2}s ease-out`;
            
            setTimeout(() => {
                line.style.strokeDashoffset = 0;
            }, index * 200);
        });
    }
}

// Initialize the animation system
const widgetAnimations = new WidgetAnimationController();

// Make controllers available globally
window.WidgetAnimationController = WidgetAnimationController;
window.ChartAnimationController = ChartAnimationController;

// Auto-refresh animations for dynamic content
setInterval(() => {
    if (document.hidden) return;
    
    const newCounters = document.querySelectorAll('.animate-counter:not([data-animated])');
    if (newCounters.length > 0) {
        widgetAnimations.animateCounters();
    }
}, 2000);