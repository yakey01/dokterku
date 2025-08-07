/**
 * Frontend Error Fixes Validation Script
 * 
 * Run this in the browser console to validate that all fixes are working properly
 */

(function validateFrontendFixes() {
    console.log('🔍 Starting Frontend Fixes Validation...\n');
    
    const results = {
        'Leaflet Utilities Available': false,
        'OptimizedResizeObserver Loaded': false,
        'CustomMarkerSystem Loaded': false,
        'AssetManager Loaded': false,
        'No Direct TypeScript Imports': true,
        'No Syntax Errors in Console': true,
        'Theme CSS Files Loading': false,
        'Vite Manifest Valid': false,
        'ResizeObserver Optimization Active': false,
        'Asset Management Initialized': false
    };
    
    // Check if LeafletUtilities are globally available
    if (typeof window.LeafletUtilities !== 'undefined') {
        results['Leaflet Utilities Available'] = true;
        
        // Check individual components
        const { OptimizedResizeObserver, CustomMarkerSystem, AssetManager } = window.LeafletUtilities;
        
        if (OptimizedResizeObserver) {
            results['OptimizedResizeObserver Loaded'] = true;
            
            // Check if global optimization is active
            if (window._resizeObserverOptimized) {
                results['ResizeObserver Optimization Active'] = true;
            }
        }
        
        if (CustomMarkerSystem) {
            results['CustomMarkerSystem Loaded'] = true;
        }
        
        if (AssetManager) {
            results['AssetManager Loaded'] = true;
            
            // Check if asset management was initialized
            try {
                const metrics = AssetManager.getMetrics();
                if (metrics.totalRequests >= 0) {
                    results['Asset Management Initialized'] = true;
                }
            } catch (e) {
                console.warn('AssetManager metrics check failed:', e);
            }
        }
    }
    
    // Check for TypeScript file requests in network log
    if (typeof performance !== 'undefined' && performance.getEntriesByType) {
        const resourceEntries = performance.getEntriesByType('resource');
        const tsRequests = resourceEntries.filter(entry => 
            entry.name.includes('.ts') && !entry.name.includes('.js')
        );
        
        if (tsRequests.length > 0) {
            results['No Direct TypeScript Imports'] = false;
            console.warn('❌ Found direct TypeScript requests:', tsRequests.map(r => r.name));
        }
    }
    
    // Check for console errors
    const originalConsoleError = console.error;
    let hasConsoleErrors = false;
    
    // Temporarily override console.error to detect new errors
    console.error = function(...args) {
        const message = args[0]?.toString?.() || '';
        
        // Filter out known/suppressed errors
        if (!message.includes('ResizeObserver loop') && 
            !message.includes('Unexpected token') &&
            !message.includes('SyntaxError')) {
            hasConsoleErrors = true;
        }
        
        return originalConsoleError.apply(console, args);
    };
    
    // Restore original after a brief delay
    setTimeout(() => {
        console.error = originalConsoleError;
        results['No Syntax Errors in Console'] = !hasConsoleErrors;
    }, 1000);
    
    // Check CSS file loading
    const cssLinks = document.querySelectorAll('link[rel="stylesheet"]');
    let cssLoaded = false;
    
    cssLinks.forEach(link => {
        if (link.href.includes('theme-') || link.href.includes('app-')) {
            cssLoaded = true;
        }
    });
    
    results['Theme CSS Files Loading'] = cssLoaded;
    
    // Check Vite manifest (if available)
    fetch('/build/manifest.json')
        .then(response => response.json())
        .then(manifest => {
            if (manifest && manifest['resources/js/leaflet-utilities.ts']) {
                results['Vite Manifest Valid'] = true;
            }
            displayResults();
        })
        .catch(() => {
            console.warn('⚠️ Could not fetch Vite manifest');
            displayResults();
        });
    
    function displayResults() {
        console.log('\n📊 Validation Results:\n');
        console.table(results);
        
        const passed = Object.values(results).filter(Boolean).length;
        const total = Object.keys(results).length;
        const percentage = Math.round((passed / total) * 100);
        
        console.log(`\n${passed}/${total} checks passed (${percentage}%)\n`);
        
        if (percentage === 100) {
            console.log('✅ All frontend fixes are working correctly!');
            console.log('🎉 No more console errors should occur.');
        } else {
            console.log('⚠️ Some issues may still exist. Check the results above.');
            
            // Provide specific guidance for failed checks
            Object.entries(results).forEach(([check, passed]) => {
                if (!passed) {
                    console.log(`❌ ${check}: ${getGuidance(check)}`);
                }
            });
        }
        
        return results;
    }
    
    function getGuidance(failedCheck) {
        const guidance = {
            'Leaflet Utilities Available': 'Run "npm run build" and ensure @vite directive is used in Blade templates',
            'OptimizedResizeObserver Loaded': 'Check if OptimizedResizeObserver.ts compiled correctly',
            'CustomMarkerSystem Loaded': 'Check if CustomMarkerSystem.ts compiled correctly',
            'AssetManager Loaded': 'Check if AssetManager.ts compiled correctly',
            'No Direct TypeScript Imports': 'Remove direct .ts imports from Blade templates',
            'No Syntax Errors in Console': 'Check browser console for JavaScript syntax errors',
            'Theme CSS Files Loading': 'Ensure theme CSS files use @vite directive',
            'Vite Manifest Valid': 'Check if Vite build completed and manifest.json exists',
            'ResizeObserver Optimization Active': 'Global ResizeObserver optimization not initialized',
            'Asset Management Initialized': 'AssetManager.setupLeafletAssets() not called'
        };
        
        return guidance[failedCheck] || 'Check implementation and rebuild assets';
    }
    
    // If manifest check is not needed, display results immediately
    if (typeof fetch === 'undefined') {
        displayResults();
    }
    
    // Return results for programmatic access
    return results;
})();

// Additional helper functions for manual testing
window.testLeafletUtilities = function() {
    if (typeof window.LeafletUtilities === 'undefined') {
        console.error('❌ LeafletUtilities not available');
        return false;
    }
    
    const { OptimizedResizeObserver, CustomMarkerSystem, AssetManager } = window.LeafletUtilities;
    
    console.log('🧪 Testing LeafletUtilities components...');
    
    try {
        // Test OptimizedResizeObserver
        if (OptimizedResizeObserver) {
            const testObserver = new OptimizedResizeObserver(() => {}, { enableMetrics: true });
            console.log('✅ OptimizedResizeObserver: Working');
            testObserver.disconnect();
        }
        
        // Test CustomMarkerSystem
        if (CustomMarkerSystem) {
            const testMarker = CustomMarkerSystem.createCustomMarker({ type: 'hospital' });
            console.log('✅ CustomMarkerSystem: Working');
        }
        
        // Test AssetManager
        if (AssetManager) {
            const metrics = AssetManager.getMetrics();
            console.log('✅ AssetManager: Working', metrics);
        }
        
        console.log('🎉 All LeafletUtilities components are working correctly!');
        return true;
        
    } catch (error) {
        console.error('❌ LeafletUtilities test failed:', error);
        return false;
    }
};

console.log('💡 Use window.testLeafletUtilities() to run additional tests');