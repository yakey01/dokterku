/**
 * Request Optimizer
 * Advanced network optimization with connection pooling, request coalescing,
 * and intelligent retry mechanisms
 */

import getUnifiedAuth from '../utils/UnifiedAuth';
import { performanceMonitor } from '../utils/PerformanceMonitor';

// Network configuration
const NETWORK_CONFIG = {
  MAX_CONCURRENT_REQUESTS: 6, // Browser limit for HTTP/1.1
  REQUEST_TIMEOUT: 3000, // 3 seconds
  RETRY_ATTEMPTS: 2,
  CIRCUIT_BREAKER_THRESHOLD: 5, // Failures before circuit opens
  CIRCUIT_BREAKER_TIMEOUT: 30000, // 30 seconds
  COMPRESSION_THRESHOLD: 1024, // Compress if > 1KB
};

// Request priority levels
export enum RequestPriority {
  CRITICAL = 10,
  HIGH = 7,
  NORMAL = 5,
  LOW = 3,
  BACKGROUND = 1,
}

// Request options
export interface OptimizedRequestOptions {
  priority?: number;
  timeout?: number;
  compression?: boolean;
  keepAlive?: boolean;
  background?: boolean;
  retries?: number;
  cache?: RequestCache;
}

// Batch request configuration
export interface BatchRequestConfig {
  endpoint: string;
  priority: number;
  cacheKey?: string;
  options?: OptimizedRequestOptions;
}

// Circuit breaker states
enum CircuitState {
  CLOSED = 'closed',
  OPEN = 'open',
  HALF_OPEN = 'half_open',
}

// Network quality detection
enum NetworkQuality {
  FAST = 'fast',
  MEDIUM = 'medium',
  SLOW = 'slow',
  OFFLINE = 'offline',
}

class RequestOptimizer {
  private static instance: RequestOptimizer;
  private requestQueue: Map<number, Set<BatchRequestConfig>> = new Map();
  private activeRequests: number = 0;
  private circuitBreaker: Map<string, {
    state: CircuitState;
    failures: number;
    lastFailure: number;
  }> = new Map();
  private networkQuality: NetworkQuality = NetworkQuality.FAST;
  private stats: {
    totalRequests: number;
    cacheHits: number;
    averageLatency: number;
    latencies: number[];
  } = {
    totalRequests: 0,
    cacheHits: 0,
    averageLatency: 0,
    latencies: [],
  };

  private constructor() {
    this.initializeNetworkMonitoring();
    this.setupConnectionPool();
  }

  static getInstance(): RequestOptimizer {
    if (!RequestOptimizer.instance) {
      RequestOptimizer.instance = new RequestOptimizer();
    }
    return RequestOptimizer.instance;
  }

  /**
   * Initialize network quality monitoring
   */
  private initializeNetworkMonitoring(): void {
    if (typeof navigator !== 'undefined' && 'connection' in navigator) {
      const connection = (navigator as any).connection;
      
      // Monitor network changes
      if (connection) {
        connection.addEventListener('change', () => {
          this.updateNetworkQuality();
        });
        this.updateNetworkQuality();
      }
    }

    // Monitor online/offline status
    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => {
        this.networkQuality = NetworkQuality.FAST;
        console.log('🌐 Network: Online');
      });

