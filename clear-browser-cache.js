// Clear all browser cache for Dokterku dashboard
console.log('🧹 Clearing Dokterku Dashboard Cache...');

// Clear localStorage
localStorage.clear();
console.log('✅ LocalStorage cleared');

// Clear sessionStorage  
sessionStorage.clear();
console.log('✅ SessionStorage cleared');

// Clear all cookies for this domain
document.cookie.split(";").forEach(function(c) { 
    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
});
console.log('✅ Cookies cleared');

// Force hard refresh
console.log('🔄 Forcing hard refresh...');
location.reload(true);