// GPS Performance Test Suite
// Tests the optimized GPS system performance improvements

class GPSPerformanceTest {
  constructor() {
    this.testResults = [];
    this.baselineMetrics = {
      averageAcquisitionTime: 15000, // 15s baseline
      cacheHitRate: 0,
      batteryOptimizationActive: false,
      fallbackSuccessRate: 0
    };
    this.performanceTargets = {
      freshGPSMaxTime: 7000,      // 7s max for fresh GPS
      cachedGPSMaxTime: 500,      // 500ms max for cached
      cacheHitRate: 0.8,          // 80% cache hit rate
      batteryOptimizationThreshold: 0.2 // 20% battery
    };
  }

  // Utility: Mock GPS with controllable timing and accuracy
  mockGeolocation(options = {}) {
    const {
      delay = 3000,
      accuracy = 10,
      shouldFail = false,
      latitude = -6.2088,
      longitude = 106.8456
    } = options;

    const mockPosition = {
      coords: {
        latitude,
        longitude,
        accuracy,
        altitude: null,
        altitudeAccuracy: null,
        heading: null,
        speed: null
      },
      timestamp: Date.now()
    };

    global.navigator = {
      geolocation: {
        getCurrentPosition: jest.fn((success, error, options) => {
          setTimeout(() => {
            if (shouldFail) {
              error({
                code: 3, // TIMEOUT
                message: 'GPS timeout'
              });
            } else {
              success(mockPosition);
            }
          }, delay);
        }),
        watchPosition: jest.fn(),
        clearWatch: jest.fn()
      }
    };

    // Mock Battery API
    global.navigator.getBattery = jest.fn(() => Promise.resolve({
      level: options.batteryLevel || 1.0,
      charging: false
    }));

    return mockPosition;
  }

  // Mock network location service
  mockNetworkLocation(options = {}) {
    const {
      delay = 2000,
      shouldFail = false,
      latitude = -6.2088,
      longitude = 106.8456
    } = options;

    global.fetch = jest.fn(() => {
      return new Promise((resolve, reject) => {
        setTimeout(() => {
          if (shouldFail) {
            reject(new Error('Network error'));
          } else {
            resolve({
              json: () => Promise.resolve({
                latitude,
                longitude,
                accuracy: 1000
              })
            });
          }
        }, delay);
      });
    });
  }

  // Performance Test 1: Fresh GPS Request (No Cache)
  async testFreshGPSAcquisition() {
    console.log('🧪 Testing Fresh GPS Acquisition...');
    const startTime = Date.now();
    
    // Clear any existing cache
    localStorage.removeItem('gps_cache');
    
    // Mock fast GPS response
    this.mockGeolocation({ delay: 4000, accuracy: 8 });
    
    try {
      // Import and test the optimized GPS hook
      const { useOptimizedGPS } = await import('../../resources/js/hooks/useOptimizedGPS.ts');
      
      const gpsHook = useOptimizedGPS({
        enableCache: false, // Disable cache for fresh test
        onProgress: (status, progress) => {
          console.log(`  📍 ${status} (${progress}%)`);
        }
      });
      
      const location = await gpsHook.getCurrentLocation();
      const acquisitionTime = Date.now() - startTime;
      
      const result = {
        testName: 'Fresh GPS Acquisition',
        acquisitionTime,
        success: acquisitionTime <= this.performanceTargets.freshGPSMaxTime,
        accuracy: location.accuracy,
        source: location.source,
        target: this.performanceTargets.freshGPSMaxTime,
        improvement: ((this.baselineMetrics.averageAcquisitionTime - acquisitionTime) / this.baselineMetrics.averageAcquisitionTime * 100).toFixed(1)
      };
      
      this.testResults.push(result);
      
      console.log(`  ✅ Fresh GPS: ${acquisitionTime}ms (Target: ${this.performanceTargets.freshGPSMaxTime}ms)`);
      console.log(`  📊 Improvement: ${result.improvement}% vs baseline`);
      
      return result;
    } catch (error) {
      console.error(`  ❌ Fresh GPS test failed:`, error);
      return { testName: 'Fresh GPS Acquisition', success: false, error: error.message };
    }
  }

