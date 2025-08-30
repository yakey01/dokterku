/**
 * 🖤 FORCE ELEGANT BLACK TABLES - JAVASCRIPT SOLUTION
 * Emergency CSS injection for immediate table styling
 */

(function() {
    'use strict';
    
    console.log('🎨 Initializing Elegant Black Tables JavaScript...');
    
    // Wait for DOM to be ready
    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }
    
    // Enhanced CSS injection with maximum specificity
    function injectElegantBlackCSS() {
        const css = `
        /* 🖤 MINIMALIST BLACK TABLE - EMERGENCY CSS INJECTION */
        html body div[data-filament-panel-id="petugas"] .fi-ta-table,
        html body div[data-filament-panel-id="petugas"] .fi-section,
        html body div[data-filament-panel-id="petugas"] .overflow-x-auto {
            background: linear-gradient(135deg, rgba(10,10,11,0.95) 0%, rgba(17,17,24,0.90) 100%) !important;
            backdrop-filter: blur(8px) saturate(120%) !important;
            -webkit-backdrop-filter: blur(8px) saturate(120%) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 1rem !important;
            box-shadow: 0 4px 16px -4px rgba(0,0,0,0.4), 0 2px 8px -2px rgba(0,0,0,0.3), inset 0 1px 0 0 rgba(255,255,255,0.06) !important;
            transition: all 0.3s ease !important;
            color: #ffffff !important;
        }
        
        html body div[data-filament-panel-id="petugas"] .fi-ta-table:hover,
        html body div[data-filament-panel-id="petugas"] .fi-section:hover {
            backdrop-filter: blur(12px) saturate(130%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(130%) !important;
            border-color: rgba(255,255,255,0.12) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 24px -8px rgba(0,0,0,0.5), 0 4px 12px -4px rgba(0,0,0,0.4), inset 0 1px 0 0 rgba(255,255,255,0.08) !important;
            background: linear-gradient(135deg, rgba(17,17,24,0.98) 0%, rgba(26,26,32,0.95) 100%) !important;
        }
        
        html body div[data-filament-panel-id="petugas"] thead th,
        html body div[data-filament-panel-id="petugas"] .fi-ta-header {
            background: linear-gradient(135deg, rgba(20,20,25,0.8) 0%, rgba(30,30,35,0.9) 100%) !important;
            backdrop-filter: blur(6px) !important;
            -webkit-backdrop-filter: blur(6px) !important;
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            letter-spacing: 0.02em !important;
            text-transform: uppercase !important;
            padding: 1rem 1.25rem !important;
        }
        
        html body div[data-filament-panel-id="petugas"] tbody tr,
        html body div[data-filament-panel-id="petugas"] .fi-ta-row {
            background: rgba(15,15,18,0.6) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            border-bottom: 1px solid rgba(255,255,255,0.06) !important;
            color: #ffffff !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
        }
        
        html body div[data-filament-panel-id="petugas"] tbody tr:hover,
        html body div[data-filament-panel-id="petugas"] .fi-ta-row:hover {
            background: linear-gradient(135deg, rgba(25,25,30,0.8) 0%, rgba(35,35,40,0.9) 100%) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            transform: translateY(-2px) !important;
            border-left: 2px solid rgba(100,116,139,0.4) !important;
            border-bottom-color: rgba(255,255,255,0.12) !important;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3), 0 2px 8px rgba(0,0,0,0.2) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            cursor: pointer !important;
        }
        
        html body div[data-filament-panel-id="petugas"] td,
        html body div[data-filament-panel-id="petugas"] th {
            padding: 1.5rem 1.25rem !important;
            border-right: 1px solid rgba(255, 255, 255, 0.06) !important;
            color: inherit !important;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1) !important;
        }
        
        html body div[data-filament-panel-id="petugas"] tbody tr:hover td {
            background: linear-gradient(90deg, 
                transparent 0%,
                rgba(100, 116, 139, 0.08) 30%,
                rgba(100, 116, 139, 0.12) 50%,
                rgba(100, 116, 139, 0.08) 70%,
                transparent 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4) !important;
        }
        
        /* Page Background */
        html body div[data-filament-panel-id="petugas"] .fi-page,
        html body div[data-filament-panel-id="petugas"] .fi-main {
            background: linear-gradient(135deg, #040405 0%, #080809 30%, #0c0c0d 70%, #101011 100%) !important;
        }
        
        /* Badges */
        html body div[data-filament-panel-id="petugas"] .fi-badge {
            background: linear-gradient(135deg, 
                rgba(100, 116, 139, 0.2) 0%, 
                rgba(71, 85, 105, 0.15) 100%) !important;
            backdrop-filter: blur(12px) saturate(140%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(140%) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 0.75rem !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            transition: all 0.3s ease !important;
        }
        `;
        
        // Create and inject style element
        const style = document.createElement('style');
        style.textContent = css;
        style.setAttribute('data-elegant-black-tables', 'emergency-injection');
        document.head.appendChild(style);
        
        console.log('✅ Emergency CSS injected successfully!');
    }
    
    // Direct DOM styling function
    function applyDirectStyling() {
        console.log('🎯 Applying direct DOM styling...');
        
        // Target table containers
        const containers = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] .fi-ta-table',
            '[data-filament-panel-id="petugas"] .fi-section',
            '[data-filament-panel-id="petugas"] .overflow-x-auto'
        ].join(','));
        
        containers.forEach(container => {
            if (container) {
                Object.assign(container.style, {
                    background: 'linear-gradient(135deg, rgba(10,10,11,0.95) 0%, rgba(17,17,24,0.90) 100%)',
                    backdropFilter: 'blur(8px) saturate(120%)',
                    webkitBackdropFilter: 'blur(8px) saturate(120%)',
                    border: '1px solid rgba(255,255,255,0.08)',
                    borderRadius: '1rem',
                    boxShadow: '0 4px 16px -4px rgba(0,0,0,0.4), 0 2px 8px -2px rgba(0,0,0,0.3), inset 0 1px 0 0 rgba(255,255,255,0.06)',
                    transition: 'all 0.3s ease',
                    color: '#ffffff'
                });
                
                console.log('✅ Styled container:', container);
            }
        });
        
        // Target table headers
        const headers = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] thead th',
            '[data-filament-panel-id="petugas"] .fi-ta-header'
        ].join(','));
        
        headers.forEach(header => {
            if (header) {
                Object.assign(header.style, {
                    background: 'linear-gradient(135deg, rgba(20,20,25,0.8) 0%, rgba(30,30,35,0.9) 100%)',
                    backdropFilter: 'blur(6px)',
                    webkitBackdropFilter: 'blur(6px)',
                    borderBottom: '1px solid rgba(255,255,255,0.1)',
                    color: '#ffffff',
                    fontWeight: '600',
                    fontSize: '0.875rem',
                    letterSpacing: '0.02em',
                    textTransform: 'uppercase',
                    padding: '1rem 1.25rem'
                });
                
                console.log('✅ Styled header:', header);
            }
        });
        
        // Target table rows
        const rows = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] tbody tr',
            '[data-filament-panel-id="petugas"] .fi-ta-row'
        ].join(','));
        
        rows.forEach((row, index) => {
            if (row) {
                Object.assign(row.style, {
                    background: 'rgba(15,15,18,0.6)',
                    backdropFilter: 'blur(4px)',
                    webkitBackdropFilter: 'blur(4px)',
                    borderBottom: '1px solid rgba(255,255,255,0.06)',
                    color: '#ffffff',
                    fontWeight: '500',
                    transition: 'all 0.3s ease'
                });
                
                // Add hover event listeners
                row.addEventListener('mouseenter', function() {
                    Object.assign(this.style, {
                        background: 'linear-gradient(135deg, rgba(25,25,30,0.8) 0%, rgba(35,35,40,0.9) 100%)',
                        backdropFilter: 'blur(8px)',
                        webkitBackdropFilter: 'blur(8px)',
                        transform: 'translateY(-2px)',
                        borderLeft: '2px solid rgba(100,116,139,0.4)',
                        borderBottomColor: 'rgba(255,255,255,0.12)',
                        boxShadow: '0 4px 16px rgba(0,0,0,0.3), 0 2px 8px rgba(0,0,0,0.2)',
                        fontWeight: '600',
                        cursor: 'pointer'
                    });
                    
                    // Style cells on hover
                    const cells = this.querySelectorAll('td');
                    cells.forEach(cell => {
                        Object.assign(cell.style, {
                            background: 'rgba(100,116,139,0.05)',
                            borderRightColor: 'rgba(255,255,255,0.1)',
                            color: '#ffffff',
                            fontWeight: '600'
                        });
                    });
                });
                
                row.addEventListener('mouseleave', function() {
                    Object.assign(this.style, {
                        background: 'rgba(15,15,18,0.6)',
                        backdropFilter: 'blur(4px)',
                        webkitBackdropFilter: 'blur(4px)',
                        transform: '',
                        borderLeft: '',
                        borderBottomColor: 'rgba(255,255,255,0.06)',
                        boxShadow: '',
                        fontWeight: '500'
                    });
                    
                    // Reset cells
                    const cells = this.querySelectorAll('td');
                    cells.forEach(cell => {
                        Object.assign(cell.style, {
                            background: 'transparent',
                            borderRightColor: 'rgba(255,255,255,0.06)',
                            color: '#ffffff',
                            fontWeight: '500'
                        });
                    });
                });
                
                console.log('✅ Styled row with hover events:', row);
            }
        });
        
        // Target page background
        const pageElements = document.querySelectorAll([
            '[data-filament-panel-id="petugas"] .fi-page',
            '[data-filament-panel-id="petugas"] .fi-main'
        ].join(','));
        
        pageElements.forEach(element => {
            if (element) {
                Object.assign(element.style, {
                    background: 'linear-gradient(135deg, #040405 0%, #080809 30%, #0c0c0d 70%, #101011 100%)',
                    minHeight: '100vh'
                });
                
                console.log('✅ Styled page element:', element);
            }
        });
        
        // Target badges
        const badges = document.querySelectorAll('[data-filament-panel-id="petugas"] .fi-badge');
        badges.forEach(badge => {
            if (badge) {
                Object.assign(badge.style, {
                    background: 'linear-gradient(135deg, rgba(100,116,139,0.2) 0%, rgba(71,85,105,0.15) 100%)',
                    backdropFilter: 'blur(12px) saturate(140%)',
                    border: '1px solid rgba(255,255,255,0.12)',
                    borderRadius: '0.75rem',
                    color: '#ffffff',
                    fontWeight: '700',
                    transition: 'all 0.3s ease'
                });
                
                console.log('✅ Styled badge:', badge);
            }
        });
        
        console.log('🎨 Direct DOM styling applied successfully!');
    }
    
    // Force CSS injection with retries
    function forceApplyStyling() {
        console.log('🔥 Force applying elegant black table styling...');
        
        // Inject CSS
        injectElegantBlackCSS();
        
        // Apply direct styling
        applyDirectStyling();
        
        // Set up mutation observer for dynamic content
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Re-apply styling to new elements
                    setTimeout(() => {
                        applyDirectStyling();
                    }, 100);
                }
            });
        });
        
        // Start observing
        const targetNode = document.querySelector('[data-filament-panel-id="petugas"]');
        if (targetNode) {
            observer.observe(targetNode, {
                childList: true,
                subtree: true
            });
            console.log('🔍 Mutation observer started for dynamic content');
        }
    }
    
    // Initialize when DOM is ready
    ready(function() {
        console.log('🎯 DOM ready, checking for petugas panel...');
        
        // Check if we're on petugas panel
        const isPetugasPanel = document.querySelector('[data-filament-panel-id="petugas"]');
        
        if (isPetugasPanel) {
            console.log('✅ Petugas panel detected, applying styling...');
            
            // Apply immediately
            forceApplyStyling();
            
            // Also apply after a short delay to catch dynamic content
            setTimeout(forceApplyStyling, 500);
            setTimeout(forceApplyStyling, 1000);
            setTimeout(forceApplyStyling, 2000);
            
        } else {
            console.log('ℹ️ Not on petugas panel, skipping styling');
        }
    });
    
    // Also apply on window load
    window.addEventListener('load', function() {
        console.log('🔄 Window loaded, re-applying styling...');
        setTimeout(forceApplyStyling, 100);
    });
    
    // Export for manual triggering
    window.forceElegantBlackTables = forceApplyStyling;
    
    console.log('🎨 Elegant Black Tables JavaScript loaded successfully!');
    console.log('💡 You can manually trigger styling with: window.forceElegantBlackTables()');
    
})();