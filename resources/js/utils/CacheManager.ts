/**
 * Advanced Caching System with localStorage Hybrid
 * Provides multi-level caching: in-memory, localStorage, and intelligent cache invalidation
 */

import React from 'react';

interface CacheEntry<T = any> {
  data: T;
  timestamp: number;
  version: string;
  expiresAt: number;
  metadata?: {
    dataSize: number;
    accessCount: number;
    lastAccessed: number;
    source: 'api' | 'websocket' | 'fallback';
  };
}

interface CacheConfig {
  ttl: number; // Time to live in milliseconds
  maxEntries?: number;
  enableLocalStorage?: boolean;
  persistOffline?: boolean;
  compressionThreshold?: number;
}

interface CacheStats {
  memoryHits: number;
  localStorageHits: number;
  apiCalls: number;
  totalRequests: number;
  hitRate: number;
  averageResponseTime: number;
  storageUsage: {
    memory: number;
    localStorage: number;
    total: number;
  };
}

class CacheManager {
  private memoryCache = new Map<string, CacheEntry>();
  private pendingRequests = new Map<string, Promise<any>>();
  private stats: CacheStats = {
    memoryHits: 0,
    localStorageHits: 0,
    apiCalls: 0,
    totalRequests: 0,
    hitRate: 0,
    averageResponseTime: 0,
    storageUsage: { memory: 0, localStorage: 0, total: 0 }
  };
  
  private readonly DEFAULT_CONFIG: CacheConfig = {
    ttl: 10 * 60 * 1000, // 10 minutes
    maxEntries: 100,
    enableLocalStorage: true,
    persistOffline: true,
    compressionThreshold: 50 * 1024 // 50KB
  };

  private readonly CACHE_VERSION = '1.0.0';
  private readonly STORAGE_PREFIX = 'dokterku_cache_';

  constructor() {
    this.initializeCache();
    this.setupCleanupInterval();
    this.setupStorageEventListener();
  }

  /**
   * Initialize cache and restore from localStorage if available
   */
  private initializeCache(): void {
    try {
      // Load cache entries from localStorage
      const storedKeys = this.getStoredKeys();
      
      console.log('🗄️ CacheManager: Initializing with', storedKeys.length, 'stored entries');
      
      // Restore valid cache entries to memory
      let restoredCount = 0;
      for (const key of storedKeys) {
        const entry = this.getFromLocalStorage(key);
        if (entry && this.isValidEntry(entry)) {
          this.memoryCache.set(key, entry);
          restoredCount++;
        } else if (entry) {
          // Remove invalid/expired entries
          this.removeFromLocalStorage(key);
        }
      }
      
      console.log('✅ CacheManager: Restored', restoredCount, 'valid cache entries');
      this.updateStats();
      
    } catch (error) {
      console.warn('⚠️ CacheManager: Failed to initialize from localStorage:', error);
      this.clearLocalStorage();
    }
  }

  /**
   * Get data from cache with fallback strategy
   */
  async get<T>(
    key: string, 
    fetcher?: () => Promise<T>, 
    config: Partial<CacheConfig> = {}
  ): Promise<T | null> {
    const startTime = performance.now();
    this.stats.totalRequests++;
    
    try {
      const finalConfig = { ...this.DEFAULT_CONFIG, ...config };
      
      // Check memory cache first
      const memoryEntry = this.memoryCache.get(key);
      if (memoryEntry && this.isValidEntry(memoryEntry)) {
        this.stats.memoryHits++;
        this.updateEntryAccess(key, memoryEntry);
        this.updateResponseTime(startTime);
        console.log('💾 Cache HIT (memory):', key);
        return memoryEntry.data as T;
      }
      
      // Check localStorage cache
      if (finalConfig.enableLocalStorage) {
        const storageEntry = this.getFromLocalStorage(key);
        if (storageEntry && this.isValidEntry(storageEntry)) {
          this.stats.localStorageHits++;
          // Restore to memory cache
          this.memoryCache.set(key, storageEntry);
          this.updateEntryAccess(key, storageEntry);
          this.updateResponseTime(startTime);
          console.log('💽 Cache HIT (localStorage):', key);
          return storageEntry.data as T;
        }
      }
      
      // Cache miss - fetch new data if fetcher provided
      if (fetcher) {
        // Check for pending request to prevent duplicate API calls
        const pendingRequest = this.pendingRequests.get(key);
        if (pendingRequest) {
          console.log('⏳ Cache: Using pending request for', key);
          return await pendingRequest;
        }
        
        console.log('🌐 Cache MISS: Fetching', key);
        this.stats.apiCalls++;
        
        // Create and store pending request
        const fetchPromise = this.fetchAndCache(key, fetcher, finalConfig);
        this.pendingRequests.set(key, fetchPromise);
        
        try {
          const result = await fetchPromise;
          this.updateResponseTime(startTime);
          return result;
        } finally {
          this.pendingRequests.delete(key);
        }
      }
      
      this.updateResponseTime(startTime);
      return null;
      
    } catch (error) {
      console.error('❌ CacheManager: Error getting data for', key, ':', error);
      this.updateResponseTime(startTime);
      return null;
    }
  }

