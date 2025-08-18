/**
 * Auto-Fetch Manager
 * Intelligent auto-fetching with rate limiting, adaptive intervals,
 * and network-aware optimization
 */

import OptimizedDashboardService from './OptimizedDashboardService';
import RequestOptimizer from './RequestOptimizer';
import { performanceMonitor } from '../utils/PerformanceMonitor';

// Auto-fetch configuration
const AUTO_FETCH_CONFIG = {
  // Intervals (in ms)
  MIN_INTERVAL: 30000,        // 30 seconds minimum
  DEFAULT_INTERVAL: 60000,    // 1 minute default
  MAX_INTERVAL: 300000,       // 5 minutes maximum
  BACKGROUND_INTERVAL: 120000, // 2 minutes when tab is hidden
  
  // Rate limiting
  MAX_FETCHES_PER_MINUTE: 4,
  BURST_LIMIT: 2,
  RATE_LIMIT_WINDOW: 60000,   // 1 minute
  
  // Adaptive settings
  IDLE_THRESHOLD: 180000,     // 3 minutes of inactivity
  ACTIVE_THRESHOLD: 10000,    // 10 seconds of activity
  
  // Network awareness
  SLOW_NETWORK_MULTIPLIER: 2,
  OFFLINE_RETRY_DELAY: 5000,
};

// Fetch strategy types
export enum FetchStrategy {
  AGGRESSIVE = 'aggressive',   // For active users
  NORMAL = 'normal',          // Default strategy
  CONSERVATIVE = 'conservative', // For idle/background
  PAUSED = 'paused',          // Temporarily stopped
}

// Auto-fetch options
export interface AutoFetchOptions {
  enabled?: boolean;
  strategy?: FetchStrategy;
  interval?: number;
  onUpdate?: (data: any) => void;
  onError?: (error: Error) => void;
  endpoints?: string[];
}

class AutoFetchManager {
  private static instance: AutoFetchManager;
  private enabled: boolean = false;
  private strategy: FetchStrategy = FetchStrategy.NORMAL;
  private interval: number = AUTO_FETCH_CONFIG.DEFAULT_INTERVAL;
  private timer: NodeJS.Timeout | null = null;
  private fetchCount: number = 0;
  private fetchTimestamps: number[] = [];
  private lastFetchTime: number = 0;
  private lastUserActivity: number = Date.now();
  private isTabVisible: boolean = true;
  private networkQuality: 'fast' | 'slow' | 'offline' = 'fast';
  private callbacks: {
    onUpdate?: (data: any) => void;
    onError?: (error: Error) => void;
  } = {};
  private abortController: AbortController | null = null;

  private constructor() {
    this.initializeEventListeners();
    this.monitorNetworkQuality();
  }

  static getInstance(): AutoFetchManager {
    if (!AutoFetchManager.instance) {
      AutoFetchManager.instance = new AutoFetchManager();
    }
    return AutoFetchManager.instance;
  }

  /**
   * Initialize event listeners for activity and visibility
   */
  private initializeEventListeners(): void {
    if (typeof window === 'undefined') return;

    // Monitor tab visibility
    document.addEventListener('visibilitychange', () => {
      this.isTabVisible = !document.hidden;
      this.adjustStrategy();
      
      if (this.isTabVisible) {
        console.log('👁️ Tab visible - resuming auto-fetch');
        this.resume();
      } else {
        console.log('😴 Tab hidden - pausing auto-fetch');
        this.adjustInterval(AUTO_FETCH_CONFIG.BACKGROUND_INTERVAL);
      }
    });

    // Monitor user activity
    const activityEvents = ['mousedown', 'keydown', 'touchstart', 'scroll'];
    activityEvents.forEach(event => {
      window.addEventListener(event, () => this.recordUserActivity(), { passive: true });
    });

    // Monitor online/offline
    window.addEventListener('online', () => {
      this.networkQuality = 'fast';
      console.log('🌐 Back online - resuming auto-fetch');
      this.resume();
    });

    window.addEventListener('offline', () => {
      this.networkQuality = 'offline';
      console.log('📵 Offline - pausing auto-fetch');
      this.pause();
    });
  }

  /**
   * Monitor network quality
   */
  private monitorNetworkQuality(): void {
    const optimizer = RequestOptimizer.getInstance();
    
    setInterval(() => {
      const quality = optimizer.getNetworkQuality();
      this.networkQuality = quality === 'offline' ? 'offline' : 
                           quality === 'slow' ? 'slow' : 'fast';
      
      // Adjust strategy based on network
      if (this.networkQuality === 'offline') {
        this.pause();
      } else if (this.networkQuality === 'slow') {
        this.adjustInterval(this.interval * AUTO_FETCH_CONFIG.SLOW_NETWORK_MULTIPLIER);
      }
    }, 10000); // Check every 10 seconds
  }

