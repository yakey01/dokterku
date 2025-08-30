/**
 * BENDAHARA CLEAN THEME - MINIMAL APPROACH
 * Simple theme integration without conflicts
 * Uses CSS custom properties for sustainable theming
 */

(function() {
    'use strict';
    
    // Wait for Filament to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎨 Bendahara Clean Theme: Initialized');
        
        // Simple theme marker for debugging
        const panel = document.querySelector('[data-filament-panel-id="bendahara"]');
        if (panel) {
            panel.setAttribute('data-theme-loaded', 'modern-black');
            console.log('✅ Modern black theme: Applied via CSS');
        }
    });
    
    // Minimal conflict detection (optional)
    if (typeof window.checkBendaharaTheme === 'function') {
        window.checkBendaharaTheme = function() {
            const conflicts = document.querySelectorAll(
                '[data-filament-panel-id="bendahara"] .bg-white:not([data-theme-component])'
            );
            
            console.log(`🔍 Theme check: ${conflicts.length} potential conflicts found`);
            return conflicts.length;
        };
    }
})();