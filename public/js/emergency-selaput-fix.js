/**
 * EMERGENCY SELAPUT OVERLAY FIX
 * 
 * This script immediately removes any invisible overlay ("selaput") 
 * that is blocking user interaction with the admin panel.
 * 
 * Usage: Include this script in the admin panel or run directly in console
 */

(function() {
    'use strict';
    
    console.log('🚨 Starting Emergency Selaput Overlay Fix...');
    
    /**
     * Remove all modal-related overlays
     */
    function removeModalOverlays() {
        const modalSelectors = [
            '.fi-modal',
            '.fi-modal-overlay', 
            '.fi-slide-over',
            '.fi-slide-over-overlay',
            '[data-modal="true"]',
            '[data-modal]',
            '[data-backdrop="true"]',
            '[x-show="show"]',
            '.modal-overlay',
            '.backdrop-blur',
            '.backdrop-blur-sm',
            '.backdrop-blur-md',
            '.backdrop-blur-lg',
            '[class*="modal"]',
            '[class*="overlay"]',
            '[class*="backdrop"]'
        ];
        
        let removedCount = 0;
        modalSelectors.forEach(selector => {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.style.display = 'none';
                    el.style.visibility = 'hidden';
                    el.style.opacity = '0';
                    el.style.zIndex = '-1';
                    el.style.pointerEvents = 'none';
                    el.remove();
                    removedCount++;
                });
            } catch (error) {
                console.warn('Error removing elements with selector:', selector, error);
            }
        });
        
        console.log('🗑️ Removed modal overlays:', removedCount);
        return removedCount;
    }
    
    /**
     * Remove all backdrop filters
     */
    function removeBackdropFilters() {
        let filteredCount = 0;
        const allElements = document.querySelectorAll('*');
        
        allElements.forEach(el => {
            try {
                const style = window.getComputedStyle(el);
                if (style.backdropFilter && style.backdropFilter !== 'none') {
                    el.style.backdropFilter = 'none';
                    el.style.webkitBackdropFilter = 'none';
                    filteredCount++;
                }
            } catch (error) {
                // Ignore errors for inaccessible elements
            }
        });
        
        console.log('🎭 Removed backdrop filters:', filteredCount);
        return filteredCount;
    }
    
    /**
     * Remove high z-index overlay elements
     */
    function removeHighZIndexOverlays() {
        let overlayCount = 0;
        const allElements = document.querySelectorAll('*');
        
        allElements.forEach(el => {
            try {
                const style = window.getComputedStyle(el);
                const zIndex = parseInt(style.zIndex);
                
                if (zIndex >= 50 && style.position === 'fixed') {
                    const rect = el.getBoundingClientRect();
                    const isFullCover = (
                        rect.width >= window.innerWidth * 0.8 && 
                        rect.height >= window.innerHeight * 0.8
                    );
                    
                    if (isFullCover) {
                        el.style.display = 'none';
                        el.style.visibility = 'hidden';
                        el.style.opacity = '0';
                        el.style.zIndex = '-1';
                        el.style.pointerEvents = 'none';
                        overlayCount++;
                        console.log('🚫 Removed high z-index overlay:', el);
                    }
                }
            } catch (error) {
                // Ignore errors for inaccessible elements
            }
        });
        
        console.log('⚡ Removed high z-index overlays:', overlayCount);
        return overlayCount;
    }
    
    /**
     * Remove elements with specific inline styles that indicate overlays
     */
    function removeInlineStyleOverlays() {
        const dangerousStyles = [
            'position: fixed; top: 0; left: 0; right: 0; bottom: 0;',
            'position: fixed; inset: 0;',
            'position: fixed; width: 100%; height: 100%;',
            'position: fixed; width: 100vw; height: 100vh;',
            'z-index: 50',
            'z-index: 51',
            'z-index: 52',
            'z-index: 53',
            'z-index: 54',
            'z-index: 55',
            'backdrop-filter:'
        ];
        
        let styleOverlayCount = 0;
        const allElements = document.querySelectorAll('*');
        
        allElements.forEach(el => {
            const inlineStyle = el.getAttribute('style');
            if (inlineStyle) {
                dangerousStyles.forEach(dangerousStyle => {
                    if (inlineStyle.includes(dangerousStyle)) {
                        el.style.display = 'none';
                        el.style.visibility = 'hidden';
                        el.style.opacity = '0';
                        el.style.zIndex = '-1';
                        el.style.pointerEvents = 'none';
                        styleOverlayCount++;
                    }
                });
            }
        });
        
        console.log('📏 Removed inline style overlays:', styleOverlayCount);
        return styleOverlayCount;
    }
    
    /**
     * Reset Alpine.js and Livewire modal states
     */
    function resetFrameworkStates() {
        let resetCount = 0;
        
        // Reset Alpine.js modals
        if (window.Alpine && window.Alpine.store) {
            try {
                window.Alpine.store('modals', {});
                resetCount++;
                console.log('🏔️ Reset Alpine.js modal store');
            } catch (error) {
                console.warn('Could not reset Alpine.js:', error);
            }
        }
        
        // Reset Livewire modal states
        if (window.Livewire) {
            try {
                // Close any open Livewire modals
                const livewireModals = document.querySelectorAll('[wire\\:model*="modal"], [x-data*="modal"]');
                livewireModals.forEach(modal => {
                    modal.style.display = 'none';
                    resetCount++;
                });
                console.log('⚡ Reset Livewire modal states');
            } catch (error) {
                console.warn('Could not reset Livewire:', error);
            }
        }
        
        return resetCount;
    }
    
    /**
     * Force admin interface to be visible and interactive
     */
    function forceAdminVisibility() {
        const adminSelectors = [
            '.fi-main',
            '.fi-page', 
            '.fi-sidebar',
            '.fi-topbar',
            '.fi-header',
            '.fi-section',
            '.fi-widget',
            '.fi-btn',
            '.fi-input',
            '.dark .fi-main',
            '.dark .fi-page',
            '.dark .fi-sidebar'
        ];
        
        let forcedCount = 0;
        adminSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
                el.style.pointerEvents = 'auto';
                el.style.userSelect = 'auto';
                forcedCount++;
            });
        });
        
        console.log('👁️ Forced admin visibility:', forcedCount);
        return forcedCount;
    }
    
    /**
     * Apply emergency CSS fixes
     */
    function applyEmergencyCSS() {
        const emergencyCSS = `
        /* EMERGENCY SELAPUT OVERLAY FIX */
        .fi-modal,
        .fi-modal-overlay,
        .fi-slide-over,
        .fi-slide-over-overlay,
        [data-modal="true"],
        [data-backdrop="true"],
        [x-show="show"],
        .modal-overlay,
        .backdrop-blur,
        .backdrop-blur-sm,
        .backdrop-blur-md,
        .backdrop-blur-lg,
        *[class*="modal"],
        *[class*="overlay"],
        *[class*="backdrop"],
        *[style*="backdrop-filter"],
        *[style*="z-index: 50"],
        *[style*="z-index: 51"],
        *[style*="z-index: 52"] {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            z-index: -1 !important;
            pointer-events: none !important;
            position: static !important;
        }
        
        /* Remove ALL backdrop filters */
        * {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        
        /* Force admin content to be interactive */
        .fi-main,
        .fi-main *,
        .fi-page,
        .fi-page *,
        .fi-sidebar,
        .fi-sidebar *,
        .dark .fi-main,
        .dark .fi-main *,
        .dark .fi-page,
        .dark .fi-page *,
        .dark .fi-sidebar,
        .dark .fi-sidebar * {
            pointer-events: auto !important;
            user-select: auto !important;
            -webkit-user-select: auto !important;
            -moz-user-select: auto !important;
            -ms-user-select: auto !important;
        }
        
        /* Ensure body and html are not blocked */
        html,
        body {
            overflow: visible !important;
            position: static !important;
            pointer-events: auto !important;
        }
        
        /* Force show admin interface */
        .fi-layout,
        .fi-main-ctn,
        .fi-sidebar,
        .fi-main,
        .fi-page {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        `;
        
        const style = document.createElement('style');
        style.id = 'emergency-selaput-fix';
        style.textContent = emergencyCSS;
        
        // Remove existing emergency fix style if present
        const existingStyle = document.getElementById('emergency-selaput-fix');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        document.head.appendChild(style);
        console.log('💉 Applied emergency CSS fixes');
    }
    
    /**
     * Main execution function
     */
    function executeEmergencyFix() {
        console.log('🔧 Executing Emergency Selaput Overlay Fix...');
        
        const results = {
            modalOverlays: removeModalOverlays(),
            backdropFilters: removeBackdropFilters(),
            highZIndexOverlays: removeHighZIndexOverlays(),
            inlineStyleOverlays: removeInlineStyleOverlays(),
            frameworkResets: resetFrameworkStates(),
            forcedVisibility: forceAdminVisibility()
        };
        
        // Apply emergency CSS
        applyEmergencyCSS();
        
        // Calculate total fixes applied
        const totalFixes = Object.values(results).reduce((sum, count) => sum + count, 0);
        
        console.log('📊 Fix Results:', results);
        console.log('✅ Total fixes applied:', totalFixes);
        
        if (totalFixes > 0) {
            console.log('🎉 Selaput overlay fix completed successfully!');
            
            // Show success notification
            showSuccessNotification();
        } else {
            console.log('ℹ️ No overlay issues detected. Admin panel should be working normally.');
        }
        
        return results;
    }
    
    /**
     * Show success notification
     */
    function showSuccessNotification() {
        // Create temporary success notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 600;
            z-index: 999999;
            animation: slideIn 0.5s ease-out;
        `;
        notification.innerHTML = '✅ Selaput overlay removed! Admin panel is now interactive.';
        
        // Add animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.remove();
            style.remove();
        }, 5000);
    }
    
    /**
     * Auto-execute on page load
     */
    function autoExecute() {
        // Execute immediately if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', executeEmergencyFix);
        } else {
            executeEmergencyFix();
        }
    }
    
    // Export functions to global scope for manual execution
    window.emergencySelaputFix = {
        execute: executeEmergencyFix,
        removeModalOverlays,
        removeBackdropFilters,
        removeHighZIndexOverlays,
        removeInlineStyleOverlays,
        resetFrameworkStates,
        forceAdminVisibility,
        applyEmergencyCSS
    };
    
    // Auto-execute the fix
    autoExecute();
    
    console.log('🛠️ Emergency Selaput Fix loaded. Use window.emergencySelaputFix.execute() to run manually.');
    
})();