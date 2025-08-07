/**
 * GPS Automation Test Suite for Dokterku Presensi Component
 * Tests GPS functionality, permissions, and cross-browser compatibility
 */

class GPSTestSuite {
    constructor() {
        this.results = {
            browser: this.detectBrowser(),
            device: this.detectDevice(),
            timestamp: new Date().toISOString(),
            tests: [],
            performance: {
                totalTests: 0,
                passed: 0,
                failed: 0,
                averageResponseTime: 0,
                accuracy: []
            }
        };
        
        this.testConfig = {
            timeouts: [3000, 7000, 12000],
            accuracyThresholds: { excellent: 10, good: 50, acceptable: 100 },
            retryAttempts: 3,
            batchSize: 5
        };
        
        this.log('GPS Test Suite initialized', 'info');
    }

    // Browser and device detection
    detectBrowser() {
        const ua = navigator.userAgent;
        const browsers = {
            chrome: /Chrome\/(\d+\.\d+)/.test(ua),
            firefox: /Firefox\/(\d+\.\d+)/.test(ua),
            safari: /Safari\/(\d+\.\d+)/.test(ua) && !/Chrome/.test(ua),
            edge: /Edge\/(\d+\.\d+)/.test(ua),
            opera: /Opera\/(\d+\.\d+)/.test(ua)
        };
        
        const version = ua.match(/(?:Chrome|Firefox|Safari|Edge|Opera)\/(\d+\.\d+)/);
        
        return {
            name: Object.keys(browsers).find(key => browsers[key]) || 'unknown',
            version: version ? version[1] : 'unknown',
            userAgent: ua,
            geolocationSupport: !!navigator.geolocation,
            permissionsSupport: !!navigator.permissions,
            batterySupport: !!navigator.getBattery
        };
    }

