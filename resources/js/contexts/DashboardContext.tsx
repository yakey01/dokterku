/**
 * Dashboard Context Provider
 * Manages and shares dashboard data across all components
 */

import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import dashboardDataService from '../services/dokter/dashboardDataService';

interface DashboardContextType {
  // Data states
  userData: any | null;
  dashboardData: any | null;
  attendanceData: any | null;
  jadwalJagaData: any | null;
  jaspelData: any | null;
  
  // Loading states
  isLoading: {
    user: boolean;
    dashboard: boolean;
    attendance: boolean;
    jadwalJaga: boolean;
    jaspel: boolean;
  };
  
  // Error states
  errors: {
    user: string | null;
    dashboard: string | null;
    attendance: string | null;
    jadwalJaga: string | null;
    jaspel: string | null;
  };
  
  // Actions
  refreshData: (type?: 'all' | 'user' | 'dashboard' | 'attendance' | 'jadwalJaga' | 'jaspel') => Promise<void>;
  clearCache: () => void;
  
  // Metadata
  lastUpdated: Date | null;
  isInitialLoad: boolean;
}

const DashboardContext = createContext<DashboardContextType | undefined>(undefined);

export const useDashboard = () => {
  const context = useContext(DashboardContext);
  if (!context) {
    throw new Error('useDashboard must be used within DashboardProvider');
  }
  return context;
};

interface DashboardProviderProps {
  children: React.ReactNode;
  prefetch?: boolean;
}

