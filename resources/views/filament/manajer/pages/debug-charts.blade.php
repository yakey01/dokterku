<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Debug Information Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4">📊 Chart Debug Information</h2>
            
            <!-- ApexCharts Status -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">1. ApexCharts Library Status:</h3>
                <div id="apexcharts-status" class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                    <span class="text-yellow-600">Checking...</span>
                </div>
            </div>

            <!-- Data Status -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">2. Data Availability:</h3>
                <div id="data-status" class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                    <span class="text-yellow-600">Checking...</span>
                </div>
            </div>

            <!-- Console Errors -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">3. Console Errors:</h3>
                <div id="console-errors" class="p-3 bg-gray-100 dark:bg-gray-700 rounded max-h-40 overflow-y-auto">
                    <span class="text-yellow-600">Monitoring...</span>
                </div>
            </div>

            <!-- Chart Container Status -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">4. Chart Container Status:</h3>
                <div id="container-status" class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                    <span class="text-yellow-600">Checking...</span>
                </div>
            </div>
        </div>

        <!-- Test Chart 1: Simple Line Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Test Chart 1: Simple Line Chart</h3>
            <div id="test-chart-1" style="height: 350px;"></div>
            <div id="test-chart-1-status" class="mt-2 text-sm"></div>
        </div>

        <!-- Test Chart 2: Simple Bar Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Test Chart 2: Simple Bar Chart</h3>
            <div id="test-chart-2" style="height: 350px;"></div>
            <div id="test-chart-2-status" class="mt-2 text-sm"></div>
        </div>

        <!-- Test Chart 3: With Real Data -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Test Chart 3: With Real Data</h3>
            <div id="test-chart-3" style="height: 350px;"></div>
            <div id="test-chart-3-status" class="mt-2 text-sm"></div>
            <pre id="chart-data" class="mt-2 text-xs bg-gray-100 dark:bg-gray-700 p-2 rounded overflow-x-auto"></pre>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Debug Actions</h3>
            <div class="space-x-2">
                <button onclick="loadApexChartsManually()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Load ApexCharts Manually
                </button>
                <button onclick="testChartCreation()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Test Chart Creation
                </button>
                <button onclick="checkDependencies()" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    Check Dependencies
                </button>
                <button onclick="clearAndRetry()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Clear & Retry
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- Try multiple CDN sources -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
    <script src="https://unpkg.com/apexcharts@latest/dist/apexcharts.min.js"></script>
    
    <script>
        // Error logging
        const errors = [];
        window.addEventListener('error', function(e) {
            errors.push({
                message: e.message,
                source: e.filename,
                line: e.lineno,
                col: e.colno,
                error: e.error
            });
            updateErrorDisplay();
        });

        function updateErrorDisplay() {
            const errorDiv = document.getElementById('console-errors');
            if (errors.length === 0) {
                errorDiv.innerHTML = '<span class="text-green-600">✅ No errors detected</span>';
            } else {
                errorDiv.innerHTML = errors.map(e => 
                    `<div class="text-red-600 mb-2">❌ ${e.message} (${e.source}:${e.line})</div>`
                ).join('');
            }
        }

        // Debug functions
        function checkApexCharts() {
            const statusDiv = document.getElementById('apexcharts-status');
            if (typeof ApexCharts !== 'undefined') {
                statusDiv.innerHTML = '<span class="text-green-600">✅ ApexCharts loaded successfully (Version: ' + (ApexCharts.version || 'Unknown') + ')</span>';
                return true;
            } else {
                statusDiv.innerHTML = '<span class="text-red-600">❌ ApexCharts not loaded</span>';
                return false;
            }
        }

        function checkData() {
            const statusDiv = document.getElementById('data-status');
            try {
                // Test data
                const testData = @json($this->getFinancialTrends() ?? ['months' => [], 'revenue' => []]);
                
                if (testData && testData.months && testData.months.length > 0) {
                    statusDiv.innerHTML = `<span class="text-green-600">✅ Data available (${testData.months.length} months)</span>`;
                    document.getElementById('chart-data').textContent = JSON.stringify(testData, null, 2);
                    return testData;
                } else {
                    statusDiv.innerHTML = '<span class="text-yellow-600">⚠️ No data available</span>';
                    return null;
                }
            } catch (e) {
                statusDiv.innerHTML = `<span class="text-red-600">❌ Data error: ${e.message}</span>`;
                return null;
            }
        }

        function checkContainers() {
            const statusDiv = document.getElementById('container-status');
            const containers = ['test-chart-1', 'test-chart-2', 'test-chart-3'];
            let allFound = true;
            let status = [];
            
            containers.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    const rect = el.getBoundingClientRect();
                    status.push(`✅ ${id}: Found (${rect.width}x${rect.height}px)`);
                } else {
                    status.push(`❌ ${id}: Not found`);
                    allFound = false;
                }
            });
            
            statusDiv.innerHTML = status.join('<br>');
            return allFound;
        }

        // Test chart creation
        function createTestChart1() {
            const statusDiv = document.getElementById('test-chart-1-status');
            
            try {
                if (typeof ApexCharts === 'undefined') {
                    throw new Error('ApexCharts not loaded');
                }

                const options = {
                    series: [{
                        name: 'Test Data',
                        data: [30, 40, 35, 50, 49, 60, 70, 91, 125]
                    }],
                    chart: {
                        type: 'line',
                        height: 350
                    },
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
                    },
                    title: {
                        text: 'Simple Line Chart Test',
                        align: 'center'
                    }
                };

                const chart = new ApexCharts(document.querySelector("#test-chart-1"), options);
                chart.render();
                
                statusDiv.innerHTML = '<span class="text-green-600">✅ Chart created successfully</span>';
                return true;
            } catch (e) {
                statusDiv.innerHTML = `<span class="text-red-600">❌ Error: ${e.message}</span>`;
                console.error('Chart 1 error:', e);
                return false;
            }
        }

        function createTestChart2() {
            const statusDiv = document.getElementById('test-chart-2-status');
            
            try {
                if (typeof ApexCharts === 'undefined') {
                    throw new Error('ApexCharts not loaded');
                }

                const options = {
                    series: [{
                        data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
                    }],
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    },
                    title: {
                        text: 'Simple Bar Chart Test',
                        align: 'center'
                    },
                    colors: ['#00E396']
                };

                const chart = new ApexCharts(document.querySelector("#test-chart-2"), options);
                chart.render();
                
                statusDiv.innerHTML = '<span class="text-green-600">✅ Chart created successfully</span>';
                return true;
            } catch (e) {
                statusDiv.innerHTML = `<span class="text-red-600">❌ Error: ${e.message}</span>`;
                console.error('Chart 2 error:', e);
                return false;
            }
        }

        function createTestChart3() {
            const statusDiv = document.getElementById('test-chart-3-status');
            
            try {
                if (typeof ApexCharts === 'undefined') {
                    throw new Error('ApexCharts not loaded');
                }

                const data = checkData();
                if (!data) {
                    throw new Error('No data available');
                }

                const options = {
                    series: [{
                        name: 'Revenue',
                        data: data.revenue || [0, 0, 0, 0, 0, 0]
                    }],
                    chart: {
                        type: 'area',
                        height: 350
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    xaxis: {
                        categories: data.months || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
                    },
                    title: {
                        text: 'Revenue Chart with Real Data',
                        align: 'center'
                    },
                    colors: ['#008FFB']
                };

                const chart = new ApexCharts(document.querySelector("#test-chart-3"), options);
                chart.render();
                
                statusDiv.innerHTML = '<span class="text-green-600">✅ Chart created with real data</span>';
                return true;
            } catch (e) {
                statusDiv.innerHTML = `<span class="text-red-600">❌ Error: ${e.message}</span>`;
                console.error('Chart 3 error:', e);
                return false;
            }
        }

        // Manual loading function
        function loadApexChartsManually() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js';
            script.onload = function() {
                console.log('ApexCharts loaded manually');
                checkApexCharts();
                testChartCreation();
            };
            script.onerror = function() {
                console.error('Failed to load ApexCharts');
                alert('Failed to load ApexCharts library');
            };
            document.head.appendChild(script);
        }

        function testChartCreation() {
            createTestChart1();
            createTestChart2();
            createTestChart3();
        }

        function checkDependencies() {
            console.log('Checking dependencies...');
            checkApexCharts();
            checkData();
            checkContainers();
            updateErrorDisplay();
        }

        function clearAndRetry() {
            // Clear all chart containers
            ['test-chart-1', 'test-chart-2', 'test-chart-3'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = '';
            });
            
            // Retry everything
            setTimeout(() => {
                checkDependencies();
                testChartCreation();
            }, 500);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing debug...');
            
            // Initial checks
            setTimeout(() => {
                checkDependencies();
                
                // Try to create charts if ApexCharts is loaded
                if (typeof ApexCharts !== 'undefined') {
                    testChartCreation();
                } else {
                    console.error('ApexCharts not available on DOMContentLoaded');
                    // Try loading manually
                    loadApexChartsManually();
                }
            }, 1000);
        });

        // Also try on window load
        window.addEventListener('load', function() {
            console.log('Window loaded');
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts still not available on window load');
            }
        });
    </script>
    @endpush
</x-filament-panels::page>