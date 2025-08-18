// ============================================
// MANAGER DASHBOARD - ISOLATED ENTRY POINT
// No Filament, No Alpine, No Livewire
// ============================================

// Import only manager-specific CSS and React app
import '../css/manajer-white-smoke-ui.css';

// Import and mount React app
import('./manajer-dashboard.tsx').then((module) => {
  // React app will self-mount when imported
  console.log('✅ Manager Dashboard loaded successfully');
}).catch((error) => {
  console.error('❌ Failed to load Manager Dashboard:', error);
  
  // Show error fallback
  document.body.innerHTML = `
    <div class="flex items-center justify-center min-h-screen bg-neutral-50 dark:bg-neutral-900">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-red-500 rounded-xl flex items-center justify-center">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-neutral-900 dark:text-white mb-2">
          ⚠️ Dashboard Load Error
        </h2>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
          Unable to load executive dashboard. Please refresh the page.
        </p>
        <button onclick="window.location.reload()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
          🔄 Refresh Page
        </button>
      </div>
    </div>
  `;
});

// Prevent any Alpine/Livewire from loading
window.deferLoadingAlpine = () => {};
window.Alpine = undefined;
window.Livewire = undefined;

// Add Alpine/Livewire conflict detection
if (window.Alpine || window.Livewire) {
  console.warn('⚠️  Alpine/Livewire detected - may cause conflicts');
}

// Setup global error handling
window.addEventListener('error', (event) => {
  if (event.error.message.includes('Alpine') || event.error.message.includes('Livewire')) {
    console.log('🛡️  Blocked Alpine/Livewire conflict error:', event.error.message);
    event.preventDefault();
    return false;
  }
});

// Setup unhandled promise rejection handling
window.addEventListener('unhandledrejection', (event) => {
  if (event.reason && (
    event.reason.message?.includes('Alpine') ||
    event.reason.message?.includes('Livewire') ||
    event.reason.message?.includes('$persist')
  )) {
    console.log('🛡️  Blocked Alpine/Livewire promise rejection:', event.reason);
    event.preventDefault();
    return false;
  }
});

console.log('🏢 Manager Dashboard Isolated Entry Point Initialized');
console.log('🛡️  Alpine/Livewire conflicts prevention active');