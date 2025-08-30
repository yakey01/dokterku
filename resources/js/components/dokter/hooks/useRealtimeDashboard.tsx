import React, { useEffect, useCallback, useRef } from 'react';
import { useDashboard } from '../providers/DashboardProvider';
import { useAttendanceUpdates, useJaspelUpdates, useLeaderboardUpdates, webSocketManager } from '../../../utils/WebSocketManager';
import { cacheManager } from '../../../utils/CacheManager';

/**
 * Advanced real-time dashboard hook with WebSocket integration and hybrid caching
 * Combines state management, WebSocket updates, and intelligent caching
 */
export const useRealtimeDashboard = () => {
  const { state, actions, cache } = useDashboard();
  const isInitialized = useRef(false);
  const lastUpdateTimes = useRef({
    attendance: 0,
    jaspel: 0,
    leaderboard: 0
  });

  // WebSocket connection status
  const attendanceStatus = useAttendanceUpdates(handleAttendanceUpdate);
  const jaspelStatus = useJaspelUpdates(handleJaspelUpdate);
  const leaderboardStatus = useLeaderboardUpdates(handleLeaderboardUpdate);

  /**
   * Handle real-time attendance updates
   */
  function handleAttendanceUpdate(data: any) {
    const now = Date.now();
    
    // Prevent duplicate updates within 1 second
    if (now - lastUpdateTimes.current.attendance < 1000) {
      return;
    }
    
    lastUpdateTimes.current.attendance = now;
    
    console.log('📡 WebSocket: Attendance update received', data);
    
    try {
      // Update cache with real-time data
      if (data.history && Array.isArray(data.history)) {
        cacheManager.update('attendance-history', data.history, 'websocket');
      }
      
      // Update dashboard metrics if attendance rate changed
      if (data.attendance_rate !== undefined) {
        const updatedMetrics = {
          ...state.metrics,
          attendance: {
            ...state.metrics.attendance,
            rate: data.attendance_rate,
            displayText: `${data.attendance_rate}%`
          }
        };
        
        cacheManager.update('dashboard-metrics', updatedMetrics, 'websocket');
        
        // Re-enabled real-time fetch with rate limiting protection
        console.log('📡 Real-time attendance update: Refreshing dashboard data...');
        
        // Add delay to prevent simultaneous requests and rate limiting
        setTimeout(() => {
          actions.fetchDashboardData().catch((error: any) => {
            if (error?.response?.status === 429) {
              console.warn('⚠️ Rate limited during WebSocket update, will retry later');
            } else {
              console.error('❌ WebSocket dashboard refresh failed:', error);
            }
          });
        }, 1000); // 1 second delay
      }
      
    } catch (error) {
      console.error('❌ Failed to process attendance update:', error);
    }
  }

  /**
   * Handle real-time JASPEL updates
   */
  function handleJaspelUpdate(data: any) {
    const now = Date.now();
    
    // Prevent duplicate updates within 1 second
    if (now - lastUpdateTimes.current.jaspel < 1000) {
      return;
    }
    
    lastUpdateTimes.current.jaspel = now;
    
    console.log('📡 WebSocket: JASPEL update received', data);
    
    try {
      // Update JASPEL metrics in cache
      if (data.current_month !== undefined || data.jaspel_amount !== undefined) {
        const updatedMetrics = {
          ...state.metrics,
          jaspel: {
            ...state.metrics.jaspel,
            currentMonth: data.current_month || data.jaspel_amount || state.metrics.jaspel.currentMonth,
            // Recalculate growth if we have the data
            growthPercentage: data.growth_percentage !== undefined 
              ? data.growth_percentage 
              : state.metrics.jaspel.growthPercentage
          }
        };
        
        cacheManager.update('dashboard-metrics', updatedMetrics, 'websocket');
        
        // Show toast notification for JASPEL updates
        if (window.Toastify) {
          window.Toastify({
            text: `💰 JASPEL Updated: Rp ${(data.current_month || data.jaspel_amount || 0).toLocaleString('id-ID')}`,
            duration: 3000,
            gravity: "top",
            position: "right",
            style: {
              background: "linear-gradient(to right, #10b981, #059669)",
            }
          }).showToast();
        }
        
        // Re-enabled JASPEL real-time fetch with rate limiting protection
        console.log('📡 Real-time JASPEL update: Refreshing dashboard data...');
        
        // Add delay to prevent simultaneous requests and rate limiting
        setTimeout(() => {
          actions.fetchDashboardData().catch((error: any) => {
            if (error?.response?.status === 429) {
              console.warn('⚠️ Rate limited during JASPEL WebSocket update, will retry later');
            } else {
              console.error('❌ WebSocket JASPEL refresh failed:', error);
            }
          });
        }, 1500); // 1.5 second delay
      }
      
    } catch (error) {
      console.error('❌ Failed to process JASPEL update:', error);
    }
  }

  /**
   * Handle real-time leaderboard updates
   */
  function handleLeaderboardUpdate(data: any) {
    const now = Date.now();
    
    // Prevent duplicate updates within 5 seconds (leaderboard changes less frequently)
    if (now - lastUpdateTimes.current.leaderboard < 5000) {
      return;
    }
    
    lastUpdateTimes.current.leaderboard = now;
    
    console.log('📡 WebSocket: Leaderboard update received', data);
    
    try {
      if (data.leaderboard && Array.isArray(data.leaderboard)) {
        // Update leaderboard cache
        cacheManager.update('leaderboard-data', data.leaderboard, 'websocket');
        
        // Force refresh leaderboard
        actions.fetchLeaderboard();
        
        // Check if current user's position changed
        const currentUser = data.leaderboard.find((doctor: any) => 
          doctor.is_current_user || doctor.id === window.currentUserId
        );
        
        if (currentUser && window.Toastify) {
          window.Toastify({
            text: `🏆 Leaderboard Updated! You're ranked #${currentUser.rank}`,
            duration: 4000,
            gravity: "top",
            position: "right",
            style: {
              background: "linear-gradient(to right, #3b82f6, #1d4ed8)",
            }
          }).showToast();
        }
      }
      
    } catch (error) {
      console.error('❌ Failed to process leaderboard update:', error);
    }
  }

  /**
   * Enhanced cache-aware data fetching
   */
  const fetchWithCache = useCallback(async (
    cacheKey: string,
    fetcher: () => Promise<any>,
    updateAction: (data: any) => void
  ) => {
    try {
      const data = await cacheManager.get(cacheKey, fetcher, {
        ttl: 10 * 60 * 1000, // 10 minutes
        enableLocalStorage: true,
        persistOffline: true
      });
      
      if (data) {
        updateAction(data);
      }
      
    } catch (error) {
      console.error(`❌ Failed to fetch ${cacheKey}:`, error);
    }
  }, []);

  /**
   * Initialize real-time dashboard with cache restoration
   */
  const initializeRealtimeDashboard = useCallback(async () => {
    if (isInitialized.current) return;
    
    console.log('🚀 Initializing real-time dashboard with hybrid caching...');
    
    try {
      // Try to restore from cache first for immediate UI
      const cachedMetrics = await cacheManager.get('dashboard-metrics', undefined, {
        ttl: 10 * 60 * 1000,
        enableLocalStorage: true
      });
      
      const cachedLeaderboard = await cacheManager.get('leaderboard-data', undefined, {
        ttl: 15 * 60 * 1000,
        enableLocalStorage: true
      });
      
      const cachedAttendance = await cacheManager.get('attendance-history', undefined, {
        ttl: 5 * 60 * 1000,
        enableLocalStorage: true
      });
      
      // Show cached data immediately if available
      if (cachedMetrics) {
        console.log('🏎️ Fast restore: Dashboard metrics from cache');
      }
      
      if (cachedLeaderboard) {
        console.log('🏎️ Fast restore: Leaderboard from cache');
      }
      
      if (cachedAttendance) {
        console.log('🏎️ Fast restore: Attendance history from cache');
      }
      
      // Then fetch fresh data in background
      await Promise.all([
        actions.fetchDashboardData(),
        actions.fetchLeaderboard()
      ]);
      
      isInitialized.current = true;
      
      console.log('✅ Real-time dashboard initialized successfully');
      
    } catch (error) {
      console.error('❌ Failed to initialize real-time dashboard:', error);
    }
  }, [actions]);

  /**
   * Connection status monitoring
   */
  const connectionStatus = React.useMemo(() => {
    const connected = attendanceStatus.connected || jaspelStatus.connected || leaderboardStatus.connected;
    const reconnecting = attendanceStatus.reconnecting || jaspelStatus.reconnecting || leaderboardStatus.reconnecting;
    const totalAttempts = attendanceStatus.attempts + jaspelStatus.attempts + leaderboardStatus.attempts;
    
    return {
      connected,
      reconnecting,
      attempts: totalAttempts,
      health: connected ? 'healthy' : reconnecting ? 'recovering' : 'disconnected'
    };
  }, [attendanceStatus, jaspelStatus, leaderboardStatus]);

  /**
   * Performance monitoring
   */
  const performanceMetrics = React.useMemo(() => {
    const cacheStats = cacheManager.getStats();
    
    return {
      cacheHitRate: cacheStats.hitRate,
      averageResponseTime: cacheStats.averageResponseTime,
      totalRequests: cacheStats.totalRequests,
      storageUsage: cacheStats.storageUsage,
      memoryEntries: cacheStats.memoryHits + cacheStats.localStorageHits,
      websocketHealth: connectionStatus.health
    };
  }, [connectionStatus.health]);

  /**
   * Manual refresh with cache invalidation
   */
  const forceRefresh = useCallback(async () => {
    console.log('🔄 Force refresh: Invalidating all caches...');
    
    // Invalidate relevant caches
    cacheManager.invalidate('dashboard-metrics');
    cacheManager.invalidate('leaderboard-data');
    cacheManager.invalidate('attendance-history');
    
    // Clear dashboard errors
    actions.clearErrors();
    
    // Fetch fresh data
    await actions.refreshAll();
    
    console.log('✅ Force refresh completed');
  }, [actions]);

  /**
   * Offline data management
   */
  const getOfflineData = useCallback(async () => {
    try {
      const [metrics, leaderboard, attendance] = await Promise.all([
        cacheManager.get('dashboard-metrics'),
        cacheManager.get('leaderboard-data'),
        cacheManager.get('attendance-history')
      ]);
      
      return {
        metrics,
        leaderboard,
        attendance,
        isOfflineData: true,
        lastSync: Math.max(
          state.lastUpdated.dashboard,
          state.lastUpdated.leaderboard,
          state.lastUpdated.attendance
        )
      };
    } catch (error) {
      console.error('❌ Failed to get offline data:', error);
      return null;
    }
  }, [state.lastUpdated]);

  // Initialize on mount
  useEffect(() => {
    initializeRealtimeDashboard();
  }, [initializeRealtimeDashboard]);

  // Log WebSocket connection changes
  useEffect(() => {
    console.log('🔌 WebSocket Status:', {
      attendance: attendanceStatus.connected ? '✅' : '❌',
      jaspel: jaspelStatus.connected ? '✅' : '❌',
      leaderboard: leaderboardStatus.connected ? '✅' : '❌',
      overall: connectionStatus.health
    });
  }, [connectionStatus, attendanceStatus, jaspelStatus, leaderboardStatus]);

  return {
    // Connection status
    connectionStatus,
    
    // Performance metrics
    performanceMetrics,
    
    // Actions
    forceRefresh,
    getOfflineData,
    
    // WebSocket manager access for advanced usage
    webSocketManager,
    
    // Cache manager access
    cacheManager,
    
    // Dashboard state (from existing provider)
    dashboardState: state,
    dashboardActions: actions,
    cacheInfo: cache,
    
    // Initialization status
    isInitialized: isInitialized.current
  };
};

export default useRealtimeDashboard;