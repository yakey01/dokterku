/**
 * Dynamic Bundle Loader - TDZ-Safe Implementation
 * Handles dynamic loading of JavaScript bundles with proper error handling
 * and dependency resolution.
 */

interface ManifestEntry {
    file: string;
    name: string;
    src?: string;
    isEntry?: boolean;
    imports?: string[];
    css?: string[];
    assets?: string[];
}

interface LoaderOptions {
    baseUrl?: string;
    timeout?: number;
    retries?: number;
    cacheBusting?: boolean;
}

class DynamicBundleLoaderError extends Error {
    constructor(message: string, public code: string, public details?: any) {
        super(message);
        this.name = 'DynamicBundleLoaderError';
    }
}

export class DynamicBundleLoader {
    private static instance: DynamicBundleLoader | null = null;
    private manifest: Record<string, ManifestEntry> | null = null;
    private loadedBundles = new Set<string>();
    private loadingPromises = new Map<string, Promise<void>>();
    private baseUrl: string;
    private options: Required<LoaderOptions>;

    private constructor(options: LoaderOptions = {}) {
        this.options = {
            baseUrl: '/build',
            timeout: 30000,
            retries: 3,
            cacheBusting: false,
            ...options
        };
        this.baseUrl = this.options.baseUrl;
        
        // TDZ-safe initialization
        this.initializeLoader();
    }

    /**
     * Get singleton instance - TDZ safe
     */
    public static getInstance(options?: LoaderOptions): DynamicBundleLoader {
        if (!DynamicBundleLoader.instance) {
            DynamicBundleLoader.instance = new DynamicBundleLoader(options);
        }
        return DynamicBundleLoader.instance;
    }

    /**
     * Initialize loader with proper error handling
     */
    private initializeLoader(): void {
        try {
            // Prevent multiple initializations
            if (this.manifest !== null) {
                return;
            }

            // Set up error handlers
            this.setupErrorHandlers();
            
            console.log('🔧 DynamicBundleLoader initialized');
        } catch (error) {
            console.error('❌ Failed to initialize DynamicBundleLoader:', error);
            throw new DynamicBundleLoaderError(
                'Initialization failed', 
                'INIT_ERROR', 
                error
            );
        }
    }

    /**
     * Set up global error handlers
     */
    private setupErrorHandlers(): void {
        if (typeof window !== 'undefined') {
            window.addEventListener('error', this.handleGlobalError.bind(this));
            window.addEventListener('unhandledrejection', this.handleUnhandledRejection.bind(this));
        }
    }

    /**
     * Handle global script errors
     */
    private handleGlobalError(event: ErrorEvent): void {
        if (event.filename && event.filename.includes('/build/')) {
            console.error('🚨 Bundle load error:', {
                filename: event.filename,
                message: event.message,
                line: event.lineno,
                col: event.colno
            });
        }
    }

    /**
     * Handle unhandled promise rejections
     */
    private handleUnhandledRejection(event: PromiseRejectionEvent): void {
        if (event.reason && typeof event.reason === 'object' && 
            event.reason.message && event.reason.message.includes('bundle')) {
            console.error('🚨 Bundle promise rejection:', event.reason);
        }
    }

    /**
     * Load and cache manifest file
     */
    public async loadManifest(): Promise<Record<string, ManifestEntry>> {
        if (this.manifest) {
            return this.manifest;
        }

        try {
            const manifestUrl = `${this.baseUrl}/manifest.json${this.options.cacheBusting ? '?t=' + Date.now() : ''}`;
            
            const response = await this.fetchWithTimeout(manifestUrl, this.options.timeout);
            
            if (!response.ok) {
                throw new Error(`Failed to fetch manifest: ${response.status} ${response.statusText}`);
            }

            const manifest = await response.json();
            
            if (!manifest || typeof manifest !== 'object') {
                throw new Error('Invalid manifest format');
            }

            this.manifest = manifest;
            console.log(`✅ Manifest loaded with ${Object.keys(manifest).length} entries`);
            
            return manifest;
        } catch (error) {
            console.error('❌ Failed to load manifest:', error);
            throw new DynamicBundleLoaderError(
                'Failed to load manifest', 
                'MANIFEST_ERROR', 
                error
            );
        }
    }

    /**
     * Load a bundle by entry key
     */
    public async loadBundle(entryKey: string): Promise<void> {
        // Prevent duplicate loading
        if (this.loadedBundles.has(entryKey)) {
            return;
        }

        // Return existing loading promise if in progress
        if (this.loadingPromises.has(entryKey)) {
            return this.loadingPromises.get(entryKey)!;
        }

        const loadPromise = this.doLoadBundle(entryKey);
        this.loadingPromises.set(entryKey, loadPromise);

        try {
            await loadPromise;
            this.loadedBundles.add(entryKey);
        } finally {
            this.loadingPromises.delete(entryKey);
        }
    }