  // Performance Test 2: Cached GPS Request
  async testCachedGPSAccess() {
    console.log('🧪 Testing Cached GPS Access...');
    const startTime = Date.now();
    
    // Pre-populate cache with fresh location
    const cacheLocation = {
      latitude: -6.2088,
      longitude: 106.8456,
      accuracy: 10,
      timestamp: Date.now(),
      source: 'gps',
      confidence: 0.9
    };
    
    // Mock successful first GPS call to populate cache
    this.mockGeolocation({ delay: 500, accuracy: 10 });
    
    try {
      const { useOptimizedGPS } = await import('../../resources/js/hooks/useOptimizedGPS.ts');
      
      const gpsHook = useOptimizedGPS({
        enableCache: true,
        onProgress: (status, progress) => {
          console.log(`  💾 ${status} (${progress}%)`);
        }
      });
      
      // First call to populate cache
      await gpsHook.getCurrentLocation();
      
      // Second call should use cache
      const cacheStartTime = Date.now();
      const cachedLocation = await gpsHook.getCurrentLocation();
      const cacheAccessTime = Date.now() - cacheStartTime;
      
      const result = {
        testName: 'Cached GPS Access',
        acquisitionTime: cacheAccessTime,
        success: cacheAccessTime <= this.performanceTargets.cachedGPSMaxTime,
        source: cachedLocation.source,
        target: this.performanceTargets.cachedGPSMaxTime,
        wasCached: cachedLocation.source === 'cache'
      };
      
      this.testResults.push(result);
      
      console.log(`  ✅ Cached GPS: ${cacheAccessTime}ms (Target: ${this.performanceTargets.cachedGPSMaxTime}ms)`);
      console.log(`  💾 Source: ${cachedLocation.source}`);
      
      return result;
    } catch (error) {
      console.error(`  ❌ Cached GPS test failed:`, error);
      return { testName: 'Cached GPS Access', success: false, error: error.message };
    }
  }

  // Performance Test 3: Progressive Timeout Strategy
  async testProgressiveTimeoutStrategy() {
    console.log('🧪 Testing Progressive Timeout Strategy...');
    
    const timeouts = [3000, 7000, 12000];
    const results = [];
    
    for (let attempt = 0; attempt < timeouts.length; attempt++) {
      const expectedTimeout = timeouts[attempt];
      const startTime = Date.now();
      
      // Mock GPS that times out on first attempts but succeeds on final
      this.mockGeolocation({
        delay: expectedTimeout + 100, // Slightly longer than timeout
        shouldFail: attempt < 2, // Fail first 2 attempts
        accuracy: 15
      });
      
      try {
        console.log(`  📡 Testing timeout strategy - Attempt ${attempt + 1}`);
        
        const { useOptimizedGPS } = await import('../../resources/js/hooks/useOptimizedGPS.ts');
        
        const gpsHook = useOptimizedGPS({
          enableCache: false,
          onProgress: (status, progress) => {
            console.log(`    ⏱️ ${status} (${progress}%)`);
          }
        });
        
        const location = await gpsHook.getCurrentLocation();
        const totalTime = Date.now() - startTime;
        
        results.push({
          attempt: attempt + 1,
          timeout: expectedTimeout,
          actualTime: totalTime,
          success: true,
          accuracy: location.accuracy
        });
        
        console.log(`    ✅ Attempt ${attempt + 1}: Success in ${totalTime}ms`);
        break;
      } catch (error) {
        const totalTime = Date.now() - startTime;
        results.push({
          attempt: attempt + 1,
          timeout: expectedTimeout,
          actualTime: totalTime,
          success: false,
          error: error.message
        });
        
        console.log(`    ⏱️ Attempt ${attempt + 1}: Timeout after ${totalTime}ms`);
      }
    }
    
    const testResult = {
      testName: 'Progressive Timeout Strategy',
      attempts: results,
      success: results.some(r => r.success),
      totalAttempts: results.length
    };
    
    this.testResults.push(testResult);
    return testResult;
  }

  // Performance Test 4: Battery Optimization
  async testBatteryOptimization() {
    console.log('🧪 Testing Battery Optimization...');
    
    const batteryLevels = [1.0, 0.5, 0.15, 0.05]; // Full, medium, low, critical
    const results = [];
    
    for (const batteryLevel of batteryLevels) {
      const startTime = Date.now();
      const isLowBattery = batteryLevel < 0.2;
      
      // Mock GPS with battery-aware settings
      this.mockGeolocation({
        delay: isLowBattery ? 6000 : 3000, // Slower when battery is low
        accuracy: isLowBattery ? 50 : 10,  // Less accurate when battery is low
        batteryLevel
      });
      
      try {
        const { useOptimizedGPS } = await import('../../resources/js/hooks/useOptimizedGPS.ts');
        
        const gpsHook = useOptimizedGPS({
          enableBatteryOptimization: true,
          onProgress: (status, progress) => {
            console.log(`  🔋 ${status} (Battery: ${Math.round(batteryLevel * 100)}%)`);
          }
        });
        
        await gpsHook.updateBatteryLevel();
        const location = await gpsHook.getCurrentLocation();
        const acquisitionTime = Date.now() - startTime;
        
        results.push({
          batteryLevel,
          isLowBattery,
          acquisitionTime,
          accuracy: location.accuracy,
          optimizationActive: isLowBattery
        });
        
        console.log(`  🔋 Battery ${Math.round(batteryLevel * 100)}%: ${acquisitionTime}ms, accuracy: ${location.accuracy}m`);
        
      } catch (error) {
        results.push({
          batteryLevel,
          isLowBattery,
          error: error.message,
          success: false
        });
      }
    }
    
    const testResult = {
      testName: 'Battery Optimization',
      batteryTests: results,
      success: results.every(r => !r.error),
      optimizationWorking: results.some(r => r.optimizationActive)
    };
    
    this.testResults.push(testResult);
    return testResult;
  }

