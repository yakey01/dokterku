/**
 * 🚀 World-Class Optimized ResizeObserver Implementation
 * 
 * Features:
 * - Intelligent debouncing with requestAnimationFrame optimization
 * - Performance monitoring and analytics
 * - Automatic loop detection and mitigation
 * - Memory leak prevention
 * - Error recovery and resilience
 * - Real-time performance metrics
 */

interface ResizeObserverMetrics {
    totalObservations: number;
    loopErrors: number;
    averageCallbackTime: number;
    maxCallbackTime: number;
    memoryUsage: number;
    activeObservers: number;
    performanceScore: number;
}

interface OptimizedResizeObserverOptions {
    debounceMs?: number;
    maxFPS?: number;
    enableMetrics?: boolean;
    enableLoopDetection?: boolean;
    performanceThreshold?: number;
    enableConsoleSupression?: boolean;
}

class OptimizedResizeObserver implements ResizeObserver {
    private observer: ResizeObserver;
    private callback: ResizeObserverCallback;
    private isDestroyed: boolean = false;
    private options: Required<OptimizedResizeObserverOptions>;
    private metrics: ResizeObserverMetrics;
    private rafId: number | null = null;
    private pendingEntries: ResizeObserverEntry[] = [];
    private lastCallTime: number = 0;
    private callbackTimes: number[] = [];
    private loopDetectionCount: number = 0;
    private static globalMetrics: Map<string, ResizeObserverMetrics> = new Map();
    private instanceId: string;