    /**
     * Internal bundle loading implementation
     */
    private async doLoadBundle(entryKey: string): Promise<void> {
        try {
            // Load manifest if not already loaded
            const manifest = await this.loadManifest();
            
            const entry = manifest[entryKey];
            if (!entry) {
                throw new Error(`Bundle entry "${entryKey}" not found in manifest`);
            }

            // Load dependencies first
            if (entry.imports && entry.imports.length > 0) {
                await Promise.all(
                    entry.imports.map(importKey => this.loadBundle(importKey))
                );
            }

            // Load CSS dependencies
            if (entry.css && entry.css.length > 0) {
                await Promise.all(
                    entry.css.map(cssFile => this.loadStylesheet(cssFile))
                );
            }

            // Load the main JavaScript bundle
            await this.loadScript(entry.file);

            console.log(`✅ Bundle loaded: ${entryKey}`);
        } catch (error) {
            console.error(`❌ Failed to load bundle "${entryKey}":`, error);
            throw new DynamicBundleLoaderError(
                `Failed to load bundle: ${entryKey}`, 
                'BUNDLE_LOAD_ERROR', 
                error
            );
        }
    }

    /**
     * Load a JavaScript file dynamically
     */
    private async loadScript(filename: string): Promise<void> {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            const src = `${this.baseUrl}/${filename}${this.options.cacheBusting ? '?t=' + Date.now() : ''}`;

            script.src = src;
            script.type = 'module';
            script.async = true;

            let timeoutId: number;

            const cleanup = () => {
                clearTimeout(timeoutId);
                script.removeEventListener('load', onLoad);
                script.removeEventListener('error', onError);
            };

            const onLoad = () => {
                cleanup();
                console.log(`✅ Script loaded: ${filename}`);
                resolve();
            };

            const onError = (event: Event | ErrorEvent) => {
                cleanup();
                document.head.removeChild(script);
                
                const error = new Error(`Failed to load script: ${filename}`);
                console.error('❌ Script load error:', error);
                reject(error);
            };

            // Set up timeout
            timeoutId = window.setTimeout(() => {
                cleanup();
                document.head.removeChild(script);
                
                const error = new Error(`Script load timeout: ${filename}`);
                console.error('⏰ Script load timeout:', error);
                reject(error);
            }, this.options.timeout);

            script.addEventListener('load', onLoad);
            script.addEventListener('error', onError);

            document.head.appendChild(script);
        });
    }

    /**
     * Load a CSS stylesheet dynamically
     */
    private async loadStylesheet(filename: string): Promise<void> {
        return new Promise((resolve, reject) => {
            // Check if already loaded
            const existingLink = document.querySelector(`link[href*="${filename}"]`);
            if (existingLink) {
                resolve();
                return;
            }

            const link = document.createElement('link');
            const href = `${this.baseUrl}/${filename}${this.options.cacheBusting ? '?t=' + Date.now() : ''}`;

            link.rel = 'stylesheet';
            link.href = href;

            let timeoutId: number;

            const cleanup = () => {
                clearTimeout(timeoutId);
                link.removeEventListener('load', onLoad);
                link.removeEventListener('error', onError);
            };

            const onLoad = () => {
                cleanup();
                console.log(`✅ Stylesheet loaded: ${filename}`);
                resolve();
            };

            const onError = () => {
                cleanup();
                document.head.removeChild(link);
                
                const error = new Error(`Failed to load stylesheet: ${filename}`);
                console.error('❌ Stylesheet load error:', error);
                reject(error);
            };

            // Set up timeout
            timeoutId = window.setTimeout(() => {
                cleanup();
                const error = new Error(`Stylesheet load timeout: ${filename}`);
                console.error('⏰ Stylesheet load timeout:', error);
                reject(error);
            }, this.options.timeout);

            link.addEventListener('load', onLoad);
            link.addEventListener('error', onError);

            document.head.appendChild(link);
        });
    }

    /**
     * Fetch with timeout support
     */
    private async fetchWithTimeout(url: string, timeout: number): Promise<Response> {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            const response = await fetch(url, { 
                signal: controller.signal,
                credentials: 'same-origin'
            });
            return response;
        } finally {
            clearTimeout(timeoutId);
        }
    }

    /**
     * Get loading status for a bundle
     */
    public getBundleStatus(entryKey: string): 'not-loaded' | 'loading' | 'loaded' {
        if (this.loadedBundles.has(entryKey)) {
            return 'loaded';
        }
        if (this.loadingPromises.has(entryKey)) {
            return 'loading';
        }
        return 'not-loaded';
    }

    /**
     * Preload bundles for performance
     */
    public async preloadBundles(entryKeys: string[]): Promise<void> {
        try {
            await Promise.all(
                entryKeys.map(key => this.loadBundle(key))
            );
            console.log(`✅ Preloaded ${entryKeys.length} bundles`);
        } catch (error) {
            console.error('❌ Bundle preloading failed:', error);
        }
    }

    /**
     * Clear cache and reset loader state
     */
    public reset(): void {
        this.manifest = null;
        this.loadedBundles.clear();
        this.loadingPromises.clear();
        console.log('🔄 DynamicBundleLoader reset');
    }

    /**
     * Get loader statistics
     */
    public getStats(): {
        loadedCount: number;
        loadingCount: number;
        manifestLoaded: boolean;
        totalEntries: number;
    } {
        return {
            loadedCount: this.loadedBundles.size,
            loadingCount: this.loadingPromises.size,
            manifestLoaded: this.manifest !== null,
            totalEntries: this.manifest ? Object.keys(this.manifest).length : 0
        };
    }
}

// Export singleton instance creator
export const createBundleLoader = (options?: LoaderOptions): DynamicBundleLoader => {
    return DynamicBundleLoader.getInstance(options);
};

// Export for global access
if (typeof window !== 'undefined') {
    (window as any).DynamicBundleLoader = DynamicBundleLoader;
}