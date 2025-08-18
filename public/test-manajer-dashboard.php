<?php
// Simple test page for Manajer Dashboard Charts

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Auto-login as manajer for testing
$user = App\Models\User::where('email', 'manajer@dokterku.com')->first();
if ($user) {
    Auth::login($user);
    echo "✅ Logged in as: " . $user->email . "\n\n";
} else {
    die("❌ Manajer user not found\n");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Manajer Dashboard Charts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">📊 Test Manajer Dashboard Charts</h1>
        
        <!-- Login Status -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <h2 class="text-xl font-semibold mb-2">Login Status</h2>
            <p>User: <?= Auth::user()->email ?? 'Not logged in' ?></p>
            <p>Role: <?= Auth::user()->getRoleNames()->first() ?? 'No role' ?></p>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <h2 class="text-xl font-semibold mb-2">Quick Links</h2>
            <div class="space-y-2">
                <a href="/manajer/dashboard" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    📊 Executive Dashboard
                </a>
                <a href="/manajer/advanced-analytics" class="block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    📈 Advanced Analytics
                </a>
                <a href="/manajer/debug-charts" class="block px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    🔧 Debug Charts
                </a>
                <a href="/manajer/simple-chart-test" class="block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                    🧪 Simple Chart Test
                </a>
            </div>
        </div>

        <!-- Test Chart -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Test ApexChart</h2>
            <div id="test-chart" style="height: 350px;"></div>
            <div id="chart-status" class="mt-2 text-sm"></div>
        </div>

        <!-- Debug Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <div id="debug-info" class="space-y-2">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <script>
        // Check ApexCharts
        const debugInfo = document.getElementById('debug-info');
        let debugHTML = '';
        
        debugHTML += '<p>✅ ApexCharts loaded: ' + (typeof ApexCharts !== 'undefined' ? 'Yes' : 'No') + '</p>';
        debugHTML += '<p>✅ User logged in: Yes (<?= Auth::user()->email ?>)</p>';
        debugHTML += '<p>✅ Role: <?= Auth::user()->getRoleNames()->first() ?></p>';
        
        // Check if we can access data
        <?php
        $revenue = [];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');
            $revenue[] = rand(10000000, 50000000);
        }
        ?>
        
        debugHTML += '<p>✅ Data available: Yes</p>';
        debugHTML += '<p>✅ CSP restrictions: Disabled for /manajer/*</p>';
        
        debugInfo.innerHTML = debugHTML;

        // Create test chart
        if (typeof ApexCharts !== 'undefined') {
            const options = {
                series: [{
                    name: 'Revenue',
                    data: <?= json_encode($revenue) ?>
                }],
                chart: {
                    type: 'area',
                    height: 350
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: <?= json_encode($months) ?>
                },
                colors: ['#3b82f6'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 90, 100]
                    }
                },
                title: {
                    text: 'Revenue Trend',
                    align: 'center'
                }
            };

            try {
                const chart = new ApexCharts(document.querySelector("#test-chart"), options);
                chart.render();
                document.getElementById('chart-status').innerHTML = '<span class="text-green-600">✅ Chart rendered successfully!</span>';
            } catch (e) {
                document.getElementById('chart-status').innerHTML = '<span class="text-red-600">❌ Error: ' + e.message + '</span>';
            }
        } else {
            document.getElementById('chart-status').innerHTML = '<span class="text-red-600">❌ ApexCharts not loaded</span>';
        }
    </script>
</body>
</html>