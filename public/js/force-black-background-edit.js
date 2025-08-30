// NUCLEAR BLACK BACKGROUND FORCE - EDIT PAGE
(function() {
    'use strict';
    
    console.log('🖤 NUCLEAR BLACK BACKGROUND FORCE - EDIT PAGE');
    
    function forceBlackBackground() {
        // TARGETED APPROACH - Only target specific navy elements
        const style = document.createElement('style');
        style.innerHTML = `
            /* TARGET ONLY NAVY BLUE ELEMENTS */
            [data-filament-panel-id="petugas"] .fi-main,
            [data-filament-panel-id="petugas"] .fi-page {
                background: linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%) !important;
            }
            
            /* TARGET NAVY BLUE INFO SECTIONS */
            [style*="rgba(59, 130, 246"], [style*="rgb(59, 130, 246"],
            .text-primary-600, .bg-primary-50, .border-primary-200 {
                background: rgba(255, 255, 255, 0.05) !important;
                backdrop-filter: blur(8px) !important;
                color: #ffffff !important;
            }
        `;
        style.setAttribute('id', 'targeted-black-override');
        document.head.appendChild(style);
        // Force page background to black - PRESERVE LAYOUT
        const pageElements = document.querySelectorAll([
            '[data-filament-panel-id="petugas"]',
            '[data-filament-panel-id="petugas"] .fi-main',
            '[data-filament-panel-id="petugas"] .fi-page',
            '[data-filament-panel-id="petugas"] .fi-page-content',
            '[data-filament-panel-id="petugas"] .fi-body',
            '[data-filament-panel-id="petugas"] .fi-layout'
        ].join(','));
        
        pageElements.forEach(element => {
            if (element) {
                element.style.setProperty('background', 'linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%)', 'important');
                element.style.setProperty('background-color', '#0a0a0b', 'important');
                element.style.setProperty('color', '#ffffff', 'important');
                console.log('✅ Forced black background on:', element.tagName);
            }
        });
        
        // Force all white backgrounds to black
        const whiteElements = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] .bg-white',
            '[data-filament-panel-id="petugas"] .dark\\:bg-gray-800',
            '[data-filament-panel-id="petugas"] .fi-section',
            '[data-filament-panel-id="petugas"] .fi-form'
        ].join(','));
        
        whiteElements.forEach(element => {
            if (element) {
                element.style.setProperty('background', 'rgba(255, 255, 255, 0.05)', 'important');
                element.style.setProperty('backdrop-filter', 'blur(12px) saturate(110%)', 'important');
                element.style.setProperty('-webkit-backdrop-filter', 'blur(12px) saturate(110%)', 'important');
                element.style.setProperty('border', '1px solid rgba(255, 255, 255, 0.08)', 'important');
                element.style.setProperty('border-radius', '1rem', 'important');
                element.style.setProperty('box-shadow', '0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06)', 'important');
                element.style.setProperty('color', '#ffffff', 'important');
                console.log('✅ Applied glass effect to:', element);
            }
        });
        
        // Force all text to white
        const allText = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] *'
        ].join(','));
        
        allText.forEach(element => {
            if (element && element.tagName !== 'SCRIPT' && element.tagName !== 'STYLE') {
                element.style.setProperty('color', '#ffffff', 'important');
            }
        });
        
        console.log('🖤 NUCLEAR BLACK BACKGROUND APPLIED!');
    }
    
    // Apply immediately and with retries
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceBlackBackground);
    } else {
        forceBlackBackground();
    }
    
    setTimeout(forceBlackBackground, 100);
    setTimeout(forceBlackBackground, 500);
    setTimeout(forceBlackBackground, 1000);
    
    // Global function
    window.forceBlackEdit = forceBlackBackground;
    
    console.log('💡 Manual trigger: window.forceBlackEdit()');
})();