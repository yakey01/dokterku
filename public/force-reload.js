// Force Reload Script for Dokterku Mobile App
// This script forces a complete cache refresh

(function() {
    'use strict';
    
    console.log('🔄 Dokterku Force Reload Script Starting...');
    
    // Clear all caches
    if ('caches' in window) {
        caches.keys().then(function(names) {
            console.log('🗑️ Clearing caches:', names);
            return Promise.all(names.map(function(name) {
                return caches.delete(name);
            }));
        }).then(function() {
            console.log('✅ All caches cleared');
        }).catch(function(error) {
            console.warn('⚠️ Cache clearing failed:', error);
        });
    }
    
    // Clear localStorage
    try {
        const keysToKeep = ['auth_token', 'csrf_token', 'user_preferences'];
        const keysToRemove = Object.keys(localStorage).filter(key => !keysToKeep.includes(key));
        
        keysToRemove.forEach(key => {
            localStorage.removeItem(key);
            console.log('🗑️ Removed localStorage key:', key);
        });
        
        console.log('✅ localStorage cleared (keeping auth tokens)');
    } catch (error) {
        console.warn('⚠️ localStorage clearing failed:', error);
    }
    
    // Clear sessionStorage
    try {
        sessionStorage.clear();
        console.log('✅ sessionStorage cleared');
    } catch (error) {
        console.warn('⚠️ sessionStorage clearing failed:', error);
    }
    
    // Force reload with cache busting
    const currentUrl = window.location.href;
    const separator = currentUrl.includes('?') ? '&' : '?';
    const reloadUrl = currentUrl + separator + 'force-reload=' + Date.now() + '&cache-bust=' + Math.random();
    
    console.log('🔄 Reloading with cache busting...');
    console.log('📍 New URL:', reloadUrl);
    
    // Small delay to ensure console messages are visible
    setTimeout(function() {
        window.location.href = reloadUrl;
    }, 500);
    
})();
