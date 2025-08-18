/**
 * Cache Manager
 * Multi-tier caching system with Memory → IndexedDB → LocalStorage fallback
 * Implements LRU eviction, versioning, and intelligent cache warming
 */

// Cache configuration
const CACHE_CONFIG = {
  MEMORY_LIMIT: 10 * 1024 * 1024, // 10MB
  INDEXEDDB_LIMIT: 50 * 1024 * 1024, // 50MB
  LOCALSTORAGE_LIMIT: 5 * 1024 * 1024, // 5MB
  DEFAULT_TTL: 5 * 60 * 1000, // 5 minutes
  VERSION: 'v1',
  DB_NAME: 'DokterKuCache',
  STORE_NAME: 'dashboard',
};

// Cache entry interface
interface CacheEntry<T = any> {
  key: string;
  data: T;
  timestamp: number;
  ttl: number;
  size: number;
  accessCount: number;
  lastAccessed: number;
  version: string;
  strategy?: 'stale-while-revalidate' | 'cache-first' | 'network-first';
}

// Cache options
export interface CacheOptions {
  ttl?: number;
  strategy?: 'stale-while-revalidate' | 'cache-first' | 'network-first';
  compress?: boolean;
}

// LRU Node for memory cache
class LRUNode<T = any> {
  key: string;
  value: CacheEntry<T>;
  prev: LRUNode<T> | null = null;
  next: LRUNode<T> | null = null;

  constructor(key: string, value: CacheEntry<T>) {
    this.key = key;
    this.value = value;
  }
}

// LRU Cache implementation
class LRUCache<T = any> {
  private capacity: number;
  private currentSize: number = 0;
  private cache: Map<string, LRUNode<T>> = new Map();
  private head: LRUNode<T> | null = null;
  private tail: LRUNode<T> | null = null;

  constructor(capacity: number) {
    this.capacity = capacity;
  }

  get(key: string): CacheEntry<T> | null {
    const node = this.cache.get(key);
    if (!node) return null;

    // Move to head (most recently used)
    this.moveToHead(node);
    
    // Update access stats
    node.value.accessCount++;
    node.value.lastAccessed = Date.now();

    return node.value;
  }

  set(key: string, value: CacheEntry<T>): void {
    const existingNode = this.cache.get(key);

    if (existingNode) {
      // Update existing node
      this.currentSize -= existingNode.value.size;
      existingNode.value = value;
      this.currentSize += value.size;
      this.moveToHead(existingNode);
    } else {
      // Add new node
      const newNode = new LRUNode(key, value);
      this.cache.set(key, newNode);
      this.addToHead(newNode);
      this.currentSize += value.size;

      // Evict if over capacity
      while (this.currentSize > this.capacity && this.tail) {
        this.evictLRU();
      }
    }
  }

  private moveToHead(node: LRUNode<T>): void {
    this.removeNode(node);
    this.addToHead(node);
  }

  private addToHead(node: LRUNode<T>): void {
    node.prev = null;
    node.next = this.head;

    if (this.head) {
      this.head.prev = node;
    }
    this.head = node;

    if (!this.tail) {
      this.tail = node;
    }
  }

  private removeNode(node: LRUNode<T>): void {
    if (node.prev) {
      node.prev.next = node.next;
    } else {
      this.head = node.next;
    }

    if (node.next) {
      node.next.prev = node.prev;
    } else {
      this.tail = node.prev;
    }
  }

  private evictLRU(): void {
    if (!this.tail) return;

    const evicted = this.tail;
    this.currentSize -= evicted.value.size;
    this.cache.delete(evicted.key);
    this.removeNode(evicted);

    console.log(`🗑️ Evicted from memory cache: ${evicted.key}`);
  }

  clear(): void {
    this.cache.clear();
    this.head = null;
    this.tail = null;
    this.currentSize = 0;
  }

  getSize(): number {
    return this.currentSize;
  }

  getStats(): { entries: number; size: number } {
    return {
      entries: this.cache.size,
      size: this.currentSize,
    };
  }
}

class CacheManager {
  private static instance: CacheManager;
  private memoryCache: LRUCache;
  private db: IDBDatabase | null = null;
  private stats = {
    hits: 0,
    misses: 0,
    evictions: 0,
  };

