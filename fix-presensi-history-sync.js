// Fix untuk sync Presensi History dengan JadwalJaga real-time data
// Patch untuk Presensi.tsx component

// 🎯 SOLUTION 1: Add auto-refresh to Presensi History
const addAutoRefreshToPresensiHistory = () => {
  // Add this to Presensi.tsx useEffect
  const refreshInterval = setInterval(() => {
    console.log('🔄 Auto-refreshing presensi history...');
    
    // Refresh history data
    if (activeTab === 'history') {
      fetchAttendanceHistory(); // Trigger refresh when history tab is active
    }
  }, 60000); // Every 60 seconds (same as JadwalJaga)
  
  return () => clearInterval(refreshInterval);
};

// 🎯 SOLUTION 2: Add cache busting to history API calls
const fixHistoryAPICall = (startDate, endDate, csrfToken) => {
  const cacheBuster = `&refresh=${Date.now()}`; // Add cache buster
  
  return fetch(`/api/v2/dashboards/dokter/presensi?start=${startDate.toISOString().split('T')[0]}&end=${endDate.toISOString().split('T')[0]}${cacheBuster}`, {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
      'Cache-Control': 'no-cache', // Force no cache
      'Pragma': 'no-cache'
    },
    credentials: 'same-origin'
  });
};

// 🎯 SOLUTION 3: Add real-time event listening
const addEventListeningToHistory = () => {
  // Listen for attendance updates from other tabs
  window.addEventListener('storage', (e) => {
    if (e.key === 'attendance_updated' || e.key === 'jadwal_updated') {
      console.log('🔔 Attendance/Jadwal updated in another tab, refreshing history...');
      fetchAttendanceHistory();
    }
  });
  
  // Listen for custom events from JadwalJaga component
  window.addEventListener('jadwal-status-changed', (e) => {
    console.log('🔔 Jadwal status changed, refreshing history...', e.detail);
    fetchAttendanceHistory();
  });
};

// 🎯 SOLUTION 4: Cross-component state synchronization
const syncWithJadwalJaga = () => {
  // Share state between JadwalJaga and Presensi components
  const SHARED_STATE_KEY = 'dokter_attendance_sync';
  
  // When JadwalJaga updates, notify Presensi
  const notifyPresensUpdate = (data) => {
    localStorage.setItem(SHARED_STATE_KEY, JSON.stringify({
      timestamp: Date.now(),
      type: 'jadwal_updated',
      data: data
    }));
    
    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('jadwal-status-changed', {
      detail: { type: 'completed', data }
    }));
  };
  
  // In Presensi component, listen for updates
  const listenForJadwalUpdates = () => {
    const checkForUpdates = () => {
      const stored = localStorage.getItem(SHARED_STATE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        const age = Date.now() - parsed.timestamp;
        
        // If update is recent (< 5 minutes), refresh history
        if (age < 300000) {
          console.log('🔄 Recent jadwal update detected, refreshing history...');
          fetchAttendanceHistory();
        }
      }
    };
    
    // Check immediately and set interval
    checkForUpdates();
    const interval = setInterval(checkForUpdates, 30000); // Check every 30s
    
    return () => clearInterval(interval);
  };
};

// 🎯 IMPLEMENTATION GUIDE
console.log(`
🔧 IMPLEMENTATION STEPS:

1. **Add to Presensi.tsx useEffect:**
   - Auto-refresh interval (60s)
   - Cache busting in API calls
   - Event listeners for cross-component sync

2. **Add to JadwalJaga.tsx:**
   - Dispatch events when status changes to 'completed'
   - Update localStorage when shifts are completed

3. **Update API calls:**
   - Add cache busting parameters
   - Add no-cache headers
   - Force fresh data on tab switches

4. **Add debugging:**
   - Console logs for sync events
   - Timestamp tracking for data freshness
   - Error handling for sync failures

📊 RESULT:
- Both components will show same real-time data
- History tab will refresh when JadwalJaga shows 'completed'
- Users see consistent status across all tabs
`);

// Export for use in components
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    addAutoRefreshToPresensiHistory,
    fixHistoryAPICall,
    addEventListeningToHistory,
    syncWithJadwalJaga
  };
}