  /**
   * Fetch data and cache it
   */
  private async fetchAndCache<T>(
    key: string, 
    fetcher: () => Promise<T>, 
    config: CacheConfig
  ): Promise<T> {
    try {
      const data = await fetcher();
      await this.set(key, data, config);
      return data;
    } catch (error) {
      console.error('❌ CacheManager: Failed to fetch and cache', key, ':', error);
      throw error;
    }
  }

  /**
   * Set data in cache
   */
  async set<T>(
    key: string, 
    data: T, 
    config: Partial<CacheConfig> = {},
    source: 'api' | 'websocket' | 'fallback' = 'api'
  ): Promise<void> {
    try {
      const finalConfig = { ...this.DEFAULT_CONFIG, ...config };
      const now = Date.now();
      
      const entry: CacheEntry<T> = {
        data,
        timestamp: now,
        version: this.CACHE_VERSION,
        expiresAt: now + finalConfig.ttl,
        metadata: {
          dataSize: this.calculateDataSize(data),
          accessCount: 1,
          lastAccessed: now,
          source
        }
      };
      
      // Store in memory cache
      this.memoryCache.set(key, entry);
      
      // Store in localStorage if enabled and data size is manageable
      if (finalConfig.enableLocalStorage) {
        const shouldCompress = entry.metadata!.dataSize > (finalConfig.compressionThreshold || 0);
        await this.setInLocalStorage(key, entry, shouldCompress);
      }
      
      // Cleanup if memory cache is too large
      if (finalConfig.maxEntries && this.memoryCache.size > finalConfig.maxEntries) {
        await this.cleanupMemoryCache(finalConfig.maxEntries);
      }
      
      this.updateStats();
      console.log('💾 Cache SET:', key, `(${entry.metadata!.dataSize} bytes)`);
      
    } catch (error) {
      console.error('❌ CacheManager: Failed to set cache for', key, ':', error);
    }
  }

  /**
   * Update existing cache entry with new data (for WebSocket updates)
   */
  async update<T>(key: string, data: T, source: 'websocket' | 'api' = 'websocket'): Promise<void> {
    const existingEntry = this.memoryCache.get(key);
    if (existingEntry) {
      // Preserve existing TTL and configuration
      const updatedEntry: CacheEntry<T> = {
        ...existingEntry,
        data,
        timestamp: Date.now(),
        metadata: {
          ...existingEntry.metadata!,
          dataSize: this.calculateDataSize(data),
          lastAccessed: Date.now(),
          source
        }
      };
      
      this.memoryCache.set(key, updatedEntry);
      
      // Update localStorage as well
      await this.setInLocalStorage(key, updatedEntry);
      
      console.log('🔄 Cache UPDATE:', key, `(via ${source})`);
    } else {
      // Create new entry with default config
      await this.set(key, data, {}, source);
    }
  }

  /**
   * Invalidate cache entry
   */
  invalidate(key: string): void {
    this.memoryCache.delete(key);
    this.removeFromLocalStorage(key);
    console.log('🗑️ Cache INVALIDATE:', key);
  }

  /**
   * Clear all cache
   */
  clear(): void {
    this.memoryCache.clear();
    this.clearLocalStorage();
    this.pendingRequests.clear();
    this.resetStats();
    console.log('🧹 Cache: Cleared all data');
  }

  /**
   * Get cache statistics
   */
  getStats(): CacheStats {
    this.updateStats();
    return { ...this.stats };
  }

  /**
   * Check if cache entry is valid
   */
  private isValidEntry(entry: CacheEntry): boolean {
    const now = Date.now();
    return entry.expiresAt > now && entry.version === this.CACHE_VERSION;
  }

  /**
   * Calculate data size for storage estimation
   */
  private calculateDataSize(data: any): number {
    try {
      return new Blob([JSON.stringify(data)]).size;
    } catch {
      return JSON.stringify(data).length * 2; // Rough estimate
    }
  }

  /**
   * Update entry access metadata
   */
  private updateEntryAccess(key: string, entry: CacheEntry): void {
    if (entry.metadata) {
      entry.metadata.accessCount++;
      entry.metadata.lastAccessed = Date.now();
    }
  }

  /**
   * Update response time statistics
   */
  private updateResponseTime(startTime: number): void {
    const responseTime = performance.now() - startTime;
    this.stats.averageResponseTime = 
      (this.stats.averageResponseTime * (this.stats.totalRequests - 1) + responseTime) / 
      this.stats.totalRequests;
  }

  /**
   * Update cache statistics
   */
  private updateStats(): void {
    this.stats.hitRate = this.stats.totalRequests > 0 
      ? ((this.stats.memoryHits + this.stats.localStorageHits) / this.stats.totalRequests) * 100
      : 0;
      
    // Calculate storage usage
    let memorySize = 0;
    for (const entry of this.memoryCache.values()) {
      memorySize += entry.metadata?.dataSize || 0;
    }
    
    this.stats.storageUsage.memory = memorySize;
    this.stats.storageUsage.localStorage = this.getLocalStorageSize();
    this.stats.storageUsage.total = this.stats.storageUsage.memory + this.stats.storageUsage.localStorage;
  }

