/**
 * Shared Cache Management System
 * Centralized cache coordination for all Jaspel components
 */

import { BaseJaspelItem, JaspelSummary, DashboardData, JaspelVariant, CacheConfig } from './types';

interface CacheEntry<T = any> {
  data: T;
  timestamp: number;
  ttl: number;
  accessCount: number;
  lastAccessed: number;
  tags: string[];
  size: number;
}

interface CacheStats {
  totalEntries: number;
  totalSize: number;
  hitRate: number;
  missRate: number;
  evictionCount: number;
  totalAccesses: number;
  cacheHits: number;
  cacheMisses: number;
}

interface CacheMetrics {
  variant: JaspelVariant;
  stats: CacheStats;
  performance: {
    avgAccessTime: number;
    slowestAccess: number;
    fastestAccess: number;
  };
}

/**
 * Enhanced LRU Cache with tagging, size tracking, and metrics
 */
class EnhancedLRUCache {
  private cache: Map<string, CacheEntry> = new Map();
  private accessOrder: string[] = [];
  private config: Required<CacheConfig>;
  private stats: CacheStats;
  private accessTimes: number[] = [];

  constructor(config: Partial<CacheConfig> = {}) {
    this.config = {
      ttl: config.ttl || 300000, // 5 minutes
      maxSize: config.maxSize || 100,
      strategy: config.strategy || 'lru'
    };

    this.stats = {
      totalEntries: 0,
      totalSize: 0,
      hitRate: 0,
      missRate: 0,
      evictionCount: 0,
      totalAccesses: 0,
      cacheHits: 0,
      cacheMisses: 0
    };
  }

  private calculateSize(data: any): number {
    try {
      return JSON.stringify(data).length;
    } catch {
      return 1000; // Fallback size estimate
    }
  }

  private updateAccessOrder(key: string): void {
    const index = this.accessOrder.indexOf(key);
    if (index > -1) {
      this.accessOrder.splice(index, 1);
    }
    this.accessOrder.push(key);
  }

  private evictLRU(): void {
    if (this.accessOrder.length === 0) return;

    const keyToEvict = this.accessOrder.shift()!;
    const entry = this.cache.get(keyToEvict);
    
    if (entry) {
      this.stats.totalSize -= entry.size;
      this.stats.evictionCount++;
    }
    
    this.cache.delete(keyToEvict);
  }

  private shouldEvict(): boolean {
    return this.cache.size >= this.config.maxSize;
  }

  private isExpired(entry: CacheEntry): boolean {
    return Date.now() - entry.timestamp > entry.ttl;
  }

  private updateStats(hit: boolean, accessTime: number): void {
    this.stats.totalAccesses++;
    this.accessTimes.push(accessTime);
    
    // Keep only last 100 access times for performance metrics
    if (this.accessTimes.length > 100) {
      this.accessTimes.shift();
    }

    if (hit) {
      this.stats.cacheHits++;
    } else {
      this.stats.cacheMisses++;
    }

    this.stats.hitRate = (this.stats.cacheHits / this.stats.totalAccesses) * 100;
    this.stats.missRate = (this.stats.cacheMisses / this.stats.totalAccesses) * 100;
  }

  set(key: string, data: any, options: {
    ttl?: number;
    tags?: string[];
    skipSizeCheck?: boolean;
  } = {}): void {
    const startTime = performance.now();
    
    const size = options.skipSizeCheck ? 0 : this.calculateSize(data);
    const entry: CacheEntry = {
      data,
      timestamp: Date.now(),
      ttl: options.ttl || this.config.ttl,
      accessCount: 0,
      lastAccessed: Date.now(),
      tags: options.tags || [],
      size
    };

    // Handle existing entry
    const existingEntry = this.cache.get(key);
    if (existingEntry) {
      this.stats.totalSize -= existingEntry.size;
    }

    // Evict if necessary
    while (this.shouldEvict() && !existingEntry) {
      this.evictLRU();
    }

    this.cache.set(key, entry);
    this.updateAccessOrder(key);
    this.stats.totalSize += size;
    this.stats.totalEntries = this.cache.size;

    const accessTime = performance.now() - startTime;
    this.updateStats(false, accessTime);
  }

  get(key: string): any | null {
    const startTime = performance.now();
    const entry = this.cache.get(key);

    if (!entry) {
      const accessTime = performance.now() - startTime;
      this.updateStats(false, accessTime);
      return null;
    }

    if (this.isExpired(entry)) {
      this.delete(key);
      const accessTime = performance.now() - startTime;
      this.updateStats(false, accessTime);
      return null;
    }

    // Update access metadata
    entry.accessCount++;
    entry.lastAccessed = Date.now();
    this.updateAccessOrder(key);

    const accessTime = performance.now() - startTime;
    this.updateStats(true, accessTime);

    return entry.data;
  }

