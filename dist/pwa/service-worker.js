/**
 * Dokterku Bendahara PWA Service Worker
 * Enhanced offline-first caching strategy for financial dashboard
 * Version: 1.0.0
 * Date: 2025-07-16
 */

const CACHE_NAME = 'dokterku-bendahara-v1.0.0';
const STATIC_CACHE = 'dokterku-static-v1.0.0';
const DYNAMIC_CACHE = 'dokterku-dynamic-v1.0.0';
const API_CACHE = 'dokterku-api-v1.0.0';

// Critical assets for offline functionality
const STATIC_ASSETS = [
  '/bendahara',
  '/bendahara/login',
  '/manifest.json',
  '/pwa/bendahara-manifest.json',
  '/css/app.css',
  '/js/app.js',
  '/images/logo.png',
  '/favicon.ico',
  // Filament assets
  '/css/filament/bendahara/theme.css',
  // Critical widget assets
  'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js'
];

// API endpoints that can be cached with TTL
const CACHEABLE_API_ROUTES = [
  '/bendahara/stats',
  '/bendahara/validation-queue',
  '/bendahara/financial-overview',
  '/api/bendahara/dashboard-stats',
  '/api/bendahara/trends'
];

// Routes that should always fetch fresh data
const FRESH_DATA_ROUTES = [
  '/bendahara/validation-queue/actions',
  '/api/bendahara/approve',
  '/api/bendahara/reject',
  '/api/livewire'
];

// Cache duration settings (in milliseconds)
const CACHE_DURATIONS = {
  static: 7 * 24 * 60 * 60 * 1000,    // 7 days
  api: 5 * 60 * 1000,                   // 5 minutes
  dynamic: 1 * 60 * 60 * 1000           // 1 hour
};

/**
 * Service Worker Installation
 */
self.addEventListener('install', event => {
  console.log('[SW] Installing service worker...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('[SW] Caching static assets...');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[SW] Static assets cached successfully');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('[SW] Failed to cache static assets:', error);
      })
  );
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', event => {
  console.log('[SW] Activating service worker...');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== STATIC_CACHE && 
                cacheName !== DYNAMIC_CACHE && 
                cacheName !== API_CACHE) {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Take control of all pages
      self.clients.claim()
    ])
  );
});

/**
 * Fetch Event Handler - Smart Caching Strategy
 */
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-HTTP requests
  if (!request.url.startsWith('http')) {
    return;
  }
  
  // Handle different types of requests
  if (isStaticAsset(request)) {
    event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
  } else if (isCacheableAPI(request)) {
    event.respondWith(staleWhileRevalidateStrategy(request, API_CACHE));
  } else if (isFreshDataRoute(request)) {
    event.respondWith(networkFirstStrategy(request));
  } else if (isBendaharaRoute(request)) {
    event.respondWith(networkFirstWithOfflineFallback(request));
  } else {
    event.respondWith(staleWhileRevalidateStrategy(request, DYNAMIC_CACHE));
  }
});

/**
 * Cache-First Strategy for Static Assets
 */
async function cacheFirstStrategy(request, cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      console.log('[SW] Serving from cache:', request.url);
      return cachedResponse;
    }
    
    console.log('[SW] Fetching and caching:', request.url);
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      await cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('[SW] Cache-first strategy failed:', error);
    return new Response('Offline content not available', { status: 503 });
  }
}

/**
 * Stale-While-Revalidate Strategy for Dynamic Content
 */
async function staleWhileRevalidateStrategy(request, cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    // Start fetch in background
    const fetchPromise = fetch(request).then(response => {
      if (response.ok) {
        cache.put(request, response.clone());
      }
      return response;
    });
    
    // Return cached version immediately if available, otherwise wait for network
    if (cachedResponse && !isExpired(cachedResponse, CACHE_DURATIONS.api)) {
      console.log('[SW] Serving cached content (revalidating):', request.url);
      fetchPromise.catch(() => {}); // Ignore background fetch failures
      return cachedResponse;
    }
    
    console.log('[SW] Fetching fresh content:', request.url);
    return await fetchPromise;
  } catch (error) {
    console.error('[SW] Stale-while-revalidate failed:', error);
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    return cachedResponse || new Response('Content unavailable', { status: 503 });
  }
}

/**
 * Network-First Strategy for Fresh Data
 */
