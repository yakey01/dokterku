/**
 * Enhanced DOM Safety Utility
 * Provides comprehensive protection against DOM manipulation errors
 * 
 * Usage:
 * - DOMSafety.safeRemove(element) - Safe element removal
 * - DOMSafety.safeRemoveById(id) - Safe removal by ID
 * - DOMSafety.safeTimeout(fn, delay) - Safe setTimeout with DOM operations
 * - DOMSafety.createRipple(button, event) - Safe ripple effect
 */

class DOMSafety {
    /**
     * Safely remove DOM element with comprehensive validation
     */
    static safeRemove(element) {
        if (!element) {
            console.debug('⚠️ DOMSafety: Element is null or undefined');
            return false;
        }

        try {
            // Triple validation: exists, has parent, and is in document
            if (element.parentNode && document.contains(element)) {
                element.remove();
                console.debug('✅ DOMSafety: Element safely removed');
                return true;
            } else {
                console.debug('🔍 DOMSafety: Element not in DOM, skipping removal');
                return false;
            }
        } catch (error) {
            if (error.name === 'NotFoundError') {
                // Element was already removed - consider this success
                console.debug('✅ DOMSafety: Element already removed (NotFoundError)');
                return true;
            } else {
                console.warn('⚠️ DOMSafety: Unexpected removal error:', error.message);
                return false;
            }
        }
    }

    /**
     * Safely remove element by ID
     */
    static safeRemoveById(elementId) {
        const element = document.getElementById(elementId);
        return this.safeRemove(element);
    }

    /**
     * Safe setTimeout wrapper for DOM operations
     */
    static safeTimeout(callback, delay) {
        return setTimeout(() => {
            try {
                callback();
            } catch (error) {
                if (error.name === 'NotFoundError') {
                    console.debug('✅ DOMSafety: Timeout callback - element already removed');
                } else {
                    console.warn('⚠️ DOMSafety: Timeout callback error:', error.message);
                }
            }
        }, delay);
    }

    /**
     * Create safe ripple effect
     */
    static createRipple(button, event) {
        try {
            const ripple = document.createElement('span');
            ripple.className = 'ripple-effect';
            
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;

            // Ensure button has proper styling
            button.style.position = button.style.position || 'relative';
            button.style.overflow = 'hidden';
            
            button.appendChild(ripple);

            // Safe removal after animation
            this.safeTimeout(() => {
                this.safeRemove(ripple);
            }, 600);

        } catch (error) {
            console.warn('⚠️ DOMSafety: Ripple creation failed:', error.message);
        }
    }

    /**
     * Clean up multiple elements by selector
     */
    static cleanupBySelector(selector) {
        try {
            const elements = document.querySelectorAll(selector);
            let cleaned = 0;
            
            elements.forEach(element => {
                if (this.safeRemove(element)) {
                    cleaned++;
                }
            });
            
            console.debug(`🧹 DOMSafety: Cleaned ${cleaned}/${elements.length} elements matching "${selector}"`);
            return cleaned;
        } catch (error) {
            console.warn('⚠️ DOMSafety: Cleanup error:', error.message);
            return 0;
        }
    }

    /**
     * Global error prevention for removeChild operations
     */
    static patchGlobalRemoveChild() {
        if (window.domSafetyPatched) {
            console.debug('ℹ️ DOMSafety: Already patched');
            return;
        }

        const originalRemoveChild = Node.prototype.removeChild;
        Node.prototype.removeChild = function(child) {
            try {
                // Validate before removal
                if (!child || !this.contains(child)) {
                    console.warn('⚠️ DOMSafety: Attempted to remove non-existent child');
                    return child;
                }
                
                return originalRemoveChild.call(this, child);
            } catch (error) {
                if (error.name === 'NotFoundError') {
                    console.debug('✅ DOMSafety: Child already removed (NotFoundError)');
                    return child;
                } else {
                    console.error('🚨 DOMSafety: RemoveChild failed:', error.message);
                    throw error;
                }
            }
        };

        // Patch the remove method as well
        const originalRemove = Element.prototype.remove;
        Element.prototype.remove = function() {
            try {
                if (!this.parentNode || !document.contains(this)) {
                    console.debug('🔍 DOMSafety: Element not in DOM, skipping remove()');
                    return;
                }
                
                return originalRemove.call(this);
            } catch (error) {
                if (error.name === 'NotFoundError') {
                    console.debug('✅ DOMSafety: Element already removed (NotFoundError)');
                } else {
                    console.error('🚨 DOMSafety: Remove failed:', error.message);
                    throw error;
                }
            }
        };

        window.domSafetyPatched = true;
        console.log('✅ DOMSafety: Global DOM methods patched for safety');
    }

    /**
     * Initialize DOM safety system
     */
    static init() {
        this.patchGlobalRemoveChild();
        
        // Add ripple animation styles if not present
        if (!document.getElementById('dom-safety-ripple-styles')) {
            const style = document.createElement('style');
            style.id = 'dom-safety-ripple-styles';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                .ripple-effect {
                    position: absolute !important;
                    border-radius: 50% !important;
                    pointer-events: none !important;
                    background: rgba(255, 255, 255, 0.6) !important;
                    transform: scale(0) !important;
                    animation: ripple 0.6s ease-out !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        console.log('🛡️ DOMSafety: Initialized with comprehensive protection');
    }
}

// Auto-initialize when loaded
if (typeof window !== 'undefined') {
    // Initialize after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => DOMSafety.init());
    } else {
        DOMSafety.init();
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DOMSafety;
} else if (typeof window !== 'undefined') {
    window.DOMSafety = DOMSafety;
}