  /**
   * LocalStorage operations
   */
  private getStoredKeys(): string[] {
    const keys: string[] = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key?.startsWith(this.STORAGE_PREFIX)) {
        keys.push(key.substring(this.STORAGE_PREFIX.length));
      }
    }
    return keys;
  }

  private getFromLocalStorage(key: string): CacheEntry | null {
    try {
      const stored = localStorage.getItem(this.STORAGE_PREFIX + key);
      return stored ? JSON.parse(stored) : null;
    } catch {
      return null;
    }
  }

  private async setInLocalStorage(key: string, entry: CacheEntry, compress = false): Promise<void> {
    try {
      // TODO: Add compression logic here if needed
      const serialized = JSON.stringify(entry);
      localStorage.setItem(this.STORAGE_PREFIX + key, serialized);
    } catch (error) {
      console.warn('⚠️ CacheManager: Failed to store in localStorage:', error);
      // Handle storage quota exceeded
      await this.cleanupLocalStorage();
    }
  }

  private removeFromLocalStorage(key: string): void {
    localStorage.removeItem(this.STORAGE_PREFIX + key);
  }

  private clearLocalStorage(): void {
    const keys = this.getStoredKeys();
    keys.forEach(key => this.removeFromLocalStorage(key));
  }

  private getLocalStorageSize(): number {
    let size = 0;
    const keys = this.getStoredKeys();
    keys.forEach(key => {
      const item = localStorage.getItem(this.STORAGE_PREFIX + key);
      if (item) size += item.length * 2; // Rough size calculation
    });
    return size;
  }

  /**
   * Cleanup operations
   */
  private async cleanupMemoryCache(maxEntries: number): Promise<void> {
    if (this.memoryCache.size <= maxEntries) return;
    
    // Sort by last accessed time and remove oldest entries
    const entries = Array.from(this.memoryCache.entries())
      .sort(([, a], [, b]) => 
        (a.metadata?.lastAccessed || 0) - (b.metadata?.lastAccessed || 0)
      );
    
    const toRemove = entries.slice(0, this.memoryCache.size - maxEntries);
    toRemove.forEach(([key]) => this.memoryCache.delete(key));
    
    console.log('🧹 Cache: Cleaned up', toRemove.length, 'memory entries');
  }

  private async cleanupLocalStorage(): Promise<void> {
    const keys = this.getStoredKeys();
    const entries = keys.map(key => ({
      key,
      entry: this.getFromLocalStorage(key)
    })).filter(({ entry }) => entry !== null);
    
    // Remove expired entries first
    const expired = entries.filter(({ entry }) => !this.isValidEntry(entry!));
    expired.forEach(({ key }) => this.removeFromLocalStorage(key));
    
    if (expired.length > 0) {
      console.log('🧹 Cache: Cleaned up', expired.length, 'expired localStorage entries');
    }
  }

  private setupCleanupInterval(): void {
    // Cleanup every 5 minutes
    setInterval(() => {
      this.cleanupLocalStorage();
      this.updateStats();
    }, 5 * 60 * 1000);
  }

  private setupStorageEventListener(): void {
    // Listen for localStorage changes in other tabs
    window.addEventListener('storage', (event) => {
      if (event.key?.startsWith(this.STORAGE_PREFIX)) {
        const cacheKey = event.key.substring(this.STORAGE_PREFIX.length);
        
        if (event.newValue === null) {
          // Entry was removed
          this.memoryCache.delete(cacheKey);
        } else {
          // Entry was updated - invalidate memory cache to force reload
          this.memoryCache.delete(cacheKey);
        }
        
        console.log('🔄 Cache: Storage event for', cacheKey);
      }
    });
  }

  private resetStats(): void {
    this.stats = {
      memoryHits: 0,
      localStorageHits: 0,
      apiCalls: 0,
      totalRequests: 0,
      hitRate: 0,
      averageResponseTime: 0,
      storageUsage: { memory: 0, localStorage: 0, total: 0 }
    };
  }
}

// Export singleton instance
export const cacheManager = new CacheManager();

// React hook for cache management
export const useCache = <T>(
  key: string,
  fetcher: () => Promise<T>,
  config: Partial<CacheConfig> = {}
) => {
  const [data, setData] = React.useState<T | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let mounted = true;

    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const result = await cacheManager.get(key, fetcher, config);
        
        if (mounted) {
          setData(result);
          setLoading(false);
        }
      } catch (err) {
        if (mounted) {
          setError(err instanceof Error ? err.message : 'Unknown error');
          setLoading(false);
        }
      }
    };

    fetchData();

    return () => {
      mounted = false;
    };
  }, [key]);

  const refresh = React.useCallback(async () => {
    cacheManager.invalidate(key);
    setLoading(true);
    setError(null);
    
    try {
      const result = await cacheManager.get(key, fetcher, config);
      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      setLoading(false);
    }
  }, [key, fetcher, config]);

  return { data, loading, error, refresh };
};

export default cacheManager;