<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEW Dashboard Links - Dokterku</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-6">🆕 NEW Dashboard System</h1>
            <p class="text-gray-600 mb-8">Akses dashboard baru yang sudah diperbaiki untuk mengatasi masalah caching dan data yang salah.</p>
            
            <!-- Dashboard Links -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- NEW Dashboard Card -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg p-6">
                    <h2 class="text-2xl font-bold mb-2">🎯 NEW Dashboard Card</h2>
                    <p class="text-blue-100 mb-4">Dashboard baru dengan data real-time yang benar</p>
                    <a href="/paramedis/new-dashboard-card" class="bg-white text-blue-600 px-4 py-2 rounded font-semibold hover:bg-gray-100 transition-colors">
                        🚀 Buka Dashboard →
                    </a>
                </div>

                <!-- API Endpoint -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-6">
                    <h2 class="text-2xl font-bold mb-2">📡 NEW API Endpoint</h2>
                    <p class="text-green-100 mb-4">Test endpoint API secara langsung</p>
                    <button onclick="testAPI()" class="bg-white text-green-600 px-4 py-2 rounded font-semibold hover:bg-gray-100 transition-colors">
                        🧪 Test API →
                    </button>
                </div>
            </div>

            <!-- Expected Results -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">✅ Expected Results untuk Bita (User ID: 20)</h3>
                <ul class="text-yellow-700 space-y-1">
                    <li><strong>Jaspel Bulan Ini:</strong> Rp 21.000 (bukan Rp 71.500)</li>
                    <li><strong>Bulan Lalu:</strong> Rp 0</li>
                    <li><strong>Growth:</strong> +100%</li>
                    <li><strong>Controller:</strong> NewParamedisDashboardController</li>
                </ul>
            </div>

            <!-- API Test Results -->
            <div id="apiResults" class="hidden bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">📊 API Test Results</h3>
                <pre id="apiData" class="text-sm text-gray-600 overflow-auto max-h-64"></pre>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">📋 Instructions</h3>
                <ol class="text-blue-700 space-y-2 list-decimal list-inside">
                    <li>Klik <strong>"🚀 Buka Dashboard"</strong> untuk melihat dashboard card baru</li>
                    <li>Dashboard akan otomatis load data dari endpoint <code>/api/new-paramedis/dashboard</code></li>
                    <li>Data akan refresh otomatis setiap 30 detik</li>
                    <li>Jika masih ada masalah, klik tombol "🔄 Refresh" di dashboard</li>
                    <li>Check "Debug Info" di dashboard untuk memastikan menggunakan controller yang benar</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        async function testAPI() {
            const resultsDiv = document.getElementById('apiResults');
            const dataDiv = document.getElementById('apiData');
            
            resultsDiv.classList.remove('hidden');
            dataDiv.textContent = 'Loading...';
            
            try {
                const response = await fetch('/api/new-paramedis/dashboard', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });
                
                const data = await response.json();
                dataDiv.textContent = JSON.stringify(data, null, 2);
                
                // Highlight key values
                if (data.success && data.data) {
                    const currentAmount = data.data.jaspel_bulan_ini.nominal;
                    if (currentAmount === 21000) {
                        dataDiv.style.backgroundColor = '#d1fae5'; // Green background
                        dataDiv.style.borderColor = '#a7f3d0';
                    } else if (currentAmount === 71500) {
                        dataDiv.style.backgroundColor = '#fee2e2'; // Red background  
                        dataDiv.style.borderColor = '#fecaca';
                    }
                }
            } catch (error) {
                dataDiv.textContent = 'Error: ' + error.message;
                dataDiv.style.backgroundColor = '#fee2e2';
            }
        }
    </script>
</body>
</html>