  delete(key: string): boolean {
    const entry = this.cache.get(key);
    if (entry) {
      this.stats.totalSize -= entry.size;
      
      const index = this.accessOrder.indexOf(key);
      if (index > -1) {
        this.accessOrder.splice(index, 1);
      }
    }

    const deleted = this.cache.delete(key);
    this.stats.totalEntries = this.cache.size;
    return deleted;
  }

  clear(): void {
    this.cache.clear();
    this.accessOrder = [];
    this.stats.totalEntries = 0;
    this.stats.totalSize = 0;
  }

  has(key: string): boolean {
    const entry = this.cache.get(key);
    return !!entry && !this.isExpired(entry);
  }

  invalidateByTag(tag: string): number {
    let invalidated = 0;
    
    for (const [key, entry] of this.cache.entries()) {
      if (entry.tags.includes(tag)) {
        this.delete(key);
        invalidated++;
      }
    }

    return invalidated;
  }

  cleanupExpired(): number {
    let cleaned = 0;
    const now = Date.now();

    for (const [key, entry] of this.cache.entries()) {
      if (now - entry.timestamp > entry.ttl) {
        this.delete(key);
        cleaned++;
      }
    }

    return cleaned;
  }

  getStats(): CacheStats {
    return { ...this.stats };
  }

  getMetrics(): CacheMetrics['performance'] {
    if (this.accessTimes.length === 0) {
      return { avgAccessTime: 0, slowestAccess: 0, fastestAccess: 0 };
    }

    const avg = this.accessTimes.reduce((sum, time) => sum + time, 0) / this.accessTimes.length;
    const slowest = Math.max(...this.accessTimes);
    const fastest = Math.min(...this.accessTimes);

    return {
      avgAccessTime: avg,
      slowestAccess: slowest,
      fastestAccess: fastest
    };
  }

  getAllKeys(): string[] {
    return Array.from(this.cache.keys());
  }

  getSize(): number {
    return this.cache.size;
  }

  getTotalSize(): number {
    return this.stats.totalSize;
  }
}

/**
 * Jaspel Cache Manager - Centralized cache coordination
 */
class JaspelCacheManager {
  private static instance: JaspelCacheManager;
  private caches: Map<string, EnhancedLRUCache> = new Map();
  private cleanupInterval: NodeJS.Timeout | null = null;

  private constructor() {
    // Start periodic cleanup
    this.startCleanupRoutine();
  }

  static getInstance(): JaspelCacheManager {
    if (!JaspelCacheManager.instance) {
      JaspelCacheManager.instance = new JaspelCacheManager();
    }
    return JaspelCacheManager.instance;
  }

  private getCacheKey(namespace: string, variant: JaspelVariant): string {
    return `${namespace}_${variant}`;
  }

  private startCleanupRoutine(): void {
    this.cleanupInterval = setInterval(() => {
      this.cleanupAllCaches();
    }, 60000); // Cleanup every minute
  }

  private cleanupAllCaches(): void {
    let totalCleaned = 0;
    
    for (const cache of this.caches.values()) {
      totalCleaned += cache.cleanupExpired();
    }

    if (totalCleaned > 0) {
      console.log(`🧹 Cleaned up ${totalCleaned} expired cache entries`);
    }
  }

  getCache(namespace: string, variant: JaspelVariant, config?: Partial<CacheConfig>): EnhancedLRUCache {
    const key = this.getCacheKey(namespace, variant);
    
    if (!this.caches.has(key)) {
      this.caches.set(key, new EnhancedLRUCache(config));
    }

    return this.caches.get(key)!;
  }

  // Specialized cache getters
  getDataCache(variant: JaspelVariant): EnhancedLRUCache {
    return this.getCache('data', variant, { ttl: 300000, maxSize: 50 });
  }

  getSummaryCache(variant: JaspelVariant): EnhancedLRUCache {
    return this.getCache('summary', variant, { ttl: 180000, maxSize: 30 });
  }

  getDashboardCache(variant: JaspelVariant): EnhancedLRUCache {
    return this.getCache('dashboard', variant, { ttl: 120000, maxSize: 20 });
  }

  getGamingCache(variant: JaspelVariant): EnhancedLRUCache {
    return this.getCache('gaming', variant, { ttl: 600000, maxSize: 100 });
  }

  // High-level cache operations
  cacheJaspelData(
    variant: JaspelVariant, 
    month: number, 
    year: number, 
    data: BaseJaspelItem[], 
    summary: JaspelSummary
  ): void {
    const dataCache = this.getDataCache(variant);
    const summaryCache = this.getSummaryCache(variant);
    
    const key = `${month}_${year}`;
    const tags = [`variant:${variant}`, `period:${month}_${year}`, 'jaspel:data'];

    dataCache.set(key, data, { tags });
    summaryCache.set(key, summary, { tags });
  }

