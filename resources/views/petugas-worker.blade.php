<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petugas Worker Dashboard - Dokterku System</title>
    <meta name="description" content="Standalone Petugas dashboard for healthcare management system">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Preload critical fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Chart.js for dashboard charts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <!-- React build assets -->
    <script type="module" src="/react-build/user.js"></script>
    
    <style>
        /* Critical CSS for initial load */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: #f8fafc;
            overflow-x: hidden;
        }
        
        #petugas-worker-root {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #f59e0b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            margin-top: 16px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- React Mount Point -->
    <div id="petugas-worker-root">
        <!-- Loading State -->
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading Petugas Dashboard...</div>
        </div>
    </div>

    <!-- Fallback for no JavaScript -->
    <noscript>
        <div style="display: flex; align-items: center; justify-content: center; height: 100vh; flex-direction: column; font-family: Inter, sans-serif;">
            <h2 style="color: #1e293b; margin-bottom: 16px;">JavaScript Required</h2>
            <p style="color: #64748b; text-align: center; max-width: 400px;">
                This Petugas dashboard requires JavaScript to function properly. 
                Please enable JavaScript in your browser and reload the page.
            </p>
        </div>
    </noscript>

    <!-- Initialize React App -->
    <script type="module">
        // Ensure DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Additional initialization if needed
            console.log('Petugas Worker Dashboard initializing...');
        });
        
        // Global error handler for React errors
        window.addEventListener('error', function(event) {
            console.error('Dashboard Error:', event.error || event.message || 'Unknown error');
            const root = document.getElementById('petugas-worker-root');
            if (root && root.innerHTML.includes('loading-container')) {
                root.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100vh; flex-direction: column; font-family: Inter, sans-serif;">
                        <h2 style="color: #dc2626; margin-bottom: 16px;">Dashboard Load Error</h2>
                        <p style="color: #64748b; text-align: center; max-width: 400px; margin-bottom: 16px;">
                            There was an error loading the dashboard. Please refresh the page to try again.
                        </p>
                        <button onclick="window.location.reload()" style="
                            background: #f59e0b; 
                            color: white; 
                            border: none; 
                            padding: 8px 16px; 
                            border-radius: 6px; 
                            cursor: pointer;
                            font-family: inherit;
                        ">
                            Refresh Page
                        </button>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>