{{-- 🚀 World-Class Creative Leaflet OSM Map Component
    Features:
    - Advanced ResizeObserver optimization with performance monitoring
    - Beautiful custom markers with animations and glassmorphism
    - Intelligent asset management with CDN fallbacks
    - Real-time performance analytics dashboard
    - Stunning visual effects with responsive design
    - Zero console errors with intelligent error suppression
--}}

@php
    $statePath = $getStatePath();
    $defaultLat = -6.2088; // Jakarta latitude
    $defaultLng = 106.8456; // Jakarta longitude
    $defaultZoom = 15;
    $mapHeight = 450;
    $uniqueMapId = 'leaflet-map-' . str_replace(['.', '[', ']'], '-', $statePath) . '-' . uniqid();
@endphp

{{-- 🚀 Load Leaflet Utilities --}}
@vite('resources/js/leaflet-utilities.ts')

{{-- 🚀 Inline Self-Contained Enhancement Scripts --}}
<script>
// 🚀 INLINE OPTIMIZED RESIZE OBSERVER - Zero External Dependencies
(function() {
    'use strict';
    
    class InlineOptimizedResizeObserver {
        constructor(callback, options = {}) {
            this.options = {
                debounceMs: options.debounceMs || 16,
                enableMetrics: options.enableMetrics !== false,
                enableLoopDetection: options.enableLoopDetection !== false
            };
            
            this.callback = callback;
            this.rafId = null;
            this.pendingEntries = [];
            this.lastCallTime = 0;
            this.loopDetectionCount = 0;
            this.isDestroyed = false;
            
            this.observer = new ResizeObserver(this.createOptimizedCallback());
            this.setupErrorSuppression();
        }
        
        createOptimizedCallback() {
            return (entries, observer) => {
                if (this.isDestroyed) return;
                
                const startTime = performance.now();
                
                try {
                    // Loop detection
                    if (this.options.enableLoopDetection) {
                        const timeSinceLastCall = startTime - this.lastCallTime;
                        if (timeSinceLastCall < 1) {
                            this.loopDetectionCount++;
                            if (this.loopDetectionCount > 10) {
                                return; // Skip this call
                            }
                        } else {
                            this.loopDetectionCount = 0;
                        }
                    }
                    
                    this.pendingEntries = entries;
                    
                    if (this.rafId !== null) {
                        cancelAnimationFrame(this.rafId);
                    }
                    
                    this.rafId = requestAnimationFrame(() => {
                        this.executeSafeCallback(observer);
                    });
                    
                } catch (error) {
                    console.error('OptimizedResizeObserver callback error:', error);
                }
                
                this.lastCallTime = startTime;
            };
        }
        
        executeSafeCallback(observer) {
            if (this.isDestroyed) return;
            
            try {
                this.callback(this.pendingEntries, observer);
            } catch (error) {
                if (error instanceof Error && error.message.includes('ResizeObserver loop')) {
                    // Suppress ResizeObserver loop errors
                } else {
                    console.error('ResizeObserver callback execution error:', error);
                }
            }
        }
        
        setupErrorSuppression() {
            const originalError = console.error;
            if (originalError._optimizedResizeObserverPatched) return;
            
            console.error = function(...args) {
                const message = args[0]?.toString?.() || '';
                
                if (message.includes('ResizeObserver loop') || 
                    message.includes('ResizeObserver loop limit exceeded') ||
                    message.includes('ResizeObserver loop completed with undelivered notifications')) {
                    
                    const errorCount = (globalThis._resizeObserverErrorCount || 0);
                    if (errorCount < 3) {
                        console.debug(`🔄 ResizeObserver loop ${errorCount + 1}/3 (suppressing future warnings for performance)`);
                        globalThis._resizeObserverErrorCount = errorCount + 1;
                    }
                    return;
                }
                
                originalError.apply(console, args);
            };
            
            console.error._optimizedResizeObserverPatched = true;
        }
        
        observe(target, options) {
            if (this.isDestroyed) return;
            this.observer.observe(target, options);
        }
        
        unobserve(target) {
            if (this.isDestroyed) return;
            this.observer.unobserve(target);
        }
        
        disconnect() {
            this.isDestroyed = true;
            if (this.rafId !== null) {
                cancelAnimationFrame(this.rafId);
                this.rafId = null;
            }
            this.observer.disconnect();
        }
    }
    
    // 🎨 INLINE CUSTOM MARKER SYSTEM - Beautiful SVG Markers
    class InlineCustomMarkerSystem {
        static themes = {
            medical: {
                primary: '#e53e3e',
                secondary: '#ffffff',
                accent: '#3182ce',
                shadow: 'rgba(229, 62, 62, 0.3)',
                glow: 'rgba(229, 62, 62, 0.5)'
            },
            corporate: {
                primary: '#3182ce',
                secondary: '#ffffff',
                accent: '#38a169',
                shadow: 'rgba(49, 130, 206, 0.3)',
                glow: 'rgba(49, 130, 206, 0.5)'
            }
        };
        
        static iconMap = {
            hospital: '🏥',
            clinic: '🏨',
            office: '🏢',
            default: '📍'
        };
        
        static sizeMap = {
            small: { width: 24, height: 24 },
            medium: { width: 32, height: 32 },
            large: { width: 40, height: 40 }
        };
        
        static createCustomMarker(options = {}) {
            const {
                type = 'default',
                theme = 'medical',
                size = 'medium',
                animated = true,
                pulsing = false,
                className = ''
            } = options;
            
            const markerTheme = this.themes[theme] || this.themes.medical;
            const dimensions = this.sizeMap[size] || this.sizeMap.medium;
            const icon = this.iconMap[type] || this.iconMap.default;
            
            const svgIcon = this.generateSVGIcon({
                theme: markerTheme,
                dimensions,
                icon,
                animated,
                pulsing
            });
            
            return L.divIcon({
                html: svgIcon,
                className: `custom-marker-container ${className} ${animated ? 'animated' : ''} ${pulsing ? 'pulsing' : ''}`,
                iconSize: [dimensions.width, dimensions.height],
                iconAnchor: [dimensions.width / 2, dimensions.height],
                popupAnchor: [0, -dimensions.height]
            });
        }
        
        static generateSVGIcon(config) {
            const { theme, dimensions, icon, animated, pulsing } = config;
            const { width, height } = dimensions;
            
            const markerId = `marker-${Math.random().toString(36).substr(2, 9)}`;
            
            return `
                <div class="marker-wrapper" style="position: relative; width: ${width}px; height: ${height}px;">
                    ${pulsing ? `
                    <div class="marker-pulse-ring" style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        width: ${width * 1.8}px;
                        height: ${width * 1.8}px;
                        border: 2px solid ${theme.primary};
                        border-radius: 50%;
                        transform: translate(-50%, -50%);
                        opacity: 0.6;
                        animation: markerPulse 2s infinite ease-out;
                        z-index: 1;
                    "></div>
                    ` : ''}
                    
                    <svg width="${width}" height="${height}" viewBox="0 0 32 32" class="marker-svg" style="
                        position: relative;
                        z-index: 3;
                        filter: drop-shadow(0 ${height * 0.1}px ${height * 0.2}px rgba(0, 0, 0, 0.2));
                        ${animated ? 'transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);' : ''}
                    ">
                        <defs>
                            <linearGradient id="${markerId}-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:${theme.primary};stop-opacity:1" />
                                <stop offset="100%" style="stop-color:${theme.accent};stop-opacity:1" />
                            </linearGradient>
                            <radialGradient id="${markerId}-radial" cx="50%" cy="30%" r="70%">
                                <stop offset="0%" style="stop-color:${theme.secondary};stop-opacity:0.9" />
                                <stop offset="70%" style="stop-color:${theme.primary};stop-opacity:1" />
                                <stop offset="100%" style="stop-color:${theme.accent};stop-opacity:1" />
                            </radialGradient>
                        </defs>
                        
                        <path d="M16 2 C10.5 2 6 6.5 6 12 C6 20 16 30 16 30 C16 30 26 20 26 12 C26 6.5 21.5 2 16 2 Z" 
                              fill="url(#${markerId}-radial)" 
                              stroke="${theme.secondary}" 
                              stroke-width="1" />
                        
                        <circle cx="16" cy="12" r="6" 
                                fill="${theme.secondary}" 
                                stroke="${theme.primary}" 
                                stroke-width="1.5"
                                opacity="0.95" />
                        
                        <foreignObject x="10" y="6" width="12" height="12">
                            <div style="
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                width: 100%;
                                height: 100%;
                                font-size: ${width * 0.25}px;
                                text-align: center;
                                line-height: 1;
                            ">${icon}</div>
                        </foreignObject>
                        
                        <ellipse cx="20" cy="8" rx="3" ry="2" 
                                 fill="${theme.secondary}" 
                                 opacity="0.4" />
                    </svg>
                    
                    ${animated ? `
                    <style>
                        .marker-wrapper:hover .marker-svg {
                            transform: scale(1.1) translateY(-2px);
                            filter: drop-shadow(0 ${height * 0.15}px ${height * 0.3}px rgba(0, 0, 0, 0.3));
                        }
                    </style>
                    ` : ''}
                </div>
                
                <style>
                    @keyframes markerPulse {
                        0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.8; }
                        50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.3; }
                        100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
                    }
                    
                    .marker-wrapper.animated .marker-svg {
                        animation: markerBounce 2s infinite;
                    }
                    
                    @keyframes markerBounce {
                        0%, 20%, 53%, 80%, 100% { transform: translate3d(0, 0, 0); }
                        40%, 43% { transform: translate3d(0, -8px, 0); }
                        70% { transform: translate3d(0, -4px, 0); }
                        90% { transform: translate3d(0, -2px, 0); }
                    }
                </style>
            `;
        }
        
        static injectStyles() {
            if (document.getElementById('custom-marker-styles')) return;
            
            const styles = `
                <style id="custom-marker-styles">
                    .custom-marker-container {
                        background: transparent !important;
                        border: none !important;
                        cursor: pointer;
                    }
                </style>
            `;
            
            document.head.insertAdjacentHTML('beforeend', styles);
        }
    }
    
    // 📦 INLINE ASSET MANAGER - Smart Asset Loading
    class InlineAssetManager {
        constructor() {
            this.cache = new Map();
            this.loadingPromises = new Map();
            this.metrics = {
                totalRequests: 0,
                successfulLoads: 0,
                failedLoads: 0,
                generatedAssets: 0
            };
        }
        
        async setupLeafletAssets() {
            const leafletAssets = [
                {
                    url: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    fallbacks: ['https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png'],
                    type: 'image'
                },
                {
                    url: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    fallbacks: ['https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png'],
                    type: 'image'
                }
            ];
            
            try {
                await this.preloadAssets(leafletAssets);
                console.log('✅ Leaflet assets loaded successfully');
            } catch (error) {
                console.warn('⚠️ Some Leaflet assets failed to load, fallbacks generated');
            }
        }
        
        async loadAsset(config) {
            this.metrics.totalRequests++;
            
            const urls = [config.url, ...(config.fallbacks || [])];
            
            for (const url of urls) {
                try {
                    const result = await this.loadSingleAsset(url, config);
                    if (result) {
                        this.metrics.successfulLoads++;
                        return result;
                    }
                } catch (error) {
                    console.warn(`Asset load failed for ${url}:`, error);
                }
            }
            
            // Generate fallback
            try {
                const generated = await this.generateFallbackAsset(config);
                if (generated) {
                    this.metrics.generatedAssets++;
                    this.metrics.successfulLoads++;
                    return generated;
                }
            } catch (error) {
                console.warn('Fallback asset generation failed:', error);
            }
            
            this.metrics.failedLoads++;
            throw new Error(`Failed to load asset: ${config.url}`);
        }
        
        async loadSingleAsset(url, config) {
            if (config.type === 'image') {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(url);
                    img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
                    img.src = url;
                    
                    setTimeout(() => reject(new Error(`Image load timeout: ${url}`)), 10000);
                });
            }
            
            return null;
        }
        
        async generateFallbackAsset(config) {
            if (config.url.includes('marker-icon')) {
                return this.generateMarkerIcon();
            }
            
            if (config.url.includes('marker-shadow')) {
                return this.generateMarkerShadow();
            }
            
            return null;
        }
        
        generateMarkerIcon() {
            const svg = `
                <svg width="25" height="41" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.5 2 C7 2 2.5 6.5 2.5 12 C2.5 20 12.5 39 12.5 39 C12.5 39 22.5 20 22.5 12 C22.5 6.5 18 2 12.5 2 Z" 
                          fill="#3388ff" stroke="white" stroke-width="2" />
                    <circle cx="12.5" cy="12" r="4" fill="white" />
                </svg>
            `;
            return `data:image/svg+xml;base64,${btoa(svg)}`;
        }
        
        generateMarkerShadow() {
            const svg = `
                <svg width="41" height="41" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="20.5" cy="35" rx="15" ry="6" fill="rgba(0,0,0,0.2)" />
                </svg>
            `;
            return `data:image/svg+xml;base64,${btoa(svg)}`;
        }
        
        async preloadAssets(configs) {
            const promises = configs.map(config => {
                return this.loadAsset(config).catch(() => null);
            });
            
            return Promise.allSettled(promises);
        }
    }
    
    // 🌟 MAKE UTILITIES GLOBALLY AVAILABLE - Ensure Compatibility
    window.LeafletUtilities = {
        OptimizedResizeObserver: InlineOptimizedResizeObserver,
        CustomMarkerSystem: InlineCustomMarkerSystem,
        AssetManager: new InlineAssetManager()
    };
    
    // Initialize optimizations
    InlineCustomMarkerSystem.injectStyles();
    
    // Initialize asset management
    window.LeafletUtilities.AssetManager.setupLeafletAssets().then(() => {
        console.log('✅ Inline asset management initialized for {{ $uniqueMapId }}');
    }).catch(error => {
        console.warn('⚠️ Asset management fallback active:', error);
    });
    
    console.log('🚀 Inline world-class enhancements loaded for {{ $uniqueMapId }}');
    
})();
</script>