    detectDevice() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        return {
            mobile: /Mobile|Android|iPhone|iPad/.test(navigator.userAgent),
            touch: 'ontouchstart' in window,
            orientation: 'DeviceOrientationEvent' in window,
            motion: 'DeviceMotionEvent' in window,
            platform: navigator.platform,
            language: navigator.language,
            cookiesEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            screen: {
                width: window.screen.width,
                height: window.screen.height,
                pixelRatio: window.devicePixelRatio
            },
            memory: navigator.deviceMemory || 'unknown',
            hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
            connection: connection ? {
                effectiveType: connection.effectiveType,
                downlink: connection.downlink,
                rtt: connection.rtt,
                saveData: connection.saveData
            } : null
        };
    }

    // Logging utility
    log(message, level = 'info') {
        const timestamp = new Date().toISOString();
        const logEntry = { timestamp, level, message };
        
        if (!this.results.logs) this.results.logs = [];
        this.results.logs.push(logEntry);
        
        console.log(`[${timestamp}] [${level.toUpperCase()}] ${message}`);
        
        // Emit custom event for UI updates
        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('gpsTestLog', { detail: logEntry }));
        }
    }

    // Permission testing
    async testPermissions() {
        const testName = 'GPS Permissions';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running'
        };

        try {
            // Check if permissions API is available
            if (navigator.permissions) {
                const permission = await navigator.permissions.query({ name: 'geolocation' });
                testResult.permissionsAPI = {
                    supported: true,
                    state: permission.state
                };
                
                this.log(`Permissions API state: ${permission.state}`, 'info');
                
                // Monitor permission changes
                permission.onchange = () => {
                    this.log(`Permission state changed to: ${permission.state}`, 'info');
                };
            } else {
                testResult.permissionsAPI = {
                    supported: false,
                    state: 'unknown'
                };
                this.log('Permissions API not supported', 'warning');
            }

            // Test direct geolocation access
            const position = await this.getCurrentPosition({
                enableHighAccuracy: false,
                timeout: 10000,
                maximumAge: 60000
            });

            const endTime = performance.now();
            
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.coordinates = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} passed: Permission granted and location acquired`, 'success');
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                code: error.code,
                message: error.message,
                type: this.getErrorType(error)
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: ${error.message}`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Basic GPS functionality test
    async testBasicGPS() {
        const testName = 'Basic GPS Functionality';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running'
        };

        try {
            const position = await this.getCurrentPosition({
                enableHighAccuracy: false,
                timeout: 10000,
                maximumAge: 30000
            });

            const endTime = performance.now();
            const accuracy = position.coords.accuracy;
            
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.coordinates = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: accuracy,
                altitude: position.coords.altitude,
                heading: position.coords.heading,
                speed: position.coords.speed
            };
            testResult.accuracyLevel = this.getAccuracyLevel(accuracy);
            testResult.endTime = new Date().toISOString();
            
            this.results.performance.accuracy.push(accuracy);
            
            this.log(`${testName} passed: Location acquired with ${accuracy}m accuracy`, 'success');
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                code: error.code,
                message: error.message,
                type: this.getErrorType(error),
                troubleshooting: this.getTroubleshootingSteps(error)
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: ${error.message}`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // High accuracy GPS test
    async testHighAccuracyGPS() {
        const testName = 'High Accuracy GPS';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running'
        };

        try {
            const position = await this.getCurrentPosition({
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });

            const endTime = performance.now();
            const accuracy = position.coords.accuracy;
            
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.coordinates = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: accuracy
            };
            testResult.accuracyLevel = this.getAccuracyLevel(accuracy);
            testResult.highAccuracy = true;
            testResult.endTime = new Date().toISOString();
            
            this.results.performance.accuracy.push(accuracy);
            
            this.log(`${testName} passed: High accuracy location with ${accuracy}m precision`, 'success');
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                code: error.code,
                message: error.message,
                type: this.getErrorType(error)
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: ${error.message}`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Timeout handling test
    async testTimeoutHandling() {
        const testName = 'GPS Timeout Handling';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running'
        };

        try {
            // Test with very short timeout to force timeout error
            const position = await this.getCurrentPosition({
                enableHighAccuracy: true,
                timeout: 1000, // Very short timeout
                maximumAge: 0
            });

            // If we get here, GPS was faster than expected
            const endTime = performance.now();
            
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.note = 'GPS responded faster than expected - excellent performance';
            testResult.coordinates = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName}: GPS faster than expected (< 1s)`, 'success');
            
        } catch (error) {
            const endTime = performance.now();
            
            if (error.code === GeolocationPositionError.TIMEOUT) {
                testResult.status = 'passed';
                testResult.duration = endTime - startTime;
                testResult.note = 'Timeout handled correctly';
                testResult.error = {
                    code: error.code,
                    message: error.message,
                    type: 'expected_timeout'
                };
                testResult.endTime = new Date().toISOString();
                
                this.log(`${testName} passed: Timeout handled correctly`, 'success');
            } else {
                testResult.status = 'failed';
                testResult.duration = endTime - startTime;
                testResult.error = {
                    code: error.code,
                    message: error.message,
                    type: this.getErrorType(error)
                };
                testResult.endTime = new Date().toISOString();
                
                this.log(`${testName} failed: Unexpected error - ${error.message}`, 'error');
            }
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Retry logic test
    async testRetryLogic() {
        const testName = 'GPS Retry Logic';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running',
            attempts: []
        };

        let success = false;
        let finalError = null;

        for (let attempt = 1; attempt <= this.testConfig.retryAttempts; attempt++) {
            const attemptStart = performance.now();
            this.log(`GPS retry attempt ${attempt}/${this.testConfig.retryAttempts}`, 'info');
            
            try {
                const position = await this.getCurrentPosition({
                    enableHighAccuracy: attempt <= 2,
                    timeout: this.testConfig.timeouts[attempt - 1] || 12000,
                    maximumAge: attempt === 1 ? 30000 : 60000
                });

                const attemptEnd = performance.now();
                
                testResult.attempts.push({
                    number: attempt,
                    status: 'success',
                    duration: attemptEnd - attemptStart,
                    coordinates: {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    }
                });

                success = true;
                break;
                
            } catch (error) {
                const attemptEnd = performance.now();
                
                testResult.attempts.push({
                    number: attempt,
                    status: 'failed',
                    duration: attemptEnd - attemptStart,
                    error: {
                        code: error.code,
                        message: error.message,
                        type: this.getErrorType(error)
                    }
                });

                finalError = error;
                
                if (attempt < this.testConfig.retryAttempts) {
                    // Wait before retry
                    await this.sleep(1000 * attempt);
                }
            }
        }

        const endTime = performance.now();
        
        if (success) {
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.successfulAttempt = testResult.attempts.find(a => a.status === 'success').number;
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} passed: Succeeded on attempt ${testResult.successfulAttempt}`, 'success');
        } else {
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                code: finalError.code,
                message: finalError.message,
                type: this.getErrorType(finalError),
                note: `All ${this.testConfig.retryAttempts} attempts failed`
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: All ${this.testConfig.retryAttempts} attempts failed`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Battery optimization test
    async testBatteryOptimization() {
        const testName = 'Battery Optimization';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running'
        };

        try {
            if ('getBattery' in navigator) {
                const battery = await navigator.getBattery();
                const batteryLevel = battery.level;
                const isCharging = battery.charging;
                const isLowBattery = batteryLevel < 0.2;
                
                testResult.batteryInfo = {
                    supported: true,
                    level: (batteryLevel * 100).toFixed(1) + '%',
                    charging: isCharging,
                    chargingTime: battery.chargingTime,
                    dischargingTime: battery.dischargingTime
                };
                
                // Test GPS with battery-optimized settings
                const gpsOptions = {
                    enableHighAccuracy: !isLowBattery && !navigator.userAgent.includes('Mobile'),
                    timeout: isLowBattery ? 15000 : 10000,
                    maximumAge: isLowBattery ? 300000 : 60000
                };
                
                const position = await this.getCurrentPosition(gpsOptions);
                
                const endTime = performance.now();
                
                testResult.status = 'passed';
                testResult.duration = endTime - startTime;
                testResult.coordinates = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                testResult.optimizationApplied = isLowBattery;
                testResult.gpsOptions = gpsOptions;
                testResult.endTime = new Date().toISOString();
                
                this.log(`${testName} passed: Battery level ${testResult.batteryInfo.level}, optimization ${isLowBattery ? 'active' : 'inactive'}`, 'success');
                
            } else {
                testResult.batteryInfo = {
                    supported: false,
                    note: 'Battery API not supported on this browser'
                };
                
                testResult.status = 'passed';
                testResult.duration = performance.now() - startTime;
                testResult.endTime = new Date().toISOString();
                
                this.log(`${testName}: Battery API not supported, test completed`, 'warning');
            }
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                code: error.code || 'UNKNOWN',
                message: error.message,
                type: this.getErrorType(error)
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: ${error.message}`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Network-based location fallback test
    async testNetworkLocationFallback() {
        const testName = 'Network Location Fallback';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running'
        };

        try {
            // First try GPS with very short timeout to likely fail
            let position;
            let source = 'gps';
            
            try {
                position = await this.getCurrentPosition({
                    enableHighAccuracy: true,
                    timeout: 2000,
                    maximumAge: 0
                });
            } catch (gpsError) {
                this.log('GPS failed, trying network location fallback...', 'info');
                
                // Try network-based location
                const response = await fetch('https://ipapi.co/json/');
                const data = await response.json();
                
                if (data.latitude && data.longitude) {
                    position = {
                        coords: {
                            latitude: data.latitude,
                            longitude: data.longitude,
                            accuracy: 1000, // Network location is less accurate
                            altitude: null,
                            altitudeAccuracy: null,
                            heading: null,
                            speed: null
                        },
                        timestamp: Date.now()
                    };
                    source = 'network';
                } else {
                    throw new Error('Network location unavailable');
                }
            }

            const endTime = performance.now();
            
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.coordinates = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            testResult.source = source;
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} passed: Location acquired via ${source}`, 'success');
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                message: error.message,
                type: 'network_fallback_failed'
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: Both GPS and network location failed`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Mobile-specific tests
    async testMobileFeatures() {
        const testName = 'Mobile Device Features';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running',
            features: {}
        };

        try {
            // Check mobile capabilities
            testResult.features = {
                touchSupport: 'ontouchstart' in window,
                deviceOrientation: 'DeviceOrientationEvent' in window,
                deviceMotion: 'DeviceMotionEvent' in window,
                vibration: 'vibrate' in navigator,
                screenOrientation: 'screen' in window && 'orientation' in window.screen,
                wakeLock: 'wakeLock' in navigator,
                fullscreen: document.fullscreenEnabled || document.webkitFullscreenEnabled,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight,
                    pixelRatio: window.devicePixelRatio
                }
            };

            // Test device orientation if available
            if (testResult.features.deviceOrientation) {
                const orientationData = await this.testDeviceOrientation();
                testResult.features.orientationData = orientationData;
            }

            // Test GPS on mobile with mobile-optimized settings
            const position = await this.getCurrentPosition({
                enableHighAccuracy: !this.results.device.mobile, // Less accuracy on mobile to save battery
                timeout: 12000,
                maximumAge: this.results.device.mobile ? 120000 : 60000
            });

            const endTime = performance.now();
            
            testResult.status = 'passed';
            testResult.duration = endTime - startTime;
            testResult.coordinates = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} passed: Mobile GPS and features tested`, 'success');
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                code: error.code,
                message: error.message,
                type: this.getErrorType(error)
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: ${error.message}`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Device orientation test helper
    async testDeviceOrientation() {
        return new Promise((resolve) => {
            let orientationData = null;
            
            const orientationHandler = (event) => {
                orientationData = {
                    alpha: event.alpha,
                    beta: event.beta,
                    gamma: event.gamma,
                    absolute: event.absolute
                };
            };
            
            window.addEventListener('deviceorientation', orientationHandler);
            
            setTimeout(() => {
                window.removeEventListener('deviceorientation', orientationHandler);
                resolve(orientationData);
            }, 3000);
        });
    }

    // Performance benchmark test
    async testPerformance() {
        const testName = 'GPS Performance Benchmark';
        this.log(`Starting ${testName} test...`, 'info');
        
        const startTime = performance.now();
        const testResult = {
            name: testName,
            startTime: new Date().toISOString(),
            status: 'running',
            iterations: []
        };

        try {
            const iterations = 5;
            let totalTime = 0;
            let successCount = 0;
            const accuracies = [];

            for (let i = 1; i <= iterations; i++) {
                this.log(`Performance test iteration ${i}/${iterations}`, 'info');
                
                const iterationStart = performance.now();
                
                try {
                    const position = await this.getCurrentPosition({
                        enableHighAccuracy: false,
                        timeout: 8000,
                        maximumAge: 0 // Force fresh reading each time
                    });

                    const iterationEnd = performance.now();
                    const duration = iterationEnd - iterationStart;
                    
                    testResult.iterations.push({
                        number: i,
                        status: 'success',
                        duration: duration,
                        accuracy: position.coords.accuracy
                    });
                    
                    totalTime += duration;
                    successCount++;
                    accuracies.push(position.coords.accuracy);
                    
                } catch (error) {
                    const iterationEnd = performance.now();
                    
                    testResult.iterations.push({
                        number: i,
                        status: 'failed',
                        duration: iterationEnd - iterationStart,
                        error: error.message
                    });
                }
                
                // Brief pause between iterations
                await this.sleep(1000);
            }

            const endTime = performance.now();
            
            if (successCount > 0) {
                testResult.status = 'passed';
                testResult.duration = endTime - startTime;
                testResult.performance = {
                    successRate: (successCount / iterations * 100).toFixed(1) + '%',
                    averageResponseTime: (totalTime / successCount).toFixed(1) + 'ms',
                    averageAccuracy: (accuracies.reduce((a, b) => a + b, 0) / accuracies.length).toFixed(1) + 'm',
                    minAccuracy: Math.min(...accuracies).toFixed(1) + 'm',
                    maxAccuracy: Math.max(...accuracies).toFixed(1) + 'm'
                };
                testResult.endTime = new Date().toISOString();
                
                this.log(`${testName} passed: ${testResult.performance.successRate} success rate`, 'success');
            } else {
                testResult.status = 'failed';
                testResult.duration = endTime - startTime;
                testResult.error = {
                    message: 'All performance test iterations failed',
                    type: 'performance_failure'
                };
                testResult.endTime = new Date().toISOString();
                
                this.log(`${testName} failed: All iterations failed`, 'error');
            }
            
        } catch (error) {
            const endTime = performance.now();
            
            testResult.status = 'failed';
            testResult.duration = endTime - startTime;
            testResult.error = {
                message: error.message,
                type: 'performance_test_error'
            };
            testResult.endTime = new Date().toISOString();
            
            this.log(`${testName} failed: ${error.message}`, 'error');
        }

        this.results.tests.push(testResult);
        return testResult;
    }

    // Run complete test suite
    async runFullTestSuite() {
        this.log('🚀 Starting complete GPS test suite...', 'info');
        
        const suiteStart = performance.now();
        
        const tests = [
            () => this.testPermissions(),
            () => this.testBasicGPS(),
            () => this.testHighAccuracyGPS(),
            () => this.testTimeoutHandling(),
            () => this.testRetryLogic(),
            () => this.testBatteryOptimization(),
            () => this.testNetworkLocationFallback(),
            () => this.testMobileFeatures(),
            () => this.testPerformance()
        ];

        for (let i = 0; i < tests.length; i++) {
            const testFunction = tests[i];
            this.log(`Running test ${i + 1}/${tests.length}...`, 'info');
            
            try {
                await testFunction();
            } catch (error) {
                this.log(`Test ${i + 1} encountered an error: ${error.message}`, 'error');
            }
            
            // Brief pause between tests
            await this.sleep(500);
        }

        const suiteEnd = performance.now();
        
        // Calculate final performance metrics
        this.results.performance.totalTests = this.results.tests.length;
        this.results.performance.passed = this.results.tests.filter(t => t.status === 'passed').length;
        this.results.performance.failed = this.results.tests.filter(t => t.status === 'failed').length;
        this.results.performance.averageResponseTime = this.results.tests
            .filter(t => t.status === 'passed' && t.duration)
            .reduce((sum, t) => sum + t.duration, 0) / this.results.performance.passed || 0;
        
        if (this.results.performance.accuracy.length > 0) {
            this.results.performance.averageAccuracy = this.results.performance.accuracy
                .reduce((sum, acc) => sum + acc, 0) / this.results.performance.accuracy.length;
        }
        
        this.results.testSuite = {
            duration: suiteEnd - suiteStart,
            endTime: new Date().toISOString(),
            successRate: (this.results.performance.passed / this.results.performance.totalTests * 100).toFixed(1) + '%'
        };
        
        this.log(`🎉 Test suite completed: ${this.results.testSuite.successRate} success rate in ${(this.results.testSuite.duration/1000).toFixed(1)}s`, 'success');
        
        return this.results;
    }

    // Utility methods
    getCurrentPosition(options = {}) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }
            
            const defaultOptions = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            };
            
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                { ...defaultOptions, ...options }
            );
        });
    }

    getErrorType(error) {
        if (!error.code) return 'unknown';
        
        const errorTypes = {
            [GeolocationPositionError.PERMISSION_DENIED]: 'permission_denied',
            [GeolocationPositionError.POSITION_UNAVAILABLE]: 'position_unavailable',
            [GeolocationPositionError.TIMEOUT]: 'timeout'
        };
        
        return errorTypes[error.code] || 'unknown_geolocation_error';
    }

    getTroubleshootingSteps(error) {
        const troubleshooting = {
            [GeolocationPositionError.PERMISSION_DENIED]: [
                'Enable location permissions in browser settings',
                'Click the location icon in the address bar',
                'Refresh the page and try again'
            ],
            [GeolocationPositionError.POSITION_UNAVAILABLE]: [
                'Check that GPS is enabled on your device',
                'Move to an open area with clear sky view',
                'Try restarting location services'
            ],
            [GeolocationPositionError.TIMEOUT]: [
                'Move to a location with better GPS signal',
                'Try again with a longer timeout',
                'Ensure location services are running'
            ]
        };
        
        return troubleshooting[error.code] || ['Try refreshing the page and enabling location permissions'];
    }

    getAccuracyLevel(accuracy) {
        if (accuracy <= this.testConfig.accuracyThresholds.excellent) {
            return `Excellent (±${accuracy.toFixed(1)}m)`;
        } else if (accuracy <= this.testConfig.accuracyThresholds.good) {
            return `Good (±${accuracy.toFixed(1)}m)`;
        } else if (accuracy <= this.testConfig.accuracyThresholds.acceptable) {
            return `Acceptable (±${accuracy.toFixed(1)}m)`;
        } else {
            return `Poor (±${accuracy.toFixed(1)}m)`;
        }
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Export results
    exportResults(format = 'json') {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        
        if (format === 'json') {
            const blob = new Blob([JSON.stringify(this.results, null, 2)], { 
                type: 'application/json' 
            });
            
            if (typeof window !== 'undefined') {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `gps-test-results-${timestamp}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
            
            return blob;
        } else if (format === 'csv') {
            const csvData = this.convertToCSV();
            const blob = new Blob([csvData], { type: 'text/csv' });
            
            if (typeof window !== 'undefined') {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `gps-test-results-${timestamp}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
            
            return blob;
        }
    }

    convertToCSV() {
        const headers = [
            'Test Name', 'Status', 'Duration (ms)', 'Start Time', 'End Time',
            'Latitude', 'Longitude', 'Accuracy (m)', 'Error Type', 'Error Message'
        ];
        
        const rows = this.results.tests.map(test => [
            test.name,
            test.status,
            test.duration || '',
            test.startTime,
            test.endTime || '',
            test.coordinates?.latitude || '',
            test.coordinates?.longitude || '',
            test.coordinates?.accuracy || '',
            test.error?.type || '',
            test.error?.message || ''
        ]);
        
        return [headers, ...rows]
            .map(row => row.map(field => `"${field}"`).join(','))
            .join('\n');
    }

    // Generate test report
    generateReport() {
        const report = {
            summary: {
                testDate: this.results.timestamp,
                browser: `${this.results.browser.name} ${this.results.browser.version}`,
                device: this.results.device.mobile ? 'Mobile' : 'Desktop',
                platform: this.results.device.platform,
                totalTests: this.results.performance.totalTests,
                passed: this.results.performance.passed,
                failed: this.results.performance.failed,
                successRate: this.results.testSuite?.successRate || '0%',
                totalDuration: this.results.testSuite?.duration || 0
            },
            capabilities: {
                geolocationSupport: this.results.browser.geolocationSupport,
                permissionsSupport: this.results.browser.permissionsSupport,
                batterySupport: this.results.browser.batterySupport,
                touchSupport: this.results.device.touch,
                orientationSupport: this.results.device.orientation
            },
            performance: {
                averageResponseTime: this.results.performance.averageResponseTime,
                averageAccuracy: this.results.performance.averageAccuracy
            },
            recommendations: this.generateRecommendations(),
            compatibility: this.assessCompatibility()
        };
        
        return report;
    }

    generateRecommendations() {
        const recommendations = [];
        
        if (this.results.performance.failed > 0) {
            recommendations.push('Some tests failed - review error logs for specific issues');
        }
        
        if (this.results.performance.averageResponseTime > 8000) {
            recommendations.push('GPS response time is slow - consider implementing retry logic');
        }
        
        if (this.results.performance.averageAccuracy > 100) {
            recommendations.push('GPS accuracy is poor - consider using high accuracy mode');
        }
        
        if (!this.results.browser.permissionsSupport) {
            recommendations.push('Permissions API not supported - implement fallback permission handling');
        }
        
        if (!this.results.browser.batterySupport) {
            recommendations.push('Battery API not supported - implement generic battery optimization');
        }
        
        if (this.results.device.mobile && this.results.performance.averageResponseTime > 6000) {
            recommendations.push('Mobile GPS performance is slow - enable battery optimization');
        }
        
        return recommendations;
    }

    assessCompatibility() {
        let score = 100;
        const issues = [];
        
        if (!this.results.browser.geolocationSupport) {
            score -= 50;
            issues.push('Geolocation API not supported');
        }
        
        if (!this.results.browser.permissionsSupport) {
            score -= 20;
            issues.push('Permissions API not supported');
        }
        
        if (!this.results.browser.batterySupport) {
            score -= 10;
            issues.push('Battery API not supported');
        }
        
        if (this.results.performance.failed > this.results.performance.passed) {
            score -= 30;
            issues.push('More tests failed than passed');
        }
        
        return {
            score: Math.max(0, score),
            level: score >= 90 ? 'Excellent' : score >= 70 ? 'Good' : score >= 50 ? 'Fair' : 'Poor',
            issues
        };
    }
}

// Export for use in browser or Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GPSTestSuite;
} else if (typeof window !== 'undefined') {
    window.GPSTestSuite = GPSTestSuite;
}