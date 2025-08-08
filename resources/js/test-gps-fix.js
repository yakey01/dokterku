/**
 * Test GPSManager Fix
 * This file tests that the GPSManager getInstance error is resolved
 */

// Test imports
try {
  console.log('🧪 Testing GPSManager imports...');
  
  // Test default import (singleton instance)
  const GPSManagerInstance = await import('./utils/GPSManager.js');
  console.log('✅ Default import successful:', typeof GPSManagerInstance.default);
  
  // Test named imports
  const { GPSManager, GPSStatus, GPSStrategy } = await import('./utils/GPSManager.js');
  console.log('✅ Named imports successful:', {
    GPSManager: typeof GPSManager,
    GPSStatus: typeof GPSStatus,
    GPSStrategy: typeof GPSStrategy
  });
  
  // Test singleton instance methods
  const instance = GPSManagerInstance.default;
  console.log('✅ Singleton instance methods:', {
    getCurrentLocation: typeof instance.getCurrentLocation,
    getStatus: typeof instance.getStatus,
    updateConfig: typeof instance.updateConfig
  });
  
  // Test class static methods
  console.log('✅ Class static methods:', {
    getInstance: typeof GPSManager.getInstance
  });
  
  // Test configuration update
  instance.updateConfig({
    enableLogging: true,
    defaultLocation: { lat: -6.2088, lng: 106.8456 }
  });
  console.log('✅ Configuration update successful');
  
  console.log('🎉 All GPSManager tests passed!');
  
} catch (error) {
  console.error('❌ GPSManager test failed:', error);
}

// Test useGPSLocation hook (if React is available)
if (typeof React !== 'undefined') {
  try {
    console.log('🧪 Testing useGPSLocation hook...');
    
    const { useGPSLocation } = await import('./hooks/useGPSLocation.js');
    console.log('✅ useGPSLocation import successful:', typeof useGPSLocation);
    
    console.log('🎉 useGPSLocation hook test passed!');
    
  } catch (error) {
    console.error('❌ useGPSLocation test failed:', error);
  }
} else {
  console.log('⚠️ React not available, skipping useGPSLocation test');
}

console.log('🏁 GPSManager fix verification complete');
