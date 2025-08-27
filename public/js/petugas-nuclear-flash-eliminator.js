// NUCLEAR NAVY BLUE FLASH ELIMINATOR - ULTIMATE PROTECTION
// This script provides maximum protection against any navy blue flashing
(function() {
    'use strict';
    
    console.log('🚨 NUCLEAR NAVY BLUE ELIMINATOR: Maximum protection active!');
    
    // NUCLEAR BLACK THEME ENFORCEMENT
    const forceBlackThemeNuclear = () => {
        try {
            // Target Petugas panel specifically
            const petugasPanel = document.querySelector('[data-filament-panel-id="petugas"]');
            if (!petugasPanel) {
                return false;
            }
            
            // FORCE PANEL-LEVEL VARIABLES
            petugasPanel.style.setProperty('--primary', '10 10 11', 'important');
            petugasPanel.style.setProperty('--primary-50', '#0a0a0b', 'important');
            petugasPanel.style.setProperty('--primary-500', '#0a0a0b', 'important');
            petugasPanel.style.setProperty('--primary-600', '#0a0a0b', 'important');
            petugasPanel.style.setProperty('--primary-700', '#0a0a0b', 'important');
            petugasPanel.style.setProperty('--primary-800', '#0a0a0b', 'important');
            petugasPanel.style.setProperty('--primary-900', '#0a0a0b', 'important');
            
            // FORCE SIDEBAR ELEMENTS
            const sidebarElements = petugasPanel.querySelectorAll(
                '.fi-sidebar, .fi-sidebar *, .fi-sidebar-nav, .fi-sidebar-nav *, .fi-sidebar-nav-item, .fi-sidebar-nav-item *'
            );
            
            sidebarElements.forEach(element => {
                // Force black background
                element.style.setProperty('background', 'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)', 'important');
                element.style.setProperty('background-color', '#0a0a0b', 'important');
                element.style.setProperty('color', '#e2e8f0', 'important');
                element.style.setProperty('border-color', '#333340', 'important');
                
                // Force CSS variables at element level
                element.style.setProperty('--primary', '10 10 11', 'important');
                element.style.setProperty('--primary-500', '#0a0a0b', 'important');
            });
            
            // SPECIFIC NAVIGATION ITEM PROTECTION
            const navItems = petugasPanel.querySelectorAll('.fi-sidebar-nav-item');
            navItems.forEach(item => {
                // Default state
                item.style.setProperty('background', 'linear-gradient(135deg, #0a0a0b 0%, #111118 100%)', 'important');
                item.style.setProperty('background-color', '#0a0a0b', 'important');
                item.style.setProperty('color', '#e2e8f0', 'important');
                
                // Force removal of any blue/slate classes
                item.classList.remove('bg-slate-500', 'bg-slate-600', 'bg-blue-500', 'bg-blue-600');
                item.classList.remove('text-slate-500', 'text-slate-600', 'text-blue-500', 'text-blue-600');
                
                // Active state protection
                if (item.classList.contains('fi-active') || 
                    item.classList.contains('active') || 
                    item.getAttribute('aria-current') === 'page') {
                    item.style.setProperty('background', 'linear-gradient(135deg, rgba(42, 42, 50, 0.9) 0%, rgba(64, 64, 80, 0.7) 100%)', 'important');
                    item.style.setProperty('background-color', 'rgba(42, 42, 50, 0.9)', 'important');
                    item.style.setProperty('color', 'white', 'important');
                    item.style.setProperty('font-weight', '600', 'important');
                }
            });
            
            return true;
        } catch (error) {
            console.warn('Navy blue eliminator error:', error);
            return false;
        }
    };
    
    // IMMEDIATE EXECUTION STRATEGY
    let executionCount = 0;
    const maxExecutions = 50; // 2.5 seconds total
    
    const executeProtection = () => {
        const success = forceBlackThemeNuclear();
        executionCount++;
        
        if (success) {
            console.log(`✅ Navy blue protection applied (execution ${executionCount})`);
        }
        
        if (executionCount >= maxExecutions) {
            console.log('🎯 NUCLEAR PROTECTION COMPLETE - Navy blue eliminated!');
            return;
        }
        
        // Continue protection for first 2.5 seconds
        setTimeout(executeProtection, 50);
    };
    
    // START IMMEDIATE PROTECTION
    executeProtection();
    
    // DOM READY PROTECTION
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceBlackThemeNuclear);
    }
    
    // WINDOW LOAD PROTECTION
    window.addEventListener('load', forceBlackThemeNuclear);
    
    // MUTATION OBSERVER FOR DYNAMIC CONTENT
    const observer = new MutationObserver(function(mutations) {
        let needsProtection = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if it's a sidebar-related element
                        if (node.classList && (
                            node.classList.contains('fi-sidebar') ||
                            node.classList.contains('fi-sidebar-nav-item') ||
                            node.closest && node.closest('.fi-sidebar')
                        )) {
                            needsProtection = true;
                        }
                        
                        // Check if it's the panel itself
                        if (node.hasAttribute && node.hasAttribute('data-filament-panel-id')) {
                            needsProtection = true;
                        }
                    }
                });
            }
            
            // Also check for attribute changes that might affect styling
            if (mutation.type === 'attributes' && 
                (mutation.attributeName === 'class' || 
                 mutation.attributeName === 'style' ||
                 mutation.attributeName === 'aria-current')) {
                const target = mutation.target;
                if (target.classList && (
                    target.classList.contains('fi-sidebar-nav-item') ||
                    target.closest('.fi-sidebar')
                )) {
                    needsProtection = true;
                }
            }
        });
        
        if (needsProtection) {
            // Small delay to let the DOM settle
            setTimeout(forceBlackThemeNuclear, 10);
        }
    });
    
    // START OBSERVING
    const startObserver = () => {
        const targetNode = document.body || document.documentElement;
        observer.observe(targetNode, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'aria-current']
        });
        console.log('🔍 Nuclear observer active - monitoring for dynamic changes');
    };
    
    if (document.body) {
        startObserver();
    } else {
        document.addEventListener('DOMContentLoaded', startObserver);
    }
    
    // LIVEWIRE HOOKS (if available)
    if (window.Livewire) {
        window.Livewire.hook('component.initialized', forceBlackThemeNuclear);
        window.Livewire.hook('element.updated', forceBlackThemeNuclear);
    }
    
    // ALPINE.JS HOOKS (if available) 
    document.addEventListener('alpine:init', forceBlackThemeNuclear);
    document.addEventListener('alpine:initialized', forceBlackThemeNuclear);
    
    // GLOBAL MANUAL TRIGGER
    window.forceBlackThemeNuclear = forceBlackThemeNuclear;
    window.eliminateNavyFlashNuclear = forceBlackThemeNuclear;
    
    // VISIBILITY CHANGE PROTECTION (when tab becomes active)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            setTimeout(forceBlackThemeNuclear, 100);
        }
    });
    
    console.log('💡 Manual triggers available:');
    console.log('  - window.forceBlackThemeNuclear()');
    console.log('  - window.eliminateNavyFlashNuclear()');
    console.log('🛡️ Nuclear protection is now active and monitoring!');
    
})();