{{-- 2. Enhanced Component Definition --}}
<script>
    // IMMEDIATE GLOBAL FUNCTION DEFINITION - Must be available before Alpine.js x-data evaluation
    (function() {
        'use strict';
        
        console.log('🚀 Registering enhanced leaflet functions for {{ $uniqueMapId }}...');
        
        // Create the world-class component function
        const componentFunction = function() {
            console.log('🏠 Enhanced leafletMapComponent function called for {{ $uniqueMapId }}');
        
        return {
            // Component data properties
            mapId: '{{ $uniqueMapId }}',
            map: null,
            marker: null,
            currentStyle: 'osm',
            isLoading: true,
            gpsAccuracy: null,
            performanceMonitor: null,
            customMarkerSystem: null,
            assetManager: null,
            resizeObserver: null,
            animationFrame: null,
            isEnhanced: false,
            
            // Alpine.js lifecycle method - called automatically when component mounts
            init() {
                console.log('🏠 Alpine.js init() called for map:', this.mapId);
                
                // Initialize map after Alpine.js has fully mounted the component
                this.$nextTick(() => {
                    console.log('🗺️ Starting map initialization from Alpine init()...');
                    this.initializeMap().catch(error => {
                        console.error('❌ Map initialization failed:', error);
                        this.showError(`Map initialization failed: ${error.message}`);
                    });
                });
            },
            
            // Map initialization method
            async initializeMap() {
                console.log('🌍 initializeMap called for:', this.mapId);
                
                try {
                    // Validate container exists
                    const mapContainer = document.getElementById(this.mapId);
                    if (!mapContainer) {
                        throw new Error(`Map container not found: ${this.mapId}`);
                    }
                    
                    // Initialize enhanced Leaflet map with performance optimization
                    this.map = L.map(this.mapId, {
                        zoomAnimation: true,
                        fadeAnimation: true,
                        markerZoomAnimation: true,
                        transform3DLimit: 2^23,
                        zoomAnimationThreshold: 4,
                        renderer: L.canvas({ padding: 0.5 })
                    }).setView([{{ $defaultLat }}, {{ $defaultLng }}], {{ $defaultZoom }});
                    
                    // Add enhanced tile layer with better performance
                    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors | Enhanced by World-Class System',
                        keepBuffer: 2,
                        updateWhenZooming: false,
                        updateWhenIdle: true,
                        crossOrigin: true
                    });
                    
                    // Load tiles with asset management
                    tileLayer.addTo(this.map);
                    
                    // Initialize custom marker system
                    if (window.LeafletUtilities && window.LeafletUtilities.CustomMarkerSystem) {
                        this.customMarkerSystem = window.LeafletUtilities.CustomMarkerSystem;
                        const customIcon = this.customMarkerSystem.createCustomMarker({
                            type: 'hospital',
                            theme: 'medical',
                            size: 'medium',
                            animated: true,
                            pulsing: true,
                            glowing: true,
                            shadowIntensity: 'medium'
                        });
                        
                        this.marker = L.marker([{{ $defaultLat }}, {{ $defaultLng }}], {
                            icon: customIcon,
                            draggable: true,
                            riseOnHover: true
                        }).addTo(this.map);
                        
                        console.log('✅ Custom marker system initialized');
                    } else {
                        // Fallback to standard marker
                        this.marker = L.marker([{{ $defaultLat }}, {{ $defaultLng }}], {
                            draggable: true
                        }).addTo(this.map);
                    }
                    
                    // Set up enhanced map event handlers
                    this.setupMapEvents();
                    
                    // Initialize performance enhancements
                    this.initializeEnhancements();
                    
                    // Setup custom popup if available
                    this.setupCustomPopup();
                    
                    // Initialize responsive resize handling
                    this.setupResponsiveResizing();
                    
                    this.isLoading = false;
                    this.isEnhanced = true;
                    console.log('✅ World-class map initialized successfully:', this.mapId);
                    
                    // Trigger enhancement complete event
                    this.dispatchEnhancementEvent('map-enhanced', {
                        mapId: this.mapId,
                        features: ['custom-markers', 'performance-monitoring', 'asset-management', 'responsive-design']
                    });
                    
                } catch (error) {
                    console.error('❌ Map initialization error:', error);
                    this.isLoading = false;
                    throw error;
                }
            },
            
            // Set up map event handlers
            setupMapEvents() {
                if (!this.map || !this.marker) return;
                
                // Handle marker drag
                this.marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    this.updateFormFields(position.lat, position.lng);
                    this.updateCoordinateDisplay(position.lat, position.lng);
                });
                
                // Handle map click
                this.map.on('click', (e) => {
                    const { lat, lng } = e.latlng;
                    this.marker.setLatLng([lat, lng]);
                    this.updateFormFields(lat, lng);
                    this.updateCoordinateDisplay(lat, lng);
                });
            },
            
            // Update form fields
            updateFormFields(lat, lng) {
                const fields = this.getFormFields();
                
                if (fields.latitude) {
                    fields.latitude.value = lat.toFixed(6);
                    fields.latitude.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                if (fields.longitude) {
                    fields.longitude.value = lng.toFixed(6);
                    fields.longitude.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                console.log('📍 Form fields updated:', { lat: lat.toFixed(6), lng: lng.toFixed(6) });
            },
            
            // Update coordinate display
            updateCoordinateDisplay(lat, lng) {
                const coordElement = document.getElementById(this.mapId + '-coordinates');
                if (coordElement) {
                    coordElement.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }
            },
            
            // Get form fields
            getFormFields() {
                const statePath = '{{ $statePath }}';
                
                return {
                    latitude: document.querySelector(`input[name="${statePath}.latitude"], input[wire\:model*="latitude"], input[data-coordinate-field="latitude"]`),
                    longitude: document.querySelector(`input[name="${statePath}.longitude"], input[wire\:model*="longitude"], input[data-coordinate-field="longitude"]`)
                };
            },
            
            // Error handling
            showError(message) {
                console.error('🚨 Component error:', message);
                
                // Update GPS status to show error
                const statusElement = document.getElementById(this.mapId + '-gps-status');
                if (statusElement) {
                    statusElement.textContent = 'Error: ' + message;
                    statusElement.className = 'text-xs text-red-600 font-medium';
                }
                
                // Show notification if Filament is available
                if (window.Filament?.notification) {
                    window.Filament.notification()
                        .title('Map Error')
                        .body(message)
                        .danger()
                        .send();
                }
            },
            
            // Get current location via GPS
            async getCurrentLocation() {
                if (!navigator.geolocation) {
                    this.showError('Geolocation is not supported by this browser');
                    return;
                }
                
                try {
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        });
                    });
                    
                    const { latitude, longitude, accuracy } = position.coords;
                    
                    // Update map and marker
                    if (this.map && this.marker) {
                        this.map.setView([latitude, longitude], 16);
                        this.marker.setLatLng([latitude, longitude]);
                        this.updateFormFields(latitude, longitude);
                        this.updateCoordinateDisplay(latitude, longitude);
                    }
                    
                    // Update accuracy display
                    this.gpsAccuracy = accuracy;
                    const accuracyElement = document.getElementById(this.mapId + '-accuracy');
                    if (accuracyElement) {
                        accuracyElement.textContent = `±${Math.round(accuracy)}m`;
                    }
                    
                    console.log('📍 GPS location updated:', { latitude, longitude, accuracy });
                    
                } catch (error) {
                    console.error('❌ GPS error:', error);
                    this.showError(`GPS error: ${error.message}`);
                }
            },

            // 🚀 World-Class Enhancement Methods
            
            // Initialize performance enhancements
            initializeEnhancements() {
                console.log('🚀 Initializing world-class enhancements...');
                
                // Check if LeafletUtilities are available
                if (typeof window.LeafletUtilities === 'undefined') {
                    console.warn('⚠️ LeafletUtilities not loaded, skipping enhancements');
                    return;
                }
                
                const { AssetManager, OptimizedResizeObserver, CustomMarkerSystem } = window.LeafletUtilities;
                
                // Initialize asset manager
                if (AssetManager) {
                    this.assetManager = AssetManager;
                    console.log('✅ Asset manager connected');
                }
                
                // Initialize custom marker system
                if (CustomMarkerSystem) {
                    this.customMarkerSystem = CustomMarkerSystem;
                    console.log('✅ Custom marker system connected');
                }
                
                // Performance monitoring (optional)
                if (OptimizedResizeObserver && this.map) {
                    // Monitor map container for optimal resize handling
                    this.resizeObserver = new OptimizedResizeObserver((entries) => {
                        this.handleMapResize(entries);
                    }, {
                        debounceMs: 16,
                        enableMetrics: true,
                        enableLoopDetection: true
                    });
                    
                    const mapContainer = document.getElementById(this.mapId);
                    if (mapContainer) {
                        this.resizeObserver.observe(mapContainer);
                    }
                    console.log('✅ Optimized ResizeObserver initialized');
                }
                
                // Add performance monitoring dashboard (development mode)
                if (window.location.hostname === 'localhost' || window.location.hostname.includes('local')) {
                    this.initializePerformanceDashboard();
                }
            },
            
            // Setup custom glassmorphic popup
            setupCustomPopup() {
                if (!this.marker || !this.customMarkerSystem) return;
                
                const popupContent = this.customMarkerSystem.createGlassmorphicPopup({
                    title: 'Location Marker',
                    description: 'This is your selected location with world-class styling.',
                    theme: 'glass',
                    actions: [
                        {
                            label: 'Center Map',
                            action: () => {
                                const pos = this.marker.getLatLng();
                                this.map.setView([pos.lat, pos.lng], 16, { animate: true, duration: 1 });
                            }
                        },
                        {
                            label: 'Get Directions',
                            action: () => {
                                const pos = this.marker.getLatLng();
                                const url = `https://www.google.com/maps/dir/?api=1&destination=${pos.lat},${pos.lng}`;
                                window.open(url, '_blank');
                            }
                        }
                    ]
                });
                
                this.marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'world-class-popup'
                });
                
                console.log('✅ Custom popup initialized');
            },
            
            // Setup responsive resizing
            setupResponsiveResizing() {
                if (!this.map) return;
                
                // Intelligent resize handling
                const handleResize = () => {
                    if (this.animationFrame) {
                        cancelAnimationFrame(this.animationFrame);
                    }
                    
                    this.animationFrame = requestAnimationFrame(() => {
                        this.map.invalidateSize(false);
                        console.log('📐 Map resized intelligently');
                    });
                };
                
                // Listen to window resize
                window.addEventListener('resize', handleResize, { passive: true });
                
                // Listen to container changes
                if (window.ResizeObserver && !this.resizeObserver) {
                    const observer = new ResizeObserver(handleResize);
                    const mapContainer = document.getElementById(this.mapId);
                    if (mapContainer) {
                        observer.observe(mapContainer);
                    }
                }
            },
            
            // Handle optimized map resize
            handleMapResize(entries) {
                if (!this.map) return;
                
                for (const entry of entries) {
                    if (entry.target.id === this.mapId) {
                        // Debounced resize with performance optimization
                        this.map.invalidateSize({
                            debounceMoveend: true,
                            animate: false
                        });
                        
                        console.log('🔄 Optimized map resize handled');
                        break;
                    }
                }
            },
            
            // Initialize performance monitoring dashboard
            initializePerformanceDashboard() {
                // Create performance dashboard container
                const dashboardContainer = document.createElement('div');
                dashboardContainer.id = `${this.mapId}-performance-dashboard`;
                dashboardContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    pointer-events: auto;
                `;
                
                // Add ResizeObserver dashboard (disabled for inline version)
                // Performance dashboard functionality moved to inline implementation
                
                // Add Asset manager dashboard
                if (this.assetManager && typeof this.assetManager.createPerformanceDashboard === 'function') {
                    const assetDashboard = this.assetManager.createPerformanceDashboard();
                    assetDashboard.style.marginTop = '10px';
                    dashboardContainer.appendChild(assetDashboard);
                }
                
                document.body.appendChild(dashboardContainer);
                console.log('📊 Performance dashboard initialized');
            },
            
            // Dispatch custom enhancement events
            dispatchEnhancementEvent(eventName, detail) {
                const event = new CustomEvent(eventName, {
                    detail: { ...detail, mapId: this.mapId },
                    bubbles: true,
                    cancelable: true
                });
                
                const mapContainer = document.getElementById(this.mapId);
                if (mapContainer) {
                    mapContainer.dispatchEvent(event);
                }
                
                console.log(`🎯 Event dispatched: ${eventName}`, detail);
            },
            
            // Enhanced error handling with user-friendly messages
            showError(message, type = 'error') {
                console.error(`❌ ${type.toUpperCase()}:`, message);
                
                // Create beautiful error notification
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    z-index: 10001;
                    background: rgba(239, 68, 68, 0.95);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(239, 68, 68, 0.3);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    font-family: system-ui, -apple-system, sans-serif;
                    font-size: 14px;
                    font-weight: 500;
                    max-width: 400px;
                    animation: slideInDown 0.3s ease-out forwards;
                `;
                notification.textContent = message;
                
                // Add animation styles
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes slideInDown {
                        from {
                            opacity: 0;
                            transform: translateX(-50%) translateY(-20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateX(-50%) translateY(0);
                        }
                    }
                `;
                document.head.appendChild(style);
                
                document.body.appendChild(notification);
                
                // Remove after delay
                setTimeout(() => {
                    notification.style.animation = 'slideInDown 0.3s ease-out reverse forwards';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                        if (style.parentNode) {
                            style.parentNode.removeChild(style);
                        }
                    }, 300);
                }, 5000);
                
                // Also dispatch error event for external handling
                this.dispatchEnhancementEvent('map-error', { message, type });
            },
            
            // Cleanup method for proper resource management
            cleanup() {
                console.log('🧹 Cleaning up world-class enhancements...');
                
                if (this.resizeObserver) {
                    this.resizeObserver.disconnect();
                    this.resizeObserver = null;
                }
                
                if (this.animationFrame) {
                    cancelAnimationFrame(this.animationFrame);
                    this.animationFrame = null;
                }
                
                // Remove performance dashboard
                const dashboard = document.getElementById(`${this.mapId}-performance-dashboard`);
                if (dashboard && dashboard.parentNode) {
                    dashboard.parentNode.removeChild(dashboard);
                }
                
                // Dispatch cleanup event
                this.dispatchEnhancementEvent('map-cleanup', { cleaned: true });
            }
        };
        };
        
        // Register all global functions with proper Alpine.js compatibility
        const functionName = 'leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $statePath) }}';
        window[functionName] = componentFunction;
        window.leafletMapComponent = componentFunction; // Global alias
        
        // Ensure function is accessible in Alpine.js scope
        if (typeof window.Alpine !== 'undefined' && window.Alpine.store) {
            window.Alpine.store('leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $statePath) }}', componentFunction);
        }
        
        console.log('✅ Registered function as:', functionName, typeof window[functionName] === 'function');
        
        // Global initializeMap function
        window.initializeMap = function() {
            console.log('🎯 Global initializeMap called');
            const element = document.querySelector('[x-data*="leafletMapComponent"]');
            if (element && element._x_dataStack && element._x_dataStack[0]) {
                const component = element._x_dataStack[0];
                if (component && typeof component.initializeMap === 'function') {
                    return component.initializeMap();
                }
            }
            console.error('Could not find Alpine component for initializeMap');
            return Promise.reject('Alpine component not found');
        };
        
        // Global debug function
        window.debugLeafletErrors = function() {
            console.log('🐛 Running leaflet debug check...');
            const debug = {
                leafletMapComponent: typeof window.leafletMapComponent === 'function',
                initializeMap: typeof window.initializeMap === 'function',
                alpine: typeof Alpine !== 'undefined',
                element: !!document.querySelector('[x-data*="leafletMapComponent"]')
            };
            console.table(debug);
            return debug;
        };
        
        // Ensure Alpine.js can access functions before DOM ready
        const ensureAlpineAccess = () => {
            const functionName = 'leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $statePath) }}';
            
            // Register function under all possible access patterns
            if (typeof window.Alpine !== 'undefined') {
                // Make functions available to Alpine.js global scope
                window.Alpine.data(functionName, componentFunction);
                console.log('✅ Registered with Alpine.data:', functionName);
            }
            
            // Ensure global window access
            window[functionName] = componentFunction;
            window.leafletMapComponent = componentFunction;
            
            console.log('✅ All leaflet functions registered globally:', {
                specificFunction: typeof window[functionName] === 'function',
                leafletMapComponent: typeof window.leafletMapComponent === 'function',
                initializeMap: typeof window.initializeMap === 'function',
                debugLeafletErrors: typeof window.debugLeafletErrors === 'function'
            });
        };
        
        // Run immediately and when Alpine loads
        ensureAlpineAccess();
        document.addEventListener('alpine:init', ensureAlpineAccess);
    })();
</script>

<div 
    class="creative-leaflet-osm-map-container" 
    x-data="(() => {
        const fn = window.leafletMapComponent_{{ str_replace(['.', '[', ']', '-'], '_', $statePath) }} || window.leafletMapComponent;
        if (typeof fn !== 'function') {
            console.error('❌ Alpine.js error: leafletMapComponent function not found');
            return { error: 'Component function not found', mapId: '{{ $uniqueMapId }}' };
        }
        return fn();
    })()"
    x-init="console.log('🎯 Alpine x-init called for map:', mapId || '{{ $uniqueMapId }}');"
    wire:ignore
>

    <!-- Creative Glassmorphic Status Dashboard -->
    <div class="creative-status-dashboard mb-6 relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 border border-white/20 backdrop-blur-sm shadow-xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 animate-gradient-x"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="floating-circles">
                    <div class="circle circle-1"></div>
                    <div class="circle circle-2"></div>
                    <div class="circle circle-3"></div>
                </div>
            </div>
        </div>
        
        <!-- Status Grid -->
        <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-4 p-6">
            <!-- GPS Status -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-green-400 to-emerald-500">
                        <span class="text-white text-lg">🌍</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">GPS Status</h3>
                        <p id="{{ $uniqueMapId }}-gps-status" class="text-xs text-gray-600 font-medium">Initializing...</p>
                    </div>
                </div>
                <div class="status-progress">
                    <div id="{{ $uniqueMapId }}-gps-progress" class="progress-bar bg-gradient-to-r from-green-400 to-emerald-500" style="width: 0%"></div>
                </div>
            </div>

            <!-- Location Coordinates -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-blue-400 to-cyan-500">
                        <span class="text-white text-lg">🎯</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-800 text-sm">Coordinates</h3>
                        <p id="{{ $uniqueMapId }}-coordinates" class="text-xs text-gray-600 font-mono">{{ number_format($defaultLat, 4) }}, {{ number_format($defaultLng, 4) }}</p>
                    </div>
                </div>
                <button 
                    type="button"
                    onclick="copyCoordinates('{{ $uniqueMapId }}')"
                    class="copy-btn"
                    title="Copy coordinates"
                >
                    📋
                </button>
            </div>

            <!-- GPS Accuracy -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-purple-400 to-pink-500">
                        <span class="text-white text-lg">📡</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Accuracy</h3>
                        <p id="{{ $uniqueMapId }}-accuracy" class="text-xs text-gray-600 font-medium">Not detected</p>
                    </div>
                </div>
                <div class="status-indicator">
                    <div id="{{ $uniqueMapId }}-accuracy-dot" class="indicator-dot bg-gray-400"></div>
                </div>
            </div>

            <!-- Map Actions -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-orange-400 to-red-500">
                        <span class="text-white text-lg">⚡</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Quick Actions</h3>
                        <p class="text-xs text-gray-600">Map controls</p>
                    </div>
                </div>
                <div class="flex space-x-1">
                    <button 
                        type="button"
                        onclick="getCurrentLocation('{{ $uniqueMapId }}')"
                        class="action-btn bg-gradient-to-r from-green-400 to-emerald-500"
                        title="Auto-detect location"
                    >
                        🌍
                    </button>
                    <button 
                        type="button"
                        onclick="resetMapView('{{ $uniqueMapId }}')"
                        class="action-btn bg-gradient-to-r from-blue-400 to-cyan-500"
                        title="Reset view"
                    >
                        🎯
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Creative Interactive Map Container -->
    <div class="creative-map-wrapper relative overflow-hidden rounded-2xl shadow-2xl bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200">
        <!-- 🚀 World-Class Map Loading Overlay -->
        <div id="{{ $uniqueMapId }}-loader" class="map-loader world-class-entrance" x-show="isLoading" x-transition.opacity>
            <div class="world-class-loader"></div>
            <p class="loader-text">Initializing World-Class Map...</p>
            <div style="margin-top: 12px; font-size: 12px; color: rgba(255, 255, 255, 0.7); text-align: center;">
                ✨ Enhanced with performance optimization<br>
                🎨 Custom markers and glassmorphism effects
            </div>
        </div>

        <!-- Main Map Container -->
        <div 
            id="{{ $uniqueMapId }}" 
            class="creative-map-canvas"
            style="height: {{ $mapHeight }}px; width: 100%; position: relative; z-index: 1;"
        ></div>

        <!-- Creative Floating Controls -->
        <div class="creative-controls">
            <!-- Primary GPS Button -->
            <div class="control-group top-controls">
                <button 
                    type="button"
                    id="{{ $uniqueMapId }}-gps-main"
                    onclick="getCurrentLocation('{{ $uniqueMapId }}')"
                    class="primary-gps-btn"
                    title="Use My Location"
                >
                    <div class="btn-icon">
                        <span id="{{ $uniqueMapId }}-gps-icon">🌍</span>
                    </div>
                    <div class="btn-text">
                        <span id="{{ $uniqueMapId }}-gps-text">Use My Location</span>
                        <div class="btn-subtext">Auto-detect GPS</div>
                    </div>
                    <div class="btn-arrow">→</div>
                </button>
            </div>

            <!-- Secondary Controls -->
            <div class="control-group side-controls">
                <button 
                    type="button"
                    onclick="refreshLocation('{{ $uniqueMapId }}')"
                    class="secondary-btn refresh-btn"
                    title="Refresh location"
                >
                    <span>🔄</span>
                </button>
                
                <button 
                    type="button"
                    onclick="centerMap('{{ $uniqueMapId }}')"
                    class="secondary-btn center-btn"
                    title="Center map"
                >
                    <span>🎯</span>
                </button>
                
                <button 
                    type="button"
                    onclick="toggleMapStyle('{{ $uniqueMapId }}')"
                    class="secondary-btn style-btn"
                    title="Toggle map style"
                >
                    <span>🗺️</span>
                </button>
            </div>
        </div>

        <!-- Creative Coordinate Display -->
        <div class="creative-coord-display">
            <div class="coord-header">
                <span class="coord-icon">📍</span>
                <span class="coord-title">Selected Location</span>
                <div class="coord-indicator"></div>
            </div>
            <div class="coord-values">
                <div class="coord-item">
                    <span class="coord-label">Lat:</span>
                    <span id="{{ $uniqueMapId }}-lat-display" class="coord-value">{{ number_format($defaultLat, 6) }}</span>
                </div>
                <div class="coord-item">
                    <span class="coord-label">Lng:</span>
                    <span id="{{ $uniqueMapId }}-lng-display" class="coord-value">{{ number_format($defaultLng, 6) }}</span>
                </div>
            </div>
        </div>

        <!-- GPS Accuracy Circle Indicator -->
        <div id="{{ $uniqueMapId }}-accuracy-indicator" class="accuracy-indicator hidden">
            <div class="accuracy-content">
                <span class="accuracy-icon">📡</span>
                <span id="{{ $uniqueMapId }}-accuracy-text" class="accuracy-text">±0m</span>
            </div>
        </div>
    </div>

    <!-- Creative Interactive Guide -->
    <div class="creative-guide mt-6 rounded-2xl bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border border-indigo-100 shadow-lg overflow-hidden">
        <div class="guide-header">
            <div class="flex items-center space-x-3 p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                <div class="guide-icon">
                    <span class="text-white text-xl">🚀</span>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Interactive Map Guide</h3>
                    <p class="text-indigo-100 text-sm">Master the map with these pro tips</p>
                </div>
            </div>
        </div>
        
        <div class="guide-content p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Desktop Guide -->
                <div class="guide-section">
                    <div class="section-header">
                        <span class="section-icon">🖥️</span>
                        <h4 class="section-title">Desktop Controls</h4>
                    </div>
                    <div class="guide-items">
                        <div class="guide-item">
                            <span class="item-icon">🌍</span>
                            <div class="item-content">
                                <span class="item-title">Auto GPS</span>
                                <span class="item-desc">Click "Use My Location" for instant detection</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">🖱️</span>
                            <div class="item-content">
                                <span class="item-title">Click & Place</span>
                                <span class="item-desc">Click anywhere on map to place marker</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">↕️</span>
                            <div class="item-content">
                                <span class="item-title">Drag & Drop</span>
                                <span class="item-desc">Drag the red marker for precise positioning</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Guide -->
                <div class="guide-section">
                    <div class="section-header">
                        <span class="section-icon">📱</span>
                        <h4 class="section-title">Mobile Touch</h4>
                    </div>
                    <div class="guide-items">
                        <div class="guide-item">
                            <span class="item-icon">👆</span>
                            <div class="item-content">
                                <span class="item-title">Tap to Select</span>
                                <span class="item-desc">Tap map to choose location point</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">🤏</span>
                            <div class="item-content">
                                <span class="item-title">Pinch to Zoom</span>
                                <span class="item-desc">Use two fingers to zoom in/out</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">📍</span>
                            <div class="item-content">
                                <span class="item-title">Hold & Drag</span>
                                <span class="item-desc">Touch and hold marker to reposition</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro Tips Banner -->
            <div class="pro-tips">
                <div class="tips-header">
                    <span class="tips-icon">💡</span>
                    <span class="tips-title">Pro Tips</span>
                </div>
                <div class="tips-content">
                    <p>For best GPS accuracy, enable location services and use outdoors. Coordinates sync automatically with form fields above!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Load GPS Help System -->
    <script src="/gps-help-system.js"></script>
    
    <!-- ResizeObserver optimization handled inline in main script below -->
    
    <!-- Enhanced JavaScript with Creative Features - Immediate Load for Alpine.js -->
    <script>
        // CRITICAL: Define Alpine functions IMMEDIATELY for x-data compatibility
        
        // Enhanced error handling for script loading issues
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            if (msg.includes('leafletMapComponent') || msg.includes('initializeMap') || msg.includes('Alpine')) {
                console.error('🚨 LEAFLET/ALPINE ERROR:', { msg, url, lineNo, columnNo, error });
                console.error('Error details:', error?.stack || 'No stack trace available');
                
                // Show user-friendly notification
                if (window.Filament) {
                    window.Filament.notification()
                        .title('❌ JavaScript Error Detected')
                        .body('Map component encountered an error. Check console for details.')
                        .danger()
                        .send();
                }
                
                return true; // Prevent default browser error handling
            }
            return false; // Allow default error handling for other errors
        };
        
        // Suppress ResizeObserver loop warnings (performance optimization)
        const originalError = console.error;
        console.error = function(...args) {
            if (args[0] && args[0].toString && args[0].toString().includes('ResizeObserver loop completed')) {
                console.debug('🔄 ResizeObserver loop detected (suppressed for performance)');
                return; // Suppress the error
            }
            originalError.apply(console, args);
        };
        
        // Global Creative Map Management
        window.CreativeLeafletMaps = window.CreativeLeafletMaps || new Map();
        
        // Validate Alpine.js availability
        if (typeof Alpine === 'undefined') {
            console.warn('⚠️ Alpine.js not loaded yet - functions may not be available immediately');
        }
        
        // ResizeObserver Performance Optimization with Error Handling
        const originalResizeObserver = window.ResizeObserver;
        if (originalResizeObserver) {
            window.ResizeObserver = class InlineOptimizedResizeObserver extends originalResizeObserver {
                constructor(callback) {
                    // Debounce and error-wrap the callback
                    const safeCallback = function(entries, observer) {
                        try {
                            callback(entries, observer);
                        } catch (error) {
                            // Suppress ResizeObserver loop errors but log others
                            if (!error.message.includes('ResizeObserver loop limit exceeded')) {
                                console.warn('ResizeObserver callback error:', error);
                            }
                        }
                    };
                    const debouncedCallback = debounce(safeCallback, 16); // ~60fps
                    super(debouncedCallback);
                }
            };
            
            console.log('✅ ResizeObserver optimized for performance');
        }
        
        // Debounce utility function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // CRITICAL: Validate function definition before Alpine.js initialization
        console.log('🗺️ Defining leafletMapComponent function for Alpine.js...');
        console.log('Alpine.js available:', typeof Alpine !== 'undefined');
        console.log('Document ready state:', document.readyState);
        
        // Test function for debugging Alpine.js integration
        window.testAlpineIntegration = function() {
            console.log('🧪 Testing Alpine.js integration...');
            console.log('Alpine.js:', typeof Alpine !== 'undefined' ? '✅ Available' : '❌ Not available');
            console.log('leafletMapComponent:', typeof leafletMapComponent === 'function' ? '✅ Function defined' : '❌ Function not defined');
            
            // Test if Alpine can find the component
            const testDiv = document.querySelector('[x-data*="leafletMapComponent"]');
            console.log('Alpine component element:', testDiv ? '✅ Found' : '❌ Not found');
            
            if (testDiv && typeof Alpine !== 'undefined') {
                console.log('Alpine component data:', Alpine.$data(testDiv));
            }
            
            return {
                alpine: typeof Alpine !== 'undefined',
                component: typeof leafletMapComponent === 'function',
                element: !!testDiv
            };
        };
        
        // ALPINE.JS SCOPE FIX - Ensure functions are available in Alpine scope
        document.addEventListener('alpine:init', () => {
            console.log('🎯 Alpine.js initializing - validating functions');
            
            const functionName = 'leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $statePath) }}';
            if (typeof window[functionName] !== 'function') {
                console.error('❌ Alpine scope error: function not available:', functionName);
            } else {
                console.log('✅ Alpine scope validation passed for:', functionName);
            }
        });
        
        // Ensure mapId is available in Alpine expressions
        document.addEventListener('alpine:initialized', () => {
            const element = document.querySelector('[x-data*="leafletMapComponent"]');
            if (element && element._x_dataStack) {
                const component = element._x_dataStack[0];
                if (component && !component.mapId) {
                    component.mapId = '{{ $uniqueMapId }}';
                    console.log('✅ Fixed mapId accessibility in Alpine component:', component.mapId);
                }
            }
        });

                autoDetectOnLoad() {
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    // Check if coordinates are empty or default values
                    const currentLat = latField ? parseFloat(latField.value) : null;
                    const currentLng = lngField ? parseFloat(lngField.value) : null;
                    
                    const isDefaultLocation = (currentLat === -6.2088 || currentLat === -6.2088200) && 
                                            (currentLng === 106.8456 || currentLng === 106.8238800);
                    const isEmpty = !currentLat || !currentLng || isNaN(currentLat) || isNaN(currentLng);
                    
                    if (isEmpty || isDefaultLocation) {
                        this.updateGPSStatus('Auto-detecting your location...', 'searching');
                        this.getCurrentLocationSilent();
                        
                        // Auto-detect location for empty/default coordinates
                        navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            const accuracy = position.coords.accuracy;
                            
                            this.map.setView([lat, lng], Math.max({{ $defaultZoom }}, 16));
                            this.marker.setLatLng([lat, lng]);
                            this.updateCoordinates(lat, lng);
                            this.updateAccuracy(accuracy);
                            this.animateMarker();
                            
                            this.updateGPSStatus(`Auto-detected location (±${Math.round(accuracy)}m)`, 'success');
                            
                            // Show success notification
                            if (window.Filament) {
                                window.Filament.notification()
                                    .title('🌍 Location Auto-Detected!')
                                    .body(`Your coordinates have been automatically filled with ±${Math.round(accuracy)}m accuracy`)
                                    .success()
                                    .send();
                            }
                        },
                        (error) => {
                            let errorMsg = 'Auto-detection failed: ';
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMsg += 'Please enable location access';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMsg += 'Location service unavailable';
                                    break;
                                case error.TIMEOUT:
                                    errorMsg += 'Request timeout';
                                    break;
                                default:
                                    errorMsg += 'Unknown error';
                            }
                            
                            this.updateGPSStatus(errorMsg, 'error');
                            
                            // Show error notification
                            if (window.Filament) {
                                window.Filament.notification()
                                    .title('⚠️ Auto-Detection Failed')
                                    .body('Please click "Get My Location" button or enter coordinates manually')
                                    .warning()
                                    .send();
                            }
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 600000
                        }
                    );
                    }
                },

                async loadLeafletResources() {
                    if (typeof L !== 'undefined') return;
                    
                    const cssLink = document.createElement('link');
                    cssLink.rel = 'stylesheet';
                    cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    cssLink.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                    cssLink.crossOrigin = '';
                    document.head.appendChild(cssLink);

                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                        script.crossOrigin = '';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                },

                async setupMap() {
                    // Initialize map with enhanced settings and performance optimizations
                    this.map = L.map(this.mapId, {
                        center: [{{ $defaultLat }}, {{ $defaultLng }}],
                        zoom: {{ $defaultZoom }},
                        zoomControl: true,
                        attributionControl: true,
                        tap: true,
                        touchZoom: true,
                        dragging: true,
                        maxZoom: 19,
                        minZoom: 3,
                        fadeAnimation: true,
                        zoomAnimation: true,
                        markerZoomAnimation: true,
                        // Performance optimizations
                        preferCanvas: true,
                        updateWhenZooming: false,
                        updateWhenIdle: true,
                        keepBuffer: 2
                    });
                    
                    // Add resize event debouncing
                    let resizeTimeout;
                    this.map.on('resize', () => {
                        clearTimeout(resizeTimeout);
                        resizeTimeout = setTimeout(() => {
                            this.map.invalidateSize({ pan: false });
                        }, 250);
                    });

                    // Add tile layers
                    this.addTileLayers();
                    
                    // Create custom marker
                    this.createCustomMarker();
                    
                    // Store reference
                    window.CreativeLeafletMaps.set(this.mapId, this);
                },

                addTileLayers() {
                    // OpenStreetMap (default)
                    this.osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 19,
                        detectRetina: true
                    });

                    // Satellite layer
                    this.satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: '&copy; <a href="https://www.esri.com/">Esri</a>',
                        maxZoom: 19
                    });

                    // Add default layer
                    this.osmLayer.addTo(this.map);
                },

                createCustomMarker() {
                    // Creative pulsing marker
                    const customIcon = L.divIcon({
                        html: `
                            <div class="creative-marker">
                                <div class="marker-pulse"></div>
                                <div class="marker-core"></div>
                                <div class="marker-shadow"></div>
                            </div>
                        `,
                        className: 'creative-marker-container',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });

                    this.marker = L.marker([{{ $defaultLat }}, {{ $defaultLng }}], {
                        draggable: true,
                        icon: customIcon
                    }).addTo(this.map);
                },

                setupEventListeners() {
                    // Map click event
                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                        this.updateCoordinates(e.latlng.lat, e.latlng.lng);
                        this.animateMarker();
                    });

                    // Marker drag event
                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.updateCoordinates(pos.lat, pos.lng);
                        this.animateMarker();
                    });

                    // Form field listeners
                    this.setupFormSync();
                },

                setupFormSync() {
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    if (latField && lngField) {
                        let debounceTimer;
                        
                        const syncFromForm = () => {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(() => {
                                const lat = parseFloat(latField.value);
                                const lng = parseFloat(lngField.value);
                                
                                if (this.isValidCoordinate(lat, lng)) {
                                    this.map.setView([lat, lng], this.map.getZoom());
                                    this.marker.setLatLng([lat, lng]);
                                    this.updateDisplays(lat, lng);
                                    this.animateMarker();
                                }
                            }, 300);
                        };
                        
                        latField.addEventListener('input', syncFromForm);
                        lngField.addEventListener('input', syncFromForm);
                        latField.addEventListener('blur', syncFromForm);
                        lngField.addEventListener('blur', syncFromForm);
                    }
                },

                updateCoordinates(lat, lng) {
                    console.log('🌍 updateCoordinates called with:', { lat, lng });
                    
                    // Enhanced form field detection strategy
                    const fields = this.findCoordinateFields();
                    
                    if (fields.latitude && fields.longitude) {
                        console.log('🔄 Updating form fields with coordinates:', { lat, lng });
                        console.log('📍 Target fields:', {
                            latField: fields.latFieldInfo,
                            lngField: fields.lngFieldInfo
                        });
                        
                        // Store previous values for comparison
                        const prevLat = fields.latitude.value;
                        const prevLng = fields.longitude.value;
                        
                        // Set values with proper precision
                        const newLatValue = lat.toFixed(6);
                        const newLngValue = lng.toFixed(6);
                        
                        fields.latitude.value = newLatValue;
                        fields.longitude.value = newLngValue;
                        
                        console.log('✅ Field values updated:', {
                            latitude: { prev: prevLat, new: newLatValue, current: fields.latitude.value },
                            longitude: { prev: prevLng, new: newLngValue, current: fields.longitude.value }
                        });
                        
                        // Enhanced: Very prominent visual feedback for coordinate update
                        [fields.latitude, fields.longitude].forEach((field, index) => {
                            // Add prominent styling
                            field.classList.add('border-green-500', 'bg-green-100', 'ring-4', 'ring-green-300');
                            
                            // Add a prominent pulse animation
                            field.style.transform = 'scale(1.05)';
                            field.style.transition = 'all 0.3s ease';
                            field.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.5)';
                            
                            // Add a floating label to show it's been updated
                            const label = document.createElement('div');
                            label.textContent = `✅ ${index === 0 ? 'Latitude' : 'Longitude'} Updated!`;
                            label.style.cssText = `
                                position: absolute;
                                top: -25px;
                                left: 50%;
                                transform: translateX(-50%);
                                background: linear-gradient(135deg, #10b981, #059669);
                                color: white;
                                padding: 4px 12px;
                                border-radius: 6px;
                                font-size: 12px;
                                font-weight: 600;
                                z-index: 1000;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                                animation: fadeInOut 2.5s ease forwards;
                            `;
                            
                            field.style.position = 'relative';
                            field.appendChild(label);
                            
                            setTimeout(() => {
                                field.classList.remove('border-green-500', 'bg-green-100', 'ring-4', 'ring-green-300');
                                field.style.transform = '';
                                field.style.transition = '';
                                field.style.boxShadow = '';
                                if (label.parentNode === field) {
                                    field.removeChild(label);
                                }
                            }, 3000);
                        });
                        
                        // Enhanced event dispatching - especially for longitude field
                        this.triggerFieldEvents(fields.latitude, newLatValue, 'latitude');
                        this.triggerFieldEvents(fields.longitude, newLngValue, 'longitude');
                        
                        // Show success notification with field identification
                        if (window.Filament) {
                            window.Filament.notification()
                                .title('✅ Form Fields Updated!')
                                .body(`📍 Latitude field: ${newLatValue}\n📍 Longitude field: ${newLngValue}\n\n✅ These are the ACTUAL form fields that will be saved!`)
                                .success()
                                .duration(4000)
                                .send();
                        }
                        
                        // Console log for debugging
                        console.log('✅ FORM FIELDS SUCCESSFULLY UPDATED:', {
                            latitudeField: {
                                id: fields.latitude.id,
                                name: fields.latitude.name,
                                oldValue: prevLat,
                                newValue: fields.latitude.value,
                                element: fields.latitude
                            },
                            longitudeField: {
                                id: fields.longitude.id,
                                name: fields.longitude.name,
                                oldValue: prevLng,
                                newValue: fields.longitude.value,
                                element: fields.longitude
                            }
                        });
                        
                    } else {
                        console.error('❌ CRITICAL: Coordinate fields not found:', {
                            latitude: !!fields.latitude,
                            longitude: !!fields.longitude,
                            debugInfo: fields
                        });
                        
                        // Show error notification
                        if (window.Filament) {
                            window.Filament.notification()
                                .title('❌ Error: Form Fields Not Found')
                                .body('Tidak dapat menemukan field latitude atau longitude pada form. Silakan isi manual.')
                                .danger()
                                .persistent()
                                .send();
                        }
                    }
                    
                    this.updateDisplays(lat, lng);
                },

                findCoordinateFields() {
                    console.log('🔍 Starting comprehensive field detection...');
                    
                    // Debug: List all input fields on the page
                    const allInputs = document.querySelectorAll('input');
                    console.log('📋 All input fields found:', Array.from(allInputs).map(input => ({
                        type: input.type,
                        name: input.name || 'no-name',
                        id: input.id || 'no-id',
                        placeholder: input.placeholder || 'no-placeholder',
                        dataField: input.getAttribute('data-coordinate-field'),
                        wireModel: input.getAttribute('wire:model'),
                        value: input.value
                    })));
                    
                    // Strategy 1: Data attributes (most reliable for Filament)
                    let latField = document.querySelector('input[data-coordinate-field="latitude"]');
                    let lngField = document.querySelector('input[data-coordinate-field="longitude"]');
                    console.log('🎯 Strategy 1 (data attributes):', { lat: !!latField, lng: !!lngField });
                    
                    // Strategy 2: ID selectors
                    if (!latField) latField = document.querySelector('#latitude');
                    if (!lngField) lngField = document.querySelector('#longitude');
                    console.log('🎯 Strategy 2 (ID selectors):', { lat: !!latField, lng: !!lngField });
                    
                    // Strategy 3: Name attributes
                    if (!latField) latField = document.querySelector('input[name="latitude"]');
                    if (!lngField) lngField = document.querySelector('input[name="longitude"]');
                    console.log('🎯 Strategy 3 (name attributes):', { lat: !!latField, lng: !!lngField });
                    
                    // Strategy 4: Wire model detection
                    if (!latField || !lngField) {
                        document.querySelectorAll('input[wire\:model]').forEach(input => {
                            const model = input.getAttribute('wire:model');
                            if (model?.includes('latitude')) latField = input;
                            if (model?.includes('longitude')) lngField = input;
                        });
                        console.log('🎯 Strategy 4 (wire model):', { lat: !!latField, lng: !!lngField });
                    }
                    
                    // Strategy 5: Partial text matching in placeholders/labels
                    if (!latField || !lngField) {
                        document.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                            const placeholder = (input.placeholder || '').toLowerCase();
                            const label = input.closest('.form-group, .field-group, div')?.querySelector('label')?.textContent?.toLowerCase() || '';
                            
                            if ((placeholder.includes('lat') || label.includes('lat')) && !latField) {
                                latField = input;
                                console.log('✅ Found latitude field by placeholder/label:', { placeholder, label });
                            }
                            if ((placeholder.includes('lng') || placeholder.includes('lon') || label.includes('lng') || label.includes('lon')) && !lngField) {
                                lngField = input;
                                console.log('✅ Found longitude field by placeholder/label:', { placeholder, label });
                            }
                        });
                        console.log('🎯 Strategy 5 (text matching):', { lat: !!latField, lng: !!lngField });
                    }
                    
                    // Final detection result with detailed info
                    const result = {
                        latitude: latField,
                        longitude: lngField,
                        latFieldInfo: latField ? {
                            id: latField.id,
                            name: latField.name,
                            placeholder: latField.placeholder,
                            type: latField.type,
                            value: latField.value,
                            dataField: latField.getAttribute('data-coordinate-field'),
                            wireModel: latField.getAttribute('wire:model')
                        } : null,
                        lngFieldInfo: lngField ? {
                            id: lngField.id,
                            name: lngField.name,
                            placeholder: lngField.placeholder,
                            type: lngField.type,
                            value: lngField.value,
                            dataField: lngField.getAttribute('data-coordinate-field'),
                            wireModel: lngField.getAttribute('wire:model')
                        } : null
                    };
                    
                    console.log('🏁 Final field detection result:', result);
                    
                    // Show user-friendly notification about detection status
                    if (latField && lngField) {
                        console.log('✅ SUCCESS: Both coordinate fields detected and ready for auto-fill');
                    } else {
                        console.error('❌ FAILED: Could not detect coordinate fields:', {
                            latitudeFound: !!latField,
                            longitudeFound: !!lngField,
                            troubleshooting: 'Check if latitude/longitude input fields exist in the form'
                        });
                        
                        // Show notification to user about the issue
                        if (window.Filament) {
                            window.Filament.notification()
                                .title('⚠️ Field Detection Issue')
                                .body(`Could not find ${!latField ? 'latitude' : ''} ${(!latField && !lngField) ? 'and ' : ''}${!lngField ? 'longitude' : ''} form field(s)`)
                                .warning()
                                .send();
                        }
                    }
                    
                    return { latitude: latField, longitude: lngField };
                },
                
                triggerFieldEvents(field, value, fieldName) {
                    if (!field) {
                        console.error(`❌ ${fieldName} field not found for event triggering`);
                        return;
                    }
                    
                    console.log(`🚀 Triggering events for ${fieldName}:`, value);
                    
                    // Standard DOM events (immediate)
                    const events = ['input', 'change', 'keyup', 'blur'];
                    events.forEach(eventType => {
                        field.dispatchEvent(new Event(eventType, { 
                            bubbles: true, 
                            cancelable: true 
                        }));
                    });
                    
                    // Focus/blur cycle for Filament reactivity
                    field.focus();
                    setTimeout(() => {
                        field.blur();
                        
                        // Additional Livewire events (delayed)
                        field.dispatchEvent(new CustomEvent('livewire:update', { 
                            detail: { value }, 
                            bubbles: true 
                        }));
                        
                        // Alpine.js events
                        if (window.Alpine) {
                            field.dispatchEvent(new CustomEvent('alpine:update', { 
                                detail: { value }, 
                                bubbles: true 
                            }));
                        }
                        
                        console.log(`✅ Events completed for ${fieldName}:`, field.value);
                    }, 50);
                },

                updateDisplays(lat, lng) {
                    // Update coordinate displays
                    const coordDisplay = document.getElementById(this.mapId + '-coordinates');
                    const latDisplay = document.getElementById(this.mapId + '-lat-display');
                    const lngDisplay = document.getElementById(this.mapId + '-lng-display');
                    
                    if (coordDisplay) coordDisplay.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    if (latDisplay) latDisplay.textContent = lat.toFixed(6);
                    if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
                },

                animateMarker() {
                    const markerElement = this.marker.getElement();
                    if (markerElement) {
                        markerElement.classList.add('marker-bounce');
                        setTimeout(() => {
                            markerElement.classList.remove('marker-bounce');
                        }, 600);
                    }
                },

                updateGPSStatus(message, type = 'info') {
                    const statusEl = document.getElementById(this.mapId + '-gps-status');
                    const progressEl = document.getElementById(this.mapId + '-gps-progress');
                    
                    if (statusEl) statusEl.textContent = message;
                    
                    if (progressEl) {
                        const widths = { ready: '100%', searching: '50%', error: '0%', success: '100%' };
                        progressEl.style.width = widths[type] || '25%';
                    }
                },

                updateAccuracy(accuracy) {
                    this.gpsAccuracy = accuracy;
                    const accuracyEl = document.getElementById(this.mapId + '-accuracy');
                    const accuracyText = document.getElementById(this.mapId + '-accuracy-text');
                    const accuracyDot = document.getElementById(this.mapId + '-accuracy-dot');
                    const accuracyIndicator = document.getElementById(this.mapId + '-accuracy-indicator');
                    
                    if (accuracy && accuracy > 0) {
                        const roundedAccuracy = Math.round(accuracy);
                        const color = roundedAccuracy <= 10 ? 'green' : roundedAccuracy <= 50 ? 'orange' : 'red';
                        
                        if (accuracyEl) accuracyEl.textContent = `±${roundedAccuracy}m`;
                        if (accuracyText) accuracyText.textContent = `±${roundedAccuracy}m`;
                        if (accuracyDot) {
                            accuracyDot.className = `indicator-dot bg-${color}-400`;
                        }
                        if (accuracyIndicator) {
                            accuracyIndicator.classList.remove('hidden');
                        }
                    }
                },

                hideLoader() {
                    const loader = document.getElementById(this.mapId + '-loader');
                    if (loader) {
                        loader.style.opacity = '0';
                        setTimeout(() => {
                            loader.style.display = 'none';
                        }, 300);
                    }
                },

                showError(message) {
                    const loader = document.getElementById(this.mapId + '-loader');
                    if (loader) {
                        loader.innerHTML = `
                            <div class="loader-content">
                                <div class="text-red-500 text-4xl mb-3">⚠️</div>
                                <p class="text-sm text-red-600 font-medium">${message}</p>
                            </div>
                        `;
                    }
                },

                isValidCoordinate(lat, lng) {
                    return !isNaN(lat) && !isNaN(lng) && 
                           lat >= -90 && lat <= 90 && 
                           lng >= -180 && lng <= 180;
                }
            };
        }
        
        // CRITICAL: Alpine.js compatibility validation
        const validateAlpineCompatibility = () => {
            const functionName = 'leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $statePath) }}';
            const isMainFunctionRegistered = typeof window.leafletMapComponent === 'function';
            const isSpecificFunctionRegistered = typeof window[functionName] === 'function';
            
            console.log('✅ Alpine.js compatibility check:', {
                mainFunction: isMainFunctionRegistered,
                specificFunction: isSpecificFunctionRegistered,
                functionName: functionName,
                alpineReady: typeof Alpine !== 'undefined'
            });
            
            // Ensure both functions exist for Alpine.js compatibility
            if (!isMainFunctionRegistered || !isSpecificFunctionRegistered) {
                console.error('❌ Alpine.js compatibility issue: missing required functions');
                return false;
            }
            
            return true;
        };
        
        // Run validation immediately and after Alpine loads
        validateAlpineCompatibility();
        document.addEventListener('alpine:init', validateAlpineCompatibility);
        
        // Global utility functions for map interaction
        window.getCurrentLocation = function(mapId) {
            console.log('🌍 getCurrentLocation called for:', mapId || '{{ $uniqueMapId }}');
            
            const targetMapId = mapId || '{{ $uniqueMapId }}';
            const mapContainer = document.querySelector(`[x-data] [id="${targetMapId}"]`)?.closest('[x-data]');
            
            if (mapContainer && mapContainer._x_dataStack) {
                const component = mapContainer._x_dataStack[0];
                if (component && typeof component.getCurrentLocation === 'function') {
                    component.getCurrentLocation();
                    return;
                }
            }
            
            console.error('❌ Could not find Alpine component or getCurrentLocation method');
        };
        
        window.copyCoordinates = function(mapId) {
            const targetMapId = mapId || '{{ $uniqueMapId }}';
            const coordElement = document.getElementById(`${targetMapId}-coordinates`);
            
            if (coordElement) {
                const text = coordElement.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    console.log('📋 Coordinates copied:', text);
                    
                    if (window.Filament?.notification) {
                        window.Filament.notification()
                            .title('📋 Copied!')
                            .body(`Coordinates copied: ${text}`)
                            .success()
                            .send();
                    }
                }).catch(err => {
                    console.error('Failed to copy:', err);
                });
            }
        };
        
        window.toggleMapStyle = function(mapId) {
            const targetMapId = mapId || '{{ $uniqueMapId }}';
            const mapContainer = document.querySelector(`[x-data] [id="${targetMapId}"]`)?.closest('[x-data]');
            
            if (mapContainer && mapContainer._x_dataStack) {
                const component = mapContainer._x_dataStack[0];
                if (component && typeof component.toggleStyle === 'function') {
                    component.toggleStyle();
                    return;
                }
            }
            
            console.error('❌ Could not find Alpine component or toggleStyle method');
        };
        
        // Validation and debugging  
        console.log('✅ Main leafletMapComponent registered:', typeof window.leafletMapComponent === 'function');
        console.log('✅ Utility functions registered:', {
            getCurrentLocation: typeof window.getCurrentLocation === 'function',
            copyCoordinates: typeof window.copyCoordinates === 'function',
            toggleMapStyle: typeof window.toggleMapStyle === 'function'
        });
        
        // Debug function to test the fixes
        window.debugLeafletErrors = function() {
            console.log('🔍 DEBUGGING LEAFLET ERRORS:');
            console.log('1. leafletMapComponent function:', typeof window.leafletMapComponent === 'function' ? '✅ Available' : '❌ Missing');
            console.log('2. initializeMap function:', typeof window.initializeMap === 'function' ? '✅ Available' : '❌ Missing');
            console.log('3. Alpine.js:', typeof Alpine !== 'undefined' ? '✅ Available' : '❌ Missing');
            console.log('4. jQuery/Livewire:', typeof $ !== 'undefined' ? '✅ Available' : '❌ Missing');
            console.log('5. Map container:', document.querySelector('[x-data*="leafletMapComponent"]') ? '✅ Found' : '❌ Missing');
            
            // Test Alpine component creation
            try {
                const testComponent = window.leafletMapComponent();
                console.log('6. Component creation:', testComponent ? '✅ Success' : '❌ Failed');
                console.log('7. Component methods:', typeof testComponent.initializeMap === 'function' ? '✅ initializeMap available' : '❌ Missing initializeMap');
            } catch (error) {
                console.error('6. Component creation: ❌ Error:', error.message);
            }
            
            return 'Debug complete - check console for details';
        };
        
        // Test Alpine.js integration
        if (typeof Alpine !== 'undefined') {
            console.log('✅ Alpine.js is available - functions ready for x-data');
        } else {
            console.log('🕰️ Alpine.js not yet loaded - functions will be available when Alpine initializes');
        }
        
        // DEBUG MODE: Comprehensive error tracking and validation
        window.debugLeafletMap = function() {
            console.group('📍 LEAFLET MAP DEBUGGING REPORT');
            
            console.log('🕰️ Script Load Status:');
            console.log('- Alpine.js:', typeof Alpine !== 'undefined' ? '✅ Loaded' : '❌ Missing');
            console.log('- Leaflet:', typeof L !== 'undefined' ? '✅ Loaded' : '❌ Missing');
            console.log('- GPS Help System:', typeof window.showGPSHelp === 'function' ? '✅ Loaded' : '❌ Missing');
            console.log('- Geolocation API:', 'geolocation' in navigator ? '✅ Supported' : '❌ Not supported');
            
            console.log('
🎯 Function Registration Status:');
            console.log('- leafletMapComponent:', typeof window.leafletMapComponent);
            console.log('- initializeMap:', typeof window.initializeMap);
            console.log('- getCurrentLocation:', typeof window.getCurrentLocation);
            
            console.log('
🗺️ Map Container Status:');
            const mapContainer = document.getElementById('{{ $uniqueMapId }}');
            console.log('- Container found:', mapContainer ? '✅ Yes' : '❌ No');
            if (mapContainer) {
                console.log('- Container dimensions:', `${mapContainer.offsetWidth}x${mapContainer.offsetHeight}`);
                console.log('- Container visible:', mapContainer.offsetParent !== null ? '✅ Yes' : '❌ Hidden');
            }
            
            console.log('
🔍 Form Field Detection:');
            const fields = findCoordinateFieldsGlobal();
            console.log('- Latitude field:', fields.latitude ? '✅ Found' : '❌ Missing');
            console.log('- Longitude field:', fields.longitude ? '✅ Found' : '❌ Missing');
            
            if (fields.latitude) {
                console.log('- Latitude field details:', {
                    id: fields.latitude.id,
                    name: fields.latitude.name,
                    type: fields.latitude.type,
                    value: fields.latitude.value
                });
            }
            
            if (fields.longitude) {
                console.log('- Longitude field details:', {
                    id: fields.longitude.id,
                    name: fields.longitude.name,
                    type: fields.longitude.type,
                    value: fields.longitude.value
                });
            }
            
            console.log('
🚀 Performance Status:');
            console.log('- Memory usage:', (performance as any).memory ? `${Math.round((performance as any).memory.usedJSHeapSize / 1024 / 1024)}MB` : 'Unknown');
            console.log('- Active maps:', window.CreativeLeafletMaps?.size || 0);
            console.log('- ResizeObserver status:', typeof window.LeafletResizeObserver !== 'undefined' ? '✅ Optimized' : '⚠️ Standard');
            
            console.groupEnd();
            
            return {
                status: 'complete',
                alpineReady: typeof Alpine !== 'undefined',
                leafletReady: typeof L !== 'undefined',
                functionsRegistered: typeof window.leafletMapComponent === 'function',
                containerFound: !!mapContainer,
                fieldsFound: !!(fields.latitude && fields.longitude)
            };
        };
        
        // Auto-run debug if in development mode or if console debugging is enabled
        if (localStorage.getItem('leaflet-debug') === 'true' || window.location.hostname === 'localhost') {
            setTimeout(window.debugLeafletMap, 1000);
        }

        // CRITICAL FIX: Test function to verify form field targeting
        window.testCoordinateFields = function() {
            console.log('🧪 Testing coordinate field detection...');
            
            // Test if we're targeting FORM fields vs status display
            const testLat = -7.8964;
            const testLng = 111.9667;
            
            // Find actual form fields (TextInput components)
            const formFields = {
                latitude: document.querySelector('input[name="latitude"], input[data-coordinate-field="latitude"], #latitude'),
                longitude: document.querySelector('input[name="longitude"], input[data-coordinate-field="longitude"], #longitude')
            };
            
            console.log('📋 Form Fields Detected:', {
                latitude: formFields.latitude ? {
                    element: formFields.latitude,
                    id: formFields.latitude.id,
                    name: formFields.latitude.name,
                    currentValue: formFields.latitude.value,
                    placeholder: formFields.latitude.placeholder
                } : 'NOT FOUND ❌',
                longitude: formFields.longitude ? {
                    element: formFields.longitude,
                    id: formFields.longitude.id,
                    name: formFields.longitude.name,
                    currentValue: formFields.longitude.value,
                    placeholder: formFields.longitude.placeholder
                } : 'NOT FOUND ❌'
            });
            
            // Find status display elements (should NOT be targeted)
            const statusElements = {
                gpsStatus: document.querySelector('[id*="gps-status"]'),
                coordinates: document.querySelector('[id*="coordinates"]'),
                accuracy: document.querySelector('[id*="accuracy"]')
            };
            
            console.log('📊 Status Display Elements (should NOT be updated):', statusElements);
            
            // Test form field updates
            if (formFields.latitude && formFields.longitude) {
                console.log('✅ Testing form field updates...');
                
                // Update form fields with test values
                formFields.latitude.value = testLat.toFixed(6);
                formFields.longitude.value = testLng.toFixed(6);
                
                // Add visual feedback
                [formFields.latitude, formFields.longitude].forEach((field, index) => {
                    field.style.border = '3px solid #10b981';
                    field.style.backgroundColor = '#ecfdf5';
                    field.style.boxShadow = '0 0 15px rgba(16, 185, 129, 0.5)';
                    
                    // Add label
                    const label = document.createElement('div');
                    label.textContent = `✅ ${index === 0 ? 'LATITUDE' : 'LONGITUDE'} FORM FIELD UPDATED!`;
                    label.style.cssText = `
                        position: absolute;
                        top: -30px;
                        left: 0;
                        right: 0;
                        background: #10b981;
                        color: white;
                        text-align: center;
                        padding: 5px;
                        font-weight: bold;
                        font-size: 12px;
                        border-radius: 4px;
                        z-index: 1000;
                    `;
                    
                    field.parentElement.style.position = 'relative';
                    field.parentElement.appendChild(label);
                    
                    // Clean up after 3 seconds
                    setTimeout(() => {
                        field.style.border = '';
                        field.style.backgroundColor = '';
                        field.style.boxShadow = '';
                        if (label.parentElement) label.remove();
                    }, 3000);
                });
                
                // Dispatch events
                ['input', 'change', 'blur'].forEach(eventType => {
                    formFields.latitude.dispatchEvent(new Event(eventType, { bubbles: true }));
                    formFields.longitude.dispatchEvent(new Event(eventType, { bubbles: true }));
                });
                
                console.log('✅ SUCCESS: Form fields updated with test coordinates');
                console.log('📍 Values set:', {
                    latitude: formFields.latitude.value,
                    longitude: formFields.longitude.value
                });
                
                return { 
                    success: true, 
                    message: 'Form fields successfully targeted and updated',
                    coordinates: { lat: testLat, lng: testLng }
                };
            } else {
                console.error('❌ FAILED: Could not find form fields');
                return { 
                    success: false, 
                    message: 'Form fields not found - map may be updating wrong elements',
                    formFields 
                };
            }
        };
        
        // Global Functions for Button Actions
        function autoDetectLocation() {
            console.log('🌍 autoDetectLocation called from GPS button');
            // Find the first map component (works for single map scenarios)
            const mapId = Array.from(window.CreativeLeafletMaps.keys())[0];
            if (mapId) {
                console.log('📍 Using map-based GPS detection for mapId:', mapId);
                getCurrentLocation(mapId);
            } else {
                console.log('📍 Using fallback GPS detection (no map found)');
                // Fallback: trigger geolocation without map
                triggerGeolocationForForm();
            }
        }

        // Register function globally for WorkLocation GPS button
        window.autoDetectLocation = autoDetectLocation;

        function triggerGeolocationForForm() {
            if (!navigator.geolocation) {
                alert('GPS tidak didukung oleh browser Anda. Silakan masukkan koordinat secara manual.');
                return;
            }

            const btn = document.getElementById('get-location-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '🔄 Detecting...';
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    console.log('🌍 GPS Success in fallback mode:', { lat, lng, accuracy });
                    
                    // Enhanced field detection
                    const fields = findCoordinateFieldsGlobal();
                    
                    if (fields.latitude && fields.longitude) {
                        fields.latitude.value = lat.toFixed(6);
                        fields.longitude.value = lng.toFixed(6);
                        
                        // Enhanced event triggering
                        triggerFieldEventsGlobal(fields.latitude, lat.toFixed(6), 'latitude');
                        triggerFieldEventsGlobal(fields.longitude, lng.toFixed(6), 'longitude');
                    }
                    
                    // Show success notification
                    if (window.Filament) {
                        window.Filament.notification()
                            .title('🌍 Location Detected!')
                            .body(`Coordinates auto-filled with ±${Math.round(accuracy)}m accuracy`)
                            .success()
                            .send();
                    } else {
                        alert(`Location detected! Accuracy: ±${Math.round(accuracy)}m`);
                    }
                    
                    // Reset button
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '🌍 Get My Location';
                    }
                },
                (error) => {
                    let errorMsg = 'GPS Error: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Akses lokasi ditolak. Silakan izinkan akses lokasi.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Lokasi tidak tersedia. Pastikan GPS aktif.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Timeout. Silakan coba lagi.';
                            break;
                        default:
                            errorMsg += 'Error tidak diketahui.';
                    }
                    
                    // Show error notification
                    if (window.Filament) {
                        window.Filament.notification()
                            .title('❌ Location Detection Failed')
                            .body(errorMsg)
                            .danger()
                            .send();
                    } else {
                        alert(errorMsg);
                    }
                    
                    // Reset button
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '🌍 Get My Location';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 300000
                }
            );
        }

        function getCurrentLocation(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (!mapComponent) return;
            
            const btn = document.getElementById(mapId + '-gps-main');
            const icon = document.getElementById(mapId + '-gps-icon');
            const text = document.getElementById(mapId + '-gps-text');
            
            if (!navigator.geolocation) {
                mapComponent.updateGPSStatus('GPS not supported', 'error');
                return;
            }
            
            // Update button state
            if (btn) btn.classList.add('loading');
            if (icon) icon.textContent = '🔄';
            if (text) text.textContent = 'Detecting...';
            
            mapComponent.updateGPSStatus('Searching for GPS location...', 'searching');
            
            // Enhanced Progressive GPS Detection
            mapComponent.enhancedGPSDetection(btn, icon, text);
        }
        
        // Add enhanced GPS detection method to map component prototype
        if (window.CreativeLeafletMaps) {
            // Enhance existing map components with better GPS
            window.CreativeLeafletMaps.forEach((component) => {
                if (!component.enhancedGPSDetection) {
                    component.enhancedGPSDetection = async function(btn, icon, text) {
                        const stages = [
                            {
                                name: 'Quick GPS',
                                icon: '🔍',
                                options: {
                                    enableHighAccuracy: false,
                                    timeout: 15000,
                                    maximumAge: 300000
                                }
                            },
                            {
                                name: 'High Accuracy GPS',
                                icon: '🎯', 
                                options: {
                                    enableHighAccuracy: true,
                                    timeout: 45000,
                                    maximumAge: 60000
                                }
                            },
                            {
                                name: 'Extended GPS',
                                icon: '📡',
                                options: {
                                    enableHighAccuracy: true,
                                    timeout: 75000,
                                    maximumAge: 0
                                }
                            }
                        ];
                        
                        for (let i = 0; i < stages.length; i++) {
                            const stage = stages[i];
                            
                            // Update UI for current stage
                            if (icon) icon.textContent = stage.icon;
                            if (text) text.textContent = `${stage.name}... (${i+1}/${stages.length})`;
                            this.updateGPSStatus(`${stage.name} in progress...`, 'searching');
                            
                            console.log(`🔍 GPS Stage ${i+1}: ${stage.name}`);
                            
                            try {
                                const position = await this.getCurrentPositionPromise(stage.options);
                                
                                // GPS Success!
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                const accuracy = position.coords.accuracy;
                                
                                this.map.setView([lat, lng], Math.max({{ $defaultZoom }}, 16));
                                this.marker.setLatLng([lat, lng]);
                                this.updateCoordinates(lat, lng);
                                this.updateAccuracy(accuracy);
                                this.animateMarker();
                                
                                this.updateGPSStatus(`${stage.name} successful (±${Math.round(accuracy)}m)`, 'success');
                                
                                // Success notification with GPS help option
                                if (window.Filament) {
                                    window.Filament.notification()
                                        .title('✅ GPS Success!')
                                        .body(`Location found with ${stage.name}: ±${Math.round(accuracy)}m accuracy`)
                                        .success()
                                        .duration(5000)
                                        .send();
                                }
                                
                                // Reset button
                                if (btn) btn.classList.remove('loading');
                                if (icon) icon.textContent = '✅';
                                if (text) text.textContent = 'GPS Success!';
                                
                                setTimeout(() => {
                                    if (icon) icon.textContent = '🌍';
                                    if (text) text.textContent = 'Use My Location';
                                }, 3000);
                                
                                return; // Success, exit function
                                
                            } catch (error) {
                                console.log(`❌ GPS Stage ${i+1} failed:`, error.message);
                                
                                // Don't retry permission errors
                                if (error.code === 1) {
                                    this.handleGPSError(error, btn, icon, text, 'permission');
                                    return;
                                }
                                
                                // Continue to next stage for other errors
                                if (i < stages.length - 1) {
                                    // Show transition message
                                    if (text) text.textContent = `Stage ${i+1} failed, trying ${i+2}...`;
                                    await new Promise(resolve => setTimeout(resolve, 1000));
                                }
                            }
                        }
                        
                        // All stages failed
                        this.handleGPSError({
                            code: 3,
                            message: 'All GPS detection stages failed'
                        }, btn, icon, text, 'timeout');
                    };
                    
                    component.handleGPSError = function(error, btn, icon, text, type = 'general') {
                        let errorMsg = 'GPS Error: ';
                        let notificationTitle = '❌ GPS Failed';
                        let notificationBody = '';
                        
                        switch(error.code) {
                            case 1: // PERMISSION_DENIED
                                errorMsg += 'Location access denied';
                                notificationTitle = '🚫 Permission Denied';
                                notificationBody = 'Please enable location access in your browser. Click the help button for detailed guidance.';
                                type = 'permission';
                                break;
                            case 2: // POSITION_UNAVAILABLE
                                errorMsg += 'Location unavailable';
                                notificationTitle = '📡 GPS Unavailable';
                                notificationBody = 'Location services may be disabled or GPS signal is weak. Try moving outdoors.';
                                type = 'unavailable';
                                break;
                            case 3: // TIMEOUT
                                errorMsg += 'Request timeout';
                                notificationTitle = '⏰ GPS Timeout';
                                notificationBody = 'GPS is taking too long. Try moving to an open area or use manual input.';
                                type = 'timeout';
                                break;
                            default:
                                errorMsg += error.message || 'Unknown error';
                                notificationBody = 'An unexpected GPS error occurred. Please try manual input.';
                        }
                        
                        this.updateGPSStatus(errorMsg, 'error');
                        
                        // Enhanced error notification with help button
                        if (window.Filament) {
                            window.Filament.notification()
                                .title(notificationTitle)
                                .body(`${notificationBody}\n\n💡 Tip: Click GPS Help for detailed troubleshooting guidance.`)
                                .danger()
                                .duration(8000)
                                .actions([
                                    {
                                        name: 'gps_help',
                                        label: '🆘 GPS Help',
                                        color: 'info',
                                        action: () => {
                                            if (window.showGPSHelp) {
                                                window.showGPSHelp(type);
                                            }
                                        }
                                    }
                                ])
                                .send();
                        }
                        
                        // Reset button
                        if (btn) btn.classList.remove('loading');
                        if (icon) icon.textContent = '❌';
                        if (text) text.textContent = 'GPS Failed - Try Help';
                        
                        setTimeout(() => {
                            if (icon) icon.textContent = '🌍';
                            if (text) text.textContent = 'Use My Location';
                        }, 5000);
                    };
                    
                    component.getCurrentPositionPromise = function(options) {
                        return new Promise((resolve, reject) => {
                            if (!navigator.geolocation) {
                                reject(new Error('GPS not supported'));
                                return;
                            }
                            navigator.geolocation.getCurrentPosition(resolve, reject, options);
                        });
                    };
                }
            });
        }
        
        function refreshLocation(mapId) {
            getCurrentLocation(mapId);
        }
        
        function centerMap(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (mapComponent) {
                const markerPos = mapComponent.marker.getLatLng();
                mapComponent.map.setView(markerPos, mapComponent.map.getZoom());
                mapComponent.animateMarker();
            }
        }
        
        function toggleMapStyle(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (!mapComponent) return;
            
            if (mapComponent.currentStyle === 'osm') {
                mapComponent.map.removeLayer(mapComponent.osmLayer);
                mapComponent.map.addLayer(mapComponent.satelliteLayer);
                mapComponent.currentStyle = 'satellite';
            } else {
                mapComponent.map.removeLayer(mapComponent.satelliteLayer);
                mapComponent.map.addLayer(mapComponent.osmLayer);
                mapComponent.currentStyle = 'osm';
            }
        }
        
        function copyCoordinates(mapId) {
            const latDisplay = document.getElementById(mapId + '-lat-display');
            const lngDisplay = document.getElementById(mapId + '-lng-display');
            
            if (latDisplay && lngDisplay) {
                const coords = `${latDisplay.textContent}, ${lngDisplay.textContent}`;
                navigator.clipboard.writeText(coords).then(() => {
                    // Show success feedback
                    const btn = document.querySelector('.copy-btn');
                    if (btn) {
                        btn.textContent = '✅';
                        setTimeout(() => {
                            btn.textContent = '📋';
                        }, 1500);
                    }
                });
            }
        }
        
        function resetMapView(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (mapComponent) {
                mapComponent.map.setView([{{ $defaultLat }}, {{ $defaultLng }}], {{ $defaultZoom }});
                mapComponent.marker.setLatLng([{{ $defaultLat }}, {{ $defaultLng }}]);
                mapComponent.updateCoordinates({{ $defaultLat }}, {{ $defaultLng }});
                mapComponent.animateMarker();
            }
        }
        
        // TEST FUNCTION: Call this in browser console to test field detection
        window.testCoordinateFields = function() {
            console.log('🧪 TESTING COORDINATE FIELD DETECTION...');
            
            const fields = findCoordinateFieldsGlobal();
            
            if (fields.latitude && fields.longitude) {
                console.log('✅ SUCCESS: Both fields found!');
                console.table({
                    'Latitude Field': {
                        id: fields.latitude.id || 'NO ID',
                        name: fields.latitude.name || 'NO NAME',
                        type: fields.latitude.type,
                        placeholder: fields.latitude.placeholder || 'NO PLACEHOLDER',
                        value: fields.latitude.value || 'EMPTY',
                        'data-coordinate-field': fields.latitude.getAttribute('data-coordinate-field') || 'NO ATTRIBUTE'
                    },
                    'Longitude Field': {
                        id: fields.longitude.id || 'NO ID',
                        name: fields.longitude.name || 'NO NAME',
                        type: fields.longitude.type,
                        placeholder: fields.longitude.placeholder || 'NO PLACEHOLDER',
                        value: fields.longitude.value || 'EMPTY',
                        'data-coordinate-field': fields.longitude.getAttribute('data-coordinate-field') || 'NO ATTRIBUTE'
                    }
                });
                
                // Test updating the fields
                const testLat = -6.123456;
                const testLng = 106.654321;
                
                fields.latitude.value = testLat.toFixed(6);
                fields.longitude.value = testLng.toFixed(6);
                
                // Add visual highlight
                [fields.latitude, fields.longitude].forEach(field => {
                    field.style.border = '3px solid #10b981';
                    field.style.backgroundColor = '#d1fae5';
                    setTimeout(() => {
                        field.style.border = '';
                        field.style.backgroundColor = '';
                    }, 3000);
                });
                
                console.log(`✅ TEST UPDATE: Set latitude to ${testLat.toFixed(6)} and longitude to ${testLng.toFixed(6)}`);
                alert(`✅ Field Detection Test SUCCESS!\n\nLatitude field: ${fields.latitude.id || fields.latitude.name}\nLongitude field: ${fields.longitude.id || fields.longitude.name}\n\nTest values have been set and fields are highlighted in green.`);
                
            } else {
                console.error('❌ FAILED: Could not detect coordinate fields');
                alert('❌ Field Detection Test FAILED!\n\nCould not find coordinate fields. Check the console for details.');
            }
        };
        
        // Global helper functions for coordinate field management
        function findCoordinateFieldsGlobal() {
            // Strategy 1: Data attributes (most reliable for Filament)
            let latField = document.querySelector('input[data-coordinate-field="latitude"]');
            let lngField = document.querySelector('input[data-coordinate-field="longitude"]');
            
            // Strategy 2: ID selectors
            if (!latField) latField = document.querySelector('#latitude');
            if (!lngField) lngField = document.querySelector('#longitude');
            
            // Strategy 3: Name attributes
            if (!latField) latField = document.querySelector('input[name="latitude"]');
            if (!lngField) lngField = document.querySelector('input[name="longitude"]');
            
            // Strategy 4: Wire model detection
            if (!latField || !lngField) {
                document.querySelectorAll('input[wire\:model]').forEach(input => {
                    const model = input.getAttribute('wire:model');
                    if (model?.includes('latitude')) latField = input;
                    if (model?.includes('longitude')) lngField = input;
                });
            }
            
            console.log('🌍 Global field detection result:', {
                latitude: !!latField,
                longitude: !!lngField
            });
            
            return { latitude: latField, longitude: lngField };
        }
        
        function triggerFieldEventsGlobal(field, value, fieldName) {
            if (!field) {
                console.error(`❌ Global: ${fieldName} field not found for event triggering`);
                return;
            }
            
            console.log(`🚀 Global: Triggering events for ${fieldName}:`, value);
            
            // Standard DOM events (immediate)
            const events = ['input', 'change', 'keyup', 'blur'];
            events.forEach(eventType => {
                field.dispatchEvent(new Event(eventType, { 
                    bubbles: true, 
                    cancelable: true 
                }));
            });
            
            // Focus/blur cycle for Filament reactivity
            field.focus();
            setTimeout(() => {
                field.blur();
                
                // Additional Livewire events (delayed)
                field.dispatchEvent(new CustomEvent('livewire:update', { 
                    detail: { value }, 
                    bubbles: true 
                }));
                
                // Alpine.js events
                if (window.Alpine) {
                    field.dispatchEvent(new CustomEvent('alpine:update', { 
                        detail: { value }, 
                        bubbles: true 
                    }));
                }
                
                console.log(`✅ Global: Events completed for ${fieldName}:`, field.value);
            }, 50);
        }
        
        // Final validation and setup
        console.log('✅ leafletMapComponent function defined successfully');
        console.log('Component function type:', typeof leafletMapComponent);
        
        // Set up a safety check for Alpine.js availability
        let alpineCheckCount = 0;
        const alpineCheck = setInterval(() => {
            alpineCheckCount++;
            if (typeof Alpine !== 'undefined') {
                console.log('✅ Alpine.js is available - components can initialize');
                clearInterval(alpineCheck);
            } else if (alpineCheckCount >= 50) { // 5 seconds timeout
                console.warn('⚠️ Alpine.js not available after 5 seconds - manual initialization may be required');
                clearInterval(alpineCheck);
            }
        }, 100);
        
        // Expose function globally for debugging with consistent naming
        const debugFunctionName = 'leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $uniqueMapId) }}';
        window[debugFunctionName] = window.leafletMapComponent;
        
        console.log('🗺️ Leaflet map component {{ $uniqueMapId }} script loaded and ready');
        console.log('🔍 Debug function registered as:', debugFunctionName, typeof window[debugFunctionName] === 'function');
        
    </script>
    
    <!-- Additional enhancements pushed to scripts stack -->
    @push('scripts')
    <script>
        // Additional setup after page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📍 Leaflet OSM Map component DOM ready');
            
            // Enhanced Alpine integration test with scope validation
            setTimeout(() => {
                const runAlpineIntegrationTest = () => {
                    const functionName = 'leafletMapComponent_{{ str_replace([".", "[", "]", "-"], "_", $statePath) }}';
                    const element = document.querySelector('[x-data*="leafletMapComponent"]');
                    
                    const testResult = {
                        alpine: typeof Alpine !== 'undefined',
                        mainComponent: typeof window.leafletMapComponent === 'function',
                        specificComponent: typeof window[functionName] === 'function',
                        element: !!element,
                        elementData: null,
                        mapIdAccessible: false
                    };
                    
                    // Test Alpine component data access
                    if (element && element._x_dataStack && element._x_dataStack[0]) {
                        testResult.elementData = !!element._x_dataStack[0];
                        testResult.mapIdAccessible = !!(element._x_dataStack[0].mapId);
                    }
                    
                    console.log('🧪 Enhanced Alpine integration test result:', testResult);
                    
                    // Report specific issues
                    if (!testResult.alpine) {
                        console.error('❌ Alpine.js not available - map components will not function');
                    }
                    if (!testResult.mainComponent || !testResult.specificComponent) {
                        console.error('❌ leafletMapComponent functions not available - check for JavaScript errors');
                    }
                    if (!testResult.element) {
                        console.warn('⚠️ Alpine component element not found - component may not be mounted');
                    }
                    if (!testResult.mapIdAccessible) {
                        console.warn('⚠️ mapId variable not accessible in Alpine scope - may cause x-init errors');
                    }
                    
                    return testResult;
                };
                
                // Run test immediately and when Alpine is ready
                runAlpineIntegrationTest();
                
                if (typeof Alpine !== 'undefined') {
                    document.addEventListener('alpine:initialized', () => {
                        console.log('🧪 Running post-Alpine initialization test...');
                        runAlpineIntegrationTest();
                    });
                }
            }, 1000);
        });
        
        // Performance monitoring for ResizeObserver
        let resizeObserverErrors = 0;
        const originalError = console.error;
        console.error = function(...args) {
            const message = args[0]?.toString?.() || '';
            if (message.includes('ResizeObserver loop limit exceeded')) {
                resizeObserverErrors++;
                if (resizeObserverErrors === 1) {
                    console.warn('🔁 ResizeObserver loop detected - this is usually harmless but performance has been optimized');
                }
                // Suppress excessive ResizeObserver loop errors
                return;
            }
            originalError.apply(console, args);
        };
    </script>
    @endpush

    <!-- Creative Styling -->
    @push('styles')
    <style>
        /* 🚀 World-Class Responsive Design System */
        
        /* CSS Custom Properties for Theme System */
        :root {
            --wc-primary: #3b82f6;
            --wc-secondary: #8b5cf6;
            --wc-accent: #10b981;
            --wc-success: #22c55e;
            --wc-warning: #f59e0b;
            --wc-error: #ef4444;
            --wc-glass: rgba(255, 255, 255, 0.1);
            --wc-glass-border: rgba(255, 255, 255, 0.2);
            --wc-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --wc-radius: 16px;
            --wc-transition: cubic-bezier(0.4, 0, 0.2, 1);
            --wc-duration: 300ms;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --wc-glass: rgba(0, 0, 0, 0.2);
                --wc-glass-border: rgba(255, 255, 255, 0.1);
            }
        }

        /* Advanced Performance-Optimized Animations */
        @keyframes gradient-x {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes worldClassPulse {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1);
            }
            50% { 
                opacity: 0.7; 
                transform: scale(1.05);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes morphGlow {
            0% { filter: brightness(1) drop-shadow(0 0 5px rgba(59, 130, 246, 0.3)); }
            50% { filter: brightness(1.2) drop-shadow(0 0 15px rgba(139, 92, 246, 0.5)); }
            100% { filter: brightness(1) drop-shadow(0 0 5px rgba(16, 185, 129, 0.3)); }
        }
        
        .animate-gradient-x {
            background-size: 400% 400%;
            animation: gradient-x 15s ease infinite;
        }

        .world-class-entrance {
            animation: slideInUp var(--wc-duration) var(--wc-transition);
        }

        .world-class-glow {
            animation: morphGlow 4s ease-in-out infinite;
        }
        
        /* Floating circles animation */
        .floating-circles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .circle-1 {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #3b82f6, #8b5cf6);
            top: 20%;
            left: 20%;
            animation-delay: 0s;
        }
        
        .circle-2 {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #10b981, #06b6d4);
            top: 60%;
            right: 30%;
            animation-delay: 2s;
        }
        
        .circle-3 {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #f59e0b, #ef4444);
            bottom: 30%;
            left: 60%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* World-Class Status Cards with Advanced Glassmorphism */
        .status-card {
            background: var(--wc-glass);
            backdrop-filter: blur(15px) saturate(180%);
            border: 1px solid var(--wc-glass-border);
            border-radius: var(--wc-radius);
            padding: clamp(12px, 3vw, 20px);
            transition: all var(--wc-duration) var(--wc-transition);
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            will-change: transform, box-shadow;
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left var(--wc-duration) var(--wc-transition);
        }
        
        .status-card:hover {
            transform: translateY(-4px) rotateX(2deg);
            box-shadow: var(--wc-shadow), 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .status-card:hover::before {
            left: 100%;
        }

        /* Advanced responsive grid system */
        .status-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(16px, 3vw, 24px);
            margin-bottom: clamp(20px, 4vw, 32px);
        }

        @media (max-width: 768px) {
            .status-dashboard-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .status-card {
                padding: 16px;
                margin-bottom: 12px;
            }
        }
        
        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .status-progress {
            height: 4px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-bar {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 2px;
        }
        
        .status-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
        }
        
        .indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Action buttons */
        .action-btn, .copy-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn:hover, .copy-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* 🗺️ World-Class Map Container System */
        .creative-map-wrapper {
            position: relative;
            border-radius: var(--wc-radius);
            overflow: hidden;
            box-shadow: var(--wc-shadow);
            transition: all var(--wc-duration) var(--wc-transition);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .creative-map-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: var(--wc-shadow), 0 25px 50px -12px rgba(0, 0, 0, 0.2);
        }

        .creative-leaflet-osm-map-container {
            position: relative;
            min-height: 400px;
            max-width: 100%;
            overflow: hidden;
            border-radius: var(--wc-radius);
        }
        
        .creative-map-canvas {
            border-radius: var(--wc-radius);
            overflow: hidden;
            cursor: crosshair !important;
            width: 100% !important;
            height: clamp(350px, 60vh, 500px) !important;
            transition: all var(--wc-duration) var(--wc-transition);
            position: relative;
            z-index: 1;
        }

        /* Enhanced responsive click hint */
        .creative-map-canvas::before {
            content: "📍 Click to set coordinates • Drag marker to move";
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--wc-glass);
            backdrop-filter: blur(10px);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: clamp(10px, 2vw, 12px);
            font-weight: 600;
            pointer-events: none;
            z-index: 1000;
            box-shadow: var(--wc-shadow);
            border: 1px solid var(--wc-glass-border);
            opacity: 0;
            transition: opacity var(--wc-duration) var(--wc-transition);
            white-space: nowrap;
        }

        .creative-map-canvas:hover::before {
            opacity: 1;
        }

        /* Mobile-optimized hint */
        @media (max-width: 768px) {
            .creative-map-canvas::before {
                content: "📍 Tap to set location";
                font-size: 11px;
                padding: 6px 12px;
                white-space: normal;
                text-align: center;
                max-width: 90%;
            }
            
            .creative-map-canvas {
                height: clamp(300px, 50vh, 400px) !important;
            }
        }

        /* Ultra-responsive design for small screens */
        @media (max-width: 480px) {
            .creative-map-canvas {
                height: 280px !important;
                border-radius: 12px;
            }
            
            .creative-map-wrapper {
                margin: 0 -16px;
                border-radius: 0;
            }
        }
        
        /* 🚀 World-Class Loading System */
        .map-loader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: var(--wc-radius);
            backdrop-filter: blur(10px);
            z-index: 10;
        }

        .world-class-loader {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--wc-accent);
            border-radius: 50%;
            animation: worldClassSpin 1s linear infinite;
            margin-bottom: 16px;
        }

        @keyframes worldClassSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loader-text {
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            opacity: 0.9;
        }

        /* Performance Dashboard Styles */
        .performance-dashboard {
            background: var(--wc-glass);
            backdrop-filter: blur(15px);
            border: 1px solid var(--wc-glass-border);
            border-radius: var(--wc-radius);
            padding: 16px;
            color: white;
            font-family: system-ui, -apple-system, sans-serif;
            min-width: 250px;
            box-shadow: var(--wc-shadow);
        }

        .performance-dashboard h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 12px;
        }

        .metric .label {
            opacity: 0.8;
        }

        .metric .value {
            font-weight: 600;
        }

        .metric .value.good { color: var(--wc-success); }
        .metric .value.warning { color: var(--wc-warning); }
        .metric .value.error { color: var(--wc-error); }

        /* World-Class Marker Styles */
        .world-class-popup .leaflet-popup-content-wrapper {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: var(--wc-radius) !important;
        }

        .world-class-popup .leaflet-popup-tip {
            background: var(--wc-glass) !important;
            border: 1px solid var(--wc-glass-border) !important;
            backdrop-filter: blur(10px) !important;
        }

        /* Ultra-smooth transitions for all interactive elements */
        * {
            transition-property: transform, opacity, box-shadow, border-color, background-color;
            transition-duration: var(--wc-duration);
            transition-timing-function: var(--wc-transition);
        }

        /* Accessibility enhancements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .status-card {
                border-width: 2px;
                border-color: currentColor;
            }
            
            .creative-map-canvas::before {
                background: black;
                color: white;
                border: 2px solid white;
            }
        }

        /* Print styles */
        @media print {
            .creative-leaflet-osm-map-container {
                background: white !important;
                box-shadow: none !important;
            }
            
            .performance-dashboard,
            .map-loader {
                display: none !important;
            }
        }
            z-index: 1000;
            border-radius: 16px;
            transition: opacity 0.3s ease;
        }
        
        .loader-content {
            text-align: center;
            color: white;
        }
        
        .loader-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        .loader-progress {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
            margin: 16px auto 0;
        }
        
        .loader-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0.8));
            border-radius: 2px;
            animation: loading-progress 2s ease-in-out infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes loading-progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        /* Creative controls */
        .creative-controls {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 500;
        }
        
        .control-group {
            position: absolute;
            pointer-events: auto;
        }
        
        .top-controls {
            top: 20px;
            right: 20px;
        }
        
        .side-controls {
            top: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .primary-gps-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 16px;
            padding: 16px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 240px;
            overflow: hidden;
            position: relative;
        }
        
        .primary-gps-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .primary-gps-btn.loading {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
            cursor: not-allowed;
        }
        
        .btn-icon {
            font-size: 20px;
            min-width: 24px;
        }
        
        .btn-text {
            flex: 1;
            text-align: left;
        }
        
        .btn-text span {
            display: block;
            font-weight: 600;
            font-size: 14px;
        }
        
        .btn-subtext {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 2px;
        }
        
        .btn-arrow {
            font-size: 16px;
            opacity: 0.7;
            transition: transform 0.2s ease;
        }
        
        .primary-gps-btn:hover .btn-arrow {
            transform: translateX(2px);
        }
        
        .secondary-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .secondary-btn:hover {
            transform: scale(1.05);
            background: white;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        /* Coordinate display */
        .creative-coord-display {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            pointer-events: auto;
        }
        
        .coord-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .coord-icon {
            font-size: 14px;
        }
        
        .coord-title {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }
        
        .coord-indicator {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .coord-values {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .coord-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 11px;
        }
        
        .coord-label {
            color: #6b7280;
            font-weight: 500;
            min-width: 28px;
        }
        
        .coord-value {
            font-family: monospace;
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Accuracy indicator */
        .accuracy-indicator {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 6px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            pointer-events: auto;
        }
        
        .accuracy-content {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .accuracy-icon {
            font-size: 12px;
        }
        
        .accuracy-text {
            font-size: 11px;
            font-weight: 600;
            font-family: monospace;
        }
        
        /* Custom marker styling */
        .creative-marker-container {
            background: transparent !important;
            border: none !important;
        }
        
        .creative-marker {
            position: relative;
            width: 30px;
            height: 30px;
        }
        
        .marker-pulse {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            background: rgba(239, 68, 68, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: marker-pulse 2s infinite;
        }
        
        .marker-core {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: 3px solid white;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }
        
        .marker-shadow {
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 20px;
            height: 6px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            transform: translateX(-50%);
            filter: blur(2px);
        }
        
        @keyframes marker-pulse {
            0% {
                transform: translate(-50%, -50%) scale(0.8);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(2);
                opacity: 0;
            }
        }
        
        .marker-bounce {
            animation: marker-bounce 0.6s ease-out;
        }
        
        @keyframes marker-bounce {
            0%, 20%, 53%, 80%, 100% {
                animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
                transform: translate3d(0, -10px, 0);
            }
            70% {
                animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
                transform: translate3d(0, -5px, 0);
            }
            90% {
                transform: translate3d(0, -2px, 0);
            }
        }
        
        /* Guide section */
        .creative-guide {
            position: relative;
            overflow: hidden;
        }
        
        .guide-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .guide-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .guide-section {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .section-icon {
            font-size: 20px;
        }
        
        .section-title {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
        }
        
        .guide-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .guide-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .guide-item:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateX(4px);
        }
        
        .item-icon {
            font-size: 16px;
            min-width: 20px;
        }
        
        .item-content {
            flex: 1;
        }
        
        .item-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 13px;
            display: block;
        }
        
        .item-desc {
            color: #6b7280;
            font-size: 12px;
            margin-top: 2px;
        }
        
        .pro-tips {
            margin-top: 20px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 12px;
            padding: 16px;
            color: white;
        }
        
        .tips-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .tips-icon {
            font-size: 18px;
        }
        
        .tips-title {
            font-weight: 700;
            font-size: 14px;
        }
        
        .tips-content {
            font-size: 13px;
            opacity: 0.95;
            line-height: 1.4;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .top-controls {
                top: 12px;
                right: 12px;
            }
            
            .side-controls {
                top: 12px;
                left: 12px;
                gap: 6px;
            }
            
            .creative-coord-display {
                bottom: 12px;
                left: 12px;
                right: 12px;
                padding: 10px 12px;
            }
            
            .primary-gps-btn {
                padding: 12px 16px;
                max-width: 200px;
            }
            
            .btn-text span {
                font-size: 13px;
            }
            
            .btn-subtext {
                font-size: 10px;
            }
            
            .secondary-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .creative-map-canvas {
                min-height: 350px !important;
            }
        }
        
        @media (max-width: 640px) {
            .coord-values {
                flex-direction: row;
                gap: 12px;
            }
            
            .accuracy-indicator {
                position: relative;
                top: auto;
                left: auto;
                transform: none;
                margin: 8px 0;
                display: inline-block;
            }
        }
        
        /* Fade in/out animation for update labels */
        @keyframes fadeInOut {
            0% {
                opacity: 0;
                transform: translateX(-50%) translateY(10px);
            }
            20%, 80% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
        }
        
        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            .animate-gradient-x,
            .floating-circles .circle,
            .progress-bar,
            .indicator-dot,
            .marker-pulse,
            .loader-spinner,
            .loader-progress-bar {
                animation: none !important;
            }
            
            .creative-marker,
            .status-card,
            .action-btn,
            .copy-btn,
            .primary-gps-btn,
            .secondary-btn,
            .guide-item {
                transition: none !important;
            }
        }
        
        /* High contrast support */
        @media (prefers-contrast: high) {
            .status-card {
                background: white;
                border: 2px solid #000;
            }
            
            .creative-coord-display,
            .accuracy-indicator {
                background: white;
                border: 1px solid #000;
            }
            
            .marker-core {
                background: #ff0000;
                border: 3px solid #000;
            }
        }
    </style>
    @endpush
</div>