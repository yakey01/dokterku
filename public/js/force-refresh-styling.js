// FORCE REFRESH - MINIMALIST GLASS TABLE STYLING
(function() {
    'use strict';
    
    console.log('🔥 FORCE REFRESH: Applying Minimalist Glass Table Styling...');
    
    function forceApplyMinimalistGlass() {
        // Force page background
        const pageElements = document.querySelectorAll('[data-filament-panel-id="petugas"]');
        pageElements.forEach(element => {
            element.style.setProperty('background', 'linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%)', 'important');
            element.style.setProperty('min-height', '100vh', 'important');
        });
        
        // Force table containers
        const containers = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] .fi-section',
            '[data-filament-panel-id="petugas"] .fi-ta',
            '[data-filament-panel-id="petugas"] .fi-ta-content',
            '[data-filament-panel-id="petugas"] .overflow-x-auto'
        ].join(','));
        
        containers.forEach(container => {
            container.style.setProperty('background', 'rgba(255, 255, 255, 0.05)', 'important');
            container.style.setProperty('backdrop-filter', 'blur(12px) saturate(110%)', 'important');
            container.style.setProperty('-webkit-backdrop-filter', 'blur(12px) saturate(110%)', 'important');
            container.style.setProperty('border', '1px solid rgba(255, 255, 255, 0.08)', 'important');
            container.style.setProperty('border-radius', '1rem', 'important');
            container.style.setProperty('box-shadow', '0 4px 16px -4px rgba(0, 0, 0, 0.4), 0 2px 8px -2px rgba(0, 0, 0, 0.3), inset 0 1px 0 0 rgba(255, 255, 255, 0.06)', 'important');
            container.style.setProperty('color', '#ffffff', 'important');
        });
        
        // Force table rows
        const rows = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] .fi-ta-row',
            '[data-filament-panel-id="petugas"] tbody tr'
        ].join(','));
        
        rows.forEach(row => {
            row.style.setProperty('background', 'rgba(255, 255, 255, 0.02)', 'important');
            row.style.setProperty('border-bottom', '1px solid rgba(255, 255, 255, 0.05)', 'important');
            row.style.setProperty('color', '#ffffff', 'important');
        });
        
        // Force all text to be white
        const allElements = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] .fi-section *',
            '[data-filament-panel-id="petugas"] .fi-ta *',
            '[data-filament-panel-id="petugas"] table *'
        ].join(','));
        
        allElements.forEach(element => {
            element.style.setProperty('color', '#ffffff', 'important');
        });
        
        console.log('✅ Minimalist Glass Styling Applied!');
    }
    
    // Apply immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceApplyMinimalistGlass);
    } else {
        forceApplyMinimalistGlass();
    }
    
    // Apply after delays
    setTimeout(forceApplyMinimalistGlass, 100);
    setTimeout(forceApplyMinimalistGlass, 500);
    setTimeout(forceApplyMinimalistGlass, 1000);
    
    // Global function for manual trigger
    window.forceMinimalistGlass = forceApplyMinimalistGlass;
    
    console.log('💡 Manual trigger: window.forceMinimalistGlass()');
})();