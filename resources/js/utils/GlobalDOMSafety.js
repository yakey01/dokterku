/**
 * Global DOM Safety Helper
 * Prevents NotFoundError by providing safe DOM manipulation methods
 */

class GlobalDOMSafety {
    static timeoutRegistry = new Map();
    static intervalRegistry = new Map();

    /**
     * Safe setTimeout with automatic cleanup registration
     */
    static safeSetTimeout(callback, delay, id = null) {
        const timeoutId = setTimeout(() => {
            try {
                callback();
            } catch (error) {
                console.warn('⚠️ Timeout callback failed:', error.message);
            }
            this.timeoutRegistry.delete(timeoutId);
        }, delay);

        this.timeoutRegistry.set(timeoutId, {
            id: id || timeoutId,
            created: new Date(),
            delay
        });

        return timeoutId;
    }

    /**
     * Safe element removal with all validations
     */
    static safeRemoveElement(element) {
        if (!element) return false;

        try {
            // Triple check: exists, has parent, and is in document
            if (element.parentNode && document.contains(element)) {
                element.parentNode.removeChild(element);
                return true;
            }
        } catch (error) {
            if (error.name === 'NotFoundError') {
                // Element was already removed - consider this success
                return true;
            }
            console.warn('⚠️ Element removal failed:', error.message);
        }
        return false;
    }

    /**
     * Cleanup all registered timers (emergency cleanup)
     */
    static emergencyCleanup() {
        console.log('🚨 Performing emergency DOM cleanup...');
        
        // Clear all registered timeouts
        this.timeoutRegistry.forEach((_, timeoutId) => {
            clearTimeout(timeoutId);
        });
        this.timeoutRegistry.clear();

        // Clear all registered intervals
        this.intervalRegistry.forEach((_, intervalId) => {
            clearInterval(intervalId);
        });
        this.intervalRegistry.clear();

        console.log('✅ Emergency cleanup completed');
    }

    /**
     * Patch native removeChild to be safer
     */
    static patchNativeRemoveChild() {
        if (window.domSafetyPatched) return;

        const originalRemoveChild = Node.prototype.removeChild;
        Node.prototype.removeChild = function(child) {
            try {
                // Validate before removal
                if (!child || !this.contains(child)) {
                    console.warn('⚠️ Attempted to remove non-existent child');
                    return child;
                }
                return originalRemoveChild.call(this, child);
            } catch (error) {
                if (error.name === 'NotFoundError') {
                    console.warn('⚠️ NotFoundError caught and handled safely');
                    return child;
                }
                throw error;
            }
        };

        window.domSafetyPatched = true;
        console.log('✅ Native removeChild patched for safety');
    }
}

// Auto-patch on load
if (typeof window !== 'undefined') {
    GlobalDOMSafety.patchNativeRemoveChild();
    
    // Register global cleanup on beforeunload
    window.addEventListener('beforeunload', () => {
        GlobalDOMSafety.emergencyCleanup();
    });

    // Make available globally
    window.GlobalDOMSafety = GlobalDOMSafety;
}

export default GlobalDOMSafety;