      window.addEventListener('offline', () => {
        this.networkQuality = NetworkQuality.OFFLINE;
        console.log('📵 Network: Offline');
      });
    }
  }

  /**
   * Update network quality based on connection info
   */
  private updateNetworkQuality(): void {
    if (typeof navigator === 'undefined' || !('connection' in navigator)) {
      return;
    }

    const connection = (navigator as any).connection;
    const effectiveType = connection?.effectiveType;
    const downlink = connection?.downlink; // Mbps

    if (effectiveType === '4g' || downlink > 10) {
      this.networkQuality = NetworkQuality.FAST;
    } else if (effectiveType === '3g' || downlink > 2) {
      this.networkQuality = NetworkQuality.MEDIUM;
    } else {
      this.networkQuality = NetworkQuality.SLOW;
    }

    console.log(`📶 Network quality: ${this.networkQuality} (${effectiveType}, ${downlink}Mbps)`);
  }

  /**
   * Setup connection pool for HTTP/1.1 optimization
   */
  private setupConnectionPool(): void {
    // Pre-warm connections by making OPTIONS requests
    if (typeof window !== 'undefined') {
      // Create persistent connection
      this.createPersistentConnection();
    }
  }

  /**
   * Create persistent connection for keep-alive
   */
  private createPersistentConnection(): void {
    // Send lightweight ping to establish connection
    fetch('/api/health', {
      method: 'HEAD',
      keepalive: true,
    }).catch(() => {
      // Ignore errors, this is just for connection warming
    });
  }

  /**
   * Make optimized request with all enhancements
   */
  async makeOptimizedRequest(
    endpoint: string,
    options: OptimizedRequestOptions = {}
  ): Promise<any> {
    const {
      priority = RequestPriority.NORMAL,
      timeout = NETWORK_CONFIG.REQUEST_TIMEOUT,
      compression = true,
      keepAlive = true,
      background = false,
      retries = NETWORK_CONFIG.RETRY_ATTEMPTS,
    } = options;

    // Check circuit breaker
    if (!this.isCircuitClosed(endpoint)) {
      throw new Error(`Circuit breaker open for ${endpoint}`);
    }

    // Wait if too many concurrent requests
    await this.waitForSlot(priority);

    const startTime = performance.now();
    this.activeRequests++;
    this.stats.totalRequests++;

    try {
      // Create abort controller for timeout
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);

      // Adjust timeout based on network quality
      const adjustedTimeout = this.getAdjustedTimeout(timeout);

      // Build optimized headers
      const headers = this.buildOptimizedHeaders(compression);

      // Make the request
      const response = await fetch(endpoint, {
        method: 'GET',
        headers,
        signal: controller.signal,
        keepalive: keepAlive,
        priority: background ? 'low' : 'high',
        credentials: 'same-origin',
      } as RequestInit);

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      // Parse response with compression support
      const data = await this.parseOptimizedResponse(response);

      // Record success
      this.recordSuccess(endpoint, performance.now() - startTime);

      return data;
    } catch (error) {
      // Record failure
      this.recordFailure(endpoint);

      // Retry logic
      if (retries > 0 && this.shouldRetry(error)) {
        console.log(`🔄 Retrying request to ${endpoint} (${retries} attempts left)`);
        await this.delay(this.getRetryDelay());
        return this.makeOptimizedRequest(endpoint, { ...options, retries: retries - 1 });
      }

      throw error;
    } finally {
      this.activeRequests--;
    }
  }

  /**
   * Create batch request for multiple endpoints
   */
  async createBatchRequest(configs: BatchRequestConfig[]): Promise<any[]> {
    performanceMonitor.start('batch-request');

    // Sort by priority
    configs.sort((a, b) => b.priority - a.priority);

    // Group by similar priority for true parallel execution
    const priorityGroups = new Map<number, BatchRequestConfig[]>();
    configs.forEach(config => {
      const priority = Math.floor(config.priority / 3) * 3; // Group in buckets of 3
      const group = priorityGroups.get(priority) || [];
      group.push(config);
      priorityGroups.set(priority, group);
    });

    const results: any[] = [];

    // Execute priority groups in order
    for (const [priority, group] of Array.from(priorityGroups.entries()).sort((a, b) => b[0] - a[0])) {
      const groupResults = await Promise.all(
        group.map(config => 
          this.makeOptimizedRequest(config.endpoint, {
            ...config.options,
            priority: config.priority,
          })
        )
      );
      results.push(...groupResults);
    }

    performanceMonitor.end('batch-request');
    return results;
  }

  /**
   * Build optimized headers
   */
  private buildOptimizedHeaders(compression: boolean): HeadersInit {
    const headers = getUnifiedAuth().getAuthHeaders();

    // Add compression support
    if (compression) {
      headers['Accept-Encoding'] = 'gzip, deflate, br';
    }

    // Add cache control for stale-while-revalidate
    headers['Cache-Control'] = 'max-age=0, stale-while-revalidate=60';

    // Add priority hints
    headers['Priority'] = 'u=1, i'; // Urgent, incremental

    return headers;
  }

  /**
   * Parse response with compression support
   */
  private async parseOptimizedResponse(response: Response): Promise<any> {
    const contentType = response.headers.get('content-type');
    const contentEncoding = response.headers.get('content-encoding');

    // Handle compressed responses
    if (contentEncoding && contentEncoding !== 'identity') {
      console.log(`📦 Response compressed with ${contentEncoding}`);
    }

    if (contentType?.includes('application/json')) {
      return response.json();
    }

    return response.text();
  }

  /**
   * Wait for available request slot
   */
  private async waitForSlot(priority: number): Promise<void> {
    while (this.activeRequests >= NETWORK_CONFIG.MAX_CONCURRENT_REQUESTS) {
      // Higher priority requests can preempt
      if (priority >= RequestPriority.CRITICAL) {
        break;
      }
      await this.delay(10);
    }
  }

  /**
   * Get adjusted timeout based on network quality
   */
  private getAdjustedTimeout(baseTimeout: number): number {
    switch (this.networkQuality) {
      case NetworkQuality.FAST:
        return baseTimeout;
      case NetworkQuality.MEDIUM:
        return baseTimeout * 1.5;
      case NetworkQuality.SLOW:
        return baseTimeout * 2;
      case NetworkQuality.OFFLINE:
        return 100; // Fail fast when offline
      default:
        return baseTimeout;
    }
  }

  /**
   * Get retry delay based on network quality
   */
  private getRetryDelay(): number {
    switch (this.networkQuality) {
      case NetworkQuality.FAST:
        return 100;
      case NetworkQuality.MEDIUM:
        return 500;
      case NetworkQuality.SLOW:
        return 1000;
      default:
        return 500;
    }
  }

  /**
   * Check if request should be retried
   */
  private shouldRetry(error: any): boolean {
    // Don't retry on client errors (4xx)
    if (error.message?.includes('HTTP 4')) {
      return false;
    }

    // Retry on network errors or server errors (5xx)
    return true;
  }

  /**
   * Circuit breaker: Check if circuit is closed
   */
  private isCircuitClosed(endpoint: string): boolean {
    const circuit = this.circuitBreaker.get(endpoint);
    if (!circuit) return true;

    if (circuit.state === CircuitState.OPEN) {
      // Check if timeout has passed
      if (Date.now() - circuit.lastFailure > NETWORK_CONFIG.CIRCUIT_BREAKER_TIMEOUT) {
        circuit.state = CircuitState.HALF_OPEN;
        circuit.failures = 0;
      } else {
        return false;
      }
    }

    return true;
  }

  /**
   * Record successful request
   */
  private recordSuccess(endpoint: string, latency: number): void {
    const circuit = this.circuitBreaker.get(endpoint);
    if (circuit) {
      circuit.failures = 0;
      circuit.state = CircuitState.CLOSED;
    }

    // Update stats
    this.stats.latencies.push(latency);
    if (this.stats.latencies.length > 100) {
      this.stats.latencies.shift();
    }
    this.stats.averageLatency = 
      this.stats.latencies.reduce((a, b) => a + b, 0) / this.stats.latencies.length;

    console.log(`✅ Request to ${endpoint} completed in ${latency.toFixed(2)}ms`);
  }

  /**
   * Record failed request
   */
  private recordFailure(endpoint: string): void {
    const circuit = this.circuitBreaker.get(endpoint) || {
      state: CircuitState.CLOSED,
      failures: 0,
      lastFailure: 0,
    };

    circuit.failures++;
    circuit.lastFailure = Date.now();

    if (circuit.failures >= NETWORK_CONFIG.CIRCUIT_BREAKER_THRESHOLD) {
      circuit.state = CircuitState.OPEN;
      console.error(`🔴 Circuit breaker opened for ${endpoint}`);
    }

    this.circuitBreaker.set(endpoint, circuit);
  }

  /**
   * Utility: Delay function
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Get optimizer statistics
   */
  getStats(): {
    averageLatency: number;
    requestCount: number;
    cacheHitRate: number;
  } {
    return {
      averageLatency: this.stats.averageLatency,
      requestCount: this.stats.totalRequests,
      cacheHitRate: this.stats.cacheHits / Math.max(this.stats.totalRequests, 1),
    };
  }

  /**
   * Reset statistics
   */
  resetStats(): void {
    this.stats = {
      totalRequests: 0,
      cacheHits: 0,
      averageLatency: 0,
      latencies: [],
    };
  }

  /**
   * Get network quality
   */
  getNetworkQuality(): NetworkQuality {
    return this.networkQuality;
  }
}

// Export singleton instance
export default RequestOptimizer;