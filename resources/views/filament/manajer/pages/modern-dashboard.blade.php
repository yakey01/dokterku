<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>🏢 Executive Manager Dashboard - Dokterku Healthcare</title>
    
    <!-- Load system fonts -->
    
    <!-- Meta Tags for PWA -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="description" content="Executive healthcare management dashboard with real-time analytics and insights">
    
    <!-- Isolated Manager Assets - No Filament Conflicts -->
    @vite(['resources/js/manager-isolated.js'])
    
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Ensure full viewport height */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        /* Use system fonts */
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Hide Filament default layout elements */
        .fi-topbar,
        .fi-sidebar,
        .fi-main-ctn {
            display: none !important;
        }
        
        /* Ensure our React app takes full space */
        .fi-main {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* Loading state */
        .dashboard-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #fafafa;
            font-family: 'Inter', sans-serif;
        }
        
        .dark .dashboard-loading {
            background: #171717;
            color: #ffffff;
        }
        
        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
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
            <div class="mt-4 w-48 mx-auto">
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full animate-pulse" style="width: 70%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- React Dashboard Root -->
    <div id="manajer-dashboard-root" class="manajer-dashboard hidden">
        <!-- React app will mount here -->
    </div>

    <!-- Authentication Data for React -->
    <script>
        window.managerAuth = {
            user: @json(auth()->user()),
            role: @json(auth()->user()?->roles?->first()?->name),
            csrfToken: '{{ csrf_token() }}',
            apiBaseUrl: '{{ url('/api/v2') }}',
            appUrl: '{{ url('/') }}',
        };
        
        window.managerConfig = {
            refreshInterval: 30000, // 30 seconds
            notificationSound: true,
            darkMode: true,
            locale: 'id',
            timezone: 'Asia/Jakarta',
        };
        
        // Show dashboard when React loads
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const loadingEl = document.getElementById('dashboard-loading');
                const dashboardEl = document.getElementById('manajer-dashboard-root');
                
                if (loadingEl) {
                    loadingEl.style.display = 'none';
                }
                if (dashboardEl) {
                    dashboardEl.classList.remove('hidden');
                }
            }, 1000);
        });
    </script>

    <!-- Error Boundary -->
    <script>
        window.addEventListener('error', (event) => {
            console.error('Dashboard Error:', event.error);
            
            // Show fallback UI
            const fallback = document.createElement('div');
            fallback.className = 'flex items-center justify-center min-h-screen bg-neutral-50 dark:bg-neutral-900';
            fallback.innerHTML = `
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-red-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-neutral-900 dark:text-white mb-2">
                        ⚠️ Dashboard Error
                    </h2>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                        Unable to load executive dashboard. Please refresh the page.
                    </p>
                    <button onclick="window.location.reload()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        🔄 Refresh Page
                    </button>
                </div>
            `;
            
            document.body.innerHTML = '';
            document.body.appendChild(fallback);
        });
    </script>

    <!-- Performance Monitoring -->
    <script>
        // Simple performance monitoring
        window.addEventListener('load', () => {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Dashboard Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
            
            // Report to analytics if needed
            if (perfData.loadEventEnd - perfData.fetchStart > 3000) {
                console.warn('Dashboard loaded slowly. Consider optimization.');
            }
        });
    </script>
    
    <!-- Removed service worker to prevent 404 errors -->
</body>
</html>