  /**
   * Start auto-fetching
   */
  start(options: AutoFetchOptions = {}): void {
    this.enabled = options.enabled !== false;
    this.strategy = options.strategy || FetchStrategy.NORMAL;
    this.interval = options.interval || AUTO_FETCH_CONFIG.DEFAULT_INTERVAL;
    this.callbacks.onUpdate = options.onUpdate;
    this.callbacks.onError = options.onError;

    if (!this.enabled) {
      console.log('❌ Auto-fetch disabled');
      return;
    }

    console.log(`🚀 Auto-fetch started with ${this.strategy} strategy (${this.interval}ms interval)`);
    
    // Initial fetch
    this.fetch();
    
    // Start interval
    this.scheduleNext();
  }

  /**
   * Stop auto-fetching
   */
  stop(): void {
    this.enabled = false;
    
    if (this.timer) {
      clearTimeout(this.timer);
      this.timer = null;
    }
    
    if (this.abortController) {
      this.abortController.abort();
      this.abortController = null;
    }
    
    console.log('🛑 Auto-fetch stopped');
  }

  /**
   * Pause auto-fetching temporarily
   */
  pause(): void {
    if (this.timer) {
      clearTimeout(this.timer);
      this.timer = null;
    }
    
    this.strategy = FetchStrategy.PAUSED;
    console.log('⏸️ Auto-fetch paused');
  }

  /**
   * Resume auto-fetching
   */
  resume(): void {
    if (!this.enabled || this.strategy === FetchStrategy.PAUSED) {
      this.strategy = this.determineStrategy();
      this.scheduleNext();
      console.log('▶️ Auto-fetch resumed');
    }
  }

  /**
   * Perform fetch with rate limiting
   */
  private async fetch(): Promise<void> {
    // Check rate limit
    if (!this.checkRateLimit()) {
      console.log('⚠️ Rate limit exceeded, skipping fetch');
      return;
    }

    // Check if we should fetch based on strategy
    if (!this.shouldFetch()) {
      console.log('⏭️ Skipping fetch based on strategy');
      return;
    }

    performanceMonitor.start('auto-fetch');
    this.lastFetchTime = Date.now();
    this.recordFetch();

    try {
      // Create abort controller for cancellation
      this.abortController = new AbortController();
      
      // Fetch data
      const data = await OptimizedDashboardService.getAllDashboardData();
      
      // Notify callback
      if (this.callbacks.onUpdate) {
        this.callbacks.onUpdate(data);
      }
      
      performanceMonitor.end('auto-fetch');
      console.log('✅ Auto-fetch completed successfully');
      
      // Adjust strategy based on success
      this.adjustStrategyOnSuccess();
      
    } catch (error) {
      performanceMonitor.end('auto-fetch', 'error');
      
      if (error instanceof Error && error.name === 'AbortError') {
        console.log('🚫 Auto-fetch aborted');
      } else {
        console.error('❌ Auto-fetch error:', error);
        
        if (this.callbacks.onError && error instanceof Error) {
          this.callbacks.onError(error);
        }
        
        // Adjust strategy based on error
        this.adjustStrategyOnError();
      }
    } finally {
      this.abortController = null;
    }
  }

  /**
   * Schedule next fetch
   */
  private scheduleNext(): void {
    if (!this.enabled || this.strategy === FetchStrategy.PAUSED) return;
    
    if (this.timer) {
      clearTimeout(this.timer);
    }
    
    const nextInterval = this.calculateNextInterval();
    
    this.timer = setTimeout(() => {
      this.fetch();
      this.scheduleNext();
    }, nextInterval);
    
    console.log(`⏰ Next auto-fetch in ${(nextInterval / 1000).toFixed(0)}s`);
  }

  /**
   * Calculate next fetch interval based on strategy
   */
  private calculateNextInterval(): number {
    let interval = this.interval;
    
    // Adjust based on strategy
    switch (this.strategy) {
      case FetchStrategy.AGGRESSIVE:
        interval = Math.max(AUTO_FETCH_CONFIG.MIN_INTERVAL, interval * 0.5);
        break;
      case FetchStrategy.CONSERVATIVE:
        interval = Math.min(AUTO_FETCH_CONFIG.MAX_INTERVAL, interval * 2);
        break;
      case FetchStrategy.NORMAL:
      default:
        // Use default interval
        break;
    }
    
    // Adjust based on network quality
    if (this.networkQuality === 'slow') {
      interval *= AUTO_FETCH_CONFIG.SLOW_NETWORK_MULTIPLIER;
    }
    
    // Adjust based on tab visibility
    if (!this.isTabVisible) {
      interval = Math.max(interval, AUTO_FETCH_CONFIG.BACKGROUND_INTERVAL);
    }
    
    // Add jitter to prevent thundering herd
    const jitter = Math.random() * 5000; // ±5 seconds
    interval += jitter;
    
    return Math.max(AUTO_FETCH_CONFIG.MIN_INTERVAL, Math.min(AUTO_FETCH_CONFIG.MAX_INTERVAL, interval));
  }

