<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Klinik Dokterku</title>
    {{-- Use Vite directive for proper asset loading --}}
    @vite(['resources/css/app.css', 'resources/js/welcome-login-app.tsx'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        /* Ensure full height and prevent flash of unstyled content */
        html, body, #welcome-login-root {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        /* Loading spinner while React loads */
        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        
        /* Hide loading spinner when React is ready */
        .react-ready .loading-spinner {
            display: none;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900">
    <!-- Loading spinner -->
    <div class="loading-spinner">
        <div class="w-12 h-12 border-4 border-cyan-400 border-t-transparent rounded-full animate-spin"></div>
    </div>
    
    <!-- React mount point -->
    <div id="welcome-login-root"></div>
    
    <!-- Animation system - triggers only on login success -->
    <script>
        // Debug mode for development
        window.DEBUG_ANIMATION = true;
        
        // Mark React as ready when DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.body.classList.add('react-ready');
            }, 500);
        });
    </script>
</body>
</html>