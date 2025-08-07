/**
 * Bootstrap Singleton - TDZ-Safe Application Bootstrap
 * Manages application initialization with proper error handling
 * and TDZ-safe patterns.
 */

import { DynamicBundleLoader } from './DynamicBundleLoader';
import OptimizedResizeObserver from './OptimizedResizeObserver';

interface BootstrapConfig {
    debug?: boolean;
    timeout?: number;
    retries?: number;
    skipGPS?: boolean;
    enableErrorReporting?: boolean;
}

interface InitializationResult {
    success: boolean;
    initTime: number;
    errors: Error[];
    warnings: string[];
    features: string[];
}

type BootstrapPhase = 'idle' | 'initializing' | 'ready' | 'error' | 'recovery';

class BootstrapError extends Error {
    constructor(message: string, public phase: string, public details?: any) {
        super(message);
        this.name = 'BootstrapError';
    }
}

export class BootstrapSingleton {
    private static instance: BootstrapSingleton | null = null;
    private phase: BootstrapPhase = 'idle';
    private initPromise: Promise<InitializationResult> | null = null;
    private config: Required<BootstrapConfig>;
    private bundleLoader: DynamicBundleLoader | null = null;
    private resizeObserver: OptimizedResizeObserver | null = null;
    private errors: Error[] = [];
    private warnings: string[] = [];
    private features: string[] = [];
    private startTime: number = 0;

    private constructor(config: BootstrapConfig = {}) {
        this.config = {
            debug: false,
            timeout: 30000,
            retries: 3,
            skipGPS: false,
            enableErrorReporting: true,
            ...config
        };

        // TDZ-safe initialization
        this.setupErrorHandling();
        this.log('🚀 BootstrapSingleton created');
    }

    /**
     * Get singleton instance - TDZ safe
     */
    public static getInstance(config?: BootstrapConfig): BootstrapSingleton {
        if (!BootstrapSingleton.instance) {
            BootstrapSingleton.instance = new BootstrapSingleton(config);
        }
        return BootstrapSingleton.instance;
    }

    /**
     * Initialize application with comprehensive error handling
     */
    public async init(): Promise<InitializationResult> {
        // Prevent multiple initializations
        if (this.initPromise) {
            return this.initPromise;
        }

        this.initPromise = this.performInitialization();
        return this.initPromise;
    }

    /**
     * Perform actual initialization sequence
     */
    private async performInitialization(): Promise<InitializationResult> {
        this.startTime = performance.now();
        this.phase = 'initializing';
        
        try {
            this.log('🔧 Starting application initialization...');

            // Phase 1: Core Systems
            await this.initializeCoreServices();
            
            // Phase 2: Bundle Management
            await this.initializeBundleLoader();
            
            // Phase 3: DOM Observers
            await this.initializeResizeObserver();
            
            // Phase 4: Application Features
            await this.initializeFeatures();
            
            // Phase 5: GPS and Location (if not skipped)
            if (!this.config.skipGPS) {
                await this.initializeLocationServices();
            }

            this.phase = 'ready';
            const initTime = performance.now() - this.startTime;
            
            this.log(`✅ Bootstrap completed in ${initTime.toFixed(2)}ms`);
            
            return {
                success: true,
                initTime,
                errors: [...this.errors],
                warnings: [...this.warnings],
                features: [...this.features]
            };

        } catch (error) {
            this.phase = 'error';
            const bootstrapError = error instanceof BootstrapError ? error : 
                new BootstrapError('Bootstrap initialization failed', 'init', error);
            
            this.errors.push(bootstrapError);
            this.logError('❌ Bootstrap failed:', bootstrapError);
            
            // Attempt recovery
            const recovery = await this.attemptRecovery();
            
            return {
                success: recovery.success,
                initTime: performance.now() - this.startTime,
                errors: [...this.errors],
                warnings: [...this.warnings],
                features: [...this.features]
            };
        }
    }

