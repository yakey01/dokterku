/**
 * 🎨 World-Class Custom Marker System for Leaflet Maps
 * 
 * Features:
 * - Beautiful custom SVG markers with gradients and shadows
 * - Location-specific icons (hospital, clinic, office, etc.)
 * - Pulsing animations for active markers
 * - Marker clustering for multiple locations
 * - Glassmorphism popup designs
 * - Theme-aware color schemes
 * - Responsive design patterns
 */

interface MarkerTheme {
    primary: string;
    secondary: string;
    accent: string;
    shadow: string;
    glow: string;
}

interface CustomMarkerOptions {
    type?: 'hospital' | 'clinic' | 'office' | 'pharmacy' | 'lab' | 'emergency' | 'default';
    theme?: 'medical' | 'corporate' | 'emergency' | 'eco' | 'luxury' | 'dark';
    size?: 'small' | 'medium' | 'large' | 'xl';
    animated?: boolean;
    pulsing?: boolean;
    glowing?: boolean;
    shadowIntensity?: 'none' | 'light' | 'medium' | 'strong';
    customIcon?: string;
    className?: string;
}

interface PopupOptions {
    title?: string;
    description?: string;
    imageUrl?: string;
    actions?: Array<{ label: string; action: () => void; style?: string }>;
    theme?: 'glass' | 'modern' | 'minimal' | 'luxury';
    maxWidth?: number;
}

class CustomMarkerSystem {
    private static themes: Map<string, MarkerTheme> = new Map([
        ['medical', {
            primary: '#e53e3e',
            secondary: '#ffffff',
            accent: '#3182ce',
            shadow: 'rgba(229, 62, 62, 0.3)',
            glow: 'rgba(229, 62, 62, 0.5)'
        }],
        ['corporate', {
            primary: '#3182ce',
            secondary: '#ffffff',
            accent: '#38a169',
            shadow: 'rgba(49, 130, 206, 0.3)',
            glow: 'rgba(49, 130, 206, 0.5)'
        }],
        ['emergency', {
            primary: '#e53e3e',
            secondary: '#ffffff',
            accent: '#f56565',
            shadow: 'rgba(229, 62, 62, 0.4)',
            glow: 'rgba(245, 101, 101, 0.6)'
        }],
        ['eco', {
            primary: '#38a169',
            secondary: '#ffffff',
            accent: '#68d391',
            shadow: 'rgba(56, 161, 105, 0.3)',
            glow: 'rgba(56, 161, 105, 0.5)'
        }],
        ['luxury', {
            primary: '#805ad5',
            secondary: '#ffffff',
            accent: '#d69e2e',
            shadow: 'rgba(128, 90, 213, 0.3)',
            glow: 'rgba(128, 90, 213, 0.5)'
        }],
        ['dark', {
            primary: '#2d3748',
            secondary: '#ffffff',
            accent: '#4299e1',
            shadow: 'rgba(45, 55, 72, 0.4)',
            glow: 'rgba(66, 153, 225, 0.5)'
        }]
    ]);

    private static iconMap: Map<string, string> = new Map([
        ['hospital', '🏥'],
        ['clinic', '🏨'],
        ['office', '🏢'],
        ['pharmacy', '💊'],
        ['lab', '🔬'],
        ['emergency', '🚑'],
        ['default', '📍']
    ]);

    private static sizeMap: Map<string, { width: number; height: number }> = new Map([
        ['small', { width: 24, height: 24 }],
        ['medium', { width: 32, height: 32 }],
        ['large', { width: 40, height: 40 }],
        ['xl', { width: 48, height: 48 }]
    ]);

    static createCustomMarker(options: CustomMarkerOptions = {}): L.DivIcon {
        const {
            type = 'default',
            theme = 'medical',
            size = 'medium',
            animated = true,
            pulsing = false,
            glowing = false,
            shadowIntensity = 'medium',
            customIcon,
            className = ''
        } = options;

        const markerTheme = this.themes.get(theme) || this.themes.get('medical')!;
        const dimensions = this.sizeMap.get(size) || this.sizeMap.get('medium')!;
        const icon = customIcon || this.iconMap.get(type) || this.iconMap.get('default')!;

        const svgIcon = this.generateSVGIcon({
            theme: markerTheme,
            dimensions,
            icon,
            animated,
            pulsing,
            glowing,
            shadowIntensity
        });

        return L.divIcon({
            html: svgIcon,
            className: `custom-marker-container ${className} ${animated ? 'animated' : ''} ${pulsing ? 'pulsing' : ''} ${glowing ? 'glowing' : ''}`,
            iconSize: [dimensions.width, dimensions.height],
            iconAnchor: [dimensions.width / 2, dimensions.height],
            popupAnchor: [0, -dimensions.height]
        });
    }

