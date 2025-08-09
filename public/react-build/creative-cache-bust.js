// 🎨 CREATIVE CACHE BUSTING SCRIPT
// This script uses creative methods to force cache refresh

(function() {
    'use strict';
    
    console.log('🎨 CREATIVE CACHE BUSTING INITIATED');
    console.log('🕐 Timestamp:', Date.now());
    console.log('🆔 Creative ID:', Math.random().toString(36).substr(2, 9));
    
    // Creative cache clearing with visual feedback
    const creativeCacheClear = async () => {
        try {
            // Clear all caches with visual feedback
            if ('caches' in window) {
                const cacheNames = await caches.keys();
                console.log('🎨 Clearing caches with creative method:', cacheNames);
                
                for (const name of cacheNames) {
                    await caches.delete(name);
                    console.log('🗑️ Deleted cache:', name);
                }
                console.log('✅ All caches cleared creatively');
            }
            
            // Clear all storage with creative method
            const storageTypes = ['localStorage', 'sessionStorage'];
            for (const storageType of storageTypes) {
                try {
                    window[storageType].clear();
                    console.log('🗑️ Cleared', storageType);
                } catch (e) {
                    console.warn('⚠️ Failed to clear', storageType, e);
                }
            }
            
            // Clear IndexedDB if available
            if ('indexedDB' in window) {
                try {
                    const databases = await indexedDB.databases();
                    for (const db of databases) {
                        if (db.name) {
                            await indexedDB.deleteDatabase(db.name);
                            console.log('🗑️ Deleted IndexedDB:', db.name);
                        }
                    }
                } catch (e) {
                    console.warn('⚠️ IndexedDB clearing failed:', e);
                }
            }
            
            console.log('🎨 Creative cache clearing completed');
            return true;
        } catch (error) {
            console.error('❌ Creative cache clearing failed:', error);
            return false;
        }
    };
    
    // Creative URL generation
    const generateCreativeUrl = (baseUrl) => {
        const timestamp = Date.now();
        const randomId = Math.random().toString(36).substr(2, 9);
        const creativeId = btoa(`creative-${timestamp}-${randomId}`).replace(/[^a-zA-Z0-9]/g, '');
        
        const separator = baseUrl.includes('?') ? '&' : '?';
        const creativeUrl = `${baseUrl}${separator}creative-bust=${creativeId}&timestamp=${timestamp}&random=${randomId}&v=2.0&mode=creative`;
        
        console.log('🎨 Generated creative URL:', creativeUrl);
        return creativeUrl;
    };
    
    // Creative reload with animation
    const creativeReload = async () => {
        console.log('🎨 Starting creative reload...');
        
        // Clear caches first
        const cacheCleared = await creativeCacheClear();
        
        if (cacheCleared) {
            // Generate creative URL
            const currentUrl = window.location.href;
            const creativeUrl = generateCreativeUrl(currentUrl);
            
            // Show creative loading message
            const loadingDiv = document.createElement('div');
            loadingDiv.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab); background-size: 400% 400%; animation: gradientShift 2s ease infinite; z-index: 9999; display: flex; align-items: center; justify-content: center; color: white; font-family: Arial, sans-serif;">
                    <div style="text-align: center;">
                        <div style="width: 50px; height: 50px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s ease-in-out infinite; margin: 0 auto 20px;"></div>
                        <h2 style="margin: 0 0 10px 0;">🎨 Creative Cache Busting</h2>
                        <p style="margin: 0;">Reloading with creative cache busting...</p>
                        <p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.8;">ID: ${Math.random().toString(36).substr(2, 9)}</p>
                    </div>
                </div>
                <style>
                    @keyframes gradientShift {
                        0% { background-position: 0% 50%; }
                        50% { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `;
            document.body.appendChild(loadingDiv);
            
            // Reload after a short delay to show the animation
            setTimeout(() => {
                window.location.href = creativeUrl;
            }, 2000);
        } else {
            console.error('❌ Creative reload failed - cache clearing unsuccessful');
            // Fallback to simple reload
            window.location.reload(true);
        }
    };
    
    // Execute creative cache busting
    creativeReload();
    
})();
