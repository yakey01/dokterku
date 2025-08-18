/**
 * Centralized Dashboard Data Service
 * Handles all dashboard API calls with caching, retry optimization, and progressive loading
 */

import getUnifiedAuth from '../../utils/UnifiedAuth';

// Cache configuration
const CACHE_DURATION = {
  USER_DATA: 10 * 60 * 1000,      // 10 minutes for user data
  DASHBOARD: 5 * 60 * 1000,        // 5 minutes for dashboard data
  ATTENDANCE: 3 * 60 * 1000,       // 3 minutes for attendance
  JADWAL: 10 * 60 * 1000,          // 10 minutes for schedule (rarely changes)
  JASPEL: 5 * 60 * 1000,           // 5 minutes for jaspel
};

// Optimized retry configuration
const RETRY_CONFIG = {
  CRITICAL: { maxAttempts: 3, delays: [500, 1000, 2000] },  // Critical data (user, main dashboard)
  NORMAL: { maxAttempts: 2, delays: [500, 1000] },          // Normal priority
  LOW: { maxAttempts: 1, delays: [500] },                   // Low priority (can use cache/fallback)
};

interface CacheEntry<T> {
  data: T;
  timestamp: number;
  expiresIn: number;
}

class DashboardDataService {
  private cache = new Map<string, CacheEntry<any>>();
  private pendingRequests = new Map<string, Promise<any>>();
  private abortControllers = new Map<string, AbortController>();

  /**
   * Get data from cache if valid
   */
  private getFromCache<T>(key: string): T | null {
    const entry = this.cache.get(key);
    if (!entry) return null;
    
    const now = Date.now();
    if (now - entry.timestamp > entry.expiresIn) {
      this.cache.delete(key);
      return null;
    }
    
    console.log(`✅ Cache hit for ${key}`);
    return entry.data as T;
  }

  /**
   * Save data to cache
   */
  private saveToCache<T>(key: string, data: T, duration: number): void {
    this.cache.set(key, {
      data,
      timestamp: Date.now(),
      expiresIn: duration,
    });
    
    // Also save to localStorage for cross-session caching
    try {
      localStorage.setItem(`dashboard_cache_${key}`, JSON.stringify({
        data,
        timestamp: Date.now(),
        expiresIn: duration,
      }));
    } catch (e) {
      console.warn('Failed to save to localStorage:', e);
    }
  }

  /**
   * Load cache from localStorage
   */
  private loadFromLocalStorage<T>(key: string): T | null {
    try {
      const stored = localStorage.getItem(`dashboard_cache_${key}`);
      if (!stored) return null;
      
      const entry = JSON.parse(stored) as CacheEntry<T>;
      const now = Date.now();
      
      if (now - entry.timestamp > entry.expiresIn) {
        localStorage.removeItem(`dashboard_cache_${key}`);
        return null;
      }
      
      console.log(`✅ LocalStorage cache hit for ${key}`);
      return entry.data;
    } catch (e) {
      return null;
    }
  }

  /**
   * Deduplicated request handler
   */
  private async makeRequest<T>(
    key: string,
    requestFn: () => Promise<T>,
    cacheKey: string,
    cacheDuration: number,
    retryConfig = RETRY_CONFIG.NORMAL
  ): Promise<T> {
    // Check cache first
    const cached = this.getFromCache<T>(cacheKey) || this.loadFromLocalStorage<T>(cacheKey);
    if (cached) return cached;

    // Check if request is already pending
    if (this.pendingRequests.has(key)) {
      console.log(`⏳ Request already pending for ${key}, waiting...`);
      return this.pendingRequests.get(key);
    }

    // Create abort controller for this request
    const abortController = new AbortController();
    this.abortControllers.set(key, abortController);

    // Execute request with retry logic
    const requestPromise = this.executeWithRetry(
      requestFn,
      retryConfig,
      abortController.signal
    ).then(data => {
      // Save to cache on success
      this.saveToCache(cacheKey, data, cacheDuration);
      this.pendingRequests.delete(key);
      this.abortControllers.delete(key);
      return data;
    }).catch(error => {
      this.pendingRequests.delete(key);
      this.abortControllers.delete(key);
      throw error;
    });

    this.pendingRequests.set(key, requestPromise);
    return requestPromise;
  }

  /**
   * Execute request with optimized retry logic
   */
  private async executeWithRetry<T>(
    requestFn: () => Promise<T>,
    retryConfig: typeof RETRY_CONFIG.NORMAL,
    signal?: AbortSignal
  ): Promise<T> {
    let lastError: Error | null = null;

    for (let attempt = 0; attempt < retryConfig.maxAttempts; attempt++) {
      if (signal?.aborted) {
        throw new Error('Request aborted');
      }

      try {
        console.log(`🔄 Attempt ${attempt + 1}/${retryConfig.maxAttempts}`);
        return await requestFn();
      } catch (error) {
        lastError = error as Error;
        console.warn(`Attempt ${attempt + 1} failed:`, error);

        // Don't retry on certain errors
        if (error instanceof Error) {
          if (
            error.message.includes('401') ||
            error.message.includes('403') ||
            error.message.includes('404')
          ) {
            throw error; // Don't retry auth or not found errors
          }
        }

        // Wait before retry if not last attempt
        if (attempt < retryConfig.maxAttempts - 1) {
          await new Promise(resolve => setTimeout(resolve, retryConfig.delays[attempt]));
        }
      }
    }

    throw lastError || new Error('Request failed after retries');
  }

