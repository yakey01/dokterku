// DOKTERKU CACHE BUSTER - Forces complete cache clear
(function() {
    'use strict';
    
    console.log('🧹 DOKTERKU: Starting aggressive cache clearing...');
    
    // Clear all localStorage
    try {
        localStorage.clear();
        sessionStorage.clear();
        console.log('✅ Storage cleared');
    } catch(e) {
        console.log('⚠️ Storage clear failed:', e);
    }
    
    // Clear service workers
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.unregister();
            }
            console.log('✅ Service workers cleared');
        });
    }
    
    // Force reload without cache
    window.location.reload(true);
})();