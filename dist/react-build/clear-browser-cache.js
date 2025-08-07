/**
 * Browser cache clearing script
 * Forces browsers to reload fresh assets
 */

(function() {
  // Clear all cached data
  if ('caches' in window) {
    caches.keys().then(function(names) {
      names.forEach(function(name) {
        caches.delete(name);
      });
    });
  }
  
  // Force reload page without cache
  if (window.location.search.indexOf('nocache') === -1) {
    const separator = window.location.search ? '&' : '?';
    window.location.href = window.location.href + separator + 'nocache=' + Date.now();
  }
  
  // Clear localStorage and sessionStorage
  if (typeof(Storage) !== "undefined") {
    localStorage.clear();
    sessionStorage.clear();
  }
  
  console.log('✅ Cache cleared, fresh assets loaded');
})();