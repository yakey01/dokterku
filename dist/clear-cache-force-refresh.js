// Force clear ALL caches and refresh
console.log('🧹 FORCE CLEARING ALL CACHES...');

// 1. Clear all localStorage
try {
    localStorage.clear();
    console.log('✅ localStorage cleared');
} catch(e) {
    console.log('❌ localStorage clear failed:', e);
}

// 2. Clear all sessionStorage  
try {
    sessionStorage.clear();
    console.log('✅ sessionStorage cleared');
} catch(e) {
    console.log('❌ sessionStorage clear failed:', e);
}

// 3. Clear all caches via Cache API
if ('caches' in window) {
    caches.keys().then(cacheNames => {
        return Promise.all(
            cacheNames.map(cacheName => {
                console.log('🗑️ Deleting cache:', cacheName);
                return caches.delete(cacheName);
            })
        );
    }).then(() => {
        console.log('✅ All Cache API caches cleared');
    }).catch(e => {
        console.log('❌ Cache API clear failed:', e);
    });
}

// 4. Unregister all service workers
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
        registrations.forEach(registration => {
            console.log('🗑️ Unregistering service worker:', registration);
            registration.unregister();
        });
        console.log('✅ All service workers unregistered');
    }).catch(e => {
        console.log('❌ Service worker unregister failed:', e);
    });
}

// 5. Clear specific app data if exists
const keysToDelete = [
    'theme',
    'jaspel_cache',
    'dashboard_cache', 
    'user_cache',
    'api_cache'
];

keysToDelete.forEach(key => {
    try {
        localStorage.removeItem(key);
        sessionStorage.removeItem(key);
        console.log('🗑️ Removed:', key);
    } catch(e) {
        console.log('❌ Failed to remove:', key, e);
    }
});

// 6. Force reload with cache bypass
console.log('🔄 FORCE RELOADING WITH CACHE BYPASS...');
setTimeout(() => {
    location.reload(true); // Force reload bypassing cache
}, 1000);