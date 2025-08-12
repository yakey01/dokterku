<x-filament-panels::page>
    <div class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto mb-4"></div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                Redirecting to Enhanced Dashboard...
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Anda akan diarahkan ke Enhanced Dashboard dengan sidebar lengkap
            </p>
            <a href="/petugas/enhanced-dashboard" 
               class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
                Buka Enhanced Dashboard
            </a>
        </div>
    </div>
    
    <script>
        // Auto-redirect after 2 seconds
        setTimeout(function() {
            window.location.href = '/petugas/enhanced-dashboard';
        }, 2000);
    </script>
</x-filament-panels::page>
