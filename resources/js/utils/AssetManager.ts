/**
 * 🚀 World-Class Asset Management System
 * 
 * Features:
 * - Intelligent asset loading with CDN fallbacks
 * - Local asset generation for missing resources
 * - Progressive enhancement and optimization
 * - Asset caching and preloading
 * - Error recovery and resilience
 * - Performance monitoring
 */

interface AssetConfig {
    url: string;
    fallbacks?: string[];
    type: 'image' | 'font' | 'css' | 'js' | 'svg';
    priority: 'high' | 'medium' | 'low';
    cache?: boolean;
    timeout?: number;
    retries?: number;
}

interface AssetMetrics {
    totalRequests: number;
    successfulLoads: number;
    failedLoads: number;
    fallbackUsage: number;
    averageLoadTime: number;
    cacheHitRate: number;
    generatedAssets: number;
}

interface GeneratedAssetOptions {
    type: 'marker-icon' | 'marker-shadow' | 'tile' | 'pattern';
    color?: string;
    size?: { width: number; height: number };
    style?: string;
    format?: 'svg' | 'png' | 'webp';
}

class AssetManager {
    private static instance: AssetManager;
    private cache: Map<string, { data: string | HTMLElement; timestamp: number }> = new Map();
    private loadingPromises: Map<string, Promise<any>> = new Map();
    private metrics: AssetMetrics;
    private retryDelays: number[] = [100, 500, 1500, 3000];

    constructor() {
        this.metrics = {
            totalRequests: 0,
            successfulLoads: 0,
            failedLoads: 0,
            fallbackUsage: 0,
            averageLoadTime: 0,
            cacheHitRate: 0,
            generatedAssets: 0
        };
    }

    static getInstance(): AssetManager {
        if (!AssetManager.instance) {
            AssetManager.instance = new AssetManager();
        }
        return AssetManager.instance;
    }

    async loadAsset(config: AssetConfig): Promise<string | HTMLElement | null> {
        const startTime = performance.now();
        this.metrics.totalRequests++;

        // Check cache first
        const cached = this.getCachedAsset(config.url);
        if (cached) {
            this.metrics.cacheHitRate = ++this.metrics.successfulLoads / this.metrics.totalRequests;
            return cached;
        }

        // Check if already loading
        if (this.loadingPromises.has(config.url)) {
            return await this.loadingPromises.get(config.url)!;
        }

        // Start loading process
        const loadPromise = this.performAssetLoad(config, startTime);
        this.loadingPromises.set(config.url, loadPromise);

        try {
            const result = await loadPromise;
            this.loadingPromises.delete(config.url);
            return result;
        } catch (error) {
            this.loadingPromises.delete(config.url);
            throw error;
        }
    }

    private async performAssetLoad(config: AssetConfig, startTime: number): Promise<string | HTMLElement | null> {
        const urls = [config.url, ...(config.fallbacks || [])];
        let lastError: Error | null = null;

        for (let i = 0; i < urls.length; i++) {
            const url = urls[i];
            const isOriginal = i === 0;

            try {
                const result = await this.loadSingleAsset(url, config);
                
                if (result) {
                    // Update metrics
                    const loadTime = performance.now() - startTime;
                    this.updateMetrics(true, loadTime, !isOriginal);

                    // Cache if enabled
                    if (config.cache !== false) {
                        this.cacheAsset(config.url, result);
                    }

                    return result;
                }
            } catch (error) {
                lastError = error as Error;
                console.warn(`Asset load failed for ${url}:`, error);
            }
        }

        // All URLs failed, try to generate fallback asset
        try {
            const generated = await this.generateFallbackAsset(config);
            if (generated) {
                this.metrics.generatedAssets++;
                this.updateMetrics(true, performance.now() - startTime, true);
                return generated;
            }
        } catch (error) {
            console.warn('Fallback asset generation failed:', error);
        }

        // Complete failure
        this.updateMetrics(false, performance.now() - startTime, false);
        throw lastError || new Error(`Failed to load asset: ${config.url}`);
    }

