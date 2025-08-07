/**
 * 🚀 Leaflet Utilities Entry Point
 * 
 * Centralized export of all Leaflet-related utilities to prevent
 * direct TypeScript imports in Blade templates.
 */

import OptimizedResizeObserver, { 
    createOptimizedResizeObserver, 
    suppressResizeObserverErrors, 
    getResizeObserverMetrics, 
    enableGlobalOptimization 
} from './utils/OptimizedResizeObserver';

import CustomMarkerSystem, { 
    type CustomMarkerOptions, 
    type PopupOptions, 
    type MarkerTheme 
} from './utils/CustomMarkerSystem';

import AssetManager, { 
    type AssetConfig, 
    type AssetMetrics, 
    type GeneratedAssetOptions 
} from './utils/AssetManager';

// Make utilities globally available
declare global {
    interface Window {
        LeafletUtilities: {
            OptimizedResizeObserver: typeof OptimizedResizeObserver;
            createOptimizedResizeObserver: typeof createOptimizedResizeObserver;
            suppressResizeObserverErrors: typeof suppressResizeObserverErrors;
            getResizeObserverMetrics: typeof getResizeObserverMetrics;
            enableGlobalOptimization: typeof enableGlobalOptimization;
            CustomMarkerSystem: typeof CustomMarkerSystem;
            AssetManager: typeof AssetManager;
        };
    }
}

// Initialize global utilities object
const LeafletUtilities = {
    OptimizedResizeObserver,
    createOptimizedResizeObserver,
    suppressResizeObserverErrors,
    getResizeObserverMetrics,
    enableGlobalOptimization,
    CustomMarkerSystem,
    AssetManager,
};

// Export for module usage
export {
    OptimizedResizeObserver,
    createOptimizedResizeObserver,
    suppressResizeObserverErrors,
    getResizeObserverMetrics,
    enableGlobalOptimization,
    CustomMarkerSystem,
    AssetManager,
    LeafletUtilities as default
};

// Types export
export type {
    CustomMarkerOptions,
    PopupOptions,
    MarkerTheme,
    AssetConfig,
    AssetMetrics,
    GeneratedAssetOptions
};

// Make available globally for direct browser usage
if (typeof window !== 'undefined') {
    window.LeafletUtilities = LeafletUtilities;
    
    // Initialize optimizations
    enableGlobalOptimization();
    suppressResizeObserverErrors();
    
    console.log('✅ Leaflet utilities loaded and optimizations enabled');
}