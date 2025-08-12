<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart.js Laravel Integration Debug</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .chart-container { width: 100%; height: 400px; position: relative; margin: 20px 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .status-indicator { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .status-success { background: #28a745; color: white; }
        .status-error { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Chart.js Laravel Integration Debug</h1>
        <p><strong>Purpose:</strong> Comprehensive testing of Chart.js in Laravel environment (similar to Petugas dashboard)</p>
        
        <div class="test-section info">
            <h2>📋 System Information</h2>
            <div class="grid">
                <div>
                    <strong>Laravel Environment:</strong> {{ app()->environment() }}<br>
                    <strong>PHP Version:</strong> {{ PHP_VERSION }}<br>
                    <strong>Laravel Version:</strong> {{ app()->version() }}<br>
                    <strong>Timestamp:</strong> {{ now()->format('Y-m-d H:i:s') }}
                </div>
                <div>
                    <strong>User Agent:</strong> <span id="userAgent">Loading...</span><br>
                    <strong>Screen Resolution:</strong> <span id="screenRes">Loading...</span><br>
                    <strong>Viewport Size:</strong> <span id="viewportSize">Loading...</span><br>
                    <strong>Device Pixel Ratio:</strong> <span id="devicePixelRatio">Loading...</span>
                </div>
            </div>
        </div>

        <div class="test-section" id="libraryTest">
            <h2>📦 Library Tests</h2>
            <p>Chart.js CDN Loading: <span class="status-indicator" id="chartjsStatus">Testing...</span></p>
            <p>Version: <span id="chartjsVersion">Unknown</span></p>
            <p>Chart Constructor: <span class="status-indicator" id="chartConstructor">Testing...</span></p>
            <p>Canvas Support: <span class="status-indicator" id="canvasSupport">Testing...</span></p>
        </div>

        <div class="test-section">
            <h2>📊 Chart Creation Tests</h2>
            <div class="grid">
                <!-- Patient Categories Chart (matches Petugas dashboard) -->
                <div>
                    <h3>Patient Categories Chart</h3>
                    <div class="chart-container">
                        <canvas id="patientCategoriesChart"></canvas>
                    </div>
                    <p>Status: <span class="status-indicator" id="patientChartStatus">Creating...</span></p>
                    <div id="patientCategoriesLegend"></div>
                </div>

                <!-- Procedure Types Chart (matches Petugas dashboard) -->
                <div>
                    <h3>Procedure Types Chart</h3>
                    <div class="chart-container">
                        <canvas id="procedureTypesChart"></canvas>
                    </div>
                    <p>Status: <span class="status-indicator" id="procedureChartStatus">Creating...</span></p>
                    <div id="procedureTypesLegend"></div>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>🐛 Debug Console</h2>
            <pre id="debugConsole">Starting debug session...\n</pre>
        </div>

        <div class="test-section">
            <h2>⚡ Manual Tests</h2>
            <p>Run these commands in browser console:</p>
            <pre>
// Test Chart.js availability
typeof Chart !== 'undefined'

// Test canvas elements
document.getElementById('patientCategoriesChart')
document.getElementById('procedureTypesChart')

// Test chart instances
window.debugCharts

// Reinitialize charts
initializeDebugCharts()
            </pre>
        </div>
    </div>

    <script>
        // Debug console
        const debugConsole = document.getElementById('debugConsole');
        let debugMessages = [];

        function debug(message) {
            const timestamp = new Date().toLocaleTimeString();
            const logMessage = `[${timestamp}] ${message}`;
            debugMessages.push(logMessage);
            debugConsole.textContent = debugMessages.join('\n') + '\n';
            console.log(logMessage);
        }

        function setStatus(elementId, success, message = '') {
            const element = document.getElementById(elementId);
            if (success) {
                element.textContent = message || 'Success';
                element.className = 'status-indicator status-success';
            } else {
                element.textContent = message || 'Failed';
                element.className = 'status-indicator status-error';
            }
        }

        // Initialize debug session
        debug('=== CHART.JS LARAVEL DEBUG SESSION STARTED ===');

        // System information
        document.getElementById('userAgent').textContent = navigator.userAgent;
        document.getElementById('screenRes').textContent = `${screen.width}x${screen.height}`;
        document.getElementById('viewportSize').textContent = `${window.innerWidth}x${window.innerHeight}`;
        document.getElementById('devicePixelRatio').textContent = window.devicePixelRatio;

        // Library tests
        debug('Testing Chart.js library availability...');
        const chartAvailable = typeof Chart !== 'undefined';
        setStatus('chartjsStatus', chartAvailable);

        if (chartAvailable) {
            debug(`Chart.js loaded successfully, version: ${Chart.version}`);
            document.getElementById('chartjsVersion').textContent = Chart.version;
            setStatus('chartConstructor', typeof Chart.Chart !== 'undefined' || typeof Chart === 'function');
        } else {
            debug('Chart.js failed to load!');
            document.getElementById('chartjsVersion').textContent = 'Not Available';
            setStatus('chartConstructor', false);
        }

        // Canvas support test
        const canvas = document.createElement('canvas');
        const canvasSupported = !!(canvas.getContext && canvas.getContext('2d'));
        setStatus('canvasSupport', canvasSupported);
        debug(`Canvas 2D support: ${canvasSupported}`);

        // Global chart storage
        window.debugCharts = {
            patientChart: null,
            procedureChart: null
        };

        // Create legend function (matching Petugas dashboard)
        function createSimpleLegend(chart, legendId) {
            debug(`Creating legend for: ${legendId}`);
            const legendContainer = document.getElementById(legendId);
            if (!legendContainer) {
                debug(`Legend container not found: ${legendId}`);
                return;
            }
            
            const legendItems = chart.data.labels.map((label, index) => {
                const dataset = chart.data.datasets[0];
                const value = dataset.data[index];
                const color = dataset.backgroundColor[index];
                const total = dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(0);
                
                return `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px; margin: 4px 0;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background-color: ${color};"></div>
                            <span style="font-size: 14px; font-weight: 500; color: #374151;">${label}</span>
                        </div>
                        <span style="font-size: 14px; font-weight: bold; color: #111827;">${percentage}%</span>
                    </div>
                `;
            }).join('');
            
            legendContainer.innerHTML = legendItems;
            debug(`Legend created with ${chart.data.labels.length} items`);
        }

        // Initialize charts function (matches Petugas dashboard logic)
        function initializeDebugCharts() {
            debug('=== INITIALIZING DEBUG CHARTS ===');
            
            if (!chartAvailable) {
                debug('Cannot initialize charts - Chart.js not available');
                setStatus('patientChartStatus', false, 'Chart.js Not Available');
                setStatus('procedureChartStatus', false, 'Chart.js Not Available');
                return;
            }

            // Destroy existing charts
            if (window.debugCharts.patientChart) {
                debug('Destroying existing patient chart');
                window.debugCharts.patientChart.destroy();
                window.debugCharts.patientChart = null;
            }
            if (window.debugCharts.procedureChart) {
                debug('Destroying existing procedure chart');
                window.debugCharts.procedureChart.destroy();
                window.debugCharts.procedureChart = null;
            }

            // Patient Categories Chart (exact copy from Petugas dashboard)
            const patientCanvas = document.getElementById('patientCategoriesChart');
            debug(`Patient canvas found: ${!!patientCanvas}`);
            
            if (patientCanvas) {
                try {
                    debug('Creating patient categories chart...');
                    const ctx = patientCanvas.getContext('2d');
                    
                    window.debugCharts.patientChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Umum', 'BPJS', 'Asuransi'],
                            datasets: [{
                                data: [45, 35, 20],
                                backgroundColor: [
                                    '#3B82F6',  // Blue
                                    '#10B981',  // Green
                                    '#F59E0B'   // Orange
                                ],
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                hoverBorderWidth: 3,
                                cutout: '60%',
                                hoverOffset: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                    titleColor: '#1F2937',
                                    bodyColor: '#374151',
                                    borderColor: '#E5E7EB',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 10,
                                    displayColors: true,
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((context.parsed / total) * 100);
                                            return `${context.label}: ${percentage}%`;
                                        }
                                    }
                                }
                            },
                            animation: {
                                animateRotate: true,
                                duration: 1500,
                                easing: 'easeInOutQuart'
                            }
                        }
                    });

                    debug('Patient chart created successfully');
                    setStatus('patientChartStatus', true);
                    createSimpleLegend(window.debugCharts.patientChart, 'patientCategoriesLegend');

                } catch (error) {
                    debug(`Error creating patient chart: ${error.message}`);
                    setStatus('patientChartStatus', false, error.message);
                }
            } else {
                debug('Patient canvas element not found');
                setStatus('patientChartStatus', false, 'Canvas Not Found');
            }

            // Procedure Types Chart (exact copy from Petugas dashboard)
            const procedureCanvas = document.getElementById('procedureTypesChart');
            debug(`Procedure canvas found: ${!!procedureCanvas}`);
            
            if (procedureCanvas) {
                try {
                    debug('Creating procedure types chart...');
                    const ctx = procedureCanvas.getContext('2d');
                    
                    window.debugCharts.procedureChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Konsultasi', 'Pemeriksaan', 'Tindakan'],
                            datasets: [{
                                data: [40, 35, 25],
                                backgroundColor: [
                                    '#8B5CF6',  // Purple
                                    '#EC4899',  // Pink
                                    '#06B6D4'   // Cyan
                                ],
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                hoverBorderWidth: 3,
                                cutout: '60%',
                                hoverOffset: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                    titleColor: '#1F2937',
                                    bodyColor: '#374151',
                                    borderColor: '#E5E7EB',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 10,
                                    displayColors: true,
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((context.parsed / total) * 100);
                                            return `${context.label}: ${percentage}%`;
                                        }
                                    }
                                }
                            },
                            animation: {
                                animateRotate: true,
                                duration: 1500,
                                easing: 'easeInOutQuart'
                            }
                        }
                    });

                    debug('Procedure chart created successfully');
                    setStatus('procedureChartStatus', true);
                    createSimpleLegend(window.debugCharts.procedureChart, 'procedureTypesLegend');

                } catch (error) {
                    debug(`Error creating procedure chart: ${error.message}`);
                    setStatus('procedureChartStatus', false, error.message);
                }
            } else {
                debug('Procedure canvas element not found');
                setStatus('procedureChartStatus', false, 'Canvas Not Found');
            }

            debug('=== CHART INITIALIZATION COMPLETED ===');
            debug(`Final state - Patient: ${!!window.debugCharts.patientChart}, Procedure: ${!!window.debugCharts.procedureChart}`);
        }

        // Auto-initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDebugCharts);
        } else {
            setTimeout(initializeDebugCharts, 100);
        }

        debug('Debug session setup complete');
    </script>
</body>
</html>