    private static generateSVGIcon(config: {
        theme: MarkerTheme;
        dimensions: { width: number; height: number };
        icon: string;
        animated: boolean;
        pulsing: boolean;
        glowing: boolean;
        shadowIntensity: string;
    }): string {
        const { theme, dimensions, icon, animated, pulsing, glowing, shadowIntensity } = config;
        const { width, height } = dimensions;
        
        const shadowOpacity = {
            none: 0,
            light: 0.1,
            medium: 0.2,
            strong: 0.4
        }[shadowIntensity] || 0.2;

        const markerId = `marker-${Math.random().toString(36).substr(2, 9)}`;
        const glowId = `glow-${markerId}`;
        const pulseId = `pulse-${markerId}`;

        return `
            <div class="marker-wrapper" style="position: relative; width: ${width}px; height: ${height}px;">
                <!-- Pulsing Animation Ring -->
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

                <!-- Glowing Effect -->
                ${glowing ? `
                <div class="marker-glow" style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    width: ${width * 1.4}px;
                    height: ${width * 1.4}px;
                    background: radial-gradient(circle, ${theme.glow} 0%, transparent 70%);
                    border-radius: 50%;
                    transform: translate(-50%, -50%);
                    animation: markerGlow 3s infinite alternate ease-in-out;
                    z-index: 2;
                "></div>
                ` : ''}

                <!-- Main SVG Marker -->
                <svg width="${width}" height="${height}" viewBox="0 0 32 32" class="marker-svg" style="
                    position: relative;
                    z-index: 3;
                    filter: drop-shadow(0 ${height * 0.1}px ${height * 0.2}px rgba(0, 0, 0, ${shadowOpacity}));
                    ${animated ? 'transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);' : ''}
                ">
                    <!-- Gradient Definitions -->
                    <defs>
                        <linearGradient id="${markerId}-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:${theme.primary};stop-opacity:1" />
                            <stop offset="100%" style="stop-color:${theme.accent};stop-opacity:1" />
                        </linearGradient>
                        <filter id="${glowId}" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                            <feMerge> 
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                        <radialGradient id="${markerId}-radial" cx="50%" cy="30%" r="70%">
                            <stop offset="0%" style="stop-color:${theme.secondary};stop-opacity:0.9" />
                            <stop offset="70%" style="stop-color:${theme.primary};stop-opacity:1" />
                            <stop offset="100%" style="stop-color:${theme.accent};stop-opacity:1" />
                        </radialGradient>
                    </defs>

                    <!-- Marker Shape with Gradient -->
                    <path d="M16 2 C10.5 2 6 6.5 6 12 C6 20 16 30 16 30 C16 30 26 20 26 12 C26 6.5 21.5 2 16 2 Z" 
                          fill="url(#${markerId}-radial)" 
                          stroke="${theme.secondary}" 
                          stroke-width="1"
                          ${glowing ? `filter="url(#${glowId})"` : ''}
                    />

                    <!-- Inner Circle -->
                    <circle cx="16" cy="12" r="6" 
                            fill="${theme.secondary}" 
                            stroke="${theme.primary}" 
                            stroke-width="1.5"
                            opacity="0.95" />

                    <!-- Icon Container -->
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

                    <!-- Highlight Effect -->
                    <ellipse cx="20" cy="8" rx="3" ry="2" 
                             fill="${theme.secondary}" 
                             opacity="0.4" />
                </svg>

                <!-- Animated Bounce Effect -->
                ${animated ? `
                <style>
                    .marker-wrapper:hover .marker-svg {
                        transform: scale(1.1) translateY(-2px);
                        filter: drop-shadow(0 ${height * 0.15}px ${height * 0.3}px rgba(0, 0, 0, ${shadowOpacity * 1.5}));
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

                @keyframes markerGlow {
                    0% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
                    100% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); }
                }