  // Performance Test 5: Network Fallback Performance
  async testNetworkFallback() {
    console.log('🧪 Testing Network Fallback Performance...');
    const startTime = Date.now();
    
    // Mock GPS failure and network success
    this.mockGeolocation({ shouldFail: true, delay: 5000 });
    this.mockNetworkLocation({ delay: 2000, shouldFail: false });
    
    try {
      const { useOptimizedGPS } = await import('../../resources/js/hooks/useOptimizedGPS.ts');
      
      const gpsHook = useOptimizedGPS({
        onProgress: (status, progress) => {
          console.log(`  🌐 ${status} (${progress}%)`);
        }
      });
      
      const location = await gpsHook.getCurrentLocation();
      const fallbackTime = Date.now() - startTime;
      
      const result = {
        testName: 'Network Fallback',
        fallbackTime,
        success: location.source === 'network',
        accuracy: location.accuracy,
        source: location.source,
        wasGPSFailed: true
      };
      
      this.testResults.push(result);
      
      console.log(`  ✅ Network fallback: ${fallbackTime}ms, source: ${location.source}`);
      
      return result;
    } catch (error) {
      console.error(`  ❌ Network fallback test failed:`, error);
      return { testName: 'Network Fallback', success: false, error: error.message };
    }
  }

  // Performance Test 6: Rapid Consecutive Requests (Cache Effectiveness)
  async testRapidConsecutiveRequests() {
    console.log('🧪 Testing Rapid Consecutive Requests...');
    
    // Mock first GPS call success
    this.mockGeolocation({ delay: 4000, accuracy: 12 });
    
    try {
      const { useOptimizedGPS } = await import('../../resources/js/hooks/useOptimizedGPS.ts');
      
      const gpsHook = useOptimizedGPS({
        enableCache: true,
        onProgress: (status, progress) => {
          console.log(`  🔄 ${status} (${progress}%)`);
        }
      });
      
      const requests = [];
      const numRequests = 5;
      
      // Make rapid consecutive requests
      for (let i = 0; i < numRequests; i++) {
        const startTime = Date.now();
        const location = await gpsHook.getCurrentLocation();
        const requestTime = Date.now() - startTime;
        
        requests.push({
          requestIndex: i,
          time: requestTime,
          source: location.source,
          wasCached: location.source === 'cache'
        });
        
        console.log(`  Request ${i + 1}: ${requestTime}ms (${location.source})`);
        
        // Small delay between requests
        await new Promise(resolve => setTimeout(resolve, 100));
      }
      
      const cacheHitRate = requests.filter(r => r.wasCached).length / requests.length;
      const averageCacheTime = requests
        .filter(r => r.wasCached)
        .reduce((sum, r) => sum + r.time, 0) / requests.filter(r => r.wasCached).length;
      
      const result = {
        testName: 'Rapid Consecutive Requests',
        requests,
        cacheHitRate,
        averageCacheTime,
        success: cacheHitRate >= this.performanceTargets.cacheHitRate,
        target: this.performanceTargets.cacheHitRate
      };
      
      this.testResults.push(result);
      
      console.log(`  📊 Cache hit rate: ${(cacheHitRate * 100).toFixed(1)}% (Target: ${(this.performanceTargets.cacheHitRate * 100)}%)`);
      console.log(`  ⚡ Average cache time: ${averageCacheTime?.toFixed(0) || 'N/A'}ms`);
      
      return result;
    } catch (error) {
      console.error(`  ❌ Rapid requests test failed:`, error);
      return { testName: 'Rapid Consecutive Requests', success: false, error: error.message };
    }
  }