    private async loadSingleAsset(url: string, config: AssetConfig): Promise<string | HTMLElement | null> {
        return new Promise((resolve, reject) => {
            const timeout = config.timeout || 10000;
            let timeoutId: NodeJS.Timeout;

            const cleanup = () => {
                if (timeoutId) clearTimeout(timeoutId);
            };

            timeoutId = setTimeout(() => {
                cleanup();
                reject(new Error(`Asset load timeout: ${url}`));
            }, timeout);

            switch (config.type) {
                case 'image':
                    this.loadImage(url)
                        .then(result => { cleanup(); resolve(result); })
                        .catch(error => { cleanup(); reject(error); });
                    break;
                
                case 'css':
                    this.loadCSS(url)
                        .then(result => { cleanup(); resolve(result); })
                        .catch(error => { cleanup(); reject(error); });
                    break;
                
                case 'js':
                    this.loadScript(url)
                        .then(result => { cleanup(); resolve(result); })
                        .catch(error => { cleanup(); reject(error); });
                    break;
                
                case 'svg':
                    this.loadSVG(url)
                        .then(result => { cleanup(); resolve(result); })
                        .catch(error => { cleanup(); reject(error); });
                    break;
                
                case 'font':
                    this.loadFont(url)
                        .then(result => { cleanup(); resolve(result); })
                        .catch(error => { cleanup(); reject(error); });
                    break;
                
                default:
                    cleanup();
                    reject(new Error(`Unsupported asset type: ${config.type}`));
            }
        });
    }

