/**
 * Optimized Dashboard Service
 * High-performance service layer with request batching, connection pooling,
 * and intelligent caching to achieve <50ms API response times
 */

import { performanceMonitor } from '../utils/PerformanceMonitor';
import CacheManager from './CacheManager';
import RequestOptimizer from './RequestOptimizer';
import { 
  DashboardMetrics, 
  LeaderboardDoctor,
  AttendanceHistory 
} from '../components/dokter/types/dashboard';
import { 
  DashboardApiResponse,
  LeaderboardApiResponse,
  AttendanceApiResponse,
  API_ENDPOINTS 
} from '../components/dokter/types/api';

// Service configuration
const CONFIG = {
  BATCH_WINDOW: 10, // ms - batch requests within this window
  CONNECTION_POOL_SIZE: 6, // Browser limit for HTTP/1.1
  PREFETCH_DELAY: 100, // ms - delay before prefetching
  CACHE_STRATEGY: 'stale-while-revalidate' as const,
  REQUEST_TIMEOUT: 3000, // ms
  CRITICAL_DATA_PRIORITY: 10,
  SECONDARY_DATA_PRIORITY: 5,
};

// Request queue for batching
interface QueuedRequest {
  id: string;
  endpoint: string;
  priority: number;
  resolver: (data: any) => void;
  rejecter: (error: any) => void;
  timestamp: number;
}

class OptimizedDashboardService {
  private static instance: OptimizedDashboardService;
  private cache: CacheManager;
  private optimizer: RequestOptimizer;
  private requestQueue: Map<string, QueuedRequest[]> = new Map();
  private batchTimer: NodeJS.Timeout | null = null;
  private activeRequests: Map<string, Promise<any>> = new Map();
  private prefetchQueue: Set<string> = new Set();

  private constructor() {
    this.cache = CacheManager.getInstance();
    this.optimizer = RequestOptimizer.getInstance();
    this.initializePrefetching();
    this.setupNetworkOptimization();
  }

  static getInstance(): OptimizedDashboardService {
    if (!OptimizedDashboardService.instance) {
      OptimizedDashboardService.instance = new OptimizedDashboardService();
    }
    return OptimizedDashboardService.instance;
  }

