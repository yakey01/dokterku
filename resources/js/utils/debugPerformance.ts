/**
 * Debug Performance - Analisis bottleneck sebenarnya
 */

export async function debugDashboardFetch(): Promise<[any, any]> {
  console.log('🔍 === PERFORMANCE DEBUG START ===');
  const totalStart = performance.now();
  
  // Test 1: Measure CSRF token retrieval
  const csrfStart = performance.now();
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const csrfTime = performance.now() - csrfStart;
  console.log(`⏱️ CSRF token: ${csrfTime.toFixed(2)}ms`);
  
  // Test 2: Measure header preparation
  const headerStart = performance.now();
  const headers = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrfToken
  };
  const headerTime = performance.now() - headerStart;
  console.log(`⏱️ Headers prep: ${headerTime.toFixed(2)}ms`);
  
  // Test 3: Measure individual API calls
  console.log('📡 Starting API calls...');
  
  // Dashboard API
  const dash1Start = performance.now();
  const dashPromise = fetch('/api/v2/dashboards/dokter', {
    method: 'GET',
    headers,
    credentials: 'same-origin'
  });
  
  // Attendance API  
  const att1Start = performance.now();
  const attPromise = fetch('/api/v2/dashboards/dokter/presensi', {
    method: 'GET',
    headers,
    credentials: 'same-origin'
  });
  
  // Wait for both
  const [dashRes, attRes] = await Promise.all([dashPromise, attPromise]);
  const networkTime = performance.now() - dash1Start;
  console.log(`📡 Network time (parallel): ${networkTime.toFixed(2)}ms`);
  
  // Test 4: Measure JSON parsing
  const parseStart = performance.now();
  const [dashData, attData] = await Promise.all([
    dashRes.json(),
    attRes.json()
  ]);
  const parseTime = performance.now() - parseStart;
  console.log(`📝 JSON parse time: ${parseTime.toFixed(2)}ms`);
  
  const totalTime = performance.now() - totalStart;
  console.log('🔍 === PERFORMANCE DEBUG END ===');
  console.log(`📊 TOTAL TIME: ${totalTime.toFixed(2)}ms`);
  console.log('📊 Breakdown:');
  console.log(`  - CSRF: ${csrfTime.toFixed(2)}ms`);
  console.log(`  - Headers: ${headerTime.toFixed(2)}ms`);
  console.log(`  - Network: ${networkTime.toFixed(2)}ms`);
  console.log(`  - Parsing: ${parseTime.toFixed(2)}ms`);
  console.log(`  - Overhead: ${(totalTime - csrfTime - headerTime - networkTime - parseTime).toFixed(2)}ms`);
  
  // Analyze the bottleneck
  if (networkTime > 100) {
    console.log('🚨 BOTTLENECK: Backend API response time');
    console.log('💡 Solution: Backend optimization needed or use caching');
  } else if (parseTime > 20) {
    console.log('🚨 BOTTLENECK: Large JSON response');
    console.log('💡 Solution: Reduce response size or paginate');
  } else {
    console.log('✅ Frontend optimized, backend is the limiting factor');
  }
  
  return [dashData.data || dashData, attData];
}

// Test single API call speed
export async function testSingleAPISpeed() {
  console.log('🧪 Testing single API speed...');
  
  const start = performance.now();
  const response = await fetch('/api/v2/dashboards/dokter', {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
  });
  const data = await response.json();
  const elapsed = performance.now() - start;
  
  console.log(`🧪 Single API call: ${elapsed.toFixed(2)}ms`);
  
  if (elapsed > 50) {
    console.log('⚠️ Backend API is slow. Consider:');
    console.log('  1. Database query optimization');
    console.log('  2. Add server-side caching');
    console.log('  3. Use Redis/Memcached');
    console.log('  4. Optimize Laravel queries');
  }
  
  return data;
}