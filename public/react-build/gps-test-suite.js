/**
 * GPS Test Suite for Dokterku Application
 * Comprehensive testing and debugging tools for GPS detection
 */

(function() {
    'use strict';

    // GPS Test Suite Configuration
    const GPS_TEST_CONFIG = {
        testCases: [
            {
                name: 'Browser Support',
                test: () => {
                    const hasGeolocation = 'geolocation' in navigator;
                    const hasPermissions = 'permissions' in navigator;
                    const hasSecureContext = window.isSecureContext;
                    
                    return {
                        passed: hasGeolocation,
                        details: {
                            geolocation: hasGeolocation,
                            permissions: hasPermissions,
                            secureContext: hasSecureContext,
                            userAgent: navigator.userAgent
                        }
                    };
                }
            },
            {
                name: 'HTTPS Requirement',
                test: () => {
                    const isHttps = window.location.protocol === 'https:';
                    const isLocalhost = window.location.hostname === 'localhost' || 
                                      window.location.hostname === '127.0.0.1';
                    const isSecure = isHttps || isLocalhost;
                    
                    return {
                        passed: isSecure,
                        details: {
                            protocol: window.location.protocol,
                            hostname: window.location.hostname,
                            isHttps: isHttps,
                            isLocalhost: isLocalhost,
                            isSecure: isSecure
                        }
                    };
                }
            },
            {
                name: 'Permission State',
                test: async () => {
                    if (!('permissions' in navigator)) {
                        return {
                            passed: false,
                            details: { error: 'Permissions API not supported' }
                        };
                    }
                    
                    try {
                        const result = await navigator.permissions.query({ name: 'geolocation' });
                        return {
                            passed: result.state === 'granted',
                            details: {
                                state: result.state,
                                supported: true
                            }
                        };
                    } catch (error) {
                        return {
                            passed: false,
                            details: { error: error.message }
                        };
                    }
                }
            },
            {
                name: 'GPS Detection Function',
                test: () => {
                    const hasAutoDetect = typeof window.autoDetectLocation === 'function';
                    const hasGPSHelp = typeof window.GPSHelpSystem !== 'undefined';
                    
                    return {
                        passed: hasAutoDetect,
                        details: {
                            autoDetectLocation: hasAutoDetect,
                            GPSHelpSystem: hasGPSHelp
                        }
                    };
                }
            },
            {
                name: 'Form Field Detection',
                test: () => {
                    const latField = document.querySelector('input[data-coordinate-field="latitude"], input[name="latitude"], #latitude');
                    const lngField = document.querySelector('input[data-coordinate-field="longitude"], input[name="longitude"], #longitude');
                    
                    return {
                        passed: !!(latField && lngField),
                        details: {
                            latitudeField: latField ? {
                                id: latField.id,
                                name: latField.name,
                                type: latField.type,
                                value: latField.value
                            } : null,
                            longitudeField: lngField ? {
                                id: lngField.id,
                                name: lngField.name,
                                type: lngField.type,
                                value: lngField.value
                            } : null
                        }
                    };
                }
            },
            {
                name: 'Map Component',
                test: () => {
                    const hasMapComponent = typeof window.CreativeLeafletMaps !== 'undefined';
                    const mapCount = hasMapComponent ? window.CreativeLeafletMaps.size : 0;
                    
                    return {
                        passed: hasMapComponent && mapCount > 0,
                        details: {
                            CreativeLeafletMaps: hasMapComponent,
                            mapCount: mapCount
                        }
                    };
                }
            }
        ],
        
        // Mock GPS position for testing
        mockPosition: {
            coords: {
                latitude: -6.2088,
                longitude: 106.8456,
                accuracy: 10,
                altitude: null,
                altitudeAccuracy: null,
                heading: null,
                speed: null
            },
            timestamp: Date.now()
        }
    };

    // GPS Test Suite Class
    class GPSTestSuite {
        constructor() {
            this.results = [];
            this.isRunning = false;
            this.init();
        }

        init() {
            this.createTestPanel();
            this.bindEvents();
        }

        createTestPanel() {
            const panel = document.createElement('div');
            panel.id = 'gps-test-panel';
            panel.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 10000;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                padding: 20px;
                max-width: 400px;
                font-family: system-ui, -apple-system, sans-serif;
                display: none;
            `;

            panel.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #1f2937; font-size: 18px;">🧪 GPS Test Suite</h3>
                    <button id="gps-test-close" style="
                        background: none;
                        border: none;
                        font-size: 20px;
                        cursor: pointer;
                        color: #6b7280;
                    ">×</button>
                </div>
                
                <div id="gps-test-results" style="margin-bottom: 16px;"></div>
                
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button id="gps-test-run" style="
                        background: #3b82f6;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-size: 14px;
                        cursor: pointer;
                    ">Run Tests</button>
                    <button id="gps-test-mock" style="
                        background: #10b981;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-size: 14px;
                        cursor: pointer;
                    ">Mock GPS</button>
                    <button id="gps-test-help" style="
                        background: #f59e0b;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-size: 14px;
                        cursor: pointer;
                    ">Help</button>
                </div>
            `;

            document.body.appendChild(panel);
        }

        bindEvents() {
            // Close button
            document.getElementById('gps-test-close').addEventListener('click', () => {
                this.hidePanel();
            });

            // Run tests button
            document.getElementById('gps-test-run').addEventListener('click', () => {
                this.runAllTests();
            });

            // Mock GPS button
            document.getElementById('gps-test-mock').addEventListener('click', () => {
                this.mockGPSDetection();
            });

            // Help button
            document.getElementById('gps-test-help').addEventListener('click', () => {
                this.showHelp();
            });
        }

        showPanel() {
            document.getElementById('gps-test-panel').style.display = 'block';
        }

        hidePanel() {
            document.getElementById('gps-test-panel').style.display = 'none';
        }

        async runAllTests() {
            if (this.isRunning) return;
            
            this.isRunning = true;
            this.results = [];
            
            const resultsContainer = document.getElementById('gps-test-results');
            resultsContainer.innerHTML = '<div style="color: #6b7280; font-size: 14px;">Running tests...</div>';
            
            let passedTests = 0;
            let totalTests = GPS_TEST_CONFIG.testCases.length;
            
            for (const testCase of GPS_TEST_CONFIG.testCases) {
                try {
                    const result = await testCase.test();
                    this.results.push({
                        name: testCase.name,
                        ...result
                    });
                    
                    if (result.passed) passedTests++;
                    
                    // Update results display
                    this.updateResultsDisplay();
                    
                } catch (error) {
                    this.results.push({
                        name: testCase.name,
                        passed: false,
                        details: { error: error.message }
                    });
                }
            }
            
            this.isRunning = false;
            
            // Show summary
            const summary = `
                <div style="
                    background: ${passedTests === totalTests ? '#d1fae5' : '#fef3c7'};
                    border: 1px solid ${passedTests === totalTests ? '#10b981' : '#f59e0b'};
                    border-radius: 8px;
                    padding: 12px;
                    margin-bottom: 16px;
                ">
                    <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                        Test Results: ${passedTests}/${totalTests} passed
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        ${passedTests === totalTests ? '✅ All tests passed!' : '⚠️ Some tests failed'}
                    </div>
                </div>
            `;
            
            resultsContainer.innerHTML = summary + resultsContainer.innerHTML;
        }

        updateResultsDisplay() {
            const container = document.getElementById('gps-test-results');
            const summary = container.querySelector('div[style*="background"]');
            
            let resultsHtml = '';
            this.results.forEach(result => {
                const statusIcon = result.passed ? '✅' : '❌';
                const statusColor = result.passed ? '#10b981' : '#ef4444';
                
                resultsHtml += `
                    <div style="
                        border: 1px solid ${statusColor};
                        border-radius: 6px;
                        padding: 8px;
                        margin-bottom: 8px;
                        background: ${result.passed ? '#f0fdf4' : '#fef2f2'};
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #1f2937;">${statusIcon} ${result.name}</span>
                            <span style="color: ${statusColor}; font-size: 12px;">
                                ${result.passed ? 'PASS' : 'FAIL'}
                            </span>
                        </div>
                        ${result.details ? `
                            <details style="margin-top: 8px;">
                                <summary style="cursor: pointer; color: #6b7280; font-size: 12px;">Details</summary>
                                <pre style="
                                    background: white;
                                    padding: 8px;
                                    border-radius: 4px;
                                    font-size: 11px;
                                    margin-top: 4px;
                                    overflow-x: auto;
                                ">${JSON.stringify(result.details, null, 2)}</pre>
                            </details>
                        ` : ''}
                    </div>
                `;
            });
            
            if (summary) {
                container.innerHTML = summary.outerHTML + resultsHtml;
            } else {
                container.innerHTML = resultsHtml;
            }
        }

        mockGPSDetection() {
            console.log('🧪 Mocking GPS detection...');
            
            // Create a mock geolocation object
            const mockGeolocation = {
                getCurrentPosition: (success, error, options) => {
                    console.log('🧪 Mock GPS: getCurrentPosition called with options:', options);
                    
                    // Simulate a delay
                    setTimeout(() => {
                        if (success) {
                            success(GPS_TEST_CONFIG.mockPosition);
                            console.log('🧪 Mock GPS: Success callback executed');
                        }
                    }, 1000);
                },
                
                watchPosition: (success, error, options) => {
                    console.log('🧪 Mock GPS: watchPosition called');
                    const watchId = Math.floor(Math.random() * 1000);
                    
                    // Simulate periodic updates
                    const interval = setInterval(() => {
                        if (success) {
                            const mockPos = {
                                ...GPS_TEST_CONFIG.mockPosition,
                                timestamp: Date.now()
                            };
                            success(mockPos);
                        }
                    }, 5000);
                    
                    return watchId;
                },
                
                clearWatch: (watchId) => {
                    console.log('🧪 Mock GPS: clearWatch called for ID:', watchId);
                }
            };
            
            // Replace the real geolocation temporarily
            const originalGeolocation = navigator.geolocation;
            navigator.geolocation = mockGeolocation;
            
            // Trigger GPS detection
            if (typeof window.autoDetectLocation === 'function') {
                window.autoDetectLocation();
            }
            
            // Restore original geolocation after 10 seconds
            setTimeout(() => {
                navigator.geolocation = originalGeolocation;
                console.log('🧪 Mock GPS: Restored original geolocation');
            }, 10000);
            
            // Show notification
            if (window.Filament) {
                window.Filament.notification()
                    .title('🧪 Mock GPS Active')
                    .body('GPS detection is now using mock data for testing. Will restore in 10 seconds.')
                    .info()
                    .send();
            }
        }

        showHelp() {
            const helpModal = document.createElement('div');
            helpModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10001;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: system-ui, -apple-system, sans-serif;
            `;

            helpModal.innerHTML = `
                <div style="
                    background: white;
                    border-radius: 16px;
                    max-width: 600px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    padding: 24px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; color: #1f2937; font-size: 20px;">🧪 GPS Test Suite Help</h3>
                        <button onclick="this.closest('[style*=\'position: fixed\']').remove()" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #6b7280;
                        ">×</button>
                    </div>
                    
                    <div style="color: #4b5563; line-height: 1.6;">
                        <h4 style="color: #1f2937; margin: 16px 0 8px 0;">Test Cases:</h4>
                        <ul style="margin: 0 0 16px 0; padding-left: 20px;">
                            <li><strong>Browser Support:</strong> Checks if geolocation API is available</li>
                            <li><strong>HTTPS Requirement:</strong> Verifies secure context for GPS</li>
                            <li><strong>Permission State:</strong> Tests location permission status</li>
                            <li><strong>GPS Detection Function:</strong> Validates autoDetectLocation function</li>
                            <li><strong>Form Field Detection:</strong> Checks for coordinate input fields</li>
                            <li><strong>Map Component:</strong> Verifies map integration</li>
                        </ul>
                        
                        <h4 style="color: #1f2937; margin: 16px 0 8px 0;">Mock GPS:</h4>
                        <p style="margin: 0 0 16px 0;">
                            Simulates GPS detection with mock coordinates for testing form field updates.
                        </p>
                        
                        <h4 style="color: #1f2937; margin: 16px 0 8px 0;">Usage:</h4>
                        <ol style="margin: 0; padding-left: 20px;">
                            <li>Click "Run Tests" to check all GPS components</li>
                            <li>Use "Mock GPS" to test form field detection</li>
                            <li>Review test results and details</li>
                            <li>Fix any failed tests before using GPS</li>
                        </ol>
                    </div>
                </div>
            `;

            document.body.appendChild(helpModal);
        }

        // Public methods
        runTests() {
            this.showPanel();
            this.runAllTests();
        }

        getResults() {
            return this.results;
        }
    }

    // Initialize GPS Test Suite
    const gpsTestSuite = new GPSTestSuite();

    // Global functions for external access
    window.GPSTestSuite = {
        run: () => gpsTestSuite.runTests(),
        getResults: () => gpsTestSuite.getResults(),
        showPanel: () => gpsTestSuite.showPanel(),
        hidePanel: () => gpsTestSuite.hidePanel()
    };

    // Auto-show test panel in development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        // Show test panel after 3 seconds in development
        setTimeout(() => {
            gpsTestSuite.showPanel();
        }, 3000);
    }

    // Keyboard shortcut to show test panel (Ctrl+Shift+G)
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.shiftKey && e.key === 'G') {
            e.preventDefault();
            gpsTestSuite.showPanel();
        }
    });

    console.log('✅ GPS Test Suite loaded successfully');
    console.log('🧪 Use GPSTestSuite.run() to run tests');
    console.log('🧪 Press Ctrl+Shift+G to show test panel');

})();