export const DashboardProvider: React.FC<DashboardProviderProps> = ({ 
  children, 
  prefetch = true 
}) => {
  const [userData, setUserData] = useState<any | null>(null);
  const [dashboardData, setDashboardData] = useState<any | null>(null);
  const [attendanceData, setAttendanceData] = useState<any | null>(null);
  const [jadwalJagaData, setJadwalJagaData] = useState<any | null>(null);
  const [jaspelData, setJaspelData] = useState<any | null>(null);
  
  const [isLoading, setIsLoading] = useState({
    user: true,
    dashboard: true,
    attendance: true,
    jadwalJaga: true,
    jaspel: true,
  });
  
  const [errors, setErrors] = useState({
    user: null as string | null,
    dashboard: null as string | null,
    attendance: null as string | null,
    jadwalJaga: null as string | null,
    jaspel: null as string | null,
  });
  
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
  const [isInitialLoad, setIsInitialLoad] = useState(true);
  const isMountedRef = useRef(true);
  const fetchPromiseRef = useRef<Promise<any> | null>(null);

  /**
   * Load all dashboard data with progressive loading
   */
  const loadDashboardData = useCallback(async (forceRefresh = false) => {
    // Prevent duplicate fetches
    if (fetchPromiseRef.current && !forceRefresh) {
      console.log('🚫 Fetch already in progress, waiting...');
      return fetchPromiseRef.current;
    }

    const fetchPromise = (async () => {
      try {
        console.log('🚀 Loading dashboard data...');
        
        // Start loading states for critical data only
        if (isMountedRef.current) {
          setIsLoading(prev => ({
            ...prev,
            user: true,
            dashboard: true,
          }));
        }

        // Fetch all data with progressive loading
        const result = await dashboardDataService.fetchAllDashboardData({
          forceRefresh,
          priority: isInitialLoad ? 'critical' : 'normal',
        });

        // Update critical data immediately
        if (isMountedRef.current) {
          setUserData(result.user);
          setDashboardData(result.dashboard);
          setIsLoading(prev => ({
            ...prev,
            user: false,
            dashboard: false,
          }));
          setErrors(prev => ({
            ...prev,
            user: null,
            dashboard: null,
          }));
          
          // Mark initial load complete after critical data
          if (isInitialLoad) {
            setIsInitialLoad(false);
          }
        }

        // Update non-critical data as it arrives
        // These are loaded in background, so we check periodically
        const checkBackgroundData = setInterval(() => {
          if (!isMountedRef.current) {
            clearInterval(checkBackgroundData);
            return;
          }

          if (result.attendance !== null) {
            setAttendanceData(result.attendance);
            setIsLoading(prev => ({ ...prev, attendance: false }));
            setErrors(prev => ({ ...prev, attendance: null }));
          }

          if (result.jadwalJaga !== null) {
            setJadwalJagaData(result.jadwalJaga);
            setIsLoading(prev => ({ ...prev, jadwalJaga: false }));
            setErrors(prev => ({ ...prev, jadwalJaga: null }));
          }

          if (result.jaspel !== null) {
            setJaspelData(result.jaspel);
            setIsLoading(prev => ({ ...prev, jaspel: false }));
            setErrors(prev => ({ ...prev, jaspel: null }));
          }

          // Clear interval when all data is loaded
          if (result.attendance !== null && result.jadwalJaga !== null && result.jaspel !== null) {
            clearInterval(checkBackgroundData);
            setLastUpdated(new Date());
          }
        }, 500);

        // Clear interval after max 30 seconds
        setTimeout(() => clearInterval(checkBackgroundData), 30000);

        return result;
      } catch (error) {
        console.error('❌ Error loading dashboard data:', error);
        
        if (isMountedRef.current) {
          const errorMessage = error instanceof Error ? error.message : 'Unknown error';
          setErrors(prev => ({
            ...prev,
            user: errorMessage,
            dashboard: errorMessage,
          }));
          setIsLoading({
            user: false,
            dashboard: false,
            attendance: false,
            jadwalJaga: false,
            jaspel: false,
          });
        }
        
        throw error;
      } finally {
        fetchPromiseRef.current = null;
      }
    })();

    fetchPromiseRef.current = fetchPromise;
    return fetchPromise;
  }, [isInitialLoad]);

  /**
   * Refresh specific data type or all data
   */
  const refreshData = useCallback(async (
    type: 'all' | 'user' | 'dashboard' | 'attendance' | 'jadwalJaga' | 'jaspel' = 'all'
  ) => {
    console.log(`🔄 Refreshing ${type} data...`);

    if (type === 'all') {
      return loadDashboardData(true);
    }

    // Refresh specific data type
    try {
      setIsLoading(prev => ({ ...prev, [type]: true }));
      setErrors(prev => ({ ...prev, [type]: null }));

      let data;
      switch (type) {
        case 'user':
          data = await dashboardDataService.fetchUserData();
          setUserData(data);
          break;
        case 'dashboard':
          data = await dashboardDataService.fetchDashboardData();
          setDashboardData(data);
          break;
        case 'attendance':
          data = await dashboardDataService.fetchAttendanceData();
          setAttendanceData(data);
          break;
        case 'jadwalJaga':
          data = await dashboardDataService.fetchJadwalJagaData();
          setJadwalJagaData(data);
          break;
        case 'jaspel':
          data = await dashboardDataService.fetchJaspelData();
          setJaspelData(data);
          break;
      }

      setIsLoading(prev => ({ ...prev, [type]: false }));
      setLastUpdated(new Date());
    } catch (error) {
      console.error(`❌ Error refreshing ${type}:`, error);
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      setErrors(prev => ({ ...prev, [type]: errorMessage }));
      setIsLoading(prev => ({ ...prev, [type]: false }));
    }
  }, [loadDashboardData]);

  /**
   * Clear all cache
   */
  const clearCache = useCallback(() => {
    dashboardDataService.clearCache();
    console.log('🗑️ Cache cleared from context');
  }, []);

  // Initial data load
  useEffect(() => {
    isMountedRef.current = true;

    // Load data immediately
    loadDashboardData();

    // Prefetch in background if enabled
    if (prefetch) {
      setTimeout(() => {
        if (isMountedRef.current) {
          dashboardDataService.prefetchData();
        }
      }, 5000);
    }

    return () => {
      isMountedRef.current = false;
      dashboardDataService.cancelAllRequests();
    };
  }, [loadDashboardData, prefetch]);

  // Auto-refresh attendance data every 3 minutes (if visible)
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (!document.hidden && isMountedRef.current) {
        refreshData('attendance').catch(console.warn);
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);

    const refreshInterval = setInterval(() => {
      if (!document.hidden && isMountedRef.current) {
        refreshData('attendance').catch(console.warn);
      }
    }, 3 * 60 * 1000); // 3 minutes

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      clearInterval(refreshInterval);
    };
  }, [refreshData]);

  const contextValue: DashboardContextType = {
    userData,
    dashboardData,
    attendanceData,
    jadwalJagaData,
    jaspelData,
    isLoading,
    errors,
    refreshData,
    clearCache,
    lastUpdated,
    isInitialLoad,
  };

  return (
    <DashboardContext.Provider value={contextValue}>
      {children}
    </DashboardContext.Provider>
  );
};

export default DashboardContext;