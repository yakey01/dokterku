<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Force authentication to true for testing -->
    <meta name="user-authenticated" content="true">
    <meta name="user-data" content='{"id":1,"name":"Dr. Test User","email":"test@dokterku.com","role":"dokter"}'>
    <meta name="api-token" content="test-token-123">
    <meta name="user-id" content="1">
    <meta name="user-name" content="Dr. Test User">
    
    <title>KLINIK DOKTERKU - Test</title>
    
    @vite(['resources/js/dokter-mobile-app.tsx'])
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
        }
        
        #dokter-app {
            min-height: 100vh;
            width: 100%;
        }
        
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading State -->
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- React App Container -->
    <div id="dokter-app"></div>
    
    <script>
        // Hide loading screen once app is ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'none';
                }
            }, 1000);
        });
    </script>
</body>
</html>