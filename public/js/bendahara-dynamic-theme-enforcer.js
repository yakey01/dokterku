/**
 * BENDAHARA DYNAMIC THEME ENFORCER
 * Based on Context7 Filament v3 research and frontend best practices
 * Handles persistent CSS conflicts via intelligent mutation observation
 */

class BendaharaDynamicThemeEnforcer {
    constructor() {
        this.processedElements = new WeakSet();
        this.conflictCount = 0;
        this.observer = null;
        this.blackTheme = {
            background: 'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)',
            border: '1px solid #333340',
            borderRadius: '1rem',
            color: '#fafafa',
            boxShadow: '0 4px 12px -2px rgba(0, 0, 0, 0.8), 0 2px 6px -2px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08)'
        };
        
        this.init();
    }
    
    init() {
        console.log('🚀 Dynamic Theme Enforcer: Initializing...');
        
        // Initial application
        this.applyThemeToExistingElements();
        
        // Setup mutation observer for dynamic content
        this.setupMutationObserver();
        
        // Monitor conflicts periodically
        this.setupConflictMonitoring();
        
        console.log('✅ Dynamic Theme Enforcer: Ready');
    }
    
    applyThemeToExistingElements() {
        const selectors = [
            '.bg-white', '.dark\\:bg-gray-800', '.rounded-xl', 
            '.shadow-sm', '.fi-section', '.fi-main',
            '[class*="bg-gray"]', '[id*="chart"]'
        ];
        
        let styled = 0;
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (this.shouldStyleElement(el)) {
                    this.applyBlackTheme(el);
                    this.processedElements.add(el);
                    styled++;
                }
            });
        });
        
        console.log(`🎨 Initial styling: ${styled} elements processed`);
        return styled;
    }
    
    setupMutationObserver() {
        this.observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                // Handle added nodes
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            this.processNewElement(node);
                        }
                    });
                }
                
                // Handle attribute changes (inline styles)
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    this.handleStyleMutation(mutation.target);
                }
            });
        });
        
        this.observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
        
        console.log('👀 Mutation Observer: Active');
    }
    
    processNewElement(element) {
        // Check if element needs black theme
        if (this.shouldStyleElement(element)) {
            this.applyBlackTheme(element);
            this.processedElements.add(element);
            console.log('🆕 New element styled:', element.tagName, element.className);
        }
        
        // Also check child elements
        const children = element.querySelectorAll('.bg-white, .dark\\:bg-gray-800, .fi-section');
        children.forEach(child => {
            if (this.shouldStyleElement(child)) {
                this.applyBlackTheme(child);
                this.processedElements.add(child);
            }
        });
    }
    
    shouldStyleElement(element) {
        // Skip sidebar, topbar, and other panels
        if (element.closest('.fi-sidebar') || 
            element.closest('.fi-topbar') || 
            element.closest('.fi-navigation')) {
            return false;
        }
        
        // Skip already processed elements
        if (this.processedElements.has(element)) {
            return false;
        }
        
        // Check if element has background that needs styling
        const computedStyle = window.getComputedStyle(element);
        const bg = computedStyle.backgroundColor;
        
        return bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent' &&
               !bg.includes('10, 10, 11') && !bg.includes('17, 17, 24');
    }
    
    applyBlackTheme(element) {
        element.style.setProperty('background', this.blackTheme.background, 'important');
        element.style.setProperty('border', this.blackTheme.border, 'important');
        element.style.setProperty('border-radius', this.blackTheme.borderRadius, 'important');
        element.style.setProperty('color', this.blackTheme.color, 'important');
        element.style.setProperty('box-shadow', this.blackTheme.boxShadow, 'important');
        
        // Mark as resolved
        element.setAttribute('data-conflict-resolved', 'true');
        element.setAttribute('data-theme-state', 'black-elegant');
    }
    
    handleStyleMutation(element) {
        // If element's style was changed externally, re-apply black theme
        if (this.shouldStyleElement(element)) {
            this.applyBlackTheme(element);
            console.log('🔄 Style mutation fixed:', element.tagName);
        }
    }
    
    setupConflictMonitoring() {
        setInterval(() => {
            this.scanForConflicts();
        }, 3000); // Check every 3 seconds
    }
    
    scanForConflicts() {
        const elements = document.querySelectorAll('.bg-white, .dark\\:bg-gray-800, .fi-section');
        let conflicts = 0;
        
        elements.forEach(el => {
            if (!el.closest('.fi-sidebar') && !el.closest('.fi-topbar')) {
                const bg = window.getComputedStyle(el).backgroundColor;
                if (!bg.includes('10, 10, 11') && !bg.includes('17, 17, 24')) {
                    conflicts++;
                    // Auto-fix conflicts
                    this.applyBlackTheme(el);
                }
            }
        });
        
        this.conflictCount = conflicts;
        
        if (conflicts > 0) {
            console.log(`🔧 Auto-fixed ${conflicts} conflicts`);
        }
        
        // Update debug panel if exists
        const status = document.getElementById('debug-status');
        if (status) {
            status.innerHTML = conflicts > 0 ? `🔧 Fixed ${conflicts} conflicts` : '✅ All OK';
        }
        
        return conflicts;
    }
    
    // Public API methods
    forceStyleAll() {
        return this.applyThemeToExistingElements();
    }
    
    getConflictCount() {
        return this.conflictCount;
    }
    
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
        console.log('🛑 Dynamic Theme Enforcer: Destroyed');
    }
}

// Auto-initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.bendaharaThemeEnforcer = new BendaharaDynamicThemeEnforcer();
    
    // Expose to global scope for debugging
    window.forceBlackTheme = () => window.bendaharaThemeEnforcer.forceStyleAll();
    window.scanConflicts = () => window.bendaharaThemeEnforcer.scanForConflicts();
});

// Auto-destroy on page unload
window.addEventListener('beforeunload', function() {
    if (window.bendaharaThemeEnforcer) {
        window.bendaharaThemeEnforcer.destroy();
    }
});