  /**
   * Check if we should fetch based on strategy
   */
  private shouldFetch(): boolean {
    // Always fetch if cache is expired
    const cacheStats = OptimizedDashboardService.getCacheStats();
    if (cacheStats.entries === 0) {
      return true;
    }
    
    // Check based on user activity
    const timeSinceActivity = Date.now() - this.lastUserActivity;
    
    if (this.strategy === FetchStrategy.CONSERVATIVE && timeSinceActivity > AUTO_FETCH_CONFIG.IDLE_THRESHOLD) {
      return false; // User is idle
    }
    
    if (this.strategy === FetchStrategy.AGGRESSIVE && timeSinceActivity < AUTO_FETCH_CONFIG.ACTIVE_THRESHOLD) {
      return true; // User is active
    }
    
    return true;
  }

  /**
   * Check rate limit
   */
  private checkRateLimit(): boolean {
    const now = Date.now();
    
    // Remove old timestamps outside the window
    this.fetchTimestamps = this.fetchTimestamps.filter(
      timestamp => now - timestamp < AUTO_FETCH_CONFIG.RATE_LIMIT_WINDOW
    );
    
    // Check if we're within limits
    if (this.fetchTimestamps.length >= AUTO_FETCH_CONFIG.MAX_FETCHES_PER_MINUTE) {
      return false;
    }
    
    // Check burst limit (rapid successive fetches)
    const recentFetches = this.fetchTimestamps.filter(
      timestamp => now - timestamp < 10000 // Last 10 seconds
    );
    
    if (recentFetches.length >= AUTO_FETCH_CONFIG.BURST_LIMIT) {
      return false;
    }
    
    return true;
  }

  /**
   * Record fetch for rate limiting
   */
  private recordFetch(): void {
    this.fetchTimestamps.push(Date.now());
    this.fetchCount++;
  }

  /**
   * Record user activity
   */
  private recordUserActivity(): void {
    this.lastUserActivity = Date.now();
    
    // Switch to aggressive strategy if user is active
    if (this.strategy === FetchStrategy.CONSERVATIVE) {
      this.strategy = FetchStrategy.NORMAL;
    }
  }

  /**
   * Determine strategy based on current conditions
   */
  private determineStrategy(): FetchStrategy {
    const timeSinceActivity = Date.now() - this.lastUserActivity;
    
    if (this.networkQuality === 'offline') {
      return FetchStrategy.PAUSED;
    }
    
    if (!this.isTabVisible) {
      return FetchStrategy.CONSERVATIVE;
    }
    
    if (timeSinceActivity < AUTO_FETCH_CONFIG.ACTIVE_THRESHOLD) {
      return FetchStrategy.AGGRESSIVE;
    }
    
    if (timeSinceActivity > AUTO_FETCH_CONFIG.IDLE_THRESHOLD) {
      return FetchStrategy.CONSERVATIVE;
    }
    
    return FetchStrategy.NORMAL;
  }

  /**
   * Adjust strategy based on conditions
   */
  private adjustStrategy(): void {
    const newStrategy = this.determineStrategy();
    
    if (newStrategy !== this.strategy) {
      console.log(`🔄 Strategy changed: ${this.strategy} → ${newStrategy}`);
      this.strategy = newStrategy;
      this.scheduleNext();
    }
  }

  /**
   * Adjust strategy on successful fetch
   */
  private adjustStrategyOnSuccess(): void {
    // If we had consecutive successes, we can be less aggressive
    if (this.strategy === FetchStrategy.AGGRESSIVE) {
      const cacheHitRate = OptimizedDashboardService.getCacheStats().hitRate;
      
      if (cacheHitRate > 0.8) {
        // High cache hit rate, reduce fetching
        this.strategy = FetchStrategy.NORMAL;
      }
    }
  }

  /**
   * Adjust strategy on error
   */
  private adjustStrategyOnError(): void {
    // Back off on errors
    if (this.strategy === FetchStrategy.AGGRESSIVE) {
      this.strategy = FetchStrategy.NORMAL;
    } else if (this.strategy === FetchStrategy.NORMAL) {
      this.strategy = FetchStrategy.CONSERVATIVE;
    }
  }

  /**
   * Adjust interval
   */
  private adjustInterval(newInterval: number): void {
    this.interval = Math.max(
      AUTO_FETCH_CONFIG.MIN_INTERVAL,
      Math.min(AUTO_FETCH_CONFIG.MAX_INTERVAL, newInterval)
    );
    
    if (this.timer) {
      this.scheduleNext();
    }
  }

  /**
   * Get auto-fetch statistics
   */
  getStats(): {
    enabled: boolean;
    strategy: FetchStrategy;
    interval: number;
    fetchCount: number;
    lastFetchTime: number;
    networkQuality: string;
  } {
    return {
      enabled: this.enabled,
      strategy: this.strategy,
      interval: this.interval,
      fetchCount: this.fetchCount,
      lastFetchTime: this.lastFetchTime,
      networkQuality: this.networkQuality,
    };
  }

  /**
   * Reset statistics
   */
  resetStats(): void {
    this.fetchCount = 0;
    this.fetchTimestamps = [];
  }
}

// Export singleton instance
export default AutoFetchManager.getInstance();