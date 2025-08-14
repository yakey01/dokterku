/**
 * Petugas Glass Morphism Interactions - World-Class Medical Dashboard
 * Advanced JavaScript interactions for glass morphism cards with performance monitoring
 * WCAG AAA compliant with comprehensive accessibility features
 */

(function() {
    'use strict';
    
    // Configuration and Performance Monitoring
    const CONFIG = {
        ANIMATION_DURATION: 300,
        DEBOUNCE_DELAY: 150,
        INTERSECTION_THRESHOLD: 0.1,
        MAX_PARTICLES: 20,
        PERFORMANCE_BUDGET: 16, // 60fps = 16ms per frame
        ACCESSIBILITY: {
            RESPECT_REDUCED_MOTION: true,
            MIN_CONTRAST_RATIO: 4.5,
            MIN_TOUCH_TARGET: 44
        }
    };
    
    // Performance metrics
    const metrics = {
        animationsActive: 0,
        memoryUsage: 0,
        lastFrameTime: 0,
        frameCount: 0
    };
    
    // Main Glass Morphism Controller
    class PetugasGlassController {
        constructor() {
            this.cards = [];
            this.observers = new Map();
            this.animationFrameId = null;
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.isVisible = !document.hidden;
            
            this.init();
        }
        
        init() {
            this.setupEventListeners();
            this.initializeCards();
            this.startPerformanceMonitoring();
            this.setupAccessibility();
            
            // Announce readiness for screen readers
            this.announceToScreenReader('Dashboard glass morphism effects loaded');
        }
        
        setupEventListeners() {
            // Reduced motion preference changes
            window.matchMedia('(prefers-reduced-motion: reduce)')
                .addEventListener('change', (e) => {
                    this.reducedMotion = e.matches;
                    this.refreshAnimations();
                });
            
            // Visibility changes for performance
            document.addEventListener('visibilitychange', () => {
                this.isVisible = !document.hidden;
                if (this.isVisible) {
                    this.resumeAnimations();
                } else {
                    this.pauseAnimations();
                }
            });
            
            // Window focus/blur for resource management
            window.addEventListener('focus', () => this.resumeAnimations());
            window.addEventListener('blur', () => this.pauseAnimations());
            
            // Resize handling with debounce
            window.addEventListener('resize', this.debounce(() => {
                this.handleResize();
            }, CONFIG.DEBOUNCE_DELAY));
        }
        
        initializeCards() {
            // Initialize metric cards with glass morphism effects
            const metricCards = document.querySelectorAll('.petugas-metric-card');
            metricCards.forEach(card => this.initializeMetricCard(card));
            
            // Initialize action cards
            const actionCards = document.querySelectorAll('.petugas-action-card');
            actionCards.forEach(card => this.initializeActionCard(card));
            
            // Initialize glass cards
            const glassCards = document.querySelectorAll('.petugas-glass-card');
            glassCards.forEach(card => this.initializeGlassCard(card));
            
            // Setup intersection observers for performance
            this.setupIntersectionObservers();
        }
        
        initializeMetricCard(card) {
            const cardData = {
                element: card,
                type: 'metric',
                animations: [],
                isVisible: false,
                hasEnhancedEffects: false
            };
            
            this.cards.push(cardData);
            
            // Enhanced hover effects
            card.addEventListener('mouseenter', (e) => {
                if (this.reducedMotion) return;
                this.startCardHoverAnimation(cardData);
                this.trackInteraction('metric_card_hover', card);
            });
            
            card.addEventListener('mouseleave', (e) => {
                if (this.reducedMotion) return;
                this.endCardHoverAnimation(cardData);
            });
            
            // Click effects with haptic feedback
            card.addEventListener('click', (e) => {
                this.triggerClickEffect(cardData, e);
                this.trackInteraction('metric_card_click', card);
            });
            
            // Keyboard navigation
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.triggerClickEffect(cardData, e);
                }
            });
            
            // Focus management
            card.addEventListener('focus', () => {
                this.enhanceCardFocus(cardData);
            });
            
            card.addEventListener('blur', () => {
                this.removeCardFocus(cardData);
            });
        }
        
        initializeActionCard(card) {
            const cardData = {
                element: card,
                type: 'action',
                animations: [],
                isVisible: false,
                rippleEffect: null
            };
            
            this.cards.push(cardData);
            
            // Ripple effect on click
            card.addEventListener('click', (e) => {
                this.createRippleEffect(cardData, e);
                this.trackInteraction('action_card_click', card);
            });
            
            // Hover glow effect
            card.addEventListener('mouseenter', () => {
                if (!this.reducedMotion) {
                    this.startActionCardGlow(cardData);
                }
            });
            
            card.addEventListener('mouseleave', () => {
                this.endActionCardGlow(cardData);
            });
        }
        
        initializeGlassCard(card) {
            const cardData = {
                element: card,
                type: 'glass',
                animations: [],
                isVisible: false,
                glowEffects: []
            };
            
            this.cards.push(cardData);
            
            // Floating animation for glass cards
            if (!this.reducedMotion) {
                this.startFloatingAnimation(cardData);
            }
            
            // Interactive glow effect
            card.addEventListener('mouseenter', () => {
                if (!this.reducedMotion) {
                    this.enhanceGlassEffect(cardData);
                }
            });
            
            card.addEventListener('mouseleave', () => {
                this.normalizeGlassEffect(cardData);
            });
        }
        
        setupIntersectionObservers() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const cardData = this.cards.find(card => card.element === entry.target);
                    if (cardData) {
                        cardData.isVisible = entry.isIntersecting;
                        
                        if (entry.isIntersecting) {
                            this.activateCardAnimations(cardData);
                        } else {
                            this.deactivateCardAnimations(cardData);
                        }
                    }
                });
            }, {
                threshold: CONFIG.INTERSECTION_THRESHOLD,
                rootMargin: '50px'
            });
            
            this.cards.forEach(cardData => {
                observer.observe(cardData.element);
            });
            
            this.observers.set('intersection', observer);
        }
        
        startCardHoverAnimation(cardData) {
            const card = cardData.element;
            const icon = card.querySelector('.petugas-metric-icon');
            
            if (icon && !this.reducedMotion) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
                icon.style.transition = `transform ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.34, 1.56, 0.64, 1)`;
                
                // Add pulsing glow effect
                this.startPulsingGlow(icon);
            }
            
            // Enhance glass effect
            card.style.transform = 'translateY(-8px) scale(1.02)';
            card.style.transition = `all ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.23, 1, 0.32, 1)`;
            
            metrics.animationsActive++;
        }
        
        endCardHoverAnimation(cardData) {
            const card = cardData.element;
            const icon = card.querySelector('.petugas-metric-icon');
            
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
                this.stopPulsingGlow(icon);
            }
            
            card.style.transform = 'translateY(0) scale(1)';
            metrics.animationsActive--;
        }
        
        triggerClickEffect(cardData, event) {
            const card = cardData.element;
            
            // Scale effect
            card.style.transform = 'scale(0.98)';
            card.style.transition = `transform 150ms cubic-bezier(0.4, 0, 0.6, 1)`;
            
            // Create ripple effect
            this.createRippleEffect(cardData, event);
            
            // Haptic feedback if supported
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
            
            // Reset after animation
            setTimeout(() => {
                card.style.transform = 'translateY(0) scale(1)';
                card.style.transition = `all ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.23, 1, 0.32, 1)`;
            }, 150);
        }
        
        createRippleEffect(cardData, event) {
            const card = cardData.element;
            const rect = card.getBoundingClientRect();
            const ripple = document.createElement('div');
            
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(59, 130, 246, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 600ms ease-out;
                pointer-events: none;
                z-index: 1000;
            `;
            
            // Add ripple animation keyframes if not exists
            if (!document.querySelector('#ripple-keyframes')) {
                const style = document.createElement('style');
                style.id = 'ripple-keyframes';
                style.textContent = `
                    @keyframes ripple {
                        to {
                            transform: scale(2);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
            
            card.style.position = 'relative';
            card.style.overflow = 'hidden';
            card.appendChild(ripple);
            
            setTimeout(() => {
                // Enhanced safety check for DOM element removal
                try {
                    if (ripple && ripple.parentNode && document.contains(ripple)) {
                        ripple.parentNode.removeChild(ripple);
                    }
                } catch (error) {
                    // Silent catch for NotFoundError race conditions
                    if (error.name !== 'NotFoundError') {
                        console.warn('⚠️ Ripple removal failed:', error.message);
                    }
                }
            }, 600);
        }
        
        startPulsingGlow(element) {
            if (this.reducedMotion) return;
            
            element.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
            element.style.animation = 'pulseGlow 2s ease-in-out infinite';
        }
        
        stopPulsingGlow(element) {
            element.style.boxShadow = '';
            element.style.animation = '';
        }
        
        startFloatingAnimation(cardData) {
            if (this.reducedMotion) return;
            
            const card = cardData.element;
            card.style.animation = 'gentleFloat 3s ease-in-out infinite';
        }
        
        enhanceGlassEffect(cardData) {
            const card = cardData.element;
            card.style.backdropFilter = 'blur(25px)';
            card.style.background = 'rgba(255, 255, 255, 0.35)';
        }
        
        normalizeGlassEffect(cardData) {
            const card = cardData.element;
            card.style.backdropFilter = 'blur(20px)';
            card.style.background = 'rgba(255, 255, 255, 0.25)';
        }
        
        enhanceCardFocus(cardData) {
            const card = cardData.element;
            card.style.outline = '3px solid var(--petugas-500)';
            card.style.outlineOffset = '2px';
        }
        
        removeCardFocus(cardData) {
            const card = cardData.element;
            card.style.outline = '';
            card.style.outlineOffset = '';
        }
        
        startActionCardGlow(cardData) {
            const card = cardData.element;
            card.style.transform = 'translateY(-4px) scale(1.02)';
            card.style.boxShadow = '0 15px 30px 0 rgba(59, 130, 246, 0.3)';
        }
        
        endActionCardGlow(cardData) {
            const card = cardData.element;
            card.style.transform = '';
            card.style.boxShadow = '';
        }
        
        setupAccessibility() {
            // Announce important state changes
            const cards = document.querySelectorAll('.petugas-metric-card, .petugas-action-card');
            cards.forEach(card => {
                // Ensure proper ARIA labels
                if (!card.getAttribute('aria-label') && !card.getAttribute('aria-labelledby')) {
                    const label = this.generateAccessibleLabel(card);
                    card.setAttribute('aria-label', label);
                }
                
                // Ensure keyboard focusability
                if (!card.getAttribute('tabindex')) {
                    card.setAttribute('tabindex', '0');
                }
                
                // Add role if missing
                if (!card.getAttribute('role')) {
                    card.setAttribute('role', 'button');
                }
            });
        }
        
        generateAccessibleLabel(card) {
            const label = card.querySelector('.petugas-metric-label');
            const value = card.querySelector('.petugas-metric-value');
            const trend = card.querySelector('.petugas-trend-indicator');
            
            let accessibleText = '';
            
            if (label) accessibleText += label.textContent.trim() + ': ';
            if (value) accessibleText += value.textContent.trim();
            if (trend) accessibleText += ', ' + trend.textContent.trim();
            
            return accessibleText || 'Interactive card';
        }
        
        activateCardAnimations(cardData) {
            if (!cardData.isVisible || this.reducedMotion) return;
            
            const card = cardData.element;
            
            // Fade in animation
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 600ms cubic-bezier(0.23, 1, 0.32, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }
        
        deactivateCardAnimations(cardData) {
            // Pause non-essential animations when out of view
            const card = cardData.element;
            const animations = card.getAnimations?.() || [];
            
            animations.forEach(animation => {
                if (animation.animationName === 'gentleFloat') {
                    animation.pause();
                }
            });
        }
        
        // Performance monitoring and optimization
        startPerformanceMonitoring() {
            const monitor = () => {
                const now = performance.now();
                const deltaTime = now - metrics.lastFrameTime;
                metrics.lastFrameTime = now;
                metrics.frameCount++;
                
                // Check if we're dropping frames
                if (deltaTime > CONFIG.PERFORMANCE_BUDGET * 2) {
                    this.optimizePerformance();
                }
                
                // Memory usage check every 60 frames
                if (metrics.frameCount % 60 === 0) {
                    this.checkMemoryUsage();
                }
                
                if (this.isVisible) {
                    this.animationFrameId = requestAnimationFrame(monitor);
                }
            };
            
            this.animationFrameId = requestAnimationFrame(monitor);
        }
        
        optimizePerformance() {
            console.log('PetugasGlass: Optimizing performance due to frame drops');
            
            // Reduce animation complexity
            if (metrics.animationsActive > 10) {
                this.pauseNonEssentialAnimations();
            }
        }
        
        pauseNonEssentialAnimations() {
            this.cards.forEach(cardData => {
                if (!cardData.isVisible) {
                    this.deactivateCardAnimations(cardData);
                }
            });
        }
        
        resumeAnimations() {
            this.cards.forEach(cardData => {
                if (cardData.isVisible && !this.reducedMotion) {
                    this.activateCardAnimations(cardData);
                }
            });
        }
        
        pauseAnimations() {
            this.cards.forEach(cardData => {
                this.deactivateCardAnimations(cardData);
            });
        }
        
        checkMemoryUsage() {
            if (performance.memory) {
                const usedMB = performance.memory.usedJSHeapSize / 1048576;
                
                if (usedMB > 50) { // 50MB threshold
                    console.warn('PetugasGlass: High memory usage detected:', usedMB.toFixed(2), 'MB');
                    this.cleanup();
                }
            }
        }
        
        cleanup() {
            // Cancel animation frames
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }
            
            // Disconnect observers
            this.observers.forEach(observer => observer.disconnect());
            this.observers.clear();
            
            // Clean up card references
            this.cards.forEach(cardData => {
                cardData.animations = [];
                cardData.glowEffects = [];
            });
        }
        
        refreshAnimations() {
            this.cleanup();
            setTimeout(() => {
                this.initializeCards();
                this.startPerformanceMonitoring();
            }, 100);
        }
        
        handleResize() {
            // Recalculate positions and sizes
            this.cards.forEach(cardData => {
                const card = cardData.element;
                card.style.transform = 'none';
                card.style.transition = 'none';
                
                // Force reflow
                card.offsetHeight;
                
                // Restore transitions
                setTimeout(() => {
                    card.style.transition = `all ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.23, 1, 0.32, 1)`;
                }, 10);
            });
        }
        
        // Utility functions
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
        
        trackInteraction(type, element) {
            // Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'glass_morphism_interaction', {
                    'interaction_type': type,
                    'element_type': element.className,
                    'timestamp': new Date().toISOString()
                });
            }
        }
        
        announceToScreenReader(message) {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.style.cssText = `
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            `;
            announcement.textContent = message;
            
            document.body.appendChild(announcement);
            
            setTimeout(() => {
                try {
                    if (announcement && announcement.parentNode && document.contains(announcement)) {
                        document.body.removeChild(announcement);
                    }
                } catch (error) {
                    if (error.name !== 'NotFoundError') {
                        console.warn('⚠️ Announcement removal failed:', error.message);
                    }
                }
            }, 1000);
        }
        
        // Public API
        setReducedMotion(enabled) {
            this.reducedMotion = enabled;
            this.refreshAnimations();
        }
        
        getPerformanceMetrics() {
            return {
                ...metrics,
                cardsCount: this.cards.length,
                visibleCards: this.cards.filter(card => card.isVisible).length
            };
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.petugasGlass = new PetugasGlassController();
        });
    } else {
        window.petugasGlass = new PetugasGlassController();
    }
    
    // Export for external access
    window.PetugasGlassController = PetugasGlassController;
    
})();