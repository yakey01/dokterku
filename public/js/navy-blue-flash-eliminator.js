// NAVY BLUE FLASH ELIMINATOR - IMMEDIATE THEME ENFORCEMENT
(function() {
    'use strict';
    
    console.log('🚨 NAVY BLUE FLASH ELIMINATOR: Enforcing black theme immediately...');
    
    // CRITICAL: Apply theme IMMEDIATELY before DOM is ready
    const enforceBlackTheme = () => {
        // Force all potential problematic elements to black
        const selectors = [
            '[data-filament-panel-id="petugas"]',
            '[data-filament-panel-id="bendahara"]', 
            '.fi-sidebar',
            '.fi-sidebar-nav-item',
            '.fi-sidebar-nav-item:hover',
            '.fi-sidebar-nav-item.fi-active'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                // Override any navy blue/slate colors immediately
                element.style.setProperty('background', 'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)', 'important');
                element.style.setProperty('color', '#fafafa', 'important');
                
                // Force CSS custom properties to black theme
                element.style.setProperty('--primary-50', '#0a0a0b', 'important');
                element.style.setProperty('--primary-100', '#111118', 'important');
                element.style.setProperty('--primary-200', '#1a1a20', 'important');
                element.style.setProperty('--primary-300', '#2a2a32', 'important');
                element.style.setProperty('--primary-400', '#333340', 'important');
                element.style.setProperty('--primary-500', '#404050', 'important');
                element.style.setProperty('--primary-600', '#4a4a5a', 'important');
                element.style.setProperty('--primary-700', '#555564', 'important');
                element.style.setProperty('--primary-800', '#60606e', 'important');
                element.style.setProperty('--primary-900', '#6b6b78', 'important');
                element.style.setProperty('--primary-950', '#767682', 'important');
            });
        });
        
        // Force body and html to black theme
        document.documentElement.style.setProperty('background', '#0a0a0b', 'important');
        document.body.style.setProperty('background', '#0a0a0b', 'important');
        
        console.log('✅ Navy blue flash eliminated!');
    };
    
    // Apply IMMEDIATELY - don't wait for anything
    enforceBlackTheme();
    
    // Apply on DOM ready as backup
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enforceBlackTheme);
    }
    
    // Apply after short delays to catch dynamic elements
    setTimeout(enforceBlackTheme, 50);
    setTimeout(enforceBlackTheme, 100);
    setTimeout(enforceBlackTheme, 250);
    
    // Observer for dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        let shouldEnforce = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (
                            node.classList.contains('fi-sidebar') ||
                            node.classList.contains('fi-sidebar-nav-item') ||
                            node.hasAttribute('data-filament-panel-id')
                        )) {
                            shouldEnforce = true;
                        }
                    }
                });
            }
        });
        
        if (shouldEnforce) {
            enforceBlackTheme();
        }
    });
    
    // Start observing
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }
    
    // Global function for manual trigger
    window.eliminateNavyBlueFlash = enforceBlackTheme;
    
    console.log('💡 Manual trigger: window.eliminateNavyBlueFlash()');
})();