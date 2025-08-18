/**
 * Turbo Fetch - Ultimate performance optimization
 * Target: <50ms through aggressive caching and request pooling
 */

// In-memory cache (fastest)
let memoryCache: any = null;
let cacheTime = 0;

// Request pooling to prevent duplicate requests
let activeRequest: Promise<any> | null = null;

// Session storage key
const STORAGE_KEY = 'turbo_dashboard_cache';
const CACHE_DURATION = 60000; // 60 seconds

export async function turboDashboardFetch(): Promise<[any, any]> {
  const start = performance.now();
  
  // Level 1: Memory cache (0ms)
  if (memoryCache && (Date.now() - cacheTime) < CACHE_DURATION) {
    const elapsed = performance.now() - start;
    console.log(`⚡ TURBO: Memory cache hit! ${elapsed.toFixed(2)}ms`);
    return memoryCache;
  }
  
  // Level 2: Session storage cache (1-2ms)
  try {
    const stored = sessionStorage.getItem(STORAGE_KEY);
    if (stored) {
      const { data, timestamp } = JSON.parse(stored);
      if ((Date.now() - timestamp) < CACHE_DURATION) {
        memoryCache = data;
        cacheTime = timestamp;
        const elapsed = performance.now() - start;
        console.log(`📦 TURBO: Storage cache hit! ${elapsed.toFixed(2)}ms`);
        return data;
      }
    }
  } catch (e) {
    // Ignore storage errors
  }
  
  // Level 3: Request pooling (reuse active request)
  if (activeRequest) {
    console.log('♻️ TURBO: Reusing active request');
    return activeRequest;
  }
  
  // Level 4: Make actual request
  activeRequest = performTurboFetch();
  
  try {
    const result = await activeRequest;
    
    // Store in both caches
    memoryCache = result;
    cacheTime = Date.now();
    
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        data: result,
        timestamp: cacheTime
      }));
    } catch (e) {
      // Ignore storage errors
    }
    
    const elapsed = performance.now() - start;
    console.log(`🚀 TURBO: Fresh fetch ${elapsed.toFixed(2)}ms`);
    
    if (elapsed < 50) {
      console.log(`✅ PERFORMANCE TARGET ACHIEVED: ${elapsed.toFixed(2)}ms < 50ms! 🎉`);
    } else {
      // Analyze why it's slow
      console.log(`📊 Performance breakdown:`);
      console.log(`  - Target: <50ms`);
      console.log(`  - Actual: ${elapsed.toFixed(2)}ms`);
      console.log(`  - Cache will make next calls instant`);
    }
    
    return result;
  } finally {
    activeRequest = null;
  }
}

async function performTurboFetch(): Promise<[any, any]> {
  // Minimal headers
  const headers: any = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };
  
  // CSRF token
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrf) headers['X-CSRF-TOKEN'] = csrf;
  
  // Single combined request if possible
  try {
    // Try to fetch both in one request (if backend supports it)
    const combined = await fetch('/api/v2/dashboards/dokter?include=presensi', {
      method: 'GET',
      headers,
      credentials: 'same-origin',
    });
    
    if (combined.ok) {
      const data = await combined.json();
      if (data.data && data.presensi) {
        console.log('⚡ TURBO: Combined request successful');
        return [data.data, data.presensi];
      }
    }
  } catch (e) {
    // Fall back to parallel requests
  }
  
  // Parallel requests as fallback
  const [res1, res2] = await Promise.all([
    fetch('/api/v2/dashboards/dokter', {
      method: 'GET',
      headers,
      credentials: 'same-origin',
    }),
    fetch('/api/v2/dashboards/dokter/presensi', {
      method: 'GET',
      headers,
      credentials: 'same-origin',
    })
  ]);
  
  const [data1, data2] = await Promise.all([
    res1.json(),
    res2.json()
  ]);
  
  return [data1.data || data1, data2];
}

// Prefetch on load
if (typeof window !== 'undefined') {
  // Prefetch after page is idle
  if ('requestIdleCallback' in window) {
    requestIdleCallback(() => turboDashboardFetch().catch(() => {}));
  } else {
    setTimeout(() => turboDashboardFetch().catch(() => {}), 50);
  }
}

// Clear cache on visibility change (optional)
if (typeof document !== 'undefined') {
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      // Check if cache is stale
      if (memoryCache && (Date.now() - cacheTime) > CACHE_DURATION) {
        memoryCache = null;
        cacheTime = 0;
        console.log('🔄 TURBO: Cache cleared, will refresh on next call');
      }
    }
  });
}