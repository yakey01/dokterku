<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NEW Dashboard Paramedis - {{ $user->name }}</title>
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .loading {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .error {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #dc2626;
        }
        
        .success {
            background-color: #d1fae5;
            border-color: #a7f3d0;
            color: #059669;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">NEW Dashboard Paramedis</h1>
            <p class="text-gray-600">Selamat datang, {{ $user->name }}</p>
            <div class="mt-2">
                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded">
                    User ID: {{ $user->id }}
                </span>
                <span class="bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded ml-2">
                    Role: {{ $user->role?->name ?? 'N/A' }}
                </span>
            </div>
        </div>

        <!-- Jaspel Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">💰 Jaspel Summary</h2>
                <button id="refreshBtn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    🔄 Refresh
                </button>
            </div>
            
            <!-- Loading State -->
            <div id="loadingState" class="loading">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-200 h-32 rounded-lg"></div>
                    <div class="bg-gray-200 h-32 rounded-lg"></div>
                    <div class="bg-gray-200 h-32 rounded-lg"></div>
                </div>
                <p class="text-center text-gray-500 mt-4">Loading data...</p>
            </div>

            <!-- Error State -->
            <div id="errorState" class="hidden error border rounded-lg p-4">
                <p id="errorMessage"></p>
                <button onclick="loadDashboardData()" class="mt-2 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Try Again
                </button>
            </div>

            <!-- Success State -->
            <div id="successState" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Jaspel Bulan Ini -->
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Jaspel Bulan Ini</p>
                                <p class="text-sm text-blue-100">Pendapatan Layanan Medis</p>
                                <p id="currentMonthAmount" class="text-3xl font-bold mt-2">Rp 0</p>
                            </div>
                            <div class="text-4xl">💰</div>
                        </div>
                    </div>

                    <!-- Jaspel Bulan Lalu -->
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-100 text-sm font-medium">Bulan Lalu</p>
                                <p id="lastMonthAmount" class="text-2xl font-bold mt-2">Rp 0</p>
                            </div>
                            <div class="text-4xl">📊</div>
                        </div>
                    </div>

                    <!-- Growth -->
                    <div id="growthCard" class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Growth</p>
                                <p id="growthPercentage" class="text-2xl font-bold mt-2">+0%</p>
                            </div>
                            <div id="growthIcon" class="text-4xl">📈</div>
                        </div>
                    </div>
                </div>

                <!-- Debug Info -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <h3 class="font-bold text-gray-700 mb-2">🔍 Debug Info</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Endpoint:</strong> <span id="debugEndpoint">Loading...</span></p>
                        <p><strong>Response Time:</strong> <span id="debugResponseTime">-</span></p>
                        <p><strong>Last Updated:</strong> <span id="debugTimestamp">-</span></p>
                        <p><strong>Controller:</strong> <span id="debugController">-</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raw Data -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">📋 Raw Response Data</h3>
            <pre id="rawData" class="bg-gray-100 rounded p-4 text-sm overflow-auto max-h-64">Loading...</pre>
        </div>
    </div>

    <script>
        let currentUserToken = null;

        // Get user token
        async function getUserToken() {
            if (currentUserToken) return currentUserToken;
            
            try {
                const response = await fetch('/sanctum/csrf-cookie');
                const tokenResponse = await fetch('/api/user', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (tokenResponse.ok) {
                    // User is authenticated via session
                    return 'session';
                }
            } catch (error) {
                console.error('Error getting token:', error);
            }
            return null;
        }

        // Load dashboard data
        async function loadDashboardData() {
            const startTime = Date.now();
            
            // Show loading state
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('successState').classList.add('hidden');

            try {
                const token = await getUserToken();
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                
                if (token && token !== 'session') {
                    headers['Authorization'] = `Bearer ${token}`;
                }

                const response = await fetch('/api/new-paramedis/dashboard', {
                    method: 'GET',
                    headers: headers,
                    credentials: 'include'
                });

                const data = await response.json();
                const endTime = Date.now();
                const responseTime = endTime - startTime;

                // Update raw data
                document.getElementById('rawData').textContent = JSON.stringify(data, null, 2);

                if (data.success && data.data) {
                    // Update UI with data
                    document.getElementById('currentMonthAmount').textContent = data.data.jaspel_bulan_ini.formatted;
                    document.getElementById('lastMonthAmount').textContent = data.data.jaspel_bulan_lalu.formatted;
                    document.getElementById('growthPercentage').textContent = data.data.growth.formatted;

                    // Update growth card color
                    const growthCard = document.getElementById('growthCard');
                    const growthIcon = document.getElementById('growthIcon');
                    
                    if (data.data.growth.direction === 'up') {
                        growthCard.className = 'bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-6';
                        growthIcon.textContent = '📈';
                    } else {
                        growthCard.className = 'bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg p-6';
                        growthIcon.textContent = '📉';
                    }

                    // Update debug info
                    document.getElementById('debugEndpoint').textContent = '/api/new-paramedis/dashboard';
                    document.getElementById('debugResponseTime').textContent = responseTime + 'ms';
                    document.getElementById('debugTimestamp').textContent = new Date().toLocaleString();
                    document.getElementById('debugController').textContent = data.meta?.controller || 'NewParamedisDashboardController';

                    // Show success state
                    document.getElementById('loadingState').classList.add('hidden');
                    document.getElementById('successState').classList.remove('hidden');
                } else {
                    throw new Error(data.message || 'Failed to load dashboard data');
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                
                // Show error state
                document.getElementById('loadingState').classList.add('hidden');
                document.getElementById('errorState').classList.remove('hidden');
                document.getElementById('errorMessage').textContent = 'Error: ' + error.message;
                document.getElementById('rawData').textContent = 'Error: ' + error.message;
            }
        }

        // Event listeners
        document.getElementById('refreshBtn').addEventListener('click', loadDashboardData);

        // Auto-refresh every 30 seconds
        setInterval(loadDashboardData, 30000);

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>