    /**
     * Initialize core services
     */
    private async initializeCoreServices(): Promise<void> {
        try {
            this.log('🔧 Initializing core services...');
            
            // Error reporting setup
            if (this.config.enableErrorReporting) {
                this.setupErrorReporting();
                this.features.push('error-reporting');
            }
            
            // Performance monitoring
            this.setupPerformanceMonitoring();
            this.features.push('performance-monitoring');
            
            // Browser compatibility checks
            this.checkBrowserCompatibility();
            this.features.push('compatibility-check');
            
            this.log('✅ Core services initialized');
            
        } catch (error) {
            throw new BootstrapError('Core services initialization failed', 'core', error);
        }
    }

    /**
     * Initialize bundle loader
     */
    private async initializeBundleLoader(): Promise<void> {
        try {
            this.log('📦 Initializing bundle loader...');
            
            this.bundleLoader = DynamicBundleLoader.getInstance({
                timeout: this.config.timeout,
                cacheBusting: this.config.debug
            });
            
            // Load manifest
            await this.bundleLoader.loadManifest();
            this.features.push('bundle-loader');
            
            this.log('✅ Bundle loader initialized');
            
        } catch (error) {
            this.warnings.push('Bundle loader initialization failed - using fallback');
            this.log('⚠️ Bundle loader failed, continuing with static loading');
        }
    }

    /**
     * Initialize resize observer
     */
    private async initializeResizeObserver(): Promise<void> {
        try {
            this.log('👁️ Initializing resize observer...');
            
            this.resizeObserver = OptimizedResizeObserver.getInstance();
            this.features.push('resize-observer');
            
            this.log('✅ Resize observer initialized');
            
        } catch (error) {
            this.warnings.push('Resize observer initialization failed');
            this.log('⚠️ Resize observer failed, layout monitoring disabled');
        }
    }