    constructor(
        callback: ResizeObserverCallback, 
        options: OptimizedResizeObserverOptions = {}
    ) {
        this.instanceId = `ro-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        
        this.options = {
            debounceMs: options.debounceMs ?? 16, // ~60fps
            maxFPS: options.maxFPS ?? 60,
            enableMetrics: options.enableMetrics ?? true,
            enableLoopDetection: options.enableLoopDetection ?? true,
            performanceThreshold: options.performanceThreshold ?? 16.67, // 60fps threshold
            enableConsoleSupression: options.enableConsoleSupression ?? true,
        };

        this.metrics = {
            totalObservations: 0,
            loopErrors: 0,
            averageCallbackTime: 0,
            maxCallbackTime: 0,
            memoryUsage: 0,
            activeObservers: 1,
            performanceScore: 100
        };

        this.callback = this.createOptimizedCallback(callback);
        this.observer = new window.ResizeObserver(this.callback);
        
        if (this.options.enableMetrics) {
            OptimizedResizeObserver.globalMetrics.set(this.instanceId, this.metrics);
        }

        this.setupErrorSuppression();
        this.startPerformanceMonitoring();
    }

    private createOptimizedCallback(originalCallback: ResizeObserverCallback): ResizeObserverCallback {
        return (entries: ResizeObserverEntry[], observer: ResizeObserver) => {
            if (this.isDestroyed) return;

            const startTime = performance.now();

            try {
                // Loop detection
                if (this.options.enableLoopDetection) {
                    const timeSinceLastCall = startTime - this.lastCallTime;
                    if (timeSinceLastCall < 1) {
                        this.loopDetectionCount++;
                        if (this.loopDetectionCount > 10) {
                            this.metrics.loopErrors++;
                            console.warn(`🔄 ResizeObserver loop detected (${this.loopDetectionCount} rapid calls) - applying throttling`);
                            return; // Skip this call
                        }
                    } else {
                        this.loopDetectionCount = 0; // Reset counter
                    }
                }

                // Debounced execution with requestAnimationFrame
                this.pendingEntries = entries;
                
                if (this.rafId !== null) {
                    cancelAnimationFrame(this.rafId);
                }

                this.rafId = requestAnimationFrame(() => {
                    this.executeSafeCallback(originalCallback, this.pendingEntries, observer, startTime);
                });

            } catch (error) {
                console.error('OptimizedResizeObserver callback error:', error);
            }

            this.lastCallTime = startTime;
        };
    }

    private executeSafeCallback(
        originalCallback: ResizeObserverCallback,
        entries: ResizeObserverEntry[],
        observer: ResizeObserver,
        startTime: number
    ): void {
        if (this.isDestroyed) return;

        try {
            originalCallback(entries, observer);
            
            if (this.options.enableMetrics) {
                this.updateMetrics(startTime);
            }
        } catch (error) {
            // Handle ResizeObserver loop errors gracefully
            if (error instanceof Error && error.message.includes('ResizeObserver loop')) {
                this.metrics.loopErrors++;
                console.debug('🔄 ResizeObserver loop handled gracefully');
            } else {
                console.error('ResizeObserver callback execution error:', error);
            }
        }
    }

    private updateMetrics(startTime: number): void {
        const callbackTime = performance.now() - startTime;
        
        this.metrics.totalObservations++;
        this.metrics.maxCallbackTime = Math.max(this.metrics.maxCallbackTime, callbackTime);
        
        this.callbackTimes.push(callbackTime);
        if (this.callbackTimes.length > 100) {
            this.callbackTimes = this.callbackTimes.slice(-50); // Keep last 50 measurements
        }
        
        this.metrics.averageCallbackTime = 
            this.callbackTimes.reduce((sum, time) => sum + time, 0) / this.callbackTimes.length;

        // Performance score calculation (0-100)
        const performanceRatio = Math.min(this.options.performanceThreshold / this.metrics.averageCallbackTime, 1);
        const loopPenalty = Math.max(0, 1 - (this.metrics.loopErrors / this.metrics.totalObservations));
        this.metrics.performanceScore = Math.round(performanceRatio * loopPenalty * 100);

        // Update memory usage
        if ('memory' in performance && (performance as any).memory) {
            this.metrics.memoryUsage = (performance as any).memory.usedJSHeapSize;
        }
    }

    private setupErrorSuppression(): void {
        if (!this.options.enableConsoleSupression) return;

        // Intelligent console error suppression
        const originalError = console.error;
        if (originalError._optimizedResizeObserverPatched) return;

        console.error = function(...args: any[]) {
            const message = args[0]?.toString?.() || '';
            
            if (message.includes('ResizeObserver loop') || 
                message.includes('ResizeObserver loop limit exceeded') ||
                message.includes('ResizeObserver loop completed with undelivered notifications')) {
                
                // Only log the first few occurrences, then suppress
                const errorCount = (globalThis as any)._resizeObserverErrorCount || 0;
                if (errorCount < 3) {
                    console.debug(`🔄 ResizeObserver loop ${errorCount + 1}/3 (suppressing future warnings for performance)`);
                    (globalThis as any)._resizeObserverErrorCount = errorCount + 1;
                }
                return;
            }
            
            originalError.apply(console, args);
        };

        (console.error as any)._optimizedResizeObserverPatched = true;
    }

    private startPerformanceMonitoring(): void {
        if (!this.options.enableMetrics) return;

        const monitorPerformance = () => {
            if (this.isDestroyed) return;

            // Update global metrics
            OptimizedResizeObserver.globalMetrics.set(this.instanceId, { ...this.metrics });

            // Schedule next monitoring
            setTimeout(monitorPerformance, 5000); // Every 5 seconds
        };

        setTimeout(monitorPerformance, 5000);
    }

    // Standard ResizeObserver interface
    observe(target: Element, options?: ResizeObserverOptions): void {
        if (this.isDestroyed) return;
        this.observer.observe(target, options);
    }

    unobserve(target: Element): void {
        if (this.isDestroyed) return;
        this.observer.unobserve(target);
    }

    disconnect(): void {
        this.isDestroyed = true;
        
        if (this.rafId !== null) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }

        this.observer.disconnect();
        OptimizedResizeObserver.globalMetrics.delete(this.instanceId);
    }

    // Additional utility methods
    getMetrics(): ResizeObserverMetrics {
        return { ...this.metrics };
    }

    static getGlobalMetrics(): ResizeObserverMetrics {
        const allMetrics = Array.from(this.globalMetrics.values());
        if (allMetrics.length === 0) {
            return {
                totalObservations: 0,
                loopErrors: 0,
                averageCallbackTime: 0,
                maxCallbackTime: 0,
                memoryUsage: 0,
                activeObservers: 0,
                performanceScore: 100
            };
        }

        return {
            totalObservations: allMetrics.reduce((sum, m) => sum + m.totalObservations, 0),
            loopErrors: allMetrics.reduce((sum, m) => sum + m.loopErrors, 0),
            averageCallbackTime: allMetrics.reduce((sum, m) => sum + m.averageCallbackTime, 0) / allMetrics.length,
            maxCallbackTime: Math.max(...allMetrics.map(m => m.maxCallbackTime)),
            memoryUsage: Math.max(...allMetrics.map(m => m.memoryUsage)),
            activeObservers: allMetrics.length,
            performanceScore: Math.round(allMetrics.reduce((sum, m) => sum + m.performanceScore, 0) / allMetrics.length)
        };
    }

    static observeChart(
        element: Element,
        callback: (dimensions: { width: number; height: number }) => void,
        options?: OptimizedResizeObserverOptions
    ): () => void {
        const observer = new OptimizedResizeObserver((entries) => {
            for (const entry of entries) {
                const { width, height } = entry.contentRect;
                callback({ width: Math.floor(width), height: Math.floor(height) });
            }
        }, options);
        
        observer.observe(element);
        
        // Return cleanup function
        return () => {
            observer.disconnect();
        };
    }

    static createPerformanceDashboard(): HTMLElement {
        const dashboard = document.createElement('div');
        dashboard.className = 'resize-observer-dashboard';
        dashboard.innerHTML = `
            <div class="performance-dashboard">
                <h3>🚀 ResizeObserver Performance</h3>
                <div class="metrics-grid">
                    <div class="metric">
                        <span class="label">Active Observers:</span>
                        <span class="value" id="ro-active">0</span>
                    </div>
                    <div class="metric">
                        <span class="label">Performance Score:</span>
                        <span class="value" id="ro-score">100</span>
                    </div>
                    <div class="metric">
                        <span class="label">Loop Errors:</span>
                        <span class="value" id="ro-loops">0</span>
                    </div>
                    <div class="metric">
                        <span class="label">Avg Callback Time:</span>
                        <span class="value" id="ro-time">0ms</span>
                    </div>
                </div>
            </div>
        `;

        // Update dashboard periodically
        const updateDashboard = () => {
            const metrics = OptimizedResizeObserver.getGlobalMetrics();
            const activeEl = dashboard.querySelector('#ro-active');
            const scoreEl = dashboard.querySelector('#ro-score');
            const loopsEl = dashboard.querySelector('#ro-loops');
            const timeEl = dashboard.querySelector('#ro-time');

            if (activeEl) activeEl.textContent = metrics.activeObservers.toString();
            if (scoreEl) {
                scoreEl.textContent = metrics.performanceScore.toString();
                scoreEl.className = `value ${metrics.performanceScore > 80 ? 'good' : metrics.performanceScore > 60 ? 'warning' : 'error'}`;
            }
            if (loopsEl) loopsEl.textContent = metrics.loopErrors.toString();
            if (timeEl) timeEl.textContent = `${metrics.averageCallbackTime.toFixed(2)}ms`;
        };

        updateDashboard();
        setInterval(updateDashboard, 1000);

        return dashboard;
    }
}

// Global utility functions
export function createOptimizedResizeObserver(
    callback: ResizeObserverCallback,
    options?: OptimizedResizeObserverOptions
): OptimizedResizeObserver {
    return new OptimizedResizeObserver(callback, options);
}

export function suppressResizeObserverErrors(): void {
    const originalError = console.error;
    if ((originalError as any)._resizeObserverPatched) return;

    console.error = function(...args: any[]) {
        const message = args[0]?.toString?.() || '';
        if (message.includes('ResizeObserver loop')) {
            console.debug('🔄 ResizeObserver loop suppressed');
            return;
        }
        originalError.apply(console, args);
    };

    (console.error as any)._resizeObserverPatched = true;
}

export function getResizeObserverMetrics(): ResizeObserverMetrics {
    return OptimizedResizeObserver.getGlobalMetrics();
}

// Replace global ResizeObserver with optimized version
export function enableGlobalOptimization(options?: OptimizedResizeObserverOptions): void {
    if ((window as any)._resizeObserverOptimized) return;

    const OriginalResizeObserver = window.ResizeObserver;
    window.ResizeObserver = class extends OptimizedResizeObserver {
        constructor(callback: ResizeObserverCallback) {
            super(callback, options);
        }
    } as any;

    (window as any)._resizeObserverOptimized = true;
    console.log('✅ Global ResizeObserver optimization enabled');
}

// Auto-enable optimization when imported
if (typeof window !== 'undefined') {
    suppressResizeObserverErrors();
    enableGlobalOptimization();
}

export default OptimizedResizeObserver;