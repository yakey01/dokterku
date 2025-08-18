<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>🏢 Executive Manager Dashboard - Dokterku Healthcare</title>
    
    <!-- Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="description" content="Executive healthcare management dashboard with real-time analytics and insights">
    
    <!-- Isolated Manager Assets - No Filament Dependencies -->
    @vite(['resources/js/manager-isolated.js'])
    
    <!-- Prevent Filament Alpine/Livewire Loading -->
    <script>
        // Prevent default Alpine/Livewire from loading
        window.deferLoadingAlpine = function () {};
        window.Alpine = null;
        window.Livewire = null;
    </script>
    
    <!-- Chart.js loaded via npm (no CDN) -->
    
    <!-- Custom Styles -->
    <style>
        /* Ensure full viewport */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Loading state */
        .dashboard-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #fafafa;
        }
        
        .dark .dashboard-loading {
            background: #171717;
            color: #ffffff;
        }
    </style>
</head>

<body class="antialiased">
    <!-- Loading State -->
    <div id="dashboard-loading" class="dashboard-loading">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg animate-pulse">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-neutral-900 dark:text-white mb-2">
                🏢 Loading Executive Dashboard
            </h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Initializing real-time healthcare analytics...
            </p>
        </div>
    </div>

    <!-- React Dashboard Root -->
    <div id="manajer-dashboard-root" class="manajer-dashboard" style="min-height: 100vh;">
        <!-- React app will mount here -->
    </div>

    <!-- Global Configuration -->
    <script>
        window.managerAuth = {
            user: @json(auth()->user()),
            role: @json(auth()->user()?->roles?->first()?->name),
            csrfToken: '{{ csrf_token() }}',
            apiBaseUrl: '{{ url('/api/v2') }}',
            appUrl: '{{ url('/') }}',
        };
        
        window.managerConfig = {
            refreshInterval: 30000,
            notificationSound: true,
            darkMode: true,
            locale: 'id',
            timezone: 'Asia/Jakarta',
        };
        
        // Simple loading management
        setTimeout(() => {
            const loadingEl = document.getElementById('dashboard-loading');
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
        }, 2000); // Hide loading after 2 seconds regardless
    </script>

    <!-- Simple Error Handler -->
    <script>
        window.addEventListener('error', (event) => {
            console.error('Dashboard Error:', event.error);
            // Continue gracefully without breaking
        });
        
        // Prevent unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            console.warn('Unhandled promise rejection:', event.reason);
            event.preventDefault();
        });
    </script>
</body>
</html>