                @keyframes markerBounce {
                    0%, 20%, 53%, 80%, 100% { transform: translate3d(0, 0, 0); }
                    40%, 43% { transform: translate3d(0, -8px, 0); }
                    70% { transform: translate3d(0, -4px, 0); }
                    90% { transform: translate3d(0, -2px, 0); }
                }

                .marker-wrapper.animated .marker-svg {
                    animation: markerBounce 2s infinite;
                }
            </style>
        `;
    }

    static createGlassmorphicPopup(content: PopupOptions = {}): string {
        const {
            title = 'Location',
            description = '',
            imageUrl = '',
            actions = [],
            theme = 'glass',
            maxWidth = 300
        } = content;

        const popupId = `popup-${Math.random().toString(36).substr(2, 9)}`;

        const themeStyles = {
            glass: {
                background: 'rgba(255, 255, 255, 0.25)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255, 255, 255, 0.18)',
                boxShadow: '0 8px 32px 0 rgba(31, 38, 135, 0.37)'
            },
            modern: {
                background: 'linear-gradient(145deg, #ffffff 0%, #f0f4f8 100%)',
                backdropFilter: 'none',
                border: '1px solid rgba(0, 0, 0, 0.1)',
                boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
            },
            minimal: {
                background: '#ffffff',
                backdropFilter: 'none',
                border: '1px solid #e2e8f0',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
            },
            luxury: {
                background: 'linear-gradient(145deg, #1a202c 0%, #2d3748 100%)',
                backdropFilter: 'blur(15px)',
                border: '1px solid rgba(255, 255, 255, 0.1)',
                boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.4)'
            }
        };

        const style = themeStyles[theme] || themeStyles.glass;
        const textColor = theme === 'luxury' ? '#ffffff' : '#2d3748';
        const subTextColor = theme === 'luxury' ? 'rgba(255, 255, 255, 0.8)' : '#718096';

        return `
            <div id="${popupId}" class="glassmorphic-popup" style="
                max-width: ${maxWidth}px;
                background: ${style.background};
                backdrop-filter: ${style.backdropFilter};
                border: ${style.border};
                border-radius: 16px;
                box-shadow: ${style.boxShadow};
                padding: 0;
                margin: 0;
                overflow: hidden;
                position: relative;
            ">
                <!-- Image Header -->
                ${imageUrl ? `
                <div class="popup-image" style="
                    width: 100%;
                    height: 120px;
                    background-image: url('${imageUrl}');
                    background-size: cover;
                    background-position: center;
                    position: relative;
                ">
                    <div style="
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        height: 50%;
                        background: linear-gradient(transparent, rgba(0, 0, 0, 0.6));
                    "></div>
                </div>
                ` : ''}

                <!-- Content -->
                <div class="popup-content" style="padding: 20px;">
                    <!-- Title -->
                    <h3 style="
                        margin: 0 0 8px 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: ${textColor};
                        line-height: 1.3;
                    ">${title}</h3>

                    <!-- Description -->
                    ${description ? `
                    <p style="
                        margin: 0 0 16px 0;
                        font-size: 14px;
                        color: ${subTextColor};
                        line-height: 1.5;
                    ">${description}</p>
                    ` : ''}

                    <!-- Actions -->
                    ${actions.length > 0 ? `
                    <div class="popup-actions" style="
                        display: flex;
                        gap: 8px;
                        flex-wrap: wrap;
                        margin-top: 16px;
                    ">
                        ${actions.map((action, index) => `
                        <button 
                            onclick="window.popupActions['${popupId}-${index}']()"
                            style="
                                padding: 8px 16px;
                                border: none;
                                border-radius: 8px;
                                background: rgba(59, 130, 246, 0.8);
                                color: white;
                                font-size: 12px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                backdrop-filter: blur(10px);
                                ${action.style || ''}
                            "
                            onmouseover="this.style.transform='scale(1.05)'; this.style.background='rgba(59, 130, 246, 0.9)'"
                            onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(59, 130, 246, 0.8)'"
                        >
                            ${action.label}
                        </button>
                        `).join('')}
                    </div>
                    ` : ''}
                </div>

                <!-- Decorative Elements -->
                <div style="
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    width: 40px;
                    height: 40px;
                    background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
                    border-radius: 50%;
                    opacity: 0.6;
                "></div>

                <div style="
                    position: absolute;
                    bottom: 12px;
                    left: 12px;
                    width: 20px;
                    height: 20px;
                    background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
                    border-radius: 50%;
                    opacity: 0.8;
                "></div>
            </div>

