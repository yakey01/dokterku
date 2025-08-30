import { useEffect, useMemo } from 'react';
import { useDashboard } from '../providers/DashboardProvider';

/**
 * Custom hook for accessing dashboard data with smart caching and loading states
 * Provides a clean API for components to consume dashboard data
 */
export const useDashboardData = () => {
  const { state, actions, cache } = useDashboard();

  // Auto-fetch data on mount if cache is invalid
  useEffect(() => {
    // Rate limiting protection with exponential backoff
    const fetchWithBackoff = async (fetchFn: () => Promise<void>, maxRetries = 3) => {
      for (let attempt = 0; attempt < maxRetries; attempt++) {
        try {
          await fetchFn();
          break; // Success, exit retry loop
        } catch (error: any) {
          if (error?.response?.status === 429 && attempt < maxRetries - 1) {
            // Rate limited, wait with exponential backoff
            const delay = Math.pow(2, attempt) * 1000; // 1s, 2s, 4s
            console.warn(`🔄 Rate limited, retrying in ${delay}ms... (attempt ${attempt + 1}/${maxRetries})`);
            await new Promise(resolve => setTimeout(resolve, delay));
          } else {
            console.error(`❌ Fetch failed after ${attempt + 1} attempts:`, error);
            break;
          }
        }
      }
    };

    // Re-enabled auto-fetch with proper error handling
    if (!cache.isDashboardCacheValid() || state.metrics.jaspel.currentMonth === 0) {
      console.log('🔄 Auto-fetching dashboard data with rate limiting protection...');
      fetchWithBackoff(() => actions.fetchDashboardData());
    }
    
    if (!cache.isLeaderboardCacheValid() || state.leaderboard.length === 0) {
      console.log('🔄 Auto-fetching leaderboard data with rate limiting protection...');
      // Add delay to prevent simultaneous requests
      setTimeout(() => {
        fetchWithBackoff(() => actions.fetchLeaderboard());
      }, 500);
    }
  }, [cache, state.metrics.jaspel.currentMonth, state.leaderboard.length, actions]);

  // Memoized computed values for performance
  const computedData = useMemo(() => {
    const { metrics, doctorLevel, experiencePoints, dailyStreak, leaderboard, attendanceHistory } = state;
    
    // Determine greeting and visual styling based on time
    const currentHour = new Date().getHours();
    const timeBasedGreeting = {
      greeting: currentHour < 12 ? 'Selamat Pagi' : currentHour < 18 ? 'Selamat Sore' : 'Selamat Malam',
      colorGradient: currentHour < 12 
        ? 'from-yellow-400 via-orange-400 to-red-400' 
        : currentHour < 18 
        ? 'from-blue-400 via-purple-400 to-pink-400'
        : 'from-purple-400 via-indigo-400 to-blue-400'
    };

    // Calculate JASPEL metrics for analytics
    const jaspelMetrics = {
      growth: metrics.jaspel.growthPercentage,
      progress: metrics.jaspel.progressPercentage,
      current: metrics.jaspel.currentMonth,
      previous: metrics.jaspel.previousMonth
    };

    // Attendance display formatting
    const attendanceDisplay = {
      rate: metrics.attendance.rate,
      text: metrics.attendance.displayText,
      daysPresent: metrics.attendance.daysPresent,
      totalDays: metrics.attendance.totalDays
    };

    // Performance summary for quick access
    const performanceSummary = {
      attendanceRate: metrics.attendance.rate,
      patientsThisMonth: metrics.patients.thisMonth,
      jaspelGrowth: metrics.jaspel.growthPercentage,
      level: doctorLevel,
      experience: experiencePoints,
      streak: dailyStreak
    };

    return {
      timeBasedGreeting,
      jaspelMetrics,
      attendanceDisplay,
      performanceSummary,
      leaderboardTop3: leaderboard.slice(0, 3),
      attendanceHistory: attendanceHistory || [],
      hasData: metrics.jaspel.currentMonth > 0 || leaderboard.length > 0
    };
  }, [state]);

  // Debug logging for attendance history
  useEffect(() => {
    console.log('🔍 useDashboardData: attendanceHistory updated:', {
      length: state.attendanceHistory?.length || 0,
      hasData: !!state.attendanceHistory && state.attendanceHistory.length > 0,
      firstRecord: state.attendanceHistory?.[0] || 'none'
    });
  }, [state.attendanceHistory]);

  // Loading states aggregation
  const loadingStates = useMemo(() => ({
    isDashboardLoading: state.isLoading.dashboard,
    isLeaderboardLoading: state.isLoading.leaderboard,
    isAttendanceLoading: state.isLoading.attendance,
    isAnyLoading: state.isLoading.dashboard || state.isLoading.leaderboard || state.isLoading.attendance
  }), [state.isLoading]);

  // Error states aggregation
  const errorStates = useMemo(() => ({
    dashboardError: state.errors.dashboard,
    leaderboardError: state.errors.leaderboard,
    attendanceError: state.errors.attendance,
    hasErrors: !!(state.errors.dashboard || state.errors.leaderboard || state.errors.attendance),
    allErrors: [state.errors.dashboard, state.errors.leaderboard, state.errors.attendance].filter(Boolean)
  }), [state.errors]);

  // Cache status information
  const cacheInfo = useMemo(() => ({
    dashboardCacheValid: cache.isDashboardCacheValid(),
    leaderboardCacheValid: cache.isLeaderboardCacheValid(),
    attendanceCacheValid: cache.isAttendanceCacheValid(),
    lastUpdated: {
      dashboard: new Date(state.lastUpdated.dashboard).toLocaleString('id-ID'),
      leaderboard: new Date(state.lastUpdated.leaderboard).toLocaleString('id-ID'),
      attendance: new Date(state.lastUpdated.attendance).toLocaleString('id-ID')
    }
  }), [cache, state.lastUpdated]);

  // Refresh functions with loading management
  const refreshMethods = useMemo(() => ({
    refreshDashboard: async () => {
      if (!state.isLoading.dashboard) {
        await actions.fetchDashboardData();
      }
    },
    refreshLeaderboard: async () => {
      if (!state.isLoading.leaderboard) {
        await actions.fetchLeaderboard();
      }
    },
    refreshAll: async () => {
      if (!loadingStates.isAnyLoading) {
        await actions.refreshAll();
      }
    },
    clearErrors: actions.clearErrors,
    resetState: actions.resetState
  }), [actions, state.isLoading, loadingStates.isAnyLoading]);

  return {
    // Raw state data
    rawData: {
      metrics: state.metrics,
      leaderboard: state.leaderboard,
      attendanceHistory: state.attendanceHistory,
      doctorLevel: state.doctorLevel,
      experiencePoints: state.experiencePoints,
      dailyStreak: state.dailyStreak
    },
    
    // Computed/formatted data
    computed: computedData,
    
    // Loading states
    loading: loadingStates,
    
    // Error states
    errors: errorStates,
    
    // Cache information
    cache: cacheInfo,
    
    // Actions
    actions: refreshMethods
  };
};

export default useDashboardData;