async function networkFirstStrategy(request) {
  try {
    console.log('[SW] Network-first fetch:', request.url);
    const response = await fetch(request);
    return response;
  } catch (error) {
    console.error('[SW] Network-first failed:', error);
    return new Response(JSON.stringify({
      error: 'Network unavailable',
      offline: true,
      timestamp: Date.now()
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

/**
 * Network-First with Offline Fallback for Bendahara Routes
 */
async function networkFirstWithOfflineFallback(request) {
  try {
    const response = await fetch(request);
    
    if (response.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      await cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.log('[SW] Network failed, trying cache:', request.url);
    
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
      const offlineHTML = await getOfflinePage();
      return new Response(offlineHTML, {
        headers: { 'Content-Type': 'text/html' }
      });
    }
    
    return new Response('Offline content not available', { status: 503 });
  }
}

/**
 * Helper Functions
 */
function isStaticAsset(request) {
  const url = request.url;
  return url.includes('.css') || 
         url.includes('.js') || 
         url.includes('.png') || 
         url.includes('.jpg') || 
         url.includes('.jpeg') ||
         url.includes('.svg') ||
         url.includes('.ico') ||
         url.includes('manifest.json');
}

function isCacheableAPI(request) {
  return CACHEABLE_API_ROUTES.some(route => request.url.includes(route));
}

function isFreshDataRoute(request) {
  return FRESH_DATA_ROUTES.some(route => request.url.includes(route));
}

function isBendaharaRoute(request) {
  return request.url.includes('/bendahara');
}

function isExpired(response, duration) {
  const dateHeader = response.headers.get('date');
  if (!dateHeader) return true;
  
  const responseTime = new Date(dateHeader).getTime();
  return Date.now() - responseTime > duration;
}

/**
 * Generate Offline Page HTML
 */
async function getOfflinePage() {
  return `
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dokterku Bendahara - Offline</title>
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #fbbf23, #f59e0b);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #1f2937;
            }
            .offline-container {
                text-align: center;
                background: white;
                padding: 3rem;
                border-radius: 1rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                max-width: 400px;
                margin: 1rem;
            }
            .icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            h1 {
                color: #fbbf23;
                margin-bottom: 1rem;
                font-size: 1.5rem;
            }
            p {
                color: #6b7280;
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            .retry-btn {
                background: #fbbf23;
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 0.5rem;
                cursor: pointer;
                font-size: 1rem;
                font-weight: 600;
                transition: background 0.2s;
            }
            .retry-btn:hover {
                background: #f59e0b;
            }
            .features {
                margin-top: 2rem;
                text-align: left;
            }
            .feature {
                display: flex;
                align-items: center;
                margin-bottom: 0.5rem;
                color: #6b7280;
                font-size: 0.9rem;
            }
            .feature::before {
                content: "✓";
                color: #10b981;
                font-weight: bold;
                margin-right: 0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="offline-container">
            <div class="icon">📱💰</div>
            <h1>Dokterku Bendahara</h1>
            <p>Anda sedang offline. Beberapa fitur mungkin tidak tersedia, tetapi data yang telah dimuat sebelumnya masih dapat diakses.</p>
            
            <button class="retry-btn" onclick="window.location.reload()">
                Coba Lagi
            </button>
            
            <div class="features">
                <div class="feature">Dashboard dapat diakses offline</div>
                <div class="feature">Data cached tersedia</div>
                <div class="feature">Sinkronisasi otomatis saat online</div>
            </div>
        </div>
        
        <script>
            // Auto-retry when online
            window.addEventListener('online', () => {
                window.location.reload();
            });
        </script>
    </body>
    </html>
  `;
}

/**
 * Background Sync for Failed Requests
 */
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    console.log('[SW] Background sync triggered');
    event.waitUntil(
      // Implement sync logic for failed validation actions
      syncFailedActions()
    );
  }
});

async function syncFailedActions() {
  // Implementation for syncing failed validation actions when back online
  console.log('[SW] Syncing failed actions...');
}

/**
 * Push Notification Handler
 */
self.addEventListener('push', event => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/pwa/icon-192x192.png',
    badge: '/pwa/badge-72x72.png',
    vibrate: [200, 100, 200],
    tag: data.tag || 'bendahara-notification',
    requireInteraction: data.requireInteraction || false,
    actions: [
      {
        action: 'view',
        title: 'Lihat',
        icon: '/pwa/action-view.png'
      },
      {
        action: 'dismiss',
        title: 'Tutup'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'view') {
    event.waitUntil(
      clients.openWindow('/bendahara')
    );
  }
});

console.log('[SW] Dokterku Bendahara Service Worker loaded successfully!');