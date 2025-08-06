<?php
// Simple test to verify dokter component is working

echo "<h1>DOKTER COMPONENT TEST PAGE</h1>";
echo "<p>Testing if JadwalJaga component loads properly</p>";

// Include the dokter app view directly
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="test-token">
    <meta name="user-authenticated" content="true">
    <meta name="user-data" content='{"id":13,"name":"Dr. Yaya Test","email":"test@dokterku.com","role":"dokter"}'>
    <meta name="api-token" content="test-token-123">
    
    <title>DOKTER COMPONENT TEST</title>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8fafc;
        }
        
        #dokter-app {
            min-height: 100vh;
            width: 100%;
        }
    </style>
</head>
<body>
    <div id="dokter-app">
        <div style="padding: 20px; text-align: center;">
            <h2>Loading Dokter Mobile App...</h2>
            <p>If you see this message, the React component is not loading properly.</p>
        </div>
    </div>

    <!-- Load the built Vite asset -->
    <script src="/build/assets/dokter-mobile-app-PlseburA.js"></script>
</body>
</html>