    private loadImage(url: string): Promise<string> {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(url);
            img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
            img.src = url;
        });
    }

    private loadCSS(url: string): Promise<HTMLLinkElement> {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.onload = () => resolve(link);
            link.onerror = () => reject(new Error(`Failed to load CSS: ${url}`));
            document.head.appendChild(link);
        });
    }

    private loadScript(url: string): Promise<HTMLScriptElement> {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = () => resolve(script);
            script.onerror = () => reject(new Error(`Failed to load script: ${url}`));
            document.head.appendChild(script);
        });
    }

    private async loadSVG(url: string): Promise<string> {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Failed to load SVG: ${url}`);
        }
        return await response.text();
    }

    private loadFont(url: string): Promise<string> {
        return new Promise((resolve, reject) => {
            const font = new FontFace('CustomFont', `url(${url})`);
            font.load()
                .then(() => {
                    document.fonts.add(font);
                    resolve(url);
                })
                .catch(error => reject(error));
        });
    }

    private async generateFallbackAsset(config: AssetConfig): Promise<string | HTMLElement | null> {
        // Detect asset type from URL
        const url = config.url.toLowerCase();
        
        if (url.includes('marker-icon') || url.includes('marker-shadow')) {
            return await this.generateMarkerAsset(config);
        }
        
        if (url.includes('tile') || url.includes('png')) {
            return await this.generateTileAsset(config);
        }

        return null;
    }

    private async generateMarkerAsset(config: AssetConfig): Promise<string> {
        const isIcon = config.url.includes('marker-icon');
        const is2x = config.url.includes('2x');
        const isShadow = config.url.includes('shadow');

        const baseSize = is2x ? 50 : 25;
        const options: GeneratedAssetOptions = {
            type: isShadow ? 'marker-shadow' : 'marker-icon',
            color: '#3388ff',
            size: { width: baseSize, height: isShadow ? baseSize * 0.6 : baseSize * 1.6 },
            format: 'svg'
        };

        if (isShadow) {
            // Generate marker shadow SVG
            const svg = `
                <svg width="${options.size!.width}" height="${options.size!.height}" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="${options.size!.width / 2}" cy="${options.size!.height / 2}" 
                             rx="${options.size!.width * 0.4}" ry="${options.size!.height * 0.3}" 
                             fill="rgba(0, 0, 0, 0.2)" 
                             filter="blur(2px)" />
                </svg>
            `;
            
            // Convert to data URL
            return `data:image/svg+xml;base64,${btoa(svg)}`;
        } else {
            // Generate marker icon SVG
            const svg = `
                <svg width="${options.size!.width}" height="${options.size!.height}" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="markerGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3388ff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1166cc;stop-opacity:1" />
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <path d="M${baseSize/2} 5 C${baseSize*0.75} 5 ${baseSize*0.9} ${baseSize*0.3} ${baseSize*0.9} ${baseSize*0.5} C${baseSize*0.9} ${baseSize*0.8} ${baseSize/2} ${baseSize*1.4} ${baseSize/2} ${baseSize*1.4} C${baseSize/2} ${baseSize*1.4} ${baseSize*0.1} ${baseSize*0.8} ${baseSize*0.1} ${baseSize*0.5} C${baseSize*0.1} ${baseSize*0.3} ${baseSize*0.25} 5 ${baseSize/2} 5 Z" 
                          fill="url(#markerGrad)" 
                          stroke="white" 
                          stroke-width="2" 
                          filter="url(#shadow)" />
                    <circle cx="${baseSize/2}" cy="${baseSize*0.5}" r="${baseSize*0.15}" fill="white" />
                </svg>
            `;
            
            return `data:image/svg+xml;base64,${btoa(svg)}`;
        }
    }

    private async generateTileAsset(config: AssetConfig): Promise<string> {
        // Generate a placeholder tile
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d')!;
        canvas.width = 256;
        canvas.height = 256;

        // Create gradient background
        const gradient = ctx.createLinearGradient(0, 0, 256, 256);
        gradient.addColorStop(0, '#f0f4f8');
        gradient.addColorStop(1, '#e2e8f0');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 256, 256);

        // Add pattern
        ctx.strokeStyle = '#cbd5e0';
        ctx.lineWidth = 1;
        for (let i = 0; i < 256; i += 32) {
            ctx.beginPath();
            ctx.moveTo(i, 0);
            ctx.lineTo(i, 256);
            ctx.stroke();
            
            ctx.beginPath();
            ctx.moveTo(0, i);
            ctx.lineTo(256, i);
            ctx.stroke();
        }

        // Add text
        ctx.fillStyle = '#a0aec0';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Map Tile', 128, 128);

        return canvas.toDataURL('image/png');
    }

    private getCachedAsset(url: string): string | HTMLElement | null {
        const cached = this.cache.get(url);
        if (!cached) return null;

        // Check cache expiration (1 hour default)
        const now = Date.now();
        const maxAge = 60 * 60 * 1000; // 1 hour
        
        if (now - cached.timestamp > maxAge) {
            this.cache.delete(url);
            return null;
        }

        return cached.data;
    }

    private cacheAsset(url: string, data: string | HTMLElement): void {
        this.cache.set(url, {
            data,
            timestamp: Date.now()
        });

        // Limit cache size
        if (this.cache.size > 100) {
            const oldestKey = this.cache.keys().next().value;
            this.cache.delete(oldestKey);
        }
    }

    private updateMetrics(success: boolean, loadTime: number, usedFallback: boolean): void {
        if (success) {
            this.metrics.successfulLoads++;
        } else {
            this.metrics.failedLoads++;
        }

        if (usedFallback) {
            this.metrics.fallbackUsage++;
        }

        // Update average load time
        const totalLoads = this.metrics.successfulLoads + this.metrics.failedLoads;
        this.metrics.averageLoadTime = (this.metrics.averageLoadTime * (totalLoads - 1) + loadTime) / totalLoads;

        // Update cache hit rate
        this.metrics.cacheHitRate = this.metrics.successfulLoads / this.metrics.totalRequests;
    }

    // Public utility methods
    preloadAssets(configs: AssetConfig[]): Promise<(string | HTMLElement | null)[]> {
        return Promise.allSettled(
            configs.map(config => this.loadAsset(config))
        ).then(results => 
            results.map(result => 
                result.status === 'fulfilled' ? result.value : null
            )
        );
    }

    clearCache(): void {
        this.cache.clear();
        console.log('Asset cache cleared');
    }

    getMetrics(): AssetMetrics {
        return { ...this.metrics };
    }

    createPerformanceDashboard(): HTMLElement {
        const dashboard = document.createElement('div');
        dashboard.className = 'asset-performance-dashboard';
        dashboard.innerHTML = `
            <div class="performance-panel">
                <h3>📦 Asset Performance</h3>
                <div class="metrics-grid">
                    <div class="metric">
                        <span class="label">Success Rate:</span>
                        <span class="value" id="asset-success-rate">0%</span>
                    </div>
                    <div class="metric">
                        <span class="label">Cache Hit Rate:</span>
                        <span class="value" id="asset-cache-rate">0%</span>
                    </div>
                    <div class="metric">
                        <span class="label">Fallback Usage:</span>
                        <span class="value" id="asset-fallback-rate">0%</span>
                    </div>
                    <div class="metric">
                        <span class="label">Avg Load Time:</span>
                        <span class="value" id="asset-load-time">0ms</span>
                    </div>
                    <div class="metric">
                        <span class="label">Generated Assets:</span>
                        <span class="value" id="asset-generated">${this.metrics.generatedAssets}</span>
                    </div>
                </div>
            </div>
        `;

        // Update dashboard periodically
        const updateDashboard = () => {
            const metrics = this.getMetrics();
            const successRate = metrics.totalRequests > 0 
                ? Math.round((metrics.successfulLoads / metrics.totalRequests) * 100) 
                : 0;
            const cacheRate = Math.round(metrics.cacheHitRate * 100);
            const fallbackRate = metrics.totalRequests > 0 
                ? Math.round((metrics.fallbackUsage / metrics.totalRequests) * 100) 
                : 0;

            const successEl = dashboard.querySelector('#asset-success-rate');
            const cacheEl = dashboard.querySelector('#asset-cache-rate');
            const fallbackEl = dashboard.querySelector('#asset-fallback-rate');
            const loadTimeEl = dashboard.querySelector('#asset-load-time');
            const generatedEl = dashboard.querySelector('#asset-generated');

            if (successEl) {
                successEl.textContent = `${successRate}%`;
                successEl.className = `value ${successRate > 90 ? 'good' : successRate > 70 ? 'warning' : 'error'}`;
            }
            if (cacheEl) cacheEl.textContent = `${cacheRate}%`;
            if (fallbackEl) fallbackEl.textContent = `${fallbackRate}%`;
            if (loadTimeEl) loadTimeEl.textContent = `${Math.round(metrics.averageLoadTime)}ms`;
            if (generatedEl) generatedEl.textContent = metrics.generatedAssets.toString();
        };

        updateDashboard();
        setInterval(updateDashboard, 2000);

        return dashboard;
    }

    // Leaflet-specific asset management
    async setupLeafletAssets(): Promise<void> {
        const leafletAssets: AssetConfig[] = [
            {
                url: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                fallbacks: [
                    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png'
                ],
                type: 'image',
                priority: 'high',
                cache: true
            },
            {
                url: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                fallbacks: [
                    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png'
                ],
                type: 'image',
                priority: 'medium',
                cache: true
            },
            {
                url: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                fallbacks: [
                    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png'
                ],
                type: 'image',
                priority: 'medium',
                cache: true
            }
        ];

        try {
            await this.preloadAssets(leafletAssets);
            console.log('✅ Leaflet assets loaded successfully');
        } catch (error) {
            console.warn('⚠️ Some Leaflet assets failed to load, fallbacks generated');
        }
    }
}

// Export singleton instance
const assetManager = AssetManager.getInstance();

export default assetManager;
export { AssetConfig, AssetMetrics, GeneratedAssetOptions, AssetManager };