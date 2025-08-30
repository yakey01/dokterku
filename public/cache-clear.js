// Cache clearing script to remove dummy leaderboard data
console.log('🧹 Clearing all dashboard and leaderboard caches...');

// Clear localStorage
const localStorageKeys = Object.keys(localStorage);
const clearedLocalKeys = [];
localStorageKeys.forEach(key => {
  if (key.includes('dashboard') || key.includes('leaderboard') || key.includes('dokterku') || key.includes('attendance')) {
    localStorage.removeItem(key);
    clearedLocalKeys.push(key);
  }
});

// Clear sessionStorage
const sessionStorageKeys = Object.keys(sessionStorage);
const clearedSessionKeys = [];
sessionStorageKeys.forEach(key => {
  if (key.includes('dashboard') || key.includes('leaderboard') || key.includes('dokterku') || key.includes('attendance')) {
    sessionStorage.removeItem(key);
    clearedSessionKeys.push(key);
  }
});

// Clear Service Worker caches if available
if ('caches' in window) {
  caches.keys().then(cacheNames => {
    return Promise.all(
      cacheNames.map(cacheName => {
        console.log('🗑️ Deleting cache:', cacheName);
        return caches.delete(cacheName);
      })
    );
  }).then(() => {
    console.log('✅ All caches cleared successfully');
  });
}

console.log('✅ Cleared localStorage keys:', clearedLocalKeys);
console.log('✅ Cleared sessionStorage keys:', clearedSessionKeys);
console.log('🔄 Dummy data should be gone after page refresh!');

// Force refresh the page after clearing caches
setTimeout(() => {
  console.log('🔄 Force refreshing page...');
  window.location.reload(true);
}, 1000);