            <style>
                .glassmorphic-popup {
                    animation: popupFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                @keyframes popupFadeIn {
                    from {
                        opacity: 0;
                        transform: scale(0.9) translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1) translateY(0);
                    }
                }

                .glassmorphic-popup:hover {
                    box-shadow: ${style.boxShadow.replace('0.1', '0.15').replace('0.04', '0.08')};
                    transform: translateY(-2px);
                    transition: all 0.3s ease;
                }
            </style>

            <script>
                // Store popup actions globally
                if (!window.popupActions) {
                    window.popupActions = {};
                }
                ${actions.map((action, index) => `
                window.popupActions['${popupId}-${index}'] = ${action.action.toString()};
                `).join('')}
            </script>
        `;
    }

    static createMarkerCluster(markers: Array<{
        lat: number;
        lng: number;
        options?: CustomMarkerOptions;
        popup?: PopupOptions;
    }>): L.MarkerClusterGroup {
        // This would require the Leaflet.markercluster plugin
        // For now, we'll return a basic implementation
        const markerClusterGroup = (L as any).markerClusterGroup({
            iconCreateFunction: (cluster: any) => {
                const count = cluster.getChildCount();
                let className = 'marker-cluster-small';
                
                if (count < 10) {
                    className = 'marker-cluster-small';
                } else if (count < 100) {
                    className = 'marker-cluster-medium';
                } else {
                    className = 'marker-cluster-large';
                }
                
                return L.divIcon({
                    html: `
                        <div class="cluster-inner">
                            <div class="cluster-count">${count}</div>
                            <div class="cluster-pulse"></div>
                        </div>
                    `,
                    className: `marker-cluster ${className}`,
                    iconSize: [40, 40]
                });
            }
        });

        markers.forEach(markerData => {
            const marker = L.marker([markerData.lat, markerData.lng], {
                icon: this.createCustomMarker(markerData.options)
            });

            if (markerData.popup) {
                marker.bindPopup(this.createGlassmorphicPopup(markerData.popup));
            }

            markerClusterGroup.addLayer(marker);
        });

        return markerClusterGroup;
    }

    static injectStyles(): void {
        if (document.getElementById('custom-marker-styles')) return;

        const styles = `
            <style id="custom-marker-styles">
                .custom-marker-container {
                    background: transparent !important;
                    border: none !important;
                    cursor: pointer;
                }

                .marker-cluster {
                    background-color: rgba(59, 130, 246, 0.8) !important;
                    border: 3px solid rgba(255, 255, 255, 0.9) !important;
                    border-radius: 50% !important;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
                    backdrop-filter: blur(10px) !important;
                }

                .marker-cluster-small {
                    width: 30px !important;
                    height: 30px !important;
                }

                .marker-cluster-medium {
                    width: 40px !important;
                    height: 40px !important;
                }

                .marker-cluster-large {
                    width: 50px !important;
                    height: 50px !important;
                }

                .cluster-inner {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .cluster-count {
                    color: white;
                    font-weight: bold;
                    font-size: 12px;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
                    position: relative;
                    z-index: 2;
                }

                .cluster-pulse {
                    position: absolute;
                    top: -5px;
                    left: -5px;
                    right: -5px;
                    bottom: -5px;
                    border: 2px solid rgba(59, 130, 246, 0.5);
                    border-radius: 50%;
                    animation: clusterPulse 2s infinite;
                }

                @keyframes clusterPulse {
                    0% { transform: scale(0.8); opacity: 1; }
                    100% { transform: scale(1.3); opacity: 0; }
                }

                .leaflet-popup-content-wrapper {
                    padding: 0 !important;
                    background: transparent !important;
                    border-radius: 16px !important;
                    box-shadow: none !important;
                }

                .leaflet-popup-content {
                    margin: 0 !important;
                }

                .leaflet-popup-tip {
                    background: rgba(255, 255, 255, 0.25) !important;
                    backdrop-filter: blur(10px) !important;
                    border: 1px solid rgba(255, 255, 255, 0.18) !important;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }
}

// Auto-inject styles when module loads
if (typeof window !== 'undefined') {
    CustomMarkerSystem.injectStyles();
}

export default CustomMarkerSystem;
export { CustomMarkerOptions, PopupOptions, MarkerTheme };