    /**
     * Initialize application features
     */
    private async initializeFeatures(): Promise<void> {
        try {
            this.log('🎯 Initializing application features...');
            
            // Initialize React if available
            if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined') {
                this.features.push('react');
                this.log('  ✓ React available');
            }
            
            // Initialize authentication
            if (typeof window !== 'undefined' && (window as any).DokterKuAuth) {
                this.features.push('authentication');
                this.log('  ✓ Authentication system available');
            }
            
            // Initialize service worker
            if ('serviceWorker' in navigator) {
                this.initializeServiceWorker();
                this.features.push('service-worker');
            }
            
            this.log('✅ Application features initialized');
            
        } catch (error) {
            throw new BootstrapError('Feature initialization failed', 'features', error);
        }
    }

    /**
     * Initialize location services
     */
    private async initializeLocationServices(): Promise<void> {
        try {
            this.log('📍 Initializing location services...');
            
            if (!navigator.geolocation) {
                this.warnings.push('Geolocation not supported');
                return;
            }
            
            // Test geolocation availability
            await new Promise<void>((resolve, reject) => {
                const timeoutId = setTimeout(() => {
                    reject(new Error('Geolocation timeout'));
                }, 5000);
                
                navigator.geolocation.getCurrentPosition(
                    () => {
                        clearTimeout(timeoutId);
                        this.features.push('geolocation');
                        resolve();
                    },
                    (error) => {
                        clearTimeout(timeoutId);
                        this.warnings.push(`Geolocation error: ${error.message}`);
                        resolve(); // Don't fail bootstrap for GPS issues
                    },
                    {
                        timeout: 4000,
                        enableHighAccuracy: false,
                        maximumAge: 300000
                    }
                );
            });
            
            this.log('✅ Location services initialized');
            
        } catch (error) {
            this.warnings.push('Location services initialization failed');
            this.log('⚠️ Location services failed, GPS features disabled');
        }
    }

    /**
     * Initialize service worker
     */
    private async initializeServiceWorker(): Promise<void> {
        try {
            if ('serviceWorker' in navigator) {
                const registration = await navigator.serviceWorker.register('/sw.js');
                this.log('✅ Service worker registered');
            }
        } catch (error) {
            this.warnings.push('Service worker registration failed');
            this.log('⚠️ Service worker registration failed');
        }
    }

    /**
     * Setup error handling
     */
    private setupErrorHandling(): void {
        if (typeof window !== 'undefined') {
            window.addEventListener('error', this.handleGlobalError.bind(this));
            window.addEventListener('unhandledrejection', this.handleUnhandledRejection.bind(this));
        }
    }

    /**
     * Setup error reporting
     */
    private setupErrorReporting(): void {
        // Could integrate with error reporting service
        this.log('📊 Error reporting enabled');
    }

    /**
     * Setup performance monitoring
     */
    private setupPerformanceMonitoring(): void {
        if (typeof window !== 'undefined' && 'performance' in window) {
            // Monitor long tasks
            if ('PerformanceObserver' in window) {
                try {
                    const observer = new PerformanceObserver((list) => {
                        list.getEntries().forEach((entry) => {
                            if (entry.duration > 50) {
                                this.log(`⚠️ Long task detected: ${entry.duration}ms`);
                            }
                        });
                    });
                    observer.observe({ entryTypes: ['longtask'] });
                } catch (error) {
                    this.log('⚠️ Performance observer not supported');
                }
            }
        }
    }

    /**
     * Check browser compatibility
     */
    private checkBrowserCompatibility(): void {
        const required = {
            'ES6 Classes': () => typeof class {} === 'function',
            'Promises': () => typeof Promise !== 'undefined',
            'Fetch API': () => typeof fetch !== 'undefined',
            'LocalStorage': () => typeof localStorage !== 'undefined'
        };

        const missing: string[] = [];
        for (const [feature, check] of Object.entries(required)) {
            try {
                if (!check()) {
                    missing.push(feature);
                }
            } catch (error) {
                missing.push(feature);
            }
        }

        if (missing.length > 0) {
            this.warnings.push(`Missing browser features: ${missing.join(', ')}`);
        }
    }

    /**
     * Handle global errors
     */
    private handleGlobalError(event: ErrorEvent): void {
        this.logError('🚨 Global error:', event.error || event.message);
    }

    /**
     * Handle unhandled promise rejections
     */
    private handleUnhandledRejection(event: PromiseRejectionEvent): void {
        this.logError('🚨 Unhandled rejection:', event.reason);
    }

    /**
     * Attempt recovery from initialization failure
     */
    private async attemptRecovery(): Promise<{ success: boolean }> {
        this.phase = 'recovery';
        this.log('🔄 Attempting bootstrap recovery...');
        
        try {
            // Minimal initialization for basic functionality
            this.setupErrorHandling();
            this.features.push('minimal-recovery');
            
            this.phase = 'ready';
            this.log('✅ Recovery successful - minimal functionality available');
            return { success: true };
            
        } catch (error) {
            this.logError('❌ Recovery failed:', error);
            return { success: false };
        }
    }

    /**
     * Get current bootstrap status
     */
    public getStatus(): {
        phase: BootstrapPhase;
        initTime: number;
        errors: Error[];
        warnings: string[];
        features: string[];
        bundleStats?: any;
    } {
        return {
            phase: this.phase,
            initTime: this.startTime ? performance.now() - this.startTime : 0,
            errors: [...this.errors],
            warnings: [...this.warnings],
            features: [...this.features],
            bundleStats: this.bundleLoader?.getStats()
        };
    }

    /**
     * Load additional bundle
     */
    public async loadBundle(bundleKey: string): Promise<void> {
        if (!this.bundleLoader) {
            throw new BootstrapError('Bundle loader not available', 'bundle');
        }
        
        return this.bundleLoader.loadBundle(bundleKey);
    }

    /**
     * Check if bootstrap is ready
     */
    public isReady(): boolean {
        return this.phase === 'ready';
    }

    /**
     * Reset bootstrap state
     */
    public reset(): void {
        this.phase = 'idle';
        this.initPromise = null;
        this.errors = [];
        this.warnings = [];
        this.features = [];
        this.bundleLoader?.reset();
        this.log('🔄 Bootstrap reset');
    }

    /**
     * Debug logging
     */
    private log(message: string): void {
        if (this.config.debug) {
            console.log(`[Bootstrap] ${message}`);
        }
    }

    /**
     * Error logging
     */
    private logError(message: string, error?: any): void {
        console.error(`[Bootstrap] ${message}`, error);
    }
}

// Export factory function
export const createBootstrap = (config?: BootstrapConfig): BootstrapSingleton => {
    return BootstrapSingleton.getInstance(config);
};

// Global access
if (typeof window !== 'undefined') {
    (window as any).DokterKuBootstrap = BootstrapSingleton;
}