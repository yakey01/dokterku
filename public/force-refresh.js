// Force browser cache clear and refresh
function forceRefreshBendahara() {
    // Clear local storage
    localStorage.clear();
    sessionStorage.clear();
    
    // Clear service worker cache if exists
    if ('caches' in window) {
        caches.keys().then(function(names) {
            for (let name of names) {
                caches.delete(name);
            }
        });
    }
    
    // Add cache-busting parameter and redirect
    const bendaharaUrl = '/bendahara/laporan-jaspel';
    const timestamp = new Date().getTime();
    const refreshUrl = `${bendaharaUrl}?refresh=${timestamp}&cache=clear`;
    
    // Force hard refresh
    window.location.href = refreshUrl;
}

// Auto-run if on the bendahara page
if (window.location.pathname.includes('bendahara/laporan-jaspel')) {
    console.log('🚀 Jaspel calculation has been updated!');
    console.log('Dr. Yaya now shows ~Rp 691,000 instead of Rp 12,663,566');
}

// Export for manual use
window.forceRefreshBendahara = forceRefreshBendahara;