  /**
   * Initialize prefetching based on user patterns
   */
  private initializePrefetching(): void {
    // Prefetch dashboard data on page visibility change
    if (typeof document !== 'undefined') {
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
          this.prefetchCriticalData();
        }
      });
    }

    // Predictive prefetch based on time patterns
    this.schedulePredictivePrefetch();
  }

  /**
   * Setup network optimization
   */
  private setupNetworkOptimization(): void {
    // DNS prefetch for API domain
    if (typeof document !== 'undefined') {
      const link = document.createElement('link');
      link.rel = 'dns-prefetch';
      link.href = window.location.origin;
      document.head.appendChild(link);

      // Preconnect for faster TLS handshake
      const preconnect = document.createElement('link');
      preconnect.rel = 'preconnect';
      preconnect.href = window.location.origin;
      document.head.appendChild(preconnect);
    }
  }

  /**
   * Get dashboard data with optimization
   */
  async getDashboardData(): Promise<DashboardApiResponse> {
    const cacheKey = 'dashboard:main';
    performanceMonitor.start('optimized-dashboard-fetch');

    // Try memory cache first (instant return)
    const cached = await this.cache.get(cacheKey);
    if (cached && this.cache.isValid(cacheKey)) {
      performanceMonitor.end('optimized-dashboard-fetch');
      console.log('🚀 Dashboard from memory cache - 0ms');
      return cached;
    }

    // Check if request is already in flight (request deduplication)
    const activeRequest = this.activeRequests.get(cacheKey);
    if (activeRequest) {
      console.log('♻️ Reusing in-flight request');
      return activeRequest;
    }

    // Create optimized request
    const request = this.createOptimizedRequest(
      API_ENDPOINTS.DASHBOARD,
      CONFIG.CRITICAL_DATA_PRIORITY
    );

    this.activeRequests.set(cacheKey, request);

    try {
      const data = await request;
      
      // Cache the response
      await this.cache.set(cacheKey, data, {
        ttl: 5 * 60 * 1000, // 5 minutes
        strategy: CONFIG.CACHE_STRATEGY,
      });

      // Prefetch related data
      this.prefetchRelatedData();

      performanceMonitor.end('optimized-dashboard-fetch');
      return data;
    } finally {
      this.activeRequests.delete(cacheKey);
    }
  }

  /**
   * Get all dashboard data in parallel with smart batching
   */
  async getAllDashboardData(): Promise<{
    dashboard: DashboardApiResponse;
    leaderboard: LeaderboardApiResponse;
    attendance: AttendanceApiResponse;
  }> {
    performanceMonitor.start('optimized-parallel-fetch');

    // Create batch request
    const batchRequest = this.optimizer.createBatchRequest([
      { 
        endpoint: API_ENDPOINTS.DASHBOARD, 
        priority: CONFIG.CRITICAL_DATA_PRIORITY,
        cacheKey: 'dashboard:main'
      },
      { 
        endpoint: API_ENDPOINTS.LEADERBOARD, 
        priority: CONFIG.SECONDARY_DATA_PRIORITY,
        cacheKey: 'dashboard:leaderboard'
      },
      { 
        endpoint: API_ENDPOINTS.ATTENDANCE_HISTORY, 
        priority: CONFIG.SECONDARY_DATA_PRIORITY,
        cacheKey: 'dashboard:attendance'
      }
    ]);

    try {
      const results = await batchRequest;
      performanceMonitor.end('optimized-parallel-fetch');
      
      // Cache all results
      await Promise.all([
        this.cache.set('dashboard:main', results[0], { ttl: 5 * 60 * 1000 }),
        this.cache.set('dashboard:leaderboard', results[1], { ttl: 10 * 60 * 1000 }),
        this.cache.set('dashboard:attendance', results[2], { ttl: 5 * 60 * 1000 }),
      ]);

      return {
        dashboard: results[0],
        leaderboard: results[1],
        attendance: results[2],
      };
    } catch (error) {
      performanceMonitor.end('optimized-parallel-fetch', 'error');
      throw error;
    }
  }

  /**
   * Create optimized request with all performance enhancements
   */
  private async createOptimizedRequest(
    endpoint: string, 
    priority: number = 5
  ): Promise<any> {
    // Check if we should batch this request
    if (this.shouldBatchRequest(endpoint)) {
      return this.addToBatch(endpoint, priority);
    }

    // Make immediate request with optimization
    return this.optimizer.makeOptimizedRequest(endpoint, {
      priority,
      timeout: CONFIG.REQUEST_TIMEOUT,
      compression: true,
      keepAlive: true,
    });
  }

  /**
   * Check if request should be batched
   */
  private shouldBatchRequest(endpoint: string): boolean {
    // Don't batch critical single requests
    if (endpoint === API_ENDPOINTS.DASHBOARD) {
      return false;
    }

    // Batch secondary data requests
    return true;
  }

  /**
   * Add request to batch queue
   */
  private addToBatch(endpoint: string, priority: number): Promise<any> {
    return new Promise((resolve, reject) => {
      const request: QueuedRequest = {
        id: `${endpoint}-${Date.now()}`,
        endpoint,
        priority,
        resolver: resolve,
        rejecter: reject,
        timestamp: Date.now(),
      };

      // Add to queue
      const queue = this.requestQueue.get(endpoint) || [];
      queue.push(request);
      this.requestQueue.set(endpoint, queue);

      // Start batch timer if not running
      if (!this.batchTimer) {
        this.batchTimer = setTimeout(() => {
          this.processBatchQueue();
        }, CONFIG.BATCH_WINDOW);
      }
    });
  }

  /**
   * Process batch queue
   */
  private async processBatchQueue(): Promise<void> {
    this.batchTimer = null;

    if (this.requestQueue.size === 0) return;

    // Group requests by endpoint
    const batches = Array.from(this.requestQueue.entries());
    this.requestQueue.clear();

    // Process each batch
    for (const [endpoint, requests] of batches) {
      try {
        const data = await this.optimizer.makeOptimizedRequest(endpoint);
        requests.forEach(req => req.resolver(data));
      } catch (error) {
        requests.forEach(req => req.rejecter(error));
      }
    }
  }

  /**
   * Prefetch critical data
   */
  private async prefetchCriticalData(): Promise<void> {
    // Only prefetch if cache is about to expire
    const cacheKey = 'dashboard:main';
    const remaining = this.cache.getRemainingTTL(cacheKey);
    
    if (remaining < 60000) { // Less than 1 minute remaining
      console.log('🔮 Prefetching dashboard data');
      await this.getDashboardData();
    }
  }

  /**
   * Prefetch related data after main request
   */
  private prefetchRelatedData(): void {
    setTimeout(() => {
      // Prefetch leaderboard and attendance in background
      if (!this.prefetchQueue.has('leaderboard')) {
        this.prefetchQueue.add('leaderboard');
        this.optimizer.makeOptimizedRequest(API_ENDPOINTS.LEADERBOARD, {
          priority: 1, // Low priority
          background: true,
        }).then(data => {
          this.cache.set('dashboard:leaderboard', data, { ttl: 10 * 60 * 1000 });
          this.prefetchQueue.delete('leaderboard');
        }).catch(() => {
          this.prefetchQueue.delete('leaderboard');
        });
      }
    }, CONFIG.PREFETCH_DELAY);
  }

  /**
   * Schedule predictive prefetch based on usage patterns
   */
  private schedulePredictivePrefetch(): void {
    // Prefetch at common usage times (e.g., start of work day)
    const now = new Date();
    const hour = now.getHours();
    
    // Prefetch before common access times
    if (hour === 7 || hour === 13) { // 8 AM and 2 PM (1 hour before)
      this.prefetchCriticalData();
    }

    // Schedule next check in 1 hour
    setTimeout(() => {
      this.schedulePredictivePrefetch();
    }, 60 * 60 * 1000);
  }

  /**
   * Warm up cache with initial data
   */
  async warmupCache(): Promise<void> {
    console.log('🔥 Warming up cache...');
    performanceMonitor.start('cache-warmup');

    try {
      // Fetch all data in parallel
      await this.getAllDashboardData();
      performanceMonitor.end('cache-warmup');
      console.log('✅ Cache warmed up successfully');
    } catch (error) {
      performanceMonitor.end('cache-warmup', 'error');
      console.error('Cache warmup failed:', error);
    }
  }

  /**
   * Clear all caches
   */
  async clearCache(): Promise<void> {
    await this.cache.clear();
    this.activeRequests.clear();
    this.prefetchQueue.clear();
  }

  /**
   * Get cache statistics
   */
  getCacheStats(): {
    hitRate: number;
    size: number;
    entries: number;
  } {
    return this.cache.getStats();
  }

  /**
   * Get network statistics
   */
  getNetworkStats(): {
    averageLatency: number;
    requestCount: number;
    cacheHitRate: number;
  } {
    return this.optimizer.getStats();
  }
}

// Export singleton instance
export default OptimizedDashboardService.getInstance();