/**
 * Circuit Breaker and Rate Limiting Protection for API Calls
 * Prevents overwhelming the API with requests during high-traffic periods
 */

interface CircuitBreakerState {
  status: 'CLOSED' | 'OPEN' | 'HALF_OPEN';
  failureCount: number;
  lastFailureTime: number;
  nextAttemptTime: number;
  successCount: number;
}

interface RateLimiterConfig {
  maxRequestsPerMinute: number;
  circuitBreakerThreshold: number;
  circuitBreakerTimeout: number; // in milliseconds
  resetTimeWindow: number; // in milliseconds
}

export class ApiRateLimiter {
  private static instance: ApiRateLimiter;
  private circuitBreakerState: CircuitBreakerState;
  private requestTimes: number[];
  private config: RateLimiterConfig;

  private constructor(config: Partial<RateLimiterConfig> = {}) {
    this.config = {
      maxRequestsPerMinute: 30, // Conservative limit
      circuitBreakerThreshold: 5, // Open circuit after 5 consecutive failures
      circuitBreakerTimeout: 60000, // 1 minute timeout
      resetTimeWindow: 60000, // 1 minute window
      ...config
    };

    this.circuitBreakerState = {
      status: 'CLOSED',
      failureCount: 0,
      lastFailureTime: 0,
      nextAttemptTime: 0,
      successCount: 0
    };

    this.requestTimes = [];
  }

  public static getInstance(config?: Partial<RateLimiterConfig>): ApiRateLimiter {
    if (!ApiRateLimiter.instance) {
      ApiRateLimiter.instance = new ApiRateLimiter(config);
    }
    return ApiRateLimiter.instance;
  }

  /**
   * Check if a request can be made based on rate limiting and circuit breaker
   */
  public canMakeRequest(endpoint?: string): { allowed: boolean; reason?: string; retryAfter?: number } {
    const now = Date.now();

    // Check circuit breaker status
    if (this.circuitBreakerState.status === 'OPEN') {
      if (now < this.circuitBreakerState.nextAttemptTime) {
        return {
          allowed: false,
          reason: 'Circuit breaker is OPEN',
          retryAfter: this.circuitBreakerState.nextAttemptTime - now
        };
      } else {
        // Transition to HALF_OPEN
        this.circuitBreakerState.status = 'HALF_OPEN';
        this.circuitBreakerState.successCount = 0;
        console.log('🔄 Circuit breaker transitioning to HALF_OPEN');
      }
    }

    // Rate limiting check
    this.cleanOldRequests(now);
    
    if (this.requestTimes.length >= this.config.maxRequestsPerMinute) {
      const oldestRequest = this.requestTimes[0];
      const retryAfter = oldestRequest + this.config.resetTimeWindow - now;
      
      return {
        allowed: false,
        reason: 'Rate limit exceeded',
        retryAfter: Math.max(retryAfter, 1000) // Minimum 1 second wait
      };
    }

    // Record this request
    this.requestTimes.push(now);
    
    return { allowed: true };
  }

  /**
   * Record a successful API call
   */
  public recordSuccess(): void {
    if (this.circuitBreakerState.status === 'HALF_OPEN') {
      this.circuitBreakerState.successCount++;
      
      // If we have enough successful calls, close the circuit
      if (this.circuitBreakerState.successCount >= 3) {
        this.circuitBreakerState.status = 'CLOSED';
        this.circuitBreakerState.failureCount = 0;
        console.log('✅ Circuit breaker CLOSED - Service recovered');
      }
    } else if (this.circuitBreakerState.status === 'CLOSED') {
      // Reset failure count on success
      this.circuitBreakerState.failureCount = Math.max(0, this.circuitBreakerState.failureCount - 1);
    }
  }

  /**
   * Record a failed API call
   */
  public recordFailure(error?: any): void {
    const now = Date.now();
    
    // Only count certain types of failures for circuit breaker
    const isCircuitBreakerFailure = 
      error?.response?.status >= 500 || // Server errors
      error?.response?.status === 429 || // Rate limiting
      error?.code === 'NETWORK_ERROR' ||
      !error?.response; // Network failures

    if (isCircuitBreakerFailure) {
      this.circuitBreakerState.failureCount++;
      this.circuitBreakerState.lastFailureTime = now;

      if (this.circuitBreakerState.failureCount >= this.config.circuitBreakerThreshold) {
        this.circuitBreakerState.status = 'OPEN';
        this.circuitBreakerState.nextAttemptTime = now + this.config.circuitBreakerTimeout;
        
        console.warn(`🔴 Circuit breaker OPEN - Too many failures (${this.circuitBreakerState.failureCount})`);
        console.warn(`⏰ Next attempt allowed at: ${new Date(this.circuitBreakerState.nextAttemptTime).toLocaleTimeString()}`);
      }
    }
  }

  /**
   * Get current circuit breaker status
   */
  public getStatus(): {
    circuitBreaker: CircuitBreakerState;
    rateLimiter: {
      requestsInWindow: number;
      maxRequests: number;
      windowResetIn: number;
    };
  } {
    const now = Date.now();
    this.cleanOldRequests(now);
    
    const oldestRequest = this.requestTimes.length > 0 ? this.requestTimes[0] : now;
    const windowResetIn = Math.max(0, oldestRequest + this.config.resetTimeWindow - now);

    return {
      circuitBreaker: { ...this.circuitBreakerState },
      rateLimiter: {
        requestsInWindow: this.requestTimes.length,
        maxRequests: this.config.maxRequestsPerMinute,
        windowResetIn
      }
    };
  }

  /**
   * Create a protected API call function
   */
  public createProtectedApiCall<T>(
    apiCall: () => Promise<T>,
    endpoint: string = 'unknown'
  ): () => Promise<T> {
    return async (): Promise<T> => {
      const permission = this.canMakeRequest(endpoint);
      
      if (!permission.allowed) {
        const error = new Error(`API call blocked: ${permission.reason}`);
        (error as any).rateLimited = true;
        (error as any).retryAfter = permission.retryAfter;
        throw error;
      }

      try {
        const result = await apiCall();
        this.recordSuccess();
        return result;
      } catch (error) {
        this.recordFailure(error);
        throw error;
      }
    };
  }

  /**
   * Reset the circuit breaker (for manual recovery)
   */
  public reset(): void {
    this.circuitBreakerState = {
      status: 'CLOSED',
      failureCount: 0,
      lastFailureTime: 0,
      nextAttemptTime: 0,
      successCount: 0
    };
    this.requestTimes = [];
    console.log('🔄 ApiRateLimiter reset');
  }

  /**
   * Remove old request timestamps outside the time window
   */
  private cleanOldRequests(now: number): void {
    const cutoff = now - this.config.resetTimeWindow;
    this.requestTimes = this.requestTimes.filter(time => time > cutoff);
  }
}

// Export singleton instance
export const apiRateLimiter = ApiRateLimiter.getInstance({
  maxRequestsPerMinute: 25, // Conservative limit for dashboard
  circuitBreakerThreshold: 3, // Open after 3 failures
  circuitBreakerTimeout: 30000, // 30 second timeout
  resetTimeWindow: 60000 // 1 minute window
});

// Export utility function for easy use
export const withRateLimit = <T>(
  apiCall: () => Promise<T>,
  endpoint?: string
): (() => Promise<T>) => {
  return apiRateLimiter.createProtectedApiCall(apiCall, endpoint);
};

export default ApiRateLimiter;