  /**
   * Fetch all dashboard data in parallel with progressive loading
   */
  async fetchAllDashboardData(options?: {
    forceRefresh?: boolean;
    priority?: 'critical' | 'normal' | 'low';
  }) {
    const forceRefresh = options?.forceRefresh || false;
    
    if (forceRefresh) {
      this.clearCache();
    }

    console.log('🚀 Starting optimized dashboard data fetch...');

    // Phase 1: Critical data (user and main dashboard) - PARALLEL
    const [userData, dashboardData] = await Promise.all([
      this.fetchUserData(),
      this.fetchDashboardData(),
    ]);

    // Return critical data immediately for progressive rendering
    const criticalData = {
      user: userData,
      dashboard: dashboardData,
      // Provide empty/loading states for non-critical data
      attendance: null,
      jadwalJaga: null,
      jaspel: null,
    };

    // Phase 2: Non-critical data - PARALLEL in background
    // Don't await, let them complete in background
    Promise.all([
      this.fetchAttendanceData().then(data => criticalData.attendance = data).catch(console.warn),
      this.fetchJadwalJagaData().then(data => criticalData.jadwalJaga = data).catch(console.warn),
      this.fetchJaspelData().then(data => criticalData.jaspel = data).catch(console.warn),
    ]).then(() => {
      console.log('✅ All background data loaded');
    });

    return criticalData;
  }

  /**
   * Fetch user data
   */
  async fetchUserData() {
    return this.makeRequest(
      'user_data',
      async () => {
        const response = await getUnifiedAuth().makeJsonRequest<{
          success: boolean;
          data: any;
        }>('/api/v2/dashboards/dokter');
        
        if (!response.success) {
          throw new Error('Failed to fetch user data');
        }
        
        return response.data.user;
      },
      'user',
      CACHE_DURATION.USER_DATA,
      RETRY_CONFIG.CRITICAL
    );
  }

  /**
   * Fetch main dashboard data
   */
  async fetchDashboardData() {
    return this.makeRequest(
      'dashboard_data',
      async () => {
        const response = await getUnifiedAuth().makeJsonRequest<{
          success: boolean;
          data: any;
        }>('/api/v2/dashboards/dokter');
        
        if (!response.success) {
          throw new Error('Failed to fetch dashboard data');
        }
        
        return response.data;
      },
      'dashboard',
      CACHE_DURATION.DASHBOARD,
      RETRY_CONFIG.CRITICAL
    );
  }

  /**
   * Fetch attendance data
   */
  async fetchAttendanceData() {
    return this.makeRequest(
      'attendance_data',
      async () => {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(endDate.getDate() - 7);
        
        const response = await fetch(
          `/api/v2/dashboards/dokter/presensi?start=${startDate.toISOString().split('T')[0]}&end=${endDate.toISOString().split('T')[0]}`,
          {
            method: 'GET',
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
          }
        );

        if (!response.ok) {
          throw new Error(`Failed to fetch attendance: ${response.status}`);
        }

        return response.json();
      },
      'attendance',
      CACHE_DURATION.ATTENDANCE,
      RETRY_CONFIG.NORMAL
    );
  }

  /**
   * Fetch jadwal jaga data
   */
  async fetchJadwalJagaData() {
    return this.makeRequest(
      'jadwal_jaga_data',
      async () => {
        const response = await fetch('/api/v2/dashboards/dokter/jadwal-jaga?include_attendance=true', {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });

        if (!response.ok) {
          throw new Error(`Failed to fetch jadwal jaga: ${response.status}`);
        }

        return response.json();
      },
      'jadwal_jaga',
      CACHE_DURATION.JADWAL,
      RETRY_CONFIG.LOW
    );
  }

  /**
   * Fetch jaspel data
   */
  async fetchJaspelData() {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1;
    const currentYear = currentDate.getFullYear();

    return this.makeRequest(
      'jaspel_data',
      async () => {
        const response = await fetch(
          `/api/v2/jaspel/validated/gaming-data?month=${currentMonth}&year=${currentYear}`,
          {
            method: 'GET',
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
          }
        );

        if (!response.ok) {
          throw new Error(`Failed to fetch jaspel: ${response.status}`);
        }

        return response.json();
      },
      'jaspel',
      CACHE_DURATION.JASPEL,
      RETRY_CONFIG.LOW
    );
  }

  /**
   * Clear all cache
   */
  clearCache() {
    this.cache.clear();
    
    // Clear localStorage cache
    const keys = Object.keys(localStorage);
    keys.forEach(key => {
      if (key.startsWith('dashboard_cache_')) {
        localStorage.removeItem(key);
      }
    });
    
    console.log('🗑️ Cache cleared');
  }

  /**
   * Cancel all pending requests
   */
  cancelAllRequests() {
    this.abortControllers.forEach((controller, key) => {
      controller.abort();
      console.log(`❌ Aborted request: ${key}`);
    });
    this.abortControllers.clear();
    this.pendingRequests.clear();
  }

  /**
   * Prefetch data for better performance
   */
  async prefetchData() {
    console.log('📥 Prefetching dashboard data...');
    
    // Prefetch in background without blocking
    this.fetchAllDashboardData({ priority: 'low' }).catch(console.warn);
  }
}

// Export singleton instance
export default new DashboardDataService();