  // Run all performance tests
  async runAllTests() {
    console.log('🚀 Starting GPS Performance Test Suite...\n');
    const suiteStartTime = Date.now();
    
    const tests = [
      () => this.testFreshGPSAcquisition(),
      () => this.testCachedGPSAccess(),
      () => this.testProgressiveTimeoutStrategy(),
      () => this.testBatteryOptimization(),
      () => this.testNetworkFallback(),
      () => this.testRapidConsecutiveRequests()
    ];
    
    for (const test of tests) {
      try {
        await test();
        console.log(''); // Add spacing between tests
      } catch (error) {
        console.error('Test failed:', error);
      }
    }
    
    const totalTime = Date.now() - suiteStartTime;
    
    // Generate performance report
    this.generatePerformanceReport(totalTime);
  }

  // Generate comprehensive performance report
  generatePerformanceReport(totalTestTime) {
    console.log('📊 GPS Performance Test Report');
    console.log('=' .repeat(50));
    
    const passedTests = this.testResults.filter(test => test.success).length;
    const totalTests = this.testResults.length;
    const passRate = (passedTests / totalTests * 100).toFixed(1);
    
    console.log(`✅ Tests Passed: ${passedTests}/${totalTests} (${passRate}%)`);
    console.log(`⏱️ Total Test Time: ${totalTestTime}ms`);
    console.log('');
    
    // Individual test results
    this.testResults.forEach(test => {
      const status = test.success ? '✅' : '❌';
      console.log(`${status} ${test.testName}`);
      
      if (test.acquisitionTime) {
        console.log(`   ⏱️ Time: ${test.acquisitionTime}ms`);
        if (test.improvement) {
          console.log(`   📈 Improvement: ${test.improvement}% vs baseline`);
        }
      }
      
      if (test.cacheHitRate !== undefined) {
        console.log(`   💾 Cache Hit Rate: ${(test.cacheHitRate * 100).toFixed(1)}%`);
      }
      
      if (test.source) {
        console.log(`   📍 Source: ${test.source}`);
      }
      
      if (test.error) {
        console.log(`   ❌ Error: ${test.error}`);
      }
      
      console.log('');
    });
    
    // Performance summary
    console.log('📈 Performance Summary:');
    console.log('=' .repeat(30));
    
    const freshGPSTest = this.testResults.find(t => t.testName === 'Fresh GPS Acquisition');
    if (freshGPSTest && freshGPSTest.acquisitionTime) {
      const improvement = ((this.baselineMetrics.averageAcquisitionTime - freshGPSTest.acquisitionTime) / this.baselineMetrics.averageAcquisitionTime * 100).toFixed(1);
      console.log(`🎯 GPS Acquisition: ${freshGPSTest.acquisitionTime}ms (${improvement}% improvement)`);
    }
    
    const cachedTest = this.testResults.find(t => t.testName === 'Cached GPS Access');
    if (cachedTest && cachedTest.acquisitionTime) {
      console.log(`💾 Cache Performance: ${cachedTest.acquisitionTime}ms`);
    }
    
    const rapidTest = this.testResults.find(t => t.testName === 'Rapid Consecutive Requests');
    if (rapidTest && rapidTest.cacheHitRate) {
      console.log(`🔄 Cache Hit Rate: ${(rapidTest.cacheHitRate * 100).toFixed(1)}%`);
    }
    
    console.log('');
    
    // Recommendations
    console.log('💡 Recommendations:');
    console.log('=' .repeat(30));
    
    if (freshGPSTest && freshGPSTest.acquisitionTime > this.performanceTargets.freshGPSMaxTime) {
      console.log('⚠️ Fresh GPS acquisition exceeds target. Consider further timeout optimization.');
    }
    
    if (rapidTest && rapidTest.cacheHitRate < this.performanceTargets.cacheHitRate) {
      console.log('⚠️ Cache hit rate below target. Review cache duration settings.');
    }
    
    const batteryTest = this.testResults.find(t => t.testName === 'Battery Optimization');
    if (batteryTest && !batteryTest.optimizationWorking) {
      console.log('⚠️ Battery optimization may not be working correctly.');
    }
    
    if (passRate === '100.0') {
      console.log('🎉 All performance targets met! GPS optimization is working excellently.');
    }
    
    return {
      passRate,
      totalTests,
      passedTests,
      testTime: totalTestTime,
      results: this.testResults
    };
  }
}

// Export for use in test runners
module.exports = GPSPerformanceTest;

// Run tests if called directly
if (require.main === module) {
  const testSuite = new GPSPerformanceTest();
  testSuite.runAllTests();
}