  getCachedJaspelData(
    variant: JaspelVariant, 
    month: number, 
    year: number
  ): { data: BaseJaspelItem[]; summary: JaspelSummary } | null {
    const dataCache = this.getDataCache(variant);
    const summaryCache = this.getSummaryCache(variant);
    
    const key = `${month}_${year}`;
    const data = dataCache.get(key);
    const summary = summaryCache.get(key);

    if (data && summary) {
      return { data, summary };
    }

    return null;
  }

  cacheDashboardData(variant: JaspelVariant, data: DashboardData): void {
    const cache = this.getDashboardCache(variant);
    const tags = [`variant:${variant}`, 'dashboard:data'];
    
    cache.set('current', data, { tags });
  }

  getCachedDashboardData(variant: JaspelVariant): DashboardData | null {
    const cache = this.getDashboardCache(variant);
    return cache.get('current');
  }

  // Gaming-specific cache operations
  cacheAchievement(variant: JaspelVariant, userId: string, achievement: any): void {
    const cache = this.getGamingCache(variant);
    const key = `achievement_${userId}`;
    const tags = [`variant:${variant}`, `user:${userId}`, 'gaming:achievement'];
    
    let achievements = cache.get(key) || [];
    achievements = [achievement, ...achievements].slice(0, 50); // Keep last 50
    
    cache.set(key, achievements, { tags });
  }

  getCachedAchievements(variant: JaspelVariant, userId: string): any[] {
    const cache = this.getGamingCache(variant);
    return cache.get(`achievement_${userId}`) || [];
  }

  // Cache invalidation
  invalidateJaspelData(variant: JaspelVariant, month?: number, year?: number): void {
    if (month && year) {
      const key = `${month}_${year}`;
      this.getDataCache(variant).delete(key);
      this.getSummaryCache(variant).delete(key);
    } else {
      // Invalidate all data for variant
      this.getDataCache(variant).invalidateByTag(`variant:${variant}`);
      this.getSummaryCache(variant).invalidateByTag(`variant:${variant}`);
    }
  }

  invalidateDashboardData(variant: JaspelVariant): void {
    this.getDashboardCache(variant).clear();
  }

  invalidateUserData(variant: JaspelVariant, userId: string): void {
    this.getGamingCache(variant).invalidateByTag(`user:${userId}`);
  }

  invalidateAll(): void {
    for (const cache of this.caches.values()) {
      cache.clear();
    }
  }

  // Metrics and monitoring
  getAllMetrics(): Map<string, CacheMetrics> {
    const metrics = new Map<string, CacheMetrics>();

    for (const [key, cache] of this.caches.entries()) {
      const [namespace, variant] = key.split('_');
      
      metrics.set(key, {
        variant: variant as JaspelVariant,
        stats: cache.getStats(),
        performance: cache.getMetrics()
      });
    }

    return metrics;
  }

  getCacheMetrics(namespace: string, variant: JaspelVariant): CacheMetrics | null {
    const key = this.getCacheKey(namespace, variant);
    const cache = this.caches.get(key);
    
    if (!cache) return null;

    return {
      variant,
      stats: cache.getStats(),
      performance: cache.getMetrics()
    };
  }

  // Memory management
  getMemoryUsage(): {
    totalCaches: number;
    totalEntries: number;
    totalSize: number;
    averageHitRate: number;
  } {
    let totalEntries = 0;
    let totalSize = 0;
    let totalHitRate = 0;
    let activeCaches = 0;

    for (const cache of this.caches.values()) {
      const stats = cache.getStats();
      totalEntries += stats.totalEntries;
      totalSize += stats.totalSize;
      
      if (stats.totalAccesses > 0) {
        totalHitRate += stats.hitRate;
        activeCaches++;
      }
    }

    return {
      totalCaches: this.caches.size,
      totalEntries,
      totalSize,
      averageHitRate: activeCaches > 0 ? totalHitRate / activeCaches : 0
    };
  }

  // Cleanup and lifecycle
  destroy(): void {
    if (this.cleanupInterval) {
      clearInterval(this.cleanupInterval);
      this.cleanupInterval = null;
    }
    
    this.invalidateAll();
    this.caches.clear();
  }
}

// Export singleton instance
export const jaspelCacheManager = JaspelCacheManager.getInstance();

// Export cache classes for advanced usage
export { EnhancedLRUCache, JaspelCacheManager };

// Export types
export type { CacheEntry, CacheStats, CacheMetrics };