<?php
// Test file untuk memeriksa JadwalJaga component
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="test-token">
    <meta name="user-authenticated" content="true">
    <meta name="api-token" content="test-api-token">
    <title>Test JadwalJaga Component</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8fafc;
        }
        #test-app {
            min-height: 100vh;
            width: 100%;
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            font-size: 18px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>🧪 Test JadwalJaga Component</h1>
    <div id="test-app">
        <div class="loading">Loading JadwalJaga component...</div>
    </div>

    <script type="module">
        import { createRoot } from '/build/assets/index-DiapNgPT.js';
        
        console.log('🚀 Starting JadwalJaga component test...');
        
        // Test user data
        window.testUserData = {
            id: 13,
            name: "Dr. Yaya Mulyana, Sp.PD",
            email: "yaya@dokterku.com",
            role: "dokter",
            token: "test-api-token"
        };
        
        console.log('✅ Test setup complete');
    </script>
    
    <script>
        // Check if component files exist
        fetch('/build/assets/dokter-mobile-app-PlseburA.js')
            .then(response => {
                if (response.ok) {
                    console.log('✅ Dokter mobile app bundle found');
                } else {
                    console.error('❌ Dokter mobile app bundle not found');
                }
            })
            .catch(err => console.error('❌ Error loading bundle:', err));
    </script>
</body>
</html>