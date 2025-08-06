<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Klinik Dokterku</title>
    @vite(['resources/css/app.css', 'resources/js/welcome-login-new.tsx'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        /* Ensure full height and prevent flash of unstyled content */
        html, body, #welcome-login-new-root {
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
<body class="bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900">
    <!-- Loading spinner -->
    <div class="loading-spinner">
        <div class="w-12 h-12 border-4 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
    </div>
    
    <!-- React mount point -->
    <div id="welcome-login-new-root"></div>
    
    <!-- Initialize React app -->
    <script>
        // Mark React as ready when DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.body.classList.add('react-ready');
            }, 500);
        });
    </script>
</body>
</html>