  private constructor() {
    this.memoryCache = new LRUCache(CACHE_CONFIG.MEMORY_LIMIT);
    this.initializeIndexedDB();
  }

  static getInstance(): CacheManager {
    if (!CacheManager.instance) {
      CacheManager.instance = new CacheManager();
    }
    return CacheManager.instance;
  }

  /**
   * Initialize IndexedDB
   */
  private async initializeIndexedDB(): Promise<void> {
    if (typeof window === 'undefined' || !window.indexedDB) {
      console.warn('IndexedDB not available');
      return;
    }

    try {
      const request = indexedDB.open(CACHE_CONFIG.DB_NAME, 1);

      request.onerror = () => {
        console.error('Failed to open IndexedDB');
      };

      request.onsuccess = () => {
        this.db = request.result;
        console.log('✅ IndexedDB initialized');
      };

      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;
        
        if (!db.objectStoreNames.contains(CACHE_CONFIG.STORE_NAME)) {
          const store = db.createObjectStore(CACHE_CONFIG.STORE_NAME, { keyPath: 'key' });
          store.createIndex('timestamp', 'timestamp', { unique: false });
          store.createIndex('lastAccessed', 'lastAccessed', { unique: false });
        }
      };
    } catch (error) {
      console.error('IndexedDB initialization failed:', error);
    }
  }

  /**
   * Get data from cache (multi-tier)
   */
  async get<T = any>(key: string): Promise<T | null> {
    // 1. Try memory cache first (fastest)
    const memoryEntry = this.memoryCache.get(key);
    if (memoryEntry && this.isEntryValid(memoryEntry)) {
      this.stats.hits++;
      console.log(`💾 Cache hit (memory): ${key}`);
      return memoryEntry.data;
    }

    // 2. Try IndexedDB (fast)
    const idbEntry = await this.getFromIndexedDB<T>(key);
    if (idbEntry && this.isEntryValid(idbEntry)) {
      // Promote to memory cache
      this.memoryCache.set(key, idbEntry);
      this.stats.hits++;
      console.log(`🗄️ Cache hit (IndexedDB): ${key}`);
      return idbEntry.data;
    }

    // 3. Try LocalStorage (fallback)
    const lsEntry = this.getFromLocalStorage<T>(key);
    if (lsEntry && this.isEntryValid(lsEntry)) {
      // Promote to higher tiers
      this.memoryCache.set(key, lsEntry);
      await this.saveToIndexedDB(key, lsEntry);
      this.stats.hits++;
      console.log(`📦 Cache hit (LocalStorage): ${key}`);
      return lsEntry.data;
    }

    this.stats.misses++;
    console.log(`❌ Cache miss: ${key}`);
    return null;
  }

  /**
   * Set data in cache (all tiers)
   */
  async set<T = any>(key: string, data: T, options: CacheOptions = {}): Promise<void> {
    const entry: CacheEntry<T> = {
      key,
      data,
      timestamp: Date.now(),
      ttl: options.ttl || CACHE_CONFIG.DEFAULT_TTL,
      size: this.calculateSize(data),
      accessCount: 0,
      lastAccessed: Date.now(),
      version: CACHE_CONFIG.VERSION,
      strategy: options.strategy || 'cache-first',
    };

    // Save to all tiers
    this.memoryCache.set(key, entry);
    await this.saveToIndexedDB(key, entry);
    this.saveToLocalStorage(key, entry);

    console.log(`✅ Cached: ${key} (${entry.size} bytes)`);
  }

  /**
   * Check if cache entry is valid
   */
  isValid(key: string): boolean {
    const entry = this.memoryCache.get(key);
    return entry ? this.isEntryValid(entry) : false;
  }

  /**
   * Get remaining TTL for cache entry
   */
  getRemainingTTL(key: string): number {
    const entry = this.memoryCache.get(key);
    if (!entry) return 0;

    const elapsed = Date.now() - entry.timestamp;
    return Math.max(0, entry.ttl - elapsed);
  }

  /**
   * Clear all caches
   */
  async clear(): Promise<void> {
    // Clear memory cache
    this.memoryCache.clear();

    // Clear IndexedDB
    if (this.db) {
      const transaction = this.db.transaction([CACHE_CONFIG.STORE_NAME], 'readwrite');
      const store = transaction.objectStore(CACHE_CONFIG.STORE_NAME);
      store.clear();
    }

    // Clear LocalStorage
    if (typeof localStorage !== 'undefined') {
      const keys = Object.keys(localStorage);
      keys.forEach(key => {
        if (key.startsWith('cache:')) {
          localStorage.removeItem(key);
        }
      });
    }

    console.log('🧹 All caches cleared');
  }

  /**
   * Get cache statistics
   */
  getStats(): { hitRate: number; size: number; entries: number } {
    const total = this.stats.hits + this.stats.misses;
    const memoryStats = this.memoryCache.getStats();

    return {
      hitRate: total > 0 ? this.stats.hits / total : 0,
      size: memoryStats.size,
      entries: memoryStats.entries,
    };
  }

  /**
   * Check if entry is valid (not expired)
   */
  private isEntryValid(entry: CacheEntry): boolean {
    const now = Date.now();
    const age = now - entry.timestamp;

    // Check version
    if (entry.version !== CACHE_CONFIG.VERSION) {
      return false;
    }

    // Check TTL
    if (age > entry.ttl) {
      // Stale-while-revalidate allows using stale data
      if (entry.strategy === 'stale-while-revalidate') {
        // Allow stale data for additional time
        return age < entry.ttl * 2;
      }
      return false;
    }

    return true;
  }

  /**
   * Get from IndexedDB
   */
  private async getFromIndexedDB<T>(key: string): Promise<CacheEntry<T> | null> {
    if (!this.db) return null;

    return new Promise((resolve) => {
      try {
        const transaction = this.db!.transaction([CACHE_CONFIG.STORE_NAME], 'readonly');
        const store = transaction.objectStore(CACHE_CONFIG.STORE_NAME);
        const request = store.get(key);

        request.onsuccess = () => {
          resolve(request.result || null);
        };

        request.onerror = () => {
          resolve(null);
        };
      } catch (error) {
        resolve(null);
      }
    });
  }

  /**
   * Save to IndexedDB
   */
  private async saveToIndexedDB(key: string, entry: CacheEntry): Promise<void> {
    if (!this.db) return;

    return new Promise((resolve) => {
      try {
        const transaction = this.db!.transaction([CACHE_CONFIG.STORE_NAME], 'readwrite');
        const store = transaction.objectStore(CACHE_CONFIG.STORE_NAME);
        store.put(entry);

        transaction.oncomplete = () => resolve();
        transaction.onerror = () => resolve();
      } catch (error) {
        resolve();
      }
    });
  }

  /**
   * Get from LocalStorage
   */
  private getFromLocalStorage<T>(key: string): CacheEntry<T> | null {
    if (typeof localStorage === 'undefined') return null;

    try {
      const item = localStorage.getItem(`cache:${key}`);
      if (!item) return null;

      return JSON.parse(item);
    } catch (error) {
      return null;
    }
  }

  /**
   * Save to LocalStorage
   */
  private saveToLocalStorage(key: string, entry: CacheEntry): void {
    if (typeof localStorage === 'undefined') return;

    try {
      // Check size limit
      const serialized = JSON.stringify(entry);
      if (serialized.length > CACHE_CONFIG.LOCALSTORAGE_LIMIT) {
        console.warn(`Entry too large for LocalStorage: ${key}`);
        return;
      }

      localStorage.setItem(`cache:${key}`, serialized);
    } catch (error) {
      // Quota exceeded or other error
      console.warn('LocalStorage save failed:', error);
    }
  }

  /**
   * Calculate approximate size of data
   */
  private calculateSize(data: any): number {
    try {
      return JSON.stringify(data).length;
    } catch {
      return 0;
    }
  }

  /**
   * Warm up cache with predictive data
   */
  async warmup(keys: string[], fetcher: (key: string) => Promise<any>): Promise<void> {
    console.log('🔥 Warming up cache...');

    await Promise.all(
      keys.map(async (key) => {
        const cached = await this.get(key);
        if (!cached) {
          try {
            const data = await fetcher(key);
            await this.set(key, data);
          } catch (error) {
            console.error(`Failed to warm up ${key}:`, error);
          }
        }
      })
    );

    console.log('✅ Cache warmup complete');
  }
}

// Export singleton instance
export default CacheManager;